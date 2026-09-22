<?php
/**
 * The header for our theme
 *
 * This is the template that displays all of the <head> section and everything up until <div id="content">
 *
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
 *
 * @package wp_rig
 */

namespace WP_Rig\WP_Rig;

?>
<!doctype html>
<html <?php language_attributes(); ?> class="no-js">
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1">
	<link rel="profile" href="http://gmpg.org/xfn/11">

		<script>document.documentElement.classList.remove( 'no-js' );</script>

	<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<div id="page" class="site">
	<a class="skip-link screen-reader-text" href="#primary"><?php esc_html_e( 'Skip to content', 'wp-rig' ); ?></a>

	<header id="masthead" class="site-header">
		<div class="site-header--inner">

			<?php get_template_part( 'template-parts/header/branding' ); ?>
			<div class="header-top">
				<?php get_template_part( 'template-parts/header/navigation-top' ); ?>
			</div>
			<div class="header-nav-wrapper">
				<?php get_template_part( 'template-parts/header/before-navigation' ); ?>
				<?php get_template_part( 'template-parts/header/navigation' ); ?>
				<?php get_template_part( 'template-parts/header/search' ); ?>
				<?php get_template_part( 'template-parts/header/after-navigation' ); ?>

			</div>
		</div>

	</header><!-- #masthead -->
	<div class="overlay"></div>
<?php
