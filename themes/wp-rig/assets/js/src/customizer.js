/**
 * File customizer.js.
 *
 * Theme Customizer enhancements for a better user experience.
 *
 * Contains handlers to make Theme Customizer preview reload changes asynchronously.
 */

function setTextContent(selector, text) {
	document.querySelectorAll(selector).forEach((element) => {
		element.textContent = text;
	});
}

function setStyles(selector, styles) {
	document.querySelectorAll(selector).forEach((element) => {
		Object.entries(styles).forEach(([property, value]) => {
			element.style[property] = value;
		});
	});
}

(function () {
	// Site title and description.
	wp.customize('blogname', function (value) {
		value.bind(function (to) {
			setTextContent('.site-title a', to);
		});
	});
	wp.customize('blogdescription', function (value) {
		value.bind(function (to) {
			setTextContent('.site-description', to);
		});
	});

	// Title Tagline.
	wp.customize('title_tagline_display', function (value) {
		value.bind(function (to) {
			if ('title_only' === to) {
				setStyles('.site-description', {
					clip: 'rect(1px, 1px, 1px, 1px)',
					position: 'absolute',
				});
				setStyles('.site-title', {
					clip: 'auto',
					position: 'relative',
				});
			} else if ('tagline_only' === to) {
				setStyles('.site-title', {
					clip: 'rect(1px, 1px, 1px, 1px)',
					position: 'absolute',
				});
				setStyles('.site-description', {
					clip: 'auto',
					position: 'relative',
				});
			} else if ('title_tagline' === to) {
				setStyles('.site-title, .site-description', {
					clip: 'auto',
					position: 'relative',
				});
			} else {
				setStyles('.site-title, .site-description', {
					clip: 'rect(1px, 1px, 1px, 1px)',
					position: 'absolute',
				});
			}
		});
	});

	// Header text color.
	wp.customize('header_textcolor', function (value) {
		value.bind(function (to) {
			setStyles('.site-title a, .site-description', {
				color: to,
			});
		});
	});
})();
