<?php
/**
 * Add custom style variations to blocks
 *
 * @packagesvpa
 */

namespace SVPATheme;

add_filter( 'after_setup_theme', __NAMESPACE__ . '\register_block_styles', 999 );
/**
 * Register all styles for blocks that I'll apply with custom CSS
 *
 * @return void
 */
function register_block_styles() {
	/* Headings */
	register_block_style(
		'core/heading',
		[
			'name'  => 'screen-reader-text',
			'label' => __( 'Screen Reader', 'svpa' ),
			'inline_style' => '.is-style-logos .wp-block-image{max-width:12rem!important;margin:auto!important;padding:var(--wp--preset--spacing--30)!important;}',
		]
	);

	/* Lists */
	register_block_style(
		'core/list',
		[
			'name'  => 'no-markers',
			'label' => __( 'No Markers', 'svpa' ),
		]
	);

	register_block_style(
		'core/list',
		[
			'name'  => 'two-col',
			'label' => __( 'Two Col', 'svpa' ),
		]
	);

	register_block_style(
		'core/list',
		[
			'name'  => 'two-col-no-markers',
			'label' => __( 'Two Col No Markers', 'svpa' ),
		]
	);

	register_block_style(
		'core/list',
		[
			'name'  => 'multicol',
			'label' => __( 'Multicol', 'svpa' ),
		]
	);

	register_block_style(
		'core/list',
		[
			'name'  => 'multicol-no-markers',
			'label' => __( 'Multicol No Markers', 'svpa' ),
		]
	);

	/* Gallery */
	register_block_style(
		'core/gallery',
		[
			'name'  => 'not-stretched',
			'label' => __( 'Not Stretched', 'svpa' ),
			'inline_style' => 'is-style-not-stretched .wp-block-image{flex-grow:0!important; margin:auto!important;}',
		]
	);
	register_block_style(
		'core/gallery',
		[
			'name'  => 'logos',
			'label' => __( 'Logos', 'svpa' ),
			'inline_style' => '.is-style-logos .wp-block-image{max-width:12rem!important;margin:auto!important;padding:var(--wp--preset--spacing--30)!important;}',
		]
	);
	register_block_style(
		'core/gallery',
		[
			'name'  => 'logos-grayscale',
			'label' => __( 'Logos Grayscale', 'svpa' ),
			'inline_style' => '.is-style-logos-grayscale .wp-block-image{max-width:12rem!important;margin:auto!important;padding:var(--wp--preset--spacing--30)!important;img{filter:grayscale(1) contrast(1.2);transition:filter 0.25s;}a:hover img,.is-style-logos-grayscale a:focus img{filter:grayscale(0) contrast(1);}',
		]
	);
}
