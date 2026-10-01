<?php
/**
 * Template part for displaying a post
 *
 * @package svpa
 */

namespace WP_Rig\WP_Rig;

?>


<article id="post-<?php the_ID(); ?>" <?php post_class( 'entry page-content single-entry' ); ?>>

	<?php if ( get_post_type() === 'post' ) : ?>
		<div class="entry-header">
			<div class="entry-title">
				<?php
				svpa()->make_breadcrumbs( get_post_type() );
				?>
			</div>
			<div class="entry-meta">
				<div class="entry-details">

					<?php

					$byline = '';
					if ( function_exists( 'get_field' ) ) :
						if ( ! empty( get_field( 'post_byline' ) ) ) :
							$byline = get_field( 'post_byline' );
						endif;
					endif;
					?>
					<?php if ( ! empty( $byline ) ) : ?>
						<div class="entry-byline">

							<span class="meta-label">By:</span>
							<?php echo wp_kses_post( $byline ); ?>

						</div>
					<?php endif; ?>
					<div class="entry-date">Published: <?php the_date(); ?></div>

				</div>
				<?php svpa()->make_social_share_links( true ); ?>

			</div>
		</div>
	<?php endif; ?>
	<?php
	get_template_part( 'template-parts/content/entry-content', get_post_type() );
	?>



</article><!-- #post-<?php the_ID(); ?> -->


<?php if ( ! empty( block_template_part( get_post_type() . '-footer' ) ) ) : ?>

	<?php
	block_template_part( get_post_type() . '-footer' );
	?>

<?php endif; ?>
</div>