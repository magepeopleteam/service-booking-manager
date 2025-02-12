<?php
	/**
	 * Class MPWPB_Category
	 *
	 * A class that represents a category and sub category for service booking.
	 * This class provides functionality to manage category and sub category data.
	 *
	 * @author Shahadat Hossain <raselsha@gmail.com>
	 * @version 1.0.0
	 */
	if (!defined('ABSPATH'))
		die;
	if (!class_exists('MPWPB_Category')) {
		class MPWPB_Service_Category {
			public function __construct() {
				add_action('mpwpb_show_category', [$this, 'category_settings_section']);
				// save category service
				add_action('wp_ajax_mpwpb_save_category_service', [$this, 'save_category_service']);
				add_action('wp_ajax_nopriv_mpwpb_save_category_service', [$this, 'save_category_service']);
				// mpwpb update category service
				add_action('wp_ajax_mpwpb_update_category_service', [$this, 'update_category_service']);
				add_action('wp_ajax_nopriv_mpwpb_update_category_service', [$this, 'update_category_service']);
				// mpwpb update sub category service
				add_action('wp_ajax_mpwpb_update_sub_category', [$this, 'update_sub_category_service']);
				add_action('wp_ajax_nopriv_mpwpb_update_sub_category', [$this, 'update_sub_category_service']);
				// mpwpb delete category service
				add_action('wp_ajax_mpwpb_category_service_delete_item', [$this, 'delete_category_service']);
				add_action('wp_ajax_nopriv_mpwpb_category_service_delete_item', [$this, 'delete_category_service']);
				// Load  category by ajax
				add_action('wp_ajax_mpwpb_load_parent_category', [$this, 'load_parent_category']);
				add_action('wp_ajax_nopriv_mpwpb_load_parent_category', [$this, 'load_parent_category']);
				// Load  category by ajax
				add_action('wp_ajax_mpwpb_load_sub_category', [$this, 'load_sub_category']);
				add_action('wp_ajax_nopriv_mpwpb_load_sub_category', [$this, 'load_sub_category']);
				// Del sub category by ajax
				add_action('wp_ajax_mpwpb_sub_category_delete', [$this, 'delete_sub_category']);
				add_action('wp_ajax_nopriv_mpwpb_sub_category_delete', [$this, 'delete_sub_category']);
			}
			public function category_settings_section($post_id) {
				$use_sub_category = MP_Global_Function::get_post_info($post_id, 'mpwpb_use_sub_category', 'off');
				$active_class = $use_sub_category == 'on' ? 'mActive' : '';
				$sub_category_checked = $use_sub_category == 'on' ? 'checked' : '';
				?>
                <div class="mpwpb-category-lists">
					<?php $this->show_category_items($post_id); ?>
                </div>
                <button class="button mpwpb-category-new" data-modal="mpwpb-category-new" type="button"><?php esc_html_e('Add Category', 'service-booking-manager'); ?></button>
                <!-- sidebar collapse open -->
                <div class="mpwpb-modal-container" data-modal-target="mpwpb-category-new">
                    <div class="mpwpb-modal-content">
                        <span class="mpwpb-modal-close"><i class="fas fa-times"></i></span>
                        <div class="title">
                            <h3><?php esc_html_e('Add Category Service', 'service-booking-manager'); ?></h3>
                            <div id="mpwpb-category-service-msg"></div>
                        </div>
                        <div class="content">
                            <input type="hidden" name="mpwpb_category_post_id" value="<?php echo esc_attr($post_id); ?>">
                            <input type="hidden" name="mpwpb_category_item_id" value="">
                            <input type="hidden" name="mpwpb_parent_item_id" value="">
                            <input type="hidden" name="mpwpb_parent_cat_id" value="">
                            <label>
								<?php esc_html_e('Category Name *', 'service-booking-manager'); ?>
                                <input type="text" name="mpwpb_category_name" placeholder="Category">
                            </label>
                            <div class="mpwpb-sub-category-enable" style="display: none;">
                                <label><?php esc_html_e('Use As Sub Category', 'service-booking-manager'); ?></label>
								<?php MP_Custom_Layout::switch_button('mpwpb_use_sub_category', $sub_category_checked); ?>
                                <div class="<?php echo esc_attr($active_class); ?>" data-collapse="#mpwpb_use_sub_category">
                                    <label><?php esc_html_e('Select Parent Category', 'service-booking-manager'); ?> </label>
                                    <div class="mpwpb-parent-category">
										<?php $this->show_parent_category_lists($post_id); ?>
                                    </div>
                                </div>
                            </div>
                            <label>
								<?php esc_html_e('Category Image/Icon', 'service-booking-manager'); ?>
                            </label>
                            <?php do_action('mp_add_icon_image','mpwpb_category_image_icon'); ?>
                            <div class="mpwpb_category_service_save_button">
                                <p>
                                    <button id="mpwpb_category_service_save" class="button button-primary button-large"><?php esc_html_e('Save', 'service-booking-manager'); ?></button>
                                    <button id="mpwpb_category_service_save_close" class="button button-primary button-large">save close</button>
                                <p>
                            </div>
                            <div class="mpwpb_category_service_update_button" style="display: none;">
                                <p>
                                    <button id="mpwpb_category_service_update" class="button button-primary button-large"><?php esc_html_e('Update and Close', 'service-booking-manager'); ?></button>
                                <p>
                            </div>
                        </div>
                    </div>
                </div>
				<?php
			}
			public function load_parent_category() {
				if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'mpwpb_admin_nonce')) {
					wp_send_json_error('Invalid nonce!'); // Prevent unauthorized access
				}
				$post_id = isset($_POST['postID']) ? sanitize_text_field(wp_unslash($_POST['postID'])) : '';
				ob_start();
				$resultMessage = esc_html__('Data Updated Successfully', 'service-booking-manager');
				$this->show_parent_category_lists($post_id);
				$html_output = ob_get_clean();
				wp_send_json_success([
					'message' => $resultMessage,
					'html' => $html_output,
				]);
				die;
			}
			public function load_sub_category() {
				if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'mpwpb_admin_nonce')) {
					wp_send_json_error('Invalid nonce!'); // Prevent unauthorized access
				}
				$post_id = isset($_POST['postID']) ? sanitize_text_field(wp_unslash($_POST['postID'])) : '';
				$parentId = isset($_POST['parentId']) ? sanitize_text_field(wp_unslash($_POST['parentId'])) : '';
				ob_start();
				$resultMessage = esc_html__('Data Updated Successfully', 'service-booking-manager');
				$this->sub_category_by_parent_id($post_id, $parentId);
				$html_output = ob_get_clean();
				wp_send_json_success([
					'message' => $resultMessage,
					'html' => $html_output,
				]);
				die;
			}
			public function show_parent_category_lists($post_id) {
				$categories = $this->get_categories($post_id);
				?>
                <select name="mpwpb_parent_cat" class="load-parent-category">
                    <option value="" selected><?php esc_html_e('Select Category', 'service-booking-manager'); ?></option>
					<?php foreach ($categories as $key => $category): ?>
                        <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($category['name']); ?></option>
					<?php endforeach; ?>
                </select>
				<?php
			}
			public function show_sub_category_lists($post_id) {
				$sub_categories = $this->get_sub_categories($post_id);
				?>
                <select name="mpwpb_sub_category">
                    <option value=""><?php esc_html_e('Select Sub Category', 'service-booking-manager'); ?></option>
					<?php foreach ($sub_categories as $key => $category): ?>
                        <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($category['name']); ?></option>
					<?php endforeach; ?>
                </select>
				<?php
			}
			public function sub_category_by_parent_id($post_id, $parentId) {
				$sub_categories = $this->get_sub_categories($post_id);
				?>
                <select name="mpwpb_sub_category">
                    <option value=""><?php esc_html_e('Select Sub Category', 'service-booking-manager'); ?></option>
					<?php foreach ($sub_categories as $key => $category):
						if ($category['cat_id'] == $parentId): ?>
                            <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($category['name']); ?></option>
						<?php endif; endforeach; ?>
                </select>
				<?php
			}
			/**
			 * set_category_service() will adjust old category data in new array structure
			 *
			 */
			public static function copy_old_category_data($post_id) {
				$category_info = get_post_meta($post_id, 'mpwpb_category_infos', true);
				$cat_service_copy =  get_post_meta($post_id, 'mpwpb_old_cat_service_copy', true);
				$cat_service_copy =$cat_service_copy?$cat_service_copy:'no';
				if (!empty($category_info) and $cat_service_copy=='no') {
					$categories = [];
					$sub_categories = [];
					$service_items = [];
					foreach ($category_info as $cat_index => $category) {
						// Add category
						if (!empty($category['category']) || !empty($category['icon']) || !empty($category['image'])) {
							$categories[] = [
								'name'  => $category['category'] ?? '',
								'icon'  => $category['icon'] ?? '',
								'image' => $category['image'] ?? '',
							];
						}
						
						if (!empty($category['sub_category'])) {
							foreach ($category['sub_category'] as $sub_cat_index => $sub_category) {
								// Add sub-category
								if (
									(!empty($sub_category['name']) || !empty($sub_category['icon']) || !empty($sub_category['image'])) 
									&& !empty($sub_category['service'])
								) {
									$sub_categories[] = [
										'name'   => $sub_category['name'] ?? '',
										'icon'   => $sub_category['icon'] ?? '',
										'image'  => $sub_category['image'] ?? '',
										'cat_id' => $cat_index,
									];
								}
								
								if (!empty($sub_category['service'])) {
									foreach ($sub_category['service'] as $service_index => $service) {
										// Add service items
										$service_items[] = [
											'name'           => $service['name'] ?? '',
											'icon'           => $service['icon'] ?? '',
											'image'          => $service['image'] ?? '',
											'details'        => $service['details'] ?? '',
											'price'          => $service['price'] ?? 0,
											'duration'       => $service['duration'] ?? '',
											'show_cat_status' => 'on',
											'parent_cat'     => $cat_index,
											'sub_cat'        => $sub_cat_index,
										];
									}
								}
							}
						}
					}

					update_post_meta($post_id, 'mpwpb_category_service', $categories);
					update_post_meta($post_id, 'mpwpb_sub_category_service', $sub_categories);
					update_post_meta($post_id, 'mpwpb_service', $service_items);
					update_post_meta($post_id, 'mpwpb_old_cat_service_copy', 'yes');
				}
			}
			public function show_category_items($post_id) {
				$categories = $this->get_categories($post_id);
				$sub_categories = $this->get_sub_categories($post_id);

				if(!empty($categories)):
					foreach ($categories as $parent_key => $category): ?>
						<div class="mpwpb-category-items" data-id="<?php echo esc_attr($parent_key); ?>">
							<div class="image-block">
								<?php if (!empty($category['image'])): ?>
									<?php echo wp_get_attachment_image($category['image'], 'medium'); ?>
								<?php endif; ?>
								<?php if (!empty($category['icon'])): ?>
									<i class="<?php echo esc_attr($category['icon']); ?>"></i>
								<?php endif; ?>
								<div class="title"><?php echo esc_html($category['name']); ?></div>
							</div>
							<div class="action">
								<span class="mpwpb-category-edit" data-modal="mpwpb-category-new"><i class="fas fa-edit"></i></span>
								<span class="mpwpb-category-delete"><i class="fas fa-trash"></i></span>
							</div>
						</div>
						<?php if(!empty($sub_categories)): ?>
							<div class="mpwpb-sub-category-lists">
								<?php foreach ($sub_categories as $child_key => $sub_category): ?>
									<?php if ($sub_category['cat_id'] == $parent_key): ?>
										<div class="mpwpb-sub-category-items" data-parent-id="<?php echo esc_attr($parent_key); ?>" data-id="<?php echo esc_attr($child_key); ?>">
											<div class="image-block">
												<?php if (!empty($sub_category['image'])): ?>
													<?php echo wp_get_attachment_image($sub_category['image'], 'medium'); ?>
												<?php endif; ?>
												<?php if (!empty($sub_category['icon'])): ?>
													<i class="<?php echo esc_attr($sub_category['icon']); ?>"></i>
												<?php endif; ?>
												<div class="title"><?php echo esc_attr($sub_category['name']); ?></div>
											</div>
											<div class="action">
												<span class="mpwpb-sub-category-edit" data-modal="mpwpb-category-new"><i class="fas fa-edit"></i></span>
												<span class="mpwpb-sub-category-delete"><i class="fas fa-trash"></i></span>
											</div>
										</div>
									<?php endif; ?>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>
					<?php endforeach; ?>
				<?php endif; ?>
				<?php
			}
			public function get_categories($post_id) {
				$old_cat_service = get_option('mpwpb_old_cat_service_copy','no');
				if($old_cat_service=='no'){
					self::copy_old_category_data($post_id);
				}
				$service_category = get_post_meta($post_id, 'mpwpb_category_service', true);
				$service_category = !empty($service_category) ? $service_category : [];
				
				return $service_category;
			}
			public function get_sub_categories($post_id) {
				$sub_category = get_post_meta($post_id, 'mpwpb_sub_category_service', true);
				$sub_category = !empty($sub_category) ? $sub_category : [];
				return $sub_category;
			}
			public function save_category_service() {
				if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'mpwpb_admin_nonce')) {
					wp_send_json_error('Invalid nonce!'); // Prevent unauthorized access
				}
				$post_id = isset($_POST['category_postID']) ? sanitize_text_field(wp_unslash($_POST['category_postID'])) : '';
				$categories = $this->get_categories($post_id);
				$sub_categories = $this->get_sub_categories($post_id);
				$iconClass = '';
				$imageID = '';
				if (isset($_POST['category_image_icon'])) {
					if (is_numeric($_POST['category_image_icon'])) {
						$imageID = sanitize_text_field(wp_unslash($_POST['category_image_icon']));
						$iconClass = '';
					} else {
						$iconClass = sanitize_text_field(wp_unslash($_POST['category_image_icon']));
						$imageID = '';
					}
				}
				if (isset($_POST['use_sub_category']) && sanitize_text_field(wp_unslash($_POST['use_sub_category'])) == 'on') {
					$new_sub_data = [
						'name' => isset($_POST['category_name'])?sanitize_text_field(wp_unslash($_POST['category_name'])):'',
						'icon' => $iconClass,
						'image' => $imageID,
						'cat_id' => isset($_POST['parent_category'])?sanitize_text_field(wp_unslash($_POST['parent_category'])):'',
						'use_sub_category' => sanitize_text_field(wp_unslash($_POST['use_sub_category'])),
					];
					array_push($sub_categories, $new_sub_data);
					update_post_meta($post_id, 'mpwpb_sub_category_service', $sub_categories);
				} else {
					$new_data = [
						'name' => isset($_POST['category_name'])?sanitize_text_field(wp_unslash($_POST['category_name'])):'',
						'icon' => $iconClass??'',
						'image' => $imageID??'',
					];
					array_push($categories, $new_data);
					update_post_meta($post_id, 'mpwpb_category_service', $categories);
				}
				ob_start();
				$resultMessage = esc_html__('Data Updated Successfully', 'service-booking-manager');
				$this->show_category_items($post_id);
				$html_output = ob_get_clean();
				wp_send_json_success([
					'message' => $resultMessage,
					'html' => $html_output,
				]);
				die;
			}
			public function update_sub_category_service() {
				if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'mpwpb_admin_nonce')) {
					wp_send_json_error('Invalid nonce!'); // Prevent unauthorized access
				}
				$post_id = isset($_POST['category_postID']) ? sanitize_text_field(wp_unslash($_POST['category_postID'])) : '';
				$sub_categories = $this->get_sub_categories($post_id);
				$iconClass = '';
				$imageID = '';
				if (isset($_POST['category_image_icon'])) {
					if (is_numeric($_POST['category_image_icon'])) {
						$imageID = sanitize_text_field(wp_unslash($_POST['category_image_icon']));
						$iconClass = '';
					} else {
						$iconClass = sanitize_text_field(wp_unslash($_POST['category_image_icon']));
						$imageID = '';
					}
				}
				$new_data = [];
				if (isset($_POST['category_name'])) {
					$new_data = [
						'name' => sanitize_text_field(wp_unslash($_POST['category_name'])),
						'icon' => $iconClass,
						'image' => $imageID,
						'cat_id' => isset($_POST['category_parentId'])?sanitize_text_field(wp_unslash($_POST['category_parentId'])):'',
					];
				}
				if (!empty($sub_categories)) {
					if (isset($_POST['category_itemId'])) {
						$sub_categories[sanitize_text_field(wp_unslash($_POST['category_itemId']))] = $new_data;
					}
				}
				update_post_meta($post_id, 'mpwpb_sub_category_service', $sub_categories);
				ob_start();
				$resultMessage = esc_html__('Data Updated Successfully', 'service-booking-manager');
				$this->show_category_items($post_id);
				$html_output = ob_get_clean();
				wp_send_json_success([
					'message' => $resultMessage,
					'html' => $html_output,
				]);
				die;
			}
			public function update_category_service() {
				if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'mpwpb_admin_nonce')) {
					wp_send_json_error('Invalid nonce!'); // Prevent unauthorized access
				}
				$post_id = isset($_POST['category_postID']) ? sanitize_text_field(wp_unslash($_POST['category_postID'])) : '';
				$categories = $this->get_categories($post_id);
				$iconClass = '';
				$imageID = '';
				if (isset($_POST['category_image_icon'])) {
					if (is_numeric($_POST['category_image_icon'])) {
						$imageID = sanitize_text_field(wp_unslash($_POST['category_image_icon']));
						$iconClass = '';
					} else {
						$iconClass = sanitize_text_field(wp_unslash($_POST['category_image_icon']));
						$imageID = '';
					}
				}
				$new_data = [];
				if (isset($_POST['category_name'])) {
					$new_data = [
						'name' => sanitize_text_field(wp_unslash($_POST['category_name'])),
						'icon' => $iconClass,
						'image' => $imageID,
					];
				}
				if (!empty($categories)) {
					if (isset($_POST['category_itemId'])) {
						$categories[sanitize_text_field(wp_unslash($_POST['category_itemId']))] = $new_data;
					}
				}
				update_post_meta($post_id, 'mpwpb_category_service', $categories);
				ob_start();
				$resultMessage = esc_html__('Data Updated Successfully', 'service-booking-manager');
				$this->show_category_items($post_id);
				$html_output = ob_get_clean();
				wp_send_json_success([
					'message' => $resultMessage,
					'html' => $html_output,
				]);
				die;
			}
			public function delete_category_service() {
				if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'mpwpb_admin_nonce')) {
					wp_send_json_error('Invalid nonce!'); // Prevent unauthorized access
				}
				$post_id = isset($_POST['category_postID']) ? sanitize_text_field(wp_unslash($_POST['category_postID'])) : '';
				$categories = $this->get_categories($post_id);
				if (!empty($categories)) {
					if (isset($_POST['itemId'])) {
						unset($categories[sanitize_text_field(wp_unslash($_POST['itemId']))]);
						$categories = array_values($categories);
					}
				}
				$result = update_post_meta($post_id, 'mpwpb_category_service', $categories);
				if ($result) {
					ob_start();
					$resultMessage = esc_html__('Data Deleted Successfully', 'service-booking-manager');
					
					if(!empty($categories)){
						$this->show_category_items($post_id);
					}

					$html_output = ob_get_clean();
					wp_send_json_success([
						'message' => $resultMessage,
						'html' => $html_output,
					]);
				} else {
					wp_send_json_success([
						'message' => 'Data not deleted',
						'html' => '',
					]);
				}
				die;
			}
			public function delete_sub_category() {
				if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'mpwpb_admin_nonce')) {
					wp_send_json_error('Invalid nonce!'); // Prevent unauthorized access
				}
				$post_id = isset($_POST['category_postID']) ? sanitize_text_field(wp_unslash($_POST['category_postID'])) : '';
				$sub_categories = $this->get_sub_categories($post_id);
				if (!empty($sub_categories)) {
					if (isset($_POST['itemId'])) {
						unset($sub_categories[sanitize_text_field(wp_unslash($_POST['itemId']))]);
						$sub_categories = array_values($sub_categories);
					}
				}
				$result = update_post_meta($post_id, 'mpwpb_sub_category_service', $sub_categories);
				if ($result) {
					ob_start();
					$resultMessage = __('Data Deleted Successfully', 'service-booking-manager');
					$this->show_category_items($post_id);
					$html_output = ob_get_clean();
					wp_send_json_success([
						'message' => $resultMessage,
						'html' => $html_output,
					]);
				} else {
					wp_send_json_success([
						'message' => 'Data not deleted',
						'html' => '',
					]);
				}
				die;
			}
		}
		$MPWPB_Category = new MPWPB_Service_Category();
	}
