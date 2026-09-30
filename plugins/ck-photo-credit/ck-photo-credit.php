<?php
/**
 * Plugin Name: Carkeek - Photo Credit
 * Description: Adds attachment photo credits and optional rendering for select core blocks.
 * Version: 0.1.0
 * Author: Carkeek Studios
 * Author URI: https://carkeekstudios.com/
 * Text Domain: wp-rig
 *
 * @package WP_Rig
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin bootstrap class.
 */
class CK_Photo_Credit_Plugin {
	/**
	 * Attachment meta key for photo credits.
	 */
	const META_KEY = 'ck_photo_credit';

	/**
	 * Block attribute keys.
	 */
	const ATTR_SHOW     = 'ckShowPhotoCredit';
	const ATTR_POSITION = 'ckPhotoCreditPosition';

	/**
	 * Plugin bootstrap.
	 */
	public static function init() {
		$instance = new self();

		add_action( 'init', array( $instance, 'register_attachment_meta' ) );
		add_action( 'enqueue_block_editor_assets', array( $instance, 'enqueue_editor_assets' ) );
		add_action( 'enqueue_block_assets', array( $instance, 'enqueue_shared_styles' ) );
		add_filter( 'attachment_fields_to_edit', array( $instance, 'add_attachment_field' ), 10, 2 );
		add_filter( 'attachment_fields_to_save', array( $instance, 'save_attachment_field' ), 10, 2 );
		add_filter( 'render_block', array( $instance, 'inject_photo_credit' ), 10, 2 );
	}

	/**
	 * Register attachment meta for REST and admin use.
	 */
	public function register_attachment_meta() {
		register_post_meta(
			'attachment',
			self::META_KEY,
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'wp_kses_post',
				'auth_callback'     => function () {
					return current_user_can( 'upload_files' );
				},
			)
		);
	}

	/**
	 * Enqueue editor-side block extension script.
	 */
	public function enqueue_editor_assets() {
		$handle = 'ck-photo-credit-editor';
		$src    = plugin_dir_url( __FILE__ ) . 'assets/js/editor.js';
		$path   = plugin_dir_path( __FILE__ ) . 'assets/js/editor.js';

		wp_enqueue_script(
			$handle,
			$src,
			array( 'wp-hooks', 'wp-compose', 'wp-element', 'wp-i18n', 'wp-components', 'wp-block-editor', 'wp-data' ),
			file_exists( $path ) ? filemtime( $path ) : false,
			true
		);
	}

	/**
	 * Enqueue shared styles for front and editor.
	 */
	public function enqueue_shared_styles() {
		$handle = 'ck-photo-credit-style';
		$src    = plugin_dir_url( __FILE__ ) . 'assets/css/photo-credit.css';
		$path   = plugin_dir_path( __FILE__ ) . 'assets/css/photo-credit.css';

		wp_enqueue_style(
			$handle,
			$src,
			array(),
			file_exists( $path ) ? filemtime( $path ) : false
		);
	}

	/**
	 * Add photo credit field to media attachment form.
	 *
	 * @param array   $form_fields Existing form fields.
	 * @param WP_Post $post        Attachment post object.
	 * @return array
	 */
	public function add_attachment_field( $form_fields, $post ) {
		$value = get_post_meta( $post->ID, self::META_KEY, true );

		$form_fields[ self::META_KEY ] = array(
			'label' => __( 'Photo Credit', 'wp-rig' ),
			'input' => 'html',
			'html'  => sprintf(
				'<textarea class="widefat" rows="3" name="attachments[%1$d][%2$s]">%3$s</textarea><p class="help">%4$s</p>',
				absint( $post->ID ),
				esc_attr( self::META_KEY ),
				esc_textarea( $value ),
				esc_html__( 'Accepts safe HTML (links, emphasis, line breaks).', 'wp-rig' )
			),
		);

		return $form_fields;
	}

	/**
	 * Save photo credit field from media attachment forms.
	 *
	 * @param array $post       Attachment post data.
	 * @param array $attachment Submitted attachment payload.
	 * @return array
	 */
	public function save_attachment_field( $post, $attachment ) {
		if ( ! current_user_can( 'upload_files' ) ) {
			return $post;
		}

		if ( array_key_exists( self::META_KEY, $attachment ) ) {
			$value = wp_kses_post( wp_unslash( (string) $attachment[ self::META_KEY ] ) );
			update_post_meta( $post['ID'], self::META_KEY, $value );
		}

		return $post;
	}

	/**
	 * Inject photo credit into selected blocks when enabled.
	 *
	 * @param string $block_content Rendered block markup.
	 * @param array  $block         Parsed block.
	 * @return string
	 */
	public function inject_photo_credit( $block_content, $block ) {
		if ( empty( $block['blockName'] ) || empty( $block_content ) ) {
			return $block_content;
		}

		if ( ! in_array( $block['blockName'], array( 'core/media-text', 'core/cover', 'core/image' ), true ) ) {
			return $block_content;
		}

		$attrs = isset( $block['attrs'] ) && is_array( $block['attrs'] ) ? $block['attrs'] : array();

		if ( empty( $attrs[ self::ATTR_SHOW ] ) ) {
			return $block_content;
		}

		if ( false !== strpos( $block_content, 'class="ck-photo-credit' ) ) {
			return $block_content;
		}

		$attachment_id = $this->resolve_attachment_id( $block['blockName'], $attrs );
		if ( ! $attachment_id ) {
			return $block_content;
		}

		$credit = get_post_meta( $attachment_id, self::META_KEY, true );
		if ( ! is_string( $credit ) || '' === trim( $credit ) ) {
			return $block_content;
		}

		$position = isset( $attrs[ self::ATTR_POSITION ] ) && is_string( $attrs[ self::ATTR_POSITION ] ) ? $attrs[ self::ATTR_POSITION ] : 'below';
		$position = in_array( $position, array( 'below', 'overlay' ), true ) ? $position : 'below';

		$credit_markup = sprintf(
			'<div class="ck-photo-credit ck-photo-credit--%1$s">%2$s</div>',
			esc_attr( $position ),
			wp_kses_post( $credit )
		);

		return $this->inject_inside_figure_or_root( $block_content, $credit_markup );
	}

	/**
	 * Resolve the media attachment ID from block attributes.
	 *
	 * @param string $block_name Block name.
	 * @param array  $attrs      Block attributes.
	 * @return int
	 */
	private function resolve_attachment_id( $block_name, $attrs ) {
		if ( 'core/media-text' === $block_name && ! empty( $attrs['mediaId'] ) ) {
			return absint( $attrs['mediaId'] );
		} else if ( ! empty( $attrs['id'] ) ) {
			return absint( $attrs['id'] );
		}

		return 0;
	}

	/**
	 * Inject markup before the last closing div to keep credit inside the root wrapper.
	 *
	 * @param string $markup       Block markup.
	 * @param string $credit_markup Credit element markup.
	 * @return string
	 */
	private function inject_inside_root( $markup, $credit_markup ) {
		$last_div_close = strrpos( $markup, '</div>' );
		if ( false === $last_div_close ) {
			return $markup . $credit_markup;
		}

		return substr( $markup, 0, $last_div_close ) . $credit_markup . substr( $markup, $last_div_close );
	}

	/**
	 * Inject markup inside the first figure tag when present.
	 *
	 * Falls back to root-wrapper injection when figure markup is unavailable.
	 *
	 * @param string $markup        Block markup.
	 * @param string $credit_markup Credit element markup.
	 * @return string
	 */
	private function inject_inside_figure_or_root( $markup, $credit_markup ) {
		$figure_close = stripos( $markup, '</figure>' );

		if ( false !== $figure_close ) {
			return substr( $markup, 0, $figure_close ) . $credit_markup . substr( $markup, $figure_close );
		}

		return $this->inject_inside_root( $markup, $credit_markup );
	}
}

CK_Photo_Credit_Plugin::init();
