<?php

/**
 * Template part for displaying a post
 *
 * @package svpa
 */

namespace WP_Rig\WP_Rig;

?>


<article id="post-<?php the_ID(); ?>" <?php post_class( 'entry page-content single-entry' ); ?>>

	<?php
	block_template_part( 'people' );
	?>


</article><!-- #post-<?php the_ID(); ?> -->


<?php if ( ! empty( block_template_part( get_post_type() . '-footer' ) ) ) : ?>

	<?php
	block_template_part( get_post_type() . '-footer' );
	?>

<?php endif; ?>
</div>