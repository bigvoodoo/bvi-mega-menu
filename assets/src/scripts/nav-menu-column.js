/* global wpNavMenu */

(function ($) {
  $('form#update-nav-menu').on('submit', function () {
    $(this)
      .find('.menu-item-column')
      .each(function () {
        const data = {
          url: $(this).find('.edit-menu-item-url').val(),
          title: $(this).find('.edit-menu-item-title').val(),
        };
        $(this).find('.edit-menu-item-title-hidden').val(JSON.stringify(data));
      });
  });

  const formatColumnFields = function () {
    $(this).find('.item-type').html('Column/Section');

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
      .html($.trim(data.title).length ? data.title : '&nbsp;');

    const urlField =
      '<p class="field-url description description-wide">' +
      '<label for="edit-menu-item-url-' +
      id +
      '">URL<br />' +
      '<input type="text" id="edit-menu-item-url-' +
      id +
      '" class="widefat code edit-menu-item-url" name="fake-url[' +
      id +
      ']" value="' +
      data.url +
      '" />' +
      '</label></p>';

    $(this).find('.menu-item-settings').prepend(urlField).prepend(hiddenTitle);
    $(this).find('.item-cancel, .meta-sep').hide();
  };

  $('#menu-settings-column').on('click', function (e) {
    if ($(e.target).hasClass('submit-add-column-to-menu')) {
      wpNavMenu.registerChange();
      $('.columndiv .spinner').show();

      const url = $('#column-menu-item-url').val().replace('(optional)', '');
      const title = $('#column-menu-item-title').val().replace('(optional)', '');

      wpNavMenu.addItemToMenu(
        {
          '-1': {
            'menu-item-type': 'column',
            'menu-item-object-id': 'column',
            'menu-item-object': 'column',
            'menu-item-title': JSON.stringify({ url, title }),
          },
        },
        wpNavMenu.addMenuItemToBottom,
        function () {
          $('.columndiv .spinner').hide();
          $('#column-menu-item-url').val('').blur();
          $('#column-menu-item-title').val('').blur();
          formatColumnFields.call($('.menu-item-column').last());
        }
      );

      return false;
    }
  });

  $('.menu-item-column').each(formatColumnFields);
})(jQuery);
