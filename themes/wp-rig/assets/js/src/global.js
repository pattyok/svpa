function moveNodeToParent(childSelector, parentSelector, prepend = false) {
	const child = document.querySelector(childSelector);
	const parent = document.querySelector(parentSelector);

	if (!child || !parent) {
		return;
	}

	if (prepend) {
		parent.prepend(child);
		return;
	}

	parent.append(child);
}

function hideSearch() {
	const searchDropdown = document.querySelector('header .search-dropdown');

	if (!searchDropdown) {
		return;
	}

	searchDropdown.setAttribute('aria-hidden', 'true');
	searchDropdown.style.display = 'none';
}

function moveSearchMobile() {
	hideSearch();

	if (window.innerWidth < 1025) {
		moveNodeToParent(
			'#before-navigation .before-navigation__inner',
			'#primary-menu-container',
			true
		);
		moveNodeToParent(
			'#after-navigation .after-navigation__inner',
			'#primary-menu-container'
		);
		moveNodeToParent(
			'#search_modal .search-dropdown__content',
			'#primary-menu-container'
		);
		return;
	}

	moveNodeToParent(
		'#primary-menu-container .search-dropdown__content',
		'#search_modal',
		true
	);
	moveNodeToParent(
		'#primary-menu-container .after-navigation__inner',
		'#after-navigation',
		true
	);
	moveNodeToParent(
		'#primary-menu-container .before-navigation__inner',
		'#before-navigation',
		true
	);
}

function setHeaderHeight() {
	const siteHeader = document.querySelector('.site-header');
	if (!siteHeader) {
		return;
	}

	let headerHeight = siteHeader.offsetHeight;
	if (document.body.classList.contains('admin-bar')) {
		const adminBar = document.querySelector('#wpadminbar');
		headerHeight += adminBar ? adminBar.offsetHeight : 0;
	}

	document.documentElement.style.setProperty(
		'--header-height',
		`${headerHeight}px`
	);
}

function setPageHeaderHeight() {
	const pageHeader = document.querySelector(
		'.wp-block-cover.is-style-page-header'
	);
	if (!pageHeader) {
		return;
	}

	if (window.innerWidth >= 600) {
		const headerGroup = pageHeader.querySelector('.wp-block-group');
		const headerGroupHeight = headerGroup ? headerGroup.offsetHeight : 0;
		pageHeader.style.paddingBottom = `${headerGroupHeight / 2}px`;
		return;
	}

	pageHeader.style.paddingBottom = '';
}

function setupAnchorScrolling() {
	const links = document.querySelectorAll(
		'a[href*="#"]:not([href="#"]):not([href="#0"]):not(.monitor-station-link)'
	);

	links.forEach((link) => {
		link.addEventListener('click', (event) => {
			const samePath =
				window.location.pathname.replace(/^\//, '') ===
				link.pathname.replace(/^\//, '');
			const sameHost = window.location.hostname === link.hostname;

			if (!samePath || !sameHost || !link.hash) {
				return;
			}

			let target = document.querySelector(link.hash);
			if (!target) {
				target = document.querySelector(
					`[name="${link.hash.slice(1)}"]`
				);
			}

			if (!target) {
				return;
			}

			event.preventDefault();
			const top =
				target.getBoundingClientRect().top + window.scrollY - 50;
			window.scrollTo({ top, behavior: 'smooth' });

			setTimeout(() => {
				target.focus();
				if (document.activeElement === target) {
					return;
				}
				target.setAttribute('tabindex', '-1');
				target.focus();
			}, 450);
		});
	});
}

function positionPopover(trigger, popover) {
	const triggerRect = trigger.getBoundingClientRect();
	const top = triggerRect.bottom + window.scrollY + 8;
	const left = triggerRect.left + window.scrollX;

	popover.style.position = 'absolute';
	popover.style.display = 'block';
	popover.style.top = `${top}px`;
	popover.style.left = `${left}px`;
	popover.style.width = '300px';

	const popoverRect = popover.getBoundingClientRect();
	const overflowRight = popoverRect.right - window.innerWidth;
	if (overflowRight > 0) {
		popover.style.left = `${Math.max(8, left - overflowRight - 8)}px`;
	}
}

function hideAllPopovers() {
	document.querySelectorAll('.gpopover').forEach((popover) => {
		popover.style.display = 'none';
	});
}

function setupPopovers() {
	const popoverTriggers = document.querySelectorAll('.info-popover');

	popoverTriggers.forEach((trigger) => {
		trigger.addEventListener('click', (event) => {
			event.preventDefault();

			const popoverId = trigger.getAttribute('data-popover');
			if (!popoverId) {
				return;
			}

			const popover = document.getElementById(popoverId);
			if (!popover) {
				return;
			}

			const wasVisible = popover.style.display === 'block';
			hideAllPopovers();

			if (!wasVisible) {
				positionPopover(trigger, popover);
			}
		});
	});

	document.addEventListener('click', (event) => {
		const trigger = event.target.closest('.info-popover');
		const openPopover = event.target.closest('.gpopover');
		if (trigger || openPopover) {
			return;
		}
		hideAllPopovers();
	});

	document.addEventListener('keydown', (event) => {
		if (event.key === 'Escape') {
			hideAllPopovers();
		}
	});
}

function setupSearchToggle() {
	const searchLinks = document.querySelectorAll(
		'#primary-menu .search-button a'
	);
	const searchDropdown = document.querySelector('header .search-dropdown');

	if (!searchDropdown || !searchLinks.length) {
		return;
	}

	searchLinks.forEach((link) => {
		link.addEventListener('click', (event) => {
			event.preventDefault();

			const hidden = searchDropdown.getAttribute('aria-hidden');
			if (hidden === 'false') {
				hideSearch();
				return;
			}

			const rect = link.getBoundingClientRect();
			const top = rect.top + window.scrollY + rect.height;
			const right = window.innerWidth - (rect.left + rect.width);

			searchDropdown.setAttribute('aria-hidden', 'false');
			searchDropdown.style.top = `${top}px`;
			searchDropdown.style.right = `${right}px`;
			searchDropdown.style.display = 'flex';
		});
	});

	document.addEventListener('click', (event) => {
		if (event.target.closest('.search-dropdown__close')) {
			hideSearch();
		}
	});
}

function setupPrintButtons() {
	document.querySelectorAll('.print-this-js').forEach((button) => {
		button.addEventListener('click', (event) => {
			event.preventDefault();
			window.print();
		});
	});
}

function setupScrolledClass() {
	window.addEventListener('scroll', () => {
		document.body.classList.toggle('scrolled', window.scrollY > 0);
	});
}

if (document.location.hash) {
	setTimeout(function () {
		window.scrollTo(window.scrollX, window.scrollY - 50);
	}, 10);
}

document.addEventListener('DOMContentLoaded', () => {
	moveSearchMobile();
	setHeaderHeight();
	setPageHeaderHeight();
	setupAnchorScrolling();
	setupPopovers();
	setupPrintButtons();
	setupSearchToggle();
	setupScrolledClass();

	window.addEventListener('resize', () => {
		moveSearchMobile();
		setHeaderHeight();
		setPageHeaderHeight();
	});
});
