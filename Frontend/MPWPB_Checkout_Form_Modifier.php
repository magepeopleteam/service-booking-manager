<?php
if (!defined('ABSPATH')) {
    die;
} // Cannot access pages directly.

/**
 * Class MPWPB_Checkout_Form_Modifier
 * Directly modifies the WooCommerce checkout form to ensure file uploads work
 *
 * @since 1.0
 */
if (!class_exists('MPWPB_Checkout_Form_Modifier')) {
    class MPWPB_Checkout_Form_Modifier {
        public function __construct() {
            // Add a filter to modify the checkout form output
            add_filter('woocommerce_checkout_form_start', array($this, 'modify_checkout_form_start'));
            
            // Add a filter to modify the checkout form end
            add_filter('woocommerce_checkout_form_end', array($this, 'modify_checkout_form_end'));
            
            // Add a filter to modify the checkout posted data
            add_filter('woocommerce_checkout_posted_data', array($this, 'modify_checkout_posted_data'));
        }
        
        /**
         * Modify the checkout form start to add the enctype attribute
         */
        public function modify_checkout_form_start() {
            // Output a script to modify the form
            echo '<script type="text/javascript">
                jQuery(document).ready(function($) {
                    // Set the enctype attribute on the checkout form
                    $("form.checkout").attr("enctype", "multipart/form-data");
                    console.log("Modified checkout form start");
                });
            </script>';
        }
        
        /**
         * Modify the checkout form end to ensure file uploads are processed
         */
        public function modify_checkout_form_end() {
            // Output a script to ensure the form is properly submitted
            echo '<script type="text/javascript">
                jQuery(document).ready(function($) {
                    // Ensure the form has the correct enctype before submission
                    $("form.checkout").on("submit", function() {
                        $(this).attr("enctype", "multipart/form-data");
                        console.log("Form submitted with enctype set");
                    });
                });
            </script>';
        }
        
        /**
         * Modify the checkout posted data to ensure file uploads are included
         */
        public function modify_checkout_posted_data($data) {
            // Log the posted data
            error_log('WooCommerce checkout posted data: ' . print_r($data, true));
            error_log('FILES data: ' . print_r($_FILES, true));
            
            // Return the data unchanged
            return $data;
        }
    }
    
    // Initialize the class
    new MPWPB_Checkout_Form_Modifier();
}
