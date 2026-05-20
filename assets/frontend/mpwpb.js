//*******owlCarousel***********//
function mpwpb_active_carousel(target,qty) {
	target.each(function () {
		let current_target = jQuery(this);
		current_target.find(".mpwpb-owl-carousel").owlCarousel({
			loop: false,
			margin: 2,
			nav: true,
			responsiveClass:true,
			responsive:{
				0:{
					items:2,
					nav:true
				},
				600:{
					items:2,
					nav:false
				},
				1000:{
					items: qty,
					nav:true,
					loop:false
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

function remove_parent_classes($){
	$(document).ready(function() {
		$('.mpwpb_registration').each(function() {
		var $this = $(this);
		$this.parent().removeClass();
		$this.parent().parent().removeClass();
		});
	});
}


(function ($) {
	"use strict";
	mpwpb_active_carousel($('.mpwpb_date_carousel'),5);
	remove_parent_classes($);
}(jQuery));

// ***********static template********************
