<?php
$event_id = get_query_var( 'wp_event_id' );
$shift_id = get_query_var( 'wp_shift_id' );

?>
<div <?php echo get_block_wrapper_attributes(); ?>>

	<?php
	if ( $event_id && $shift_id ) {
		// Get event details.
		$event_name  = get_the_title( $event_id );
		$event_link  = get_permalink( $event_id );
		$shift_times = VEMgmt_Helpers::get_formatted_shift_times( $shift_id, 'l, M j' );

		// Get data that we need to pass back to SF via hidden fields.
		$sf_job_id      = get_post_meta( $event_id, 'event_sf_id', true );
		$sf_shift_id    = get_post_meta( $shift_id, 'vol_shift_id', true );
		$sf_campaign_id = get_post_meta( $event_id, 'event_campaign_id', true );
		$sf_hours       = get_post_meta( $shift_id, 'vol_shift_duration', true );
		$sf_shift_times = VEMgmt_Helpers::get_formatted_shift_times( $shift_id, 'Y-m-d' );

		// Use the global form ID from settings; fall back to the per-block attribute for backward compatibility.
		$vemgmt_settings = get_option( 'vemgmt_settings', array() );
		$form_id         = ! empty( $vemgmt_settings['form_id'] ) ? $vemgmt_settings['form_id'] : $attributes['formID'];

		$short_code = sprintf(
			'[gravityform title="false" ajax="true" description="false" id="%s" field_values="shift_id=%s&amp;job_id=%s&amp;campaign_id=%s&amp;hours=%s&amp;start=%s&amp;event_name=%s&amp;event_link=%s&amp;wp_shift_id=%s"]',
			$form_id,
			$sf_shift_id,
			$sf_job_id,
			$sf_campaign_id,
			$sf_hours,
			$sf_shift_times['start_date'],
			$event_name,
			$event_link,
			$shift_id
		);

		?>

		<ul class="event-meta no-bullets">
			<li class="meta-label">
				<a target="_blank" href="<?php echo esc_url( $event_link ); ?>"><?php echo esc_html( $event_name ); ?></a>
			</li>
			<li class="meta-value">
				<?php echo esc_html( $shift_times['start_date'] ); ?>
			</li>
			<li class="meta-value">
				<?php echo esc_html( $shift_times['start_time'] ); ?>
				- <?php echo esc_html( $shift_times['end_time'] ); ?>
			</li>
		</ul>
		<?php
		// Use is_registration_open() as the single source of truth — it checks both
		// capacity (vol_shift_volunteers_needed > 0) and the configured cutoff time.
		$vol_needed = (int) get_post_meta( $shift_id, 'vol_shift_volunteers_needed', true );
		$is_open    = VEMgmt_Helpers::is_registration_open( (int) $shift_id );

		$capacity_msg = ! empty( $vemgmt_settings['capacity_error_message'] )
			? $vemgmt_settings['capacity_error_message']
			: __( 'Uh-oh! Registration is closed for this shift.', 'volunteer-event-management' );

		$closed_msg = ! empty( $vemgmt_settings['registration_closed_message'] )
			? $vemgmt_settings['registration_closed_message']
			: __( 'Registration is closed for this event', 'volunteer-event-management' );

		if ( $is_open ) {
			echo do_shortcode( $short_code );
		} elseif ( $vol_needed <= 0 ) {
			// Shift is full.
			echo '<p>' . esc_html( $capacity_msg ) . '</p>';
		} else {
			// Past the registration cutoff time.
			echo '<p>' . esc_html( $closed_msg ) . '</p>';
		}
	} else {
		?>
		<p><?php esc_html_e( 'Event details are not available. Please check your link and try again.', 'volunteer-event-management' ); ?></p>
		<?php
	}
	?>
</div>
