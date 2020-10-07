(function($) {
	// TODO: Add comments!
	var add_descendants_button = function() {
		if($(this).find('.add_descendants').size() == 0) {
			// add the button!
			var button = $('<input type="button" name="add_descendants" class="button button-primary menu-save add_descendants" value="Add Descendants" title="Add descendants of this page in the hierarchy." />')
				.on('click', function() {
					wpNavMenu.registerChange();

					var actions_el = $(this).parents('.menu-item-actions');

					var params = {
						'action': 'nav_menu_get_post_descendants',
						'menu': $('#menu').val(),
						'menu-settings-column-nonce': $('#menu-settings-column-nonce').val(),
						'db_id': actions_el.siblings('.menu-item-data-db-id').val(),
						'post_id': actions_el.siblings('.menu-item-data-object-id').val(),
						'depth': $(this).parents('.menu-item').attr('class').replace(/.*menu-item-depth-([0-9]+).*/, "$1")
					};

					// wpNavMenu doesn't have an "addMenuItemAtPosition"-type function, so we have to do this manually
					$.get(ajaxurl, params, function(menuMarkup) {
						$(menuMarkup).hideAdvancedMenuItemFields().insertAfter(button.parents('.menu-item'));
					});

					return false;
				})
				.appendTo($(this).find('.menu-item-actions'));
		}
		return false;
	};

	$('.menu-item-page').each(add_descendants_button);
	$('#menu-to-edit').on('mouseenter', '.menu-item-page', add_descendants_button);
})(jQuery);
