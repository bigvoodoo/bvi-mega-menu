/**
 * BVI Mega Menu - Standard (non-AJAX) frontend behavior.
 *
 * Handles hover dropdowns and mobile toggle.
 */

const SELECTORS = {
	container: '.bvi-mega-menu-container',
	mobileToggle: '.mobile-toggle',
	mobileMenu: '.bvi-mega-menu-custom-mobile-menu',
	depthZero: '.menu-item-depth-0',
	megaMenu: '.mega-menu',
};

function initMobileToggle() {
	document.querySelectorAll(SELECTORS.mobileToggle).forEach((toggle) => {
		toggle.addEventListener('click', (event) => {
			event.preventDefault();

			toggle.classList.toggle('open');

			const menu = toggle.nextElementSibling;
			if (menu) {
				jQuery(menu).slideToggle();
			}
		});
	});
}

function initHoverDropdowns() {
	document.querySelectorAll(SELECTORS.depthZero).forEach((item) => {
		item.addEventListener('mouseenter', () => {
			if (isMobileView()) {
				return;
			}

			// hide all other mega menus
			document
				.querySelectorAll(
					`${SELECTORS.depthZero} > ${SELECTORS.megaMenu}`
				)
				.forEach((menu) => {
					jQuery(menu).stop(true, true).hide();
				});

			// show this item's mega menu
			const megaMenu = item.querySelector(SELECTORS.megaMenu);
			if (megaMenu && megaMenu.querySelector('ul')) {
				jQuery(megaMenu).slideDown();
			}
		});

		item.addEventListener('mouseleave', () => {
			document
				.querySelectorAll(
					`${SELECTORS.depthZero} > ${SELECTORS.megaMenu}`
				)
				.forEach((menu) => {
					jQuery(menu).stop(true, true).hide();
				});

			const megaMenu = item.querySelector(SELECTORS.megaMenu);
			if (megaMenu && megaMenu.querySelector('ul')) {
				jQuery(megaMenu).slideUp();
			}
		});
	});
}

function initResizeHandler() {
	let wasMobile = isMobileView();

	window.addEventListener('resize', () => {
		const isMobile = isMobileView();
		const hasCustomMobile =
			document.querySelector(SELECTORS.mobileMenu) !== null;

		if (isMobile && !wasMobile) {
			if (hasCustomMobile) {
				jQuery(SELECTORS.mobileMenu).hide();
			}
			jQuery(SELECTORS.container).hide();
			jQuery(`${SELECTORS.container} ${SELECTORS.megaMenu}`).hide();
			wasMobile = true;
		} else if (!isMobile && wasMobile) {
			if (hasCustomMobile) {
				jQuery(SELECTORS.mobileMenu).show().removeAttr('style');
			} else {
				jQuery(SELECTORS.container).show().removeAttr('style');
			}
			wasMobile = false;
		}

		// reset mobile toggles
		document.querySelectorAll(SELECTORS.mobileToggle).forEach((toggle) => {
			toggle.classList.remove('open');
			const menu = toggle.nextElementSibling;
			if (menu) {
				menu.removeAttribute('style');
			}
		});
	});
}

function isMobileView() {
	const toggle = document.querySelector(SELECTORS.mobileToggle);
	return toggle ? toggle.offsetParent !== null : false;
}

// Initialize on DOM ready
jQuery(function () {
	initMobileToggle();
	initHoverDropdowns();
	initResizeHandler();
});
