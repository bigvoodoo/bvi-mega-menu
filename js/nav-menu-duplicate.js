(function($) {
	// TODO: Add comments!
	// FIXME: finish me!
	var add_duplicate_button = function() {
		if($(this).find('.duplicate').size() == 0) {
			// add the button!
			var button = $('<input type="button" name="duplicate" class="button button-primary menu-save duplicate" value="Duplicate" title="This will duplicate all descendants as well" />')
				.on('click', function() {
					wpNavMenu.registerChange();

					var actions_el = $(this).parents('.menu-item-actions');

					var items = actions_el.parents('.menu-item').add(find_nav_menu_children(actions_el.siblings('input.menu-item-data-db-id').val()));

					console.log(items);

					// var params = {
					// 	'action': 'nav_menu_duplicate_item',
					// 	'menu': $('#menu').val(),
					// 	'menu-settings-column-nonce': $('#menu-settings-column-nonce').val(),
					// 	'parent_id': actions_el.siblings('.menu-item-data-parent-id').val(),
					// 	'items': items,
					// 	'depth' $(this).parents('.menu-item').attr('class').replace(/.*menu-item-depth-([0-9]+).*/, "$1")
					// };

					// $.get(ajaxurl, params, function(menuMarkup) {
					// 	$(menuMarkup).hideAdvancedMenuItemFields().insertAfter(button.parents('.menu-item'));
					// });

					return false;
				})
				.appendTo($(this).find('.menu-item-actions'));
		}
		return false;
	};

	var find_nav_menu_children = function(parent_id) {
		var children = $('input.menu-item-data-parent-id[value=' + parent_id + ']').parents('.menu-item');
		children.each(function() {
			children = children.add(find_nav_menu_children($(this).find('input.menu-item-data-db-id').val()));
		});

		return children;
	};

	$('.menu-item').each(add_duplicate_button);
	$('#menu-to-edit').on('mouseenter', '.menu-item', add_duplicate_button);
})(jQuery);
