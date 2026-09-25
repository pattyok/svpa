<?php
/**
 * Template part for displaying a post's footer
 *
 * @package svpa
 */

namespace WP_Rig\WP_Rig;

?>
<footer class="entry-footer page-content">
	<div class="entry-post-nav">
		<div class="prev-post-link arrow-link arrow-link--prev">
			<?php previous_post_link( '%link', '%title' ); ?>
		</div>
		<div class="next-post-link arrow-link arrow-link--next">
			<?php next_post_link( '%link', '%title' ); ?>
		</div>
	</div>
</footer><!-- .entry-footer -->
