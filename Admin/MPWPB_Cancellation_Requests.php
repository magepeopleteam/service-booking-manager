<?php
	/*
	 * Administrator review screen for customer cancellation requests.
	 */
	if (!defined('ABSPATH')) {
		die;
	}

	if (!class_exists('MPWPB_Cancellation_Requests')) {
		class MPWPB_Cancellation_Requests {
			const PAGE_SLUG = 'mpwpb_cancellation_requests';

			public function __construct() {
				add_action('admin_menu', array($this, 'add_menu'), 30);
				add_action('admin_post_mpwpb_review_cancellation', array($this, 'review_request'));
				add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
			}

			public function add_menu(): void {
				$count = $this->count_requests(MPWPB_Cancellation::STATUS_PENDING);
				$label = esc_html__('Cancellation Requests', 'service-booking-manager');
				if ($count) {
					$label .= ' <span class="awaiting-mod count-' . absint($count) . '"><span class="pending-count">' . absint($count) . '</span></span>';
				}
				add_submenu_page(
					'edit.php?post_type=' . MPWPB_Function::get_cpt(),
					esc_html__('Cancellation Requests', 'service-booking-manager'),
					$label,
					'manage_options',
					self::PAGE_SLUG,
					array($this, 'render_page')
				);
			}

			public function enqueue_assets(): void {
				if (!isset($_GET['page']) || sanitize_key(wp_unslash($_GET['page'])) !== self::PAGE_SLUG) {
					return;
				}
				wp_enqueue_style('mpwpb-cancellation-requests', MPWPB_PLUGIN_URL . '/assets/admin/mpwpb-cancellation-requests.css', array(), MPWPB_VERSION);
			}

			private function count_requests($status): int {
				global $wpdb;
				return (int) $wpdb->get_var($wpdb->prepare(
					"SELECT COUNT(DISTINCT p.ID)
					 FROM {$wpdb->posts} p
					 INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
					 WHERE p.post_type = 'mpwpb_booking'
					 AND pm.meta_key = 'mpwpb_cancellation_status'
					 AND pm.meta_value = %s",
					$status
				));
			}

			private function get_requests($status): array {
				global $wpdb;
				$where = '';
				$params = array();
				if ($status !== 'all') {
					$where = ' AND pm.meta_value = %s';
					$params[] = $status;
				}
				$sql = "SELECT DISTINCT p.ID
					FROM {$wpdb->posts} p
					INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
					WHERE p.post_type = 'mpwpb_booking'
					AND pm.meta_key = 'mpwpb_cancellation_status'{$where}
					ORDER BY p.ID DESC LIMIT 200";
				if ($params) {
					$sql = $wpdb->prepare($sql, $params);
				}
				return array_map('intval', (array) $wpdb->get_col($sql));
			}

			public function render_page(): void {
				if (!current_user_can('manage_options')) {
					wp_die(esc_html__('You do not have permission to review cancellation requests.', 'service-booking-manager'));
				}
				$allowed = array('pending', 'approved', 'rejected', 'all');
				$status = isset($_GET['status']) ? sanitize_key(wp_unslash($_GET['status'])) : 'pending';
				if (!in_array($status, $allowed, true)) {
					$status = 'pending';
				}
				$requests = $this->get_requests($status);
				$notice = isset($_GET['mpwpb_notice']) ? sanitize_key(wp_unslash($_GET['mpwpb_notice'])) : '';
				?>
				<div class="wrap mpwpb-cancel-admin">
					<div class="mpwpb-cancel-admin__header">
						<div>
							<h1><?php esc_html_e('Cancellation Requests', 'service-booking-manager'); ?></h1>
							<p><?php esc_html_e('Review customer requests before a booking and its order are cancelled.', 'service-booking-manager'); ?></p>
						</div>
						<span class="mpwpb-cancel-admin__pending"><?php echo esc_html(sprintf(_n('%d pending request', '%d pending requests', $this->count_requests('pending'), 'service-booking-manager'), $this->count_requests('pending'))); ?></span>
					</div>

					<?php if ($notice === 'approved') : ?>
						<div class="notice notice-success is-dismissible"><p><?php esc_html_e('Cancellation approved. The booking/order was cancelled and the customer was emailed.', 'service-booking-manager'); ?></p></div>
					<?php elseif ($notice === 'approved_email_failed') : ?>
						<div class="notice notice-warning is-dismissible"><p><?php esc_html_e('Cancellation approved and the booking/order was cancelled, but WordPress could not hand off the customer email. Configure a working SMTP transport and contact the customer manually.', 'service-booking-manager'); ?></p></div>
					<?php elseif ($notice === 'rejected') : ?>
						<div class="notice notice-success is-dismissible"><p><?php esc_html_e('Cancellation request rejected. The booking remains active and the customer was emailed.', 'service-booking-manager'); ?></p></div>
					<?php elseif ($notice === 'rejected_email_failed') : ?>
						<div class="notice notice-warning is-dismissible"><p><?php esc_html_e('The request was rejected and the booking remains active, but WordPress could not hand off the customer email. Configure a working SMTP transport and contact the customer manually.', 'service-booking-manager'); ?></p></div>
					<?php elseif ($notice === 'error') : ?>
						<div class="notice notice-error is-dismissible"><p><?php esc_html_e('The request could not be updated. It may already have been reviewed.', 'service-booking-manager'); ?></p></div>
					<?php endif; ?>

					<nav class="nav-tab-wrapper">
						<?php foreach (array('pending' => __('Pending', 'service-booking-manager'), 'approved' => __('Approved', 'service-booking-manager'), 'rejected' => __('Rejected', 'service-booking-manager'), 'all' => __('All', 'service-booking-manager')) as $key => $label) : ?>
							<a class="nav-tab <?php echo $status === $key ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url(add_query_arg(array('post_type' => MPWPB_Function::get_cpt(), 'page' => self::PAGE_SLUG, 'status' => $key), admin_url('edit.php'))); ?>"><?php echo esc_html($label); ?></a>
						<?php endforeach; ?>
					</nav>

					<?php if (!$requests) : ?>
						<div class="mpwpb-cancel-admin__empty"><span class="dashicons dashicons-yes-alt"></span><h2><?php esc_html_e('No cancellation requests here', 'service-booking-manager'); ?></h2><p><?php esc_html_e('New customer requests will appear on this screen.', 'service-booking-manager'); ?></p></div>
					<?php else : ?>
						<div class="mpwpb-cancel-admin__list">
							<?php foreach ($requests as $booking_id) : $this->render_request($booking_id); endforeach; ?>
						</div>
					<?php endif; ?>
				</div>
				<?php
			}

			private function render_request($booking_id): void {
				$status = MPWPB_Cancellation::get_status($booking_id);
				$service_id = (int) get_post_meta($booking_id, 'mpwpb_id', true);
				$order_id = (int) get_post_meta($booking_id, 'mpwpb_order_id', true);
				$customer = (string) get_post_meta($booking_id, 'mpwpb_billing_name', true);
				$email = (string) get_post_meta($booking_id, 'mpwpb_billing_email', true);
				$date = (string) get_post_meta($booking_id, 'mpwpb_date', true);
				$reason = (string) get_post_meta($booking_id, 'mpwpb_cancellation_reason', true);
				$requested = (string) get_post_meta($booking_id, 'mpwpb_cancellation_requested_at', true);
				$review_note = (string) get_post_meta($booking_id, 'mpwpb_cancellation_review_note', true);
				$order_url = '';
				if (function_exists('wc_get_order')) {
					$order = wc_get_order($order_id);
					$order_url = $order ? $order->get_edit_order_url() : '';
				}
				?>
				<article class="mpwpb-cancel-request mpwpb-cancel-request--<?php echo esc_attr($status); ?>">
					<header>
						<div><span class="mpwpb-cancel-request__eyebrow"><?php echo esc_html(sprintf(__('Booking #%d', 'service-booking-manager'), $booking_id)); ?></span><h2><?php echo esc_html(get_the_title($service_id)); ?></h2></div>
						<span class="mpwpb-cancel-request__status"><?php echo esc_html(ucfirst($status)); ?></span>
					</header>
					<div class="mpwpb-cancel-request__details">
						<div><small><?php esc_html_e('Customer', 'service-booking-manager'); ?></small><strong><?php echo esc_html($customer ?: __('Customer', 'service-booking-manager')); ?></strong><a href="mailto:<?php echo esc_attr($email); ?>"><?php echo esc_html($email); ?></a></div>
						<div><small><?php esc_html_e('Appointment', 'service-booking-manager'); ?></small><strong><?php echo esc_html(MPWPB_Global_Function::date_format($date)); ?></strong><span><?php echo esc_html(MPWPB_Global_Function::date_format($date, 'time')); ?></span></div>
						<div><small><?php esc_html_e('Order', 'service-booking-manager'); ?></small><?php if ($order_url) : ?><a class="button" href="<?php echo esc_url($order_url); ?>"><?php echo esc_html(sprintf(__('Open order #%d', 'service-booking-manager'), $order_id)); ?></a><?php else : ?><strong>#<?php echo esc_html($order_id); ?></strong><?php endif; ?></div>
						<div><small><?php esc_html_e('Requested', 'service-booking-manager'); ?></small><strong><?php echo esc_html($requested ? mysql2date(get_option('date_format') . ' ' . get_option('time_format'), $requested) : '—'); ?></strong></div>
					</div>
					<div class="mpwpb-cancel-request__reason"><small><?php esc_html_e('Customer reason', 'service-booking-manager'); ?></small><p><?php echo nl2br(esc_html($reason)); ?></p></div>
					<?php if ($status === MPWPB_Cancellation::STATUS_PENDING) : ?>
						<form class="mpwpb-cancel-request__review" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
							<input type="hidden" name="action" value="mpwpb_review_cancellation">
							<input type="hidden" name="booking_id" value="<?php echo esc_attr($booking_id); ?>">
							<?php wp_nonce_field('mpwpb_review_cancellation_' . $booking_id); ?>
							<label><span><?php esc_html_e('Message to customer (optional)', 'service-booking-manager'); ?></span><textarea name="admin_note" rows="2" placeholder="<?php esc_attr_e('Add context for your decision…', 'service-booking-manager'); ?>"></textarea></label>
							<div><button class="button button-primary" type="submit" name="decision" value="approve"><?php esc_html_e('Approve cancellation', 'service-booking-manager'); ?></button><button class="button mpwpb-reject-button" type="submit" name="decision" value="reject"><?php esc_html_e('Reject request', 'service-booking-manager'); ?></button></div>
						</form>
					<?php elseif ($review_note) : ?>
						<div class="mpwpb-cancel-request__review-note"><small><?php esc_html_e('Administrator note', 'service-booking-manager'); ?></small><p><?php echo nl2br(esc_html($review_note)); ?></p></div>
					<?php endif; ?>
				</article>
				<?php
			}

			public function review_request(): void {
				if (!current_user_can('manage_options')) {
					wp_die(esc_html__('You do not have permission to review cancellation requests.', 'service-booking-manager'));
				}
				$booking_id = isset($_POST['booking_id']) ? absint($_POST['booking_id']) : 0;
				check_admin_referer('mpwpb_review_cancellation_' . $booking_id);
				$decision = isset($_POST['decision']) ? sanitize_key(wp_unslash($_POST['decision'])) : '';
				$note = isset($_POST['admin_note']) ? sanitize_textarea_field(wp_unslash($_POST['admin_note'])) : '';
				$result = $decision === 'approve'
					? MPWPB_Cancellation::approve($booking_id, $note)
					: ($decision === 'reject' ? MPWPB_Cancellation::reject($booking_id, $note) : new WP_Error('invalid_decision'));
				if (is_wp_error($result)) {
					$notice = 'error';
				} else {
					$notice = $decision === 'approve' ? 'approved' : 'rejected';
					if (get_post_meta($booking_id, 'mpwpb_cancellation_email_sent', true) !== 'yes') {
						$notice .= '_email_failed';
					}
				}
				wp_safe_redirect(add_query_arg(array('post_type' => MPWPB_Function::get_cpt(), 'page' => self::PAGE_SLUG, 'mpwpb_notice' => $notice), admin_url('edit.php')));
				exit;
			}
		}
		new MPWPB_Cancellation_Requests();
	}
