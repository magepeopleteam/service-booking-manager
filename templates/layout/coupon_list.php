<?php

if (!defined('ABSPATH')) {
	die;
}

$statuses = ['publish', 'draft'];
$query = new WP_Query([
	'post_type' => 'mpwpb_coupon',
	'post_status' => $statuses,
	'posts_per_page' => -1,
	'orderby' => 'date',
	'order' => 'DESC',
]);

$count_coupon = wp_count_posts('mpwpb_coupon');
$publish = isset($count_coupon->publish) ? (int) $count_coupon->publish : 0;
$draft = isset($count_coupon->draft) ? (int) $count_coupon->draft : 0;
$trash = isset($count_coupon->trash) ? (int) $count_coupon->trash : 0;
$total = $publish + $draft + $trash;
$trash_link = add_query_arg([
	'post_status' => 'trash',
	'post_type' => 'mpwpb_coupon',
], admin_url('edit.php'));
$add_new_link = admin_url('post-new.php?post_type=mpwpb_coupon');

function mpwpb_coupon_discount_summary($post_id): string {
	$type = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_coupon_discount_type', 'fixed');
	$value = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_coupon_discount_value', 0);
	switch ($type) {
		case 'percentage':
			return $value . '% ' . esc_html__('OFF', 'service-booking-manager');
		case 'fixed_price':
			return esc_html__('Flat', 'service-booking-manager') . ' ' . wp_kses_post(MPWPB_Global_Function::format_price($value));
		case 'free':
			return esc_html__('100% OFF (Free)', 'service-booking-manager');
		default:
			return wp_kses_post(MPWPB_Global_Function::format_price($value)) . ' ' . esc_html__('OFF', 'service-booking-manager');
	}
}

function mpwpb_coupon_usage_summary($post_id): string {
	$used = (int) MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_coupon_usage_count', 0);
	$limit = (int) MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_coupon_usage_limit_total', 0);
	if ($limit > 0) {
		return $used . ' / ' . $limit;
	}
	return $used . ' / ' . esc_html__('Unlimited', 'service-booking-manager');
}

function mpwpb_display_coupon_list($query) {
	$today = current_time('Y-m-d');
	if ($query->have_posts()) {
		while ($query->have_posts()) {
			$query->the_post();
			$post_id = get_the_ID();
			$status = get_post_status($post_id);
			$code = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_coupon_code', '');
			$expiry = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_coupon_expiry_date', '');
			$is_expired = $expiry && $expiry < $today;

			$edit_link = get_edit_post_link($post_id);
			$delete_link = get_delete_post_link($post_id);
			$duplicate_link = wp_nonce_url(
				admin_url('admin.php?action=mpwpb_coupon_duplicate&post_id=' . $post_id),
				'mpwpb_coupon_duplicate_' . $post_id
			);

			if ($is_expired) {
				$status_class = 'mpwpb_trash';
				$status_text = esc_html__('Expired', 'service-booking-manager');
			} elseif ($status === 'publish') {
				$status_class = 'published';
				$status_text = esc_html__('Active', 'service-booking-manager');
			} elseif ($status === 'draft') {
				$status_class = 'draft';
				$status_text = esc_html__('Inactive', 'service-booking-manager');
			} else {
				$status_class = 'mpwpb_trash';
				$status_text = esc_html__('Trash', 'service-booking-manager');
			}
			?>
			<tr data-coupon-status="<?php echo esc_attr($status); ?>" data-coupon-code="<?php echo esc_attr($code); ?>">
				<td>
					<div class="mpwpb_service_list_service-info">
						<div class="mpwpb_service_list_service-title"><code><?php echo esc_html($code ?: '—'); ?></code></div>
						<div class="mpwpb_service_list_service-subtitle"><?php echo esc_html(get_the_title($post_id)); ?></div>
					</div>
				</td>
				<td><?php echo wp_kses_post(mpwpb_coupon_discount_summary($post_id)); ?></td>
				<td><?php echo esc_html(mpwpb_coupon_usage_summary($post_id)); ?></td>
				<td>
					<span class="mpwpb_service_list_status-badge <?php echo esc_attr($status_class); ?>"><span class="mpwpb_service_list_status-dot"></span><?php echo esc_html($status_text); ?></span>
				</td>
				<td>
					<div class="mpwpb_service_list_actions">
						<a href="<?php echo esc_url($edit_link); ?>"><button class="mpwpb_service_list_action-btn"><i class="mi mi-edit"></i></button></a>
						<a title="<?php esc_attr_e('Duplicate Coupon', 'service-booking-manager'); ?>" href="<?php echo esc_url($duplicate_link); ?>">
							<button class="mpwpb_service_list_action-btn"><i class="mi mi-clone"></i></button>
						</a>
						<a class="delete" href="<?php echo esc_url($delete_link); ?>" onclick="return confirm('<?php esc_attr_e('Are you sure you want to move this to trash?', 'service-booking-manager'); ?>');" title="<?php esc_attr_e('Trash', 'service-booking-manager'); ?>">
							<button class="mpwpb_service_list_action-btn"><i class="mi mi-trash"></i></button>
						</a>
					</div>
				</td>
			</tr>
			<?php
		}
	}
}

$total_redemptions = 0;
foreach ($query->posts as $coupon_post) {
	$total_redemptions += (int) MPWPB_Global_Function::get_post_info($coupon_post->ID, 'mpwpb_coupon_usage_count', 0);
}
?>

<div class="mpwpv_service_list_container">

	<div class="mpwpv_service_list_header">
		<div class="mpwpv_service_list_header-titles">
			<h1><?php esc_html_e('Coupons', 'service-booking-manager'); ?></h1>
			<p><?php esc_html_e('Create and manage booking discount coupons', 'service-booking-manager'); ?></p>
		</div>
	</div>

	<div class="mpwpv_service_list_analytics-container">
		<div class="mpwpv_service_list_analytics-card">
			<div class="mpwpv_service_list_analytics-icon blue"><i class="fas fa-tags"></i></div>
			<div class="mpwpv_service_list_analytics-text">
				<p class="mpwpv_service_list_analytics-label"><?php esc_html_e('Total Coupons', 'service-booking-manager'); ?></p>
				<p class="mpwpv_service_list_analytics-value"><?php echo esc_html($total); ?></p>
			</div>
		</div>
		<div class="mpwpv_service_list_analytics-card">
			<div class="mpwpv_service_list_analytics-icon green"><i class="fas fa-check-circle"></i></div>
			<div class="mpwpv_service_list_analytics-text">
				<p class="mpwpv_service_list_analytics-label"><?php esc_html_e('Active', 'service-booking-manager'); ?></p>
				<p class="mpwpv_service_list_analytics-value"><?php echo esc_html($publish); ?></p>
			</div>
		</div>
		<div class="mpwpv_service_list_analytics-card">
			<div class="mpwpv_service_list_analytics-icon orange"><i class="fas fa-ticket-alt"></i></div>
			<div class="mpwpv_service_list_analytics-text">
				<p class="mpwpv_service_list_analytics-label"><?php esc_html_e('Total Redemptions', 'service-booking-manager'); ?></p>
				<p class="mpwpv_service_list_analytics-value"><?php echo esc_html($total_redemptions); ?></p>
			</div>
		</div>
	</div>

	<div class="mpwpv_service_list_main-card">
		<div class="mpwpv_service_list_card-header">
			<div class="mpwpv_service_list_header-top">
				<div class="mpwpv_service_list_header-top-titles">
					<h2><?php esc_html_e('Coupon Listings', 'service-booking-manager'); ?></h2>
				</div>
			</div>
			<div class="mpwpv_service_list_filter-row">
				<div class="mpwpv_service_list_filter-buttons">
					<button class="mpwpv_service_list_filter-btn ttbm_filter_btn_active_bg_color" data-filter-item="all"><?php esc_html_e('All', 'service-booking-manager'); ?> <span class="mpwpb_service_list_filter-count"><?php echo esc_html($total); ?></span></button>
					<button class="mpwpv_service_list_filter-btn ttbm_filter_btn_bg_color" data-filter-item="publish"><?php esc_html_e('Active', 'service-booking-manager'); ?> <span class="mpwpb_service_list_filter-count"><?php echo esc_html($publish); ?></span></button>
					<button class="mpwpv_service_list_filter-btn ttbm_filter_btn_bg_color" data-filter-item="draft"><?php esc_html_e('Inactive', 'service-booking-manager'); ?> <span class="mpwpb_service_list_filter-count"><?php echo esc_html($draft); ?></span></button>
					<a class="ttbm_trash_link" href="<?php echo esc_url($trash_link); ?>" target="_blank">
						<button class="mpwpv_service_list_filter-btn ttbm_filter_btn_bg_color" data-filter-item="trash"><?php esc_html_e('Trash', 'service-booking-manager'); ?> <span class="mpwpb_service_list_filter-count"><?php echo esc_html($trash); ?></span></button>
					</a>
				</div>
				<div class="mpwpv_service_list_actions-inline">
					<a href="<?php echo esc_url($add_new_link); ?>"><div class="mpwpb_add_new_Service"><span class="fas fa-plus _mR_xs"></span><?php esc_html_e('Add New Coupon', 'service-booking-manager'); ?></div></a>
				</div>
			</div>
		</div>

		<div class="mpwpb_service_list_table-container">
			<table class="mpwpb_service_list_services-table">
				<thead>
				<tr>
					<th><?php esc_html_e('Coupon', 'service-booking-manager'); ?></th>
					<th><?php esc_html_e('Discount', 'service-booking-manager'); ?></th>
					<th><?php esc_html_e('Usage', 'service-booking-manager'); ?></th>
					<th><?php esc_html_e('Status', 'service-booking-manager'); ?></th>
					<th><?php esc_html_e('Actions', 'service-booking-manager'); ?></th>
				</tr>
				</thead>
				<tbody>
				<?php mpwpb_display_coupon_list($query); ?>
				</tbody>
			</table>
		</div>
	</div>
</div>
