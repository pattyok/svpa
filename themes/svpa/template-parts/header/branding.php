<?php
/**
 * Template part for displaying the header branding
 *
 * @package svpa
 */

namespace WP_Rig\WP_Rig;

$title_tagline_display = get_theme_mod( 'title_tagline_display' );
$tabindex              = 0;
$title_style           = '';
$tagline_style         = '';
if ( 'none' === $title_tagline_display || 'tagline_only' === $title_tagline_display ) {
	$tabindex    = -1;
	$title_style = 'screen-reader-text';
}
if ( 'none' === $title_tagline_display || 'title_only' === $title_tagline_display ) {
	$tagline_style = 'screen-reader-text';
}
?>

<div class="site-branding">
	<?php the_custom_logo(); ?>

	<?php
	if ( is_front_page() && is_home() ) {
		?>
		<h1 class="site-title"><a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home" tabindex="<?php echo esc_attr( $tabindex ); ?>"><span class="<?php echo esc_attr( $title_style ); ?>"><?php bloginfo( 'name' ); ?></span></a></h1>
		<?php
	} else {
		?>
		<p class="site-title"><a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home" tabindex="<?php echo esc_attr( $tabindex ); ?>"><span class="<?php echo esc_attr( $title_style ); ?>"><?php bloginfo( 'name' ); ?></span></a></p>
		<?php
	}
	?>

	<?php
	$svpa_description = get_bloginfo( 'description', 'display' );
	if ( $svpa_description || is_customize_preview() ) {
		?>
		<p class="site-description <?php echo esc_attr( $tagline_style ); ?>">
			<?php echo $svpa_description; /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ ?>
		</p>
		<?php
	}
	?>
</div><!-- .site-branding -->
