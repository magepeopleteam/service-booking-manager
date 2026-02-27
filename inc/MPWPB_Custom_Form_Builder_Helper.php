<?php
if (!defined('ABSPATH')) {
    die;
}

if (!class_exists('MPWPB_Custom_Form_Builder_Helper')) {
    class MPWPB_Custom_Form_Builder_Helper {

        public static function get_allowed_field_types() {
            return array(
                'text' => esc_html__('Text', 'service-booking-manager'),
                'email' => esc_html__('Email', 'service-booking-manager'),
                'tel' => esc_html__('Phone', 'service-booking-manager'),
                'textarea' => esc_html__('Textarea', 'service-booking-manager'),
                'select' => esc_html__('Select', 'service-booking-manager'),
                'radio' => esc_html__('Radio', 'service-booking-manager'),
                'checkbox' => esc_html__('Checkbox', 'service-booking-manager'),
                'date' => esc_html__('Date', 'service-booking-manager'),
            );
        }

        public static function get_predefined_templates() {
            return array(
                'basic_contact' => array(
                    'label' => esc_html__('Basic Contact', 'service-booking-manager'),
                    'fields' => array(
                        array(
                            'label' => esc_html__('Full Name', 'service-booking-manager'),
                            'key' => 'full_name',
                            'type' => 'text',
                            'options' => '',
                            'placeholder' => esc_html__('Enter your full name', 'service-booking-manager'),
                            'required' => '1',
                        ),
                        array(
                            'label' => esc_html__('Phone', 'service-booking-manager'),
                            'key' => 'phone',
                            'type' => 'tel',
                            'options' => '',
                            'placeholder' => esc_html__('Enter your phone number', 'service-booking-manager'),
                            'required' => '1',
                        ),
                        array(
                            'label' => esc_html__('Email', 'service-booking-manager'),
                            'key' => 'email',
                            'type' => 'email',
                            'options' => '',
                            'placeholder' => esc_html__('Enter your email address', 'service-booking-manager'),
                            'required' => '0',
                        ),
                    ),
                ),
                'address_notes' => array(
                    'label' => esc_html__('Address + Notes', 'service-booking-manager'),
                    'fields' => array(
                        array(
                            'label' => esc_html__('Service Address', 'service-booking-manager'),
                            'key' => 'service_address',
                            'type' => 'textarea',
                            'options' => '',
                            'placeholder' => esc_html__('Enter full service address', 'service-booking-manager'),
                            'required' => '1',
                        ),
                        array(
                            'label' => esc_html__('Preferred Date', 'service-booking-manager'),
                            'key' => 'preferred_date',
                            'type' => 'date',
                            'options' => '',
                            'placeholder' => '',
                            'required' => '0',
                        ),
                        array(
                            'label' => esc_html__('Additional Notes', 'service-booking-manager'),
                            'key' => 'additional_notes',
                            'type' => 'textarea',
                            'options' => '',
                            'placeholder' => esc_html__('Write any special instructions', 'service-booking-manager'),
                            'required' => '0',
                        ),
                    ),
                ),
                'business_profile' => array(
                    'label' => esc_html__('Business Profile', 'service-booking-manager'),
                    'fields' => array(
                        array(
                            'label' => esc_html__('Company Name', 'service-booking-manager'),
                            'key' => 'company_name',
                            'type' => 'text',
                            'options' => '',
                            'placeholder' => esc_html__('Enter company name', 'service-booking-manager'),
                            'required' => '1',
                        ),
                        array(
                            'label' => esc_html__('Business Type', 'service-booking-manager'),
                            'key' => 'business_type',
                            'type' => 'select',
                            'options' => esc_html__('Retail,Corporate,Hospitality,Other', 'service-booking-manager'),
                            'placeholder' => '',
                            'required' => '0',
                        ),
                        array(
                            'label' => esc_html__('Need Invoice?', 'service-booking-manager'),
                            'key' => 'need_invoice',
                            'type' => 'radio',
                            'options' => esc_html__('Yes,No', 'service-booking-manager'),
                            'placeholder' => '',
                            'required' => '0',
                        ),
                    ),
                ),
            );
        }

        public static function is_enabled($post_id) {
            return get_post_meta($post_id, 'mpwpb_custom_form_enable', true) === 'on';
        }

        public static function get_fields($post_id) {
            $fields = get_post_meta($post_id, 'mpwpb_custom_form_fields', true);
            return self::normalize_fields($fields);
        }

        public static function get_template_fields($template_id) {
            $templates = self::get_predefined_templates();
            if (!isset($templates[$template_id]['fields']) || !is_array($templates[$template_id]['fields'])) {
                return array();
            }
            return self::normalize_fields($templates[$template_id]['fields']);
        }

        public static function normalize_fields($fields) {
            $allowed_types = array_keys(self::get_allowed_field_types());
            $rows = array();
            $used_keys = array();

            if (!is_array($fields)) {
                return array();
            }

            foreach ($fields as $field) {
                if (!is_array($field)) {
                    continue;
                }

                $label = isset($field['label']) ? sanitize_text_field(wp_unslash($field['label'])) : '';
                $key = isset($field['key']) ? sanitize_key(wp_unslash($field['key'])) : '';
                $type = isset($field['type']) ? sanitize_key(wp_unslash($field['type'])) : 'text';
                $options = isset($field['options']) ? sanitize_text_field(wp_unslash($field['options'])) : '';
                $placeholder = isset($field['placeholder']) ? sanitize_text_field(wp_unslash($field['placeholder'])) : '';
                $required = isset($field['required']) && (string) $field['required'] === '1' ? '1' : '0';

                if (!$label) {
                    continue;
                }

                if (!$key) {
                    $key = sanitize_key($label);
                }
                if (!$key) {
                    continue;
                }

                if (!in_array($type, $allowed_types, true)) {
                    $type = 'text';
                }

                $unique_key = $key;
                $suffix = 2;
                while (isset($used_keys[$unique_key])) {
                    $unique_key = $key . '_' . $suffix;
                    $suffix++;
                }
                $used_keys[$unique_key] = true;

                $rows[] = array(
                    'label' => $label,
                    'key' => $unique_key,
                    'type' => $type,
                    'options' => $options,
                    'placeholder' => $placeholder,
                    'required' => $required,
                );
            }

            return $rows;
        }

        public static function prepare_cart_values($submitted_values, $fields) {
            $prepared_values = array();

            if (!is_array($submitted_values) || !is_array($fields)) {
                return $prepared_values;
            }

            foreach ($fields as $field) {
                $key = isset($field['key']) ? $field['key'] : '';
                if (!$key) {
                    continue;
                }

                $raw_value = isset($submitted_values[$key]) ? $submitted_values[$key] : '';
                $value = self::sanitize_field_value($raw_value, $field);

                if ($value === '') {
                    continue;
                }

                $prepared_values[$key] = array(
                    'label' => isset($field['label']) ? $field['label'] : $key,
                    'value' => $value,
                    'type' => isset($field['type']) ? $field['type'] : 'text',
                );
            }

            return $prepared_values;
        }

        public static function get_missing_required_fields($values, $fields) {
            $missing = array();

            if (!is_array($fields)) {
                return $missing;
            }

            foreach ($fields as $field) {
                if (!isset($field['required']) || (string) $field['required'] !== '1') {
                    continue;
                }

                $key = isset($field['key']) ? $field['key'] : '';
                if (!$key) {
                    continue;
                }

                $value = '';
                if (isset($values[$key]['value'])) {
                    $value = sanitize_text_field((string) $values[$key]['value']);
                }

                if ($value === '') {
                    $missing[] = isset($field['label']) ? $field['label'] : $key;
                }
            }

            return $missing;
        }

        public static function sanitize_field_value($value, $field) {
            $field_type = isset($field['type']) ? $field['type'] : 'text';

            if (is_array($value)) {
                $value = array_map(
                    static function ($single_value) {
                        return sanitize_text_field(wp_unslash($single_value));
                    },
                    $value
                );
                $value = array_filter($value, static function ($single_value) {
                    return $single_value !== '';
                });

                if (empty($value)) {
                    return '';
                }

                return implode(', ', $value);
            }

            $value = sanitize_text_field(wp_unslash($value));

            if ($field_type === 'email' && $value !== '') {
                $value = sanitize_email($value);
            }

            return $value;
        }

        public static function parse_options($option_string) {
            if (!is_string($option_string) || $option_string === '') {
                return array();
            }

            $options = array_map('trim', explode(',', $option_string));
            $options = array_filter($options, static function ($option) {
                return $option !== '';
            });

            return array_values($options);
        }
    }
}
