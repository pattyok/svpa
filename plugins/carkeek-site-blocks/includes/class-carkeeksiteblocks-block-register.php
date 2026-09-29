<?php
/**
 * Load assets for our blocks.
 *
 * @package   CarkeekSiteBlocks
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
class CarkeekSiteBlocks_Block_Register {

	/**
	 * This plugin's instance.
	 *
	 * @var CarkeekSiteBlocks_Block_Register
	 */
	private static $instance;

	/**
	 * Registers the plugin.
	 */
	public static function register() {
		if ( null === self::$instance ) {
			self::$instance = new CarkeekSiteBlocks_Block_Register();
		}
	}

	/**
	 * The Plugin slug.
	 *
	 * @var string $slug
	 */
	private $slug;

	/**
	 * The Constructor.
	 */
	private function __construct() {
		$this->slug = 'carkeek-site-blocks';

		add_action( 'init', array( $this, 'carkeek_blocks_register_blocks' ), 9999 );


	}

	/**
	 * Register Blocks.
	 */
	public function carkeek_blocks_register_blocks() {

		$dir = plugin_dir_path( dirname( __FILE__ ) );
		register_block_type( "$dir/build/link-tiles" );
		register_block_type( "$dir/build/link-tile" );

		register_block_type( "$dir/build/expand-collapse-section" );

	}

	/** Get Selected or Random Color
	 * If a color has been selected for the archive, use that.
	 * Otherwise, pick a random color from the list.
	 * Probably could be done better within the block editor. But this works.
	 *
	 * @param int $post_id The post ID.
	 * @param int $nbr     The number of colors to choose from.
	 * @return string The color slug.
	 * @since 0.1.0
	 */
	public function get_selected_or_random_color( $post_id, $nbr = null ) {
		$selected_color = get_post_meta( $post_id, '_carkeekblocks_archive_background_color', true );
		$colors         = array(
			array(
				'name'  => __( 'Blue', 'wp-rig' ),
				'slug'  => 'theme-blue',
				'color' => '#a2c6d2',
			),
			array(
				'name'  => __( 'Green', 'wp-rig' ),
				'slug'  => 'theme-green',
				'color' => '#637d36',
			),
			array(
				'name'  => __( 'Yellow', 'wp-rig' ),
				'slug'  => 'theme-yellow',
				'color' => '#CAB44B',
			),
			array(
				'name'  => __( 'Orange', 'wp-rig' ),
				'slug'  => 'theme-orange',
				'color' => '#bc8a24',
			),
			array(
				'name'  => __( 'Red', 'wp-rig' ),
				'slug'  => 'theme-red',
				'color' => '#a55525',
			),
			array(
				'name'  => __( 'Green Light', 'wp-rig' ),
				'slug'  => 'theme-green-light',
				'color' => '#a7ad37',
			),
		);
		if ( empty( $selected_color ) ) {
			if ( isset( $nbr ) && $nbr < count( $colors ) ) {
				return $colors[ $nbr ]['slug'];
			} else {
				return $colors[ wp_rand( 0, count( $colors ) - 1 ) ]['slug'];
			}
		} else {
			return $selected_color;
		}
	}


	/**
	 * Render Custom Post Type Archive - this is a special version for this site.
	 *
	 * @param array $attributes Attributes passed to callback.
	 * @return string HTML of dynamic content.
	 */
	public function carkeek_site_blocks_render_custom_archive_tiles( $attributes ) {
		if ( empty( $attributes['postTypeSelected'] ) ) {
			return;
		}
		$layout = $attributes['postLayout'];
		$args   = array(
			'posts_per_page' => $attributes['numberOfPosts'],
			'post_type'      => $attributes['postTypeSelected'],
			'order'          => 'DESC',
		);
		if ( 'tribe_events' === $attributes['postTypeSelected'] ) {
			$args['meta_query'] = array(
				array(
					'key'     => '_EventEndDate',
					'value'   => gmdate( 'Y-m-d' ),
					'compare' => '>=',
					'type'    => 'NUMERIC,',
				),
			);
			$args['orderby']    = 'meta_value';
			$args['meta_key']   = '_EventEndDate';
			$args['order']      = 'ASC';
		}
		if ( true === $attributes['filterByTaxonomy'] && ! empty( $attributes['taxonomySelected'] ) && ! empty( $attributes['taxTermsSelected'] ) ) {
			$args['tax_query'] = array(
				array(
					'taxonomy' => $attributes['taxonomySelected'],
					'field'    => 'term_id',
					'terms'    => explode( ',', $attributes['taxTermsSelected'] ),
				),
			);
		}

		$args  = apply_filters( 'carkeek_block_custom_post_layout__query_args', $args, $attributes );
		$query = new WP_Query( $args );
		$posts = '';

		$class_pre         = 'wp-block-carkeek-blocks-custom-archive';
		$css_classes_outer = array(
			$class_pre,
			'is-' . $attributes['postLayout'],
			'post-type-' . $attributes['postTypeSelected'],
			'align' . $attributes['align'],
		);

		$css_classes_outer = apply_filters( 'carkeek_block_custom_post_layout__css_classes_outer', $css_classes_outer, $attributes );
		$block_start       = '<div class="' . implode( ' ', $css_classes_outer ) . '">';
		if ( ! empty( $attributes['headline'] ) ) {
			$tag_name     = 'h' . $attributes['headlineLevel'];
			$block_start .= '<' . $tag_name . ' class="' . $class_pre . '__headline">' . $attributes['headline'] . '</' . $tag_name . '>';
		}
		if ( $query->have_posts() ) {
			$posts           .= $block_start;
			$css_classes_list = array(
				$class_pre . '__list',
			);
			if ( 'link-tile' === $layout ) {
				$css_classes_list[] = 'wp-block-carkeek-blocks-link-tiles';
				$css_classes_list[] = 'wp-block-columns';
			}

			$css_classes_list = apply_filters( 'carkeek_block_custom_post_layout__css_classes_list', $css_classes_list, $attributes );
			$posts           .= '<div class="' . implode( ' ', $css_classes_list ) . '">';
			$count            = 0;
			while ( $query->have_posts() ) {
				$query->the_post();
				global $post;

				$css_classes_item = array(
					$class_pre . '__item',
				);
				if ( 'link-tile' === $layout ) {
					$css_classes_item[] = 'wp-block-carkeek-blocks-link-tile';
					$css_classes_item[] = 'wp-block-column';
					$css_classes_item[] = 'has-' . $this->get_selected_or_random_color( $post->ID, $count ) . '-background-color';
				}

				$css_classes_list = apply_filters( 'carkeek_block_custom_post_layout__css_classes_item', $css_classes_item, $attributes );

				$post_title     = get_the_title();
				$featured_image = get_the_post_thumbnail_url( null, 'medium_large' );
				$excerpt        = '';

				if ( true == $attributes['displayPostExcerpt'] ) {
					$excerpt = get_the_excerpt();
					$limit   = $attributes['excerptLength'];
					if ( str_word_count( $excerpt, 0 ) > $limit ) {
						$words   = str_word_count( $excerpt, 2 );
						$pos     = array_keys( $words );
						$excerpt = substr( $excerpt, 0, $pos[ $limit ] );
					}
				}
				$post_html = '<div class="' . implode( ' ', $css_classes_item ) . '">';
				if ( 'link-tile' == $attributes['postLayout'] ) {
					$post_html .= '<a class="wp-block-carkeek-blocks-link-tile__link wp-block-carkeek-blocks-link-tile__inner" href="' . get_permalink() . '">
								<div style="background-image:url(' . $featured_image . ')"
									class="wp-block-carkeek-blocks-link-tile__img wp-block-carkeek-blocks-link-tile__inner"
									>
									<span class="link-tile__title">' . $post_title . '</span>
								</div>
								<span class="link-tile__hover_title">' . $excerpt . '</span>
								</a>';
				} else {
					$post_html .= '<a class="' . $class_pre . '__image_link" href="' . get_permalink() . '">
								<img src="' . $featured_image . '"/>
								</a>
								<div class="' . $class_pre . '__content-wrap">
									<a class="' . $class_pre . '__title_link" href="' . get_permalink() . '">' . $post_title . '</a>';
					if ( ! empty( $excerpt ) ) {
						$post_html .= '<div class="' . $class_pre . '__excerpt">' . $excerpt . '</div>';
					}
					$post_html .= '</div>';
				}
				$post_html .= '</div>';
				$posts     .= apply_filters( 'carkeek_block_custom_post_layout', $post_html, $post, $attributes );
				$count++;
			}
			$posts .= '</div></div>';
			wp_reset_postdata();
			return $posts;
		} else {
			if ( false === $attributes['hideIfEmpty'] ) {
				$block_content = '<div class="' . $class_pre . '__list empty">' . $attributes['emptyMessage'] . '</div>';
				return $block_start . $block_content . '</div>';
			} else {
				return;
			}
		}
	}



}

CarkeekSiteBlocks_Block_Register::register();
