<?php
	/*
   * @Author 		engr.sumonazma@gmail.com
   * Copyright: 	mage-people.com
   */
	if (!defined('ABSPATH')) {
		die;
	} // Cannot access pages directly.
	if (!class_exists('MPWPB_Price_Settings')) {
		class MPWPB_Price_Settings {
			public function __construct() {
				add_action('add_mpwpb_settings_tab_content', [$this, 'price_settings'], 10, 1);
				add_action('mpwpb_settings_save', [$this, 'save_price_settings'], 10, 1);
			}
			public function price_settings($post_id) {
				?>
				<div class="tabsItem mpwpb_price_settings" data-tabs="#mpwpb_price_settings">
					<h5><?php echo get_the_title($post_id) . ' ' . esc_html__('Price Settings', 'service-booking-manager'); ?></h5>
					<div class="divider"></div>
					<?php $this->price($post_id); ?>
				</div>
				<?php
			}
			public function price($post_id) {
				$category_text = MPWPB_Function::get_category_text($post_id);
				$sub_category_text = MPWPB_Function::get_sub_category_text($post_id);
				$service_text = MPWPB_Function::get_service_text($post_id);
				//**************//
				$category_infos = MP_Global_Function::get_post_info($post_id, 'mpwpb_category_infos', array());
				$category_active = MP_Global_Function::get_post_info($post_id, 'mpwpb_category_active', 'on');
				$category_active_class = $category_active == 'on' ? 'mActive' : '';
				$category_active_checked = $category_active == 'on' ? 'checked' : '';
				//**************//
				$sub_category_active = MP_Global_Function::get_post_info($post_id, 'mpwpb_sub_category_active', 'off');
				$sub_category_active_class = $category_active == 'on' && $sub_category_active == 'on' ? 'mActive' : '';
				$sub_category_active_checked = $category_active == 'on' && $sub_category_active == 'on' ? 'checked' : '';
				//**************//
				$service_details_active = MP_Global_Function::get_post_info($post_id, 'mpwpb_service_details_active', 'off');
				$service_details_active_class = $service_details_active == 'on' ? 'mActive' : '';
				$service_details_active_checked = $service_details_active == 'on' ? 'checked' : '';
				$service_duration_active = MP_Global_Function::get_post_info($post_id, 'mpwpb_service_duration_active', 'on');
				$service_duration_active_class = $service_duration_active == 'on' ? 'mActive' : '';
				$service_duration_active_checked = $service_duration_active == 'on' ? 'checked' : '';
				//echo '<pre>';print_r($category_infos);echo '</pre>';
				$category_count = 0;
				$active['category'] = $category_active_class;
				$active['category_text'] = $category_text;
				$active['sub_category'] = $sub_category_active_class;
				$active['sub_category_text'] = $sub_category_text;
				$active['service_text'] = $service_text;
				$active['service_details'] = $service_details_active_class;
				$active['service_duration'] = $service_duration_active_class;
				?>
				<div class="mpPanel mT">
					<div class="mpPanelHeader bgTheme" data-collapse-target="#mpwpb_settings_pricing" data-open-icon="fa-minus" data-close-icon="fa-plus">
						<h6>
							<span data-icon class="fas fa-minus mR_xs"></span><?php _e('Price Settings', 'service-booking-manager'); ?></h6>
					</div>
					<div class="mpPanelBody mActive" data-collapse="#mpwpb_settings_pricing">
						<h5 class="dFlex">
							<?php MP_Custom_Layout::switch_button('mpwpb_category_active', $category_active_checked); ?>
							<span class="mR"><?php echo esc_html__('Enable', 'service-booking-manager') . ' <span data-input-change="mpwpb_category_text">' . $category_text . ' </span> ' . esc_html__('Section', 'service-booking-manager'); ?></span>
						</h5>
						<?php MPWPB_Settings::info_text('mpwpb_category_active'); ?>
						<div class="divider"></div>
						<div class="<?php echo esc_attr($category_active_class); ?>" data-collapse="#mpwpb_category_active">
							<h5 class="dFlex">
								<?php MP_Custom_Layout::switch_button('mpwpb_sub_category_active', $sub_category_active_checked); ?>
								<span class="mR"><?php echo esc_html__('Enable', 'service-booking-manager') . ' <span data-input-change="mpwpb_sub_category_text">' . $sub_category_text . ' </span> ' . esc_html__('Section', 'service-booking-manager'); ?></span>
							</h5>
							<?php MPWPB_Settings::info_text('mpwpb_sub_category_active'); ?>
						</div>
						<div class="divider"></div>
						<h5 class="dFlex">
							<?php MP_Custom_Layout::switch_button('mpwpb_service_details_active', $service_details_active_checked); ?>
							<span class="mR"><?php echo esc_html__('Enable', 'service-booking-manager') . ' <span data-input-change="mpwpb_service_text">' . $service_text . ' </span> ' . esc_html__('Details', 'service-booking-manager'); ?></span>
						</h5>
						<?php MPWPB_Settings::info_text('mpwpb_service_details_active'); ?>
						<div class="divider"></div>
						<h5 class="dFlex">
							<?php MP_Custom_Layout::switch_button('mpwpb_service_duration_active', $service_duration_active_checked); ?>
							<span class="mR"><?php echo esc_html__('Enable', 'service-booking-manager') . ' <span data-input-change="mpwpb_service_text">' . $service_text . ' </span> ' . esc_html__('Duration', 'service-booking-manager'); ?></span>
						</h5>
						<?php MPWPB_Settings::info_text('mpwpb_service_duration_active'); ?>
						<div class="divider"></div>
						<div class="mp_settings_area">
							<div class="_oAuto_border">
								<div class="mpwpb_category_area mpwpb_category_header">
									<div class="mpwpb_category_item  <?php echo esc_attr($category_active_class); ?>" data-collapse="#mpwpb_category_active">
										<label class="fullWidth">
											<input type="text" data-input-text="mpwpb_category_text" name="mpwpb_category_text" class="formControl mp_name_validation" value="<?php echo esc_attr($category_text); ?>" placeholder="<?php esc_attr_e('Category', 'service-booking-manager'); ?>"/>
											<span class="textRequired">&nbsp;*</span>
										</label>
									</div>
									<div class="mpwpb_category_content">
										<div class="mpwpb_sub_category_area">
											<div class="mpwpb_sub_category_item  <?php echo esc_attr($sub_category_active_class); ?>" data-collapse="#mpwpb_sub_category_active">
												<label class="fullWidth">
													<input type="text" data-input-text="mpwpb_sub_category_text" name="mpwpb_sub_category_text" class="formControl mp_name_validation" value="<?php echo esc_attr($sub_category_text); ?>" placeholder="<?php esc_attr_e('Sub-Category', 'service-booking-manager'); ?>"/>
													<span class="textRequired">&nbsp;*</span>
												</label>
											</div>
											<div class="mpwpb_sub_category_content">
												<div class="mpwpb_service_area">
													<div class="mpwpb_service_item">
														<label class="fullWidth">
															<input type="text" data-input-text="mpwpb_service_text" name="mpwpb_service_text" class="formControl mp_name_validation" value="<?php echo esc_attr($service_text); ?>" placeholder="<?php esc_attr_e('Service', 'service-booking-manager'); ?>"/>
															<span class="textRequired">&nbsp;*</span>
														</label>
													</div>
													<div class="mpwpb_service_content">
														<h6><?php esc_html_e('Image/Icon', 'service-booking-manager'); ?></h6>
													</div>
													<div class="mpwpb_service_content">
														<h6><?php esc_html_e('Price', 'service-booking-manager'); ?>
															<span class="textRequired">&nbsp;*</span>
														</h6>
													</div>
													<div class="mpwpb_service_content <?php echo esc_attr($service_duration_active_class); ?>" data-collapse="#mpwpb_service_duration_active">
														<h6><?php esc_html_e('Duration', 'service-booking-manager'); ?></h6>
													</div>
													<div class="mpwpb_service_item <?php echo esc_attr($service_details_active_class); ?>" data-collapse="#mpwpb_service_details_active">
														<h6><?php esc_html_e('Details', 'service-booking-manager'); ?></h6>
													</div>
												</div>
											</div>
										</div>
									</div>
								</div>
								<div class="mp_item_insert mp_sortable_area">
									<?php
										if (sizeof($category_infos) > 0) {
											foreach ($category_infos as $categories) {
												$this->category_item($category_count, $active, $categories);
												$category_count = 1;
											}
										}
										else {
											$this->category_item($category_count, $active);
										}
									?>
								</div>
							</div>
							<div class="<?php echo esc_attr($category_active_class); ?>" data-collapse="#mpwpb_category_active">
								<?php MP_Custom_Layout::add_new_button(esc_html__('Add New ', 'service-booking-manager') . ' ' . $category_text , 'mpwpb_add_category', '_successButton_xs_mT_xs','','mpwpb_category_text'); ?>
								<div class="mp_hidden_content">
									<div class="mp_hidden_item">
										<?php $this->category_item(1, $active); ?>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
				<?php
			}
			public function category_item($category_count, $active, $categories = array()) {
				$category_active_class = $active['category'];
				$categories = $categories && is_array($categories) ? $categories : array();
				$unique_name = uniqid();
				$category_name = array_key_exists('category', $categories) ? $categories['category'] : '';
				$category_icon = array_key_exists('icon', $categories) ? $categories['icon'] : '';
				$category_image = array_key_exists('image', $categories) ? $categories['image'] : '';
				?>
				<div class="<?php echo esc_attr($category_count > 0 ? $category_active_class : ''); ?>" <?php if ($category_count > 0) { ?>  data-collapse="#mpwpb_category_active" <?php } ?>>
					<div class="mpwpb_category_area mp_remove_area">
						<div class="mpwpb_category_item  <?php echo esc_attr($category_active_class); ?>" data-collapse="#mpwpb_category_active">
							<div class="groupContent">
								<?php if ($category_count > 0) {
									MP_Custom_Layout::remove_button();
								} ?>
								<label class="fullWidth">
									<input type="hidden" name="mpwpb_category_hidden_id[]" value="<?php echo esc_attr($unique_name); ?>"/>
									<input type="text" name="mpwpb_category_name[]" class="formControl mp_name_validation" value="<?php echo esc_attr($category_name); ?>" placeholder="<?php esc_attr_e('Category Name', 'service-booking-manager'); ?>"/>
								</label>
							</div>
							<div class="divider"></div>
							<?php do_action('mp_add_icon_image', 'mpwpb_category_img_icon[]', $category_icon, $category_image); ?>
						</div>
						<div class="mpwpb_category_content">
							<?php $this->sub_category($unique_name, $active, $categories); ?>
						</div>
					</div>
				</div>
				<?php
			}
			public function sub_category($unique_name, $active, $categories) {
				$sub_category_text = $active['sub_category_text'];
				$sub_category_active_class = $active['sub_category'];
				$sub_categories = array_key_exists('sub_category', $categories) ? $categories['sub_category'] : array();
				$sub_category_count = 0;
				?>
				<div class="mp_settings_area">
					<div class="mp_sortable_area mp_item_insert">
						<?php
							if (sizeof($sub_categories) > 0) {
								foreach ($sub_categories as $sub_category) {
									$this->sub_category_item($sub_category_count, $unique_name, $active, $sub_category);
									$sub_category_count = 1;
								}
							}
							else {
								$this->sub_category_item(0, $unique_name, $active);
							}
						?>
					</div>
					<div class="<?php echo esc_attr($sub_category_active_class); ?>" data-collapse="#mpwpb_sub_category_active">
						<?php MP_Custom_Layout::add_new_button(esc_html__('Add New ', 'service-booking-manager') . $sub_category_text, 'mpwpb_add_sub_category', '_navy_blueButton_xs_mTB_xs','','mpwpb_sub_category_text'); ?>
						<div class="mp_hidden_content">
							<div class="mp_hidden_item">
								<?php $this->sub_category_item(1, $unique_name, $active); ?>
							</div>
						</div>
					</div>
				</div>
				<?php
			}
			public function sub_category_item($sub_category_count, $unique_name, $active, $sub_category_item = array()) {
				$category_active_class = $active['category'];
				$sub_category_active_class = $active['sub_category'];
				$sub_category = array_key_exists('name', $sub_category_item) ? $sub_category_item['name'] : '';
				$sub_category_icon = array_key_exists('icon', $sub_category_item) ? $sub_category_item['icon'] : '';
				$sub_category_img = array_key_exists('image', $sub_category_item) ? $sub_category_item['image'] : '';
				$sub_category_name = 'mpwpb_sub_category_' . $unique_name . '[]';
				$sub_category_imag_icon_name = 'mpwpb_sub_category_img_icon_' . $unique_name . '[]';
				$sub_category_hidden_name = 'mpwpb_sub_category_hidden_id_' . $unique_name . '[]';
				$unique_name = uniqid();
				?>
				<div class="<?php echo esc_attr($sub_category_count > 0 ? $category_active_class : ''); ?>" <?php if ($sub_category_count > 0) { ?>  data-collapse="#mpwpb_category_active" <?php } ?>>
					<div class="<?php echo esc_attr($sub_category_count > 0 ? $sub_category_active_class : ''); ?>" <?php if ($sub_category_count > 0) { ?>data-collapse="#mpwpb_sub_category_active" <?php } ?>>
						<div class="mpwpb_sub_category_area mp_remove_area">
							<div class="mpwpb_sub_category_item  <?php echo esc_attr($sub_category_active_class); ?>" data-collapse="#mpwpb_sub_category_active">
								<div class="groupContent">
									<?php if ($sub_category_count > 0) {
										MP_Custom_Layout::remove_button();
									} ?>
									<label class="fullWidth">
										<input type="hidden" name="<?php echo esc_attr($sub_category_hidden_name); ?>" value="<?php echo esc_attr($unique_name); ?>"/>
										<input type="text" name="<?php echo esc_attr($sub_category_name); ?>" class="formControl mp_name_validation" value="<?php echo esc_attr($sub_category); ?>" placeholder="<?php esc_attr_e('Sub-Category', 'service-booking-manager'); ?>"/>
									</label>
								</div>
								<div class="divider"></div>
								<?php do_action('mp_add_icon_image', $sub_category_imag_icon_name, $sub_category_icon, $sub_category_img); ?>
							</div>
							<div class="mpwpb_sub_category_content">
								<?php $this->service($unique_name, $active, $sub_category_item); ?>
							</div>
						</div>
					</div>
				</div>
				<?php
			}
			public function service($unique_name, $active, $sub_categories = array()) {
				$service_text = $active['service_text'];
				$services = array_key_exists('service', $sub_categories) ? $sub_categories['service'] : array();
				?>
				<div class="mp_settings_area">
					<div class="mp_sortable_area mp_item_insert">
						<?php
							if (sizeof($services) > 0) {
								foreach ($services as $service) {
									$this->service_item($unique_name, $active, $service);
								}
							}
							else {
								$this->service_item($unique_name, $active);
							}
						?>
					</div>
					<?php MP_Custom_Layout::add_new_button(esc_html__('Add New ', 'service-booking-manager') . $service_text, 'mp_add_item', '_warningButton_xs_mTB_xs','','mpwpb_service_text'); ?>
					<div class="mp_hidden_content">
						<div class="mp_hidden_item">
							<?php $this->service_item($unique_name, $active); ?>
						</div>
					</div>
				</div>
				<?php
			}
			public function service_item($unique_name, $active, $service_info = array()) {
				$image_name = 'mpwpb_service_img_' . $unique_name . '[]';
				$icon = array_key_exists('icon', $service_info) ? $service_info['icon'] : '';
				$image = array_key_exists('image', $service_info) ? $service_info['image'] : '';
				$service_name = 'mpwpb_service_name_' . $unique_name . '[]';
				$service = array_key_exists('name', $service_info) ? $service_info['name'] : '';
				$details_name = 'mpwpb_service_details_' . $unique_name . '[]';
				$details = array_key_exists('details', $service_info) ? $service_info['details'] : '';
				$price_name = 'mpwpb_service_price_' . $unique_name . '[]';
				$price = array_key_exists('price', $service_info) ? $service_info['price'] : '';
				$duration_name = 'mpwpb_service_duration_' . $unique_name . '[]';
				$duration = array_key_exists('duration', $service_info) ? $service_info['duration'] : '';
				$details_active_class = $active['service_details'];
				$duration_active_class = $active['service_duration'];
				?>
				<div class="mpwpb_service_area mp_remove_area">
					<div class="mpwpb_service_item">
						<div class="groupContent">
							<?php MP_Custom_Layout::remove_button(); ?>
							<label class="fullWidth">
								<input type="text" name="<?php echo esc_attr($service_name); ?>" class="formControl mp_name_validation" value="<?php echo esc_attr($service); ?>" placeholder="<?php _e('Service Name', 'service-booking-manager'); ?>"/>
							</label>
							<?php MP_Custom_Layout::move_button(); ?>
						</div>
					</div>
					<div class="mpwpb_service_content">
						<?php do_action('mp_add_icon_image', $image_name, $icon, $image); ?>
					</div>
					<div class="mpwpb_service_content">
						<label class="fullWidth">
							<input type="text" name="<?php echo esc_attr($price_name); ?>" class="formControl mp_price_validation" value="<?php echo esc_attr($price); ?>"/>
						</label>
					</div>
					<div class="mpwpb_service_content <?php echo esc_attr($duration_active_class); ?>" data-collapse="#mpwpb_service_duration_active">
						<label class="fullWidth">
							<input type="text" name="<?php echo esc_attr($duration_name); ?>" class="formControl mp_name_validation" value="<?php echo esc_attr($duration); ?>" placeholder="<?php _e('Service Duration', 'service-booking-manager'); ?>"/>
						</label>
					</div>
					<div class="mpwpb_service_item <?php echo esc_attr($details_active_class); ?>" data-collapse="#mpwpb_service_details_active">
						<label class="fullWidth">
							<textarea name="<?php echo esc_attr($details_name); ?>" class='formControl ' placeholder="<?php esc_attr_e('Service details...', 'service-booking-manager'); ?>"><?php echo esc_html($details); ?></textarea>
						</label>
					</div>
				</div>
				<?php
			}
			/*******************/
			public function save_price_settings($post_id) {
				if (get_post_type($post_id) == MPWPB_Function::get_cpt()) {
					/****************************/
					$active_category = MP_Global_Function::get_submit_info('mpwpb_category_active') ? 'on' : 'off';
					$active_sub_category = MP_Global_Function::get_submit_info('mpwpb_sub_category_active') ? 'on' : 'off';
					$active_sub_category = $active_category == 'on' ? $active_sub_category : 'off';
					update_post_meta($post_id, 'mpwpb_category_active', $active_category);
					update_post_meta($post_id, 'mpwpb_sub_category_active', $active_sub_category);
					$active_service_details = MP_Global_Function::get_submit_info('mpwpb_service_details_active') ? 'on' : 'off';
					update_post_meta($post_id, 'mpwpb_service_details_active', $active_service_details);
					/****************************/
					$category_text = MP_Global_Function::get_submit_info('mpwpb_category_text', MPWPB_Function::get_category_text($post_id));
					update_post_meta($post_id, 'mpwpb_category_text', $category_text);
					$sub_category_text = MP_Global_Function::get_submit_info('mpwpb_sub_category_text', MPWPB_Function::get_sub_category_text($post_id));
					update_post_meta($post_id, 'mpwpb_sub_category_text', $sub_category_text);
					$service_text = MP_Global_Function::get_submit_info('mpwpb_service_text', MPWPB_Function::get_service_text($post_id));
					update_post_meta($post_id, 'mpwpb_service_text', $service_text);
					/****************************/
					$category_infos = array();
					$category_hidden_id = MP_Global_Function::get_submit_info('mpwpb_category_hidden_id', array());
					$categories = MP_Global_Function::get_submit_info('mpwpb_category_name', array());
					$categories_img_icon = MP_Global_Function::get_submit_info('mpwpb_category_img_icon', array());
					$categories_count = sizeof($categories);
					if ($categories_count > 0) {
						$categories_count = $active_category == 'on' ? $categories_count : 1;
						for ($i = 0; $i < $categories_count; $i++) {
							$sub_category_infos = [];
							$sub_category_hidden_id = MP_Global_Function::get_submit_info('mpwpb_sub_category_hidden_id_' . $category_hidden_id[$i], array());
							$sub_categories = MP_Global_Function::get_submit_info('mpwpb_sub_category_' . $category_hidden_id[$i], array());
							$sub_categories_img_icon = MP_Global_Function::get_submit_info('mpwpb_sub_category_img_icon_' . $category_hidden_id[$i], array());
							$sub_categories_count = sizeof($sub_categories);
							if ($sub_categories_count > 0) {
								$sub_categories_count = $active_category == 'on' && $active_sub_category == 'on' ? $sub_categories_count : 1;
								for ($j = 0; $j < $sub_categories_count; $j++) {
									$service_infos = [];
									$service_names = MP_Global_Function::get_submit_info('mpwpb_service_name_' . $sub_category_hidden_id[$j], array());
									$service_price = MP_Global_Function::get_submit_info('mpwpb_service_price_' . $sub_category_hidden_id[$j], array());
									$images = MP_Global_Function::get_submit_info('mpwpb_service_img_' . $sub_category_hidden_id[$j], array());
									$details = MP_Global_Function::get_submit_info('mpwpb_service_details_' . $sub_category_hidden_id[$j], array());
									$duration = MP_Global_Function::get_submit_info('mpwpb_service_duration_' . $sub_category_hidden_id[$j], array());
									if (sizeof($service_names) > 0 && sizeof($service_price) > 0) {
										foreach ($service_names as $key => $service_name) {
											if ($service_name && $service_price[$key] != '') {
												$service_infos[$key]['name'] = $service_name;
												$service_infos[$key]['price'] = $service_price[$key];
												$service_infos[$key]['details'] = array_key_exists($key, $details) ? $details[$key] : '';
												$service_infos[$key]['duration'] = array_key_exists($key, $duration) ? $duration[$key] : '';
												$current_image_icon = array_key_exists($key, $images) ? $images[$key] : '';
												$service_infos[$key]['icon'] = '';
												$service_infos[$key]['image'] = '';
												if ($current_image_icon) {
													if (preg_match('/\s/', $current_image_icon)) {
														$service_infos[$key]['icon'] = $current_image_icon;
													}
													else {
														$service_infos[$key]['image'] = $current_image_icon;
													}
												}
											}
										}
									}
									if (sizeof($service_infos) > 0) {
										$current_sub_categories_img_icon = $active_category == 'on' && $active_sub_category == 'on' ? $sub_categories_img_icon[$j] : '';
										$sub_category_infos[$j]['icon'] = '';
										$sub_category_infos[$j]['image'] = '';
										if ($current_sub_categories_img_icon) {
											if (preg_match('/\s/', $current_sub_categories_img_icon)) {
												$sub_category_infos[$j]['icon'] = $current_sub_categories_img_icon;
											}
											else {
												$sub_category_infos[$j]['image'] = $current_sub_categories_img_icon;
											}
										}
										$sub_category_infos[$j]['name'] = $active_category == 'on' && $active_sub_category == 'on' ? $sub_categories[$j] : '';
										$sub_category_infos[$j]['service'] = $service_infos;
									}
								}
							}
							if (sizeof($sub_category_infos) > 0) {
								if ($active_category == 'on') {
									if ($categories[$i]) {
										$current_categories_img_icon = $categories_img_icon[$i];
										$category_infos[$i]['icon'] = '';
										$category_infos[$i]['image'] = '';
										if ($current_categories_img_icon) {
											if (preg_match('/\s/', $current_categories_img_icon)) {
												$category_infos[$i]['icon'] = $current_categories_img_icon;
											}
											else {
												$category_infos[$i]['image'] = $current_categories_img_icon;
											}
										}
										$category_infos[$i]['category'] = $categories[$i];
										$category_infos[$i]['sub_category'] = $sub_category_infos;
									}
								}
								else {
									$category_infos[$i]['category'] = '';
									$category_infos[$i]['sub_category'] = $sub_category_infos;
								}
							}
						}
					}
					//echo '<pre>'; print_r( $category_infos ); echo '</pre>'; die();
					update_post_meta($post_id, 'mpwpb_category_infos', $category_infos);
				}
			}
		}
		new MPWPB_Price_Settings();
	}