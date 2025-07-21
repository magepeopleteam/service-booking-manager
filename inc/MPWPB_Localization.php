<?php
/**
 * Service Booking Manager Localization
 *
 * Handles all localization and multi-language functionality
 *
 * @author      MagePeople Team
 * @copyright   Copyright (c) 2023, MagePeople Team
 * @package     service-booking-manager
 */

if (!defined('ABSPATH')) {
    die;
} // Cannot access pages directly.

if (!class_exists('MPWPB_Localization')) {
    class MPWPB_Localization {
        /**
         * @var MPWPB_Localization The single instance of the class
         */
        protected static $_instance = null;

        /**
         * Main Instance
         */
        public static function instance() {
            if (is_null(self::$_instance)) {
                self::$_instance = new self();
            }
            return self::$_instance;
        }

        /**
         * Constructor
         */
        public function __construct() {
            // Load text domain
            add_action('init', array($this, 'load_plugin_textdomain'));
            
            // WPML compatibility
            add_filter('wpml_register_single_string', array($this, 'register_wpml_strings'), 10, 3);
            
            // Polylang compatibility
            add_action('plugins_loaded', array($this, 'register_polylang_strings'));
            
            // Add language switcher to booking form
            add_action('mpwpb_before_booking_form', array($this, 'add_language_switcher'));
            
            // Filter translatable content
            add_filter('mpwpb_translatable_content', array($this, 'translate_content'), 10, 2);
            
            // Make dynamic strings translatable
            add_filter('mpwpb_service_name', array($this, 'translate_string'), 10, 2);
            add_filter('mpwpb_category_name', array($this, 'translate_string'), 10, 2);
            add_filter('mpwpb_sub_category_name', array($this, 'translate_string'), 10, 2);
            add_filter('mpwpb_extra_service_name', array($this, 'translate_string'), 10, 2);
            
            // Add translation metabox to service post type
            add_action('add_meta_boxes', array($this, 'add_translation_metabox'));
            
            // Save translation data
            add_action('save_post', array($this, 'save_translation_data'), 10, 2);
        }

        /**
         * Load plugin text domain
         */
        public function load_plugin_textdomain() {
            $locale = apply_filters('plugin_locale', get_locale(), 'service-booking-manager');
            
            // Look for translation in /wp-content/languages/plugins/
            load_textdomain(
                'service-booking-manager',
                WP_LANG_DIR . '/plugins/service-booking-manager-' . $locale . '.mo'
            );
            
            // Fallback to plugin's /languages/ directory
            load_plugin_textdomain(
                'service-booking-manager',
                false,
                dirname(plugin_basename(MPWPB_PLUGIN_DIR)) . '/languages/'
            );
        }

        /**
         * Register strings for WPML String Translation
         */
        public function register_wpml_strings($string, $context, $name) {
            // This filter is used by WPML to register strings
            return $string;
        }

        /**
         * Register strings for Polylang
         */
        public function register_polylang_strings() {
            if (function_exists('pll_register_string')) {
                global $wpdb;
                
                // Register service names
                $services = $wpdb->get_results(
                    "SELECT post_id, meta_value FROM {$wpdb->postmeta} 
                    WHERE meta_key = 'mpwpb_service_list'"
                );
                
                if ($services) {
                    foreach ($services as $service) {
                        $service_data = maybe_unserialize($service->meta_value);
                        if (is_array($service_data)) {
                            foreach ($service_data as $key => $data) {
                                if (isset($data['name'])) {
                                    pll_register_string(
                                        'service-name-' . $key,
                                        $data['name'],
                                        'Service Booking Manager',
                                        true
                                    );
                                }
                            }
                        }
                    }
                }
                
                // Register category names
                $categories = $wpdb->get_results(
                    "SELECT post_id, meta_value FROM {$wpdb->postmeta} 
                    WHERE meta_key = 'mpwpb_category_list'"
                );
                
                if ($categories) {
                    foreach ($categories as $category) {
                        $category_data = maybe_unserialize($category->meta_value);
                        if (is_array($category_data)) {
                            foreach ($category_data as $key => $data) {
                                if (isset($data['name'])) {
                                    pll_register_string(
                                        'category-name-' . $key,
                                        $data['name'],
                                        'Service Booking Manager',
                                        true
                                    );
                                }
                            }
                        }
                    }
                }
                
                // Register extra service names
                $extra_services = $wpdb->get_results(
                    "SELECT post_id, meta_value FROM {$wpdb->postmeta} 
                    WHERE meta_key = 'mpwpb_extra_service_list'"
                );
                
                if ($extra_services) {
                    foreach ($extra_services as $extra_service) {
                        $extra_service_data = maybe_unserialize($extra_service->meta_value);
                        if (is_array($extra_service_data)) {
                            foreach ($extra_service_data as $group_key => $group) {
                                if (isset($group['group_name'])) {
                                    pll_register_string(
                                        'extra-service-group-' . $group_key,
                                        $group['group_name'],
                                        'Service Booking Manager',
                                        true
                                    );
                                }
                                
                                if (isset($group['services']) && is_array($group['services'])) {
                                    foreach ($group['services'] as $service_key => $service) {
                                        if (isset($service['name'])) {
                                            pll_register_string(
                                                'extra-service-name-' . $service_key,
                                                $service['name'],
                                                'Service Booking Manager',
                                                true
                                            );
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                
                // Register common strings used in the plugin
                $common_strings = array(
                    'booking_form_title' => __('Book Your Service', 'service-booking-manager'),
                    'select_category' => __('Select Category', 'service-booking-manager'),
                    'select_service' => __('Select Service', 'service-booking-manager'),
                    'select_date' => __('Select Date', 'service-booking-manager'),
                    'select_time' => __('Select Time', 'service-booking-manager'),
                    'extra_services' => __('Extra Services', 'service-booking-manager'),
                    'booking_summary' => __('Booking Summary', 'service-booking-manager'),
                    'proceed_checkout' => __('Proceed to Checkout', 'service-booking-manager'),
                    'total_price' => __('Total Price', 'service-booking-manager'),
                );
                
                foreach ($common_strings as $key => $string) {
                    pll_register_string($key, $string, 'Service Booking Manager', true);
                }
            }
        }

        /**
         * Add language switcher to booking form
         */
        public function add_language_switcher() {
            // Only add if WPML or Polylang is active
            if (function_exists('icl_get_languages') || function_exists('pll_the_languages')) {
                echo '<div class="mpwpb-language-switcher">';
                
                if (function_exists('icl_get_languages')) {
                    // WPML language switcher
                    do_action('wpml_add_language_selector');
                } elseif (function_exists('pll_the_languages')) {
                    // Polylang language switcher
                    echo '<ul class="mpwpb-languages">';
                    pll_the_languages(array('show_flags' => 1, 'show_names' => 1));
                    echo '</ul>';
                }
                
                echo '</div>';
            }
        }

        /**
         * Translate dynamic content
         */
        public function translate_content($content, $context = '') {
            // If WPML is active
            if (function_exists('icl_t')) {
                return icl_t('Service Booking Manager', $context . '-' . md5($content), $content);
            }
            
            // If Polylang is active
            if (function_exists('pll__')) {
                return pll__($content);
            }
            
            return $content;
        }

        /**
         * Translate string based on context
         */
        public function translate_string($string, $context = '') {
            if (empty($string)) {
                return $string;
            }
            
            // Create a unique identifier for this string
            $string_id = $context . '-' . sanitize_title($string);
            
            // If WPML is active
            if (function_exists('apply_filters')) {
                $translated = apply_filters('wpml_translate_single_string', $string, 'Service Booking Manager', $string_id);
                if ($translated !== $string) {
                    return $translated;
                }
            }
            
            // If Polylang is active
            if (function_exists('pll__')) {
                return pll__($string);
            }
            
            // If Loco Translate or other translation plugins
            return __($string, 'service-booking-manager');
        }

        /**
         * Add translation metabox to service post type
         */
        public function add_translation_metabox() {
            add_meta_box(
                'mpwpb_translation_metabox',
                __('Translation Settings', 'service-booking-manager'),
                array($this, 'render_translation_metabox'),
                MPWPB_Function::get_cpt(),
                'normal',
                'default'
            );
        }

        /**
         * Render translation metabox
         */
        public function render_translation_metabox($post) {
            // Add nonce for security
            wp_nonce_field('mpwpb_translation_nonce', 'mpwpb_translation_nonce');
            
            // Get saved translation settings
            $translation_enabled = get_post_meta($post->ID, '_mpwpb_translation_enabled', true);
            
            ?>
            <div class="mpStyle">
                <div class="mpwpb-translation-settings-container">
                    <!-- Modern Toggle Switch -->
                    <div class="mpwpb-toggle-container">
                        <label class="mpwpb-switch">
                            <input type="checkbox" name="mpwpb_translation_enabled" value="1" <?php checked($translation_enabled, '1'); ?> />
                            <span class="mpwpb-slider"></span>
                        </label>
                        <span class="mpwpb-toggle-label"><?php _e('Enable advanced translation for this service', 'service-booking-manager'); ?></span>
                    </div>

                    <!-- Card-based Info Section -->
                    <div class="mpwpb-translation-card">
                        <div class="mpwpb-card-header">
                            <span class="mpwpb-card-icon dashicons dashicons-translation"></span>
                            <h3><?php _e('Translation Information', 'service-booking-manager'); ?></h3>
                        </div>

                        <div class="mpwpb-card-content">
                            <p class="mpwpb-description"><?php _e('When enabled, all service details will be registered for translation with WPML, Polylang, or other translation plugins.', 'service-booking-manager'); ?></p>

                            <div class="mpwpb-tips-section">
                                <h4><span class="dashicons dashicons-lightbulb"></span> <?php _e('Translation Tips:', 'service-booking-manager'); ?></h4>
                                <div class="mpwpb-tips-grid">
                                    <div class="mpwpb-tip-item">
                                        <span class="dashicons dashicons-admin-site-alt3"></span>
                                        <p><?php _e('Use WPML String Translation or Polylang Strings to translate service names, categories, and other dynamic content.', 'service-booking-manager'); ?></p>
                                    </div>
                                    <div class="mpwpb-tip-item">
                                        <span class="dashicons dashicons-search"></span>
                                        <p><?php _e('For Loco Translate, scan the plugin for translatable strings.', 'service-booking-manager'); ?></p>
                                    </div>
                                    <div class="mpwpb-tip-item">
                                        <span class="dashicons dashicons-admin-customizer"></span>
                                        <p><?php _e('Remember to translate both static text and dynamic content like service names.', 'service-booking-manager'); ?></p>
                                    </div>
                                </div>
                            </div>

                            <?php
                            // Show active translation plugins
                            $active_plugin = $this->is_translation_plugin_active();
                            if ($active_plugin) {
                                echo '<div class="mpwpb-active-plugin">';
                                echo '<span class="dashicons dashicons-yes-alt"></span>';
                                echo '<p>' . sprintf(__('Active Translation Plugin: %s', 'service-booking-manager'), ucfirst($active_plugin)) . '</p>';
                                echo '</div>';
                            }
                            ?>
                        </div>
                    </div>
                </div>

                <style>
                    /* Modern Styling for Translation Settings */
                    .mpwpb-translation-settings-container {
                        background: #fff;
                        border-radius: 8px;
                        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
                        padding: 20px;
                        max-width: 100%;
                        margin-bottom: 20px;
                    }

                    /* Toggle Switch Styling */
                    .mpwpb-toggle-container {
                        display: flex;
                        align-items: center;
                        margin-bottom: 20px;
                    }

                    .mpwpb-switch {
                        position: relative;
                        display: inline-block;
                        width: 50px;
                        height: 24px;
                        margin-right: 15px;
                    }

                    .mpwpb-switch input {
                        opacity: 0;
                        width: 0;
                        height: 0;
                    }

                    .mpwpb-slider {
                        position: absolute;
                        cursor: pointer;
                        top: 0;
                        left: 0;
                        right: 0;
                        bottom: 0;
                        background-color: #ccc;
                        transition: .4s;
                        border-radius: 24px;
                    }

                    .mpwpb-slider:before {
                        position: absolute;
                        content: "";
                        height: 16px;
                        width: 16px;
                        left: 4px;
                        bottom: 4px;
                        background-color: white;
                        transition: .4s;
                        border-radius: 50%;
                    }

                    input:checked + .mpwpb-slider {
                        background-color: #2196F3;
                    }

                    input:focus + .mpwpb-slider {
                        box-shadow: 0 0 1px #2196F3;
                    }

                    input:checked + .mpwpb-slider:before {
                        transform: translateX(26px);
                    }

                    .mpwpb-toggle-label {
                        font-weight: 600;
                        font-size: 15px;
                    }

                    /* Card Styling */
                    .mpwpb-translation-card {
                        background: #f9f9f9;
                        border-radius: 6px;
                        overflow: hidden;
                        border: 1px solid #eee;
                    }

                    .mpwpb-card-header {
                        background: linear-gradient(135deg, #4a89dc, #5d9cec);
                        color: white;
                        padding: 15px 20px;
                        display: flex;
                        align-items: center;
                    }

                    .mpwpb-card-header h3 {
                        margin: 0;
                        font-size: 16px;
                        font-weight: 500;
                    }

                    .mpwpb-card-icon {
                        margin-right: 10px;
                        font-size: 20px;
                    }

                    .mpwpb-card-content {
                        padding: 20px;
                    }

                    .mpwpb-description {
                        font-size: 14px;
                        line-height: 1.6;
                        color: #555;
                        margin-bottom: 20px;
                    }

                    /* Tips Section */
                    .mpwpb-tips-section {
                        background: white;
                        border-radius: 6px;
                        padding: 15px;
                        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
                    }

                    .mpwpb-tips-section h4 {
                        display: flex;
                        align-items: center;
                        color: #333;
                        font-size: 15px;
                        margin-top: 0;
                        margin-bottom: 15px;
                        border-bottom: 1px solid #eee;
                        padding-bottom: 10px;
                    }

                    .mpwpb-tips-section h4 .dashicons {
                        color: #f1c40f;
                        margin-right: 8px;
                    }

                    .mpwpb-tips-grid {
                        display: grid;
                        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                        gap: 15px;
                    }

                    .mpwpb-tip-item {
                        display: flex;
                        align-items: flex-start;
                        background: #f5f7fa;
                        padding: 12px;
                        border-radius: 4px;
                        border-left: 3px solid #4a89dc;
                    }

                    .mpwpb-tip-item .dashicons {
                        color: #4a89dc;
                        margin-right: 10px;
                        margin-top: 2px;
                    }

                    .mpwpb-tip-item p {
                        margin: 0;
                        font-size: 13px;
                        line-height: 1.5;
                        color: #555;
                    }

                    /* Active Plugin Indicator */
                    .mpwpb-active-plugin {
                        display: flex;
                        align-items: center;
                        margin-top: 20px;
                        background: #e8f5e9;
                        padding: 10px 15px;
                        border-radius: 4px;
                    }

                    .mpwpb-active-plugin .dashicons {
                        color: #4caf50;
                        margin-right: 10px;
                    }

                    .mpwpb-active-plugin p {
                        margin: 0;
                        color: #2e7d32;
                        font-size: 14px;
                    }
                </style>
            </div>
            <?php
        }

        /**
         * Save translation data
         */
        public function save_translation_data($post_id, $post) {
            // Check if our nonce is set and verify it
            if (!isset($_POST['mpwpb_translation_nonce']) || !wp_verify_nonce($_POST['mpwpb_translation_nonce'], 'mpwpb_translation_nonce')) {
                return;
            }
            
            // Check if user has permissions to save data
            if (!current_user_can('edit_post', $post_id)) {
                return;
            }
            
            // Check if not an autosave
            if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
                return;
            }
            
            // Check if correct post type
            if ($post->post_type !== MPWPB_Function::get_cpt()) {
                return;
            }
            
            // Save translation settings
            $translation_enabled = isset($_POST['mpwpb_translation_enabled']) ? '1' : '0';
            update_post_meta($post_id, '_mpwpb_translation_enabled', $translation_enabled);
            
            // If translation is enabled, register all strings for this service
            if ($translation_enabled === '1') {
                $this->register_service_strings_for_translation($post_id);
            }
        }

        /**
         * Register all strings for a service for translation
         */
        private function register_service_strings_for_translation($post_id) {
            // Get service data
            $service_list = get_post_meta($post_id, 'mpwpb_service_list', true);
            $category_list = get_post_meta($post_id, 'mpwpb_category_list', true);
            $extra_service_list = get_post_meta($post_id, 'mpwpb_extra_service_list', true);
            
            // Register service names
            if (is_array($service_list)) {
                foreach ($service_list as $key => $service) {
                    if (isset($service['name'])) {
                        $this->register_string_for_translation(
                            $service['name'],
                            'service-name-' . $key,
                            $post_id
                        );
                    }
                }
            }
            
            // Register category names
            if (is_array($category_list)) {
                foreach ($category_list as $key => $category) {
                    if (isset($category['name'])) {
                        $this->register_string_for_translation(
                            $category['name'],
                            'category-name-' . $key,
                            $post_id
                        );
                    }
                }
            }
            
            // Register extra service names
            if (is_array($extra_service_list)) {
                foreach ($extra_service_list as $group_key => $group) {
                    if (isset($group['group_name'])) {
                        $this->register_string_for_translation(
                            $group['group_name'],
                            'extra-service-group-' . $group_key,
                            $post_id
                        );
                    }
                    
                    if (isset($group['services']) && is_array($group['services'])) {
                        foreach ($group['services'] as $service_key => $service) {
                            if (isset($service['name'])) {
                                $this->register_string_for_translation(
                                    $service['name'],
                                    'extra-service-name-' . $service_key,
                                    $post_id
                                );
                            }
                        }
                    }
                }
            }
        }

        /**
         * Register a string for translation with available translation plugins
         */
        private function register_string_for_translation($string, $context, $post_id) {
            // WPML
            if (function_exists('icl_register_string')) {
                icl_register_string('Service Booking Manager', $context, $string);
            }
            
            // Polylang
            if (function_exists('pll_register_string')) {
                pll_register_string($context, $string, 'Service Booking Manager', true);
            }
        }

        /**
         * Get available languages
         */
        public function get_available_languages() {
            $languages = array();
            
            // WPML
            if (function_exists('icl_get_languages')) {
                $wpml_languages = icl_get_languages('skip_missing=0');
                if (!empty($wpml_languages)) {
                    foreach ($wpml_languages as $lang) {
                        $languages[$lang['language_code']] = $lang['native_name'];
                    }
                    return $languages;
                }
            }
            
            // Polylang
            if (function_exists('pll_languages_list')) {
                $pll_languages = pll_languages_list(array('fields' => 'slug'));
                $pll_names = pll_languages_list(array('fields' => 'name'));
                
                if (!empty($pll_languages)) {
                    foreach ($pll_languages as $key => $code) {
                        $languages[$code] = $pll_names[$key];
                    }
                    return $languages;
                }
            }
            
            // Default WordPress languages
            $wp_languages = get_available_languages();
            $wp_languages[] = 'en_US'; // Add English
            
            foreach ($wp_languages as $locale) {
                $languages[$locale] = $this->get_language_name_from_locale($locale);
            }
            
            return $languages;
        }

        /**
         * Get language name from locale
         */
        private function get_language_name_from_locale($locale) {
            $languages = array(
                'en_US' => 'English (United States)',
                'bn_BD' => 'Bengali',
                // Add more as needed
            );
            
            return isset($languages[$locale]) ? $languages[$locale] : $locale;
        }

        /**
         * Get current language
         */
        public function get_current_language() {
            // WPML
            if (defined('ICL_LANGUAGE_CODE')) {
                return ICL_LANGUAGE_CODE;
            }
            
            // Polylang
            if (function_exists('pll_current_language')) {
                return pll_current_language();
            }
            
            // Default WordPress
            return get_locale();
        }

        /**
         * Check if a translation plugin is active
         */
        public function is_translation_plugin_active() {
            // WPML
            if (defined('ICL_SITEPRESS_VERSION')) {
                return 'wpml';
            }
            
            // Polylang
            if (defined('POLYLANG_VERSION')) {
                return 'polylang';
            }
            
            // Loco Translate
            if (defined('LOCO_PLUGIN_VERSION')) {
                return 'loco';
            }
            
            return false;
        }
    }

    // Initialize the class
    MPWPB_Localization::instance();
}