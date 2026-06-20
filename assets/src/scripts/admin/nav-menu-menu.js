/**
 * BVI Mega Menu - Admin Menu item handler.
 *
 * Formats embedded menu items in the menu editor.
 */

/* global wpNavMenu */

(function ($) {
  // save menu data as JSON before form submit
  $('form#update-nav-menu').on('submit', function () {
    $(this)
      .find('.menu-item-menu')
      .each(function () {
        const data = {
          menu: $(this).find('.edit-menu-item-menu option:selected').val(),
          title: $(this).find('.edit-menu-item-title').val(),
        };
        $(this).find('.edit-menu-item-title-hidden').val(JSON.stringify(data));
      });
  });

  /**
   * Formats menu boxes properly in the menu editor.
   */
  const formatMenuFields = function () {
    $(this).find('.item-type').html('Menu');

    const titleField = $(this).find('.edit-menu-item-title');
    const data = $.parseJSON(titleField.val());
    const id = $(this)
      .attr('id')
      .replace(/[^0-9]*/g, '');

    const hiddenTitle = $(
      '<input type="hidden" name="' + titleField.attr('name') + '" class="edit-menu-item-title-hidden" value="" />'
    ).val(titleField.val());

    titleField.val(data.title);
    titleField.attr('name', 'fake-title[' + id + ']');

    $(this)
      .find('.menu-item-title')
      .html(
        data.title +
          ' (<em>' +
          $.trim(
            $('#menu-menu-item-menu')
              .find('option[value="' + data.menu + '"]')
              .html()
          ) +
          '</em>)'
      );

    const select = $('#menu-menu-item-menu').clone();
    select.addClass('widefat edit-menu-item-menu');
    select.attr('name', 'fake-menu[' + id + ']');
    select.attr('id', 'edit-menu-item-menu[' + id + ']');
    select.find('option[value="' + data.menu + '"]').attr('selected', 'selected');

    const selectField = $(
      '<p class="field-menu description description-wide">' +
        '<label for="edit-menu-item-menu-' +
        id +
        '">Menu<br /></label></p>'
    );
    selectField.find('label').append(select);

    $(this).find('.menu-item-settings').prepend(selectField).prepend(hiddenTitle);
    $(this).find('.item-cancel, .meta-sep').hide();
  };

  // click handler for "Add to Menu" button
  $('#menu-settings-column').on('click', function (e) {
    if ($(e.target).hasClass('submit-add-menu-to-menu')) {
      if ($('#menu-menu-item-menu option:selected').val() === '0') {
        return false;
      }

      wpNavMenu.registerChange();
      $('.menudiv .spinner').show();

      const menu = $('#menu-menu-item-menu option:selected').val();
      const title = $('#menu-menu-item-title').val().replace('(optional)', '');

      wpNavMenu.addItemToMenu(
        {
          '-1': {
            'menu-item-type': 'menu',
            'menu-item-object-id': 'menu',
            'menu-item-object': 'menu',
            'menu-item-title': JSON.stringify({ menu, title }),
          },
        },
        wpNavMenu.addMenuItemToBottom,
        function () {
          $('.menudiv .spinner').hide();
          $('#menu-menu-item-menu option:eq(1)').attr('selected', 'selected').blur();
          $('#menu-menu-item-title').val('').blur();
          formatMenuFields.call($('.menu-item-menu').last());
        }
      );

      return false;
    }
  });

  // format menu fields on page load
  $('.menu-item-menu').each(formatMenuFields);
})(jQuery);
