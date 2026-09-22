/* eslint-env es6 */
/* eslint no-undef: "off" */
'use strict';

// External dependencies
import {parallel, series} from 'gulp';

// Internal dependencies
import docs from './gulp/docs.js';
import generateCert from './gulp/generateCert.js';
import images from './gulp/images.js';
import php from './gulp/php.js';
import {serve} from './gulp/browserSync.js';
import scripts from './gulp/scripts.js';
import {styles, blockStyles} from './gulp/styles.js';
import translate from './gulp/translate.js';
import watch from './gulp/watch.js';
import prodPrep from './gulp/prodPrep.js';
import prodStringReplace from './gulp/prodStringReplace.js';
import prodCompress from './gulp/prodCompress.js';
import {cleanCSS, cleanJS} from './gulp/clean.js';

/**
 * Map out the sequence of events on first load and make it the default task
 */
export const firstRun = series(
    cleanCSS, cleanJS, parallel(php, images, series( styles, blockStyles ), scripts, docs), serve, watch
);

export default firstRun;

/**
 * Build theme for development without BrowserSync or watching
 */
export const buildDev = parallel(
    php, images, series( styles, blockStyles ), scripts, translate, docs
);

/**
 * Export theme for distribution.
 */
export const bundleTheme = series(
    prodPrep, parallel(php, scripts, series( styles, blockStyles ), images, docs), translate, prodStringReplace, prodCompress
);

/**
 * Export all imported functions as tasks
 */
export { docs, generateCert, images, php, scripts, styles, blockStyles, translate, watch, cleanCSS, cleanJS };
