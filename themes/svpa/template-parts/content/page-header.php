<?php
/**
 * Template part for displaying the page header of the currently displayed page
 *
 * @package svpa
 */

namespace WP_Rig\WP_Rig;

use WP_Rig\WP_Rig\Helpers;


if ( is_404() ) {
	?>
	<header class="page-header">
		<h1 class="page-title">
			<?php esc_html_e( 'Oops! That page can&rsquo;t be found.', 'svpa' ); ?>
		</h1>
	</header><!-- .page-header -->
	<?php
} elseif ( is_home() && ! have_posts() ) {
	?>
	<header class="page-header">
		<h1 class="page-title">
			<?php esc_html_e( 'Nothing Found', 'svpa' ); ?>
		</h1>
	</header><!-- .page-header -->
	<?php
} elseif ( is_home() && ! is_front_page() ) {
	$page_for_posts = get_option( 'page_for_posts' );
	$feat_image_id  = get_post_thumbnail_id( $page_for_posts );
	$add_class      = '';
	$image          = '';

	if ( ! empty( $feat_image_id ) ) {
		$add_class = 'has-post-thumbnail';
		$image     = '<div class="post-thumbnail">' . wp_get_attachment_image( $feat_image_id, 'large' ) . '</div>';
	}
	?>
	<header class="post-archive-header page-header <?php echo esc_attr( $add_class ); ?>">

		<?php echo wp_kses_post( $image ); ?>

		<div class="entry-title">
		<?php
			svpa()->make_breadcrumbs( get_post_type() );
		?>
			<h1 class="page-title">

				<?php single_post_title(); ?>
			</h1>
		</div>

	</header><!-- .page-header -->
	<div class="wp-block-group is-page-intro blog-intro-wrapper"><div class="wp-block-group__inner-container">
		<p class="has-text-align-center blog-intro"><?php echo wp_kses_post( get_theme_mod( 'blog_header_intro' ) ); ?></p>
	</div></div>

	<?php
} elseif ( is_search() ) {
	?>
	<header class="page-header">
		<h1 class="page-title">
			<?php
			printf(
				/* translators: %s: search query */
				esc_html__( 'Search Results for: %s', 'svpa' ),
				'<span>' . get_search_query() . '</span>'
			);
			?>
		</h1>
	</header><!-- .page-header -->
	<?php
} elseif ( is_archive() ) {
	?>
	<header class="page-header archive-header">
		<div class="entry-title">
		<?php
			svpa()->make_breadcrumbs( $args['post_type'], false );
		?>
			<?php
			the_archive_title( '<h1 class="page-title">', '</h1>' );
			?>
		</div>

	</header><!-- .page-header -->
	<?php
		the_archive_description( '<div class="archive-description is-style-intro-text">', '</div>' );
	?>
	<?php
} elseif ( is_page() || is_singular() ) {
	$hide_title = filter_var( get_post_meta( $post->ID, '_carkeekblocks_title_hidden', true ), FILTER_VALIDATE_BOOLEAN );
	$hide_image = filter_var( get_post_meta( $post->ID, '_carkeekblocks_featuredimage_hidden', true ), FILTER_VALIDATE_BOOLEAN );

	$header_class   = '';
	$header_content = '';
	$header_style   = '';

	if ( 'post' === get_post_type() ) {
		$hide_title = true;
	}


	if ( true !== $hide_image && ( has_post_thumbnail() ) ) {
		$header_class .= 'has-post-thumbnail';
		$show_image    = true;
	} else {
		$hide_image = true;
	}

	if ( true !== $hide_image || true !== $hide_title ) {
		?>
	<header class="page-header <?php echo esc_attr( $header_class ); ?>" <?php echo esc_attr( $header_style ); ?>>
		<?php
		if ( true !== $hide_image ) {
			get_template_part( 'template-parts/content/entry-thumbnail', get_post_type(), array( 'is_header' => true ) );
		}
		if ( true !== $hide_title ) {
			?>
		<div class="entry-title">
			<?php
			svpa()->make_breadcrumbs( get_post_type() );
			?>
		</div>
			<?php
		}
		?>
		</header><!-- .page-header -->
		<?php
	}
	?>
	<?php
}
