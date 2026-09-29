<?php
/**
 * Gravity Forms shift capacity validation.
 *
 * Hooks into Gravity Forms to:
 *  - Check shift capacity before allowing form submission (gform_validation).
 *  - Decrement vol_shift_volunteers_needed after a successful submission (gform_after_submission).
 *
 * DESIGN NOTE — Race Condition Tolerance:
 * A brief window exists between the capacity check (gform_validation) and the decrement
 * (gform_after_submission). Two simultaneous submissions could both pass validation before
 * either decrement runs, resulting in a temporary overbooking. This is accepted as a known
 * trade-off: Salesforce is the authoritative source and resyncs vol_shift_volunteers_needed
 * within minutes. The decrement is a best-effort immediate update to prevent most double-bookings.
 *
 * DESIGN NOTE — Missing Meta = Blocked:
 * If vol_shift_volunteers_needed is not set on a vol_shift post, get_post_meta() returns ''.
 * In PHP, '' > 0 evaluates to false, so a shift with no meta is treated as full. This is the
 * safe default — never allow registration when capacity is unknown.
 *
 * @package volunteer-event-management
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'VEMgmt_Form_Validation' ) ) {

	/**
	 * Class VEMgmt_Form_Validation
	 *
	 * @since 2.0.0
	 */
	class VEMgmt_Form_Validation {

		/**
		 * The class instance.
		 *
		 * @var VEMgmt_Form_Validation
		 */
		private static $instance;

		/**
		 * Stores a capacity error message to be passed to the gform_validation_message filter.
		 * Set during check_shift_capacity; read in filter_validation_message.
		 *
		 * @var string
		 */
		private static $validation_error = '';

		/**
		 * Returns the single instance.
		 *
		 * @return VEMgmt_Form_Validation
		 */
		public static function instance() {
			if ( ! isset( self::$instance ) ) {
				self::$instance = new VEMgmt_Form_Validation();
				self::$instance->setup_hooks();
			}
			return self::$instance;
		}

		/**
		 * Register WordPress / Gravity Forms hooks.
		 * Shows an admin notice if Gravity Forms is not active.
		 */
		private function setup_hooks() {
			if ( ! class_exists( 'GFForms' ) ) {
				add_action( 'admin_notices', array( $this, 'notice_gf_missing' ) );
				return;
			}

			add_filter( 'gform_validation', array( $this, 'check_shift_capacity' ) );
			add_filter( 'gform_validation_message', array( $this, 'filter_validation_message' ), 10, 2 );
			add_action( 'gform_after_submission', array( $this, 'decrement_shift_counter' ), 10, 2 );
		}

		/**
		 * Admin notice shown when Gravity Forms is not installed or active.
		 */
		public function notice_gf_missing() {
			echo '<div class="notice notice-warning"><p>';
			esc_html_e(
				'Volunteer Event Management: Gravity Forms is required for shift capacity validation. Please install and activate Gravity Forms.',
				'volunteer-event-management'
			);
			echo '</p></div>';
		}

		// -------------------------------------------------------------------------
		// Private helpers
		// -------------------------------------------------------------------------

		/**
		 * Get the configured Gravity Forms form ID from settings.
		 *
		 * @return int Form ID, or 0 if not configured.
		 */
		private function get_configured_form_id() {
			$settings = get_option( 'vemgmt_settings', array() );
			return absint( $settings['form_id'] ?? 0 );
		}

		/**
		 * Get the configured capacity error message from settings.
		 *
		 * @return string
		 */
		private function get_capacity_error_message() {
			$settings = get_option( 'vemgmt_settings', array() );
			$message  = $settings['capacity_error_message'] ?? '';
			if ( empty( $message ) ) {
				$message = __( 'Sorry, this shift is now full. Please choose another shift.', 'volunteer-event-management' );
			}
			return $message;
		}

		/**
		 * Find the wp_shift_id hidden field in the form by admin label and return the submitted value.
		 *
		 * The GF form must have a Hidden field with:
		 *   - Admin Label: wp_shift_id
		 *   - Parameter Name: wp_shift_id  (so field_values="wp_shift_id=..." populates it)
		 *
		 * @param array $form GF form object.
		 * @return int|false Positive WP post ID, or false if not found or invalid.
		 */
		private function get_wp_shift_id_from_form( $form ) {
			foreach ( $form['fields'] as $field ) {
				if ( 'wp_shift_id' === $field->adminLabel ) {
					$value = absint( rgpost( 'input_' . $field->id ) );
					return $value > 0 ? $value : false;
				}
			}
			return false;
		}

		/**
		 * Find the wp_shift_id hidden field in the form by admin label and return the value from a saved entry.
		 *
		 * @param array $entry GF entry object.
		 * @param array $form  GF form object.
		 * @return int|false Positive WP post ID, or false if not found or invalid.
		 */
		private function get_wp_shift_id_from_entry( $entry, $form ) {
			foreach ( $form['fields'] as $field ) {
				if ( 'wp_shift_id' === $field->adminLabel ) {
					$value = absint( $entry[ (string) $field->id ] ?? 0 );
					return $value > 0 ? $value : false;
				}
			}
			return false;
		}

		// -------------------------------------------------------------------------
		// Hook callbacks
		// -------------------------------------------------------------------------

		/**
		 * gform_validation callback.
		 *
		 * Checks that the vol_shift still has available spots before allowing submission.
		 * Scoped to the form ID configured in settings. Skips intermediate pages of
		 * multi-page forms (only runs on the final page submission).
		 *
		 * @param array $validation_result GF validation result array with keys 'form' and 'is_valid'.
		 * @return array Modified validation result.
		 */
		public function check_shift_capacity( $validation_result ) {
			$configured_form_id = $this->get_configured_form_id();

			// Skip entirely if no form ID is configured.
			if ( ! $configured_form_id ) {
				return $validation_result;
			}

			$form = $validation_result['form'];

			// Skip if this is not the configured form.
			if ( (int) $form['id'] !== $configured_form_id ) {
				return $validation_result;
			}

			// For multi-page forms, only validate on the final page.
			// GF sets gform_target_page_number_{id} to 0 on the final submission.
			$target_page = absint( rgpost( 'gform_target_page_number_' . $form['id'] ) );
			if ( $target_page > 0 ) {
				return $validation_result;
			}

			// Locate the wp_shift_id value from the submitted form data.
			$wp_shift_id = $this->get_wp_shift_id_from_form( $form );

			if ( ! $wp_shift_id ) {
				$validation_result['is_valid'] = false;
				self::$validation_error        = __( 'Unable to verify shift availability. Please refresh and try again.', 'volunteer-event-management' );
				return $validation_result;
			}

			// Confirm the shift post exists and is the correct post type.
			$shift_post = get_post( $wp_shift_id );
			if ( ! $shift_post || 'vol_shift' !== $shift_post->post_type ) {
				$validation_result['is_valid'] = false;
				self::$validation_error        = __( 'Unable to verify shift availability. Please refresh and try again.', 'volunteer-event-management' );
				return $validation_result;
			}

			// Check available spots.
			// NOTE: Missing meta returns '' which is not > 0 — treated as full (safe default).
			$vol_needed = get_post_meta( $wp_shift_id, 'vol_shift_volunteers_needed', true );
			if ( (int) $vol_needed <= 0 ) {
				$validation_result['is_valid'] = false;
				self::$validation_error        = $this->get_capacity_error_message();
			}

			return $validation_result;
		}

		/**
		 * gform_validation_message filter callback.
		 *
		 * Replaces the default GF "There was a problem" message with our custom error
		 * when validation was failed by check_shift_capacity.
		 *
		 * @param string $message Default GF validation error message.
		 * @param array  $form    GF form object.
		 * @return string
		 */
		public function filter_validation_message( $message, $form ) {
			if ( ! empty( self::$validation_error ) ) {
				return '<h2 class="gf_submission_limit_reached">' . esc_html( self::$validation_error ) . '</h2>';
			}
			return $message;
		}

		/**
		 * gform_after_submission callback.
		 *
		 * Decrements vol_shift_volunteers_needed by 1 after a successful form submission.
		 * Counter is floored at 0 and never goes negative.
		 *
		 * NOTE: Race condition is accepted — see file-level DESIGN NOTE above.
		 *
		 * @param array $entry GF entry object.
		 * @param array $form  GF form object.
		 */
		public function decrement_shift_counter( $entry, $form ) {
			$configured_form_id = $this->get_configured_form_id();

			// Skip if no form ID is configured or this is not the configured form.
			if ( ! $configured_form_id || (int) $form['id'] !== $configured_form_id ) {
				return;
			}

			$wp_shift_id = $this->get_wp_shift_id_from_entry( $entry, $form );

			if ( ! $wp_shift_id ) {
				return;
			}

			$current   = (int) get_post_meta( $wp_shift_id, 'vol_shift_volunteers_needed', true );
			$new_value = max( 0, $current - 1 );

			update_post_meta( $wp_shift_id, 'vol_shift_volunteers_needed', $new_value );
		}
	}
}

VEMgmt_Form_Validation::instance();
