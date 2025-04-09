<?php
if (!defined('ABSPATH')) {
    die;
} // Cannot access pages directly.

/**
 * Class MPWPB_Pro_Display_Fixer
 * Fixes display issues with uploaded files in admin order sections for the Pro version
 *
 * @since 1.0
 */
if (!class_exists('MPWPB_Pro_Display_Fixer')) {
    class MPWPB_Pro_Display_Fixer {
        public function __construct() {
            // Remove duplicate file displays
            add_action('admin_init', array($this, 'remove_duplicate_file_displays'));
            
            // Add CSS to fix display issues
            add_action('admin_head', array($this, 'add_admin_css'));
        }
        
        /**
         * Remove duplicate file displays from the order admin page
         */
        public function remove_duplicate_file_displays() {
            global $pagenow;
            
            // Only run on the order edit page
            if ($pagenow == 'post.php' && isset($_GET['post']) && get_post_type($_GET['post']) == 'shop_order') {
                // Remove the original file display functions
                remove_all_actions('woocommerce_admin_order_data_after_billing_address');
                remove_all_actions('woocommerce_admin_order_data_after_shipping_address');
                
                // Re-add our custom file display function
                add_action('woocommerce_admin_order_data_after_billing_address', array($this, 'display_uploaded_files'), 20);
            }
        }
        
        /**
         * Display uploaded files in the order admin page
         */
        public function display_uploaded_files($order) {
            // Get the order ID
            $order_id = $order->get_id();
            
            // Get all meta data for this order
            $meta_data = get_post_meta($order_id);
            
            // Find all file upload fields
            $file_fields = array();
            foreach ($meta_data as $key => $value) {
                // Only process fields that start with an underscore (custom fields)
                if (substr($key, 0, 1) === '_' && substr($key, -9) !== '_filename') {
                    // Get the value
                    $field_value = isset($value[0]) ? $value[0] : '';
                    
                    // Check if this is a URL
                    if (filter_var($field_value, FILTER_VALIDATE_URL)) {
                        // This is likely a file upload field
                        $file_fields[$key] = $field_value;
                    }
                }
            }
            
            // Group files by URL to avoid duplicates
            $unique_files = array();
            foreach ($file_fields as $key => $url) {
                if (!isset($unique_files[$url])) {
                    $unique_files[$url] = array(
                        'key' => $key,
                        'url' => $url
                    );
                }
            }
            
            // Display the files
            if (!empty($unique_files)) {
                echo '<div class="order_data_column_container">';
                echo '<div class="order_data_column">';
                echo '<h3>' . esc_html__('Uploaded Files', 'mpwpb_plugin_pro') . '</h3>';
                
                foreach ($unique_files as $file_data) {
                    $key = $file_data['key'];
                    $url = $file_data['url'];
                    
                    // Get the field name without the leading underscore
                    $field_name = substr($key, 1);
                    
                    // Get the original filename if available
                    $original_filename = get_post_meta($order_id, $key . '_filename', true);
                    
                    echo '<p class="form-field form-field-wide">';
                    echo '<strong>' . esc_html($field_name) . ':</strong><br>';
                    
                    // Get the file extension
                    $file_extension = strtolower(pathinfo($url, PATHINFO_EXTENSION));
                    
                    if (in_array($file_extension, array('jpg', 'jpeg', 'png'))) {
                        // Display image
                        echo '<img src="' . esc_url($url) . '" alt="' . esc_attr($field_name) . '" width="100" height="100"><br>';
                    }
                    
                    // Display filename
                    if (!empty($original_filename)) {
                        echo '<strong>' . esc_html__('File:', 'mpwpb_plugin_pro') . '</strong> ' . esc_html($original_filename) . '<br>';
                    } else {
                        echo '<strong>' . esc_html__('File:', 'mpwpb_plugin_pro') . '</strong> ' . esc_html(basename($url)) . '<br>';
                    }
                    
                    // Display download link
                    echo '<a class="button button-tiny button-primary" href="' . esc_url($url) . '" download>' . esc_html__('Download', 'mpwpb_plugin_pro') . '</a>';
                    
                    echo '</p>';
                }
                
                echo '</div>';
                echo '</div>';
            }
        }
        
        /**
         * Add CSS to fix display issues
         */
        public function add_admin_css() {
            global $pagenow;
            
            // Only add CSS on the order edit page
            if ($pagenow == 'post.php' && isset($_GET['post']) && get_post_type($_GET['post']) == 'shop_order') {
                echo '<style>
                    /* Hide duplicate file displays */
                    .order_data_column h3:not(:first-of-type) {
                        display: none;
                    }
                    
                    /* Improve file display layout */
                    .order_data_column .form-field {
                        margin-bottom: 15px;
                        padding-bottom: 15px;
                        border-bottom: 1px solid #eee;
                    }
                    
                    /* Make download buttons more prominent */
                    .order_data_column .button-primary {
                        margin-top: 5px;
                    }
                </style>';
            }
        }
    }
    
    // Initialize the class
    new MPWPB_Pro_Display_Fixer();
}
