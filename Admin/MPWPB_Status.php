<?php
	/*
   * @Author 		engr.sumonazma@gmail.com
   * Copyright: 	mage-people.com
   */
	if (!defined('ABSPATH')) {
		die;
	} // Cannot access pages directly.
	if (!class_exists('MPWPB_Status')) {
		class MPWPB_Status {
			public function __construct() {
				add_action('admin_menu', array($this, 'status_menu'));
			}
			public function status_menu() {
				$cpt = MPWPB_Function::get_cpt();
				add_submenu_page('edit.php?post_type=' . $cpt, esc_html__('Status', 'service-booking-manager'), '<span style="color:yellow">' . esc_html__('Status', 'service-booking-manager') . '</span>', 'manage_options', 'mpwpb_status_page', array($this, 'status_page'));
			}
			public function status_page() {
				$label = MPWPB_Function::get_name();
				$wc_i = MPWPB_Global_Function::check_woocommerce();
				$wc_i_text = $wc_i == 1 ? esc_html__('Yes', 'service-booking-manager') : esc_html__('No', 'service-booking-manager');
				$wp_v = get_bloginfo('version');
				$wc_v = class_exists('WooCommerce') ? WC()->version : '-';
				$from_name = get_option('woocommerce_email_from_name');
				$from_email = get_option('woocommerce_email_from_address');
				$dummy_imported = class_exists('MPWPB_Dummy_Import') && MPWPB_Dummy_Import::is_already_imported();
				?>
                <div class="wrap">
                </div>
                <div class="mpwpb_style">
					<?php do_action('mp_status_notice_sec'); ?>
                    <div class=_dShadow_6_adminLayout">
                        <h2 class="textCenter"><?php echo esc_html($label) . '  ' . esc_html__('For Woocommerce Environment Status', 'service-booking-manager'); ?></h2>
                        <div class="divider"></div>
                        <table>
                            <tbody>
                            <tr>
                                <th data-export-label="WC Version"><?php esc_html_e('WordPress Version : ', 'service-booking-manager'); ?></th>
                                <th class="<?php echo esc_attr($wp_v > 5.5 ? 'textSuccess' : 'textWarning'); ?>">
                                    <span class="<?php echo esc_attr($wp_v > 5.5 ? 'far fa-check-circle' : 'fas fa-exclamation-triangle'); ?> mR_xs"></span><?php echo esc_html($wp_v); ?>
                                </th>
                            </tr>
                            <tr>
                                <th data-export-label="WC Version"><?php esc_html_e('Woocommerce Installed : ', 'service-booking-manager'); ?></th>
                                <th class="<?php echo esc_attr($wc_i == 1 ? 'textSuccess' : 'textWarning'); ?>">
                                    <span class="<?php echo esc_attr($wc_i == 1 ? 'far fa-check-circle' : 'fas fa-exclamation-triangle'); ?> mR_xs"></span><?php echo esc_html($wc_i_text); ?>
                                </th>
                            </tr>
							<?php if ($wc_i == 1) { ?>
                                <tr>
                                    <th data-export-label="WC Version"><?php esc_html_e('Woocommerce Version : ', 'service-booking-manager'); ?></th>
                                    <th class="<?php echo esc_attr($wc_v > 4.8 ? 'textSuccess' : 'textWarning'); ?>">
                                        <span class="<?php echo esc_attr($wc_v > 4.8 ? 'far fa-check-circle' : 'fas fa-exclamation-triangle'); ?> mR_xs"></span><?php echo esc_html($wc_v); ?>
                                    </th>
                                </tr>
                                <tr>
                                    <th data-export-label="WC Version"><?php esc_html_e('Name : ', 'service-booking-manager'); ?></th>
                                    <th class="<?php echo esc_attr($from_name ? 'textSuccess' : 'textWarning'); ?>">
                                        <span class="<?php echo esc_attr($from_name ? 'far fa-check-circle' : 'fas fa-exclamation-triangle'); ?> mR_xs"></span><?php echo esc_html($from_name); ?>
                                    </th>
                                </tr>
                                <tr>
                                    <th data-export-label="WC Version"><?php esc_html_e('Email Address : ', 'service-booking-manager'); ?></th>
                                    <th class="<?php echo esc_attr($from_email ? 'textSuccess' : 'textWarning'); ?>">
                                        <span class="<?php echo esc_attr($from_email ? 'far fa-check-circle' : 'fas fa-exclamation-triangle'); ?> mR_xs"></span><?php echo esc_html($from_email); ?>
                                    </th>
                                </tr>
							<?php }
								do_action('mp_status_table_item_sec'); ?>
                            </tbody>
                        </table>
                    </div>

					<?php if ($wc_i == 1) { ?>
					<div class="_dShadow_6_adminLayout" style="margin-top: 30px;">
						<h2 class="textCenter"><?php esc_html_e('Dummy Data Import', 'service-booking-manager'); ?></h2>
						<div class="divider"></div>
						<div style="padding: 20px; text-align: center;">
							<?php if ($dummy_imported) { ?>
								<p style="font-size: 14px; color: #50575e; margin-bottom: 15px;">
									<span class="far fa-check-circle textSuccess" style="margin-right: 5px;"></span>
									<?php esc_html_e('Dummy data has already been imported. You can re-import to restore default demo services.', 'service-booking-manager'); ?>
								</p>
							<?php } else { ?>
								<p style="font-size: 14px; color: #50575e; margin-bottom: 15px;">
									<span class="fas fa-info-circle" style="color: #2271b1; margin-right: 5px;"></span>
									<?php esc_html_e('Import dummy services to quickly see how Service Booking Manager works. This will create sample service posts with categories and settings.', 'service-booking-manager'); ?>
								</p>
							<?php } ?>
							<button type="button" id="mpwpb-trigger-dummy-import-btn" class="button button-primary" style="padding: 8px 24px; font-size: 14px; height: auto; border-radius: 6px;">
								<span class="fas fa-download" style="margin-right: 6px;"></span>
								<?php echo $dummy_imported ? esc_html__('Re-Import Dummy Data', 'service-booking-manager') : esc_html__('Import Dummy Data', 'service-booking-manager'); ?>
							</button>
						</div>
					</div>
					<?php } ?>
                </div>
				<?php
			}
		}
		new MPWPB_Status();
	}