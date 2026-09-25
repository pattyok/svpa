---
title: Entry Meta
related_file: template-parts/content/entry-meta.php
summary: Renders the post meta line (date, author, etc.) under an entry.
---

Renders the meta line under a post entry - typically the publish date and
author byline. Included from `template-parts/content/entry.php` for
single posts and archive listings.

## Usage

```php
get_template_part( 'template-parts/content/entry-meta' );
```

## Notes

- Only renders for the `post` post type.
- Pulls formatting from `wp_rig()->posted_on()` and `wp_rig()->posted_by()`.
