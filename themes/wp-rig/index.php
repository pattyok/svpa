<?php
/**
 * The main template file
 *
 * This is the most generic template file in a WordPress theme
 * and one of the two required files for a theme (the other being style.css).
 * It is used to display a page when nothing more specific matches a query.
 * E.g., it puts together the home page when no home.php file exists.
 *
 * @link https://codex.wordpress.org/Template_Hierarchy
 *
 * @package wp_rig
 */

namespace WP_Rig\WP_Rig;

get_header();

?>

	<main id="primary" class="site-main">
		<?php
		if ( have_posts() ) {
			if ( get_post_type() != 'people' ) {
				get_template_part( 'template-parts/content/page-header', get_post_type() );
			}

			if ( ! is_singular() ) {
				?>
				<?php block_template_part( 'archive' ); ?>
				<?php
			} else {
				while ( have_posts() ) {
					the_post();

					if ( ! is_singular() ) {
						get_template_part( 'template-parts/content/entry', get_post_type() );
					} else {
						get_template_part( 'template-parts/content/single', get_post_type() );
					}
				}
			}
		} else {
			get_template_part( 'template-parts/content/error' );
		}
		?>
	</main><!-- #primary -->
<?php

get_footer();
