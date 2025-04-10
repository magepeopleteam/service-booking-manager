<?php
if (!defined('ABSPATH')) {
    die;
} // Cannot access pages directly.

/**
 * Class MPWPB_File_Display_Helper
 * Handles displaying uploaded files in admin order sections
 *
 * @since 1.0
 */
if (!class_exists('MPWPB_File_Display_Helper')) {
    class MPWPB_File_Display_Helper {
        private $allowed_mime_types = array(
            'jpg|jpeg|jpe' => 'image/jpeg',
            'png' => 'image/png',
            'pdf' => 'application/pdf',
        );

        private $allowed_extensions = array('jpg', 'jpeg', 'png', 'pdf');

        // Track which files have been displayed to avoid duplicates
        private $displayed_files = array();

        public function __construct() {
            // Hook into the order display - only use one hook to avoid duplicates
            add_action('woocommerce_admin_order_data_after_billing_address', array($this, 'display_order_files'), 20);

            // Remove the original file display functions from the checkout fields helper
            add_action('init', array($this, 'remove_original_file_display'));
        }

        /**
         * Remove the original file display functions to avoid duplicates
         */
        public function remove_original_file_display() {
            // Remove the original hooks that display files
            remove_action('woocommerce_admin_order_data_after_billing_address', 'order_details', 10);
            remove_action('woocommerce_admin_order_data_after_shipping_address', 'order_details', 10);
            remove_action('woocommerce_admin_order_data_after_order_details', 'order_details', 10);
        }

        /**
         * Display uploaded files in admin order sections
         */
        public function display_order_files($order) {
            // Get the order ID
            $order_id = $order->get_id();

            // Get all meta data for this order
            $meta_data = get_post_meta($order_id);

            // Find all file upload fields
            $file_fields = array();
            foreach ($meta_data as $key => $value) {
                // Only process fields that start with an underscore (custom fields)
                if (substr($key, 0, 1) === '_') {
                    // Skip filename fields
                    if (strpos($key, '_filename') !== false) {
                        continue;
                    }

                    // Get the value
                    $field_value = isset($value[0]) ? $value[0] : '';

                    // Check if this is a URL
                    if (filter_var($field_value, FILTER_VALIDATE_URL)) {
                        // This is likely a file upload field
                        $file_fields[$key] = $field_value;
                    }
                }
            }

            // Display the files
            if (!empty($file_fields)) {
                echo '<div class="order_data_column_container">';
                echo '<div class="order_data_column">';
                echo '<h3>' . esc_html__('Uploaded Files', 'service-booking-manager') . '</h3>';

                // Group files by URL to avoid duplicates
                $unique_files = array();
                foreach ($file_fields as $key => $url) {
                    if (!isset($unique_files[$url])) {
                        $unique_files[$url] = array(
                            'keys' => array($key),
                            'url' => $url
                        );
                    } else {
                        $unique_files[$url]['keys'][] = $key;
                    }
                }

                // Display each unique file
                foreach ($unique_files as $file_data) {
                    $url = $file_data['url'];
                    $key = $file_data['keys'][0]; // Use the first key for display

                    // Get the field name without the leading underscore
                    $field_name = substr($key, 1);

                    // Get the original filename if available
                    $original_filename = get_post_meta($order_id, $key . '_filename', true);

                    // Get the file extension
                    $file_extension = strtolower(pathinfo($url, PATHINFO_EXTENSION));

                    echo '<p class="form-field form-field-wide">';
                    echo '<strong>' . esc_html($field_name) . ':</strong><br>';

                    if (in_array($file_extension, $this->allowed_extensions)) {
                        if ($file_extension !== 'pdf') {
                            // Display image
                            echo '<img src="' . esc_url($url) . '" alt="' . esc_attr($field_name) . '" width="100" height="100"><br>';
                        }

                        // Display filename
                        if (!empty($original_filename)) {
                            echo '<strong>' . esc_html__('File:', 'service-booking-manager') . '</strong> ' . esc_html($original_filename) . '<br>';
                        } else {
                            echo '<strong>' . esc_html__('File:', 'service-booking-manager') . '</strong> ' . esc_html(basename($url)) . '<br>';
                        }

                        // Display download link
                        echo '<a class="button button-tiny button-primary" href="' . esc_url($url) . '" download>' . esc_html__('Download', 'service-booking-manager') . '</a>';
                    } else {
                        // Unknown file type
                        echo '<span class="description">' . esc_html__('File uploaded but format not recognized', 'service-booking-manager') . '</span><br>';
                        echo '<a class="button button-tiny button-primary" href="' . esc_url($url) . '" download>' . esc_html__('Download File', 'service-booking-manager') . '</a>';
                    }

                    echo '</p>';
                }

                echo '</div>';
                echo '</div>';
            }
        }
    }

    // Initialize the class
    new MPWPB_File_Display_Helper();
}
