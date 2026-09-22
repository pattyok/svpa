---
title: Theme Item Documentation System
type: feat
status: active
date: 2026-09-16
---

# Theme Item Documentation System

## Overview

Add a Markdown-based documentation system for custom items in the SVPA WordPress theme (template-part "components," block patterns/variations, and any other custom theme item, without a fixed taxonomy). Docs are authored as `.md` files in the `wp-rig` development theme, compiled to HTML at **build time** via the existing gulp pipeline (no PHP Markdown parser needed at runtime), and surfaced to admins through a new "Theme Docs" page in wp-admin. A WP-CLI command scaffolds new doc stubs, and it doubles as the single mechanism an AI coding assistant (Claude Code) uses when creating new components — avoiding two independently-maintained scaffolding implementations.

## Problem Statement / Motivation

Custom theme items (template parts, block patterns, block-editor customizations) currently have no documentation surfaced anywhere an editor or future developer would see it. As more components get built, there's no record of what a given component does, what file backs it, or how to use it — knowledge that currently lives only in the developer's head or in code comments no one but a developer will read.

## Proposed Solution

Markdown docs live in `wp-rig/docs/<category>/<slug>.md`, where `<category>` is a free-form folder (e.g. `components`, `block-patterns`) that doubles as the grouping used in the admin UI — there's no fixed taxonomy to maintain. Each `.md` file has minimal YAML frontmatter:

```yaml
---
title: Post Meta
related_file: components/post-meta.php
summary: Renders the post date/author line under a title.
---
```

`related_file` is the theme-relative path to the file the doc describes. It powers two things: an orphan check (does the file still exist?) and a staleness check (is the related file newer than the doc?) — both computed cheaply at render time in PHP via `file_exists()` / `filemtime()`, not baked into the build.

### Build-time compilation (gulp)

A new task, `gulp/docs.js`, exported as `docs` and wired into `gulpfile.babel.js` alongside the existing `php`, `styles`, `scripts` tasks:

- **Dev mode** (`buildDev` / `gulp watch`): globs `docs/**/*.md`, validates frontmatter (`title` and `related_file` required), logs (doesn't throw on) missing/invalid fields — mirrors how `gulp/php.js` logs PHPCS problems via `phpcs.reporter('log')` without failing the task.
- **Production mode** (`bundleTheme`): for each valid `.md`, parses frontmatter + body with `front-matter`, converts the body to HTML with `marked`, and writes the compiled fragment to `<prodThemePath>/docs/<category>/<slug>.html`. Any local images/files referenced under `docs/<category>/assets/<slug>/` are copied verbatim into the same relative path in the bundle; external URLs pass through unchanged. A single `docs/manifest.json` is written listing every doc's `slug`, `title`, `category`, `related_file`, `summary`, and `updated_at` (the `.md` file's mtime).
- **A malformed `.md` file is skipped and logged, not fatal** — one bad doc can't break `npm run bundle`.

This keeps Markdown parsing entirely in the JS build toolchain (`marked` + `front-matter` as new `devDependencies` in `wp-rig/package.json`), so the production theme ships pre-rendered HTML and a JSON manifest — no PHP Markdown dependency, no Composer setup needed.

### WP-CLI scaffold command (single source of truth)

`wp-rig` currently has no `wp-cli/` directory (that pattern only exists in the sibling `wprig` theme, at `wprig/wp-cli/wp-rig-commands.php:1`, registered from `wprig/functions.php:87-89`). Add the same pattern to `wp-rig`:

- `wp-rig/wp-cli/theme-docs-commands.php` — a `Docs_Command extends WP_CLI_Command` class with a `scaffold` method:
  `wp rig docs scaffold <category> <slug> --related-file=<path>`
  - Creates `docs/<category>/<slug>.md` with the frontmatter stub above (title auto-generated from the slug, e.g. `post-meta` → "Post Meta").
  - Errors via `WP_CLI::error()` if the target file already exists, unless `--force` is passed.
- Registered from `wp-rig/functions.php` the same way `wprig` does it (`if ( defined( 'WP_CLI' ) && WP_CLI ) { require get_template_directory() . '/wp-cli/theme-docs-commands.php'; }`).

**Claude Code convention:** rather than building a second, separately-maintained stub generator, add a short rule (in a `CLAUDE.md` at the `wp-content` repo root) instructing that whenever a new theme component/pattern file is created, the same `wp rig docs scaffold ...` command is run (via the Local site's WP-CLI) to generate the stub, which is then filled in. One implementation, one frontmatter schema, no drift between "the CLI way" and "the AI way."

### Admin UI

New file `wp-rig/inc/theme-docs-admin.php` (procedural, matching the theme's existing style in `inc/template-tags.php` / `inc/template-functions.php`), hooked on `admin_menu`:

- Registers a top-level menu, "Theme Docs" (`manage_options` capability — admin-only in v1; SVPA is a single-site install, so no network-admin handling is needed).
- Reads `get_theme_file_path( 'docs/manifest.json' )`, groups entries by `category`, renders a simple two-pane screen: category-grouped nav on the left, selected doc's pre-compiled HTML fragment on the right.
- For each entry, checks `file_exists( get_theme_file_path( $related_file ) )` — shows an "orphaned: source file not found" badge if false — and compares `filemtime()` of the related file against the doc's `updated_at` — shows a "possibly stale" badge if the related file is newer.
- **Not in v1:** detecting *undocumented* items (items that exist but have no doc). There's no generic registry of "all custom theme items" to diff against without inventing a fixed taxonomy, which this system deliberately avoids. Noted as a known limitation below.

## Technical Considerations

- **Markdown library:** `marked` (HTML rendering) + `front-matter` (YAML frontmatter parsing), both added as `devDependencies` in `wp-rig/package.json`. Build-time only — nothing added to the production PHP runtime.
- **Output format bridging gulp → PHP:** one `manifest.json` (list/nav data) plus one compiled `.html` fragment per doc (content), rather than a single giant JSON blob of rendered HTML — keeps individual doc renders small and avoids re-parsing HTML out of JSON in PHP.
- **Path rewriting for images:** doc-local assets live under `docs/<category>/assets/<slug>/` in the source tree and are copied to the identical relative path in the bundle by the `docs` gulp task; Markdown image paths in `.md` files should be written relative to that convention (e.g. `assets/post-meta/screenshot.png`) so they resolve the same in source and bundle.
- **`.gitignore` fix (prerequisite):** `wp-content/.gitignore:9` currently ignores `themes/*` except `!themes/wp-rig` and `!themes/wa-farm-2-schools` — **SVPA-theme (the bundled production theme) is not tracked in git today.** Add `!themes/SVPA-theme` to that allowlist so the theme and its compiled `docs/` output are version-controlled going forward.

## System-Wide Impact

- **Interaction graph:** `npm run bundle` (`gulp bundleTheme`) runs `prodPrep` → `parallel(php, scripts, styles+blockStyles, images)` → `translate` → `prodStringReplace` → `prodCompress` (`wp-rig/gulpfile.babel.js:41-43`). The new `docs` task joins that `parallel()` group. `prodPrep` calls `createProdDir()` (`wp-rig/gulp/utils.js:112-122`), which **`rimraf.sync`-deletes the entire existing production theme directory before rebuilding it.**
- **Existing risk this feature doesn't create but does interact with:** SVPA-theme currently has custom code (a `components/` folder, a converted `SVPATheme` namespace) that does **not** exist in the `wp-rig` source it's supposedly bundled from. Running `npm run bundle` today would silently delete that drift. This plan doesn't fix that reconciliation, but implementers should commit SVPA-theme's current state (via the `.gitignore` fix above) **before** the next `npm run bundle`, or that hand-edited state is unrecoverable.
- **Failure propagation:** a malformed `.md` (bad frontmatter, unparseable body) is caught per-file inside the `docs` gulp task and logged via `fancy-log`/`colors` (matching `gulp/prodPrep.js`'s existing logging style) — it does not throw and does not abort `bundleTheme`.
- **Admin page load:** `manifest.json` is read fresh on every "Theme Docs" page view (no caching in v1) — acceptable given the expected doc count is small (tens, not thousands, of theme components).

## Acceptance Criteria

- [ ] `wp-rig/gulp/docs.js` compiles `docs/**/*.md` to HTML fragments + `manifest.json` during `npm run bundle`, and validates (without failing the build) during `npm run dev` / `gulp watch`.
- [ ] `marked` and `front-matter` added to `wp-rig/package.json` devDependencies.
- [ ] A malformed doc file is logged and skipped, not fatal to the bundle.
- [ ] `wp-rig/wp-cli/theme-docs-commands.php` provides `wp rig docs scaffold <category> <slug> --related-file=<path>`, refusing to overwrite an existing file without `--force`.
- [ ] `wp-rig/functions.php` registers the new WP-CLI command file (mirroring `wprig/functions.php:87-89`).
- [ ] `wp-rig/inc/theme-docs-admin.php` registers a `manage_options`-gated "Theme Docs" top-level admin page, grouped by category, rendering compiled doc HTML.
- [ ] Orphaned docs (missing `related_file`) and possibly-stale docs (related file newer than doc) are visibly flagged in the admin list.
- [ ] Local images/files referenced from a doc resolve correctly both in the `wp-rig` source tree and in the bundled `SVPA-theme` output.
- [ ] `wp-content/.gitignore` allowlists `!themes/SVPA-theme`, and the theme's current state is committed before any subsequent `npm run bundle`.
- [ ] A `CLAUDE.md` note documents the scaffold-on-create convention for AI-assisted component creation.

## Success Metrics

- Every new component/pattern added after this ships has a corresponding doc within the same working session (measured informally — no doc left un-scaffolded).
- Zero production `npm run bundle` failures caused by doc content.
- SVPA-theme is committed to git and stays in sync with `wp-rig` going forward (no more silent, unversioned drift).

## Dependencies & Risks

- **Known limitation (by design, not a bug):** v1 cannot detect *undocumented* items — only orphaned/stale *existing* docs — because there's no generic registry of "every custom theme item" to diff against without imposing a fixed taxonomy. A future iteration could add an opt-in registry (e.g., a PHP array of expected `related_file` paths) if this becomes painful.
- **No link-checking:** broken external URLs or mistyped local asset paths in a doc are not validated at build time in v1.
- **Bundle wipe risk:** `npm run bundle` fully deletes and regenerates the production theme directory (`wp-rig/gulp/utils.js:112-122`) — pre-existing behavior, but worth re-confirming before running it now that SVPA-theme has uncommitted hand-edits (see System-Wide Impact above).
- **Single WP-CLI source of truth depends on discipline:** the Claude Code convention only works if the assistant (or developer) actually invokes the WP-CLI command rather than hand-authoring a `.md` file with drifted frontmatter keys — worth a periodic spot-check.

## Sources & References

### Internal References

- Gulp task wiring: `wp-rig/gulpfile.babel.js:9-43`
- Prod directory wipe behavior: `wp-rig/gulp/utils.js:112-122`
- Existing "log, don't fail" pattern to mirror: `wp-rig/gulp/php.js:20-33`
- Config-driven file copying: `wp-rig/config/config.json` (`export.filesToCopy`), `wp-rig/gulp/prodPrep.js`
- Path/glob conventions: `wp-rig/gulp/constants.js:60-120`
- Existing WP-CLI command pattern to mirror: `wprig/wp-cli/wp-rig-commands.php:1`, registered at `wprig/functions.php:87-89`
- Current custom components (documentation subjects): `SVPA-theme/components/*.php` (e.g. `SVPA-theme/components/post-meta.php`)
- Block-editor customizations (another doc subject category): `SVPA-theme/inc/block-editor/*.php`
- `.gitignore` gap: `wp-content/.gitignore` (`themes/*` ignored except `!themes/wp-rig`, `!themes/wa-farm-2-schools` — `SVPA-theme` currently untracked)

### Related Work

- SpecFlow gap analysis (this session) surfaced: build-failure isolation, orphan/staleness detection, WP-CLI/AI scaffold drift risk, permissions scope, taxonomy-free grouping, and image path rewriting — all addressed in Proposed Solution / Technical Considerations / Dependencies & Risks above.
