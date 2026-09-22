/* eslint-env es6 */
'use strict';

/**
 * External dependencies
 */
import { src, dest } from 'gulp';
import pump from 'pump';
import newer from 'gulp-newer';
import imagemin from 'gulp-imagemin';

/**
 * Internal dependencies
 */
import { paths } from './constants.js';

/**
 * Optimize images.
 * @param {function} done function to call when async processes finish
 * @return {Stream} single stream
 */
export default function images( done ) {
	return pump( [
		src( paths.images.src ),
		newer( paths.images.dest ),
		imagemin(),
		dest( paths.images.dest ),
	], done );
}
