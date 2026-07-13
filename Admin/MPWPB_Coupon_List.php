<?php
	/*
   * @Author 		engr.sumonazma@gmail.com
   * Copyright: 	mage-people.com
   */
	if (!defined('ABSPATH')) {
		die;
	} // Cannot access pages directly.
	if (!class_exists('MPWPB_Coupon_List')) {
		class MPWPB_Coupon_List {
			public function __construct() {
				add_action('admin_menu', [$this, 'coupon_list_menu']);
				add_action('admin_action_mpwpb_coupon_duplicate', [$this, 'duplicate_coupon']);
			}
			public function coupon_list_menu() {
				add_submenu_page('edit.php?post_type=' . MPWPB_Function::get_cpt(), esc_html__('Coupons', 'service-booking-manager'), esc_html__('Coupons', 'service-booking-manager'), 'manage_options', 'mpwpb_coupon_list', [$this, 'coupon_list'], 20);
			}
			public function coupon_list() {
				?>
				<div class="wrap">
					<div class="mpwpb_style mpwpb_order_filter_area">
						<div id="mpwpb_coupon_list_result">
							<?php $this->coupon_list_result(); ?>
						</div>
					</div>
				</div>
				<style>
					#update-nag, .update-nag {display: none;}
				</style>
				<?php
			}
			public function coupon_list_result() {
				include(MPWPB_Function::template_path('layout/coupon_list.php'));
			}
			public function duplicate_coupon() {
				if (!isset($_GET['post_id']) || !isset($_GET['_wpnonce']) ||
					!wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'mpwpb_coupon_duplicate_' . sanitize_text_field($_GET['post_id']))
				) {
					wp_die('Invalid request (missing or invalid nonce).');
				}
				if (!current_user_can('manage_options')) {
					wp_die('You are not allowed to do this.');
				}
				$post_id = (int) sanitize_text_field(wp_unslash($_GET['post_id']));
				$post = get_post($post_id);
				if (!$post || $post->post_type !== 'mpwpb_coupon') {
					wp_die('Invalid coupon.');
				}
				$new_post_id = wp_insert_post([
					'post_title' => $post->post_title . ' (Copy)',
					'post_status' => 'draft',
					'post_type' => 'mpwpb_coupon',
					'post_author' => get_current_user_id(),
				]);
				if (is_wp_error($new_post_id) || !$new_post_id) {
					wp_die('Failed to duplicate coupon.');
				}
				$meta = get_post_meta($post_id);
				foreach ($meta as $key => $values) {
					if ($key === 'mpwpb_coupon_code' || $key === 'mpwpb_coupon_usage_count') {
						continue; // code must stay unique; a copy starts with zero usage
					}
					foreach ($values as $value) {
						add_post_meta($new_post_id, $key, maybe_unserialize($value));
					}
				}
				update_post_meta($new_post_id, 'mpwpb_coupon_usage_count', 0);
				wp_redirect(admin_url('post.php?action=edit&post=' . $new_post_id));
				exit;
			}
		}
		new MPWPB_Coupon_List();
	}
