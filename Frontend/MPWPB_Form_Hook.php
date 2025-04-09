<?php
if (!defined('ABSPATH')) {
    die;
} // Cannot access pages directly.

/**
 * Class MPWPB_Form_Hook
 * Handles form-related hooks and modifications
 *
 * @since 1.0
 */
if (!class_exists('MPWPB_Form_Hook')) {
    class MPWPB_Form_Hook {
        public function __construct() {
            // Add enctype attribute to WooCommerce checkout form
            add_filter('woocommerce_checkout_form_tag', array($this, 'add_enctype_to_checkout_form'));

            // Add action to modify the checkout form directly
            add_action('woocommerce_before_checkout_form', array($this, 'modify_checkout_form_for_file_upload'), 5);

            // Add action to print JavaScript to ensure form has correct enctype
            add_action('wp_footer', array($this, 'add_checkout_form_script'));

            // Add action to modify the checkout process
            add_action('woocommerce_checkout_process', array($this, 'handle_file_upload_checkout_process'));
        }

        /**
         * Add enctype attribute to WooCommerce checkout form
         * This is necessary for file uploads to work properly
         *
         * @return string
         */
        public function add_enctype_to_checkout_form() {
            return 'enctype="multipart/form-data"';
        }

        /**
         * Modify the checkout form directly to ensure it has the correct enctype
         */
        public function modify_checkout_form_for_file_upload() {
            // Add inline script to modify the form
            echo '<script type="text/javascript">
                jQuery(document).ready(function($) {
                    $("form.checkout").attr("enctype", "multipart/form-data");
                    console.log("Modified checkout form enctype directly");
                });
            </script>';
        }

        /**
         * Add JavaScript to ensure the checkout form has the correct enctype
         * This is added to the footer to make sure it runs after the form is loaded
         */
        public function add_checkout_form_script() {
            if (is_checkout()) {
                echo '<script type="text/javascript">
                    jQuery(document).ready(function($) {
                        // Set the enctype attribute on page load
                        $("form.checkout").attr("enctype", "multipart/form-data");
                        console.log("Set checkout form enctype in footer");

                        // Also set it whenever the form is updated
                        $(document.body).on("updated_checkout", function() {
                            $("form.checkout").attr("enctype", "multipart/form-data");
                            console.log("Updated checkout form enctype after update");
                        });
                    });
                </script>';
            }
        }

        /**
         * Handle file upload during checkout process
         * This is a fallback method to ensure files are processed correctly
         */
        public function handle_file_upload_checkout_process() {
            // Log the checkout process
            error_log('WooCommerce checkout process started');
            error_log('Form method: ' . $_SERVER['REQUEST_METHOD']);
            error_log('Content-Type: ' . (isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : 'Not set'));
            error_log('All $_FILES data: ' . print_r($_FILES, true));

            // If we have file uploads, make sure they're processed correctly
            if (!empty($_FILES)) {
                error_log('Files detected in checkout process');
                // No need to do anything else here, just making sure the files are logged
            }
        }
    }

    // Initialize the class
    new MPWPB_Form_Hook();
}
