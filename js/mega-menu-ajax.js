jQuery(function($) {
	var mobile = $('.mobile-toggle:visible').length == 1;
	$('.bvi-mega-menu-container').each(function() {
		var container = $(this),
			theme_location = container.data('theme_location'),
			home = container.data('home');

		container.find('.menu-item-depth-0').on('mouseover', function() {
			clearTimeout(this.timeout);
			if(!$(this).children('.mega-menu:visible').length) {
				$('.menu-item-depth-0').children('.mega-menu').stop(true, true).hide();
				this.timeout = setTimeout($.proxy(over, this), 0);
			}
		}).on('mouseleave', function() {
			clearTimeout(this.timeout);
			this.timeout = setTimeout($.proxy(out, this), 250);
		});

		var over = function() {
			if(container.siblings('.mobile-toggle').is(':visible').length) {
				return true;
			}

			ajax_load_menu.call($(this), theme_location, home);

			if(!$(this).children('.mega-menu:empty').length) {
				$(this).children('.mega-menu').stop(true, true).slideDown(300);
			}
		};

		var out = function() {
			if(container.siblings('.mobile-toggle').is(':visible').length) {
				return true;
			}

			if(!$(this).children('.mega-menu:empty').length) {
				$(this).children('.mega-menu').stop(true, true).slideUp(300).fadeOut();
			}
		};

		container.siblings('.mobile-toggle').click(function(e) {
			e.preventDefault();

			$(this).toggleClass('open').next('ul').slideToggle();

			return false;
		});
	});

	$(window).resize(function() {
		if($('.bvi-mega-menu-custom-mobile-menu').length === 0) {
			if($('.mobile-toggle:visible').length && !mobile) {
				$('.bvi-mega-menu-container').hide();
				$('.bvi-mega-menu-container .mega-menu').hide();
				$('.bvi-mega-menu-custom-mobile-menu').hide();
				mobile = true;
			} else if(!$('.mobile-toggle:visible').length && mobile) {
				$('.bvi-mega-menu-container').show();
				$('.bvi-mega-menu-container').removeAttr('style');
				mobile = false;
			}
		} else {
			if($('.mobile-toggle:visible').length && !mobile) {
				$('.bvi-mega-menu-custom-mobile-menu').hide();
				$('.bvi-mega-menu-container').hide();
				$('.bvi-mega-menu-container .mega-menu').hide();
				mobile = true;
			} else if(!$('.mobile-toggle:visible').length && mobile) {
				$('.bvi-mega-menu-custom-mobile-menu').show();
				$('.bvi-mega-menu-custom-mobile-menu').removeAttr('style');
				mobile = false;
			}
		}

		$('.mobile-toggle').removeClass('open').next('ul').removeAttr('style');
	});

	var ajax_load_menu = function(theme_location, home) {
		if(this.find('.mega-menu').is(':empty')) {
			var id = this.attr('id').replace(/[^0-9]+/, ''),
				url = '/ajax_mega_menu/' + encodeURIComponent(theme_location) + '/' + id,
				qs = $.extend({}, this.parents('ul.bvi-mega-menu-container').data());

			$('script[src]').each(function() {
				var match = $(this).attr('src').match(/mega-menu.js\?([^"']+)=([^"']+)/);
				if(match)
					qs[match[1]] = match[2];
			});

			$.ajax({
				async: false,
				url: home + url + '?' + $.param(qs),
				dataType: 'html',
				success: $.proxy(function(html) {
					// make sure CF7 forms have the current URL instead of the AJAX menu url
					html = html.replace(url, window.location.pathname + window.location.search);
					// insert the HTML
					this.find('.mega-menu').replaceWith($($.parseHTML(html)).find('.mega-menu'));
				}, this)
			});
		}
	};
});
