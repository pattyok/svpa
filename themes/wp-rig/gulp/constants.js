/* eslint-env es6 */
'use strict';

/**
 * External dependencies
 */
import path from 'path';

/**
 * Internal dependencies
 */
import { getThemeConfig, configValueDefined } from './utils.js';

// Root path is where npm run commands happen
export const rootPath = process.cwd();

export const gulpPath = `${rootPath}/gulp`;

export const gulpTestPath = `${rootPath}/gulp/tests`;

// Dev or production
export const isProd = process.env.NODE_ENV === 'production';

// get the config
const config = getThemeConfig();

// directory for the production theme
export const prodThemePath = path.normalize(
	`${rootPath}/../${config.theme.slug}`
);

// directory for assets (CSS, JS, images)
export const assetsDir = `${rootPath}/assets`;

// directory for assets (CSS, JS, images) in production
export const prodAssetsDir = `${prodThemePath}/assets`;

// PHPCS options
const phpcsBin = Object.prototype.hasOwnProperty.call(config.dev, 'phpcsBin')
	? config.dev.phpcsBin
	: `${rootPath}/vendor/bin/phpcs`;
export const PHPCSOptions = {
	bin: phpcsBin,
	showSniffCode: true,
	report: 'full',
	reporter: 'log',
};

// Theme config name fields and their defaults
export const nameFieldDefaults = {
	author: 'The WP Rig Contributors',
	PHPNamespace: 'WP_Rig\\WP_Rig',
	slug: 'wp-rig',
	name: 'WP Rig',
	underscoreCase: 'wp_rig',
	constant: 'WP_RIG',
	camelCase: 'WpRig',
	camelCaseVar: 'wpRig',
};

// Project paths
const paths = {
	assetsDir,
	browserSync: {
		dir: `${rootPath}/BrowserSync`,
		cert: `${rootPath}/BrowserSync/wp-rig-browser-sync-cert.crt`,
		caCert: `${rootPath}/BrowserSync/wp-rig-browser-sync-root-cert.crt`,
		key: `${rootPath}/BrowserSync/wp-rig-browser-sync-key.key`,
	},
	config: {
		themeConfig: `${rootPath}/config/themeConfig.js`,
	},
	php: {
		src: [
			`${rootPath}/**/*.php`,
			`!${rootPath}/optional/**/*.*`,
			`!${rootPath}/tests/**/*.*`,
			`!${rootPath}/vendor/**/*.*`,
		],
		dest: `${rootPath}/`,
	},
	styles: {
		blockSrc: [
			`${assetsDir}/css/blocks/src/*.css`,
			// Ignore partial files.
			`!${assetsDir}/css/blocks/src/_*.css`,
		],
		blockDest: `${assetsDir}/css/blocks`,
		src: [
			`${assetsDir}/css/global/src/*.css`,
			// Ignore partial files.
			`!${assetsDir}/css/global/src/_*.css`,
		],
		srcDir: `${assetsDir}/css/global/src`,
		dest: `${assetsDir}/css/global`,
		watch: `${assetsDir}/css/**/src/*.css`,
	},
	scripts: {
		src: [
			`${assetsDir}/js/src/**/*.js`,
			// Ignore partial files.
			`!${assetsDir}/js/src/**/_*.js`,
		],
		srcDir: `${assetsDir}/js/src`,
		dest: `${assetsDir}/js`,
	},
	images: {
		src: `${assetsDir}/images/src/**/*.{jpg,JPG,png,svg,gif,GIF}`,
		dest: `${assetsDir}/images/`,
	},
	export: {
		src: [],
		stringReplaceSrc: [
			`${rootPath}/style.css`,
			`${rootPath}/languages/*.po`,
		],
	},
	docs: {
		src: `${rootPath}/docs/**/*.md`,
		srcDir: `${rootPath}/docs`,
		dest: `${rootPath}/docs`,
	},
	languages: {
		src: [
			`${rootPath}/**/*.php`,
			`!${rootPath}/optional/**/*.*`,
			`!${rootPath}/tests/**/*.*`,
			`!${rootPath}/vendor/**/*.*`,
		],
		dest: `${rootPath}/languages/${nameFieldDefaults.slug}.pot`,
	},
};

// Add rootPath to filesToCopy and additionalFilesToCopy
const additionalFilesToCopy = configValueDefined('export.additionalFilesToCopy')
	? config.export.additionalFilesToCopy
	: [];
const filesToCopy = configValueDefined('export.filesToCopy')
	? config.export.filesToCopy
	: [];
for (const filePath of filesToCopy.concat(additionalFilesToCopy)) {
	// Add the files to export src
	paths.export.src.push(`${rootPath}/${filePath}`);
}

// Override paths for production
if (isProd) {
	paths.php.dest = `${prodThemePath}/`;
	paths.styles.dest = `${prodAssetsDir}/css/global`;
	paths.styles.blockDest = `${prodAssetsDir}/css/blocks/`;
	paths.scripts.dest = `${prodAssetsDir}/js/`;
	paths.images.dest = `${prodAssetsDir}/images/`;
	paths.languages = {
		src: `${prodThemePath}/**/*.php`,
		dest: `${prodThemePath}/languages/${config.theme.slug}.pot`,
	};
	paths.docs.dest = `${prodThemePath}/docs`;
}

export { paths };
