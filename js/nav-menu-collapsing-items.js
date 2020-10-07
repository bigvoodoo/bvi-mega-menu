(function($) {
	var add_collapse_button = function() {
		$('<a/>')
			.attr('href', '#')
			.attr('id', get_id(this))
			.addClass('collapse collapse-expand')
			.html('<em>-</em>')
			.on('click', function(e) {
				e.preventDefault();

				if($(this).hasClass('collapse')) {
					toggle_descendents($(this), 'slideUp');
					$(this)
						.addClass('expand')
						.removeClass('collapse')
						.html('<em>+</em>');
				} else {
					toggle_descendents($(this), 'slideDown');
					$(this)
						.addClass('collapse')
						.removeClass('expand')
						.html('<em>-</em>');
				}
			})
			.appendTo(this);
		return $(this);
	};

	var toggle_descendents = function(el, func) {
		var id = get_id(el);
		$('.menu-item-data-parent-id[value="' + id + '"]').parents('.menu-item').each(function() {
			$(this)[func]();
			toggle_descendents($(this), func);
			if(func == 'slideDown') {
				$(this).find('.collapse-expand')
					.addClass('collapse')
					.removeClass('expand')
					.html('<em>-</em>');
			}
		});
	};

	var get_id = function(el) {
		return $(el).attr('id').replace(/[^0-9]+/g, '');
	}

	var old = {
		addMenuItemToBottom: wpNavMenu.addMenuItemToBottom,
		addMenuItemToTop: wpNavMenu.addMenuItemToTop
	};

	wpNavMenu.addMenuItemToBottom = function(menuMarkup, req) {
		add_collapse_button.call(menuMarkup);
		old.addMenuItemToBottom(menuMarkup, req);
	};

	wpNavMenu.addMenuItemToTop = function(menuMarkup, req) {
		add_collapse_button.call(menuMarkup);
		old.addMenuItemToTop(menuMarkup, req);
	};

	$('.menu-item').each(add_collapse_button);
})(jQuery);
