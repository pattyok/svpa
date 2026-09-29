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
class CarkeekMappedPosts_Post_Meta {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'pass_metafields_to_restapi' ) );
		//add_action( 'init', array( $this, 'register_meta_fields' ), 20 ); // Using ACF for now...
	}


	/**
	 * Create our own meta fields so we can edit them in the block editor.
	 */
	public function register_meta_fields( ) {
		$post_type = 'skgt_location';
		$fields = array(
			array(
				'key' => 'loc_type',
				'type' => 'string',
			),
			array(
				'key' => 'loc_location',
				'type' => 'string',
			),
			array(
				'key' => 'loc_lat',
				'type' => 'string',
			),
			array(
				'key' => 'loc_long',
				'type' => 'string',
			),
			array(
				'key' => 'loc_acreage',
				'type' => 'string',
			),
			array(
				'key' => 'loc_date_added',
				'type' => 'string',
			),
			array(
				'key' => 'loc_date_added_notes',
				'type' => 'string',
			),
			array(
				'key' => 'loc_open_to_the_public',
				'type' => 'boolean',
			)
		);
		foreach ( $fields as $field ) {
			register_post_meta(
				$post_type,
				$field['key'],
				array(
					'show_in_rest' => true,
					'single'       => true,
					'type'         => $field['type'],
				)
			);
		}
	}

	/**
	 * Pass metafields to rest api to be usable in the block editor.
	 */
	public function pass_metafields_to_restapi() {
		register_rest_field(
			'type',
			'metafields',
			array(
				'get_callback' => function( $postype_arr ) {
					$post_type = $postype_arr['slug'];
					$meta_fields = $this->getFieldsForPostType( $post_type );
					return $meta_fields;
				},
				'schema'       => array(
					'description' => __( 'Meta Fields for Post Type' ),
					'type'        => 'array',
				),
			)
		);
	}

		/**
		 * Get all custom fields associated with a post type
		 *
		 * @param string post_type
		 * @return array
		 */
	public function getFieldsForPostType( $post_type, $show_hidden = false ) {
		global $wpdb;
		$post_table = $wpdb->prefix . 'posts';
		$meta_table = $wpdb->prefix . 'postmeta';
		if ( $show_hidden ) {
			$sql = "SELECT DISTINCT meta_key FROM $post_table AS p LEFT JOIN $meta_table AS m ON m.post_id = p.id WHERE p.post_type = '$post_type' AND meta_key NOT LIKE ''";
		} else {
			$sql = "SELECT DISTINCT meta_key FROM $post_table AS p LEFT JOIN $meta_table AS m ON m.post_id = p.id WHERE p.post_type = '$post_type' AND meta_key NOT LIKE '\_%'";
		}
		$results = $wpdb->get_results( $sql );
		return $results;
	}

}

return new CarkeekMappedPosts_Post_Meta();
