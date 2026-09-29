<?php
/**
 * Register post meta.
 *
 * @package   VEMgmt
 * @author    Patty O'Hara from VEMgmt
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * VEMgmt_Post_Meta Class
 *
 * @since 1.6.0
 */
class VEMgmt_Register {
	/**
	 * This plugin's instance.
	 *
	 * @var VEMgmt_Register
	 */
	private static $instance;

	/**
	 * Registers the plugin.
	 */
	public static function register() {
		if ( null === self::$instance ) {
			self::$instance = new VEMgmt_Register();
		}
	}

	/**
	 * The Plugin slug.
	 *
	 * @var string $slug
	 */
	private $slug;

	/**
	 * The base URL path (without trailing slash).
	 *
	 * @var string $url
	 */
	private $url;

	/**
	 * The Plugin version.
	 *
	 * @var string $version
	 */
	private $version;

	/**
	 * The Constructor.
	 */
	/**
	 * The Constructor.
	 */
	private function __construct() {
		add_action( 'init', array( $this, 'register_blocks' ) );
	}

	/**
	 * Registers the block using the metadata loaded from the `block.json` file.
	 * Behind the scenes, it registers also all assets so they can be enqueued
	 * through the block editor in the corresponding context.
	 *
	 * @see https://developer.wordpress.org/reference/functions/register_block_type/
	 *
	 * The fancy version of the location-details is not being used but keeping it around for a while, we may make use of it later.
	 * I think we have to switch away from ACF to register the fields to make it work though.
	 */
	function register_blocks() {
		register_block_type( plugin_dir_path( dirname( __FILE__ ) ) . 'build/event-registration' );
		register_block_type( plugin_dir_path( dirname( __FILE__ ) ) . 'build/event-shifts' );
	}


}
VEMgmt_Register::register();
