/* eslint-env es6 */
'use strict';

// External dependencies
/**
 * External dependencies
 */
import { src, dest } from 'gulp';
import pump from 'pump';
import { pipeline } from 'mississippi';
import newer from 'gulp-newer';
import gulpIf from 'gulp-if';
import babel from 'gulp-babel';
import uglify from 'gulp-uglify';
import rename from 'gulp-rename';

/**
 * Internal dependencies
 */
import { paths, isProd, assetsDir } from './constants.js';
import { getThemeConfig, getStringReplacementTasks, logError } from './utils.js';

export function scriptsBeforeReplacementStream() {
	// Return a single stream containing all the
	// before replacement functionality
	return pipeline.obj( [
		logError( 'JavaScript' ),
		newer( {
			dest: paths.scripts.dest,
			extra: [ paths.config.themeConfig ],
		} ),
	] );
}

export function scriptsAfterReplacementStream() {
	const config = getThemeConfig();

	// Return a single stream containing all the
	// after replacement functionality
	return pipeline.obj( [
		babel( {
			presets: [
				'@babel/preset-env',
			],
		} ),
		gulpIf(
			! config.dev.debug.scripts,
			uglify()
		),
		rename( {
			suffix: '.min',
		} ),
	] );
}

/**
 * JavaScript via Babel, ESlint, and uglify.
 * @param {function} done function to call when async processes finish
 * @return {Stream} single stream
 */
export default function scripts( done ) {
	return pump( [
		src( paths.scripts.src, { sourcemaps: ! isProd } ),
		scriptsBeforeReplacementStream(),
		// Only do string replacements when building for production
		gulpIf(
			isProd,
			getStringReplacementTasks()
		),
		scriptsAfterReplacementStream(),
		dest( paths.scripts.dest, { sourcemaps: ! isProd } ),
	], done );
}
