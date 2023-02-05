//*******owlCarousel***********//
function mpwpb_active_carousel(target) {
	target.each(function () {
		let current_target = jQuery(this);
		current_target.find(".owl-carousel").owlCarousel({
			loop: false,
			margin: 2,
			nav: true,
			responsive: {
				0: {
					items: 7
				}
			}
		});
		current_target.find(".next").click(function () {
			current_target.find('.owl-next').trigger('click');
		});
		current_target.find(".prev").click(function () {
			current_target.find('.owl-prev').trigger('click');
		});
	});
}

(function ($) {
	"use strict";
	mpwpb_active_carousel($('.mpwpb_date_carousel'));
}(jQuery));