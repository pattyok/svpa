<?php
/**
 * WP_Rig\WP_Rig\Styles\Component class
 *
 * @package wp_rig
 */

namespace WP_Rig\WP_Rig\Styles;

use WP_Rig\WP_Rig\Component_Interface;
use WP_Rig\WP_Rig\Templating_Component_Interface;
use function WP_Rig\WP_Rig\wp_rig;
use function add_action;
use function add_filter;
use function wp_enqueue_style;
use function wp_register_style;
use function wp_style_add_data;
use function get_theme_file_uri;
use function get_theme_file_path;
use function wp_styles;
use function esc_attr;
use function esc_url;
use function wp_style_is;
use function apply_filters;
use function add_query_arg;

/**
 * Class for managing stylesheets.
 *
 * Exposes template tags:
 * * `wp_rig()->print_styles()`
 */
class Component implements Component_Interface, Templating_Component_Interface {


	/**
	 * Associative array of CSS files, as $handle => $data pairs.
	 * $data must be an array with keys 'file' (file path relative to 'assets/css' directory), and optionally 'global'
	 * (whether the file should immediately be enqueued instead of just being registered) and 'preload_callback'
	 * (callback function determining whether the file should be preloaded for the current request).
	 *
	 * Do not access this property directly, instead use the `get_css_files()` method.
	 *
	 * @var array
	 */
	protected $css_files;

	/**
	 * Associative array of Google Fonts to load, as $font_name => $font_variants pairs.
	 *
	 * Do not access this property directly, instead use the `get_google_fonts()` method.
	 *
	 * @var array
	 */
	protected $google_fonts;

	/**
	 * Gets the unique identifier for the theme component.
	 *
	 * @return string Component slug.
	 */
	public function get_slug(): string {
		return 'styles';
	}

	/**
	 * Adds the action and filter hooks to integrate with WordPress.
	 */
	public function initialize() {
		add_action( 'wp_enqueue_scripts', array( $this, 'action_enqueue_styles' ), 50 );
		add_action( 'wp_head', array( $this, 'action_preload_styles' ) );
		add_action( 'enqueue_block_editor_assets', array( $this, 'action_add_editor_styles' ), 50 );
		add_action( 'after_setup_theme', array( $this, 'action_add_block_styles' ) );
		// This is necessary to load fa to the site editor. THough Im not sure if its workking.
		add_action( 'enqueue_block_editor_content_assets', array( $this, 'action_add_block_scripts' ), 50 );

		add_filter( 'wp_resource_hints', array( $this, 'filter_resource_hints' ), 10, 2 );
	}

	/**
	 * Gets template tags to expose as methods on the Template_Tags class instance, accessible through `wp_rig()`.
	 *
	 * @return array Associative array of $method_name => $callback_info pairs. Each $callback_info must either be
	 *               a callable or an array with key 'callable'. This approach is used to reserve the possibility of
	 *               adding support for further arguments in the future.
	 */
	public function template_tags(): array {
		return array(

		);
	}

	/**
	 * Registers or enqueues stylesheets.
	 *
	 * Stylesheets that are global are enqueued. All other stylesheets are only registered, to be enqueued later.
	 */
	public function action_enqueue_styles() {

		// Enqueue Google Fonts.
		$google_fonts_url = $this->get_google_fonts_url();
		if ( ! empty( $google_fonts_url ) ) {
			wp_enqueue_style( 'wp-rig-fonts', $google_fonts_url, array(), null ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
		}

		// Enqueue Typekit fonts if using.
		$typekit_fonts_url = $this->get_typekit_fonts_url();
		if ( ! empty( $typekit_fonts_url ) ) {
			wp_enqueue_style( 'wp-rig-typekit', $typekit_fonts_url, array(), null ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
		}

		// Enqueue Typekit fonts if using.
		$fontawesome_kit_url = $this->get_fontawesome_fonts_url();
		if ( ! empty( $fontawesome_kit_url ) ) {
			// dequeue um fontawesome
			wp_dequeue_style( 'um_fontawesome-css' );
			wp_enqueue_script( 'wp-rig-fa', $fontawesome_kit_url, array(), null, array( 'in_footer' => true ) ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
		}

		$css_uri = get_theme_file_uri( '/assets/css/global/' );
		$css_dir = get_theme_file_path( '/assets/css/global/' );

		$preloading_styles_enabled = $this->preloading_styles_enabled();

		$css_files = $this->get_css_files();
		foreach ( $css_files as $handle => $data ) {
			$version = wp_rig()->get_asset_version( $css_dir . $data['file'] );
			$asset   = $css_uri . wp_rig()->get_asset_path( $data['file'] );
			$deps    = array();
			/*
			* Enqueue global stylesheets immediately and register the other ones for later use
			* (unless preloading stylesheets is disabled, in which case stylesheets should be immediately
			* enqueued based on whether they are necessary for the page content).
			*/
			if ( $data['global'] || ! $preloading_styles_enabled && is_callable( $data['preload_callback'] ) && call_user_func( $data['preload_callback'] ) ) {
				wp_enqueue_style( $handle, $asset, $deps, $version, $data['media'] );
			} else {
				wp_register_style( $handle, $asset, $deps, $version, $data['media'] );
			}

			wp_style_add_data( $handle, 'precache', true );
		}
	}

	/**
	 * Preloads in-body stylesheets depending on what templates are being used.
	 *
	 * Only stylesheets that have a 'preload_callback' provided will be considered. If that callback evaluates to true
	 * for the current request, the stylesheet will be preloaded.
	 *
	 * Preloading is disabled when AMP is active, as AMP injects the stylesheets inline.
	 *
	 * @link https://developer.mozilla.org/en-US/docs/Web/HTML/Preloading_content
	 */
	public function action_preload_styles() {

		// If preloading styles is disabled, return early.
		if ( ! $this->preloading_styles_enabled() ) {
			return;
		}

		$wp_styles = wp_styles();

		$css_files = $this->get_css_files();
		foreach ( $css_files as $handle => $data ) {

			// Skip if stylesheet not registered.
			if ( ! isset( $wp_styles->registered[ $handle ] ) ) {
				continue;
			}

			// Skip if no preload callback provided.
			if ( ! is_callable( $data['preload_callback'] ) ) {
				continue;
			}

			// Skip if preloading is not necessary for this request.
			if ( ! call_user_func( $data['preload_callback'] ) ) {
				continue;
			}

			$preload_uri = $wp_styles->registered[ $handle ]->src . '?ver=' . $wp_styles->registered[ $handle ]->ver;

			echo '<link rel="preload" id="' . esc_attr( $handle ) . '-preload" href="' . esc_url( $preload_uri ) . '" as="style">';
			echo "\n";
		}
	}

	/**
	 * Enqueues WordPress theme styles for the editor.
	 */
	public function action_add_editor_styles() {

		// Enqueue Google Fonts.
		$google_fonts_url = $this->get_google_fonts_url();
		if ( ! empty( $google_fonts_url ) ) {
			wp_enqueue_style( 'wp-rig-google-fonts', $google_fonts_url, array(), null ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
		}

		// Enqueue Typekit fonts if using.
		$typekit_fonts_url = $this->get_typekit_fonts_url();
		if ( ! empty( $typekit_fonts_url ) ) {
			wp_enqueue_style( 'wp-rig-typekit', $typekit_fonts_url, array(), null ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
			add_editor_style( $typekit_fonts_url );
		}

		// Enqueue block editor stylesheet.
		add_editor_style( 'assets/css/global/editor-styles.min.css' );

		$fontawesome_kit_url = $this->get_fontawesome_fonts_url();
		if ( ! empty( $fontawesome_kit_url ) ) {
			wp_enqueue_script( 'wp-rig-fa', $fontawesome_kit_url, array(), null, array( 'in_footer' => true ) ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
		}
	}
	// /**
	// * Enqueues WordPress theme styles for the editor.
	// */
	// public function action_add_block_scripts() {
	// Enqueue Fontawesome kit if using.


	// }
	/**
	 * Enqueues block styles
	 */
	public function action_add_block_styles() {
		$styled_blocks = array(
			'core'           => array( 'button', 'column', 'cover', 'group', 'media-text', 'list', 'separator', 'image', 'navigation', 'pullquote' ),
			'carkeek-blocks' => array( 'accordion', 'custom-archive', 'extended-gallery', 'featured-image' ),
		);
		foreach ( $styled_blocks as $prefix => $blocks ) {
			foreach ( $blocks as $block_name ) {
				$args = array(
					'handle' => "trc-fse-$block_name",
					'src'    => get_theme_file_uri( "assets/css/blocks/$block_name.min.css" ),
					'ver'    => filemtime( get_theme_file_path( "assets/css/blocks/$block_name.min.css" ) ),
				);
				// Replace the "core" prefix if you are styling blocks from plugins.
				wp_enqueue_block_style( "$prefix/$block_name", $args );
				add_editor_style( "assets/css/blocks/$block_name.min.css" );
			}
		}
	}
	/**
	 * Adds preconnect resource hint for Google Fonts.
	 *
	 * @param  array  $urls          URLs to print for resource hints.
	 * @param  string $relation_type The relation type the URLs are printed.
	 * @return array URLs to print for resource hints.
	 */
	public function filter_resource_hints( array $urls, string $relation_type ): array {
		if ( 'preconnect' === $relation_type && wp_style_is( 'wp-rig-fonts', 'queue' ) ) {
			$urls[] = array(
				'href' => 'https://fonts.gstatic.com',
				'crossorigin',
			);
		}

		return $urls;
	}



	/**
	 * Determines whether to preload stylesheets and inject their link tags directly within the page content.
	 *
	 * Using this technique generally improves performance, however may not be preferred under certain circumstances.
	 * For example, since AMP will include all style rules directly in the head, it must not be used in that context.
	 * By default, this method returns true unless the page is being served in AMP. The
	 * {@see 'wp_rig_preloading_styles_enabled'} filter can be used to tweak the return value.
	 *
	 * @return bool True if preloading stylesheets and injecting them is enabled, false otherwise.
	 */
	protected function preloading_styles_enabled() {
		$preloading_styles_enabled = true;

		/**
		 * Filters whether to preload stylesheets and inject their link tags within the page content.
		 *
		 * @param bool $preloading_styles_enabled Whether preloading stylesheets and injecting them is enabled.
		 */
		return apply_filters( 'wp_rig_preloading_styles_enabled', $preloading_styles_enabled );
	}

	/**
	 * Gets all CSS files.
	 *
	 * @return array Associative array of $handle => $data pairs.
	 */
	protected function get_css_files(): array {
		if ( is_array( $this->css_files ) ) {
			return $this->css_files;
		}

		$css_files = array(
			'wp-rig-global' => array(
				'file'   => 'global.min.css',
				'global' => true,
			),
		);

		/**
		 * Filters default CSS files.
		 *
		 * @param array $css_files Associative array of CSS files, as $handle => $data pairs.
		 *                         $data must be an array with keys 'file' (file path relative to 'assets/css'
		 *                         directory), and optionally 'global' (whether the file should immediately be
		 *                         enqueued instead of just being registered) and 'preload_callback' (callback)
		 *                         function determining whether the file should be preloaded for the current request).
		 */
		$css_files = apply_filters( 'wp_rig_css_files', $css_files );

		$this->css_files = array();
		foreach ( $css_files as $handle => $data ) {
			if ( is_string( $data ) ) {
				$data = array( 'file' => $data );
			}

			if ( empty( $data['file'] ) ) {
				continue;
			}

			$this->css_files[ $handle ] = array_merge(
				array(
					'global'           => false,
					'preload_callback' => null,
					'media'            => 'all',
				),
				$data
			);
		}

		return $this->css_files;
	}

	/**
	 * Returns Google Fonts used in theme.
	 *
	 * @return array Associative array of $font_name => $font_variants pairs.
	 */
	protected function get_google_fonts(): array {
		if ( is_array( $this->google_fonts ) ) {
			return $this->google_fonts;
		}

		$google_fonts = array();

		/**
		 * Filters default Google Fonts.
		 *
		 * @param array $google_fonts Associative array of $font_name => $font_variants pairs.
		 */
		$this->google_fonts = (array) apply_filters( 'wp_rig_google_fonts', $google_fonts );

		return $this->google_fonts;
	}

	/**
	 * Returns the Google Fonts URL to use for enqueuing Google Fonts CSS.
	 *
	 * Uses `latin` subset by default. To use other subsets, add a `subset` key to $query_args and the desired value.
	 *
	 * @return string Google Fonts URL, or empty string if no Google Fonts should be used.
	 */
	protected function get_google_fonts_url(): string {
		$google_fonts = $this->get_google_fonts();

		if ( empty( $google_fonts ) ) {
			return '';
		}

		$font_families = array();

		foreach ( $google_fonts as $font_name => $font_variants ) {
			if ( ! empty( $font_variants ) ) {
				if ( ! is_array( $font_variants ) ) {
					$font_variants = explode( ',', str_replace( ' ', '', $font_variants ) );
				}

				$font_families[] = $font_name . ':' . implode( ',', $font_variants );
				continue;
			}

			$font_families[] = $font_name;
		}

		$query_args = array(
			'family'  => implode( '|', $font_families ),
			'display' => 'swap',
		);

		return add_query_arg( $query_args, 'https://fonts.googleapis.com/css' );
	}

	/**
	 * Returns the Typekit URL to use for enqueuing.
	 *
	 * If not using just empty the string.
	 *
	 * @return string Adobe Typekit URL, or empty string if no typekit should be used.
	 */
	protected function get_typekit_fonts_url(): string {
		return 'https://use.typekit.net/tvr1eqm.css';
	}

	/**
	 * Returns the Fontawesome URL to use for enqueuing.
	 *
	 * If not using just empty the string.
	 *
	 * @return string  URL, or empty string if no kit should be used.
	 */
	protected function get_fontawesome_fonts_url(): string {
		return '';
	}
}
