<?php
/**
 * Load assets for our blocks.
 *
 * @package   CarkeekMappedPosts
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
class CarkeekMappedPosts_CustomPost {

	/**
	 * This plugin's instance.
	 *
	 * @var CarkeekMappedPosts_CustomPost
	 */
	private static $instance;

	/**
	 * Registers the plugin.
	 */
	public static function register() {
		if ( null === self::$instance ) {
			self::$instance = new CarkeekMappedPosts_CustomPost();
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
		add_action( 'init', array( $this, 'register_custompost_template' ), 9999 );
		add_filter( 'rest_prepare_skgt_location', array( $this, 'rest_api_locations' ), 10, 3 );

	}

	/**
	 * Register post type
	 */
	public function register_custompost() {
		register_post_type(
			'skgt_location',
			array(
				'labels'       => array(
					'name'          => __( 'Locations', 'textdomain' ),
					'singular_name' => __( 'Location', 'textdomain' ),
					'menu_name'     => __( 'Locations', 'textdomain' ),
				),
				'public'       => true,
				'has_archive'  => false,
				'rewrite'      => array(
					'slug'       => 'locations',
					'with_front' => false,
				),
				'menu_icon'    => 'dashicons-location-alt',
				'supports'     => array( 'title', 'editor', 'thumbnail', 'page-attributes', 'excerpt', 'custom-fields' ),
				'show_in_rest' => true,
				'show_in_nav_menus' => false,
				'map_meta_cap' => true,
			)
		);

		// Add new taxonomy, make it hierarchical (like categories)
		$labels = array(
			'name'              => _x( 'Activities', 'taxonomy general name', 'textdomain' ),
			'singular_name'     => _x( 'Activity', 'taxonomy singular name', 'textdomain' ),
			'search_items'      => __( 'Search Activities', 'textdomain' ),
			'all_items'         => __( 'All Activities', 'textdomain' ),
			'parent_item'       => __( 'Parent Activity', 'textdomain' ),
			'parent_item_colon' => __( 'Parent Activity:', 'textdomain' ),
			'edit_item'         => __( 'Edit Activity', 'textdomain' ),
			'update_item'       => __( 'Update Activity', 'textdomain' ),
			'add_new_item'      => __( 'Add New Activity', 'textdomain' ),
			'new_item_name'     => __( 'New Activity Name', 'textdomain' ),
			'menu_name'         => __( 'Activities', 'textdomain' ),
		);

		$args = array(
			'hierarchical'      => true,
			'labels'            => $labels,
			'show_ui'           => true,
			'show_admin_column' => true,
			'query_var'         => true,
			'rewrite'           => array( 'slug' => 'activity' ),
			'show_in_rest'      => true,
		);

		register_taxonomy( 'skgt_location_cat', array( 'skgt_location' ), $args );

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

CarkeekMappedPosts_CustomPost::register();


