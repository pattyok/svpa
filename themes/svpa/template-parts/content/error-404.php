<?php
/**
 * Template part for displaying the page content when a 404 error has occurred
 *
 * @package svpa
 */

namespace WP_Rig\WP_Rig;

?>
<section class="error">


	<div class="page-content">
		<h1 style="margin-top: 3rem">Uh-oh, this page is not found.</h1>
		<p>
			<?php echo __( 'Try searching for the page by name. If you think you reached this in error, please <a href="mailto:info@waf2snetwork.org">reach out</a>.', 'svpa' ); ?>
		</p>
		<div class="page-content-search">
		<?php
		get_search_form();
		?>
		</div>
	</div><!-- .page-content -->
</section><!-- .error -->
