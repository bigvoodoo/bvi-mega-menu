/**
 * BVI Mega Menu - AJAX-based frontend behavior.
 *
 * Loads dropdown content via AJAX on hover, caches results.
 */

/* global DropdownSpeed */

const SELECTORS = {
	container: '.bvi-mega-menu-container',
	mobileToggle: '.mobile-toggle',
	mobileMenu: '.bvi-mega-menu-custom-mobile-menu',
	depthZero: '.menu-item-depth-0',
	megaMenu: '.mega-menu',
};

function showMegaMenu($el) {
	if (DropdownSpeed.instant_dropdown) {
		$el.stop(true, true).show();
	} else {
		$el.stop(true, true).slideDown(300);
	}
}

function hideMegaMenu($el) {
	if (DropdownSpeed.instant_dropdown) {
		$el.stop(true, true).hide();
	} else {
		$el.stop(true, true).slideUp(300);
	}
}

function loadMenuAjax($item, themeLocation, home) {
	const $megaMenu = $item.find(SELECTORS.megaMenu);

	if (!$megaMenu.is(':empty')) {
		return;
	}

	const id = $item.attr('id').replace(/[^0-9]+/, '');
	const url =
		'/ajax_mega_menu/' + encodeURIComponent(themeLocation) + '/' + id;
	const qs = jQuery.extend(
		{},
		$item.parents('ul.bvi-mega-menu-container').data()
	);

	jQuery('script[src]').each(function () {
		const match = jQuery(this)
			.attr('src')
			.match(/mega-menu-ajax\.min\.js\?([^"']+)=([^"']+)/);
		if (match) {
			qs[match[1]] = match[2];
		}
	});

	jQuery.ajax({
		async: true,
		url: home + url + '?' + jQuery.param(qs),
		dataType: 'html',
		success: function (html) {
			// make sure CF7 forms have the current URL instead of the AJAX menu url
			html = html.replace(
				url,
				window.location.pathname + window.location.search
			);
			$megaMenu.replaceWith(
				jQuery(jQuery.parseHTML(html)).find(SELECTORS.megaMenu)
			);

			// show menu after loading
			const $loaded = $item.find(SELECTORS.megaMenu);
			if (!$loaded.is(':empty')) {
				showMegaMenu($loaded);
			}
		},
	});
}

function initContainers() {
	jQuery(SELECTORS.container).each(function () {
		const $container = jQuery(this);
		const themeLocation =
			$container.data('theme_location') || $container.data('menu');
		const home = $container.data('home');

		$container.find(SELECTORS.depthZero).each(function () {
			const el = this;

			el.addEventListener('mouseenter', function () {
				clearTimeout(el._megaTimeout);

				if (
					jQuery(el).children(SELECTORS.megaMenu).is(':visible') ===
					false
				) {
					jQuery(`${SELECTORS.depthZero} > ${SELECTORS.megaMenu}`)
						.stop(true, true)
						.hide();
					el._megaTimeout = setTimeout(() => {
						if (isMobileView($container)) {
							return;
						}

						loadMenuAjax(jQuery(el), themeLocation, home);

						const $megaMenu = jQuery(el).children(
							SELECTORS.megaMenu
						);
						if (!$megaMenu.is(':empty')) {
							showMegaMenu($megaMenu);
						}
					}, 0);
				}
			});

			el.addEventListener('mouseleave', function () {
				clearTimeout(el._megaTimeout);
				el._megaTimeout = setTimeout(() => {
					if (isMobileView($container)) {
						return;
					}

					const $megaMenu = jQuery(el).children(SELECTORS.megaMenu);
					if (!$megaMenu.is(':empty')) {
						hideMegaMenu($megaMenu);
					}
				}, 250);
			});
		});

		// mobile toggle
		$container.siblings(SELECTORS.mobileToggle).on('click', function (e) {
			e.preventDefault();

			if (DropdownSpeed.instant_dropdown) {
				jQuery(this).toggleClass('open').next('ul').toggle();
			} else {
				jQuery(this).toggleClass('open').next('ul').slideToggle();
			}

			return false;
		});
	});
}

function initResizeHandler() {
	let wasMobile = isMobileView();

	jQuery(window).on('resize', function () {
		const isMobile = isMobileView();
		const hasCustomMobile = jQuery(SELECTORS.mobileMenu).length > 0;

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

		jQuery(SELECTORS.mobileToggle)
			.removeClass('open')
			.next('ul')
			.removeAttr('style');
	});
}

function isMobileView($container) {
	const toggle = ($container || jQuery(document)).find(
		SELECTORS.mobileToggle
	);
	return toggle.length > 0 && toggle.is(':visible');
}

// Initialize on DOM ready
jQuery(function () {
	initContainers();
	initResizeHandler();
});
