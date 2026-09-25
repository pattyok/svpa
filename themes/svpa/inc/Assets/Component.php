<?php
/**
 * WP_Rig\WP_Rig\Custom_Background\Component class
 *
 * @package svpa
 */

namespace WP_Rig\WP_Rig\Assets;

use WP_Rig\WP_Rig\Component_Interface;
use function add_action;


/**
 * Class for asset management support.
 * Looks for a manifest file, if found loads css/js with unique string to prevent cacheing on updated files.
 */
class Component implements Component_Interface {

	/**
	 * Stores the current manifest.
	 *
	 * @var string
	 */
	private $manifest;

	/**
	 * Gets the unique identifier for the theme component.
	 *
	 * @return string Component slug.
	 */
	public function get_slug(): string {
		return 'json_manifest';
	}

	/**
	 * Sets the manifest variable.
	 *
	 * @param string $manifest_path path to the manifest file.
	 */
	public function __construct( $manifest_path ) {
		if ( file_exists( $manifest_path ) ) {
			$this->manifest = json_decode( file_get_contents( $manifest_path ), true );
		} else {
			$this->manifest = array();
		}
	}

	/**
	 * Empty function not used but required by Component.
	 */
	public function initialize() {}

	/**
	 * Returns the manifest content.
	 *
	 * @return array $manifest content.
	 */
	public function get() {
		return $this->manifest;
	}

	/**
	 * Returns the whole collection or one item if key is provided.
	 *
	 * @param string $key the file key in the manifest.
	 * @param string $default will return default if no file is found in the manifest.
	 *
	 * @return string or array depending on inputs.
	 */
	public function getPath( $key = '', $default = null ) {
		$collection = $this->manifest;
		if ( is_null( $key ) ) {
			return $collection;
		}
		if ( isset( $collection[ $key ] ) ) {
			return $collection[ $key ];
		}
		foreach ( explode( '.', $key ) as $segment ) {
			if ( ! isset( $collection[ $segment ] ) ) {
				return $default;
			} else {
				$collection = $collection[ $segment ];
			}
		}
		return $collection;
	}
}
