# SVPA WordPress Project

## Theme development workflow

- `wp-rig` (`themes/wp-rig`) is the active development theme. Edit source there, never in `SVPA-theme` directly.
- `SVPA-theme` (`themes/SVPA-theme`) is the bundled production theme, generated from `wp-rig` via `npm run bundle` (run from inside `wp-rig`). Don't hand-edit it.
- `wprig` (`themes/wprig`) is an abandoned experiment - ignore it.

## Documenting new theme components

Whenever a new component, template part, or block pattern is added to `wp-rig`, scaffold its documentation immediately using the theme's own WP-CLI command - don't hand-write the `.md` file's frontmatter, since that's how the doc schema stays consistent:

```
wp theme-docs scaffold <category> <slug> --related-file=<path>
```

Example: after adding `wp-rig/components/post-meta.php`, run:

```
wp theme-docs scaffold components post-meta --related-file=components/post-meta.php
```

Then fill in the generated `wp-rig/docs/components/post-meta.md` stub. It compiles automatically during `npm run dev` / `npm run bundle` and shows up in wp-admin under **Theme Docs**.
