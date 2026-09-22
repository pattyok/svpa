<?php
/**
 * The header for our theme
 *
 * This is the template that displays all of the <head> section and everything up until <div id="content">
 *
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
 *
 * @packagesvpa
 */

?>
<!doctype html>
<html <?php language_attributes(); ?> id="root">
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="profile" href="https://gmpg.org/xfn/11">

	<?php wp_head(); ?>
</head>

<body <?php body_class( 'no-js' ); ?>>
<script>document.body.className = document.body.className.replace(' no-js ', ' ');</script>
<?php wp_body_open(); ?>
<div id="page" class="site">
	<a class="skip-link screen-reader-text" href="#content"><?php esc_html_e( 'Skip to content', 'svpa' ); ?></a>

	<header id="masthead" class="site-header">
		<div class="site-branding">
			<?php
			$svpa_title_tag = is_front_page() || is_home() ? 'h1' : 'div';
			?>
			<<?php echo $svpa_title_tag; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> class="site-title">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home"<?php echo $svpa_title_tag === 'h1' ? ' aria-current="page"' : ''; ?>>
					<?php echo svpa_get_svg( 'logo', [] ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<span class="screen-reader-text"><?php bloginfo( 'name' ); ?></span>
				</a>
			</<?php echo $svpa_title_tag; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
		</div><!-- .site-branding -->

		<?php get_template_part( 'template-parts/navigation-main' ); ?>
	</header><!-- #masthead -->
