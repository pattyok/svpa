<?php
/**
 * WP-CLI commands for scaffolding theme item documentation.
 *
 * @package wp_rig
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

/**
 * Class Theme_Docs_Command
 *
 * Scaffolds Markdown documentation stubs for custom theme items (components,
 * block patterns, or any other item type) under docs/<category>/<slug>.md.
 * This is the single source of truth for doc scaffolding - both manual use
 * and AI-assisted development (see CLAUDE.md) should go through this command
 * so the frontmatter schema never drifts between the two.
 */
class Theme_Docs_Command extends WP_CLI_Command {

	/**
	 * Creates a new documentation stub for a theme item.
	 *
	 * ## OPTIONS
	 *
	 * <category>
	 * : Grouping used in the "Theme Docs" admin page, e.g. "components" or "block-patterns".
	 *
	 * <slug>
	 * : Kebab-case identifier for the item, e.g. "post-meta". Becomes the doc filename.
	 *
	 * --related-file=<path>
	 * : Theme-relative path to the file this doc describes, e.g. "components/post-meta.php".
	 *
	 * [--force]
	 * : Overwrite the doc file if it already exists.
	 *
	 * ## EXAMPLES
	 *
	 *     wp theme-docs scaffold components post-meta --related-file=components/post-meta.php
	 *
	 * @param array $args Positional arguments: category, slug.
	 * @param array $assoc_args Associative arguments: related-file, force.
	 */
	public function scaffold( array $args, array $assoc_args ) {
		list( $category, $slug ) = $args;

		if ( ! preg_match( '/^[a-z0-9]+(-[a-z0-9]+)*$/', $category ) ) {
			WP_CLI::error( 'category must be kebab-case (lowercase letters, numbers, hyphens).' );
		}

		if ( ! preg_match( '/^[a-z0-9]+(-[a-z0-9]+)*$/', $slug ) ) {
			WP_CLI::error( 'slug must be kebab-case (lowercase letters, numbers, hyphens).' );
		}

		$related_file = WP_CLI\Utils\get_flag_value( $assoc_args, 'related-file', '' );
		if ( empty( $related_file ) ) {
			WP_CLI::error( 'Missing required --related-file=<path> argument.' );
		}

		$force    = WP_CLI\Utils\get_flag_value( $assoc_args, 'force', false );
		$doc_dir  = get_theme_file_path( "docs/{$category}" );
		$doc_path = "{$doc_dir}/{$slug}.md";

		if ( file_exists( $doc_path ) && ! $force ) {
			WP_CLI::error( "Doc already exists at docs/{$category}/{$slug}.md. Use --force to overwrite." );
		}

		if ( ! is_dir( $doc_dir ) && ! wp_mkdir_p( $doc_dir ) ) {
			WP_CLI::error( "Could not create docs/{$category} directory." );
		}

		$title = ucwords( str_replace( '-', ' ', $slug ) );

		$stub = <<<MD
---
title: {$title}
related_file: {$related_file}
summary: TODO - one sentence describing what this item does.
---

TODO - describe {$title} here: what it does, when to use it, and any gotchas.
MD;

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		if ( false === file_put_contents( $doc_path, $stub ) ) {
			WP_CLI::error( "Failed to write docs/{$category}/{$slug}.md." );
		}

		WP_CLI::success( "Created docs/{$category}/{$slug}.md - fill it in, then run npm run dev or npm run bundle to compile it." );
	}
}

WP_CLI::add_command( 'theme-docs', 'Theme_Docs_Command' );
