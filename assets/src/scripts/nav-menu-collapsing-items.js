/* global wpNavMenu */

(function ($) {
  const getId = function (el) {
    return $(el)
      .attr('id')
      .replace(/[^0-9]+/g, '');
  };

  const toggleDescendants = function (el, func) {
    const id = getId(el);

    $('.menu-item-data-parent-id[value="' + id + '"]')
      .parents('.menu-item')
      .each(function () {
        $(this)[func]();
        toggleDescendants($(this), func);

        if (func === 'slideDown') {
          $(this).find('.collapse-expand').addClass('collapse').removeClass('expand').html('<em>-</em>');
        }
      });
  };

  const addCollapseButton = function () {
    $('<a/>')
      .attr('href', '#')
      .attr('id', getId(this))
      .addClass('collapse collapse-expand')
      .html('<em>-</em>')
      .on('click', function (e) {
        e.preventDefault();

        if ($(this).hasClass('collapse')) {
          toggleDescendants($(this), 'slideUp');
          $(this).addClass('expand').removeClass('collapse').html('<em>+</em>');
        } else {
          toggleDescendants($(this), 'slideDown');
          $(this).addClass('collapse').removeClass('expand').html('<em>-</em>');
        }
      })
      .appendTo(this);

    return $(this);
  };

  const original = {
    addMenuItemToBottom: wpNavMenu.addMenuItemToBottom,
    addMenuItemToTop: wpNavMenu.addMenuItemToTop,
  };

  wpNavMenu.addMenuItemToBottom = function (menuMarkup, req) {
    addCollapseButton.call(menuMarkup);
    original.addMenuItemToBottom(menuMarkup, req);
  };

  wpNavMenu.addMenuItemToTop = function (menuMarkup, req) {
    addCollapseButton.call(menuMarkup);
    original.addMenuItemToTop(menuMarkup, req);
  };

  $('.menu-item').each(addCollapseButton);
})(jQuery);
