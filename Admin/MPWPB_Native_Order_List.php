<?php
	/*
	 * Free, basic "Orders" admin screen for native (non-WooCommerce) orders.
	 *
	 * Native/offline bookings are stored as mpwpb_order posts (MPWPB_Native_Order,
	 * registered with show_in_menu => false), so without this screen a free site
	 * that takes bookings with WooCommerce inactive has no way at all to see the
	 * orders customers place. This is a deliberately lightweight list + detail
	 * view -- the richer Order List / Service Queue / Service Calendar / Backend
	 * Order tools remain Pro-only (teased in MPWPB_Pro_Locked_Menus).
	 *
	 * Only registered when Pro is inactive AND WooCommerce payment mode is off:
	 *  - Pro ships its own functional Order List under its own slug, so this
	 *    would just duplicate it.
	 *  - WooCommerce mode records bookings as real WC orders, viewable on
	 *    WooCommerce's own Orders screen; no mpwpb_order posts are created there.
	 */
	if (!defined('ABSPATH')) {
		die;
	} // Cannot access pages directly.

	if (!class_exists('MPWPB_Native_Order_List')) {
		class MPWPB_Native_Order_List {
			const PER_PAGE = 20;

			public function __construct() {
				if (MPWPB_Global_Function::is_pro_active() || MPWPB_Global_Function::is_wc_payment_mode()) {
					return;
				}
				add_action('admin_menu', [$this, 'register_menu'], 12);
			}

			/** Sits right after "Service List" (position 10) so it's a first-class,
			 *  daily-use screen rather than buried at the bottom of the menu. */
			public function register_menu(): void {
				add_submenu_page(
					'edit.php?post_type=mpwpb_item',
					esc_html__('Orders', 'service-booking-manager'),
					esc_html__('Orders', 'service-booking-manager'),
					'manage_options',
					'mpwpb_orders',
					[$this, 'render_page'],
					12
				);
			}

			public function render_page(): void {
				if (!current_user_can('manage_options')) {
					wp_die(esc_html__('You do not have permission to view this page.', 'service-booking-manager'));
				}
				$order_id = isset($_GET['order']) ? absint($_GET['order']) : 0;
				echo '<div class="wrap mpwpb-orders-wrap">';
				$this->print_styles();
				if ($order_id && get_post_type($order_id) === MPWPB_Native_Order::CPT) {
					$this->render_detail($order_id);
				} else {
					$this->render_list();
				}
				echo '</div>';
			}

			/* ------------------------------------------------------------------ *
			 *  List
			 * ------------------------------------------------------------------ */
			private function render_list(): void {
				$paged = isset($_GET['paged']) ? max(1, absint($_GET['paged'])) : 1;
				$search = isset($_GET['s']) ? sanitize_text_field(wp_unslash($_GET['s'])) : '';

				$args = [
					'post_type' => MPWPB_Native_Order::CPT,
					'post_status' => 'publish',
					'posts_per_page' => self::PER_PAGE,
					'paged' => $paged,
					'orderby' => 'date',
					'order' => 'DESC',
				];
				if ($search !== '') {
					// Match on the customer name stored as the post title.
					$args['s'] = $search;
				}
				$query = new WP_Query($args);
				?>
				<h1 class="wp-heading-inline"><?php esc_html_e('Orders', 'service-booking-manager'); ?></h1>
				<hr class="wp-header-end">
				<p class="mpwpb-orders-sub"><?php esc_html_e('Bookings placed through the built-in (non-WooCommerce) checkout.', 'service-booking-manager'); ?></p>

				<form method="get" class="mpwpb-orders-searchbar">
					<input type="hidden" name="post_type" value="mpwpb_item"/>
					<input type="hidden" name="page" value="mpwpb_orders"/>
					<input type="search" name="s" value="<?php echo esc_attr($search); ?>" placeholder="<?php esc_attr_e('Search by customer name…', 'service-booking-manager'); ?>"/>
					<button type="submit" class="button"><?php esc_html_e('Search', 'service-booking-manager'); ?></button>
					<?php if ($search !== '') : ?>
						<a class="button-link mpwpb-orders-clear" href="<?php echo esc_url(admin_url('edit.php?post_type=mpwpb_item&page=mpwpb_orders')); ?>"><?php esc_html_e('Clear', 'service-booking-manager'); ?></a>
					<?php endif; ?>
				</form>

				<?php if (!$query->have_posts()) : ?>
					<div class="mpwpb-orders-empty">
						<span class="dashicons dashicons-clipboard"></span>
						<h2><?php esc_html_e('No orders yet', 'service-booking-manager'); ?></h2>
						<p><?php esc_html_e('When a customer completes the booking checkout, their order will appear here.', 'service-booking-manager'); ?></p>
					</div>
				<?php else : ?>
					<table class="widefat striped mpwpb-orders-table">
						<thead>
							<tr>
								<th class="mpwpb-col-id"><?php esc_html_e('Order', 'service-booking-manager'); ?></th>
								<th><?php esc_html_e('Customer', 'service-booking-manager'); ?></th>
								<th><?php esc_html_e('Service & Slot', 'service-booking-manager'); ?></th>
								<th><?php esc_html_e('Total', 'service-booking-manager'); ?></th>
								<th><?php esc_html_e('Payment', 'service-booking-manager'); ?></th>
								<th><?php esc_html_e('Status', 'service-booking-manager'); ?></th>
								<th><?php esc_html_e('Placed', 'service-booking-manager'); ?></th>
								<th class="mpwpb-col-actions"></th>
							</tr>
						</thead>
						<tbody>
						<?php while ($query->have_posts()) : $query->the_post();
							$oid = get_the_ID();
							$data = $this->collect_order_data($oid);
							$detail_url = admin_url('edit.php?post_type=mpwpb_item&page=mpwpb_orders&order=' . $oid);
							?>
							<tr>
								<td class="mpwpb-col-id">
									<a href="<?php echo esc_url($detail_url); ?>"><strong>#<?php echo esc_html($oid); ?></strong></a>
								</td>
								<td>
									<div class="mpwpb-cell-name"><?php echo esc_html($data['customer'] ?: '—'); ?></div>
									<?php if ($data['email']) : ?><div class="mpwpb-cell-muted"><?php echo esc_html($data['email']); ?></div><?php endif; ?>
									<?php if ($data['phone']) : ?><div class="mpwpb-cell-muted"><?php echo esc_html($data['phone']); ?></div><?php endif; ?>
								</td>
								<td>
									<div class="mpwpb-cell-name"><?php echo esc_html($data['service_title'] ?: '—'); ?></div>
									<?php if (!empty($data['slots'])) : ?>
										<div class="mpwpb-cell-muted"><?php echo esc_html(implode(' · ', array_slice($data['slots'], 0, 2)) . (count($data['slots']) > 2 ? ' +' . (count($data['slots']) - 2) : '')); ?></div>
									<?php endif; ?>
								</td>
								<td>
									<span class="mpwpb-cell-total"><?php echo wp_kses_post($data['total_html']); ?></span>
									<?php if ($data['amount_due'] > 0) : ?>
										<div class="mpwpb-cell-muted"><?php echo esc_html__('Due:', 'service-booking-manager') . ' ' . wp_kses_post($data['due_html']); ?></div>
									<?php endif; ?>
								</td>
								<td><?php echo esc_html($data['payment_label']); ?></td>
								<td><span class="mpwpb-status mpwpb-status--<?php echo esc_attr($data['status_key']); ?>"><?php echo esc_html($data['status_label']); ?></span></td>
								<td><?php echo esc_html($data['placed']); ?></td>
								<td class="mpwpb-col-actions">
									<a class="button button-small" href="<?php echo esc_url($detail_url); ?>"><?php esc_html_e('View', 'service-booking-manager'); ?></a>
								</td>
							</tr>
						<?php endwhile; ?>
						</tbody>
					</table>

					<?php
					$total_pages = (int) $query->max_num_pages;
					if ($total_pages > 1) {
						$base = admin_url('edit.php?post_type=mpwpb_item&page=mpwpb_orders');
						if ($search !== '') {
							$base = add_query_arg('s', rawurlencode($search), $base);
						}
						echo '<div class="tablenav"><div class="tablenav-pages">';
						echo wp_kses_post(paginate_links([
							'base' => $base . '%_%',
							'format' => '&paged=%#%',
							'current' => $paged,
							'total' => $total_pages,
							'prev_text' => '‹',
							'next_text' => '›',
						]));
						echo '</div></div>';
					}
					?>
				<?php endif;
				wp_reset_postdata();
			}

			/* ------------------------------------------------------------------ *
			 *  Detail
			 * ------------------------------------------------------------------ */
			private function render_detail(int $order_id): void {
				$data = $this->collect_order_data($order_id);
				$back_url = admin_url('edit.php?post_type=mpwpb_item&page=mpwpb_orders');
				?>
				<h1 class="wp-heading-inline">
					<?php echo esc_html(sprintf(/* translators: %d: order id */ __('Order #%d', 'service-booking-manager'), $order_id)); ?>
					<span class="mpwpb-status mpwpb-status--<?php echo esc_attr($data['status_key']); ?>"><?php echo esc_html($data['status_label']); ?></span>
				</h1>
				<a class="page-title-action" href="<?php echo esc_url($back_url); ?>"><?php esc_html_e('← Back to Orders', 'service-booking-manager'); ?></a>
				<hr class="wp-header-end">

				<div class="mpwpb-order-detail-grid">
					<div class="mpwpb-order-card">
						<h2><?php esc_html_e('Booking Details', 'service-booking-manager'); ?></h2>
						<div class="mpwpb-order-line"><span><?php esc_html_e('Service', 'service-booking-manager'); ?></span><strong><?php echo esc_html($data['service_title'] ?: '—'); ?></strong></div>
						<?php if (!empty($data['slots'])) : ?>
							<div class="mpwpb-order-line"><span><?php esc_html_e('Appointment', 'service-booking-manager'); ?></span><strong><?php echo esc_html(implode(', ', $data['slots'])); ?></strong></div>
						<?php endif; ?>
						<?php if (!empty($data['services'])) : ?>
							<div class="mpwpb-order-subhead"><?php esc_html_e('Services', 'service-booking-manager'); ?></div>
							<ul class="mpwpb-order-items">
								<?php foreach ($data['services'] as $line) : ?>
									<li><span><?php echo esc_html($line['name']); ?> <em>×<?php echo esc_html($line['qty']); ?></em></span><span><?php echo wp_kses_post($line['price_html']); ?></span></li>
								<?php endforeach; ?>
							</ul>
						<?php endif; ?>
						<?php if (!empty($data['extras'])) : ?>
							<div class="mpwpb-order-subhead"><?php esc_html_e('Extra Services', 'service-booking-manager'); ?></div>
							<ul class="mpwpb-order-items">
								<?php foreach ($data['extras'] as $line) : ?>
									<li><span><?php echo esc_html($line['name']); ?> <em>×<?php echo esc_html($line['qty']); ?></em></span><span><?php echo wp_kses_post($line['price_html']); ?></span></li>
								<?php endforeach; ?>
							</ul>
						<?php endif; ?>
						<div class="mpwpb-order-totals">
							<?php if ($data['tax_amount'] > 0) : ?>
								<div class="mpwpb-order-line"><span><?php esc_html_e('Tax', 'service-booking-manager'); ?></span><strong><?php echo wp_kses_post($data['tax_html']); ?></strong></div>
							<?php endif; ?>
							<div class="mpwpb-order-line mpwpb-order-line--total"><span><?php esc_html_e('Total', 'service-booking-manager'); ?></span><strong><?php echo wp_kses_post($data['total_html']); ?></strong></div>
							<?php if ($data['amount_due'] > 0) : ?>
								<div class="mpwpb-order-line"><span><?php esc_html_e('Paid', 'service-booking-manager'); ?></span><strong><?php echo wp_kses_post($data['paid_html']); ?></strong></div>
								<div class="mpwpb-order-line mpwpb-order-line--due"><span><?php esc_html_e('Balance Due', 'service-booking-manager'); ?></span><strong><?php echo wp_kses_post($data['due_html']); ?></strong></div>
							<?php endif; ?>
						</div>
					</div>

					<div class="mpwpb-order-card">
						<h2><?php esc_html_e('Customer', 'service-booking-manager'); ?></h2>
						<div class="mpwpb-order-line"><span><?php esc_html_e('Name', 'service-booking-manager'); ?></span><strong><?php echo esc_html($data['customer'] ?: '—'); ?></strong></div>
						<div class="mpwpb-order-line"><span><?php esc_html_e('Email', 'service-booking-manager'); ?></span><strong><?php echo esc_html($data['email'] ?: '—'); ?></strong></div>
						<div class="mpwpb-order-line"><span><?php esc_html_e('Phone', 'service-booking-manager'); ?></span><strong><?php echo esc_html($data['phone'] ?: '—'); ?></strong></div>
						<div class="mpwpb-order-line"><span><?php esc_html_e('Address', 'service-booking-manager'); ?></span><strong><?php echo esc_html($data['address'] ?: '—'); ?></strong></div>

						<h2 class="mpwpb-order-card-sep"><?php esc_html_e('Payment', 'service-booking-manager'); ?></h2>
						<div class="mpwpb-order-line"><span><?php esc_html_e('Method', 'service-booking-manager'); ?></span><strong><?php echo esc_html($data['payment_label']); ?></strong></div>
						<div class="mpwpb-order-line"><span><?php esc_html_e('Status', 'service-booking-manager'); ?></span><strong><?php echo esc_html($data['status_label']); ?></strong></div>
						<?php if ($data['txn_id']) : ?>
							<div class="mpwpb-order-line"><span><?php esc_html_e('Transaction', 'service-booking-manager'); ?></span><strong><?php echo esc_html($data['txn_id']); ?></strong></div>
						<?php endif; ?>
						<div class="mpwpb-order-line"><span><?php esc_html_e('Placed', 'service-booking-manager'); ?></span><strong><?php echo esc_html($data['placed']); ?></strong></div>
					</div>
				</div>
				<?php
			}

			/* ------------------------------------------------------------------ *
			 *  Data + formatting helpers
			 * ------------------------------------------------------------------ */
			private function collect_order_data(int $order_id): array {
				$item = get_post_meta($order_id, 'mpwpb_line_items', true);
				$item = is_array($item) ? $item : [];
				$service_post_id = (int) ($item['mpwpb_id'] ?? 0);

				$first = get_post_meta($order_id, 'mpwpb_billing_first_name', true);
				$last = get_post_meta($order_id, 'mpwpb_billing_last_name', true);
				$total = (float) get_post_meta($order_id, 'mpwpb_total', true);
				$tax = (float) get_post_meta($order_id, 'mpwpb_tax_amount', true);
				$paid = (float) get_post_meta($order_id, 'mpwpb_amount_paid', true);
				$due = (float) get_post_meta($order_id, 'mpwpb_amount_due', true);
				$status = (string) get_post_meta($order_id, 'mpwpb_order_status', true);
				$method = (string) get_post_meta($order_id, 'mpwpb_payment_method', true);

				$services = [];
				if (!empty($item['mpwpb_service']) && is_array($item['mpwpb_service'])) {
					foreach ($item['mpwpb_service'] as $svc) {
						$qty = max(1, (int) ($svc['qty'] ?? 1));
						$price = (float) ($svc['price'] ?? 0) * $qty;
						$services[] = [
							'name' => (string) ($svc['name'] ?? ''),
							'qty' => $qty,
							'price_html' => MPWPB_Global_Function::wc_price($service_post_id, $price),
						];
					}
				}
				$extras = [];
				if (!empty($item['mpwpb_extra_service_info']) && is_array($item['mpwpb_extra_service_info'])) {
					foreach ($item['mpwpb_extra_service_info'] as $ex) {
						$ex_qty = max(1, (int) ($ex['ex_qty'] ?? 1));
						$ex_price = (float) ($ex['ex_price'] ?? 0) * $ex_qty;
						$extras[] = [
							'name' => (string) ($ex['ex_name'] ?? ''),
							'qty' => $ex_qty,
							'price_html' => MPWPB_Global_Function::wc_price($service_post_id, $ex_price),
						];
					}
				}

				return [
					'customer' => trim($first . ' ' . $last),
					'email' => (string) get_post_meta($order_id, 'mpwpb_billing_email', true),
					'phone' => (string) get_post_meta($order_id, 'mpwpb_billing_phone', true),
					'address' => (string) get_post_meta($order_id, 'mpwpb_billing_address_1', true),
					'service_title' => $service_post_id ? get_the_title($service_post_id) : '',
					'slots' => $this->format_slots((string) ($item['mpwpb_date'] ?? '')),
					'services' => $services,
					'extras' => $extras,
					'total' => $total,
					'total_html' => MPWPB_Global_Function::wc_price($service_post_id, $total),
					'tax_amount' => $tax,
					'tax_html' => MPWPB_Global_Function::wc_price($service_post_id, $tax),
					'amount_due' => $due,
					'due_html' => MPWPB_Global_Function::wc_price($service_post_id, $due),
					'paid_html' => MPWPB_Global_Function::wc_price($service_post_id, $paid),
					'payment_label' => $this->payment_label($method),
					'status_key' => $this->status_key($status),
					'status_label' => $this->status_label($status),
					'txn_id' => (string) get_post_meta($order_id, 'mpwpb_gateway_txn_id', true),
					'placed' => get_the_date('', $order_id) . ' ' . get_the_time('', $order_id),
				];
			}

			/** Same slot formatting the front-end recap uses (comma-joined dates =
			 *  a recurring booking's individual occurrences). */
			private function format_slots(string $date_raw): array {
				if ($date_raw === '') {
					return [];
				}
				$use_24hour = MPWPB_Global_Function::get_settings('mpwpb_global_settings', 'time_format_24hour', 'no');
				$time_format = $use_24hour === 'yes' ? 'H:i' : 'g:i A';
				/* translators: %s: formatted time */
				$slot_format = sprintf(__('M j, Y \@ %s', 'service-booking-manager'), $time_format);
				$out = [];
				foreach (array_filter(array_map('trim', explode(',', $date_raw))) as $segment) {
					$ts = strtotime($segment);
					if ($ts) {
						$out[] = date_i18n($slot_format, $ts);
					}
				}
				return $out;
			}

			private function payment_label(string $method): string {
				$map = [
					'offline' => __('Offline', 'service-booking-manager'),
					'stripe' => __('Stripe', 'service-booking-manager'),
					'paypal' => __('PayPal', 'service-booking-manager'),
				];
				return $map[$method] ?? ($method !== '' ? ucfirst($method) : '—');
			}

			private function status_key(string $status): string {
				$status = strtolower(trim($status));
				$known = ['pending', 'processing', 'completed', 'cancelled', 'canceled', 'refunded', 'on-hold', 'failed'];
				return in_array($status, $known, true) ? str_replace('canceled', 'cancelled', $status) : 'default';
			}

			private function status_label(string $status): string {
				$status = strtolower(trim($status));
				$map = [
					'pending' => __('Pending', 'service-booking-manager'),
					'processing' => __('Confirmed', 'service-booking-manager'),
					'completed' => __('Completed', 'service-booking-manager'),
					'cancelled' => __('Cancelled', 'service-booking-manager'),
					'canceled' => __('Cancelled', 'service-booking-manager'),
					'refunded' => __('Refunded', 'service-booking-manager'),
					'on-hold' => __('On Hold', 'service-booking-manager'),
					'failed' => __('Failed', 'service-booking-manager'),
				];
				return $map[$status] ?? ($status !== '' ? ucwords(str_replace(['-', '_'], ' ', $status)) : __('Pending', 'service-booking-manager'));
			}

			private function print_styles(): void {
				?>
				<style>
					.mpwpb-orders-wrap .mpwpb-orders-sub{color:#646970;font-size:13px;margin:4px 0 14px;}
					.mpwpb-orders-searchbar{display:flex;gap:8px;align-items:center;margin:0 0 14px;}
					.mpwpb-orders-searchbar input[type=search]{min-width:260px;}
					.mpwpb-orders-clear{margin-left:2px;}
					.mpwpb-orders-table{margin-top:6px;}
					.mpwpb-orders-table th{font-weight:600;}
					.mpwpb-orders-table .mpwpb-col-id{width:70px;}
					.mpwpb-orders-table .mpwpb-col-actions{width:70px;text-align:right;}
					.mpwpb-cell-name{font-weight:600;color:#1d2327;}
					.mpwpb-cell-muted{color:#787c82;font-size:12px;margin-top:2px;}
					.mpwpb-cell-total{font-weight:700;color:#1d2327;}
					.mpwpb-status{display:inline-block;padding:2px 10px;border-radius:999px;font-size:11px;font-weight:700;line-height:1.7;text-transform:uppercase;letter-spacing:.02em;}
					.mpwpb-status--pending{background:#fef3c7;color:#92600a;}
					.mpwpb-status--processing{background:#dcfce7;color:#166534;}
					.mpwpb-status--completed{background:#dbeafe;color:#1e40af;}
					.mpwpb-status--cancelled{background:#fee2e2;color:#991b1b;}
					.mpwpb-status--refunded{background:#e5e7eb;color:#374151;}
					.mpwpb-status--on-hold{background:#ffedd5;color:#9a3412;}
					.mpwpb-status--failed{background:#fee2e2;color:#991b1b;}
					.mpwpb-status--default{background:#e5e7eb;color:#374151;}
					.mpwpb-orders-empty{background:#fff;border:1px solid #dcdcde;border-radius:10px;padding:48px 20px;text-align:center;margin-top:16px;}
					.mpwpb-orders-empty .dashicons{font-size:44px;width:44px;height:44px;color:#c3c4c7;}
					.mpwpb-orders-empty h2{margin:12px 0 6px;font-size:18px;}
					.mpwpb-orders-empty p{color:#787c82;margin:0;}
					.mpwpb-order-detail-grid{display:grid;grid-template-columns:1.4fr 1fr;gap:20px;margin-top:16px;align-items:start;}
					@media (max-width:1100px){.mpwpb-order-detail-grid{grid-template-columns:1fr;}}
					.mpwpb-order-card{background:#fff;border:1px solid #dcdcde;border-radius:10px;padding:20px 22px;}
					.mpwpb-order-card h2{margin:0 0 12px;font-size:15px;padding:0 0 10px;border-bottom:1px solid #f0f0f1;}
					.mpwpb-order-card-sep{margin-top:22px !important;}
					.mpwpb-order-line{display:flex;justify-content:space-between;gap:16px;padding:7px 0;font-size:13.5px;border-bottom:1px solid #f6f7f7;}
					.mpwpb-order-line:last-child{border-bottom:0;}
					.mpwpb-order-line > span{color:#787c82;flex:0 0 auto;}
					.mpwpb-order-line > strong{color:#1d2327;text-align:right;font-weight:600;}
					.mpwpb-order-line--total > strong,.mpwpb-order-line--total > span{font-size:15px;font-weight:800;color:#1d2327;}
					.mpwpb-order-line--due > strong{color:#b91c1c;}
					.mpwpb-order-subhead{margin:14px 0 4px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:#787c82;}
					.mpwpb-order-items{margin:0;}
					.mpwpb-order-items li{display:flex;justify-content:space-between;gap:16px;padding:5px 0;font-size:13px;border-bottom:1px solid #f6f7f7;}
					.mpwpb-order-items li em{font-style:normal;color:#787c82;}
					.mpwpb-order-totals{margin-top:12px;padding-top:8px;border-top:2px solid #f0f0f1;}
					.wp-heading-inline .mpwpb-status{vertical-align:middle;margin-left:8px;}
				</style>
				<?php
			}
		}
		new MPWPB_Native_Order_List();
	}
