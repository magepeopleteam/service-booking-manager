(function ($) {
    "use strict";
    function init() {
        const tabItems = document.querySelectorAll('.mpwpb_styles .checkout .tab-item');
        const tabContents = document.querySelectorAll('.mpwpb_styles .checkout .tab-content');
        tabItems.forEach((tabItem) => {
            tabItem.addEventListener('click', () => {
                tabItems.forEach((item) => {
                    item.classList.remove('active');
                });
                tabContents.forEach((content) => {
                    content.classList.remove('active');
                });
                const target = tabItem.getAttribute('data-tabs-target');
                tabItem.classList.add('active');
                document.querySelector(target).classList.add('active');
                window.location.hash = target;
            });
        });
        const currentHash = window.location.hash;
        if (currentHash.length > 0) {
            tabItems.forEach((tabItem) => {
                tabItem.classList.remove('active');
            });
            tabContents.forEach((tabContent) => {
                tabContent.classList.remove('active');
            });
            tabItems.forEach((tabItem) => {
                const target = tabItem.getAttribute('data-tabs-target');
                if (target === currentHash) {
                    tabItem.classList.add('active');
                }
            });
            tabContents.forEach((tabContent) => {
                if (tabContent.getAttribute('id') === currentHash.substring(1)) {
                    tabContent.classList.add('active');
                }
            });
        }
    }
    function reset_type_select() {
        var option = {'text': "text", 'select': "select", 'file': "file"};
        $('.mpwpb_styles .checkout select#type option').remove();
        $.each(option, function (key, value) {
            $('.mpwpb_styles .checkout select#type').append($("<option></option>").attr("value", key).text(value));
        });
        $('.mpwpb_styles .checkout select#type').prop('disabled', false);
        type_rendering($('.mpwpb_styles .checkout select#type').find(":selected").val(), $('.mpwpb_styles .checkout .open-modal').data('action'));
    }
    function type_rendering(type, action, field = null) {
        if (type == 'text') {
            prepare_text(action, field);
        } else if (type == 'select') {
            prepare_select(action, field);
        } else if (type == 'file') {
            prepare_file(action, field);
        } else {
            prepare_other(action, field);
        }
    }
    function prepare_text(action, field = null) {
        if (action === 'add') {
            $('.mpwpb_styles .checkout .custom-var-attr-section').empty();
            $('.mpwpb_styles .checkout .custom-var-attr-section').html(
                '<label for="placeholder">Placeholder:</label>' +
                '<input type="text" name="placeholder" id="placeholder">'
            );
        } else if (action === 'edit') {
            $('.mpwpb_styles .checkout .custom-var-attr-section').empty();
            $('.mpwpb_styles .checkout .custom-var-attr-section').html(
                '<label for="placeholder">Placeholder:</label>' +
                '<input type="text" name="placeholder" id="placeholder">'
            );
            $('.mpwpb_styles .checkout input[name="placeholder"]').val(field.attributes.placeholder);
        }
    }
    function prepare_select(action, field = null) {
        if (action === 'add') {
            $('.mpwpb_styles .checkout .custom-var-attr-section').empty();
            $('.mpwpb_styles .checkout .custom-var-attr-section').html(
                '<table>' +
                '<tbody class="ui-sortable">' +
                '<tr>' +
                '<td>' +
                '<div class="option-row">' +
                '<div class="input-cell">' +
                '<input type="text" name="option_value[]" placeholder="Option Value">' +
                '</div>' +
                '<div class="input-cell">' +
                '<input type="text" name="option_text[]" placeholder="Option Text">' +
                '</div>' +
                '<div class="action-cell">' +
                '<a class="action-plus" href="javascript:void(0)" onclick="thwcfdAddNewOptionRow(this)" title="Add option"><i class="dashicons dashicons-plus-alt2"></i></a>' +
                '<a class="action-minus" href="javascript:void(0)" onclick="thwcfdRemoveOptionRow(this)" title="Remove option"><i class="dashicons dashicons-minus"></i></a>' +
                '<a class="action-move sort ui-sortable-handle" href="javascript:void(0)" title="Move option"><i class="dashicons dashicons-move"></i></a>' +
                '</div>' +
                '</div>' +
                '</td>' +
                '</tr>' +
                '</tbody>' +
                '</table>'
            );
        } else if (action === 'edit') {
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
            $('.mpwpb_styles .checkout .custom-var-attr-section').empty();
            $('.mpwpb_styles .checkout .custom-var-attr-section').html(html);
        }
        $(".ui-sortable").sortable();
    }
    function prepare_file(action, field = null) {
        if (action === 'add') {
            $('.mpwpb_styles .checkout input[name="validate"]').val('');
            $('.mpwpb_styles .checkout .custom-var-attr-section').empty();
            $('.mpwpb_styles .checkout .custom-var-attr-section').html(
                '<label for="placeholder">Placeholder:</label>' +
                '<input type="text" name="placeholder" id="placeholder">'
            );
        } else if (action === 'edit') {
            $('.mpwpb_styles .checkout .custom-var-attr-section').empty();
            $('.mpwpb_styles .checkout .custom-var-attr-section').html(
                '<label for="placeholder">Placeholder:</label>' +
                '<input type="text" name="placeholder" id="placeholder">'
            );
            $('.mpwpb_styles .checkout input[name="placeholder"]').val(field.attributes.placeholder);
        }
    }
    function prepare_other(action, field = null) {
        if (action === 'add') {
            $('.mpwpb_styles .checkout .custom-var-attr-section').empty();
            $('.mpwpb_styles .checkout .custom-var-attr-section').html(
                '<label for="placeholder">Placeholder:</label>' +
                '<input type="text" name="placeholder" id="placeholder">'
            );
        } else if (action === 'edit') {
            $('.mpwpb_styles .checkout .custom-var-attr-section').empty();
            $('.mpwpb_styles .checkout .custom-var-attr-section').html(
                '<label for="placeholder">Placeholder:</label>' +
                '<input type="text" name="placeholder" id="placeholder">'
            );
            $('.mpwpb_styles .checkout input[name="placeholder"]').val(field.attributes.placeholder);
        }
    }
    $(document).ready(
        function () {
            init();
            $('.mpwpb_styles .checkout .open-modal').click(function () {
                // Original modal functionality - now handled by mpwpb-modal-fix.js
                $('.mpwpb_styles .checkout #field-modal').css('display', 'block');
                $('.mpwpb_styles .checkout #field-modal input[name="action"]').val($(this).data('action'));
                $('.mpwpb_styles .checkout #field-modal input[name="key"]').val($(this).data('key'));
                if ($(this).data('action') == 'add') {
                    $('.mpwpb_styles .checkout #field-modal #mpwpb_pro_checkout_field_edit_nonce').prop("disabled", true);
                    $('.mpwpb_styles .checkout #field-modal #mpwpb_pro_checkout_field_add_nonce').prop("disabled", false);
                    $('.mpwpb_styles .checkout input[name="old_name"]').val('');
                    $('.mpwpb_styles .checkout input[name="new_name"]').val('');
                    $('.mpwpb_styles .checkout input[name="new_type"]').val('');
                    $('.mpwpb_styles .checkout input[name="name"]').val('');
                    $('.mpwpb_styles .checkout input[name="name"]').prop('disabled', false);
                    reset_type_select();
                    $('.mpwpb_styles .checkout input[name="label"]').val('');
                    $('.mpwpb_styles .checkout input[name="priority"]').val('');
                    $('.mpwpb_styles .checkout input[name="class"]').val('');
                    $('.mpwpb_styles .checkout input[name="validate"]').val('');
                    $('.mpwpb_styles .checkout input[name="required"]').prop('checked', true);
                    $('.mpwpb_styles .checkout input[name="disabled"]').prop('checked', false);
                    type_rendering($('.mpwpb_styles .checkout select#type').find(":selected").val(), $('.mpwpb_styles .checkout #field-modal input[name="action"]').val());
                } else if ($(this).data('action') == 'edit') {
                    $('.mpwpb_styles .checkout #field-modal #mpwpb_pro_checkout_field_edit_nonce').prop("disabled", false);
                    $('.mpwpb_styles .checkout #field-modal #mpwpb_pro_checkout_field_add_nonce').prop("disabled", true);
                    let field = JSON.parse($('input[name="' + $(this).data('name') + '"]').val());
                    $('.mpwpb_styles .checkout input[name="old_name"]').val(field.name);
                    $('.mpwpb_styles .checkout input[name="new_name"]').val(field.name);
                    $('.mpwpb_styles .checkout input[name="name"]').val(field.name);
                    $('.mpwpb_styles .checkout input[name="name"]').prop('disabled', true);
                    var option = new Option(field.attributes.type, field.attributes.type, '1', '1');
                    $('.mpwpb_styles .checkout select#type').append(option);
                    $('.mpwpb_styles .checkout select#type').prop('disabled', true);
                    $('.mpwpb_styles .checkout input[name="new_type"]').val(field.attributes.type);
                    $('.mpwpb_styles .checkout input[name="label"]').val(field.attributes.label);
                    $('.mpwpb_styles .checkout input[name="priority"]').val(field.attributes.priority);
                    $('.mpwpb_styles .checkout input[name="class"]').val(field.attributes.class);
                    $('.mpwpb_styles .checkout input[name="validate"]').val(field.attributes.validate);
                    $('.mpwpb_styles .checkout input[name="required"]').prop('checked', field.attributes.required == 1 ? true : false);
                    $('.mpwpb_styles .checkout input[name="disabled"]').prop('checked', field.attributes.disabled == 1 ? true : false);
                    type_rendering($('.mpwpb_styles .checkout input[name="new_type"]').val(), $('.mpwpb_styles .checkout #field-modal input[name="action"]').val(), field);
                }
            });
            $('.mpwpb_styles .checkout select#type').on('change', function () {
                type_rendering(this.value, $('.mpwpb_styles .checkout .open-modal').data('action'));
            });
            $(".ui-sortable").sortable();
            function thwcfdAddNewOptionRow(button) {
                var $row = $(button).closest("tr");
                var $clone = $row.clone();
                $row.after($clone);
            }
            function thwcfdRemoveOptionRow(button) {
                var rowCount = $(".ui-sortable tr").length;
                if (rowCount > 1) {
                    $(button).closest("tr").remove();
                }
            }
            window.thwcfdAddNewOptionRow = thwcfdAddNewOptionRow;
            window.thwcfdRemoveOptionRow = thwcfdRemoveOptionRow;
            $('.mpwpb_styles .checkout .close,.mpwpb_styles .checkout .modal').click(function () {
                // Original close functionality - now handled by mpwpb-modal-fix.js
                $('.mpwpb_styles .checkout #field-modal').css('display', 'none');
            });
            $('.mpwpb_styles .checkout .modal-content').click(function (e) {
                e.stopPropagation();
            });
            $('.mpwpb_styles .checkout .checkoutSwitchButton').on('change', function () {
                var element = $(this);
                var key = $(this).data('key');
                var name = $(this).data('name');
                var isChecked = this.checked;
                $.ajax({
                    type: 'POST',
                    url: mpwpb_admin_ajax.ajax_url,
                    data: {
                        action: "mpwpb_disable_field",
                        key: key,
                        name: name,
                        isChecked: isChecked,
                        nonce: mpwpb_admin_ajax.nonce
                    },
                    success: function (response) {
                        var jsonResponse = response;
                        if (jsonResponse == 'success') {
                            element.prop('checked', isChecked);
                        } else {
                            element.prop('checked', !isChecked);
                        }
                        if (isChecked) {
                            element.closest('tr').find('td .checkout-disabled').removeClass("dashicons dashicons-yes tips");
                        } else {
                            element.closest('tr').find('td .checkout-disabled').addClass("dashicons dashicons-yes tips");
                        }
                    }
                });
            });
        }
    );
}(jQuery));

