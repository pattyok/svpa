# Theme Docs

Markdown files in this folder are compiled into the **Theme Docs** page in wp-admin
by `gulp/docs.js` into `assets/docs/` — in wp-rig on `npm run dev` / `npm run build` (and on save
while `dev` is watching), and in the production theme on `npm run bundle`. The generated
`manifest.json` lives there too — don't write it by hand. This README is not compiled.

## Frontmatter

Every doc starts with a frontmatter block:

```markdown
---
title: Header and Footer          # required
summary: One-line description.    # optional
related_file: template-parts/header/branding.php  # optional
---
```

`related_file` is a path relative to the theme root. Set it for docs that describe a specific
file: the page shows an "orphaned" badge if the file is deleted and "possibly stale" if it
changed after the doc was last edited. Leave it out for general user guides.

## Organisation

- **Slug** = filename (`header-footer.md` → `header-footer`).
- **Category** = first folder under `docs/` (`docs/blocks/hero.md` → "blocks").
  Files directly in `docs/` are grouped under "general".
- `main.md` is the landing page shown when no doc is selected.

## Order

The sidebar lists docs in `docs/` itself first, then each folder in name order.
Within a group, `main.md` comes first, then files in name order. To set the order,
prefix folder or file names with a number:

```
docs/
  main.md
  01-getting-started/
  02-blocks/
    01-hero.md
    02-card-grid.md
```

The prefix (`01-`, `2_`, `10.`) is stripped from labels and slugs, so `02-blocks/01-hero.md`
shows under "blocks" with the slug `hero`. Links can use the real filename
(`[Hero](../02-blocks/01-hero.md)`) or the bare one (`hero.md`); both work. Numbers sort
naturally (2 before 10), but zero-padding keeps them tidy in your file browser.

## Links and images

- Link to another doc by its filename: `[Header & Footer](header-footer.md)`.
- Every heading gets an id from WordPress's `sanitize_title()` rules ("Site Footer" → `site-footer`,
  "Tips & Tricks" → `tips-tricks`). Repeated headings in one doc get `-2`, `-3`… Link to them
  within a doc with `[Footer](#site-footer)`, or from another doc with `[Footer](header-footer.md#site-footer)`.
- Images use paths relative to the doc: `![Menu screen](images/menus.png)`.
  Everything that isn't Markdown is copied into `assets/docs/` alongside the compiled HTML.
