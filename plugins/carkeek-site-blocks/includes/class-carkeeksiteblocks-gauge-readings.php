<?php
/**
 * Fetch, store and render the latest river gauge readings.
 *
 * Readings come from the SVPA readings API (Floodzilla). Each API response
 * contains every gauge in the region, so one throttled fetch refreshes all
 * stations. Only the latest reading per station is kept, in a single option,
 * so the last good value survives API outages.
 *
 * @package   CarkeekSiteBlocks
 * @author    Patty O'Hara
 * @link      https://carkeekstudios.com
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Gauge readings data layer, REST endpoint and dynamic markup.
 */
class CarkeekSiteBlocks_Gauge_Readings {

	/**
	 * Option holding the stored readings and fetch bookkeeping.
	 */
	const OPTION = 'carkeek_gauge_readings';

	/**
	 * Readings API endpoint.
	 */
	const API_URL = 'https://prodplanreadingsvc.azurewebsites.net/api/GetGageStatusAndRecentReadings';

	/**
	 * Region requested from the API.
	 */
	const REGION_ID = 1;

	/**
	 * Timezone of the (offset-less) timestamps returned by the API. Unconfirmed with the API owners.
	 */
	const API_TIMEZONE = 'America/Los_Angeles';

	/**
	 * Seconds to wait after a successful fetch before fetching again.
	 */
	const REFRESH_INTERVAL = 900;

	/**
	 * Seconds to wait after a failed fetch before trying again.
	 */
	const RETRY_INTERVAL = 300;

	/**
	 * HTTP timeout in seconds.
	 */
	const TIMEOUT = 3;

	/**
	 * Hours of readings to request. Gauges without readings in the window are left out of the response.
	 */
	const WINDOW_HOURS = 6;

	/**
	 * Allowed station ID characters.
	 */
	const STATION_PATTERN = '[A-Za-z0-9_-]{1,40}';

	/**
	 * REST namespace.
	 */
	const REST_NAMESPACE = 'carkeek-site-blocks/v1';

	/**
	 * Hook into WordPress.
	 */
	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
	}

	/**
	 * Normalize a station ID, or return an empty string if it is not valid.
	 *
	 * @param mixed $station Raw station ID.
	 * @return string
	 */
	public static function sanitize_station( $station ) {
		$station = strtoupper( trim( (string) $station ) );
		return preg_match( '/^' . self::STATION_PATTERN . '$/', $station ) ? $station : '';
	}

	/**
	 * Stored state, with defaults filled in.
	 *
	 * @return array
	 */
	public static function get_state() {
		$state = get_option( self::OPTION, array() );
		return wp_parse_args(
			is_array( $state ) ? $state : array(),
			array(
				'last_attempt' => 0,
				'last_success' => 0,
				'last_error'   => '',
				'stations'     => array(),
			)
		);
	}

	/**
	 * Latest stored reading for a station.
	 *
	 * @param string $station     Sanitized station ID.
	 * @param bool   $allow_fetch Whether a stale store may be refreshed from the API first.
	 * @return array|null
	 */
	public static function get_reading( $station, $allow_fetch = false ) {
		if ( $allow_fetch ) {
			self::maybe_refresh();
		}
		$state = self::get_state();
		return isset( $state['stations'][ $station ] ) ? $state['stations'][ $station ] : null;
	}

	/**
	 * Refresh from the API if the throttle window has passed.
	 *
	 * The attempt is recorded before the HTTP call so concurrent requests are
	 * served the stored data instead of all hitting the API.
	 */
	public static function maybe_refresh() {
		$state  = self::get_state();
		$failed = $state['last_attempt'] > $state['last_success'];
		$wait   = $failed ? self::RETRY_INTERVAL : self::REFRESH_INTERVAL;

		if ( time() - (int) $state['last_attempt'] < $wait ) {
			return;
		}

		$state['last_attempt'] = time();
		update_option( self::OPTION, $state, false );

		self::refresh( $state );
	}

	/**
	 * Fetch the API and merge the latest reading of every gauge into the store.
	 *
	 * Stations missing from the response keep their previous reading. On failure
	 * the stored readings are left untouched and the error is recorded.
	 *
	 * @param array $state Current state, with last_attempt already set.
	 * @return bool Whether the fetch succeeded.
	 */
	public static function refresh( $state ) {
		$url = add_query_arg(
			array(
				'regionId'     => self::REGION_ID,
				'fromDateTime' => gmdate( 'Y-m-d\TH:i:s\Z', time() - self::WINDOW_HOURS * HOUR_IN_SECONDS ),
				'toDateTime'   => gmdate( 'Y-m-d\TH:i:s\Z', time() + HOUR_IN_SECONDS ),
			),
			self::API_URL
		);

		/**
		 * Filter the readings API URL (e.g. to use a station-filtered endpoint).
		 *
		 * @param string $url API URL.
		 */
		$url = apply_filters( 'carkeek_gauge_reading_api_url', $url );

		$stations = self::parse_response( wp_remote_get( $url, array( 'timeout' => self::TIMEOUT ) ) );

		if ( is_wp_error( $stations ) ) {
			$state['last_error'] = $stations->get_error_message();
			update_option( self::OPTION, $state, false );
			return false;
		}

		$state['stations']     = array_merge( $state['stations'], $stations );
		$state['last_success'] = $state['last_attempt'];
		$state['last_error']   = '';
		update_option( self::OPTION, $state, false );
		return true;
	}

	/**
	 * Extract the latest reading of each gauge from an API response.
	 *
	 * @param array|WP_Error $response Response from wp_remote_get().
	 * @return array|WP_Error Readings keyed by station ID.
	 */
	public static function parse_response( $response ) {
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		if ( 200 !== $code ) {
			return new WP_Error( 'gauge_http', sprintf( 'HTTP %d', $code ) );
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $body ) || empty( $body['gages'] ) || ! is_array( $body['gages'] ) ) {
			return new WP_Error( 'gauge_body', 'Response has no gauges' );
		}

		$stations = array();
		foreach ( $body['gages'] as $gauge ) {
			$station = self::sanitize_station( isset( $gauge['locationId'] ) ? $gauge['locationId'] : '' );
			$status  = isset( $gauge['status'] ) && is_array( $gauge['status'] ) ? $gauge['status'] : array();
			$last    = isset( $status['lastReading'] ) && is_array( $status['lastReading'] ) ? $status['lastReading'] : array();

			if ( '' === $station || empty( $last['timestamp'] ) || ! empty( $last['isDeleted'] ) ) {
				continue;
			}
			if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}(:\d{2})?/', $last['timestamp'] ) ) {
				continue;
			}

			$stations[ $station ] = array(
				'timestamp'      => substr( $last['timestamp'], 0, 19 ),
				'waterHeight'    => self::to_number( $last, 'waterHeight' ),
				'waterDischarge' => self::to_number( $last, 'waterDischarge' ),
				'floodLevel'     => isset( $status['floodLevel'] ) ? sanitize_text_field( $status['floodLevel'] ) : '',
				'levelTrend'     => isset( $status['levelTrend'] ) ? sanitize_text_field( $status['levelTrend'] ) : '',
				'trendValue'     => self::to_number( isset( $status['waterTrend'] ) ? $status['waterTrend'] : array(), 'trendValue' ),
			);
		}

		if ( empty( $stations ) ) {
			return new WP_Error( 'gauge_empty', 'Response has no usable readings' );
		}

		return $stations;
	}

	/**
	 * Map pin, adapted from Floodzilla's mapicon-flat-green.svg.
	 *
	 * Inlined so CSS can color the fill by flood status and rotate the arrow by
	 * trend. The original's <style> classes and shadow filter are replaced with
	 * attributes and a CSS drop-shadow so multiple pins can't collide.
	 *
	 * @return string
	 */
	private static function pin_svg() {
		return '<svg class="gauge-reading__pin" viewBox="0 0 72 78.14" width="40" height="43" focusable="false">'
			. '<g transform="translate(9 6)">'
			. '<path fill="#fff" fill-opacity=".92" d="M27 3a24 24 0 0 0-4.72 47.54c1.73.32 4.79 4.57 4.79 4.57s3.29-4.28 4.59-4.56A24 24 0 0 0 27 3m0-3a26.955 26.955 0 0 1 20.83 44.15 27.334 27.334 0 0 1-6.88 5.93 27.613 27.613 0 0 1-8.32 3.34 26.077 26.077 0 0 0-3.18 3.52l-2.46 3.2-2.36-3.27c0-.01-.58-.8-1.34-1.68a9.923 9.923 0 0 0-1.78-1.73 26.554 26.554 0 0 1-15.25-9.11A27.015 27.015 0 0 1 27 0z"/>'
			. '<path class="gauge-reading__pin-fill" d="M27 3a23.995 23.995 0 0 1 4.66 47.54c-1.3.28-4.59 4.56-4.59 4.56s-3.06-4.24-4.79-4.57A24 24 0 0 1 27 3z"/>'
			. '<g transform="translate(13 15)"><path class="gauge-reading__pin-arrow" fill="#fff" d="M27.867 12.631l-3.72-3.72A.668.668 0 0 0 23 9.377v2.387H4.333a1.333 1.333 0 1 0 0 2.667H23v2.387a.66.66 0 0 0 1.133.467l3.72-3.72a.656.656 0 0 0 .013-.933z"/></g>'
			. '</g></svg>';
	}

	/**
	 * Numeric field as a float, or null when missing.
	 *
	 * @param mixed  $data Source array.
	 * @param string $key  Field name.
	 * @return float|null
	 */
	private static function to_number( $data, $key ) {
		return is_array( $data ) && isset( $data[ $key ] ) && is_numeric( $data[ $key ] ) ? (float) $data[ $key ] : null;
	}

	/**
	 * Reading timestamp as a date object, or null if it cannot be parsed.
	 *
	 * @param string $timestamp API timestamp (local time, no offset).
	 * @return DateTimeImmutable|null
	 */
	public static function get_datetime( $timestamp ) {
		try {
			return new DateTimeImmutable( $timestamp, new DateTimeZone( self::API_TIMEZONE ) );
		} catch ( Exception $e ) {
			return null;
		}
	}

	/**
	 * Register the public REST route used by the front-end refresh.
	 */
	public static function register_routes() {
		register_rest_route(
			self::REST_NAMESPACE,
			'/gauge-reading/(?P<station>' . self::STATION_PATTERN . ')',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'rest_reading' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * REST URL for a station.
	 *
	 * @param string $station Sanitized station ID.
	 * @return string
	 */
	public static function rest_url( $station ) {
		return rest_url( self::REST_NAMESPACE . '/gauge-reading/' . rawurlencode( $station ) );
	}

	/**
	 * REST callback: latest reading for a station, as rendered markup parts.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function rest_reading( $request ) {
		$station  = self::sanitize_station( $request['station'] );
		$reading  = self::get_reading( $station, true );
		$response = rest_ensure_response(
			array(
				'station'   => $station,
				'found'     => null !== $reading,
				'timestamp' => $reading ? $reading['timestamp'] : null,
				'parts'     => self::render_parts( $station, $reading ),
			)
		);
		$response->header( 'Cache-Control', 'public, max-age=300' );
		return $response;
	}

	/**
	 * Reading-dependent markup, shared by render.php and the REST refresh.
	 *
	 * Each part carries a data-gauge-part attribute so the view script can swap it.
	 *
	 * @param string     $station   Sanitized station ID.
	 * @param array|null $reading   Stored reading, or null if none.
	 * @param bool       $is_editor Whether to show editor-only notices.
	 * @return array Markup keyed by part name: time, marker, values.
	 */
	public static function render_parts( $station, $reading, $is_editor = false ) {
		$date = $reading ? self::get_datetime( $reading['timestamp'] ) : null;

		if ( ! $reading || ! $date ) {
			$notice = '';
			if ( $is_editor ) {
				$notice = '<p class="gauge-reading__notice">' . esc_html(
					'' === $station
						? __( 'Enter a valid station ID in the block settings.', 'carkeek-blocks' )
						/* translators: %s: station ID */
						: sprintf( __( 'No data found for station "%s". Check the station ID.', 'carkeek-blocks' ), $station )
				) . '</p>';
			}
			return array(
				'time'   => '<p class="gauge-reading__time" data-gauge-part="time" hidden></p>',
				'marker' => '<span class="gauge-reading__marker" data-gauge-part="marker" hidden></span>',
				'values' => '<div class="gauge-reading__values is-empty" data-gauge-part="values">' . $notice
					. '<p>' . esc_html__( 'Current river conditions are unavailable.', 'carkeek-blocks' ) . '</p></div>',
			);
		}

		$timestamp = $date->getTimestamp();
		$format    = wp_date( 'Y-m-d', $timestamp ) === wp_date( 'Y-m-d' ) ? 'g:i a' : 'M j, g:i a';
		$flood     = $reading['floodLevel'] ? $reading['floodLevel'] : __( 'Unknown', 'carkeek-blocks' );
		$classes   = 'is-flood-' . sanitize_title( $flood ) . ' is-trend-' . sanitize_title( $reading['levelTrend'] ? $reading['levelTrend'] : 'steady' );
		$arrow     = '<img class="gauge-reading__arrow" src="' . esc_url( CARKEEKSITEBLOCKS_PLUGIN_URL . 'assets/gauge-reading/trend-arrow.svg' ) . '" width="37" height="12" alt="" aria-hidden="true">';

		$rows = array(
			'level' => array(
				__( 'Water Level:', 'carkeek-blocks' ),
				null === $reading['waterHeight'] ? '&mdash;' : esc_html( number_format_i18n( $reading['waterHeight'], 2 ) . 'ft' ),
			),
		);
		if ( null !== $reading['waterDischarge'] ) {
			$rows['flow'] = array(
				__( 'Water Flow:', 'carkeek-blocks' ),
				esc_html( number_format_i18n( $reading['waterDischarge'], 0 ) . ' cfs' ),
			);
		}
		$rows['status'] = array(
			__( 'Status:', 'carkeek-blocks' ),
			'<span class="gauge-reading__pill ' . esc_attr( $classes ) . '">' . esc_html( $flood ) . '</span>',
		);
		if ( null !== $reading['trendValue'] ) {
			$trend         = $reading['trendValue'];
			$rows['trend'] = array(
				__( 'Trend:', 'carkeek-blocks' ),
				'<span class="gauge-reading__trend ' . esc_attr( $classes ) . '">'
					. esc_html( ( $trend < 0 ? '-' : '' ) . number_format_i18n( abs( $trend ), 2 ) . 'ft/hr' )
					. $arrow . '</span>',
			);
		}

		$values = '';
		foreach ( $rows as $key => $row ) {
			$values .= '<div class="gauge-reading__row is-' . esc_attr( $key ) . '"><dt>' . esc_html( $row[0] ) . '</dt><dd>' . $row[1] . '</dd></div>';
		}

		return array(
			'time'   => '<p class="gauge-reading__time" data-gauge-part="time"><strong>' . esc_html__( 'Last Reading:', 'carkeek-blocks' ) . '</strong> '
				. '<span class="gauge-reading__relative"></span>'
				. '<time datetime="' . esc_attr( $date->format( 'c' ) ) . '">' . esc_html( wp_date( $format, $timestamp ) ) . '</time></p>',
			'marker' => '<span class="gauge-reading__marker ' . esc_attr( $classes ) . '" data-gauge-part="marker" aria-hidden="true">' . self::pin_svg() . '</span>',
			'values' => '<dl class="gauge-reading__values" data-gauge-part="values">' . $values . '</dl>',
		);
	}
}

CarkeekSiteBlocks_Gauge_Readings::init();
