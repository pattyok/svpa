/* eslint-env es6 */
'use strict';

/**
 * External dependencies
 */
import { watch as gulpWatch, series, src } from 'gulp';
import pump from 'pump';
import phpcs from 'gulp-phpcs';

/**
 * Internal dependencies
 */
import { paths, PHPCSOptions } from './constants.js';
import { getThemeConfig, backslashToForwardSlash } from './utils.js';
import { reload } from './browserSync.js';
import images from './images.js';
import scripts from './scripts.js';
import {styles, blockStyles} from './styles.js';

/**
 * Watch everything
 */
export default function watch() {
	/**
	 * gulp watch uses chokidar, which doesn't play well with backslashes
	 * in file paths, so they are replaced with forward slashes, which are
	 * valid for Windows paths in a NodeJS context.
	 */
	const PHPwatcher = gulpWatch( backslashToForwardSlash( paths.php.src ), reload );
	const config = getThemeConfig();

	// Only code sniff PHP files if the debug setting is true
	if ( config.dev.debug.phpcs ) {
		PHPwatcher.on( 'change', function( path ) {
			return pump( [
				src( path ),
				// Run code sniffing
				phpcs( PHPCSOptions ),
				// Log all problems that were found.
				phpcs.reporter( 'log' ),
			] );
		} );
	}

	gulpWatch( backslashToForwardSlash( paths.styles.watch ), series( styles, blockStyles ) );

	gulpWatch( backslashToForwardSlash( paths.scripts.src[ 0 ] ), series( scripts, reload ) );

	gulpWatch( backslashToForwardSlash( paths.images.src ), series( images, reload ) );
}
