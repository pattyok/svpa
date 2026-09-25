<?php
/**
 * Template part for displaying a post
 *
 * @package svpa
 */

namespace WP_Rig\WP_Rig;

$page_link = get_permalink();

?>

<article id="post-<?php the_ID(); ?>" <?php post_class( 'entry search-entry' ); ?>>


	<div class="search-entry--content">
	<h2><a href="<?php echo esc_url( $page_link ); ?>"><?php the_title(); ?></a></h2>
	<?php
	get_template_part( 'template-parts/content/entry-summary', get_post_type() );

	?>
	</div>
</article><!-- #post-<?php the_ID(); ?> -->
