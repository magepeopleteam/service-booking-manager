(function ($) {
    "use strict";

	var allowDefaultBehavior = false;

	function reset_element(element)
	{
		element.find("label").removeClass("danger-text");
		element.find("input").removeClass("danger");
		element.find("span.danger-text").removeClass("danger-text").remove();
	}

	function set_element(element, spanText)
	{
		var text_of_span = '';
		element.find("label").addClass("danger-text");
		element.find("input").addClass("danger");
		if(spanText == " is required.")
		{
			text_of_span = element.find('label').text() + spanText;
		}
		else if(spanText == " is not valid phone number !")
		{
			text_of_span = 'Entered ' + element.find('label').text() + spanText
		}
		else if(spanText == " is not valid email address !")
		{
			text_of_span = 'Entered ' + element.find('label').text() + spanText
		}

		var spanElement = $("<span>", {
			class: 'danger-text',
			text: text_of_span
		});

		element.append(spanElement);
	}

	function isValidInput(element,validate_key)
	{
		if (element.hasClass("validate-required") && validate_key == 'validate-required')
		{
			// Check if the input is empty
			var inputElement = element.find("input, select, textarea");
			var isEmpty = inputElement.val() == '';

			// For file inputs, check the hidden input value
			if (inputElement.attr('type') === 'file') {
				var hiddenInput = element.find("input[type='hidden']");
				isEmpty = hiddenInput.length > 0 ? hiddenInput.val() == '' : isEmpty;
			}

			if (isEmpty)
			{
				return false;
			}
		}

		if (element.hasClass("validate-phone"))
		{
			if (!isValidPhone(element.find("input").val()) && validate_key == 'validate-phone')
			{
				return false;
			}
		}

		if (element.hasClass("validate-email"))
		{
			if (!isValidEmail(element.find("input").val()) && validate_key == 'validate-email')
			{
				return false;
			}
		}

		return true;

	}

    function customValidation()
	{
		var top = '';
		var fields = $('form[name="checkout"] .form-row');

		fields.each(function(index, element) {

			var invalid = false;
			var element = $(element);

			reset_element(element);

			if (element.hasClass("validate-required"))
			{
				// Check if the input is empty
				var inputElement = element.find("input, select, textarea");
				var isEmpty = inputElement.val() == '';

				// For file inputs, check the hidden input value
				if (inputElement.attr('type') === 'file') {
					var hiddenInput = element.find("input[type='hidden']");
					// If there's a file input, check if it's required and if a file has been selected
					var fileInput = element.find("input[type='file']");
					var isRequired = fileInput.attr('required') === 'required';
					// Only validate as required if the field is actually marked as required
					if (isRequired) {
						isEmpty = hiddenInput.length > 0 ? hiddenInput.val() == '' : isEmpty;
					} else {
						isEmpty = false; // Not required, so never empty for validation purposes
					}
				}

				if (isEmpty && invalid == false)
				{
					if (top == '')
					{
						top = element.offset().top;
						top -= element.height();
					}

					set_element(element, " is required.");
					invalid = true;
				}
			}

			if (element.hasClass("validate-phone"))
			{
				if (element.find("input").val().length > 0 && !isValidPhone(element.find("input").val()) && invalid == false)
				{
					if (top == '')
					{
						top = element.offset().top;
						top -= element.height();
					}

					set_element(element, " is not valid phone number !");
					invalid = true;
				}
			}

			if (element.hasClass("validate-email"))
			{
				if (element.find("input").val().length > 0 && !isValidEmail(element.find("input").val()) && invalid == false)
				{
					if (top == '')
					{
						top = element.offset().top;
						top -= element.height();
					}

					set_element(element, " is not valid email address !");
					invalid = true;
				}
			}
		});

		if (top != '')
		{
			$('html, body').animate({
				scrollTop: top
			}, 1000);

			return false;
		}

		return true;
	}

	function isValidPhone(phoneNumber)
	{
		var pattern = new RegExp( /[\s\#0-9_\-\+\/\(\)\.]/g );

		if ( 0 < phoneNumber.replace( pattern, '' ).length )
		{
			return false;
		}

		return true;
	}

	function isValidEmail(email)
	{
		var pattern = new RegExp( /^([a-z\d!#$%&'*+\-\/=?^_`{|}~\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]+(\.[a-z\d!#$%&'*+\-\/=?^_`{|}~\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]+)*|"((([ \t]*\r\n)?[ \t]+)?([\x01-\x08\x0b\x0c\x0e-\x1f\x7f\x21\x23-\x5b\x5d-\x7e\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]|\\[\x01-\x09\x0b\x0c\x0d-\x7f\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]))*(([ \t]*\r\n)?[ \t]+)?")@(([a-z\d\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]|[a-z\d\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF][a-z\d\-._~\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]*[a-z\d\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])\.)+([a-z\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]|[a-z\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF][a-z\d\-._~\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]*[0-9a-z\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])\.?$/i );
		return pattern.test(email);
	}

    $(document).ready(function ($) {
		// Ensure the checkout form has the correct enctype for file uploads
		var $checkoutForm = $('form.checkout');
		if ($checkoutForm.length && $('input[name="mpwpb_has_file_upload"]').length) {
			$checkoutForm.attr('enctype', 'multipart/form-data');
			console.log('Added enctype to checkout form');
		}

		$('#billing_country-custom, #billing_state-custom').select2();

		$('#billing_country-custom, #billing_state-custom').select2('destroy');

		$(document).on('change keyup focusout', 'input:not([type="file"])', function(e) {
    		reset_element($(this).closest('p#'+$(this).attr('id')+'_field'));
		});

		$(document).on('change', 'input[type="file"]', function(e) {
			var selectedFile = $(this).val().split('\\').pop();
			var hiddenInput = $(this).siblings('input[type="hidden"]');
			hiddenInput.val(selectedFile);
			reset_element(hiddenInput.closest('p#'+hiddenInput.attr('id')+'_field'));

			// Make sure the form has the correct enctype
			var $form = $(this).closest('form');
			if ($form.attr('enctype') !== 'multipart/form-data') {
				$form.attr('enctype', 'multipart/form-data');
			}
		});

        $(document).on('click', '.mpwpb_order_proceed_area #place_order', function(e) {

			if (!allowDefaultBehavior)
			{
				e.preventDefault();
				if (customValidation())
				{
					allowDefaultBehavior = true;
                    $('#place_order').click();
				}
			}
			else
			{
				allowDefaultBehavior = false;
			}

		});

    });

})(jQuery);
