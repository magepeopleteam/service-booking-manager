<?php
if (!defined('ABSPATH')) {
    die;
}

if (!class_exists('MPWPB_Custom_Form_Builder_Settings')) {
    class MPWPB_Custom_Form_Builder_Settings {

        public function __construct() {
            add_action('add_mpwpb_settings_tab_after_date', array($this, 'add_settings_tab'));
            add_action('add_mpwpb_settings_tab_content', array($this, 'render_settings_tab'), 30, 1);
            add_action('mpwpb_settings_save', array($this, 'save_settings'), 30, 1);
            add_action('add_mpwpb_admin_script', array($this, 'enqueue_assets'));
        }

        public function add_settings_tab($post_id = 0) {
            ?>
            <li data-tabs-target="#mpwpb_custom_form_builder">
                <i class="mi mi-list-task"></i><?php esc_html_e('Form Builder', 'service-booking-manager'); ?>
            </li>
            <?php
        }

        public function enqueue_assets() {
            $screen = get_current_screen();
            if (!$screen || $screen->post_type !== MPWPB_Function::get_cpt()) {
                return;
            }

            wp_enqueue_style(
                'mpwpb_custom_form_builder_admin',
                MPWPB_PLUGIN_URL . '/assets/admin/mpwpb_custom_form_builder.css',
                array(),
                time()
            );

            wp_enqueue_script(
                'mpwpb_custom_form_builder_admin',
                MPWPB_PLUGIN_URL . '/assets/admin/mpwpb_custom_form_builder.js',
                array('jquery'),
                time(),
                true
            );

            wp_localize_script('mpwpb_custom_form_builder_admin', 'mpwpbCustomFormBuilder', array(
                'templates' => MPWPB_Custom_Form_Builder_Helper::get_predefined_templates(),
                'fieldTypes' => MPWPB_Custom_Form_Builder_Helper::get_allowed_field_types(),
                'i18n' => array(
                    'selectTemplate' => esc_html__('Please select a template first.', 'service-booking-manager'),
                    'templateLoaded' => esc_html__('Template fields are loaded. Save the service to apply.', 'service-booking-manager'),
                    'label' => esc_html__('Label', 'service-booking-manager'),
                    'key' => esc_html__('Field Key', 'service-booking-manager'),
                    'type' => esc_html__('Type', 'service-booking-manager'),
                    'options' => esc_html__('Options (comma separated)', 'service-booking-manager'),
                    'placeholder' => esc_html__('Placeholder', 'service-booking-manager'),
                    'required' => esc_html__('Required', 'service-booking-manager'),
                    'remove' => esc_html__('Remove', 'service-booking-manager'),
                ),
            ));
        }

        public function render_settings_tab($post_id) {
            $enabled = MPWPB_Custom_Form_Builder_Helper::is_enabled($post_id) ? 'on' : 'off';
            $enabled_checked = $enabled === 'on' ? 'checked' : '';
            $active_class = $enabled === 'on' ? 'mActive' : '';

            $saved_template = get_post_meta($post_id, 'mpwpb_custom_form_template', true);
            if (!$saved_template) {
                $saved_template = 'custom';
            }

            $field_rows = MPWPB_Custom_Form_Builder_Helper::get_fields($post_id);
            $field_types = MPWPB_Custom_Form_Builder_Helper::get_allowed_field_types();
            $templates = MPWPB_Custom_Form_Builder_Helper::get_predefined_templates();
            ?>
            <div class="tabsItem mpwpb_custom_form_builder_settings" data-tabs="#mpwpb_custom_form_builder">
                <header>
                    <h2><?php esc_html_e('Custom Form Builder', 'service-booking-manager'); ?></h2>
                    <span><?php esc_html_e('Build service-specific fields and reuse predefined templates.', 'service-booking-manager'); ?></span>
                </header>

                <section>
                    <label class="label">
                        <div>
                            <p><?php esc_html_e('Enable Custom Form', 'service-booking-manager'); ?></p>
                            <span><?php esc_html_e('When enabled, these fields appear in frontend booking flow.', 'service-booking-manager'); ?></span>
                        </div>
                        <div>
                            <?php MPWPB_Custom_Layout::switch_button('mpwpb_custom_form_enable', $enabled_checked); ?>
                        </div>
                    </label>
                </section>

                <section class="<?php echo esc_attr($active_class); ?>" data-collapse="#mpwpb_custom_form_enable">
                    <label class="label mpwpb-cfb-template-row">
                        <div>
                            <p><?php esc_html_e('Predefined Template', 'service-booking-manager'); ?></p>
                            <span><?php esc_html_e('Load common field setups with one click.', 'service-booking-manager'); ?></span>
                        </div>
                        <div class="mpwpb-cfb-template-control">
                            <select name="mpwpb_custom_form_template" class="mpwpb-cfb-template-select">
                                <option value="custom" <?php selected($saved_template, 'custom'); ?>><?php esc_html_e('Custom', 'service-booking-manager'); ?></option>
                                <?php foreach ($templates as $template_key => $template_data) : ?>
                                    <option value="<?php echo esc_attr($template_key); ?>" <?php selected($saved_template, $template_key); ?>>
                                        <?php echo esc_html($template_data['label']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="button" class="button mpwpb-cfb-apply-template"><?php esc_html_e('Load Template', 'service-booking-manager'); ?></button>
                        </div>
                    </label>

                    <div class="mpwpb-cfb-table-wrap">
                        <table class="table mpwpb-cfb-table">
                            <thead>
                            <tr>
                                <th><?php esc_html_e('Label', 'service-booking-manager'); ?></th>
                                <th><?php esc_html_e('Field Key', 'service-booking-manager'); ?></th>
                                <th><?php esc_html_e('Type', 'service-booking-manager'); ?></th>
                                <th><?php esc_html_e('Options (comma separated)', 'service-booking-manager'); ?></th>
                                <th><?php esc_html_e('Placeholder', 'service-booking-manager'); ?></th>
                                <th><?php esc_html_e('Required', 'service-booking-manager'); ?></th>
                                <th><?php esc_html_e('Action', 'service-booking-manager'); ?></th>
                            </tr>
                            </thead>
                            <tbody class="mpwpb-cfb-rows">
                            <?php
                            if (!empty($field_rows)) {
                                foreach ($field_rows as $index => $field_row) {
                                    $this->render_field_row($index, $field_row, $field_types);
                                }
                            }
                            ?>
                            </tbody>
                        </table>

                        <button type="button" class="button mpwpb-cfb-add-row"><?php esc_html_e('Add Field', 'service-booking-manager'); ?></button>
                        <p class="description">
                            <?php esc_html_e('Use options for Select/Radio/Checkbox fields. Example: Option 1, Option 2, Option 3', 'service-booking-manager'); ?>
                        </p>
                    </div>
                </section>
            </div>
            <?php
        }

        private function render_field_row($index, $field, $field_types) {
            $label = isset($field['label']) ? $field['label'] : '';
            $key = isset($field['key']) ? $field['key'] : '';
            $type = isset($field['type']) ? $field['type'] : 'text';
            $options = isset($field['options']) ? $field['options'] : '';
            $placeholder = isset($field['placeholder']) ? $field['placeholder'] : '';
            $required = isset($field['required']) && (string) $field['required'] === '1';
            ?>
            <tr class="mpwpb-cfb-row" data-index="<?php echo esc_attr($index); ?>">
                <td>
                    <input type="text" class="regular-text" name="mpwpb_custom_form_fields[<?php echo esc_attr($index); ?>][label]" value="<?php echo esc_attr($label); ?>" />
                </td>
                <td>
                    <input type="text" class="regular-text mpwpb-cfb-key" name="mpwpb_custom_form_fields[<?php echo esc_attr($index); ?>][key]" value="<?php echo esc_attr($key); ?>" />
                </td>
                <td>
                    <select name="mpwpb_custom_form_fields[<?php echo esc_attr($index); ?>][type]">
                        <?php foreach ($field_types as $field_type_key => $field_type_label) : ?>
                            <option value="<?php echo esc_attr($field_type_key); ?>" <?php selected($type, $field_type_key); ?>>
                                <?php echo esc_html($field_type_label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
                <td>
                    <input type="text" class="regular-text" name="mpwpb_custom_form_fields[<?php echo esc_attr($index); ?>][options]" value="<?php echo esc_attr($options); ?>" />
                </td>
                <td>
                    <input type="text" class="regular-text" name="mpwpb_custom_form_fields[<?php echo esc_attr($index); ?>][placeholder]" value="<?php echo esc_attr($placeholder); ?>" />
                </td>
                <td>
                    <label>
                        <input type="checkbox" name="mpwpb_custom_form_fields[<?php echo esc_attr($index); ?>][required]" value="1" <?php checked($required); ?> />
                    </label>
                </td>
                <td>
                    <button type="button" class="button-link-delete mpwpb-cfb-remove-row"><?php esc_html_e('Remove', 'service-booking-manager'); ?></button>
                </td>
            </tr>
            <?php
        }

        public function save_settings($post_id) {
            $enabled = isset($_POST['mpwpb_custom_form_enable']) ? 'on' : 'off';
            update_post_meta($post_id, 'mpwpb_custom_form_enable', $enabled);

            $template = isset($_POST['mpwpb_custom_form_template']) ? sanitize_key(wp_unslash($_POST['mpwpb_custom_form_template'])) : 'custom';
            update_post_meta($post_id, 'mpwpb_custom_form_template', $template);

            $submitted_fields = isset($_POST['mpwpb_custom_form_fields']) ? wp_unslash($_POST['mpwpb_custom_form_fields']) : array();
            $normalized_fields = MPWPB_Custom_Form_Builder_Helper::normalize_fields($submitted_fields);
            if (empty($normalized_fields) && $template !== 'custom') {
                $normalized_fields = MPWPB_Custom_Form_Builder_Helper::get_template_fields($template);
            }

            update_post_meta($post_id, 'mpwpb_custom_form_fields', $normalized_fields);
        }
    }

    new MPWPB_Custom_Form_Builder_Settings();
}
