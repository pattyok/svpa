<?php
/**
 * Template part for displaying a post's header
 *
 * @package svpa
 */

namespace WP_Rig\WP_Rig;

?>

	<div class="postmeta">
	<?php
	if ( 'post' === get_post_type() ) {
		if ( true === $args['show_social'] ) {
			svpa()->make_social_share_links( true );
		}
	}

	?>
	</div>


