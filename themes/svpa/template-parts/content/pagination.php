<?php
/**
 * Template part for displaying a pagination
 *
 * @package svpa
 */

namespace WP_Rig\WP_Rig;

?>
<nav class="single-nav-links">
	<?php previous_post_link( '<span class="nav-link nav-previous"> %link </span>', _x( '<span class="nav-label prev">&larr; Previous</span><span class="nav-title"> %title </span>', 'Previous post link', 'svpa' ), true ); ?>
	<?php next_post_link( '<span class="nav-link nav-next"> %link </span>', _x( '<span class="nav-label next">Next &rarr;</span><span class="nav-title"> %title </span>', 'Next post link', 'svpa' ), true ); ?>
</nav>
