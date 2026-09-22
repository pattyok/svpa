<?php
/**
 * Template part for displaying a message that posts cannot be found
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @packagesvpa
 */

?>
<article class="no-results not-found flow">
	<header class="page-header is-layout-constrained">
		<h1 class="page-title"><?php esc_html_e( 'No results', 'svpa' ); ?></h1>
	</header><!-- .page-header -->

	<div class="page-content is-root-container is-layout-constrained">
		<?php
		if ( is_search() ) :
			?>

			<p><?php esc_html_e( 'Nothing matched your search term. Try a different search.', 'svpa' ); ?></p>
			<?php
			get_search_form();

		else :
			?>

			<p><?php esc_html_e( 'No content found. Try searching instead.', 'svpa' ); ?></p>
			<?php
			get_search_form();

		endif;
		?>
	</div><!-- .page-content -->
</article><!-- .no-results -->
