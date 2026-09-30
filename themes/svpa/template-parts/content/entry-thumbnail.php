<?php
/**
 * Template part for displaying a post's featured image
 *
 * @package svpa
 */

namespace WP_Rig\WP_Rig;

// Audio or video attachments can have featured images, so they need to be specifically checked.
$support_slug = get_post_type();
if ( 'attachment' === $support_slug ) {
	if ( wp_attachment_is( 'audio' ) ) {
		$support_slug .= ':audio';
	} elseif ( wp_attachment_is( 'video' ) ) {
		$support_slug .= ':video';
	}
}
if ( isset( $args ) && isset( $args['post_id'] ) ) {
	$postid = $args['post_id'];
} else {
	$postid = $post->ID;
}

if ( ! has_post_thumbnail( $postid ) || post_password_required() || ! post_type_supports( $support_slug, 'thumbnail' ) ) {
	return;
}
$thumb_class  = '';
$thumb_style  = '';
$photo_credit = '';


$is_header   = isset( $args ) && isset( $args['is_header'] ) && true == $args['is_header'];
$focal_point = get_post_meta( $postid, '_carkeekblocks_featured_image_focal_point', true );
$style       = '';
if ( ! empty( $focal_point ) ) {
	$x     = $focal_point['x'] * 100;
	$y     = $focal_point['y'] * 100;
	$style = 'object-position:' . esc_attr( $x ) . '% ' . esc_attr( $y ) . '%;';

}
if ( $is_header ) {

	$photo_credit = get_post_meta( get_post_thumbnail_id(), 'ck_photo_credit', true );

	$use_opacity = get_option( '_carkeekblocks_featuredimage_use_opacity', false );
	if ( true == $use_opacity ) {
		$image_opacity = get_post_meta( $postid, '_carkeekblocks_featured_image_opacity', true );
		// if 101 that is default, so set to 0.
		$image_opacity = 101 == $image_opacity ? 0 : $image_opacity;
		if ( $image_opacity && 0 !== $image_opacity && 101 !== $image_opacity ) {
			$thumb_class .= ' has-image-opacity';
			$thumb_style  = '--featured-image-opacity: ' . $image_opacity;
		}
	}
}

if ( is_singular( get_post_type() ) || $is_header ) {
	/** Pages and posts. */

	?>
	<div class="post-thumbnail <?php echo esc_attr( $thumb_class ); ?>" style="<?php echo $thumb_style; //phpcs:ignore ?>">
		<?php
		echo get_the_post_thumbnail(
			$postid,
			'x-large',
			array(
				'class' => 'skip-lazy',
				'style' => $style,
			)
		);
		?>
		<?php if ( $photo_credit ) : ?>
			<p class="ck-photo-credit ck-photo-credit--overlay"><?php echo wp_kses_post( $photo_credit ); ?></p>
		<?php endif; ?>
	</div><!-- .post-thumbnail -->

	<?php
} else {

		$feat_image_id = get_post_thumbnail_id();

	?>
	<a class="post-thumbnail" href="<?php the_permalink(); ?>" aria-hidden="true">
		<?php
		global $wp_query;

		if ( 0 === $wp_query->current_post ) {
			echo wp_get_attachment_image(
				$feat_image_id,
				'x-large',
				false,
				array(
					'class' => 'skip-lazy',
					'style' => $style,
					'alt'   => the_title_attribute(
						array(
							'echo' => false,
						)
					),
				)
			);
		} else {
			echo wp_get_attachment_image(
				$feat_image_id,
				'medium_large',
				false,
				array(
					'style' => $style,
					'alt'   => the_title_attribute(
						array(
							'echo' => false,
						)
					),
				)
			);
		}
		?>
	</a><!-- .post-thumbnail -->
	<?php
}
