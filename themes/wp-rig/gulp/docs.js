/* eslint-env es6 */
'use strict';

/**
 * External dependencies
 */
import fs from 'fs';
import path from 'path';
import glob from 'glob';
import mkdirp from 'mkdirp';
import { Marked } from 'marked';
import frontMatter from 'front-matter';
import log from 'fancy-log';
import colors from 'ansi-colors';

/**
 * Internal dependencies
 */
import { paths } from './constants.js';

const REQUIRED_FIELDS = ['title'];

// Replaced with the theme's assets/docs/ URL by Theme_Docs\Component at render time,
// since the final theme URL isn't known at build time.
const DOCS_URL_PLACEHOLDER = '__THEME_DOCS_URL__';

/**
 * Strip an ordering prefix from a folder or file name: "02-blocks" -> "blocks".
 * The prefix only controls sidebar order; it's left out of slugs and labels.
 * @param {string} name folder or file name
 * @return {string} name without the prefix
 */
function stripOrder(name) {
	return name.replace(/^\d+[-_.]/, '');
}

// Characters WordPress's sanitize_title_with_dashes() handles specially in
// 'save' context, as lowercase UTF-8 octets (wp-includes/formatting.php).
const WP_TO_HYPHEN = [
	'%c2%a0', '%e2%80%91', '%e2%80%93', '%e2%80%94', // nbsp, non-breaking hyphen, ndash, mdash.
	'&nbsp;', '&#8209;', '&#160;', '&ndash;', '&#8211;', '&mdash;', '&#8212;',
	'/',
];
const WP_STRIP = [
	'%c2%ad', // Soft hyphen.
	'%c2%a1', '%c2%bf', // Inverted ! and ?.
	'%c2%ab', '%c2%bb', '%e2%80%b9', '%e2%80%ba', // Angle quotes.
	'%e2%80%98', '%e2%80%99', '%e2%80%9c', '%e2%80%9d', '%e2%80%9a', '%e2%80%9b', '%e2%80%9e', '%e2%80%9f', // Curly quotes.
	'%e2%80%a2', // Bullet.
	'%c2%a9', '%c2%ae', '%c2%b0', '%e2%80%a6', '%e2%84%a2', // Copy, reg, deg, hellip, trade.
	'%c2%b4', '%cb%8a', '%cc%81', '%cd%81', '%cc%80', '%cc%84', '%cc%8c', // Accents.
	'%e2%80%8b', '%e2%80%8c', '%e2%80%8d', '%e2%80%8e', '%e2%80%8f', // Zero-width chars and marks.
	'%e2%80%aa', '%e2%80%ab', '%e2%80%ac', '%e2%80%ad', '%e2%80%ae', // Directional formatting.
	'%ef%bb%bf', '%ef%bf%bc', // BOM, object replacement.
];
const WP_SPACES_TO_HYPHEN = [
	'%e2%80%80', '%e2%80%81', '%e2%80%82', '%e2%80%83', '%e2%80%84', '%e2%80%85', '%e2%80%86',
	'%e2%80%87', '%e2%80%88', '%e2%80%89', '%e2%80%8a', '%e2%80%a8', '%e2%80%a9', '%e2%80%af',
];
// Letters remove_accents() maps that Unicode decomposition doesn't cover.
const ACCENT_MAP = { ß: 's', æ: 'ae', Æ: 'AE', ø: 'o', Ø: 'O', œ: 'oe', Œ: 'OE', đ: 'd', Đ: 'D', ð: 'd', Ð: 'D', þ: 'th', Þ: 'TH', ł: 'l', Ł: 'L', ı: 'i' };

/**
 * Port of WordPress's sanitize_title() (save context): remove_accents()
 * followed by sanitize_title_with_dashes(). "Tips & Tricks: Step 1.2" -> "tips-tricks-step-1-2".
 * @param {string} title heading text (may contain HTML)
 * @return {string} slug
 */
function sanitizeTitle(title) {
	let s = title.replace(/<[^>]*>/g, '');

	// remove_accents().
	s = s
		.normalize('NFD')
		.replace(/[̀-ͯ]/g, '')
		.replace(/[ßæÆøØœŒđĐðÐþÞłŁı]/g, (c) => ACCENT_MAP[c])
		.normalize('NFC');

	// Keep escaped octets, drop other percent signs.
	s = s.replace(/%(?![a-fA-F0-9]{2})/g, '');

	// mb_strtolower() + utf8_uri_encode() + strtolower().
	s = s
		.toLowerCase()
		.replace(/[^\x00-\x7f]/gu, (c) => encodeURIComponent(c))
		.toLowerCase();

	for (const str of WP_TO_HYPHEN) s = s.split(str).join('-');
	for (const str of WP_STRIP) s = s.split(str).join('');
	for (const str of WP_SPACES_TO_HYPHEN) s = s.split(str).join('-');
	s = s.split('%c3%97').join('x'); // &times;

	return s
		.replace(/&.+?;/g, '')
		.replace(/\./g, '-')
		.replace(/[^%a-z0-9 _-]/g, '')
		.replace(/\s+/g, '-')
		.replace(/-+/g, '-')
		.replace(/^-+|-+$/g, '');
}

/**
 * Whether a link/image target points inside the docs folder (not an absolute
 * URL, protocol link, root-relative path, or in-page anchor).
 * @param {string} href link or image target
 * @return {boolean} true if relative to the doc file
 */
function isRelative(href) {
	return !!href && !/^([a-z][a-z0-9+.-]*:|\/|#)/i.test(href);
}

/**
 * Render a doc's Markdown body to HTML, rewriting relative targets so they
 * work inside wp-admin:
 * - `[text](other-doc.md)` -> link to that doc on the Theme Docs page.
 * - `![alt](images/foo.png)` -> URL of the copied image in the theme's docs/.
 * Headings get WordPress-style slug ids (de-duplicated with -2, -3...) so
 * they can be linked to, e.g. `[Footer](header-footer.md#site-footer)`.
 * @param {Object} entry parsed doc entry
 * @return {string} HTML
 */
function renderDoc(entry) {
	const docDir = path.dirname(entry.sourcePath);
	const usedIds = {};
	const marked = new Marked({
		renderer: {
			heading(text, level) {
				let id = sanitizeTitle(text);
				if (!id) {
					return false; // Default rendering, no id.
				}
				usedIds[id] = (usedIds[id] || 0) + 1;
				if (usedIds[id] > 1) {
					id += `-${usedIds[id]}`;
				}
				return `<h${level} id="${id}">${text}</h${level}>\n`;
			},
		},
		walkTokens(token) {
			if ('link' === token.type && isRelative(token.href)) {
				const [target, hash] = token.href.split('#');
				if (target.endsWith('.md')) {
					token.href =
						`admin.php?page=theme-docs&doc=${stripOrder(path.basename(target, '.md'))}` +
						(hash ? `#${hash}` : '');
				}
			}
			if ('image' === token.type && isRelative(token.href)) {
				const relativeToDocs = path
					.relative(paths.docs.srcDir, path.resolve(docDir, token.href))
					.split(path.sep)
					.join('/');
				token.href = `${DOCS_URL_PLACEHOLDER}/${relativeToDocs}`;
			}
		},
	});

	return marked.parse(entry.body);
}

/**
 * Read every docs/**\/*.md source file and parse its frontmatter + body.
 * A file with invalid frontmatter, or missing a required field, is logged
 * and skipped rather than thrown - one bad doc can't break the build.
 * @return {Array} parsed doc entries
 */
function readDocs() {
	const files = glob.sync(paths.docs.src, { ignore: '**/README.md' });
	const entries = [];

	for (const filePath of files) {
		const raw = fs.readFileSync(filePath, 'utf8');
		let parsed;

		try {
			parsed = frontMatter(raw);
		} catch (error) {
			log(
				colors.red(
					`${colors.bold('Docs:')} could not parse frontmatter in ${filePath}: ${error.message}`
				)
			);
			continue;
		}

		const missing = REQUIRED_FIELDS.filter(
			(field) => !parsed.attributes[field]
		);
		if (missing.length) {
			log(
				colors.yellow(
					`${colors.bold('Docs:')} skipping ${filePath}, missing required field(s): ${missing.join(', ')}`
				)
			);
			continue;
		}

		// Category is the first path segment under docs/, e.g. docs/02-blocks/x.md -> "blocks".
		// Docs directly in docs/ are grouped under "general".
		const relativeDir = path.dirname(
			path.relative(paths.docs.srcDir, filePath)
		);
		const categoryDir =
			'.' === relativeDir ? '' : relativeDir.split(path.sep)[0];
		const fileName = path.basename(filePath, '.md');

		entries.push({
			slug: stripOrder(fileName),
			category: categoryDir ? stripOrder(categoryDir) : 'general',
			sortKey: [categoryDir, fileName],
			title: parsed.attributes.title,
			relatedFile: parsed.attributes.related_file || '',
			summary: parsed.attributes.summary || '',
			body: parsed.body,
			sourcePath: filePath,
			updatedAt: fs.statSync(filePath).mtime.toISOString(),
		});
	}

	// Sidebar order: docs in docs/ itself, then folders by name (so numeric
	// prefixes like "01-" control order); within each, "main" first, then by filename.
	const compare = (a, b) =>
		a.localeCompare(b, undefined, { numeric: true });
	return entries.sort(
		(a, b) =>
			compare(a.sortKey[0], b.sortKey[0]) ||
			('main' === b.slug) - ('main' === a.slug) ||
			compare(a.sortKey[1], b.sortKey[1])
	);
}

/**
 * Compile docs/**\/*.md into HTML fragments plus a manifest.json for the
 * "Theme Docs" admin page, in assets/docs/ of the dev theme (wp-rig) or the
 * production theme when bundling.
 * @param {Function} done function to call when async processes finish
 */
export default function docs(done) {
	const entries = readDocs();

	// Output is fully generated, so start clean to drop deleted docs.
	fs.rmSync(paths.docs.dest, { recursive: true, force: true });
	const manifest = [];

	for (const entry of entries) {
		const destDir = `${paths.docs.dest}/${entry.category}`;
		mkdirp.sync(destDir);

		let html;
		try {
			html = renderDoc(entry);
		} catch (error) {
			log(
				colors.red(
					`${colors.bold('Docs:')} could not render ${entry.sourcePath}: ${error.message}`
				)
			);
			continue;
		}

		fs.writeFileSync(`${destDir}/${entry.slug}.html`, html);

		manifest.push({
			slug: entry.slug,
			category: entry.category,
			title: entry.title,
			related_file: entry.relatedFile,
			summary: entry.summary,
			updated_at: entry.updatedAt,
		});
	}

	// Copy images and other non-Markdown files, keeping their paths under docs/.
	const assets = glob.sync(`${paths.docs.srcDir}/**/*`, {
		nodir: true,
		ignore: '**/*.md',
	});
	for (const asset of assets) {
		const dest = `${paths.docs.dest}/${path.relative(paths.docs.srcDir, asset)}`;
		mkdirp.sync(path.dirname(dest));
		fs.copyFileSync(asset, dest);
	}

	mkdirp.sync(paths.docs.dest);
	fs.writeFileSync(
		`${paths.docs.dest}/manifest.json`,
		JSON.stringify(manifest, null, 2)
	);

	done();
}
