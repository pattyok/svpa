<?php
/**
 * Register Helper Functions for Volunteer Event Management
 *
 * @package   VEMgmt
 * @author    Patty O'Hara from VEMgmt
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * VEMgmt_Helpers Class
 *
 * @since 1.6.0
 */
class VEMgmt_Helpers {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_filter( 'query_vars', array( $this, 'custom_query_vars' ) );
	}

	/**
	 * Get all shifts for an event.
	 *
	 * @param int $event_id WP post ID of a vol_event post.
	 * @return WP_Post[]
	 */
	public static function get_all_shifts_for_job( $event_id ) {
		$job_id = get_post_meta( $event_id, 'event_sf_id', true );
		if ( empty( $job_id ) ) {
			return array();
		}
		$args = array(
			'post_type'      => 'vol_shift',
			'posts_per_page' => -1,
			'meta_key'       => 'vol_shift_job_id',
			'meta_value'     => $job_id,
			'orderby'        => 'meta_value',
			'meta_type'      => 'vol_shift_start_time',
			'order'          => 'ASC',
		);
		$shifts = get_posts( $args );
		return $shifts;
	}

	/**
	 * Get formatted shift dates and times for display.
	 *
	 * @param int    $shift_id    WP post ID of a vol_shift post.
	 * @param string $date_format PHP date format string for the date portion.
	 * @param string $time_format PHP date format string for the time portion.
	 * @return array{start_date_time: string, start_date: string, start_time: string, end_time: string}
	 */
	public static function get_formatted_shift_times( $shift_id, $date_format = 'M j, Y', $time_format = 'g:i a' ) {
		$start_date_time = get_post_meta( $shift_id, 'vol_shift_start_date_time', true );
		$end_date_time   = get_post_meta( $shift_id, 'vol_shift_end_date_time', true );
		if ( empty( $start_date_time ) || empty( $end_date_time ) ) {
			return array(
				'start_date_time' => '',
				'start_date'      => '',
				'start_time'      => '',
				'end_time'        => '',
			);
		}
		$dt_start = DateTime::createFromFormat( 'Y-m-d H:i:s', $start_date_time );
		$dt_end   = DateTime::createFromFormat( 'Y-m-d H:i:s', $end_date_time );

		return array(
			'start_date_time' => $start_date_time,
			'start_date'      => $dt_start->format( $date_format ),
			'start_time'      => $dt_start->format( $time_format ),
			'end_time'        => $dt_end->format( $time_format ),
		);
	}

	/**
	 * Returns true if a shift is currently open for registration.
	 *
	 * Checks both shift capacity (vol_shift_volunteers_needed > 0) and the
	 * configured registration cutoff time. Fails open: returns true if shift
	 * datetime meta is missing or malformed so that bad sync data does not
	 * silently block volunteers.
	 *
	 * @param int $shift_id WP post ID of a vol_shift post.
	 * @return bool
	 */
	public static function is_registration_open( int $shift_id ): bool {
		$vol_needed = (int) get_post_meta( $shift_id, 'vol_shift_volunteers_needed', true );
		if ( $vol_needed <= 0 ) {
			return false;
		}

		$start_str = get_post_meta( $shift_id, 'vol_shift_start_date_time', true );
		$dt_start  = DateTimeImmutable::createFromFormat( 'Y-m-d H:i:s', $start_str, wp_timezone() );

		// Fail open: missing or malformed meta should not silently block volunteers.
		if ( ! $dt_start ) {
			return true;
		}

		$settings     = get_option( 'vemgmt_settings', array() );
		$cutoff_type  = $settings['cutoff_type'] ?? 'day_before_5pm';
		$cutoff_hours = max( 1, (int) ( $settings['cutoff_hours'] ?? 24 ) );
		$dt_cutoff    = self::compute_cutoff( $dt_start, $cutoff_type, $cutoff_hours );
		$dt_now       = current_datetime();
		return $dt_now < $dt_cutoff;
	}

	/**
	 * Computes the registration cutoff datetime for a shift.
	 *
	 * DateTimeImmutable::modify() and setTime() return new instances without
	 * mutating the original, so no clone is needed.
	 *
	 * @param DateTimeImmutable $dt_start     Shift start time in site/Pacific timezone.
	 * @param string            $cutoff_type  One of: start_of_event, day_before_5pm, x_hours_before.
	 * @param int               $cutoff_hours Hours before start (used for x_hours_before only).
	 * @return DateTimeImmutable
	 */
	public static function compute_cutoff(
		DateTimeImmutable $dt_start,
		string $cutoff_type,
		int $cutoff_hours
	): DateTimeImmutable {
		switch ( $cutoff_type ) {
			case 'start_of_event':
				return $dt_start;

			case 'day_before_5pm':
				return $dt_start
					->modify( '-1 day' )
					->setTime( 17, 0, 0 );

			case 'x_hours_before':
				return $dt_start->modify( '-' . $cutoff_hours . ' hours' );

			default:
				return $dt_start;
		}
	}

	/**
	 * Returns signup button HTML for a single shift array entry.
	 *
	 * Call this once per shift inside a loop. Resolve $full_msg and $closed_msg
	 * from settings before the loop — do not rely on the defaults in high-volume
	 * contexts.
	 *
	 * @param array  $shift      Entry from get_shift_data_for_job()['shifts'].
	 *                           Required keys: is_registration_open (bool), is_full (bool), reg_link (string).
	 * @param string $full_msg   Message when shift is full.
	 * @param string $closed_msg Message when registration is past the cutoff time.
	 * @return string Escaped HTML string, safe to echo directly.
	 */
	public static function get_signup_button_html(
		array $shift,
		string $full_msg    = 'This event is currently full',
		string $closed_msg  = 'Registration is closed for this event'
	): string {
		// Defensive guard: handle pre-feature shift arrays that lack the new keys.
		if ( ! isset( $shift['is_registration_open'] ) ) {
			return '<a class="button" href="' . $shift['reg_link'] . '">Sign Up</a>';
		}

		if ( $shift['is_registration_open'] ) {
			// reg_link is already esc_url()-encoded by get_shift_data_for_job — do not double-encode.
			return '<a class="button" href="' . $shift['reg_link'] . '">Sign Up</a>';
		}

		$msg = $shift['is_full'] ? $full_msg : $closed_msg;

		return '<div class="button button-disabled">Sign Up</div>'
			 . '<span class="full-shift-note">' . esc_html( $msg ) . '</span>';
	}

	/**
	 * Build registration link data for all upcoming shifts of a volunteer event.
	 *
	 * Only future shifts (start time > now) are returned, sorted ascending by
	 * start time. Each entry includes registration status flags so callers can
	 * use get_signup_button_html() without additional queries.
	 *
	 * @param int    $event_id   WP post ID of a vol_event post.
	 * @param string $reg_link   Base URL for the registration page (e.g. '/volunteer-registration/').
	 *                           Read from vemgmt_settings['registration_base_url'] at the call site
	 *                           so the admin-configured URL is used.
	 * @param string $date_format PHP date format string for the date portion.
	 * @param string $time_format PHP date format string for the time portion.
	 * @return array{shifts: array, last_end_time: string}
	 */
	public static function get_shift_data_for_job( $event_id, $reg_link, $date_format = 'M j, Y', $time_format = 'g:i a' ) {
		$shift_data = array();
		$shifts     = get_field( 'event_shifts', $event_id );

		if ( empty( $shifts ) ) {
			return $shift_data;
		}

		// Hoist "now" outside the loop — it does not change during iteration.
		$dt_now = new DateTimeImmutable();

		foreach ( $shifts as $shift ) {

			$shift_times = self::get_formatted_shift_times( $shift->ID, $date_format, $time_format );
			$vol_needed  = (int) get_post_meta( $shift->ID, 'vol_shift_volunteers_needed', true );

			// Stored datetimes are in the server timezone — no explicit DateTimeZone needed.
			$dt_start = DateTimeImmutable::createFromFormat( 'Y-m-d H:i:s', $shift_times['start_date_time'] );

			// Skip shifts with missing or malformed start datetime meta.
			if ( ! $dt_start ) {
				continue;
			}

			// Only include shifts that have a start date/time in the future.
			// if ( $dt_start <= $dt_now ) {
			// 	continue;
			// }

			$is_open = self::is_registration_open( $shift->ID );

			$shift_data[] = array(
				'start_date'           => $shift_times['start_date'],
				'start_time'           => $shift_times['start_time'],
				'end_time'             => $shift_times['end_time'],
				'start_date_time_raw'  => $shift_times['start_date_time'],
				'vol_needed'           => $vol_needed,
				'is_registration_open' => $is_open,
				'is_full'              => ( $vol_needed <= 0 ),
				'reg_link'             => esc_url( add_query_arg(
					array(
						'wp_event_id' => $event_id,
						'wp_shift_id' => $shift->ID,
					),
					$reg_link
				) ),
			);
		}

		// Sort by raw Y-m-d H:i:s string — this format sorts correctly via lexicographic comparison,
		// avoiding the O(N log N) DateTime object allocations of the previous usort implementation.
		usort( $shift_data, function ( $a, $b ) {
			return strcmp( $a['start_date_time_raw'], $b['start_date_time_raw'] );
		} );

		// Get the last end time.
		$last_shift    = end( $shift_data );
		$last_end_time = $last_shift['end_time'] ?? '';

		return array(
			'shifts'        => $shift_data,
			'last_end_time' => $last_end_time,
		);
	}

	/**
	 * Add custom query vars so we can use them to process the form.
	 *
	 * @param array $vars Existing query vars.
	 * @return array
	 */
	public function custom_query_vars( $vars ) {
		$vars[] = 'wp_event_id';
		$vars[] = 'wp_shift_id';
		return $vars;
	}
}

return new VEMgmt_Helpers();
