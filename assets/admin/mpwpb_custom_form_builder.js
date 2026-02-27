(function ($) {
    'use strict';

    function toKey(value) {
        return String(value || '')
            .toLowerCase()
            .replace(/[^a-z0-9_\s-]/g, '')
            .replace(/\s+/g, '_')
            .replace(/_+/g, '_')
            .replace(/^_+|_+$/g, '');
    }

    function getNextIndex($tableBody) {
        var max = 0;
        $tableBody.find('.mpwpb-cfb-row').each(function () {
            var index = parseInt($(this).attr('data-index'), 10);
            if (!isNaN(index) && index >= max) {
                max = index + 1;
            }
        });
        return max;
    }

    function fieldTypeOptions(selectedType) {
        var types = (window.mpwpbCustomFormBuilder && window.mpwpbCustomFormBuilder.fieldTypes) || {};
        var html = '';

        $.each(types, function (key, label) {
            var selected = key === selectedType ? ' selected="selected"' : '';
            html += '<option value="' + key + '"' + selected + '>' + label + '</option>';
        });

        return html;
    }

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function buildRow(index, fieldData) {
        var data = fieldData || {};
        var required = String(data.required || '0') === '1';

        return '' +
            '<tr class="mpwpb-cfb-row" data-index="' + index + '">' +
            '   <td><input type="text" class="regular-text mpwpb-cfb-label" name="mpwpb_custom_form_fields[' + index + '][label]" value="' + escapeHtml(data.label || '') + '" /></td>' +
            '   <td><input type="text" class="regular-text mpwpb-cfb-key" name="mpwpb_custom_form_fields[' + index + '][key]" value="' + escapeHtml(data.key || '') + '" /></td>' +
            '   <td><select name="mpwpb_custom_form_fields[' + index + '][type]">' + fieldTypeOptions(data.type || 'text') + '</select></td>' +
            '   <td><input type="text" class="regular-text" name="mpwpb_custom_form_fields[' + index + '][options]" value="' + escapeHtml(data.options || '') + '" /></td>' +
            '   <td><input type="text" class="regular-text" name="mpwpb_custom_form_fields[' + index + '][placeholder]" value="' + escapeHtml(data.placeholder || '') + '" /></td>' +
            '   <td><label><input type="checkbox" name="mpwpb_custom_form_fields[' + index + '][required]" value="1" ' + (required ? 'checked="checked"' : '') + ' /></label></td>' +
            '   <td><button type="button" class="button-link-delete mpwpb-cfb-remove-row">' + (window.mpwpbCustomFormBuilder.i18n.remove || 'Remove') + '</button></td>' +
            '</tr>';
    }

    function appendRow($settings, fieldData) {
        var $rows = $settings.find('.mpwpb-cfb-rows');
        var nextIndex = getNextIndex($rows);
        $rows.append(buildRow(nextIndex, fieldData));
    }

    function loadTemplateRows($settings, templateKey) {
        var templates = (window.mpwpbCustomFormBuilder && window.mpwpbCustomFormBuilder.templates) || {};
        var template = templates[templateKey];
        if (!template || !template.fields || !template.fields.length) {
            return;
        }

        var $rows = $settings.find('.mpwpb-cfb-rows');
        $rows.empty();

        $.each(template.fields, function (_, fieldData) {
            appendRow($settings, fieldData);
        });
    }

    $(document).on('click', '.mpwpb-cfb-add-row', function () {
        var $settings = $(this).closest('.mpwpb_custom_form_builder_settings');
        appendRow($settings, {});
    });

    $(document).on('click', '.mpwpb-cfb-remove-row', function () {
        $(this).closest('.mpwpb-cfb-row').remove();
    });

    $(document).on('keyup change', '.mpwpb-cfb-label', function () {
        var $row = $(this).closest('.mpwpb-cfb-row');
        var $key = $row.find('.mpwpb-cfb-key');
        if ($key.val().trim() === '') {
            $key.val(toKey($(this).val()));
        }
    });

    $(document).on('click', '.mpwpb-cfb-apply-template', function () {
        var $settings = $(this).closest('.mpwpb_custom_form_builder_settings');
        var templateKey = $settings.find('.mpwpb-cfb-template-select').val();
        var i18n = (window.mpwpbCustomFormBuilder && window.mpwpbCustomFormBuilder.i18n) || {};

        if (!templateKey || templateKey === 'custom') {
            window.alert(i18n.selectTemplate || 'Please select a template first.');
            return;
        }

        loadTemplateRows($settings, templateKey);
        window.alert(i18n.templateLoaded || 'Template fields are loaded. Save the service to apply.');
    });
}(jQuery));
