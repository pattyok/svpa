<?php
/**
 * Load assets for our blocks.
 *
 * @package   VEMgmt
 * @author    Patty O'Hara
 * @link      https://carkeekstudios.com
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Load general assets for our blocks.
 *
 * @since 1.0.0
 */
class VEMgmt_CustomPost {

	/**
	 * This plugin's instance.
	 *
	 * @var VEMgmt_CustomPost
	 */
	private static $instance;

	/**
	 * Registers the plugin.
	 */
	public static function register() {
		if ( null === self::$instance ) {
			self::$instance = new VEMgmt_CustomPost();
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
	private function __construct() {
		add_action( 'init', array( $this, 'register_custompost' ) );

	}

	/**
	 * Register post type
	 */
	public function register_custompost() {
		register_post_type(
			'vol_event',
			array(
				'labels'       => array(
					'name'          => __( 'Volunteer Events', 'textdomain' ),
					'singular_name' => __( 'Volunteer Event', 'textdomain' ),
					'menu_name'     => __( 'Volunteer Events', 'textdomain' ),
					'add_new'               => __( 'Add New', 'textdomain' ),
					'add_new_item'          => __( 'Add New Event', 'textdomain' ),
					'new_item'              => __( 'New Event', 'textdomain' ),
					'edit_item'             => __( 'Edit Event', 'textdomain' ),
					'view_item'             => __( 'View Event', 'textdomain' ),
					'all_items'             => __( 'All Events', 'textdomain' ),
				),
				'public'       => true,
				'has_archive'  => false,
				'rewrite'      => array(
					'slug'       => 'volunteer-event',
					'with_front' => false,
				),
				'menu_icon'    => 'dashicons-megaphone',
				'supports'     => array( 'title', 'editor', 'thumbnail', 'page-attributes', 'excerpt', 'custom-fields' ),
				'show_in_rest' => true,
				'show_in_nav_menus' => false,
				'map_meta_cap' => true,
			)
		);

		register_post_type(
			'vol_shift',
			array(
				'labels'       => array(
					'name'          => __( 'Volunteer Shifts', 'textdomain' ),
					'singular_name' => __( 'Volunteer Shift', 'textdomain' ),
					'menu_name'     => __( 'Volunteer Shifts', 'textdomain' ),
					'add_new'               => __( 'Add New', 'textdomain' ),
					'add_new_item'          => __( 'Add New Shift', 'textdomain' ),
					'new_item'              => __( 'New Shift', 'textdomain' ),
					'edit_item'             => __( 'Edit Shift', 'textdomain' ),
					'view_item'             => __( 'View Shift', 'textdomain' ),
					'all_items'             => __( 'All Shifts', 'textdomain' ),
				),
				'public'       => true,
				'has_archive'  => false,
				'rewrite'      => array(
					'slug'       => 'volunteer-shift',
					'with_front' => false,
				),
				'menu_icon'    => 'dashicons-megaphone',
				'supports'     => array( 'title', 'editor', 'thumbnail', 'page-attributes', 'excerpt', 'custom-fields' ),
				'show_in_rest' => true,
				'show_in_nav_menus' => false,
				'show_in_menu' => 'edit.php?post_type=vol_event',
				'map_meta_cap' => true,
			)
		);




	}

	/**
	 * Add Blocks to Custom Post Template
	 */
	public function register_custompost_template() {
		$post_type_object           = get_post_type_object( 'skgt_location' );
		$post_type_object->template = array(
			array( 'core/group', array( 'align' => 'full', 'backgroundColor' => 'secondary', 'layout' => array( 'type' => 'constrained' ) ), array(
				array( 'core/columns', array( 'align' => 'wide' ), array(
					array( 'core/column', array( 'width' => '60%' ), array(
						array( 'carkeek-site-blocks/location-map' ),
					) ),
					array( 'core/column', array( 'width' => '40%' ), array(
						array( 'carkeek-site-blocks/location-details', array() ),
					) ),
				) ),
			) ),
		);


	}


	/**
	 * Pass Fields from parent project to the rest API.
	 *
	 * Items URL = http://trc-sound-water-stewards.local/wp-json/wp/v2/sws_locations?per_page=100&_fields=id,title,acf,project,cat
	 *
	 * @param object $response original rest response.
	 * @param object $post current post object.
	 * @param object $request current request.
	 */
	public function rest_api_locations( $response, $post, $request ) {
		//add the featured image
		if ( !empty( $response->data['featured_media'] ) ) {
			$img = wp_get_attachment_image_src( $response->data['featured_media'], 'medium' );
			$response->data['featured_image'] = $img[0];
		}
		return $response;
	}


}

VEMgmt_CustomPost::register();


