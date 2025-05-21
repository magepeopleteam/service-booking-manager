jQuery(document).ready(function($) {
    // Create a new modal container outside of WordPress structure with improved styling
    var modalHtml = `
    <div id="mpwpb-custom-modal" style="
        display: none;
        position: fixed;
        z-index: 999999;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        overflow: auto;
        background-color: rgba(0,0,0,0.7);
    ">
        <div id="mpwpb-custom-modal-content" style="
            background-color: #fff;
            margin: 5% auto;
            padding: 0;
            border: 1px solid #ddd;
            width: 80%;
            max-width: 600px;
            position: relative;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            border-radius: 3px;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen-Sans, Ubuntu, Cantarell, 'Helvetica Neue', sans-serif;
        ">
            <div style="
                background-color: #f5f5f5;
                padding: 10px 15px;
                border-bottom: 1px solid #ddd;
                position: relative;
                font-weight: 600;
                font-size: 14px;
            ">
                Checkout Field
                <span id="mpwpb-custom-modal-close" style="
                    position: absolute;
                    top: 10px;
                    right: 15px;
                    color: #666;
                    font-size: 18px;
                    font-weight: bold;
                    cursor: pointer;
                    text-decoration: none;
                    line-height: 1;
                ">&times;</span>
            </div>
            <div id="mpwpb-custom-modal-body" style="padding: 15px;"></div>
        </div>
    </div>`;

    $('body').append(modalHtml);

    // When the original "Add Field" or "Edit" button is clicked
    $('.mpwpb_styles .checkout .open-modal').on('click', function() {
        console.log('Open modal button clicked');

        // Get the original modal content
        var originalModalContent = $('.mpwpb_styles .checkout #field-modal .modal-content').html();

        // Set the content in our custom modal
        $('#mpwpb-custom-modal-body').html(originalModalContent);

        // Add WordPress admin classes to form elements
        $('#mpwpb-custom-modal-body input[type="text"]').addClass('regular-text');
        $('#mpwpb-custom-modal-body select').addClass('regular-text');
        $('#mpwpb-custom-modal-body button[type="submit"]').addClass('button button-primary');

        // Show our custom modal
        $('#mpwpb-custom-modal').show();
        $('body').addClass('has-mpwpb-modal-open');

        // Set the form values based on the button data
        var action = $(this).data('action');
        var key = $(this).data('key');

        $('#mpwpb-custom-modal-body input[name="action"]').val(action);
        $('#mpwpb-custom-modal-body input[name="key"]').val(key);

        if (action == 'add') {
            $('#mpwpb-custom-modal-body input[name="old_name"]').val('');
            $('#mpwpb-custom-modal-body input[name="new_name"]').val('');
            $('#mpwpb-custom-modal-body input[name="new_type"]').val('');
            $('#mpwpb-custom-modal-body input[name="name"]').val('');
            $('#mpwpb-custom-modal-body input[name="name"]').prop('disabled', false);

            // Reset select
            var option = {'text': "text", 'select': "select", 'file': "file"};
            $('#mpwpb-custom-modal-body select#type option').remove();
            $.each(option, function (key, value) {
                $('#mpwpb-custom-modal-body select#type').append($("<option></option>").attr("value", key).text(value));
            });
            $('#mpwpb-custom-modal-body select#type').prop('disabled', false);

            $('#mpwpb-custom-modal-body input[name="label"]').val('');
            $('#mpwpb-custom-modal-body input[name="priority"]').val('');
            $('#mpwpb-custom-modal-body input[name="class"]').val('');
            $('#mpwpb-custom-modal-body input[name="validate"]').val('');
            $('#mpwpb-custom-modal-body input[name="required"]').prop('checked', true);
            $('#mpwpb-custom-modal-body input[name="disabled"]').prop('checked', false);

            // Prepare placeholder section
            $('#mpwpb-custom-modal-body .custom-var-attr-section').empty();
            $('#mpwpb-custom-modal-body .custom-var-attr-section').html(
                '<label for="placeholder">Placeholder:</label>' +
                '<input type="text" name="placeholder" id="placeholder">'
            );
        } else if (action == 'edit') {
            let field = JSON.parse($('input[name="' + $(this).data('name') + '"]').val());
            $('#mpwpb-custom-modal-body input[name="old_name"]').val(field.name);
            $('#mpwpb-custom-modal-body input[name="new_name"]').val(field.name);
            $('#mpwpb-custom-modal-body input[name="name"]').val(field.name);
            $('#mpwpb-custom-modal-body input[name="name"]').prop('disabled', true);

            var option = new Option(field.attributes.type, field.attributes.type, '1', '1');
            $('#mpwpb-custom-modal-body select#type').append(option);
            $('#mpwpb-custom-modal-body select#type').prop('disabled', true);

            $('#mpwpb-custom-modal-body input[name="new_type"]').val(field.attributes.type);
            $('#mpwpb-custom-modal-body input[name="label"]').val(field.attributes.label);
            $('#mpwpb-custom-modal-body input[name="priority"]').val(field.attributes.priority);
            $('#mpwpb-custom-modal-body input[name="class"]').val(field.attributes.class);
            $('#mpwpb-custom-modal-body input[name="validate"]').val(field.attributes.validate);
            $('#mpwpb-custom-modal-body input[name="required"]').prop('checked', field.attributes.required == 1 ? true : false);
            $('#mpwpb-custom-modal-body input[name="disabled"]').prop('checked', field.attributes.disabled == 1 ? true : false);

            // Handle different field types
            if (field.attributes.type == 'text' || field.attributes.type == 'file') {
                $('#mpwpb-custom-modal-body .custom-var-attr-section').empty();
                $('#mpwpb-custom-modal-body .custom-var-attr-section').html(
                    '<label for="placeholder">Placeholder:</label>' +
                    '<input type="text" name="placeholder" id="placeholder">'
                );
                $('#mpwpb-custom-modal-body input[name="placeholder"]').val(field.attributes.placeholder);
            } else if (field.attributes.type == 'select') {
                let html = "";
                if (typeof field.attributes.options === 'object' && !Array.isArray(field.attributes.options) && field.attributes.options !== null) {
                    html += ('<table>' +
                        '<tbody class="ui-sortable">');
                    for (const [key, value] of Object.entries(field.attributes.options)) {
                        html += ('<tr>' +
                            '<td>' +
                            '<div class="option-row">' +
                            '<div class="input-cell">' +
                            '<input type="text" name="option_value[]" placeholder="Option Value" value="' + key + '">' +
                            '</div>' +
                            '<div class="input-cell">' +
                            '<input type="text" name="option_text[]" placeholder="Option Text" value="' + value + '">' +
                            '</div>' +
                            '<div class="action-cell">' +
                            '<a class="action-plus" href="javascript:void(0)" onclick="thwcfdAddNewOptionRow(this)" title="Add option"><i class="dashicons dashicons-plus-alt2"></i></a>' +
                            '<a class="action-minus" href="javascript:void(0)" onclick="thwcfdRemoveOptionRow(this)" title="Remove option"><i class="dashicons dashicons-minus"></i></a>' +
                            '<a class="action-move sort ui-sortable-handle" href="javascript:void(0)" title="Move option"><i class="dashicons dashicons-move"></i></a>' +
                            '</div>' +
                            '</div>' +
                            '</td>' +
                            '</tr>');
                    }
                    html += ('</tbody>' +
                        '</table>');
                }
                $('#mpwpb-custom-modal-body .custom-var-attr-section').empty();
                $('#mpwpb-custom-modal-body .custom-var-attr-section').html(html);
            }
        }

        return false;
    });

    // Close the modal only when clicking the close button
    $('#mpwpb-custom-modal-close').on('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $('#mpwpb-custom-modal').hide();
        $('body').removeClass('has-mpwpb-modal-open');
    });

    // Close when clicking outside the modal content (on the overlay)
    $('#mpwpb-custom-modal').on('click', function(e) {
        // Only close if the click is directly on the overlay (not on any child elements)
        if (e.target === this) {
            $('#mpwpb-custom-modal').hide();
            $('body').removeClass('has-mpwpb-modal-open');
        }
    });

    // Prevent any clicks inside the modal content from closing the modal
    $('#mpwpb-custom-modal-content').on('click', function(e) {
        e.stopPropagation();
    });

    // Style the form elements when they're dynamically added
    function styleFormElements() {
        $('#mpwpb-custom-modal-body input[type="text"]').addClass('regular-text');
        $('#mpwpb-custom-modal-body select').addClass('regular-text');
        $('#mpwpb-custom-modal-body button[type="submit"]').addClass('button button-primary');

        // Improve checkbox layout
        $('#mpwpb-custom-modal-body input[type="checkbox"]').each(function() {
            $(this).parent().css({
                'display': 'inline-flex',
                'align-items': 'center',
                'margin-right': '15px'
            });
        });

        // Hide the original heading
        $('#mpwpb-custom-modal-body h2').hide();

        // Make sure delete button is properly styled
        $('#mpwpb-custom-modal-body .button-link-delete').addClass('button');
    }

    // Apply styling when select type changes
    $(document).on('change', '#mpwpb-custom-modal-body select#type', function() {
        setTimeout(styleFormElements, 100);
    });

    // Handle form submission from the custom modal
    $(document).on('submit', '#mpwpb-custom-modal-body form', function(e) {
        // Prevent the event from bubbling up
        e.stopPropagation();
        // The form will submit normally to the server
        return true;
    });

    // Prevent clicks on form elements from bubbling up to parent elements
    // But exclude the delete button to allow it to work normally
    $(document).on('click', '#mpwpb-custom-modal-body input, #mpwpb-custom-modal-body select, #mpwpb-custom-modal-body button, #mpwpb-custom-modal-body a:not(.delete)', function(e) {
        e.stopPropagation();
    });

    // Handle the delete button click
    $(document).on('click', '.button-link-delete.delete', function() {
        // The default browser behavior will handle this link
        return true;
    });

    // Apply styling whenever content changes
    const observer = new MutationObserver(function(mutations) {
        styleFormElements();
    });

    // Start observing the modal body for content changes
    observer.observe(document.getElementById('mpwpb-custom-modal-body'), {
        childList: true,
        subtree: true
    });
});
