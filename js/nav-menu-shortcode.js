(function($) {
	/**
	 * Formats shortcode boxes properly, which basically means hiding
	 * everything but the "title" and turning the "title" into a textarea
	 */
	var format_shortcode_fields = function() {
		// set the type in the top-right of the box
		$(this).find('.item-type').html('Shortcode/HTML');

		var title = $(this).find('.menu-item-title');
		title.html(title.html().substr(0, 50) + " ...");

		// grab all the p.description elements
		var fields = $(this).find('p.description');

		// turn the first field ("title") into a textarea
		fields.eq(0).find('input').each(function() {
			var textArea = $('<textarea></textarea>')
				.val($(this).val())
				.attr('id', $(this).attr('id'))
				.attr('name', $(this).attr('name'))
				.addClass($(this).attr('class'));
			$(this).parent().html(textArea);
		});

		// hide all other fields
		fields.slice(1).hide();
		$(this).find('.item-cancel, .meta-sep').hide();
	};

	// add a click handler for the "Add to Menu" button for shortcode
	$('#menu-settings-column').on('click', function(e) {
		if ($(e.target).hasClass('submit-add-shortcode-to-menu')) {
			wpNavMenu.registerChange();

			// Show the ajax spinner
			$('.shortcodediv .spinner').show();

			// send the new menu item over to wpNavMenu and have it submit to
			// the server
			wpNavMenu.addItemToMenu({
				'-1': {
					'menu-item-type': 'shortcode',
					'menu-item-object-id': 'shortcode',
					'menu-item-object': 'shortcode',
					'menu-item-title': $('#shortcode-menu-item').val()
				}
			}, wpNavMenu.addMenuItemToBottom, function() {
				// Hide the ajax spinner
				$('.shortcodediv .spinner').hide();

				// Set shortcode form back to default
				$('#shortcode-menu-item').val('').blur();

				// format the newly added shortcode box
				format_shortcode_fields.call($('.menu-item-shortcode').last());
			});

			// we caught the click, so make sure no one else tries it
			return false;
		}
	});

	// format the shortcode fields on page load
	$('.menu-item-shortcode').each(format_shortcode_fields);
})(jQuery);
