(function ($) {
	"use strict";

	function getCookie(name) {
		var match = document.cookie.match(new RegExp('(?:^|; )' + name + '=([^;]*)'));
		return match ? decodeURIComponent(match[1]) : null;
	}

	function setCookie(name, value, days) {
		var expires = new Date();
		expires.setTime(expires.getTime() + (days * 24 * 60 * 60 * 1000));
		document.cookie = name + '=' + encodeURIComponent(value) + ';expires=' + expires.toUTCString() + ';path=/;SameSite=Lax';
	}

	function clearCookie(name) {
		document.cookie = name + '=;expires=Thu, 01 Jan 1970 00:00:00 UTC;path=/;SameSite=Lax';
	}

	// Exposed so mpwpb_registration.js can check before writing/reading the
	// "remember my info" mpwpb_customer_info cookie on the booking form --
	// Reject (or no choice made yet) means that convenience feature stays off.
	window.mpwpbGdprConsentAccepted = function () {
		return getCookie('mpwpb_cookie_consent') === 'accepted';
	};

	$(function () {
		if (typeof mpwpb_gdpr === 'undefined') {
			return;
		}
		var $banner = $('#mpwpb_gdpr_banner');
		if (!$banner.length || getCookie('mpwpb_cookie_consent') !== null) {
			return;
		}
		$('#mpwpb_gdpr_banner_msg').text(mpwpb_gdpr.message);
		$('#mpwpb_gdpr_accept').text(mpwpb_gdpr.accept_text);
		$('#mpwpb_gdpr_reject').text(mpwpb_gdpr.reject_text);
		$banner.show();

		$('#mpwpb_gdpr_accept').on('click', function () {
			setCookie('mpwpb_cookie_consent', 'accepted', 180);
			$banner.hide();
		});
		$('#mpwpb_gdpr_reject').on('click', function () {
			setCookie('mpwpb_cookie_consent', 'rejected', 180);
			// Clear anything saved from a prior Accept so nothing lingers
			// client-side once the visitor rejects.
			clearCookie('mpwpb_customer_info');
			$banner.hide();
		});
	});
}(jQuery));
