/* eslint-env es6 */
'use strict';

/**
 * External dependencies
 */
import fs from 'fs';
import path from 'path';
import glob from 'glob';
import mkdirp from 'mkdirp';
import { marked } from 'marked';
import frontMatter from 'front-matter';
import log from 'fancy-log';
import colors from 'ansi-colors';

/**
 * Internal dependencies
 */
import { paths, isProd } from './constants.js';

const REQUIRED_FIELDS = ['title', 'related_file'];

/**
 * Read every docs/**\/*.md source file and parse its frontmatter + body.
 * A file with invalid frontmatter, or missing a required field, is logged
 * and skipped rather than thrown - one bad doc can't break the build.
 * @return {Array} parsed doc entries
 */
function readDocs() {
	const files = glob.sync(paths.docs.src);
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

		// Category is the first path segment under docs/, e.g. docs/components/x.md -> "components".
		const relativeDir = path.dirname(
			path.relative(paths.docs.srcDir, filePath)
		);
		const category =
			'.' === relativeDir
				? 'uncategorized'
				: relativeDir.split(path.sep)[0];
		const slug = path.basename(filePath, '.md');

		entries.push({
			slug,
			category,
			title: parsed.attributes.title,
			relatedFile: parsed.attributes.related_file,
			summary: parsed.attributes.summary || '',
			body: parsed.body,
			sourcePath: filePath,
			updatedAt: fs.statSync(filePath).mtime.toISOString(),
		});
	}

	return entries;
}

/**
 * Compile docs/**\/*.md into HTML fragments plus a manifest.json for the
 * "Theme Docs" admin page. Only runs the compile step in production; in dev
 * mode this validates frontmatter and logs problems without writing files.
 * @param {Function} done function to call when async processes finish
 */
export default function docs(done) {
	if (!isProd) {
		// readDocs() validates frontmatter and logs problems as a side effect.
		readDocs();
		return done();
	}

	const entries = readDocs();
	const manifest = [];

	for (const entry of entries) {
		const destDir = `${paths.docs.dest}/${entry.category}`;
		mkdirp.sync(destDir);

		let html;
		try {
			html = marked.parse(entry.body);
		} catch (error) {
			log(
				colors.red(
					`${colors.bold('Docs:')} could not render ${entry.sourcePath}: ${error.message}`
				)
			);
			continue;
		}

		fs.writeFileSync(`${destDir}/${entry.slug}.html`, html);

		// Copy any local assets authored alongside this doc.
		const assetsSrcDir = `${paths.docs.srcDir}/${entry.category}/assets/${entry.slug}`;
		if (fs.existsSync(assetsSrcDir)) {
			const assetsDestDir = `${destDir}/assets/${entry.slug}`;
			mkdirp.sync(assetsDestDir);
			for (const asset of fs.readdirSync(assetsSrcDir)) {
				fs.copyFileSync(
					`${assetsSrcDir}/${asset}`,
					`${assetsDestDir}/${asset}`
				);
			}
		}

		manifest.push({
			slug: entry.slug,
			category: entry.category,
			title: entry.title,
			related_file: entry.relatedFile,
			summary: entry.summary,
			updated_at: entry.updatedAt,
		});
	}

	mkdirp.sync(paths.docs.dest);
	fs.writeFileSync(
		`${paths.docs.dest}/manifest.json`,
		JSON.stringify(manifest, null, 2)
	);

	done();
}
