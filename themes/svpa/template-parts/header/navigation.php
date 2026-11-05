<?php
/**
 * Template part for displaying the header navigation menu
 *
 * @package svpa
 */

namespace WP_Rig\WP_Rig;

if ( ! svpa()->is_primary_nav_menu_active() ) {
	return;
}

?>

<nav id="site-navigation" class="main-navigation nav--toggle-sub nav--toggle-small" aria-label="<?php esc_attr_e( 'Main menu', 'svpa' ); ?>">


	<button class="header-toggle menu-toggle hamurger hamburger--spring" aria-label="<?php esc_attr_e( 'Open menu', 'svpa' ); ?>" aria-controls="primary-menu" aria-expanded="false">
		<span class="hamburger-box">
			<span class="hamburger-inner"></span>
		</span>
		<span class="menu-toggle-label menu-closed"><?php esc_html_e( 'Menu', 'svpa' ); ?></span>
		<span class="menu-toggle-label menu-open"><?php esc_html_e( 'Close', 'svpa' ); ?></span>

	</button>


	<div class="primary-menu-container" id="primary-menu-container">
		<?php svpa()->display_primary_nav_menu( array( 'menu_id' => 'primary-menu' ) ); ?>

		</div><!-- .primary-menu-container -->
</nav><!-- #site-navigation -->
