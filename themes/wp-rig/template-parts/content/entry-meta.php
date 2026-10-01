<?php
/**
 * Template part for displaying a post's header
 *
 * @package wp_rig
 */

namespace WP_Rig\WP_Rig;

?>

	<div class="postmeta">
	<?php
	if ( 'post' === get_post_type() ) {
		if ( true === $args['show_social'] ) {
			wp_rig()->make_social_share_links( true );
		}
	}

	?>
	</div>


