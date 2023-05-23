jQuery(function($) {
	var mobile = $('.mobile-toggle').is(':visible');
	$('.bvi-mega-menu-container').each(function() {
		var container = $(this),
			theme_location = container.data('theme_location'),
			home = container.data('home');

		if(!theme_location) {
			theme_location = container.data('menu');
		}

		container.find('.menu-item-depth-0').mouseenter(function() {
			clearTimeout(this.timeout);
			if($(this).children('.mega-menu').is(':visible') == false) {
				$('.menu-item-depth-0').children('.mega-menu').stop(true, true).hide();
				this.timeout = setTimeout($.proxy(over, this), 0);
			}
		}).mouseleave(function() {
			clearTimeout(this.timeout);
			this.timeout = setTimeout($.proxy(out, this), 250);
		});

		var over = function() {
			if(container.siblings('.mobile-toggle').is(':visible')) {
				return true;
			}

			ajax_load_menu.call($(this), theme_location, home);

			if(!$(this).children('.mega-menu:empty').length) {
				if(parseInt(DropdownSpeed.instant_dropdown) === 0){
					$(this).children('.mega-menu').stop(true, true).slideDown(300);
				} else {
					$(this).children('.mega-menu').stop(true, true).show();
				}
			}
		};

		var out = function() {
			if(container.siblings('.mobile-toggle').is(':visible')) {
				return true;
			}

			if(!$(this).children('.mega-menu:empty').length) {
				if(parseInt(DropdownSpeed.instant_dropdown) === 0){
					$(this).children('.mega-menu').stop(true, true).slideUp(300).fadeOut();
				} else {
					$(this).children('.mega-menu').stop(true, true).hide();
				}
			}
		};

		container.siblings('.mobile-toggle').click(function(e) {
			e.preventDefault();

			if(parseInt(DropdownSpeed.instant_dropdown) === 0){
				$(this).toggleClass('open').next('ul').slideToggle();
			} else {
				$(this).toggleClass('open').next('ul').toggle();
			}

			return false;
		});
	});

	$(window).resize(function() {
		if($('.bvi-mega-menu-custom-mobile-menu').length === 0) {
			if($('.mobile-toggle').is(':visible') && !mobile) {
				$('.bvi-mega-menu-container').hide();
				$('.bvi-mega-menu-container .mega-menu').hide();
				$('.bvi-mega-menu-custom-mobile-menu').hide();
				mobile = true;
			} else if($('.mobile-toggle').is(':visible') == false && mobile) {
				$('.bvi-mega-menu-container').show();
				$('.bvi-mega-menu-container').removeAttr('style');
				mobile = false;
			}
		} else {
			if($('.mobile-toggle').is(':visible') && !mobile) {
				$('.bvi-mega-menu-custom-mobile-menu').hide();
				$('.bvi-mega-menu-container').hide();
				$('.bvi-mega-menu-container .mega-menu').hide();
				mobile = true;
			} else if($('.mobile-toggle').is(':visible') == false && mobile) {
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
				var match = $(this).attr('src').match(/mega-menu-ajax.js\?([^"']+)=([^"']+)/);
				if(match)
					qs[match[1]] = match[2];
			});

			$.ajax({
				async: true,
				url: home + url + '?' + $.param(qs),
				dataType: 'html',
				success: $.proxy(function(html) {
					// make sure CF7 forms have the current URL instead of the AJAX menu url
					html = html.replace(url, window.location.pathname + window.location.search);
					// insert the HTML
					this.find('.mega-menu').replaceWith($($.parseHTML(html)).find('.mega-menu'));
					// to prevent it not loading on the first go-around, add an additional check
					// to load the menu once the ajax is completed
					if(!this.find('.mega-menu:empty').length) {
						if(parseInt(DropdownSpeed.instant_dropdown) === 0){
							this.find('.mega-menu').stop(true, true).slideDown(300);
						} else {
							this.find('.mega-menu').stop(true, true).show();
						}
					}
				}, this)
			});
		}
	};
});
