<?php
if (!defined('ABSPATH')) {
    die;
}

if (!class_exists('MPWPB_Custom_Form_Builder')) {
    class MPWPB_Custom_Form_Builder {

        public function __construct() {
            add_action('mpwpb_render_custom_form_fields', array($this, 'render_custom_form_fields'));
            add_filter('mpwpb_add_cart_item', array($this, 'add_custom_fields_to_cart_item'), 10, 2);
            add_action('mpwpb_show_cart_item', array($this, 'show_custom_fields_in_cart_item'), 10, 2);
            add_action('mpwpb_validate_cart_item', array($this, 'validate_required_custom_fields'), 10, 2);
            add_action('mpwpb_checkout_create_order_line_item', array($this, 'save_custom_fields_in_order_item'), 10, 2);
            add_action('add_mpwpb_frontend_script', array($this, 'enqueue_assets'));
        }

        public function enqueue_assets() {
            wp_enqueue_style(
                'mpwpb_custom_form_builder_front',
                MPWPB_PLUGIN_URL . '/assets/frontend/mpwpb_custom_form_builder.css',
                array(),
                time()
            );
        }

        public function render_custom_form_fields($post_id) {
            if (!MPWPB_Custom_Form_Builder_Helper::is_enabled($post_id)) {
                return;
            }

            $fields = MPWPB_Custom_Form_Builder_Helper::get_fields($post_id);
            if (empty($fields)) {
                return;
            }
            ?>
            <div class="_dShadow_7_mB_xs mpwpb_custom_form_builder_area" id="mpwpb_custom_form_builder_area">
                <header>
                    <h3><?php esc_html_e('Additional Information', 'service-booking-manager'); ?></h3>
                </header>

                <div class="mpwpb_custom_form_builder_fields">
                    <?php foreach ($fields as $field) : ?>
                        <?php $this->render_single_field($field); ?>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php
        }

        private function render_single_field($field) {
            $key = isset($field['key']) ? $field['key'] : '';
            $label = isset($field['label']) ? $field['label'] : '';
            $type = isset($field['type']) ? $field['type'] : 'text';
            $placeholder = isset($field['placeholder']) ? $field['placeholder'] : '';
            $required = isset($field['required']) && (string) $field['required'] === '1';
            $options = MPWPB_Custom_Form_Builder_Helper::parse_options(isset($field['options']) ? $field['options'] : '');

            if (!$key || !$label) {
                return;
            }

            $required_attr = $required ? '1' : '0';
            $required_mark = $required ? '<span class="textRequired">*</span>' : '';
            ?>
            <div class="mpwpb-custom-form-group" data-field-key="<?php echo esc_attr($key); ?>" data-field-label="<?php echo esc_attr($label); ?>" data-field-type="<?php echo esc_attr($type); ?>" data-required="<?php echo esc_attr($required_attr); ?>">
                <label class="mpwpb-custom-form-label">
                    <?php echo esc_html($label); ?> <?php echo wp_kses_post($required_mark); ?>
                </label>

                <?php if ($type === 'textarea') : ?>
                    <textarea class="formControl mpwpb-custom-form-field" name="mpwpb_custom_form[<?php echo esc_attr($key); ?>]" placeholder="<?php echo esc_attr($placeholder); ?>"></textarea>
                <?php elseif ($type === 'select') : ?>
                    <select class="formControl mpwpb-custom-form-field" name="mpwpb_custom_form[<?php echo esc_attr($key); ?>]">
                        <option value=""><?php esc_html_e('Please Select', 'service-booking-manager'); ?></option>
                        <?php foreach ($options as $option) : ?>
                            <option value="<?php echo esc_attr($option); ?>"><?php echo esc_html($option); ?></option>
                        <?php endforeach; ?>
                    </select>
                <?php elseif ($type === 'radio') : ?>
                    <?php
                    if (empty($options)) {
                        $options = array(esc_html__('Yes', 'service-booking-manager'), esc_html__('No', 'service-booking-manager'));
                    }
                    ?>
                    <div class="mpwpb-custom-form-choices">
                        <?php foreach ($options as $option) : ?>
                            <label class="mpwpb-custom-choice">
                                <input type="radio" class="mpwpb-custom-form-field" name="mpwpb_custom_form[<?php echo esc_attr($key); ?>]" value="<?php echo esc_attr($option); ?>" />
                                <span><?php echo esc_html($option); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                <?php elseif ($type === 'checkbox') : ?>
                    <?php
                    if (empty($options)) {
                        $options = array(esc_html__('Yes', 'service-booking-manager'));
                    }
                    ?>
                    <div class="mpwpb-custom-form-choices">
                        <?php foreach ($options as $option) : ?>
                            <label class="mpwpb-custom-choice">
                                <input type="checkbox" class="mpwpb-custom-form-field" name="mpwpb_custom_form[<?php echo esc_attr($key); ?>][]" value="<?php echo esc_attr($option); ?>" />
                                <span><?php echo esc_html($option); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                <?php else : ?>
                    <input type="<?php echo esc_attr($type); ?>" class="formControl mpwpb-custom-form-field" name="mpwpb_custom_form[<?php echo esc_attr($key); ?>]" placeholder="<?php echo esc_attr($placeholder); ?>" />
                <?php endif; ?>
            </div>
            <?php
        }

        public function add_custom_fields_to_cart_item($cart_item_data, $post_id) {
            if (!MPWPB_Custom_Form_Builder_Helper::is_enabled($post_id)) {
                return $cart_item_data;
            }

            $fields = MPWPB_Custom_Form_Builder_Helper::get_fields($post_id);
            if (empty($fields)) {
                return $cart_item_data;
            }

            $submitted_values = isset($_POST['mpwpb_custom_form_values']) ? wp_unslash($_POST['mpwpb_custom_form_values']) : array();
            $prepared_values = MPWPB_Custom_Form_Builder_Helper::prepare_cart_values($submitted_values, $fields);

            if (!empty($prepared_values)) {
                $cart_item_data['mpwpb_custom_form_values'] = $prepared_values;
            }

            return $cart_item_data;
        }

        public function validate_required_custom_fields($cart_item, $post_id) {
            if (!MPWPB_Custom_Form_Builder_Helper::is_enabled($post_id)) {
                return;
            }

            $fields = MPWPB_Custom_Form_Builder_Helper::get_fields($post_id);
            if (empty($fields)) {
                return;
            }

            $saved_values = isset($cart_item['mpwpb_custom_form_values']) && is_array($cart_item['mpwpb_custom_form_values'])
                ? $cart_item['mpwpb_custom_form_values']
                : array();

            $missing_fields = MPWPB_Custom_Form_Builder_Helper::get_missing_required_fields($saved_values, $fields);
            if (!empty($missing_fields)) {
                wc_add_notice(
                    sprintf(
                        esc_html__('Please complete required custom form fields: %s', 'service-booking-manager'),
                        esc_html(implode(', ', $missing_fields))
                    ),
                    'error'
                );
            }
        }

        public function show_custom_fields_in_cart_item($cart_item) {
            $values = isset($cart_item['mpwpb_custom_form_values']) && is_array($cart_item['mpwpb_custom_form_values'])
                ? $cart_item['mpwpb_custom_form_values']
                : array();

            if (empty($values)) {
                return;
            }
            ?>
            <div class="dLayout_xs mpwpb_custom_cart_info">
                <h5 class="mB_xs"><?php esc_html_e('Additional Information', 'service-booking-manager'); ?></h5>
                <?php foreach ($values as $single_value) : ?>
                    <?php
                    $label = isset($single_value['label']) ? $single_value['label'] : '';
                    $value = isset($single_value['value']) ? $single_value['value'] : '';
                    if ($label === '' || $value === '') {
                        continue;
                    }
                    ?>
                    <div class="dFlex">
                        <h6><?php echo esc_html($label); ?>&nbsp;:&nbsp;</h6>
                        <span><?php echo esc_html($value); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php
        }

        public function save_custom_fields_in_order_item($item, $values) {
            $custom_form_values = isset($values['mpwpb_custom_form_values']) && is_array($values['mpwpb_custom_form_values'])
                ? $values['mpwpb_custom_form_values']
                : array();

            if (empty($custom_form_values)) {
                return;
            }

            foreach ($custom_form_values as $custom_value) {
                $label = isset($custom_value['label']) ? sanitize_text_field($custom_value['label']) : '';
                $value = isset($custom_value['value']) ? sanitize_text_field($custom_value['value']) : '';
                if ($label && $value) {
                    $item->add_meta_data($label, $value);
                }
            }

            $item->add_meta_data('_mpwpb_custom_form_values', $custom_form_values);
        }
    }

    new MPWPB_Custom_Form_Builder();
}
