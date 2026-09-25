<?php
/**
 * The template for displaying search results pages
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/#search-result
 *
 * @package svpa
 */

namespace WP_Rig\WP_Rig;

get_header();

?>
	<main id="primary" class="site-main">
		<?php
		if ( have_posts() ) {

			get_template_part( 'template-parts/content/page-header' );
			?>
			<div class="page-content page-content-search archive-wrapper">
				<?php
				while ( have_posts() ) {
					the_post();

					get_template_part( 'template-parts/content/search', get_post_type() );
				}

				get_template_part( 'template-parts/content/pagination' );
				?>
			</div>
			<?php
		} else {

			?>

				<?php
				get_template_part( 'template-parts/content/error' );
		}
		?>

	</main><!-- #primary -->
<?php
get_footer();
