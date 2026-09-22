/* eslint-env es6 */
'use strict';

/**
 * External dependencies
 */
import { src, dest } from 'gulp';
import postcssPresetEnv from 'postcss-preset-env';
import postcssSimpleVars from 'postcss-simple-vars';
import postcssNested from 'postcss-nested';
import AtImport from 'postcss-import';
import pump from 'pump';
import cssnano from 'cssnano';
import stylelint from 'stylelint';
import reporter from 'postcss-reporter';
import calc from 'postcss-calc';
import { pipeline } from 'mississippi';
import newer from 'gulp-newer';
import phpcs from 'gulp-phpcs';
import postcss from 'gulp-postcss';
import gulpIf from 'gulp-if';
import tabify from 'gulp-tabify';
import rename from 'gulp-rename';

/**
 * Internal dependencies
 */
import { rootPath, paths, isProd } from './constants.js';
import {
	getThemeConfig,
	getStringReplacementTasks,
	logError,
	configValueDefined,
	appendBaseToFilePathArray,
} from './utils.js';
import { server } from './browserSync.js';
// get the config
const config = getThemeConfig();

export function stylesBeforeReplacementStream() {
	// Return a single stream containing all the
	// before replacement functionality
	const phpcsBin = Object.prototype.hasOwnProperty.call( config.dev, 'phpcsBin' ) ? config.dev.phpcsBin : `${ rootPath }/vendor/bin/phpcs`;
	return pipeline.obj( [
		logError( 'CSS' ),
		newer( {
			dest: paths.styles.dest,
			extra: [ paths.config.themeConfig ],
		} ),
		phpcs( {
			bin: phpcsBin,
			standard: 'WordPress',
			warningSeverity: 0,
		} ),
		// Log all problems that were found.
		phpcs.reporter( 'log' ),
	] );
}

export function stylesAfterReplacementStream() {
	const config = getThemeConfig();

	const postcssPlugins = [
		//stylelint(),
		postcssNested(),
		postcssPresetEnv( {
			insertAfter: {
				'all-property': postcssSimpleVars,
			},
			stage: 3,
			autoprefixer: {
				grid: true,
			},
			features: {
				'nesting-rules': false,
				'custom-properties': {
					preserve: true,
				},
				'custom-media-queries': {
					preserve: false,
				},
				"custom-selectors": true,
				'is-pseudo-class': false
			},
		} ),
		calc( {
			preserve: false,
		} ),
		cssnano({
			preset: [
			  'default',
			  { cssDeclarationSorter: false,
				mergeRules: false
			   }
			]
		  }),
	];

	// THIS NOT WORKING
	// postcssPresetEnv( {
	// 	insertAfter: {
	// 		'all-property': postcssSimpleVars,
	// 	},
	// 	stage: (
	// 		configValueDefined( 'config.dev.styles.stage' )
	// 			? config.dev.styles.stage
	// 			: 3
	// 	),
	// 	autoprefixer: (
	// 		configValueDefined( 'config.dev.styles.autoprefixer' )
	// 			? config.dev.styles.autoprefixer
	// 			: {}
	// 	),
	// 	features: (
	// 		configValueDefined( 'config.dev.styles.features' )
	// 			? config.dev.styles.features
	// 			: {
	// 				'custom-media-queries': {
	// 					preserve: false,
	// 				},
	// 				'custom-properties': {
	// 					preserve: true,
	// 				},
	// 				'nesting-rules': true,
	// 			}
	// 	),
	// } ),

	// Skip minifying files if we aren't building for
	// production and debug is enabled
	if ( config.dev.debug.styles && ! isProd ) {
		postcssPlugins.pop();
	}

	// Report messages from other postcss plugins
	postcssPlugins.push(
		reporter( { clearReportedMessages: true } )
	);

	// Return a single stream containing all the
	// after replacement functionality
	return pipeline.obj( [
		postcss( [
			AtImport( {
				path: [ paths.styles.srcDir ],
				plugins: [
					stylelint(),
				],
			} ),
		] ),
		postcss( postcssPlugins ),
		gulpIf(
			config.dev.debug.styles,
			tabify( 2, true )
		),
		rename( {
			suffix: '.min',
		} ),
		// gulpPlugins.if(
		// 	isProd,
		// 	writeToManifest()
		// ),
		server.stream( { match: '**/*.css' } ),

	] );
}

// export function writeToManifest() {
// 	return pipeline.obj( [
// 		gulpPlugins.rev(),
// 		dest(paths.styles.dest),
// 		gulpPlugins.rev.manifest( {
// 			base: assetsDir,
// 			merge: true
// 		}),
// 		dest(assetsDir),
// 	]);
// }

/**
 * CSS via PostCSS + CSSNext (includes Autoprefixer by default).
 *
 * @param {Function} done function to call when async processes finish
 * @return {Stream} single stream
 */
export function styles( done ) {
	return pump( [
		src( paths.styles.src, { sourcemaps: ! isProd } ),
		stylesBeforeReplacementStream(),
		// Only do string replacements when building for production
		gulpIf(
			isProd,
			getStringReplacementTasks()
		),
		stylesAfterReplacementStream(),
		dest( paths.styles.dest, { sourcemaps: ! isProd } ),
	], done );
}

export function blockStyles( done ) {
	return pump( [
		src( paths.styles.blockSrc, { sourcemaps: ! isProd } ),
		stylesBeforeReplacementStream(),
		// Only do string replacements when building for production
		gulpIf(
			isProd,
			getStringReplacementTasks()
		),
		stylesAfterReplacementStream(),
		dest( paths.styles.blockDest, { sourcemaps: ! isProd } ),
	], done );
}
