<?php
/**
 * WP_Rig\WP_Rig\Scripts\Component class
 *
 * @package svpa
 */

namespace WP_Rig\WP_Rig\Scripts;

use WP_Rig\WP_Rig\Component_Interface;
use function WP_Rig\WP_Rig\svpa;
use function add_filter;
/**
 * Class for managing scripts including jQuery.
 */
class Component implements Component_Interface {

	/**
	 * Associative array of JS files, as $handle => $data pairs.
	 * $data must be an array with keys 'file' (file path relative to 'assets/js' directory),
	 *
	 * Do not access this property directly, instead use the `get_js_files()` method.
	 *
	 * @var array
	 */
	protected $js_files;

	/**
	 * Gets the unique identifier for the theme component.
	 *
	 * @return string Component slug.
	 */
	public function get_slug(): string {
		return 'theme_scripts';
	}

		/**
		 * Adds the action and filter hooks to integrate with WordPress.
		 */
	public function initialize() {
		add_action( 'wp_enqueue_scripts', array( $this, 'register_global' ), 100 );
	}

	/**
	 * Gets all global js files.
	 *
	 * @return array Associative array of $handle => $data pairs.
	 */
	protected function get_js_files(): array {
		if ( is_array( $this->js_files ) ) {
			return $this->js_files;
		}

		$js_files = array(
			'svpa-global-js' => array(
				'file'         => 'global.min.js',
				'dependencies' => array(),
				'in_footer'    => true,
			),

		);
		/**
		 * Filters default CSS files.
		 *
		 * @param array $js_files Associative array of js files, as $handle => $data pairs.
		 *                         $data must be an array with keys 'file' (file path relative to 'assets/js'
		 *                         directory), and optionally 'dependencies'.
		 */
		$js_files       = apply_filters( 'svpa_js_files', $js_files );
		$this->js_files = array();
		foreach ( $js_files as $handle => $data ) {
			if ( is_string( $data ) ) {
				$data = array( 'file' => $data );
			}

			if ( empty( $data['file'] ) ) {
				continue;
			}

			$this->js_files[ $handle ] = array_merge(
				array(
					'dependencies' => null,
					'in_footer'    => true,
				),
				$data
			);
		}

		return $this->js_files;
	}
	/**
	 * Registers global js scripts
	 */
	public function register_global() {
		$js_uri = get_theme_file_uri( '/assets/js/' );
		$js_dir = get_theme_file_path( '/assets/js/' );

		$js_files = $this->get_js_files();
		foreach ( $js_files as $handle => $data ) {
			$version = svpa()->get_asset_version( $js_dir . $data['file'] );
			$asset   = $js_uri . svpa()->get_asset_path( $data['file'] );

			/*
			 * Enqueue global scripts
			 */
			wp_enqueue_script( $handle, $asset, $data['dependencies'], $version, $data['in_footer'] );
		}

		// load video-lite-script from carkeek-block if exists and not loaded.
		$blocks = WP_PLUGIN_DIR . '/carkeek-blocks';
		if ( is_dir( $blocks ) ) {
			$file = $blocks . '/build/video-lite/script.js';
			if ( file_exists( $file ) ) {
				wp_enqueue_script(
					'carkeek-blocks-video-lite-view-script',
					plugins_url( '/carkeek-blocks/build/video-lite/script.js' ),
					array(),
					filemtime( $file ),
					true
				);
			}
		}
	}
}
