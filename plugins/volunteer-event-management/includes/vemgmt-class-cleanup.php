<?php
/**
 * Scheduled cleanup of expired volunteer shifts and events.
 *
 * Runs two passes, in this order:
 *  - Pass 1: force-delete vol_shift posts whose vol_shift_end_date_time is older
 *    than the retention window.
 *  - Pass 2: force-delete vol_event posts whose event_start_date_time is older than
 *    the retention window AND which have no remaining shifts.
 *
 * Pass order matters: clearing expired shifts first is what makes a job whose shifts
 * have all passed eligible for deletion in the same run. It also covers the case
 * where past shifts were never linked into the job's ACF relationship field — they
 * are gone before the emptiness test runs.
 *
 * DESIGN NOTE — Force delete, not trash:
 * Object Sync registers only save_post / acf/save_post for custom post types, with no
 * before_delete_post handler. wp_delete_post( $id, true ) therefore never reaches
 * Salesforce. Trashing would: wp_trash_post() updates post_status through
 * wp_update_post(), which fires save_post, which Object Sync *is* listening to.
 * Force delete is the only option that leaves Salesforce untouched.
 *
 * DESIGN NOTE — Deletion order within a post:
 * The post is deleted first, then its object-sync map row. The reverse order is unsafe:
 * if the map row were removed and the post delete then failed, the next Salesforce pull
 * would see no map row, treat the record as new, and create a duplicate post. Failing
 * with a stale map row is the cheaper mistake, and the pull-side guard in
 * VEMgmt_Object_Sync::sf_pull_allowed() keeps that row from being acted on.
 *
 * Triggered by the vemgmt_cleanup_past_events cron hook (scheduled via wp-crontrol)
 * or by the buttons on the plugin settings page.
 *
 * @package volunteer-event-management
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'VEMgmt_Cleanup' ) ) {

	/**
	 * Class VEMgmt_Cleanup
	 *
	 * @since 2.1.0
	 */
	class VEMgmt_Cleanup {

		/**
		 * Cron hook name. This is the hook to register in wp-crontrol.
		 */
		const CRON_HOOK = 'vemgmt_cleanup_past_events';

		/**
		 * Option holding the summary of the most recent run.
		 *
		 * Deliberately a separate option rather than a key inside vemgmt_settings:
		 * VEMgmt_Settings::sanitize_settings() rebuilds its output array from scratch,
		 * so anything stored there that is not an explicitly registered field is
		 * discarded the next time an admin saves the settings page.
		 */
		const LOG_OPTION = 'vemgmt_cleanup_last_run';

		/**
		 * Default retention window in days.
		 */
		const DEFAULT_RETENTION_DAYS = 7;

		/**
		 * Default maximum deletions per run.
		 */
		const DEFAULT_BATCH_SIZE = 1000;

		/**
		 * The class instance.
		 *
		 * @var VEMgmt_Cleanup
		 */
		private static $instance;

		/**
		 * Returns the single instance.
		 *
		 * @return VEMgmt_Cleanup
		 */
		public static function instance() {
			if ( ! isset( self::$instance ) ) {
				self::$instance = new VEMgmt_Cleanup();
				self::$instance->setup_hooks();
			}
			return self::$instance;
		}

		/**
		 * Register WordPress hooks.
		 */
		private function setup_hooks() {
			add_action( self::CRON_HOOK, array( $this, 'run_scheduled' ) );
			add_action( 'wp_ajax_vemgmt_run_cleanup', array( $this, 'ajax_run' ) );
		}

		// -------------------------------------------------------------------------
		// Entry points
		// -------------------------------------------------------------------------

		/**
		 * Cron callback. Takes no arguments — wp-crontrol should schedule this hook
		 * with no PHP arguments.
		 */
		public function run_scheduled() {
			$this->run( array( 'trigger' => 'cron' ) );
		}

		/**
		 * admin-ajax callback for the settings page buttons.
		 *
		 * Defaults to a dry run unless the request explicitly asks for a real one, so a
		 * malformed or truncated request can never delete anything.
		 */
		public function ajax_run() {
			check_ajax_referer( 'vemgmt_run_cleanup', 'nonce' );

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error(
					array( 'message' => __( 'Insufficient permissions.', 'volunteer-event-management' ) ),
					403
				);
			}

			$requested = isset( $_POST['dry_run'] )
				? sanitize_text_field( wp_unslash( $_POST['dry_run'] ) )
				: '1';

			wp_send_json_success(
				$this->run(
					array(
						'trigger' => 'manual',
						'dry_run' => ( '0' !== $requested ),
					)
				)
			);
		}

		// -------------------------------------------------------------------------
		// The run itself
		// -------------------------------------------------------------------------

		/**
		 * Execute a cleanup run and record its summary.
		 *
		 * @param array $args {
		 *     Optional overrides.
		 *
		 *     @type string $trigger 'cron' or 'manual'. Default 'cron'.
		 *     @type bool   $dry_run Override the stored dry-run setting.
		 * }
		 * @return array Summary of what happened, as stored in self::LOG_OPTION.
		 */
		public function run( array $args = array() ) {
			$started  = microtime( true );
			$settings = get_option( 'vemgmt_settings', array() );

			$trigger = isset( $args['trigger'] ) ? $args['trigger'] : 'cron';

			// Dry run defaults to ON until an admin has explicitly saved it off, so
			// activating the plugin update never deletes anything unattended.
			if ( isset( $args['dry_run'] ) ) {
				$dry_run = (bool) $args['dry_run'];
			} elseif ( array_key_exists( 'cleanup_dry_run', $settings ) ) {
				$dry_run = (bool) $settings['cleanup_dry_run'];
			} else {
				$dry_run = true;
			}

			$retention_days = max( 0, (int) ( $settings['cleanup_retention_days'] ?? self::DEFAULT_RETENTION_DAYS ) );
			$batch_size     = max( 1, (int) ( $settings['cleanup_batch_size'] ?? self::DEFAULT_BATCH_SIZE ) );

			$summary = array(
				'timestamp'        => current_time( 'mysql' ),
				'trigger'          => $trigger,
				'dry_run'          => $dry_run,
				'ran'              => false,
				'retention_days'   => $retention_days,
				'cutoff'           => '',
				'shifts_matched'   => 0,
				'shifts_deleted'   => 0,
				'jobs_matched'     => 0,
				'jobs_deleted'     => 0,
				'map_rows_deleted' => 0,
				'skipped_no_date'  => array(),
				'delete_failures'  => array(),
				'budget_exhausted' => false,
				'deferred'         => 0,
				'duration_seconds' => 0,
			);

			// The scheduled run respects the enabled toggle. A manual run from the
			// settings page is a deliberate act and always executes.
			//
			// Deliberately does NOT write the log: while cleanup is disabled the cron
			// still fires weekly, and recording each no-op would overwrite the summary
			// of the last run that actually did something — usually the preview an
			// admin just generated and wants to read.
			if ( 'cron' === $trigger && empty( $settings['cleanup_enabled'] ) ) {
				$summary['duration_seconds'] = round( microtime( true ) - $started, 3 );
				return $summary;
			}

			$summary['ran']    = true;
			$summary['cutoff'] = current_datetime()
				->modify( '-' . $retention_days . ' days' )
				->format( 'Y-m-d H:i:s' );

			// One budget shared across both passes. If pass 1 consumes it, pass 2 simply
			// defers to the next run — a job is only ever deleted after its shifts are
			// confirmed gone, so deferring is safe rather than merely tolerable.
			$budget = $batch_size;

			$this->delete_past_shifts( $summary['cutoff'], $budget, $dry_run, $summary );
			$this->delete_orphaned_jobs( $summary['cutoff'], $budget, $dry_run, $summary );

			$summary['duration_seconds'] = round( microtime( true ) - $started, 3 );
			$this->write_log( $summary );

			return $summary;
		}

		/**
		 * Pass 1 — delete shifts whose end time is past the retention cutoff.
		 *
		 * @param string $cutoff  Pacific-local 'Y-m-d H:i:s' cutoff.
		 * @param int    $budget  Remaining deletions allowed this run, modified in place.
		 * @param bool   $dry_run When true, count but do not delete.
		 * @param array  $summary Run summary, modified in place.
		 */
		private function delete_past_shifts( $cutoff, &$budget, $dry_run, array &$summary ) {
			$shift_ids = get_posts(
				array(
					'post_type'      => 'vol_shift',
					'post_status'    => $this->post_statuses(),
					'posts_per_page' => -1,
					'fields'         => 'ids',
					'no_found_rows'  => true,
					'meta_query'     => array(
						array(
							'key'     => 'vol_shift_end_date_time',
							'value'   => $cutoff,
							'compare' => '<',
							'type'    => 'DATETIME',
						),
					),
				)
			);

			$summary['shifts_matched'] = count( $shift_ids );

			foreach ( $shift_ids as $shift_id ) {
				if ( $budget <= 0 ) {
					$summary['budget_exhausted'] = true;
					$summary['deferred']++;
					continue;
				}

				if ( $this->delete_post_and_map( (int) $shift_id, 'vol_shift', $dry_run, $summary ) ) {
					$summary['shifts_deleted']++;
					$budget--;
				}
			}

			// The DATETIME comparison above silently excludes empty and malformed values
			// (CAST('' AS DATETIME) is NULL, and NULL < x is never true). Surface those
			// separately so they are skipped with a reason rather than invisibly.
			$summary['skipped_no_date'] = array_merge(
				$summary['skipped_no_date'],
				$this->find_posts_missing_date( 'vol_shift', 'vol_shift_end_date_time', $cutoff )
			);
		}

		/**
		 * Pass 2 — delete past-dated jobs that no longer have any shifts.
		 *
		 * @param string $cutoff  Pacific-local 'Y-m-d H:i:s' cutoff.
		 * @param int    $budget  Remaining deletions allowed this run, modified in place.
		 * @param bool   $dry_run When true, count but do not delete.
		 * @param array  $summary Run summary, modified in place.
		 */
		private function delete_orphaned_jobs( $cutoff, &$budget, $dry_run, array &$summary ) {
			$job_ids = get_posts(
				array(
					'post_type'      => 'vol_event',
					'post_status'    => $this->post_statuses(),
					'posts_per_page' => -1,
					'fields'         => 'ids',
					'no_found_rows'  => true,
					'meta_query'     => array(
						array(
							'key'     => 'event_start_date_time',
							'value'   => $cutoff,
							'compare' => '<',
							'type'    => 'DATETIME',
						),
					),
				)
			);

			foreach ( $job_ids as $job_id ) {
				if ( $this->has_remaining_shifts( (int) $job_id ) ) {
					continue;
				}

				$summary['jobs_matched']++;

				if ( $budget <= 0 ) {
					$summary['budget_exhausted'] = true;
					$summary['deferred']++;
					continue;
				}

				if ( $this->delete_post_and_map( (int) $job_id, 'vol_event', $dry_run, $summary ) ) {
					$summary['jobs_deleted']++;
					$budget--;
				}
			}

			$summary['skipped_no_date'] = array_merge(
				$summary['skipped_no_date'],
				$this->find_posts_missing_date( 'vol_event', 'event_start_date_time', $cutoff )
			);
		}

		// -------------------------------------------------------------------------
		// Helpers
		// -------------------------------------------------------------------------

		/**
		 * Does this job still have any shifts attached?
		 *
		 * Reads the ACF relationship field rather than the vol_shift_job_id meta so that
		 * "has shifts" means the same thing here as it does to the front end
		 * (VEMgmt_Helpers::get_shift_data_for_job reads the same field).
		 *
		 * ACF does not prune deleted post IDs out of the stored relationship value, but
		 * acf_field_relationship::format_value() resolves them through acf_get_posts(),
		 * so get_field() returns only posts that still exist. That filtering only happens
		 * when the field's return format is 'object' — which it is, and which both
		 * existing consumers depend on.
		 *
		 * @param int $job_id vol_event post ID.
		 * @return bool True if shifts remain, or if the answer cannot be determined.
		 */
		private function has_remaining_shifts( $job_id ) {
			// Without ACF the relationship cannot be read at all. Report "has shifts" so
			// an unverifiable job is never deleted.
			if ( ! function_exists( 'get_field' ) ) {
				return true;
			}

			$shifts = get_field( 'event_shifts', $job_id );

			return ! empty( $shifts );
		}

		/**
		 * Delete a post and then its object-sync map row.
		 *
		 * @param int    $post_id   Post to delete.
		 * @param string $post_type Post type, used to target the map row.
		 * @param bool   $dry_run   When true, report success without deleting.
		 * @param array  $summary   Run summary, modified in place.
		 * @return bool True when the post was deleted (or would have been).
		 */
		private function delete_post_and_map( $post_id, $post_type, $dry_run, array &$summary ) {
			if ( $dry_run ) {
				return true;
			}

			// Post first — see the DESIGN NOTE at the top of this file.
			$deleted = wp_delete_post( $post_id, true );

			if ( ! $deleted ) {
				$summary['delete_failures'][] = $post_id;
				return false;
			}

			$summary['map_rows_deleted'] += $this->delete_object_map_row( $post_id, $post_type );

			return true;
		}

		/**
		 * Remove the object-sync map row for a deleted post.
		 *
		 * Leaving it behind would leave a row pointing at a post ID that no longer
		 * exists, which Object Sync would try to update on every subsequent pull.
		 *
		 * @param int    $post_id   The deleted post ID.
		 * @param string $post_type The deleted post's type.
		 * @return int Number of map rows removed.
		 */
		private function delete_object_map_row( $post_id, $post_type ) {
			if ( ! $this->map_table_exists() ) {
				return 0;
			}

			global $wpdb;

			// wordpress_id is varchar(32), not an integer column — bind it as a string.
			$deleted = $wpdb->delete(
				$wpdb->prefix . 'object_sync_sf_object_map',
				array(
					'wordpress_id'     => (string) $post_id,
					'wordpress_object' => $post_type,
				),
				array( '%s', '%s' )
			);

			return (int) $deleted;
		}

		/**
		 * Is the Object Sync for Salesforce map table present?
		 *
		 * Cached per request — this is called once per deleted post.
		 *
		 * @return bool
		 */
		private function map_table_exists() {
			static $exists = null;

			if ( null !== $exists ) {
				return $exists;
			}

			global $wpdb;

			$table  = $wpdb->prefix . 'object_sync_sf_object_map';
			$found  = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
			$exists = ( $found === $table );

			return $exists;
		}

		/**
		 * Find posts whose date meta is missing or empty, so they can be reported
		 * rather than silently ignored by the DATETIME comparison.
		 *
		 * Restricted to posts created before the cutoff. A record synced yesterday may
		 * simply not have its date yet — reporting those would bury the genuinely stuck
		 * records in churn, and the point of this list is that it stays actionable.
		 *
		 * @param string $post_type Post type to search.
		 * @param string $meta_key  Date meta key to check.
		 * @param string $cutoff    Pacific-local 'Y-m-d H:i:s' cutoff.
		 * @return int[] Post IDs.
		 */
		private function find_posts_missing_date( $post_type, $meta_key, $cutoff ) {
			return get_posts(
				array(
					'post_type'      => $post_type,
					'post_status'    => $this->post_statuses(),
					'posts_per_page' => -1,
					'fields'         => 'ids',
					'no_found_rows'  => true,
					'date_query'     => array(
						array(
							'column' => 'post_date',
							'before' => $cutoff,
						),
					),
					'meta_query'     => array(
						'relation' => 'OR',
						array(
							'key'     => $meta_key,
							'compare' => 'NOT EXISTS',
						),
						array(
							'key'     => $meta_key,
							'value'   => '',
							'compare' => '=',
						),
					),
				)
			);
		}

		/**
		 * Post statuses the cleanup considers.
		 *
		 * Explicit rather than 'any': the default is publish-only, and 'any' omits
		 * trashed posts — which are exactly the stale rows worth clearing.
		 *
		 * @return string[]
		 */
		private function post_statuses() {
			return array( 'publish', 'pending', 'draft', 'future', 'private', 'trash' );
		}

		/**
		 * Store the run summary. Not autoloaded — it is only read on the settings page.
		 *
		 * @param array $summary Run summary.
		 */
		private function write_log( array $summary ) {
			update_option( self::LOG_OPTION, $summary, false );
		}
	}
}

VEMgmt_Cleanup::instance();
