<?php
/**
 * Template part for displaying the header search
 *
 * @package svpa
 */

namespace WP_Rig\WP_Rig;

$label = '';
if ( ! empty( $args ) ) {
	$label = isset( $args['label'] ) ? $args['label'] : '';
}
?>


<div id="search_modal" class="nav--toggle-small header-search-wrapper">
	<button class="search-toggle header-toggle" data-toggleoff="primary-menu-toggle" id="search-toggle" data-target="#header-search" aria-label="<?php esc_attr_e( 'Open search', 'svpa' ); ?>" aria-controls="header-search" aria-expanded="false">
	<span class="search-label"><?php echo esc_html( $label ); ?></span>
	<span class="search-toggle-box">
	<span class="search-toggle__circle"></span>
		<span class="search-toggle__rectangle"></span>
	</span>
	</button>
	<div class="header-search search-dropdown__content">
		<?php get_search_form( true ); ?>
	</div>
</div>

