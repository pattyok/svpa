<?php
/**
 * The sidebar containing the main widget area
 *
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
 *
 * @package svpa
 */

namespace WP_Rig\WP_Rig;

if ( ! svpa()->is_primary_sidebar_active() ) {
	return;
}


?>
<aside id="secondary" class="primary-sidebar widget-area">
	<?php svpa()->display_primary_sidebar(); ?>
</aside><!-- #secondary -->
