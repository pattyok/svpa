<?php
/**
 * Event Shift Schedule block — server-side render.
 *
 * WordPress injects $attributes, $content, and $block into this file's scope.
 * Post context is accessed via $block->context, not a $context variable.
 *
 * @package VEMgmt
 */

$event_id = $block->context['postId'] ?? get_the_ID();
$reg_link  = '/volunteer-registration/';
$job_data  = VEMgmt_Helpers::get_shift_data_for_job( $event_id, $reg_link, 'l, M j' );

if ( empty( $job_data['shifts'] ) ) {
	return;
}

$settings   = get_option( 'vemgmt_settings', array() );
$closed_msg = $settings['closed_message'] ?? 'Registration is closed for this shift.';
$full_msg   = $settings['full_message'] ?? 'This shift is currently full.';

?>
<div <?php echo get_block_wrapper_attributes(); ?>>
<?php
foreach ( $job_data['shifts'] as $shift ) :
	?>
	<ul class="event-meta event-shift">
		<li class="event-date"><?php echo esc_html( $shift['start_date'] ); ?></li>
		<li class="event-time"><?php echo esc_html( $shift['start_time'] ); ?> – <?php echo esc_html( $shift['end_time'] ); ?></li>
		<li class="event-signup">
			<?php echo VEMgmt_Helpers::get_signup_button_html( $shift, $full_msg, $closed_msg ); ?>
		</li>
	</ul>
	<?php
endforeach;
?>
</div>