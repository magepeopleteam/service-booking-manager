<?php
	/**
	 * @author Sahahdat Hossain <raselsha@gmail.com>
	 * @license mage-people.com
	 * @var 1.0.0
	 */
	if (!defined('ABSPATH'))
		die;
	if (!class_exists('MPWPB_Service_Details')) {
		class MPWPB_Service_Details {
			public function __construct() {
				add_action('add_mpwpb_settings_tab_content', [$this, 'service_details']);
			}
			public function service_details($post_id) {
				$service_features_status = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_features_status', 'on');
				$service_overview_status = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_service_overview_status', 'off');
				$service_details_status = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_service_details_status', 'off');
				$service_ratings = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_service_review_ratings', '');
				$service_rating_scale = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_service_rating_scale', '');
				$service_rating_text = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_service_rating_text', '');
				$service_features_checked = $service_features_status == 'on' ? 'checked' : '';
				$features_active_class = $service_features_status == 'on' ? 'mActive' : '';
				$service_overview_checked = $service_overview_status == 'on' ? 'checked' : '';
				$service_overview_class = $service_overview_status == 'on' ? 'mActive' : '';
				$service_details_checked = $service_details_status == 'on' ? 'checked' : '';
				$active_class = $service_details_status == 'on' ? 'mActive' : '';
				$service_overview_content = get_post_meta($post_id, 'mpwpb_service_overview_content', true);
				$service_details_content = get_post_meta($post_id, 'mpwpb_service_details_content', true);
				?>
                <div class="tabsItem" data-tabs="#mpwpb_service_details">
                    <header>
                        <h2><?php esc_html_e('Service Details Settings', 'service-booking-manager'); ?></h2>
                        <span><?php esc_html_e('Service Details will be here.', 'service-booking-manager'); ?></span>
                    </header>
                    <!-- service heighlight -->
                    <section class="section">
                        <h2><?php esc_html_e('Service Features', 'service-booking-manager'); ?></h2>
                        <span><?php esc_html_e('Service Features', 'service-booking-manager'); ?></span>
                    </section>
                    <section>
                        <label class="label">
                            <p><?php esc_html_e('Service Features', 'service-booking-manager'); ?></p>
                                
							<?php MPWPB_Custom_Layout::switch_button('mpwpb_features_status', $service_features_checked); ?>
                            
                        </label>
                    </section>
                    <section class="mpwpb-service-features <?php echo esc_attr($features_active_class); ?>" data-collapse="#mpwpb_features_status">
                        <label class="">
                            <div class="mp_settings_area" style="width: 100%;">
                                <div class="mp_item_insert mp_sortable_area">
									<?php
										$features = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_features', []);
										if (sizeof($features) > 0) {
											foreach ($features as $item) {
												if ($item) {
													self::feature_lists('mpwpb_features[]', $item);
												}
											}
										}
									?>
                                </div>
								<?php MPWPB_Custom_Layout::add_new_button(esc_html__('Add New Feature', 'service-booking-manager')); ?>
                                <div class="mpwpb_hidden_content">
                                    <div class="mpwpb_hidden_item">
										<?php self::feature_lists('mpwpb_features[]'); ?>
                                    </div>
                                </div>
                            </div>
                        </label>
                    </section>
                    <!-- service overview -->
                    <section class="section">
                        <h2><?php esc_html_e('Service Overview', 'service-booking-manager'); ?></h2>
                        <span><?php esc_html_e('Service Overview', 'service-booking-manager'); ?></span>
                    </section>
                    <section>
                        <label class="label">
                            <p><?php esc_html_e('Enable Service Overview', 'service-booking-manager'); ?></p>
                                
							<?php MPWPB_Custom_Layout::switch_button('mpwpb_service_overview_status', $service_overview_checked); ?>
                            
                        </label>
                    </section>
                    <section class="mpwpb-service-overview <?php echo esc_attr($service_overview_class); ?>" data-collapse="#mpwpb_service_overview_status">
						<?php $this->show_editor($service_overview_content, 'mpwpb_service_overview_content'); ?>
                    </section>
                    <!-- service details -->
                    <section class="section">
                        <h2><?php esc_html_e('Service Details', 'service-booking-manager'); ?></h2>
                        <span><?php esc_html_e('Service Details', 'service-booking-manager'); ?></span>
                    </section>
                    <section>
                        <label class="label">
                            <p><?php esc_html_e('Enable Service Details', 'service-booking-manager'); ?></p>
                            
							<?php MPWPB_Custom_Layout::switch_button('mpwpb_service_details_status', $service_details_checked); ?>
                            
                        </label>
                    </section>
                    <section class="mpwpb-service-details <?php echo esc_attr($active_class); ?>" data-collapse="#mpwpb_service_details_status">
						<?php $this->show_editor($service_details_content, 'mpwpb_service_details_content'); ?>
                    </section>
                    <!-- service review and ratings -->
                    <section class="section">
                        <h2><?php esc_html_e('Service Review', 'service-booking-manager'); ?></h2>
                        <span><?php esc_html_e('Service Review Settings', 'service-booking-manager'); ?></span>
                    </section>
                    <section>
                        <label class="label">
                            <p><?php esc_html_e('Service Review Rating', 'service-booking-manager'); ?></p>
                                
                            <input type="text" name="mpwpb_service_review_ratings" value="<?php echo esc_html($service_ratings); ?>" placeholder="4.5">
                        </label>
                    </section>
                    <section>
                        <label class="label">
                            <p><?php esc_html_e('Service Review Scale', 'service-booking-manager'); ?></p>
                                
                            <input type="text" name="mpwpb_service_rating_scale" value="<?php echo esc_html($service_rating_scale); ?>" placeholder="Out of 5">
                        </label>
                    </section>
                    <section>
                        <label class="label">
                            
                            <p><?php esc_html_e('Service Review Text', 'service-booking-manager'); ?></p>
                            
                            <input type="text" name="mpwpb_service_rating_text" value="<?php echo esc_html($service_rating_text); ?>" placeholder="(8868 ratings on 3 services)">
                        </label>
                    </section>
                </div>
				<?php
			}
			public static function feature_lists($name, $item = '') {
				$item = $item ? $item : '';
				?>
                <div class="mp_remove_area  _mB_xs">
                    <div class="justifyBetween">
                        <label class="col_12">
                            <input type="text" class="formControl" name="<?php echo esc_attr($name); ?>" value="<?php echo esc_attr($item); ?>"/>
                        </label>
						<?php MPWPB_Custom_Layout::move_remove_button(); ?>
                    </div>
                </div>
				<?php
			}
			public function show_editor($content, $field_name) {
				$content = $content; // You can set default content if needed.
				$editor_id = $field_name; // ID for the editor (used internally by wp_editor).
				$settings = array();
				wp_editor($content, $editor_id, $settings);
			}
		}
		new MPWPB_Service_Details();
	}