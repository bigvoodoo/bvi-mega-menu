jQuery(function($) {
	var mobile = $('.mobile-toggle:visible').length == 1;
	
	$(window).resize(function() {
		if($('.bvi-mega-menu-custom-mobile-menu').length === 0) {
			if($('.mobile-toggle:visible').length && !mobile) {
				$('.bvi-mega-menu-container').hide();
				$('.bvi-mega-menu-container .mega-menu').hide();
				$('.bvi-mega-menu-custom-mobile-menu').hide();
				mobile = true;
			} else if(!$('.mobile-toggle:visible').length && mobile) {
				$('.bvi-mega-menu-container').show();
				$('.bvi-mega-menu-container').removeAttr('style');
				mobile = false;
			}
		} else {
			if($('.mobile-toggle:visible').length && !mobile) {
				$('.bvi-mega-menu-custom-mobile-menu').hide();
				$('.bvi-mega-menu-container').hide();
				$('.bvi-mega-menu-container .mega-menu').hide();
				mobile = true;
			} else if(!$('.mobile-toggle:visible').length && mobile) {
				$('.bvi-mega-menu-custom-mobile-menu').show();
				$('.bvi-mega-menu-custom-mobile-menu').removeAttr('style');
				mobile = false;
			}
		}

		$('.mobile-toggle').removeClass('open').next('ul').removeAttr('style');
	});

	// mobile toggle functionality
	$('.mobile-toggle').click(function(event){
		event.preventDefault();
		
		$(this).toggleClass('open').next('ul').slideToggle();
	});

	$('.menu-item-depth-0').on('mouseover', function() {
		$('.menu-item-depth-0').children('.mega-menu').stop(true, true).hide();
		$(this).children('.mega-menu').has('ul').slideDown();
	}).on('mouseleave', function() {
		$('.menu-item-depth-0').children('.mega-menu').stop(true, true).hide();
		$(this).children('.mega-menu').has('ul').slideUp();
	});
});
