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
				// mpwpb update category service
				add_action('wp_ajax_mpwpb_update_category_service', [$this, 'update_category_service']);
				// mpwpb update sub category service
				add_action('wp_ajax_mpwpb_update_sub_category', [$this, 'update_sub_category_service']);
				// mpwpb delete category service
				add_action('wp_ajax_mpwpb_category_service_delete_item', [$this, 'delete_category_service']);
				// Load  category by ajax
				add_action('wp_ajax_mpwpb_load_parent_category', [$this, 'load_parent_category']);
				// Load  category by ajax
				add_action('wp_ajax_mpwpb_load_sub_category', [$this, 'load_sub_category']);
				// Del sub category by ajax
				add_action('wp_ajax_mpwpb_sub_category_delete', [$this, 'delete_sub_category']);
				// Category sort order
				add_action('wp_ajax_mpwpb_sort_category',[$this,'sort_category']);
				// Sub Category sort order
				add_action('wp_ajax_mpwpb_sort_sub_category',[$this,'sort_sub_category']);
			}
			private function ensure_post_edit_permission($post_id): bool {
				$post_id = absint($post_id);
				if (!$post_id || !current_user_can('edit_post', $post_id)) {
					wp_send_json_error('Unauthorized request');
					return false;
				}
				return true;
			}
			public function sort_category() {
				if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'mpwpb_admin_nonce')) {
					wp_send_json_error('Invalid nonce!'); // Prevent unauthorized access
				}
				$post_id = isset($_POST['postID']) ? absint(wp_unslash($_POST['postID'])) : 0;
				if (!$this->ensure_post_edit_permission($post_id)) {
					return;
				}
				$sorted_ids = isset($_POST['sortedIDs']) ? array_map('intval', $_POST['sortedIDs']) : [];
				$categories = $this->get_categories($post_id);
				$new_ordered = [];
				foreach ($sorted_ids as $id) {
					if (isset($categories[$id])) {
						$new_ordered[$id] = $categories[$id];
					}
				}
				update_post_meta($post_id, 'mpwpb_category_service', $new_ordered);
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
			public function sort_sub_category() {
				if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'mpwpb_admin_nonce')) {
					wp_send_json_error('Invalid nonce!'); // Prevent unauthorized access
				}
				$post_id = isset($_POST['postID']) ? absint(wp_unslash($_POST['postID'])) : 0;
				if (!$this->ensure_post_edit_permission($post_id)) {
					return;
				}
				$sorted_ids = isset($_POST['sortedIDs']) ? array_map('intval', $_POST['sortedIDs']) : [];
				$categories = $this->get_sub_categories($post_id);
				$new_ordered = [];
				foreach ($sorted_ids as $id) {
					if (isset($categories[$id])) {
						$new_ordered[$id] = $categories[$id];
					}
				}
				update_post_meta($post_id, 'mpwpb_sub_category_service', $new_ordered);
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
			public function category_settings_section($post_id) {
				$use_sub_category = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_use_sub_category', 'off');
				$active_class = $use_sub_category == 'on' ? 'mActive' : '';
				$sub_category_checked = $use_sub_category == 'on' ? 'checked' : '';
				?>
                <div class="mpwpb-category-lists">
					<?php $this->show_category_items($post_id); ?>
                </div>
                <div class="mpwpb_add_new_category_btn_holder">
                    <button class="button mpwpb-category-new mpwpb-category-new-width" data-modal="mpwpb-category-new" type="button"><?php esc_html_e('Add Category', 'service-booking-manager'); ?></button>
                </div>
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
								<?php MPWPB_Custom_Layout::switch_button('mpwpb_use_sub_category', $sub_category_checked); ?>
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
                            <?php do_action('mpwpb_add_icon_image','mpwpb_category_image_icon'); ?>
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
				$post_id = isset($_POST['postID']) ? absint(wp_unslash($_POST['postID'])) : 0;
				if (!$this->ensure_post_edit_permission($post_id)) {
					return;
				}
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
				$post_id = isset($_POST['postID']) ? absint(wp_unslash($_POST['postID'])) : 0;
				if (!$this->ensure_post_edit_permission($post_id)) {
					return;
				}
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
                $is_sub = false;
                $cat_ids = array_column($sub_categories, 'cat_id');
                if (in_array($parentId, $cat_ids)) {
                    $is_sub = true;
                }
                if( $is_sub ){
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
							<div class="parent-category-items" data-id="<?php echo esc_attr($parent_key); ?>">
								<div class="image-block" data-imageid="<?php echo esc_attr($category['image']); ?>">
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
												<div class="image-block" data-imageid="<?php echo esc_attr($sub_category['image']); ?>">
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
						</div>
						
					<?php endforeach; ?>
				<?php endif; ?>
				<?php
			}
			public function get_categories($post_id) {
				$service_category = get_post_meta($post_id, 'mpwpb_category_service', true);
				$service_category = !empty($service_category) ? $service_category : [];
				
				return $service_category;
			}
			public function get_category_by_id($post_id,$cat_id) {
				if($cat_id!=''){
					$service_category = get_post_meta($post_id, 'mpwpb_category_service', true);
					$service_category = !empty($service_category) ? $service_category : [];
//					return $service_category[$cat_id];
                    return array_key_exists($cat_id, $service_category) ? $service_category[$cat_id] : '';
				}
                return null;
			}
			public function get_sub_category_by_id($post_id,$cat_id) {
				if(!empty($cat_id)){
					$sub_category = get_post_meta($post_id, 'mpwpb_sub_category_service', true);
					$sub_category = !empty($sub_category) ? $sub_category : [];
					//return $sub_category[$cat_id];
					return array_key_exists($cat_id, $sub_category) ? $sub_category[$cat_id] : '';
				}
				return null;
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
				$post_id = isset($_POST['category_postID']) ? absint(wp_unslash($_POST['category_postID'])) : 0;
				if (!$this->ensure_post_edit_permission($post_id)) {
					return;
				}
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
				$post_id = isset($_POST['category_postID']) ? absint(wp_unslash($_POST['category_postID'])) : 0;
				if (!$this->ensure_post_edit_permission($post_id)) {
					return;
				}
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
				$post_id = isset($_POST['category_postID']) ? absint(wp_unslash($_POST['category_postID'])) : 0;
				if (!$this->ensure_post_edit_permission($post_id)) {
					return;
				}
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
				$post_id = isset($_POST['category_postID']) ? absint(wp_unslash($_POST['category_postID'])) : 0;
				if (!$this->ensure_post_edit_permission($post_id)) {
					return;
				}
				$categories = $this->get_categories($post_id);
				if (!empty($categories)) {
					if (isset($_POST['itemId'])) {
						$deleted_cat_id = sanitize_text_field(wp_unslash($_POST['itemId']));
						unset($categories[$deleted_cat_id]);
						// Deliberately NOT array_values()-reindexed: existing ids (this array's own
						// keys) are referenced elsewhere as stable ids (mpwpb_sub_category_service's
						// cat_id, mpwpb_service's parent_cat/sub_cat). Reindexing here would silently
						// shift every subsequent category's id and misattribute other services.
						$this->cascade_category_deletion($post_id, $deleted_cat_id);
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
				$post_id = isset($_POST['category_postID']) ? absint(wp_unslash($_POST['category_postID'])) : 0;
				if (!$this->ensure_post_edit_permission($post_id)) {
					return;
				}
				$sub_categories = $this->get_sub_categories($post_id);
				if (!empty($sub_categories)) {
					if (isset($_POST['itemId'])) {
						$deleted_sub_id = sanitize_text_field(wp_unslash($_POST['itemId']));
						unset($sub_categories[$deleted_sub_id]);
						// Not reindexed — same reasoning as delete_category_service(): this array's
						// keys are the stable ids mpwpb_service's sub_cat field references.
						$this->reassign_services_for_deleted_ids($post_id, [], [$deleted_sub_id]);
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
			/**
			 * When a top-level category is deleted, also delete every subcategory
			 * that pointed at it (cat_id match) — without reindexing, same as the
			 * parent delete — and reassign services affected by either to
			 * "Uncategorized" (both fields cleared, matching the mockup's flat
			 * reassign-to-null behavior rather than "promoted to parent").
			 */
			private function cascade_category_deletion($post_id, $deleted_cat_id) {
				$sub_categories = $this->get_sub_categories($post_id);
				$deleted_sub_ids = [];
				if (!empty($sub_categories)) {
					foreach ($sub_categories as $sub_id => $sub_cat) {
						if (isset($sub_cat['cat_id']) && $sub_cat['cat_id'] == $deleted_cat_id) {
							$deleted_sub_ids[] = $sub_id;
							unset($sub_categories[$sub_id]);
						}
					}
					if (!empty($deleted_sub_ids)) {
						update_post_meta($post_id, 'mpwpb_sub_category_service', $sub_categories);
					}
				}
				$this->reassign_services_for_deleted_ids($post_id, [$deleted_cat_id], $deleted_sub_ids);
			}
			/**
			 * Clear parent_cat/sub_cat (send to "Uncategorized") on every service
			 * that referenced one of the just-deleted category/subcategory ids.
			 */
			private function reassign_services_for_deleted_ids($post_id, $deleted_cat_ids, $deleted_sub_ids) {
				if (empty($deleted_cat_ids) && empty($deleted_sub_ids)) {
					return;
				}
				$services = get_post_meta($post_id, 'mpwpb_service', true);
				$services = is_array($services) ? $services : [];
				if (empty($services)) {
					return;
				}
				$changed = false;
				foreach ($services as $service_id => $service) {
					$parent_matches = !empty($deleted_cat_ids) && isset($service['parent_cat']) && in_array($service['parent_cat'], $deleted_cat_ids, false);
					$sub_matches = !empty($deleted_sub_ids) && isset($service['sub_cat']) && in_array($service['sub_cat'], $deleted_sub_ids, false);
					if ($parent_matches || $sub_matches) {
						$services[$service_id]['parent_cat'] = '';
						$services[$service_id]['sub_cat'] = '';
						$changed = true;
					}
				}
				if ($changed) {
					update_post_meta($post_id, 'mpwpb_service', $services);
				}
			}
		}
		new MPWPB_Service_Category();
	}
