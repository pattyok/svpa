<?php
/**
 * Template part for displaying a post
 *
 * @package svpa
 */

namespace WP_Rig\WP_Rig;

?>


<article id="post-<?php the_ID(); ?>" <?php post_class( 'entry page-content single-entry' ); ?>>

<div class="entry-title">
				<?php
				echo '<h1>' . wp_kses_post( get_the_title() ) . '</h1>';
				?>
			</div>
		<div class="event-header">

			<div class="event-meta">
			<?php
			$post_id = get_the_ID();
			$date_range      = \CarkeekEvents_Display::get_date_range_html( $post_id );
			$location        = \CarkeekEvents_Display::get_event_location_html( $post_id );
			$organizer       = \CarkeekEvents_Display::get_event_organizer_html( $post_id );
			$event_link      = \CarkeekEvents_Display::get_event_link_html( $post_id );
			$add_to_calendar = \CarkeekEvents_Display::get_add_to_calendar_html( $post_id );
			?>

				<?php if ( $date_range ) : ?>
					<div class="carkeek-event__dates">
					<div class="event-label">Date & Time</div>
					<?php echo wp_kses_post( $date_range ); ?></div>
				<?php endif; ?>
				<?php if ( $location ) : ?>
					<div class="carkeek-event__location">
						<div class="event-label">Location</div>
						<?php echo wp_kses_post( $location ); ?>
					</div>
				<?php endif; ?>
				<?php if ( $organizer ) : ?>
					<div class="carkeek-event__organizer">
						<div class="event-label">Organizer</div>
						<?php echo wp_kses_post( $organizer ); ?>
					</div>
				<?php endif; ?>
				<?php if ( $event_link ) : ?>
					<div class="carkeek-event__link"><?php echo wp_kses_post( $event_link ); ?></div>
				<?php endif; ?>
				<?php if ( $add_to_calendar ) : ?>
					<div class="carkeek-event__add-to-calendar"><?php echo wp_kses_post( $add_to_calendar ); ?></div>
				<?php endif; ?>
			</div>
			<?php if ( has_post_thumbnail() ) :
					$hide_image = filter_var( get_post_meta( $post->ID, '_carkeekblocks_featuredimage_hidden', true ), FILTER_VALIDATE_BOOLEAN );
					if ( ! $hide_image ) :
				?>
			<div class="page-content-featured">
				<?php get_template_part( 'template-parts/content/entry-thumbnail', get_post_type(), array( 'is_header' => false, 'include_caption' => false ) ); ?>
			</div>
			<?php endif; ?>
		<?php endif; ?>

	</div>
	<div class="event-details">

	<?php
	get_template_part( 'template-parts/content/entry-content', get_post_type() );
	?>


	</div>
</article><!-- #post-<?php the_ID(); ?> -->


<?php if ( ! empty( block_template_part( get_post_type() . '-footer' ) ) ) : ?>

	<?php
	block_template_part( get_post_type() . '-footer' );
	?>

<?php endif; ?>
</div>