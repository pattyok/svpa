<?php
/**
 * WP_Rig\WP_Rig\Theme_Docs\Component class
 *
 * @package wp_rig
 */

namespace WP_Rig\WP_Rig\Theme_Docs;

use WP_Rig\WP_Rig\Component_Interface;
use function add_action;
use function add_menu_page;
use function add_query_arg;
use function esc_html;
use function esc_html__;
use function esc_url;
use function get_theme_file_path;
use function get_theme_file_uri;
use function menu_page_url;
use function sanitize_key;
use function wp_unslash;

/**
 * Class for the "Theme Docs" admin page.
 *
 * Reads assets/docs/manifest.json (compiled by `gulp/docs.js` from
 * docs/**\/*.md - see wp-rig/docs/README.md) and renders the pre-compiled
 * HTML for each doc, grouped by category. The doc with the slug "main" is
 * shown when no doc is selected.
 */
class Component implements Component_Interface {

	const PAGE_SLUG = 'theme-docs';

	const DEFAULT_DOC_SLUG = 'main';

	// Must match DOCS_URL_PLACEHOLDER in gulp/docs.js.
	const DOCS_URL_PLACEHOLDER = '__THEME_DOCS_URL__';

	/**
	 * Gets the unique identifier for the theme component.
	 *
	 * @return string Component slug.
	 */
	public function get_slug(): string {
		return 'theme_docs';
	}

	/**
	 * Adds the action and filter hooks to integrate with WordPress.
	 */
	public function initialize() {
		add_action( 'admin_menu', array( $this, 'register_admin_page' ) );
	}

	/**
	 * Registers the top-level "Theme Docs" admin page.
	 */
	public function register_admin_page() {
		add_menu_page(
			esc_html__( 'Theme Docs', 'wp-rig' ),
			esc_html__( 'Theme Docs', 'wp-rig' ),
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'render_admin_page' ),
			'dashicons-media-text'
		);
	}

	/**
	 * Reads and decodes assets/docs/manifest.json.
	 *
	 * @return array List of doc entries, or an empty array if no manifest exists yet.
	 */
	protected function get_manifest(): array {
		$manifest_path = get_theme_file_path( 'assets/docs/manifest.json' );

		if ( ! file_exists( $manifest_path ) ) {
			return array();
		}

		$manifest = json_decode( file_get_contents( $manifest_path ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents

		return is_array( $manifest ) ? $manifest : array();
	}

	/**
	 * Renders the admin page.
	 */
	public function render_admin_page() {
		$manifest = $this->get_manifest();

		echo '<div class="wrap theme-docs-wrap">';
		echo '<h1>' . esc_html__( 'Theme Docs', 'wp-rig' ) . '</h1>';

		if ( empty( $manifest ) ) {
			echo '<p>' . esc_html__( 'No docs have been compiled yet. Run npm run dev (or npm run build) in wp-rig after adding a docs/**/*.md file.', 'wp-rig' ) . '</p>';
			echo '</div>';
			return;
		}

		$grouped = array();
		foreach ( $manifest as $entry ) {
			// Manifest is already in sidebar order (see gulp/docs.js).
			$grouped[ $entry['category'] ][] = $entry;
		}

		$selected_slug = isset( $_GET['doc'] ) ? sanitize_key( wp_unslash( $_GET['doc'] ) ) : self::DEFAULT_DOC_SLUG; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$selected      = null;
		foreach ( $manifest as $entry ) {
			if ( $entry['slug'] === $selected_slug ) {
				$selected = $entry;
				break;
			}
		}
		if ( ! $selected ) {
			$selected = $manifest[0];
		}

		echo '<style>
			.theme-docs-layout { display: flex; gap: 24px; margin-top: 16px; }
			.theme-docs-nav { flex: 0 0 240px; }
			.theme-docs-nav h3 { text-transform: uppercase; font-size: 11px; color: #646970; margin: 16px 0 4px; }
			.theme-docs-nav ul { margin: 0 0 8px; }
			.theme-docs-nav li a.is-active { font-weight: 600; }
			.theme-docs-content { flex: 1; background: #fff; border: 1px solid #c3c4c7; padding: 24px; min-width: 0; }
			.theme-docs-content [id] { scroll-margin-top: 48px; }
			.theme-docs-badge { display: inline-block; font-size: 11px; padding: 2px 8px; border-radius: 3px; margin-left: 8px; }
			.theme-docs-badge.is-orphaned { background: #f8d7da; color: #842029; }
			.theme-docs-badge.is-stale { background: #fff3cd; color: #664d03; }
			.theme-docs-related-file { color: #646970; font-size: 13px; }
			.theme-docs-content img { max-width: 100%; height: auto; }
			.theme-docs-content table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
			.theme-docs-content table th,
			.theme-docs-content table td { border: 1px solid #c3c4c7; padding: 8px; text-align: left; }
			.theme-docs-content table img {max-width: 300px; }
		</style>';

		echo '<div class="theme-docs-layout">';

		echo '<nav class="theme-docs-nav">';
		foreach ( $grouped as $category => $entries ) {
			echo '<h3>' . esc_html( str_replace( array( '-', '_' ), ' ', $category ) ) . '</h3><ul>';
			foreach ( $entries as $entry ) {
				$is_active = ( $entry['slug'] === $selected['slug'] );
				$url       = add_query_arg( 'doc', $entry['slug'], menu_page_url( self::PAGE_SLUG, false ) );
				echo '<li><a' . ( $is_active ? ' class="is-active"' : '' ) . ' href="' . esc_url( $url ) . '">' . esc_html( $entry['title'] ) . '</a></li>';
			}
			echo '</ul>';
		}
		echo '</nav>';

		echo '<div class="theme-docs-content">';
		$this->render_doc( $selected );
		echo '</div>';

		echo '</div>'; // .theme-docs-layout
		echo '</div>'; // .wrap
	}

	/**
	 * Renders a single doc's compiled HTML, with orphan/staleness badges.
	 *
	 * @param array $entry Manifest entry.
	 */
	protected function render_doc( array $entry ) {
		$related_file = isset( $entry['related_file'] ) ? $entry['related_file'] : '';

		echo '<h2>' . esc_html( $entry['title'] );
		if ( $related_file ) {
			$related_path = get_theme_file_path( $related_file );
			if ( ! file_exists( $related_path ) ) {
				echo ' <span class="theme-docs-badge is-orphaned">' . esc_html__( 'orphaned: source file not found', 'wp-rig' ) . '</span>';
			} elseif ( filemtime( $related_path ) > strtotime( $entry['updated_at'] ) ) {
				echo ' <span class="theme-docs-badge is-stale">' . esc_html__( 'possibly stale', 'wp-rig' ) . '</span>';
			}
		}
		echo '</h2>';

		if ( $related_file ) {
			echo '<p class="theme-docs-related-file">' . esc_html__( 'Related file:', 'wp-rig' ) . ' <code>' . esc_html( $related_file ) . '</code></p>';
		}

		$html_path = get_theme_file_path( "assets/docs/{$entry['category']}/{$entry['slug']}.html" );
		if ( file_exists( $html_path ) ) {
			// Pre-rendered at build time from developer-authored Markdown, not user input.
			$html = file_get_contents( $html_path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			echo str_replace( self::DOCS_URL_PLACEHOLDER, esc_url( get_theme_file_uri( 'assets/docs' ) ), $html ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}
}
