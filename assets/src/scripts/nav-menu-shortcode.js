/* global wpNavMenu */

(function ($) {
  const formatShortcodeFields = function () {
    $(this).find('.item-type').html('Shortcode/HTML');

    const title = $(this).find('.menu-item-title');
    title.html(title.html().substr(0, 50) + ' ...');

    const fields = $(this).find('p.description');

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

    fields.slice(1).hide();
    $(this).find('.item-cancel, .meta-sep').hide();
  };

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
          formatShortcodeFields.call($('.menu-item-shortcode').last());
        }
      );

      return false;
    }
  });

  $('.menu-item-shortcode').each(formatShortcodeFields);
})(jQuery);
