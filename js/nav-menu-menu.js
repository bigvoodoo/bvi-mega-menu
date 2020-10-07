(function($) {
	// TODO: documentation
	$('form#update-nav-menu').on('submit', function() {
		$(this).find('.menu-item-menu').each(function() {
			var data = {
				menu: $(this).find('.edit-menu-item-menu option:selected').val(),
				title: $(this).find('.edit-menu-item-title').val()
			};
			$(this).find('.edit-menu-item-title-hidden').val(JSON.stringify(data));
		});
	});

	/**
	 * Formats menu boxes properly
	 * TODO: documentation
	 */
	var format_menu_fields = function() {
		// set the type in the top-right of the box
		$(this).find('.item-type').html('Menu');

		var title_field = $(this).find('.edit-menu-item-title');
		var data = $.parseJSON(title_field.val());
		var hidden_title = $('<input type="hidden" name="' + title_field.attr('name') + '" class="edit-menu-item-title-hidden" value="" />').val(title_field.val());
		var id = $(this).attr('id').replace(/[^0-9]*/g, '');

		title_field.val(data.title);
		title_field.attr('name', 'fake-title[' + id + ']');

		$(this).find('.menu-item-title').html(data.title + ' (<em>' + $.trim($('#menu-menu-item-menu').find('option[value="' + data.menu + '"]').html()) + '</em>)');

		var select = $('#menu-menu-item-menu').clone();
		select.addClass('widefat edit-menu-item-menu');
		select.attr('name', 'fake-menu[' + id + ']');
		select.attr('id', 'edit-menu-item-menu[' + id + ']');
		select.find('option[value="' + data.menu + '"]').attr('selected', 'selected');
		var select_field = $('<p class="field-menu description description-wide">\
						<label for="edit-menu-item-menu-' + id + '">\
							Menu<br />\
						</label>\
					</p>');
		select_field.find('label').append(select);

		$(this).find('.menu-item-settings')
			.prepend(select_field)
			.prepend(hidden_title);

		$(this).find('.item-cancel, .meta-sep').hide();
	};

	// add a click handler for the "Add to Menu" button for menu
	// TODO: documentation
	$('#menu-settings-column').on('click', function(e) {
		if ($(e.target).hasClass('submit-add-menu-to-menu')) {
			// user did not select a menu!
			if($('#menu-menu-item-menu option:selected').val() == 0) {
				return false;
			}

			wpNavMenu.registerChange();

			// Show the ajax spinner
			$('.menudiv .spinner').show();

			var menu = $('#menu-menu-item-menu option:selected').val();
			var title = $('#menu-menu-item-title').val().replace('(optional)', '');

			var data = {
				menu: menu,
				title: title
			};

			// send the new menu item over to wpNavMenu and have it submit to
			// the server
			wpNavMenu.addItemToMenu({
				'-1': {
					'menu-item-type': 'menu',
					'menu-item-object-id': 'menu',
					'menu-item-object': 'menu',
					'menu-item-title': JSON.stringify(data)
				}
			}, wpNavMenu.addMenuItemToBottom, function() {
				// Hide the ajax spinner
				$('.menudiv .spinner').hide();

				// Set menu form back to default
				$('#menu-menu-item-menu option:eq(1)').attr('selected', 'selected').blur();
				$('#menu-menu-item-title').val('').blur();

				// format the newly added menu box
				format_menu_fields.call($('.menu-item-menu').last());
			});

			// we caught the click, so make sure no one else tries it
			return false;
		}
	});

	// format the menu fields on page load
	$('.menu-item-menu').each(format_menu_fields);
})(jQuery);
