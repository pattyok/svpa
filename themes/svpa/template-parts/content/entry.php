<?php
/**
 * Template part for displaying a post
 *
 * @package svpa
 */

namespace WP_Rig\WP_Rig;

// with jetpack infinite scroll they grab the the entry template.
if ( is_search() ) {
	get_template_part( 'template-parts/content/search', get_post_type() );
} else {
	?>

<article id="post-<?php the_ID(); ?>" <?php post_class( 'entry page-content single-entry' ); ?>>
	<?php get_template_part( 'template-parts/content/entry-thumbnail', get_post_type(), array( 'is_header' => false ) ); ?>
	<div class="entry-body">
		<div class="entry-title">
			<a href="<?php the_permalink(); ?>"><?php the_title( '<h2>', '</h2>' ); ?></a>
		</div>
		<?php
		get_template_part( 'template-parts/content/entry-meta', get_post_type(), array( 'show_social' => false ) );

		get_template_part( 'template-parts/content/entry-summary', get_post_type() );

		?>
	</div>
</article><!-- #post-<?php the_ID(); ?> -->

	<?php
	if ( is_singular( get_post_type() ) ) {
		// Show post navigation only when the post type is 'post' or has an archive.
		if ( 'post' === get_post_type() || get_post_type_object( get_post_type() )->has_archive ) {
			the_post_navigation(
				array(
					'prev_text' => '<div class="post-navigation-sub"><span>' . esc_html__( 'Previous:', 'svpa' ) . '</span></div>%title',
					'next_text' => '<div class="post-navigation-sub"><span>' . esc_html__( 'Next:', 'svpa' ) . '</span></div>%title',
				)
			);
		}

		// Show comments only when the post type supports it and when comments are open or at least one comment exists.
		if ( post_type_supports( get_post_type(), 'comments' ) && ( comments_open() || get_comments_number() ) ) {
			comments_template();
		}
	}
}
