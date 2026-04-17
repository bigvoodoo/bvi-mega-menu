/**
 * BVI Mega Menu - Admin Shortcode/HTML item handler.
 *
 * Converts the title input to a textarea for shortcode content.
 */

/* global wpNavMenu */

(function ($) {
	/**
	 * Formats shortcode boxes in the menu editor.
	 */
	const formatShortcodeFields = function () {
		$(this).find('.item-type').html('Shortcode/HTML');

		const title = $(this).find('.menu-item-title');
		title.html(title.html().substr(0, 50) + ' ...');

		const fields = $(this).find('p.description');

		// turn the first field ("title") into a textarea
		fields
			.eq(0)
			.find('input')
			.each(function () {
				const textArea = $('<textarea></textarea>')
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

	// click handler for "Add to Menu" button
	$('#menu-settings-column').on('click', function (e) {
		if ($(e.target).hasClass('submit-add-shortcode-to-menu')) {
			wpNavMenu.registerChange();
			$('.shortcodediv .spinner').show();

			wpNavMenu.addItemToMenu(
				{
					'-1': {
						'menu-item-type': 'shortcode',
						'menu-item-object-id': 'shortcode',
						'menu-item-object': 'shortcode',
						'menu-item-title': $('#shortcode-menu-item').val(),
					},
				},
				wpNavMenu.addMenuItemToBottom,
				function () {
					$('.shortcodediv .spinner').hide();
					$('#shortcode-menu-item').val('').blur();
					formatShortcodeFields.call(
						$('.menu-item-shortcode').last()
					);
				}
			);

			return false;
		}
	});

	// format shortcode fields on page load
	$('.menu-item-shortcode').each(formatShortcodeFields);
})(jQuery);
