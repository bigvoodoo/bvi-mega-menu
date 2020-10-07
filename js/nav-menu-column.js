(function($) {
	// TODO: documentation
	$('form#update-nav-menu').on('submit', function() {
		$(this).find('.menu-item-column').each(function() {
			var data = {
				url: $(this).find('.edit-menu-item-url').val(),
				title: $(this).find('.edit-menu-item-title').val()
			};
			$(this).find('.edit-menu-item-title-hidden').val(JSON.stringify(data));
		});
	});

	/**
	 * Formats column boxes properly
	 * TODO: documentation
	 */
	var format_column_fields = function() {
		// set the type in the top-right of the box
		$(this).find('.item-type').html('Column/Section');

		var title_field = $(this).find('.edit-menu-item-title');
		var data = $.parseJSON(title_field.val());
		var hidden_title = $('<input type="hidden" name="' + title_field.attr('name') + '" class="edit-menu-item-title-hidden" value="" />').val(title_field.val());
		var id = $(this).attr('id').replace(/[^0-9]*/g, '');

		title_field.val(data.title);
		title_field.attr('name', 'fake-title[' + id + ']');

		$(this).find('.menu-item-title').html($.trim(data.title).length ? data.title : '&nbsp;');

		var url_field = '<p class="field-url description description-wide">\
						<label for="edit-menu-item-url-' + id + '">\
							URL<br />\
							<input type="text" id="edit-menu-item-url-' + id + '" class="widefat code edit-menu-item-url" name="fake-url[' + id + ']" value="' + data.url + '" />\
						</label>\
					</p>';

		$(this).find('.menu-item-settings')
			.prepend(url_field)
			.prepend(hidden_title);

		$(this).find('.item-cancel, .meta-sep').hide();
	};

	// add a click handler for the "Add to Menu" button for column
	// TODO: documentation
	$('#menu-settings-column').on('click', function(e) {
		if ($(e.target).hasClass('submit-add-column-to-menu')) {
			wpNavMenu.registerChange();

			// Show the ajax spinner
			$('.columndiv .spinner').show();

			var url = $('#column-menu-item-url').val().replace('(optional)', '');
			var title = $('#column-menu-item-title').val().replace('(optional)', '');

			var data = {
				url: url,
				title: title
			};

			// send the new menu item over to wpNavMenu and have it submit to
			// the server
			wpNavMenu.addItemToMenu({
				'-1': {
					'menu-item-type': 'column',
					'menu-item-object-id': 'column',
					'menu-item-object': 'column',
					'menu-item-title': JSON.stringify(data)
				}
			}, wpNavMenu.addMenuItemToBottom, function() {
				// Hide the ajax spinner
				$('.columndiv .spinner').hide();

				// Set column form back to default
				$('#column-menu-item-url').val('').blur();
				$('#column-menu-item-title').val('').blur();

				// format the newly added column box
				format_column_fields.call($('.menu-item-column').last());
			});

			// we caught the click, so make sure no one else tries it
			return false;
		}
	});

	// format the column fields on page load
	$('.menu-item-column').each(format_column_fields);
})(jQuery);
