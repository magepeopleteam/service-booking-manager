<?php
	/*
	 * GDPR "right to erasure" request queue + admin approval workflow.
	 *
	 * Registers a non-public mpwpb_gdpr_request CPT to hold each customer's
	 * request (created from Frontend/MPWPB_User_Dashboard.php's "Privacy &
	 * Data" section). The review/approve UI (render_requests_table()) is
	 * rendered at the bottom of the Pro plugin's "GDPR Compliance Tools"
	 * page (service-booking-manager-pro/admin/MPWPB_GDPR_Tools.php::render_gdpr_page(),
	 * guarded with class_exists('MPWPB_Gdpr_Requests') there since that
	 * plugin can't assume this one's classes are loaded) rather than on
	 * Admin/MPWPB_Gdpr_Settings.php's "GDPR" tab, so configuration (the
	 * Settings tab) and action (GDPR Tools) each have one place.
	 *
	 * Approving actually performs the requested change (execute_deletion()).
	 * There is no persistent "customer" entity in this plugin -- mpwpb_booking
	 * / mpwpb_order / WooCommerce shop_order posts each carry their own
	 * copied billing snapshot (see Admin/MPWPB_Native_Order.php and
	 * Frontend/MPWPB_Woocommerce.php::create_bookings_from_data()) -- so
	 * every matching post is looked up by mpwpb_user_id meta, and by billing
	 * email for WooCommerce orders (which don't carry mpwpb_user_id). The
	 * WordPress account/login itself is never deleted by either strategy,
	 * only the plugin's own profile fields and booking/order records.
	 */
	if (!defined('ABSPATH')) {
		die;
	} // Cannot access pages directly.
	if (!class_exists('MPWPB_Gdpr_Requests')) {
		class MPWPB_Gdpr_Requests {
			const CPT = 'mpwpb_gdpr_request';
			const STRATEGY_KEEP = 'keep_accounting';
			const STRATEGY_REMOVE = 'remove_everything';

			public function __construct() {
				add_action('init', array($this, 'register_cpt'));
				add_action('init', array($this, 'register_statuses'));
				add_action('wp_ajax_mpwpb_gdpr_resolve_request', array($this, 'ajax_resolve_request'));
			}

			public function register_cpt(): void {
				register_post_type(self::CPT, array(
					'label' => esc_html__('GDPR Data Requests', 'service-booking-manager'),
					'public' => false,
					'show_ui' => false,
					'show_in_menu' => false,
					'capability_type' => 'post',
					'supports' => array('title'),
					'rewrite' => false,
					'query_var' => false,
				));
			}

			public function register_statuses(): void {
				register_post_status('mpwpb_pending', array(
					'label' => _x('Pending', 'GDPR request status', 'service-booking-manager'),
					'public' => false,
					'internal' => true,
					'exclude_from_search' => true,
				));
				register_post_status('mpwpb_approved', array(
					'label' => _x('Approved', 'GDPR request status', 'service-booking-manager'),
					'public' => false,
					'internal' => true,
					'exclude_from_search' => true,
				));
				register_post_status('mpwpb_rejected', array(
					'label' => _x('Rejected', 'GDPR request status', 'service-booking-manager'),
					'public' => false,
					'internal' => true,
					'exclude_from_search' => true,
				));
			}

			/**
			 * Called by Frontend/MPWPB_User_Dashboard.php when a logged-in
			 * customer submits a data request. Refuses a second request
			 * while one is already pending for that user, so the queue
			 * can't be spammed.
			 *
			 * @param array $sub_options {profile,phone,address,notes} => bool, only meaningful for STRATEGY_KEEP.
			 * @return int|WP_Error New request post ID, or WP_Error on failure.
			 */
			public static function create_request(int $user_id, string $strategy, array $sub_options) {
				if (self::has_pending_request($user_id)) {
					return new WP_Error('mpwpb_gdpr_pending_exists', esc_html__('You already have a pending data request awaiting admin review.', 'service-booking-manager'));
				}
				if (!in_array($strategy, array(self::STRATEGY_KEEP, self::STRATEGY_REMOVE), true)) {
					return new WP_Error('mpwpb_gdpr_invalid_strategy', esc_html__('Please choose a valid request type.', 'service-booking-manager'));
				}
				$user = get_userdata($user_id);
				$request_id = wp_insert_post(array(
					'post_type' => self::CPT,
					'post_status' => 'mpwpb_pending',
					'post_title' => sprintf(
						/* translators: %s: customer display name or ID */
						esc_html__('GDPR request from %s', 'service-booking-manager'),
						$user ? $user->display_name : $user_id
					),
					'post_author' => $user_id,
				));
				if (is_wp_error($request_id) || !$request_id) {
					return new WP_Error('mpwpb_gdpr_create_failed', esc_html__('Could not submit your request. Please try again.', 'service-booking-manager'));
				}
				update_post_meta($request_id, 'mpwpb_gdpr_strategy', $strategy);
				foreach (array('profile', 'phone', 'address', 'notes') as $field) {
					update_post_meta($request_id, 'mpwpb_gdpr_delete_' . $field, !empty($sub_options[$field]) ? 'yes' : 'no');
				}
				return (int) $request_id;
			}

			public static function has_pending_request(int $user_id): bool {
				$existing = get_posts(array(
					'post_type' => self::CPT,
					'post_status' => 'mpwpb_pending',
					'author' => $user_id,
					'posts_per_page' => 1,
					'fields' => 'ids',
				));
				return !empty($existing);
			}

			/** Most recent request (any status) for this user, or null. Used by the dashboard to show current status. */
			public static function get_latest_request_for_user(int $user_id) {
				$posts = get_posts(array(
					'post_type' => self::CPT,
					'post_status' => array('mpwpb_pending', 'mpwpb_approved', 'mpwpb_rejected'),
					'author' => $user_id,
					'posts_per_page' => 1,
					'orderby' => 'date',
					'order' => 'DESC',
				));
				return $posts ? $posts[0] : null;
			}

			public function ajax_resolve_request(): void {
				if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'mpwpb_gdpr_admin_nonce')) {
					wp_send_json_error(array('message' => esc_html__('Invalid request.', 'service-booking-manager')));
				}
				if (!current_user_can('manage_options')) {
					wp_send_json_error(array('message' => esc_html__('You do not have permission to do this.', 'service-booking-manager')));
				}
				$request_id = isset($_POST['request_id']) ? absint($_POST['request_id']) : 0;
				$action = isset($_POST['gdpr_action']) ? sanitize_key(wp_unslash($_POST['gdpr_action'])) : '';
				if (!$request_id || get_post_type($request_id) !== self::CPT) {
					wp_send_json_error(array('message' => esc_html__('Request not found.', 'service-booking-manager')));
				}
				if (get_post_status($request_id) !== 'mpwpb_pending') {
					wp_send_json_error(array('message' => esc_html__('This request has already been resolved.', 'service-booking-manager')));
				}
				if ($action === 'approve') {
					self::execute_deletion($request_id);
					wp_update_post(array('ID' => $request_id, 'post_status' => 'mpwpb_approved'));
				} elseif ($action === 'reject') {
					wp_update_post(array('ID' => $request_id, 'post_status' => 'mpwpb_rejected'));
				} else {
					wp_send_json_error(array('message' => esc_html__('Unknown action.', 'service-booking-manager')));
				}
				update_post_meta($request_id, 'mpwpb_gdpr_resolved_by', get_current_user_id());
				update_post_meta($request_id, 'mpwpb_gdpr_resolved_date', current_time('mysql'));
				wp_send_json_success(array('status' => get_post_status($request_id)));
			}

			/**
			 * Performs the approved change. STRATEGY_REMOVE hard-deletes every
			 * matching booking/order (native and WooCommerce); STRATEGY_KEEP
			 * only redacts the specific fields the customer opted into,
			 * leaving the records themselves in place for accounting. Under
			 * both strategies, "profile" only clears the plugin's own extra
			 * user-meta fields (phone/address/preferences) -- never the core
			 * WordPress account (name/email/login).
			 */
			public static function execute_deletion(int $request_id): void {
				$user_id = (int) get_post_field('post_author', $request_id);
				if (!$user_id) {
					return;
				}
				$strategy = get_post_meta($request_id, 'mpwpb_gdpr_strategy', true);
				$user = get_userdata($user_id);
				$email = $user ? $user->user_email : '';

				$booking_ids = self::find_posts_by_user('mpwpb_booking', $user_id, $email);
				$order_ids = self::find_posts_by_user('mpwpb_order', $user_id, $email);
				$wc_order_ids = ($email && MPWPB_Global_Function::is_wc_payment_mode() && function_exists('wc_get_orders'))
					? wc_get_orders(array('billing_email' => $email, 'limit' => -1, 'return' => 'ids'))
					: array();

				if ($strategy === self::STRATEGY_REMOVE) {
					foreach (array_merge($booking_ids, $order_ids) as $post_id) {
						wp_delete_post($post_id, true);
					}
					foreach ($wc_order_ids as $wc_order_id) {
						$order = wc_get_order($wc_order_id);
						if ($order) {
							$order->delete(true);
						}
					}
					self::clear_profile_fields($user_id);
					return;
				}

				// STRATEGY_KEEP: redact only the fields the customer selected; the booking/order records stay.
				$delete_phone = get_post_meta($request_id, 'mpwpb_gdpr_delete_phone', true) === 'yes';
				$delete_address = get_post_meta($request_id, 'mpwpb_gdpr_delete_address', true) === 'yes';
				$delete_notes = get_post_meta($request_id, 'mpwpb_gdpr_delete_notes', true) === 'yes';
				$delete_profile = get_post_meta($request_id, 'mpwpb_gdpr_delete_profile', true) === 'yes';

				foreach (array_merge($booking_ids, $order_ids) as $post_id) {
					if ($delete_phone) {
						update_post_meta($post_id, 'mpwpb_billing_phone', '');
					}
					if ($delete_address) {
						// mpwpb_booking uses a single combined field; mpwpb_order (native
						// checkout) splits it into two -- clear whichever exist.
						update_post_meta($post_id, 'mpwpb_billing_address', '');
						update_post_meta($post_id, 'mpwpb_billing_address_1', '');
						update_post_meta($post_id, 'mpwpb_billing_address_2', '');
					}
					if ($delete_notes) {
						update_post_meta($post_id, 'mpwpb_booking_notes', '');
					}
				}
				if ($delete_notes) {
					foreach ($wc_order_ids as $wc_order_id) {
						$order = wc_get_order($wc_order_id);
						if ($order) {
							$order->set_customer_note('');
							$order->save();
						}
					}
				}
				if ($delete_profile) {
					self::clear_profile_fields($user_id);
				}
			}

			private static function find_posts_by_user(string $post_type, int $user_id, string $email): array {
				$meta_query = array(
					'relation' => 'OR',
					array('key' => 'mpwpb_user_id', 'value' => $user_id),
				);
				if ($email) {
					$meta_query[] = array('key' => 'mpwpb_billing_email', 'value' => $email);
				}
				return get_posts(array(
					'post_type' => $post_type,
					'post_status' => 'any',
					'posts_per_page' => -1,
					'fields' => 'ids',
					'meta_query' => $meta_query,
				));
			}

			/** Clears the plugin's own extra profile fields; never touches the WP account/login itself. */
			private static function clear_profile_fields(int $user_id): void {
				foreach (array('billing_phone', 'billing_address_1', 'billing_city', 'mpwpb_service_preferences') as $meta_key) {
					delete_user_meta($user_id, $meta_key);
				}
			}

			/**
			 * Renders the request review/approve table. Called inline at the
			 * bottom of Admin/MPWPB_Gdpr_Settings.php's "GDPR" tab (there is
			 * no separate "GDPR Tools" admin menu/page) -- that tab is
			 * already manage_options-gated by the Settings screen itself,
			 * but the capability check here is kept anyway in case this is
			 * ever called from elsewhere.
			 */
			public static function render_requests_table(): void {
				if (!current_user_can('manage_options')) {
					return;
				}
				$requests = get_posts(array(
					'post_type' => self::CPT,
					'post_status' => array('mpwpb_pending', 'mpwpb_approved', 'mpwpb_rejected'),
					'posts_per_page' => 100,
					'orderby' => 'date',
					'order' => 'DESC',
				));
				$option_labels = array(
					'profile' => esc_html__('Profile', 'service-booking-manager'),
					'phone' => esc_html__('Phone', 'service-booking-manager'),
					'address' => esc_html__('Address', 'service-booking-manager'),
					'notes' => esc_html__('Notes', 'service-booking-manager'),
				);
				?>
				<div class="mpwpb-gdpr-requests">
					<h3><?php esc_html_e('Customer Data Requests', 'service-booking-manager'); ?></h3>
					<p class="description"><?php esc_html_e('Customer-submitted data requests wait here for your approval before anything is changed.', 'service-booking-manager'); ?></p>
					<table class="wp-list-table widefat fixed striped" style="margin-top:16px;">
						<thead>
							<tr>
								<th><?php esc_html_e('Customer', 'service-booking-manager'); ?></th>
								<th><?php esc_html_e('Requested', 'service-booking-manager'); ?></th>
								<th><?php esc_html_e('Strategy', 'service-booking-manager'); ?></th>
								<th><?php esc_html_e('Options', 'service-booking-manager'); ?></th>
								<th><?php esc_html_e('Status', 'service-booking-manager'); ?></th>
								<th><?php esc_html_e('Action', 'service-booking-manager'); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php if (empty($requests)) : ?>
								<tr><td colspan="6"><?php esc_html_e('No data requests yet.', 'service-booking-manager'); ?></td></tr>
							<?php endif; ?>
							<?php foreach ($requests as $request) :
								$req_user = get_userdata((int) $request->post_author);
								$strategy = get_post_meta($request->ID, 'mpwpb_gdpr_strategy', true);
								$status = $request->post_status;
								$status_obj = get_post_status_object($status);
								$selected_options = array();
								foreach ($option_labels as $key => $label) {
									if (get_post_meta($request->ID, 'mpwpb_gdpr_delete_' . $key, true) === 'yes') {
										$selected_options[] = $label;
									}
								}
								?>
								<tr>
									<td>
										<?php
										if ($req_user) {
											echo esc_html($req_user->display_name) . ' &lt;' . esc_html($req_user->user_email) . '&gt;';
										} else {
											esc_html_e('Unknown user', 'service-booking-manager');
										}
										?>
									</td>
									<td><?php echo esc_html(get_the_date('', $request)); ?></td>
									<td><?php echo esc_html($strategy === self::STRATEGY_REMOVE ? esc_html__('Completely remove everything', 'service-booking-manager') : esc_html__('Keep booking for accounting', 'service-booking-manager')); ?></td>
									<td><?php echo esc_html($strategy === self::STRATEGY_REMOVE ? '—' : ($selected_options ? implode(', ', $selected_options) : esc_html__('None', 'service-booking-manager'))); ?></td>
									<td><span class="mpwpb-gdpr-status mpwpb-gdpr-status-<?php echo esc_attr($status); ?>"><?php echo esc_html($status_obj ? $status_obj->label : $status); ?></span></td>
									<td>
										<?php if ($status === 'mpwpb_pending') : ?>
											<button type="button" class="button button-primary mpwpb-gdpr-approve" data-request="<?php echo esc_attr($request->ID); ?>"><?php esc_html_e('Approve', 'service-booking-manager'); ?></button>
											<button type="button" class="button mpwpb-gdpr-reject" data-request="<?php echo esc_attr($request->ID); ?>"><?php esc_html_e('Reject', 'service-booking-manager'); ?></button>
										<?php else : ?>
											&mdash;
										<?php endif; ?>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
				<style>
					.mpwpb-gdpr-status{display:inline-block;padding:2px 10px;border-radius:999px;font-size:11px;font-weight:700;background:#f0f0f1;color:#50575e;}
					.mpwpb-gdpr-status-mpwpb_pending{background:#fff6dd;color:#8a6d00;}
					.mpwpb-gdpr-status-mpwpb_approved{background:#eafaf0;color:#1c9a5b;}
					.mpwpb-gdpr-status-mpwpb_rejected{background:#fdecea;color:#b32d2e;}
				</style>
				<script>
				(function ($) {
					"use strict";
					var nonce = <?php echo wp_json_encode(wp_create_nonce('mpwpb_gdpr_admin_nonce')); ?>;
					var confirmApprove = <?php echo wp_json_encode(esc_html__('Approve this request? The selected data will be changed immediately and this cannot be undone.', 'service-booking-manager')); ?>;
					var confirmReject = <?php echo wp_json_encode(esc_html__('Reject this request? No data will be changed.', 'service-booking-manager')); ?>;
					function resolve(requestId, gdprAction, confirmText) {
						if (!window.confirm(confirmText)) {
							return;
						}
						$.post(ajaxurl, {
							action: 'mpwpb_gdpr_resolve_request',
							request_id: requestId,
							gdpr_action: gdprAction,
							nonce: nonce
						}).done(function (response) {
							if (response && response.success) {
								window.location.reload();
							} else {
								window.alert((response && response.data && response.data.message) ? response.data.message : 'Something went wrong.');
							}
						}).fail(function () {
							window.alert('Request failed. Please try again.');
						});
					}
					$(document).on('click', '.mpwpb-gdpr-approve', function () {
						resolve($(this).data('request'), 'approve', confirmApprove);
					});
					$(document).on('click', '.mpwpb-gdpr-reject', function () {
						resolve($(this).data('request'), 'reject', confirmReject);
					});
				}(jQuery));
				</script>
				<?php
			}
		}
		new MPWPB_Gdpr_Requests();
	}
