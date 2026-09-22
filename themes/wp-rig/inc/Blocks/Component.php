<?php
/**
 * WP_Rig\WP_Rig\Blocks\Component class
 *
 * @package wp_rig
 */

namespace WP_Rig\WP_Rig\Blocks;

use WP_Rig\WP_Rig\Component_Interface;
use function WP_Rig\WP_Rig\wp_rig;
use function add_action;


/**
 * Class for adding custom block styles
 *
 * @link https://developer.wordpress.org/block-editor/developers/filters/block-filters/
 */
class Component implements Component_Interface {

	/**
	 * Gets the unique identifier for the theme component.
	 *
	 * @return string Component slug.
	 */
	public function get_slug(): string {
		return 'blocks';
	}

	/**
	 * Style overrides for blocks
	 */
	public function initialize() {
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_editor_scripts' ) );
		add_filter( 'render_block', array( $this, 'display_photo_credit_on_image_blocks' ), 10, 2 );
	}


	/**
	 * Register custom custom scripts for blocks
	 */
	public function enqueue_editor_scripts() {
		$js_files = array(
			'wp-rig-editor-js' => array(
				'file'         => 'editor.min.js',
				'dependencies' => array( 'wp-blocks', 'wp-dom-ready' ),
				'in_footer'    => true,
			),
		);

		$js_uri = get_theme_file_uri( '/assets/js/' );
		$js_dir = get_theme_file_path( '/assets/js/' );

		foreach ( $js_files as $handle => $data ) {
			// $src     = $css_uri . $data['file'];
			$version = wp_rig()->get_asset_version( $js_dir . $data['file'] );
			$asset   = $js_uri . wp_rig()->get_asset_path( $data['file'] );

			/*
			 * Enqueue global scripts
			 */
			wp_enqueue_script( $handle, $asset, $data['dependencies'], $version, $data['in_footer'] );
		}
	}

	public function display_photo_credit_on_image_blocks( $block_content, $block ) {
		// Check if it's the core image block and has an ID
		$photo_credit = '';
		$is_cover     = false;
		if ( ( 'core/image' === $block['blockName']  ) && ! empty( $block['attrs']['id'] ) ) {
			$image_id = $block['attrs']['id'];
			// Retrieve your custom photo credit (e.g., from an attachment meta field)
			$photo_credit = get_post_meta( $image_id, 'photo_credit', true );
		} elseif ( 'core/media-text' === $block['blockName'] && ! empty( $block['attrs']['mediaId'] ) ) {
			$image_id = $block['attrs']['mediaId'];
			$photo_credit = get_post_meta( $image_id, 'photo_credit', true );
		} elseif ( 'core/cover' === $block['blockName'] && ! empty( $block['attrs']['id'] ) ) {
			$image_id = $block['attrs']['id'];
			$photo_credit = get_post_meta( $image_id, 'photo_credit', true );
			$is_cover     = true;
		}

		if ( ! empty( $photo_credit ) ) {
			$credit_html = '<span class="photo-credit">' . esc_html( $photo_credit ) . '</span>';

			if ( $is_cover ) {
				$last_div_position = strrpos( $block_content, '</div>' );
				if ( false !== $last_div_position ) {
					$block_content = substr_replace( $block_content, $credit_html . '</div>', $last_div_position, strlen( '</div>' ) );
				}
			} else {
				$last_figure_position = strrpos( $block_content, '</figure>' );
				if ( false !== $last_figure_position ) {
					$block_content = substr_replace( $block_content, $credit_html . '</figure>', $last_figure_position, strlen( '</figure>' ) );
				}
			}
		}

		return $block_content;
	}
}
