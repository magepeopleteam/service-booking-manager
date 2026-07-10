<?php
	if (!defined('ABSPATH')) {
		die;
	}
	if (!class_exists('MPWPB_Coupon_Services_Settings')) {
		class MPWPB_Coupon_Services_Settings {
			public function __construct() {
				add_action('add_mpwpb_coupon_tab_content', [$this, 'render'], 10, 1);
			}
			public function render($post_id) {
				$scope = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_coupon_service_scope', 'all');
				$selected = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_coupon_services', []);
				if (!is_array($selected)) {
					$selected = [];
				}
				$all_services = MPWPB_Coupon_Function::get_all_services_flat();
				?>
				<div class="tabsItem" data-tabs="#mpwpb_coupon_services">
					<header>
						<h2><?php esc_html_e('Services', 'service-booking-manager'); ?></h2>
						<span><?php esc_html_e('Restrict this coupon to specific services, or allow it on every service.', 'service-booking-manager'); ?></span>
					</header>
					<section class="section">
						<label class="label">
							<p><?php esc_html_e('Applies To', 'service-booking-manager'); ?></p>
							<select name="mpwpb_coupon_service_scope" id="mpwpb_coupon_service_scope">
								<option value="all" <?php selected($scope, 'all'); ?>><?php esc_html_e('All Services', 'service-booking-manager'); ?></option>
								<option value="specific" <?php selected($scope, 'specific'); ?>><?php esc_html_e('Specific Service(s)', 'service-booking-manager'); ?></option>
							</select>
						</label>
					</section>
					<section class="section" id="mpwpb_coupon_services_wrap" style="display: <?php echo esc_attr($scope === 'specific' ? 'block' : 'none'); ?>;">
						<label class="label">
							<p><?php esc_html_e('Select Service(s)', 'service-booking-manager'); ?></p>
							<select name="mpwpb_coupon_services[]" multiple size="8" style="min-width:320px;">
								<?php foreach ($all_services as $option): ?>
									<option value="<?php echo esc_attr($option['value']); ?>" <?php selected(in_array($option['value'], $selected, true), true); ?>><?php echo esc_html($option['label']); ?></option>
								<?php endforeach; ?>
							</select>
						</label>
					</section>
				</div>
				<script>
					jQuery(function ($) {
						$('#mpwpb_coupon_service_scope').on('change', function () {
							$('#mpwpb_coupon_services_wrap').toggle($(this).val() === 'specific');
						});
					});
				</script>
				<?php
			}
		}
		new MPWPB_Coupon_Services_Settings();
	}
