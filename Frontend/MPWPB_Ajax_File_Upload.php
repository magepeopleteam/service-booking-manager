<?php
if (!defined('ABSPATH')) {
    die;
} // Cannot access pages directly.

/**
 * Class MPWPB_Ajax_File_Upload
 * Handles AJAX file uploads for checkout fields
 *
 * @since 1.0
 */
if (!class_exists('MPWPB_Ajax_File_Upload')) {
    class MPWPB_Ajax_File_Upload {
        private $allowed_mime_types = array(
            'jpg|jpeg|jpe' => 'image/jpeg',
            'png' => 'image/png',
            'pdf' => 'application/pdf',
        );
        
        private $allowed_extensions = array('jpg', 'jpeg', 'png', 'pdf');
        
        public function __construct() {
            // Add AJAX actions for file upload
            add_action('wp_ajax_mpwpb_upload_checkout_file', array($this, 'handle_file_upload'));
            add_action('wp_ajax_nopriv_mpwpb_upload_checkout_file', array($this, 'handle_file_upload'));
            
            // Add script to handle file uploads
            add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        }
        
        /**
         * Enqueue scripts for file upload
         */
        public function enqueue_scripts() {
            if (is_checkout()) {
                wp_enqueue_script('mpwpb-file-upload', MPWPB_PLUGIN_URL . '/assets/checkout/front/js/mpwpb-file-upload.js', array('jquery'), time(), true);
                wp_localize_script('mpwpb-file-upload', 'mpwpb_file_upload', array(
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('mpwpb_file_upload_nonce'),
                ));
            }
        }
        
        /**
         * Handle file upload via AJAX
         */
        public function handle_file_upload() {
            // Check nonce
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mpwpb_file_upload_nonce')) {
                wp_send_json_error(array('message' => 'Invalid nonce'));
                return;
            }
            
            // Check if file was uploaded
            if (!isset($_FILES['file']) || empty($_FILES['file']['name'])) {
                wp_send_json_error(array('message' => 'No file uploaded'));
                return;
            }
            
            // Log the file upload
            error_log('AJAX file upload started');
            error_log('File data: ' . print_r($_FILES['file'], true));
            
            // Check file size
            if ($_FILES['file']['size'] <= 0) {
                wp_send_json_error(array('message' => 'File has zero size'));
                return;
            }
            
            // Create upload overrides
            $upload_overrides = array(
                'test_form' => false,
                'mimes' => $this->allowed_mime_types
            );
            
            // Use WordPress's built-in file handling
            $movefile = wp_handle_upload($_FILES['file'], $upload_overrides);
            
            if ($movefile && !isset($movefile['error'])) {
                $image_url = $movefile['url'];
                error_log('File uploaded successfully via AJAX: ' . $image_url);
                
                // Store the file in the media library for better management
                $filename = basename($movefile['file']);
                $wp_filetype = wp_check_filetype($filename, null);
                $attachment = array(
                    'post_mime_type' => $wp_filetype['type'],
                    'post_title' => sanitize_file_name($filename),
                    'post_content' => '',
                    'post_status' => 'inherit'
                );
                
                $attach_id = wp_insert_attachment($attachment, $movefile['file']);
                if (!is_wp_error($attach_id)) {
                    require_once(ABSPATH . 'wp-admin/includes/image.php');
                    $attach_data = wp_generate_attachment_metadata($attach_id, $movefile['file']);
                    wp_update_attachment_metadata($attach_id, $attach_data);
                    error_log('File added to media library with ID: ' . $attach_id);
                }
                
                // Return success response
                wp_send_json_success(array(
                    'url' => $image_url,
                    'filename' => $_FILES['file']['name'],
                    'field_name' => isset($_POST['field_name']) ? sanitize_text_field($_POST['field_name']) : '',
                ));
            } else {
                $error = isset($movefile['error']) ? $movefile['error'] : 'Unknown error';
                error_log('AJAX file upload failed: ' . $error);
                wp_send_json_error(array('message' => $error));
            }
        }
    }
    
    // Initialize the class
    new MPWPB_Ajax_File_Upload();
}
