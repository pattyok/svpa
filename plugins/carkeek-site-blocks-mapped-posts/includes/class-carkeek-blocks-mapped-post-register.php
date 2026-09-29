<?php
/**
 * Register post meta.
 *
 * @package   CarkeekMappedPosts
 * @author    Patty O'Hara from CarkeekMappedPosts
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * CarkeekMappedPosts_Post_Meta Class
 *
 * @since 1.6.0
 */
class CarkeekMappedPosts_Register {
	/**
	 * This plugin's instance.
	 *
	 * @var CarkeekMappedPosts_Register
	 */
	private static $instance;

	/**
	 * Registers the plugin.
	 */
	public static function register() {
		if ( null === self::$instance ) {
			self::$instance = new CarkeekMappedPosts_Register();
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
		add_action( 'enqueue_block_assets', array( $this, 'carkeek_blocks_mapped_posts_block_enqueue_assets' ), 9999 );
		add_action( 'init', array( $this, 'carkeek_blocks_mapped_posts_block_block_init' ) );
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
	function carkeek_blocks_mapped_posts_block_block_init() {
		register_block_type( plugin_dir_path( dirname( __FILE__ ) ) . 'build/mapped-post-block' );
		register_block_type( plugin_dir_path( dirname( __FILE__ ) ) . 'build/location-details' );
		register_block_type( plugin_dir_path( dirname( __FILE__ ) ) . 'build/location-map' );
	}




	/** Enqueue assets not auto included */
	function carkeek_blocks_mapped_posts_block_enqueue_assets() {
		if ( has_block( 'carkeek-site-blocks/mapped-posts-block' ) || has_block( 'carkeek-site-blocks/location-map' ) ) {
			$dir        = plugin_dir_path( dirname( __FILE__ ) );
			$shared_css = 'build/mapped-post-block/script.css';
			wp_enqueue_style(
				'carkeek-blocks-mapped-posts',
				plugins_url( $shared_css, dirname( __FILE__ ) ),
				array(),
				filemtime( "$dir/$shared_css" )
			);
		}
	}

}
CarkeekMappedPosts_Register::register();
