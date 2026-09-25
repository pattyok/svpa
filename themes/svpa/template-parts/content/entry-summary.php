<?php
/**
 * Template part for displaying a post's summary
 *
 * @package svpa
 */

namespace WP_Rig\WP_Rig;

?>

<div class="entry-summary">
	<?php echo wp_kses_post( svpa()->get_custom_excerpt( 30, false ) ); ?>
</div><!-- .entry-summary -->
