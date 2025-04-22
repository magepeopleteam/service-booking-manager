<?php
if (!defined('ABSPATH')) {
    die;
} // Cannot access pages directly.

/**
 * Class MPWPB_Direct_Form_Modifier
 * Directly modifies the WooCommerce checkout form HTML to ensure file uploads work
 *
 * @since 1.0
 */
if (!class_exists('MPWPB_Direct_Form_Modifier')) {
    class MPWPB_Direct_Form_Modifier {
        public function __construct() {
            // Add a filter to directly modify the checkout form HTML
            add_filter('woocommerce_before_checkout_form', array($this, 'add_form_enctype_script'), 1);
            
            // Add a filter to modify the checkout process
            add_action('woocommerce_checkout_process', array($this, 'debug_checkout_process'));
            
            // Add a filter to output a direct form modification
            add_action('wp_footer', array($this, 'add_direct_form_modification'));
        }
        
        /**
         * Add a script to set the form enctype
         */
        public function add_form_enctype_script() {
            ?>
            <script type="text/javascript">
                jQuery(document).ready(function($) {
                    // Direct form modification
                    $('form.checkout').attr('enctype', 'multipart/form-data');
                    console.log('Direct form modification applied');
                    
                    // Also modify the form when it's updated
                    $(document.body).on('updated_checkout', function() {
                        $('form.checkout').attr('enctype', 'multipart/form-data');
                        console.log('Form updated, reapplied enctype');
                    });
                });
            </script>
            <?php
        }
        
        /**
         * Debug the checkout process
         */
        public function debug_checkout_process() {
            error_log('MPWPB_Direct_Form_Modifier: Checkout process started');
            error_log('REQUEST_METHOD: ' . $_SERVER['REQUEST_METHOD']);
            error_log('CONTENT_TYPE: ' . (isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : 'Not set'));
            error_log('$_FILES: ' . print_r($_FILES, true));
        }
        
        /**
         * Add a direct form modification to the footer
         */
        public function add_direct_form_modification() {
            if (is_checkout()) {
                ?>
                <script type="text/javascript">
                    jQuery(document).ready(function($) {
                        // Get the checkout form
                        var $form = $('form.checkout');
                        
                        if ($form.length) {
                            // Set the enctype attribute
                            $form.attr('enctype', 'multipart/form-data');
                            console.log('Footer script: Set enctype on checkout form');
                            
                            // Also modify the form when it's submitted
                            $form.on('submit', function() {
                                $(this).attr('enctype', 'multipart/form-data');
                                console.log('Form submitted, enctype set');
                                return true;
                            });
                        }
                    });
                </script>
                <?php
            }
        }
    }
    
    // Initialize the class
    new MPWPB_Direct_Form_Modifier();
}
