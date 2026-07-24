<?php
	/*
	 * Free, deliberately limited "Orders" admin screen for native
	 * (non-WooCommerce) orders.
	 *
	 * Native/offline bookings are stored as mpwpb_order posts (MPWPB_Native_Order,
	 * registered with show_in_menu => false), so without this screen a free site
	 * that takes bookings through the built-in Offline checkout has no way at all
	 * to see the orders customers place. What it shows is intentionally minimal --
	 * a read-only list of the essentials. Everything richer is Pro and is teased
	 * here rather than hidden:
	 *
	 *   - Statistics bar  -> blurred placeholders behind a "PRO" overlay (no real figures).
	 *   - Filter / search -> blurred, disabled inputs behind a "PRO" overlay.
	 *   - Order detail    -> locked action (PRO badge, no navigation).
	 *   - Status changes  -> locked action (status shown read-only).
	 *
	 * The real, functional versions of all of the above live in
	 * service-booking-manager-pro (Order List / Service Queue / Service Calendar /
	 * Backend Order), teased as menu items by MPWPB_Pro_Locked_Menus.
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

			private function base_url(): string {
				return admin_url('edit.php?post_type=mpwpb_item&page=mpwpb_orders');
			}

			public function render_page(): void {
				if (!current_user_can('manage_options')) {
					wp_die(esc_html__('You do not have permission to view this page.', 'service-booking-manager'));
				}
				$paged = isset($_GET['paged']) ? max(1, absint($_GET['paged'])) : 1;
				list($rows, $total) = $this->fetch_page($paged);
				$pages = max(1, (int) ceil($total / self::PER_PAGE));
				if ($paged > $pages) {
					// Clamp out-of-range page requests (e.g. a bookmarked page that
					// no longer exists) instead of rendering an empty table.
					$paged = $pages;
					list($rows, $total) = $this->fetch_page($paged);
				}
				$upgrade = MPWPB_Global_Function::pro_upgrade_url();
				?>
				<div class="wrap mpwpb-orders-wrap">
					<div class="mpwpb-orders-header">
						<div>
							<h1 class="mpwpb-orders-title"><span class="dashicons dashicons-clipboard"></span><?php esc_html_e('Orders', 'service-booking-manager'); ?></h1>
							<p class="mpwpb-orders-subtitle"><?php esc_html_e('A read-only overview of bookings placed through the built-in (non-WooCommerce) checkout. Unlock statistics, filters, order details and status management with Pro.', 'service-booking-manager'); ?></p>
						</div>
						<a href="<?php echo esc_url($upgrade); ?>" target="_blank" rel="noopener" class="mpwpb-btn mpwpb-btn-primary"><span class="dashicons dashicons-star-filled"></span><?php esc_html_e('Upgrade to Pro', 'service-booking-manager'); ?></a>
					</div>

					<?php /* Statistics -- locked. Blurred placeholders behind a PRO overlay. */ ?>
					<div class="mpwpb-pro-lock">
						<div class="mpwpb-pro-lock-inner" aria-hidden="true">
							<div class="mpwpb-stats-bar">
								<div class="mpwpb-stat-card"><span class="mpwpb-stat-icon dashicons dashicons-clipboard"></span><div class="mpwpb-stat-info"><span class="mpwpb-stat-value">1,248</span><span class="mpwpb-stat-label"><?php esc_html_e('Total Orders', 'service-booking-manager'); ?></span></div></div>
								<div class="mpwpb-stat-card"><span class="mpwpb-stat-icon dashicons dashicons-money-alt"></span><div class="mpwpb-stat-info"><span class="mpwpb-stat-value">48,920</span><span class="mpwpb-stat-label"><?php esc_html_e('Total Revenue', 'service-booking-manager'); ?></span></div></div>
								<div class="mpwpb-stat-card"><span class="mpwpb-stat-icon dashicons dashicons-clock"></span><div class="mpwpb-stat-info"><span class="mpwpb-stat-value">36</span><span class="mpwpb-stat-label"><?php esc_html_e('Pending', 'service-booking-manager'); ?></span></div></div>
							</div>
						</div>
						<?php $this->pro_overlay($upgrade, __('Live statistics are a Pro feature', 'service-booking-manager')); ?>
					</div>

					<?php /* Filter / search -- locked. Blurred, disabled inputs behind a PRO overlay. */ ?>
					<div class="mpwpb-pro-lock">
						<div class="mpwpb-pro-lock-inner" aria-hidden="true">
							<div class="mpwpb-filter-panel">
								<div class="mpwpb-filter-panel-header"><span class="dashicons dashicons-filter"></span><strong><?php esc_html_e('Filter Orders', 'service-booking-manager'); ?></strong></div>
								<div class="mpwpb-filter-body">
									<div class="mpwpb-filter-grid">
										<div class="mpwpb-filter-field"><label><?php esc_html_e('Search', 'service-booking-manager'); ?></label><input type="text" disabled placeholder="<?php esc_attr_e('Name, email or #ID…', 'service-booking-manager'); ?>"></div>
										<div class="mpwpb-filter-field"><label><?php esc_html_e('Status', 'service-booking-manager'); ?></label><select disabled><option><?php esc_html_e('All Statuses', 'service-booking-manager'); ?></option></select></div>
										<div class="mpwpb-filter-field"><label><?php esc_html_e('Service', 'service-booking-manager'); ?></label><select disabled><option><?php esc_html_e('All Services', 'service-booking-manager'); ?></option></select></div>
										<div class="mpwpb-filter-field"><label><?php esc_html_e('Date From', 'service-booking-manager'); ?></label><input type="date" disabled></div>
									</div>
								</div>
							</div>
						</div>
						<?php $this->pro_overlay($upgrade, __('Filtering & search are a Pro feature', 'service-booking-manager')); ?>
					</div>

					<div class="mpwpb-table-wrap">
						<div class="mpwpb-table-toolbar">
							<span class="mpwpb-result-count">
								<?php
								printf(
									esc_html(
										/* translators: %d: number of orders */
										_n('%d order', '%d orders', $total, 'service-booking-manager')
									),
									(int) $total
								);
								?>
							</span>
						</div>
						<div class="mpwpb-table-scroll">
							<table class="mpwpb-orders-table">
								<thead>
									<tr>
										<th><?php esc_html_e('Order', 'service-booking-manager'); ?></th>
										<th><?php esc_html_e('Customer', 'service-booking-manager'); ?></th>
										<th><?php esc_html_e('Service & Slot', 'service-booking-manager'); ?></th>
										<th><?php esc_html_e('Total', 'service-booking-manager'); ?></th>
										<th><?php esc_html_e('Status', 'service-booking-manager'); ?></th>
										<th><?php esc_html_e('Placed', 'service-booking-manager'); ?></th>
										<th class="mpwpb-col-actions"><?php esc_html_e('Actions', 'service-booking-manager'); ?></th>
									</tr>
								</thead>
								<tbody>
									<?php if (empty($rows)) : ?>
										<tr>
											<td colspan="7" class="mpwpb-no-orders">
												<span class="dashicons dashicons-clipboard"></span>
												<p><?php esc_html_e('No orders yet.', 'service-booking-manager'); ?></p>
												<small><?php esc_html_e('When a customer completes the booking checkout, their order will appear here.', 'service-booking-manager'); ?></small>
											</td>
										</tr>
									<?php else : foreach ($rows as $row) : ?>
										<tr>
											<td class="mpwpb-col-id"><span class="mpwpb-order-link">#<?php echo esc_html($row['id']); ?></span></td>
											<td>
												<strong><?php echo esc_html($row['customer'] ?: '—'); ?></strong>
												<?php if ($row['email']) : ?><br><span class="mpwpb-cell-muted"><?php echo esc_html($row['email']); ?></span><?php endif; ?>
											</td>
											<td>
												<?php echo esc_html($row['service_title'] ?: '—'); ?>
												<?php if ($row['slot']) : ?><br><span class="mpwpb-cell-muted"><?php echo esc_html($row['slot']); ?></span><?php endif; ?>
											</td>
											<td class="mpwpb-col-total"><strong><?php echo wp_kses_post($row['total_html']); ?></strong></td>
											<td><span class="mpwpb-status-pill mpwpb-status-<?php echo esc_attr($row['status_key']); ?>"><?php echo esc_html($row['status_label']); ?></span></td>
											<td class="mpwpb-col-date"><?php echo esc_html($row['placed']); ?></td>
											<td class="mpwpb-col-actions">
												<div class="mpwpb-row-actions">
													<span class="mpwpb-icon-btn mpwpb-locked" title="<?php esc_attr_e('Order details are available in Pro', 'service-booking-manager'); ?>"><span class="dashicons dashicons-visibility"></span><?php $this->pro_badge(); ?></span>
													<span class="mpwpb-icon-btn mpwpb-locked" title="<?php esc_attr_e('Changing status is available in Pro', 'service-booking-manager'); ?>"><span class="dashicons dashicons-update"></span><?php $this->pro_badge(); ?></span>
												</div>
											</td>
										</tr>
									<?php endforeach; endif; ?>
								</tbody>
							</table>
						</div>
						<?php if ($pages > 1) :
							$prev = $paged > 1 ? add_query_arg('paged', $paged - 1, $this->base_url()) : '';
							$next = $paged < $pages ? add_query_arg('paged', $paged + 1, $this->base_url()) : '';
							?>
							<div class="mpwpb-pagination">
								<a class="mpwpb-btn mpwpb-btn-outline <?php echo $prev ? '' : 'mpwpb-btn-disabled'; ?>" href="<?php echo esc_url($prev ?: '#'); ?>"><span class="dashicons dashicons-arrow-left-alt2"></span><?php esc_html_e('Prev', 'service-booking-manager'); ?></a>
								<span class="mpwpb-page-indicator">
									<?php
									printf(
										esc_html(
											/* translators: 1: current page number, 2: total number of pages */
											__('Page %1$d of %2$d', 'service-booking-manager')
										),
										(int) $paged,
										(int) $pages
									);
									?>
								</span>
								<a class="mpwpb-btn mpwpb-btn-outline <?php echo $next ? '' : 'mpwpb-btn-disabled'; ?>" href="<?php echo esc_url($next ?: '#'); ?>"><?php esc_html_e('Next', 'service-booking-manager'); ?><span class="dashicons dashicons-arrow-right-alt2"></span></a>
							</div>
						<?php endif; ?>
					</div>
				</div>
				<?php
				$this->print_styles();
			}

			/* ------------------------------------------------------------------ *
			 *  Data
			 * ------------------------------------------------------------------ */

			/**
			 * One page of native orders (newest first). Returns [ rows[], total ].
			 * Only the requested page is queried, so nothing loads the whole table
			 * into memory.
			 *
			 * @return array{0: array<int,array>, 1: int}
			 */
			private function fetch_page(int $paged): array {
				$query = new WP_Query([
					'post_type' => MPWPB_Native_Order::CPT,
					'post_status' => 'publish',
					'posts_per_page' => self::PER_PAGE,
					'paged' => max(1, $paged),
					'orderby' => 'date',
					'order' => 'DESC',
				]);
				$rows = [];
				while ($query->have_posts()) {
					$query->the_post();
					$rows[] = $this->collect_row_data(get_the_ID());
				}
				wp_reset_postdata();
				return [$rows, (int) $query->found_posts];
			}

			private function collect_row_data(int $order_id): array {
				$item = get_post_meta($order_id, 'mpwpb_line_items', true);
				$item = is_array($item) ? $item : [];
				$service_post_id = (int) ($item['mpwpb_id'] ?? 0);

				$first = (string) get_post_meta($order_id, 'mpwpb_billing_first_name', true);
				$last = (string) get_post_meta($order_id, 'mpwpb_billing_last_name', true);
				$total = (float) get_post_meta($order_id, 'mpwpb_total', true);
				$status = (string) get_post_meta($order_id, 'mpwpb_order_status', true);
				$slots = $this->format_slots((string) ($item['mpwpb_date'] ?? ''));

				return [
					'id' => $order_id,
					'customer' => trim($first . ' ' . $last),
					'email' => (string) get_post_meta($order_id, 'mpwpb_billing_email', true),
					'service_title' => $service_post_id ? get_the_title($service_post_id) : '',
					// Only the first occurrence is listed here; the full itinerary of
					// a recurring booking is part of the Pro order detail view.
					'slot' => $slots ? $slots[0] . (count($slots) > 1 ? ' +' . (count($slots) - 1) : '') : '',
					'total_html' => MPWPB_Global_Function::wc_price($service_post_id, $total),
					'status_key' => $this->status_key($status),
					'status_label' => $this->status_label($status),
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

			/* ------------------------------------------------------------------ *
			 *  Pro locks + styles
			 * ------------------------------------------------------------------ */

			private function pro_badge(): void {
				?>
				<span class="mpwpb-lock-badge"><span class="dashicons dashicons-lock"></span><?php esc_html_e('PRO', 'service-booking-manager'); ?></span>
				<?php
			}

			private function pro_overlay(string $upgrade, string $text): void {
				?>
				<a class="mpwpb-pro-overlay" href="<?php echo esc_url($upgrade); ?>" target="_blank" rel="noopener">
					<span class="mpwpb-pro-overlay-badge"><span class="dashicons dashicons-lock"></span><?php esc_html_e('PRO', 'service-booking-manager'); ?></span>
					<span class="mpwpb-pro-overlay-text"><?php echo esc_html($text); ?></span>
				</a>
				<?php
			}

			private function print_styles(): void {
				?>
				<style>
					.mpwpb-orders-wrap{--mpwpb-accent:#6366f1;--mpwpb-accent-soft:#eef2ff;max-width:100%;box-sizing:border-box;padding:0 20px 40px 0;color:#1e293b;}
					.mpwpb-orders-header{display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:12px;margin:18px 0 20px;}
					.mpwpb-orders-title{display:flex;align-items:center;gap:10px;font-size:22px!important;font-weight:700!important;color:#0f172a!important;margin:0!important;padding:0!important;}
					.mpwpb-orders-title .dashicons{font-size:22px;width:22px;height:22px;color:var(--mpwpb-accent);}
					.mpwpb-orders-subtitle{color:#64748b;font-size:13px;margin:4px 0 0;max-width:680px;}
					.mpwpb-btn{display:inline-flex;align-items:center;gap:6px;padding:8px 16px;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;text-decoration:none;border:none;line-height:1.4;transition:all .15s ease;}
					.mpwpb-btn .dashicons{font-size:15px;width:15px;height:15px;margin-top:1px;}
					.mpwpb-btn-primary{background:var(--mpwpb-accent);color:#fff;}
					.mpwpb-btn-primary:hover,.mpwpb-btn-primary:focus{background:#4f52d8;color:#fff;}
					.mpwpb-btn-outline{background:#fff;border:1.5px solid #cbd5e1;color:#475569;}
					.mpwpb-btn-outline:hover{border-color:var(--mpwpb-accent);color:var(--mpwpb-accent);}
					.mpwpb-btn-disabled{opacity:.4;pointer-events:none;}
					.mpwpb-stats-bar{display:flex;gap:16px;flex-wrap:wrap;}
					.mpwpb-stat-card{flex:1;min-width:160px;background:#fff;border:1px solid #eef0f3;border-radius:12px;padding:16px 20px;display:flex;align-items:center;gap:14px;box-shadow:0 1px 2px rgba(15,23,42,.05);}
					.mpwpb-stat-icon{font-size:22px;width:22px;height:22px;color:var(--mpwpb-accent);background:var(--mpwpb-accent-soft);border-radius:8px;padding:10px;display:flex;align-items:center;justify-content:center;}
					.mpwpb-stat-info{display:flex;flex-direction:column;}
					.mpwpb-stat-value{font-size:20px;font-weight:700;color:#0f172a;line-height:1.2;}
					.mpwpb-stat-label{font-size:11.5px;color:#64748b;margin-top:2px;text-transform:uppercase;letter-spacing:.04em;}
					/* Pro lock */
					.mpwpb-pro-lock{position:relative;margin-bottom:20px;border-radius:12px;overflow:hidden;}
					.mpwpb-pro-lock-inner{filter:blur(4px);pointer-events:none;user-select:none;opacity:.9;}
					.mpwpb-pro-overlay{position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:8px;text-decoration:none;background:rgba(248,250,252,.55);}
					.mpwpb-pro-overlay:focus{box-shadow:none;outline:2px solid var(--mpwpb-accent);outline-offset:-2px;}
					.mpwpb-pro-overlay-badge{display:inline-flex;align-items:center;gap:6px;background:linear-gradient(135deg,#6366f1,#4338ca);color:#fff;font-size:12px;font-weight:800;letter-spacing:.08em;padding:6px 16px;border-radius:20px;box-shadow:0 6px 16px rgba(99,102,241,.35);}
					.mpwpb-pro-overlay-badge .dashicons{font-size:14px;width:14px;height:14px;}
					.mpwpb-pro-overlay-text{font-size:12.5px;font-weight:600;color:#334155;background:#fff;padding:4px 12px;border-radius:20px;box-shadow:0 1px 3px rgba(0,0,0,.08);}
					/* Table */
					.mpwpb-table-wrap{background:#fff;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.04);}
					.mpwpb-table-toolbar{display:flex;align-items:center;justify-content:space-between;padding:14px 20px;border-bottom:1px solid #f1f5f9;background:#f8fafc;}
					.mpwpb-result-count{font-size:12.5px;color:#64748b;font-weight:500;}
					.mpwpb-table-scroll{overflow-x:auto;}
					.mpwpb-orders-table{width:100%;border-collapse:collapse;font-size:13px;}
					.mpwpb-orders-table thead tr{background:#f8fafc;}
					.mpwpb-orders-table thead th{padding:12px 16px;text-align:left;font-size:11.5px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#64748b;border-bottom:1px solid #e2e8f0;white-space:nowrap;}
					.mpwpb-orders-table tbody tr{border-bottom:1px solid #f1f5f9;}
					.mpwpb-orders-table tbody tr:last-child{border-bottom:none;}
					.mpwpb-orders-table tbody tr:hover{background:#fafaff;}
					.mpwpb-orders-table tbody td{padding:13px 16px;vertical-align:middle;color:#374151;line-height:1.5;}
					.mpwpb-col-id,.mpwpb-col-total,.mpwpb-col-date{white-space:nowrap;}
					.mpwpb-col-date{font-size:12px;color:#64748b;}
					.mpwpb-order-link{font-weight:700;color:var(--mpwpb-accent);font-size:13.5px;}
					.mpwpb-cell-muted{font-size:12px;color:#64748b;}
					.mpwpb-status-pill{display:inline-flex;align-items:center;padding:3px 10px;border-radius:20px;font-size:11.5px;font-weight:600;letter-spacing:.03em;white-space:nowrap;}
					.mpwpb-status-pending{background:#fef3c7;color:#92400e;}
					.mpwpb-status-processing{background:#dcfce7;color:#166534;}
					.mpwpb-status-completed{background:#dbeafe;color:#1e40af;}
					.mpwpb-status-cancelled{background:#fee2e2;color:#991b1b;}
					.mpwpb-status-refunded{background:#f3e8ff;color:#6b21a8;}
					.mpwpb-status-on-hold{background:#fef9c3;color:#854d0e;}
					.mpwpb-status-failed{background:#fee2e2;color:#7f1d1d;}
					.mpwpb-status-default{background:#e5e7eb;color:#374151;}
					.mpwpb-col-actions{width:150px;}
					.mpwpb-row-actions{display:flex;align-items:center;gap:6px;}
					.mpwpb-icon-btn{display:inline-flex;align-items:center;gap:3px;height:30px;padding:0 8px;border-radius:7px;border:1.5px solid #e2e8f0;background:#fff;color:#475569;}
					.mpwpb-icon-btn .dashicons{font-size:16px;width:16px;height:16px;}
					.mpwpb-locked{cursor:not-allowed;background:#f8fafc;color:#94a3b8;}
					.mpwpb-lock-badge{display:inline-flex;align-items:center;gap:2px;font-size:8.5px;font-weight:800;letter-spacing:.06em;color:#fff;background:linear-gradient(135deg,#6366f1,#4338ca);padding:1px 5px;border-radius:10px;}
					.mpwpb-lock-badge .dashicons{font-size:9px;width:9px;height:9px;}
					.mpwpb-no-orders{text-align:center;padding:56px 20px!important;color:#94a3b8;}
					.mpwpb-no-orders .dashicons{font-size:46px;width:46px;height:46px;display:block;margin:0 auto 12px;color:#cbd5e1;}
					.mpwpb-no-orders p{margin:0;font-size:14px;color:#64748b;}
					.mpwpb-no-orders small{display:block;margin-top:4px;font-size:12px;}
					.mpwpb-pagination{display:flex;align-items:center;justify-content:center;gap:14px;padding:16px 20px;border-top:1px solid #e2e8f0;background:#f8fafc;}
					.mpwpb-page-indicator{font-size:12.5px;color:#64748b;}
					/* Blurred filter placeholders (visual only) */
					.mpwpb-filter-panel{background:#fff;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;}
					.mpwpb-filter-panel-header{display:flex;align-items:center;gap:8px;padding:14px 20px;background:#f8fafc;border-bottom:1px solid #e2e8f0;font-size:13px;font-weight:600;color:#374151;}
					.mpwpb-filter-panel-header .dashicons{color:var(--mpwpb-accent);font-size:16px;width:16px;height:16px;}
					.mpwpb-filter-body{padding:20px;}
					.mpwpb-filter-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:16px;}
					.mpwpb-filter-field{display:flex;flex-direction:column;gap:5px;}
					.mpwpb-filter-field label{font-size:12px;font-weight:600;color:#475569;text-transform:uppercase;letter-spacing:.04em;}
					.mpwpb-filter-field input,.mpwpb-filter-field select{width:100%;padding:8px 12px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:13px;background:#fff;height:38px;box-sizing:border-box;}
					@media (max-width:782px){.mpwpb-orders-header{flex-direction:column;align-items:stretch;}}
				</style>
				<?php
			}
		}
		new MPWPB_Native_Order_List();
	}
