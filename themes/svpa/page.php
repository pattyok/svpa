<?php
/**
 * Render your site front page, whether the front page displays the blog posts index or a static page.
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/#front-page-display
 *
 * @package svpa
 */

namespace WP_Rig\WP_Rig;

get_header();


?>
	<main id="primary" class="site-main">
		<?php
		get_template_part( 'template-parts/content/page-header' );
		while ( have_posts() ) {
			the_post();

			get_template_part( 'template-parts/content/entry-page' );
		}

		get_template_part( 'template-parts/content/pagination' );
		?>
	</main><!-- #primary -->
<?php
get_footer();
