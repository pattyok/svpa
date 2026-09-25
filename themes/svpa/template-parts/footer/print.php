<?php
/** This content displays in the print view only
 *
 * @package svpa
 */

?>
<div class="print-only">
	<?php
	echo '&copy;' . date( 'Y' ) . ' ' . get_bloginfo('name') . '<br>';
	echo get_permalink() . '<br>'; //phpcs:ignore
	echo wp_date( 'F j, Y g:i a' ); //phpcs:ignore
	?>
</div>
