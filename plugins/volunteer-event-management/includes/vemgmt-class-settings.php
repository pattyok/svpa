<?php
/**
 * Settings page for Volunteer Event Management plugin.
 *
 * Adds a Settings submenu under the Volunteer Events admin menu.
 * Options are stored as a single array in the `vemgmt_settings` WP option.
 *
 * @package volunteer-event-management
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'VEMgmt_Settings' ) ) {

	/**
	 * Class VEMgmt_Settings
	 *
	 * @since 2.0.0
	 */
	class VEMgmt_Settings {

		/**
		 * The class instance.
		 *
		 * @var VEMgmt_Settings
		 */
		private static $instance;

		/**
		 * Returns the single instance.
		 *
		 * @return VEMgmt_Settings
		 */
		public static function instance() {
			if ( ! isset( self::$instance ) ) {
				self::$instance = new VEMgmt_Settings();
				self::$instance->setup_hooks();
			}
			return self::$instance;
		}

		/**
		 * Register WordPress hooks.
		 */
		private function setup_hooks() {
			add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
			add_action( 'admin_init', array( $this, 'register_settings' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_settings_scripts' ) );
		}

		/**
		 * Add Settings submenu under the Volunteer Events post type menu.
		 */
		public function add_settings_page() {
			add_submenu_page(
				'edit.php?post_type=vol_event',
				__( 'Volunteer Event Settings', 'volunteer-event-management' ),
				__( 'Settings', 'volunteer-event-management' ),
				'manage_options',
				'vemgmt-settings',
				array( $this, 'render_page' )
			);
		}

		/**
		 * Enqueue admin scripts for the settings page only.
		 *
		 * Adds a small inline script that toggles the "Cutoff Hours" field
		 * based on the selected Cutoff Type, avoiding a full JS file for minimal logic.
		 *
		 * @param string $hook_suffix The current admin page hook suffix.
		 */
		public function enqueue_settings_scripts( string $hook_suffix ): void {
			// The hook suffix for add_submenu_page under vol_event CPT with slug vemgmt-settings.
			if ( 'vol_event_page_vemgmt-settings' !== $hook_suffix ) {
				return;
			}
			wp_add_inline_script(
				'jquery',
				"(function($){
					function toggleCutoffHours() {
						var row = $('#vemgmt_cutoff_hours').closest('tr');
						( 'x_hours_before' === $('#vemgmt_cutoff_type').val() )
							? row.show()
							: row.hide();
					}
					$(document).ready(function(){
						toggleCutoffHours();
						$('#vemgmt_cutoff_type').on('change', toggleCutoffHours);
					});
				})(jQuery);"
			);

			$confirm = __( 'This permanently deletes past shifts and events. It cannot be undone. Continue?', 'volunteer-event-management' );

			wp_add_inline_script(
				'jquery',
				'(function($){
					var nonce   = ' . wp_json_encode( wp_create_nonce( 'vemgmt_run_cleanup' ) ) . ';
					var ajaxUrl = ' . wp_json_encode( admin_url( 'admin-ajax.php' ) ) . ';
					var confirmMsg = ' . wp_json_encode( $confirm ) . ';

					function runCleanup( dryRun ) {
						var $spinner = $("#vemgmt-cleanup-spinner").addClass("is-active");
						var $result  = $("#vemgmt-cleanup-result").empty();

						$("#vemgmt-cleanup-preview, #vemgmt-cleanup-run").prop("disabled", true);

						$.post( ajaxUrl, {
							action:  "vemgmt_run_cleanup",
							nonce:   nonce,
							dry_run: dryRun ? "1" : "0"
						} ).done( function( response ) {
							if ( ! response || ! response.success ) {
								$result.html( "<div class=\'notice notice-error inline\'><p>" +
									"Cleanup failed. Check the PHP error log." + "</p></div>" );
								return;
							}
							var d = response.data;
							$result.html( "<div class=\'notice notice-success inline\'><p>" +
								( d.dry_run ? "Dry run: " : "Deleted: " ) +
								d.shifts_deleted + " of " + d.shifts_matched + " shift(s), " +
								d.jobs_deleted + " of " + d.jobs_matched + " event(s). " +
								"Reload the page to refresh the Last Run details." +
								"</p></div>" );
						} ).fail( function() {
							$result.html( "<div class=\'notice notice-error inline\'><p>" +
								"The request did not complete." + "</p></div>" );
						} ).always( function() {
							$spinner.removeClass("is-active");
							$("#vemgmt-cleanup-preview, #vemgmt-cleanup-run").prop("disabled", false);
						} );
					}

					$(document).ready(function(){
						$("#vemgmt-cleanup-preview").on("click", function(){
							runCleanup( true );
						});
						$("#vemgmt-cleanup-run").on("click", function(){
							if ( window.confirm( confirmMsg ) ) {
								runCleanup( false );
							}
						});
					});
				})(jQuery);'
			);
		}

		/**
		 * Register settings, sections, and fields.
		 */
		public function register_settings() {
			register_setting(
				'vemgmt_settings_group',
				'vemgmt_settings',
				array( $this, 'sanitize_settings' )
			);

			// ── Gravity Forms section ──────────────────────────────────────────────
			add_settings_section(
				'vemgmt_gf_section',
				__( 'Gravity Forms Integration', 'volunteer-event-management' ),
				array( $this, 'render_gf_section_description' ),
				'vemgmt-settings'
			);

			add_settings_field(
				'vemgmt_form_id',
				__( 'Volunteer Registration Form ID', 'volunteer-event-management' ),
				array( $this, 'render_form_id_field' ),
				'vemgmt-settings',
				'vemgmt_gf_section'
			);

			add_settings_field(
				'vemgmt_capacity_error_message',
				__( 'Shift Full Error Message', 'volunteer-event-management' ),
				array( $this, 'render_error_message_field' ),
				'vemgmt-settings',
				'vemgmt_gf_section'
			);

			// ── Registration section ───────────────────────────────────────────────
			add_settings_section(
				'vemgmt_registration_section',
				__( 'Registration Settings', 'volunteer-event-management' ),
				array( $this, 'render_registration_section_description' ),
				'vemgmt-settings'
			);

			add_settings_field(
				'vemgmt_registration_base_url',
				__( 'Registration Page URL', 'volunteer-event-management' ),
				array( $this, 'render_registration_base_url_field' ),
				'vemgmt-settings',
				'vemgmt_registration_section'
			);

			add_settings_field(
				'vemgmt_cutoff_type',
				__( 'Registration Cutoff Type', 'volunteer-event-management' ),
				array( $this, 'render_cutoff_type_field' ),
				'vemgmt-settings',
				'vemgmt_registration_section'
			);

			add_settings_field(
				'vemgmt_cutoff_hours',
				__( 'Cutoff Hours Before Shift', 'volunteer-event-management' ),
				array( $this, 'render_cutoff_hours_field' ),
				'vemgmt-settings',
				'vemgmt_registration_section'
			);

			add_settings_field(
				'vemgmt_registration_closed_message',
				__( 'Registration Closed Message', 'volunteer-event-management' ),
				array( $this, 'render_registration_closed_message_field' ),
				'vemgmt-settings',
				'vemgmt_registration_section'
			);

			// ── Data cleanup section ───────────────────────────────────────────────
			add_settings_section(
				'vemgmt_cleanup_section',
				__( 'Data Cleanup', 'volunteer-event-management' ),
				array( $this, 'render_cleanup_section_description' ),
				'vemgmt-settings'
			);

			add_settings_field(
				'vemgmt_cleanup_enabled',
				__( 'Enable Scheduled Cleanup', 'volunteer-event-management' ),
				array( $this, 'render_cleanup_enabled_field' ),
				'vemgmt-settings',
				'vemgmt_cleanup_section'
			);

			add_settings_field(
				'vemgmt_cleanup_dry_run',
				__( 'Dry Run', 'volunteer-event-management' ),
				array( $this, 'render_cleanup_dry_run_field' ),
				'vemgmt-settings',
				'vemgmt_cleanup_section'
			);

			add_settings_field(
				'vemgmt_cleanup_retention_days',
				__( 'Retention (Days)', 'volunteer-event-management' ),
				array( $this, 'render_cleanup_retention_days_field' ),
				'vemgmt-settings',
				'vemgmt_cleanup_section'
			);

			add_settings_field(
				'vemgmt_cleanup_batch_size',
				__( 'Maximum Deletions Per Run', 'volunteer-event-management' ),
				array( $this, 'render_cleanup_batch_size_field' ),
				'vemgmt-settings',
				'vemgmt_cleanup_section'
			);
		}

		/**
		 * Sanitize settings on save.
		 *
		 * @param array $input Raw input values.
		 * @return array Sanitized values.
		 */
		public function sanitize_settings( $input ) {
			$output = array();

			// ── Gravity Forms fields ───────────────────────────────────────────────
			$output['form_id']                = absint( $input['form_id'] ?? 0 );
			$output['capacity_error_message'] = sanitize_text_field( $input['capacity_error_message'] ?? '' );

			// ── Registration fields ────────────────────────────────────────────────

			// URL: use esc_url_raw() — normalizes the URL without HTML-encoding ampersands,
			// so the stored value remains usable as a raw URL in add_query_arg().
			$output['registration_base_url'] = esc_url_raw( $input['registration_base_url'] ?? '/volunteer-registration/' );

			// Cutoff type: allowlist validation — sanitize_text_field() would allow arbitrary
			// strings to be stored, causing the switch() in compute_cutoff() to silently fall through.
			$allowed_cutoff_types  = array( 'start_of_event', 'day_before_5pm', 'x_hours_before' );
			$raw_cutoff_type       = $input['cutoff_type'] ?? 'day_before_5pm';
			$output['cutoff_type'] = in_array( $raw_cutoff_type, $allowed_cutoff_types, true )
				? $raw_cutoff_type
				: 'day_before_5pm';

			// Cutoff hours: absint() + floor of 1 — a value of 0 or negative would produce
			// a cutoff in the future or present, effectively disabling the guard.
			$output['cutoff_hours'] = max( 1, absint( $input['cutoff_hours'] ?? 24 ) );

			// Closed message: plain text, no HTML.
			$output['registration_closed_message'] = sanitize_text_field( $input['registration_closed_message'] ?? '' );

			// ── Data cleanup fields ────────────────────────────────────────────────
			// NOTE: every key must be copied through explicitly. This method rebuilds
			// $output from scratch, so anything omitted here is silently dropped the
			// next time an admin saves this page.
			$output['cleanup_enabled'] = ! empty( $input['cleanup_enabled'] );
			$output['cleanup_dry_run'] = ! empty( $input['cleanup_dry_run'] );

			// Retention: 0 is meaningful (delete as soon as the window passes), so the
			// floor is 0 rather than 1.
			$output['cleanup_retention_days'] = max( 0, absint( $input['cleanup_retention_days'] ?? 7 ) );

			// Batch size: a value of 0 would delete nothing and look like a silent failure.
			$output['cleanup_batch_size'] = max( 1, absint( $input['cleanup_batch_size'] ?? 1000 ) );

			return $output;
		}

		/**
		 * Render the Gravity Forms section description.
		 */
		public function render_gf_section_description() {
			echo '<p>';
			esc_html_e(
				'Configure the Gravity Forms form used for volunteer shift registrations and the message shown when a shift is full.',
				'volunteer-event-management'
			);
			echo '</p>';
			echo '<p>';
			printf(
				/* translators: 1: opening strong tag, 2: closing strong tag */
				esc_html__( '%1$sSetup required:%2$s In the Gravity Forms editor, add a Hidden field to your registration form. Set its Admin Label to %1$swp_shift_id%2$s and its Parameter Name to %1$swp_shift_id%2$s. This field carries the volunteer shift post ID to the server for capacity validation.', 'volunteer-event-management' ),
				'<strong>',
				'</strong>'
			);
			echo '</p>';
		}

		/**
		 * Render the Registration section description.
		 */
		public function render_registration_section_description() {
			echo '<p>';
			esc_html_e(
				'Configure the registration page URL and when registration closes for each shift.',
				'volunteer-event-management'
			);
			echo '</p>';
		}

		/**
		 * Render the form ID field.
		 */
		public function render_form_id_field() {
			$settings = get_option( 'vemgmt_settings', array() );
			$form_id  = absint( $settings['form_id'] ?? 0 );
			?>
			<input
				type="number"
				min="0"
				name="vemgmt_settings[form_id]"
				id="vemgmt_form_id"
				value="<?php echo esc_attr( $form_id ); ?>"
				class="small-text"
			/>
			<p class="description">
				<?php esc_html_e( 'Enter the numeric ID of the Gravity Forms form used for volunteer shift registrations.', 'volunteer-event-management' ); ?>
			</p>
			<?php
		}

		/**
		 * Render the shift full error message field.
		 */
		public function render_error_message_field() {
			$settings = get_option( 'vemgmt_settings', array() );
			$message  = $settings['capacity_error_message'] ?? '';
			if ( empty( $message ) ) {
				$message = __( 'Sorry, this shift is now full. Please choose another shift.', 'volunteer-event-management' );
			}
			?>
			<input
				type="text"
				name="vemgmt_settings[capacity_error_message]"
				id="vemgmt_capacity_error_message"
				value="<?php echo esc_attr( $message ); ?>"
				class="regular-text"
			/>
			<p class="description">
				<?php esc_html_e( 'Message shown to volunteers when a shift has no available spots at the time they submit the registration form.', 'volunteer-event-management' ); ?>
			</p>
			<?php
		}

		/**
		 * Render the registration base URL field.
		 */
		public function render_registration_base_url_field() {
			$settings = get_option( 'vemgmt_settings', array() );
			$url      = $settings['registration_base_url'] ?? '/volunteer-registration/';
			?>
			<input
				type="url"
				name="vemgmt_settings[registration_base_url]"
				id="vemgmt_registration_base_url"
				value="<?php echo esc_url( $url ); ?>"
				class="regular-text"
			/>
			<p class="description">
				<?php esc_html_e( 'The URL of the volunteer registration page. Shift query parameters are appended automatically.', 'volunteer-event-management' ); ?>
			</p>
			<?php
		}

		/**
		 * Render the cutoff type select field.
		 */
		public function render_cutoff_type_field() {
			$settings    = get_option( 'vemgmt_settings', array() );
			$cutoff_type = $settings['cutoff_type'] ?? 'day_before_5pm';
			?>
			<select name="vemgmt_settings[cutoff_type]" id="vemgmt_cutoff_type">
				<option value="start_of_event" <?php selected( $cutoff_type, 'start_of_event' ); ?>>
					<?php esc_html_e( 'At shift start time', 'volunteer-event-management' ); ?>
				</option>
				<option value="day_before_5pm" <?php selected( $cutoff_type, 'day_before_5pm' ); ?>>
					<?php esc_html_e( '5:00 PM the day before the shift', 'volunteer-event-management' ); ?>
				</option>
				<option value="x_hours_before" <?php selected( $cutoff_type, 'x_hours_before' ); ?>>
					<?php esc_html_e( 'X hours before shift start', 'volunteer-event-management' ); ?>
				</option>
			</select>
			<p class="description">
				<?php esc_html_e( 'When registration closes for all shifts. This setting applies site-wide.', 'volunteer-event-management' ); ?>
			</p>
			<?php
		}

		/**
		 * Render the cutoff hours field.
		 * Shown/hidden via JavaScript based on the cutoff type selection.
		 */
		public function render_cutoff_hours_field() {
			$settings     = get_option( 'vemgmt_settings', array() );
			$cutoff_hours = max( 1, absint( $settings['cutoff_hours'] ?? 24 ) );
			?>
			<input
				type="number"
				min="1"
				name="vemgmt_settings[cutoff_hours]"
				id="vemgmt_cutoff_hours"
				value="<?php echo esc_attr( $cutoff_hours ); ?>"
				class="small-text"
			/>
			<p class="description">
				<?php esc_html_e( 'Number of hours before shift start when registration closes. Only used when "X hours before shift start" is selected above.', 'volunteer-event-management' ); ?>
			</p>
			<?php
		}

		/**
		 * Render the registration closed message field.
		 */
		public function render_registration_closed_message_field() {
			$settings = get_option( 'vemgmt_settings', array() );
			$message  = $settings['registration_closed_message'] ?? '';
			if ( empty( $message ) ) {
				$message = __( 'Registration is closed for this event', 'volunteer-event-management' );
			}
			?>
			<input
				type="text"
				name="vemgmt_settings[registration_closed_message]"
				id="vemgmt_registration_closed_message"
				value="<?php echo esc_attr( $message ); ?>"
				class="regular-text"
			/>
			<p class="description">
				<?php esc_html_e( 'Message shown when the registration cutoff time has passed for a shift.', 'volunteer-event-management' ); ?>
			</p>
			<?php
		}

		/**
		 * Render the Data Cleanup section description.
		 */
		public function render_cleanup_section_description() {
			echo '<p>';
			esc_html_e(
				'Permanently deletes expired volunteer data. Shifts are removed once their end time is older than the retention window; events are removed once their start date is older than the retention window and they have no shifts left. Deletions cannot be undone.',
				'volunteer-event-management'
			);
			echo '</p><p>';
			printf(
				/* translators: %s: the cron hook name. */
				esc_html__( 'To run this automatically, schedule the %s hook in WP Crontrol (weekly is recommended).', 'volunteer-event-management' ),
				'<code>' . esc_html( VEMgmt_Cleanup::CRON_HOOK ) . '</code>'
			);
			echo '</p>';
		}

		/**
		 * Render the cleanup enabled checkbox.
		 */
		public function render_cleanup_enabled_field() {
			$settings = get_option( 'vemgmt_settings', array() );
			$enabled  = ! empty( $settings['cleanup_enabled'] );
			?>
			<label>
				<input
					type="checkbox"
					name="vemgmt_settings[cleanup_enabled]"
					id="vemgmt_cleanup_enabled"
					value="1"
					<?php checked( $enabled ); ?>
				/>
				<?php esc_html_e( 'Allow the scheduled cron run to delete data.', 'volunteer-event-management' ); ?>
			</label>
			<p class="description">
				<?php esc_html_e( 'When unchecked, the scheduled run exits immediately without deleting anything. The buttons below still work.', 'volunteer-event-management' ); ?>
			</p>
			<?php
		}

		/**
		 * Render the dry run checkbox.
		 */
		public function render_cleanup_dry_run_field() {
			$settings = get_option( 'vemgmt_settings', array() );
			// Defaults to on until explicitly saved off, so a fresh install never
			// deletes anything unattended.
			$dry_run = array_key_exists( 'cleanup_dry_run', $settings )
				? ! empty( $settings['cleanup_dry_run'] )
				: true;
			?>
			<label>
				<input
					type="checkbox"
					name="vemgmt_settings[cleanup_dry_run]"
					id="vemgmt_cleanup_dry_run"
					value="1"
					<?php checked( $dry_run ); ?>
				/>
				<?php esc_html_e( 'Report what would be deleted without deleting it.', 'volunteer-event-management' ); ?>
			</label>
			<p class="description">
				<?php esc_html_e( 'Applies to the scheduled run only. The two buttons below always choose their own mode.', 'volunteer-event-management' ); ?>
			</p>
			<?php
		}

		/**
		 * Render the retention days field.
		 */
		public function render_cleanup_retention_days_field() {
			$settings = get_option( 'vemgmt_settings', array() );
			$days     = max( 0, absint( $settings['cleanup_retention_days'] ?? 7 ) );
			?>
			<input
				type="number"
				min="0"
				name="vemgmt_settings[cleanup_retention_days]"
				id="vemgmt_cleanup_retention_days"
				value="<?php echo esc_attr( $days ); ?>"
				class="small-text"
			/>
			<p class="description">
				<?php esc_html_e( 'How long a shift or event survives after it has passed. This value also stops Salesforce from re-creating records this old, so lowering it makes both rules more aggressive together.', 'volunteer-event-management' ); ?>
			</p>
			<?php
		}

		/**
		 * Render the batch size field.
		 */
		public function render_cleanup_batch_size_field() {
			$settings = get_option( 'vemgmt_settings', array() );
			$batch    = max( 1, absint( $settings['cleanup_batch_size'] ?? 1000 ) );
			?>
			<input
				type="number"
				min="1"
				name="vemgmt_settings[cleanup_batch_size]"
				id="vemgmt_cleanup_batch_size"
				value="<?php echo esc_attr( $batch ); ?>"
				class="small-text"
			/>
			<p class="description">
				<?php esc_html_e( 'Safety cap on how many posts a single run may delete. Anything over the cap is picked up on the next run.', 'volunteer-event-management' ); ?>
			</p>
			<?php
		}

		/**
		 * Render the manual cleanup controls and the last-run summary.
		 */
		public function render_cleanup_panel() {
			$settings = get_option( 'vemgmt_settings', array() );
			$dry_run  = array_key_exists( 'cleanup_dry_run', $settings )
				? ! empty( $settings['cleanup_dry_run'] )
				: true;
			?>
			<h2><?php esc_html_e( 'Run Cleanup', 'volunteer-event-management' ); ?></h2>

			<?php if ( $dry_run ) : ?>
				<div class="notice notice-warning inline">
					<p>
						<?php esc_html_e( 'Dry run is enabled. The scheduled cleanup reports what it would delete but deletes nothing.', 'volunteer-event-management' ); ?>
					</p>
				</div>
			<?php endif; ?>

			<p>
				<button type="button" class="button" id="vemgmt-cleanup-preview">
					<?php esc_html_e( 'Preview (dry run)', 'volunteer-event-management' ); ?>
				</button>
				<button type="button" class="button button-primary" id="vemgmt-cleanup-run">
					<?php esc_html_e( 'Run Cleanup Now', 'volunteer-event-management' ); ?>
				</button>
				<span class="spinner" id="vemgmt-cleanup-spinner" style="float:none;"></span>
			</p>
			<p class="description">
				<?php esc_html_e( 'Preview never deletes anything. Run Cleanup Now deletes immediately, regardless of the Dry Run setting above.', 'volunteer-event-management' ); ?>
			</p>

			<div id="vemgmt-cleanup-result"></div>

			<?php $this->render_last_run_summary(); ?>
			<?php
		}

		/**
		 * Render the stored summary of the most recent cleanup run.
		 */
		private function render_last_run_summary() {
			$log = get_option( VEMgmt_Cleanup::LOG_OPTION, array() );

			if ( empty( $log ) || ! is_array( $log ) ) {
				echo '<h3>' . esc_html__( 'Last Run', 'volunteer-event-management' ) . '</h3>';
				echo '<p>' . esc_html__( 'Cleanup has not run yet.', 'volunteer-event-management' ) . '</p>';
				return;
			}

			$mode = ! empty( $log['dry_run'] )
				? __( 'dry run — nothing deleted', 'volunteer-event-management' )
				: __( 'live — data deleted', 'volunteer-event-management' );

			$rows = array(
				__( 'When', 'volunteer-event-management' )              => $log['timestamp'] ?? '',
				__( 'Triggered by', 'volunteer-event-management' )      => $log['trigger'] ?? '',
				__( 'Mode', 'volunteer-event-management' )              => $mode,
				__( 'Cutoff', 'volunteer-event-management' )            => $log['cutoff'] ?? '',
				__( 'Shifts matched', 'volunteer-event-management' )    => $log['shifts_matched'] ?? 0,
				__( 'Shifts deleted', 'volunteer-event-management' )    => $log['shifts_deleted'] ?? 0,
				__( 'Events matched', 'volunteer-event-management' )    => $log['jobs_matched'] ?? 0,
				__( 'Events deleted', 'volunteer-event-management' )    => $log['jobs_deleted'] ?? 0,
				__( 'Sync map rows removed', 'volunteer-event-management' ) => $log['map_rows_deleted'] ?? 0,
				__( 'Duration (seconds)', 'volunteer-event-management' ) => $log['duration_seconds'] ?? 0,
			);

			echo '<h3>' . esc_html__( 'Last Run', 'volunteer-event-management' ) . '</h3>';
			echo '<table class="widefat striped" style="max-width:640px;"><tbody>';
			foreach ( $rows as $label => $value ) {
				echo '<tr><th scope="row" style="width:220px;">' . esc_html( $label ) . '</th>';
				echo '<td>' . esc_html( (string) $value ) . '</td></tr>';
			}
			echo '</tbody></table>';

			$this->render_id_list(
				__( 'Skipped — missing or invalid date', 'volunteer-event-management' ),
				$log['skipped_no_date'] ?? array(),
				__( 'These posts have no usable date meta, so cleanup cannot evaluate them. Review them by hand.', 'volunteer-event-management' )
			);

			$this->render_id_list(
				__( 'Delete failures', 'volunteer-event-management' ),
				$log['delete_failures'] ?? array(),
				__( 'WordPress refused to delete these posts. Their sync map rows were left intact.', 'volunteer-event-management' )
			);

			if ( ! empty( $log['budget_exhausted'] ) ) {
				echo '<p><strong>' . esc_html__( 'Note:', 'volunteer-event-management' ) . '</strong> ';
				printf(
					/* translators: %d: number of posts deferred to the next run. */
					esc_html__( 'The per-run limit was reached. %d item(s) were deferred to the next run.', 'volunteer-event-management' ),
					(int) ( $log['deferred'] ?? 0 )
				);
				echo '</p>';
			}
		}

		/**
		 * Render a collapsible list of post IDs, if any.
		 *
		 * @param string $title       Heading for the list.
		 * @param array  $ids         Post IDs.
		 * @param string $description Explanatory text.
		 */
		private function render_id_list( $title, $ids, $description ) {
			if ( empty( $ids ) || ! is_array( $ids ) ) {
				return;
			}
			?>
			<details style="margin-top:1em;">
				<summary>
					<strong><?php echo esc_html( $title ); ?></strong>
					(<?php echo esc_html( (string) count( $ids ) ); ?>)
				</summary>
				<p class="description"><?php echo esc_html( $description ); ?></p>
				<p>
					<?php
					$links = array();
					foreach ( $ids as $id ) {
						$links[] = '<a href="' . esc_url( get_edit_post_link( (int) $id ) ) . '">'
							. esc_html( (string) $id ) . '</a>';
					}
					// Each link was escaped individually above.
					echo wp_kses_post( implode( ', ', $links ) );
					?>
				</p>
			</details>
			<?php
		}

		/**
		 * Render the settings page.
		 */
		public function render_page() {
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}
			?>
			<div class="wrap">
				<h1><?php esc_html_e( 'Volunteer Event Settings', 'volunteer-event-management' ); ?></h1>
				<form method="post" action="options.php">
					<?php
					settings_fields( 'vemgmt_settings_group' );
					do_settings_sections( 'vemgmt-settings' );
					submit_button();
					?>
				</form>
				<hr />
				<?php $this->render_cleanup_panel(); ?>
			</div>
			<?php
		}
	}
}

VEMgmt_Settings::instance();
