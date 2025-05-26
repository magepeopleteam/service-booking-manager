<?php

if ( ! defined( 'ABSPATH' ) ) {
    die;
}

if ( ! class_exists( 'MPWPB_Service_List' ) ) {
    class MPWPB_Service_List{
        public function __construct(){
            add_action( 'admin_menu', array( $this, 'service_list_menu' ) );
            add_action('admin_action_mpwpb_duplicate_post', [$this,'mpwpb_duplicate_post_function']);
        }
        public function service_list_menu() {
            add_submenu_page( 'edit.php?post_type=mpwpb_item', esc_html__( 'Service List', 'service-booking-manager' ), esc_html__( 'Service List', 'service-booking-manager' ), 'manage_options', 'mpwpb_service_list', array( $this, 'service_list' ),10 );
        }
        public function service_list() {?>
            <div class="wrap">
                <div class="mpwpb_style mpwpb_order_filter_area">
                    <div id="mpwpb_order_list_result">
                        <?php $this->service_list_result(); ?>
                    </div>
                </div>
            </div>
            <style>
                #update-nag, .update-nag {display: none;}
            </style>
            <?php
        }

        public function service_list_result() {
            include(MPWPB_Function::template_path('layout/service_list.php'));
        }

        function mpwpb_duplicate_post_function() {
            if ( !isset( $_GET['post_id']) || !isset($_GET['_wpnonce']) ||
                !wp_verify_nonce($_GET['_wpnonce'], 'mpwpb_duplicate_post_' . sanitize_text_field( $_GET['post_id'] ) )
            ) {
                wp_die('Invalid request (missing or invalid nonce).');
            }

            $post_id = (int)sanitize_text_field( wp_unslash( $_GET['post_id'] ) );
            $post = get_post($post_id);

            /*if (!$post || $post->post_type !== 'mpwpb_tour') {
                wp_die('Invalid post or post type.');
            }*/

            // Create new post array
            $new_post = array(
                'post_title'   => $post->post_title . ' (Copy)',
                'post_content' => $post->post_content,
                'post_status'  => 'draft',
                'post_type'    => $post->post_type,
                'post_author'  => get_current_user_id(),
            );

            // Insert new post
            $new_post_id = wp_insert_post($new_post);

            if (is_wp_error($new_post_id) || !$new_post_id) {
                wp_die('Failed to duplicate post.');
            }

            // Copy post meta
            $meta = get_post_meta($post_id);
            foreach ($meta as $key => $values) {
                foreach ($values as $value) {
                    add_post_meta($new_post_id, $key, maybe_unserialize($value));
                }
            }

            // Redirect to the edit page of the new post
            wp_redirect(admin_url('post.php?action=edit&post=' . $new_post_id));
            exit;
        }

    }

    new MPWPB_Service_List();

}