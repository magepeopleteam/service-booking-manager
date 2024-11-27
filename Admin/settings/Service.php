<?php
/**
 * Service class
 * @author shahadat Hossain <raselsha@gmmail.com>
 */

use SimplePie\Category;

if(!defined('ABSPATH'))die;

if(!class_exists('MPWPB_Services')){
	class MPWPB_Services{
		public function __construct(){
			add_action('mpwpb_show_service', [$this, 'show_service']);
			
			add_action('wp_ajax_mpwpb_save_service', [$this, 'save_service']);
			add_action('wp_ajax_nopriv_mpwpb_save_service', [$this, 'save_service']);
			//update service
			add_action('wp_ajax_mpwpb_service_update', [$this, 'update_service']);
			add_action('wp_ajax_nopriv_mpwpb_service_update', [$this, 'update_service']);
			// delete service
			add_action('wp_ajax_mpwpb_service_delete_item',[$this,'delete_service']);
			add_action('wp_ajax_nopriv_mpwpb_service_delete_item',[$this,'delete_service']);
			// load service by category
			add_action('wp_ajax_mpwpb_load_service_by_category',[$this,'load_service_by_category']);
			add_action('wp_ajax_nopriv_mpwpb_load_service_by_category',[$this,'load_service_by_category']);
		}
		public function load_service_by_category(){
			$post_id = $_POST['postId'];
			$category_id = $_POST['itemId'];
			ob_start();
			$resultMessage = __('Data Updated Successfully', 'mptbm_plugin_pro');
			$this->show_service_by_category($post_id,$category_id);
			$html_output = ob_get_clean();
			wp_send_json_success([
				'message' => $resultMessage,
				'html' => $html_output,
			]);
			die;
		}
		public function show_service($post_id){
			$show_category_status = MP_Global_Function::get_post_info($post_id, 'mpwpb_show_category_status', 'off');
			$active_class = $show_category_status == 'on' ? 'mActive' : '';
			$show_category_status = $show_category_status == 'on' ? 'checked' : '';
			?>
				<div class="load-service-items-area">
					<table class="table mB">
						<thead>
							<tr>
								<th style="width:66px">Image</th>
								<th style="width:250px;text-align:left">Name</th>
								<th >Price</th>
								<th >Duration</th>
								<th style="width:65px"></th>
							</tr>
						</thead>
						<tbody class="mpwpb-service-table">
							<?php $this->show_service_items($post_id); ?>
						</tbody>
					</table>
				</div>
				<!-- sidebar collapse open -->
				<div class="mpwpb-modal-container" data-modal-target="mpwpb-service-new">
					<div class="mpwpb-modal-content">
						<span class="mpwpb-modal-close"><i class="fas fa-times"></i></span>
						<div class="title">
							<h3 ><?php _e('Add Service','service-booking-manager'); ?></h3>
							<div id="mpwpb-service-msg"></div>
						</div>
						<div class="content">
							<input type="hidden" name="mpwpb_post_id" value="<?php echo $post_id; ?>"> 
							<input type="hidden" name="service_item_id" value="">
							<input type="hidden" name="mpwpb_parent_cat_id" value="">
							<input type="hidden" name="mpwpb_sub_cat_id" value="">
							<label><?php _e('Use Category','service-booking-manager'); ?></label>
							<?php MP_Custom_Layout::switch_button('mpwpb_show_category_status', $show_category_status); ?>
							<div class="<?php echo $active_class ?>" data-collapse="#mpwpb_show_category_status" style="display:none;">
								<div class="mpwpb-parent-category">
									<label><?php _e('Select Category','service-booking-manager'); ?> </label>
									<?php
									$MPWPB_Service_Category = new MPWPB_Service_Category();
									$MPWPB_Service_Category->show_parent_category_lists($post_id); ?>
								</div>
								<div class="mpwpb-sub-category">
									<label><?php _e('Select Category','service-booking-manager'); ?> </label>
									<?php
									$MPWPB_Service_Category->show_sub_category_lists($post_id); ?>
								</div>
							</div>

							<label>
								<?php _e('Service Name','service-booking-manager'); ?>
								<input type="text"   name="service_name"> 
							</label>

							<label>
								<?php _e('Price','service-booking-manager'); ?>
								<input type="number"   name="service_price"> 
							</label>

							<label>
								<?php _e('Duration','service-booking-manager'); ?>
								<input type="number"   name="service_duration"> 
							</label>

							<label>
								<?php _e('Description','service-booking-manager'); ?>
								<textarea name="service_description" rows="5"></textarea> 
							</label>

							<label>
								<?php _e('Image/Icon','service-booking-manager'); ?>
							</label>
							<div class="mp_add_icon_image_area">
								<input type="hidden" name="service_image_icon" value="">
								<div class="mp_icon_item dNone">
									<span class="" data-add-icon=""></span>
									<span class="fas fa-times mp_remove_icon mp_icon_remove"></span>
								</div>
								<div class="mp_image_item dNone">
									<img class="" src="" alt="">
									<span class="fas fa-times mp_remove_icon mp_image_remove"></span>
								</div>
								<div class="mp_add_icon_image_button_area ">
									<button class="mp_image_add" type="button">
										<span class="fas fa-images"></span>Image</button>
									<button class="mp_icon_add" type="button" data-target-popup="#mp_add_icon_popup">
										<span class="fas fa-plus"></span>Icon</button>
								</div>
							</div>

							<div class="mpwpb_service_save_button">
								<p><button id="mpwpb_service_save" class="button button-primary button-large"><?php _e('Save','service-booking-manager'); ?></button> <button id="mpwpb_service_save_close" class="button button-primary button-large">save close</button><p>
							</div>
							<div class="mpwpb_service_update_button" style="display: none;">
								<p><button id="mpwpb_service_update" class="button button-primary button-large"><?php _e('Update and Close','service-booking-manager'); ?></button><p>
							</div>
						</div>
					</div>
				</div>
			<?php
		}

		public function show_service_by_category($post_id,$category_id){
			
			?>
				<table class="table mB">
					<thead>
						<tr>
							<th style="width:66px">Image</th>
							<th style="width:250px;text-align:left">Name</th>
							<th >Price</th>
							<th >Duration</th>
							<th style="width:65px"></th>
						</tr>
					</thead>
					<tbody class="mpwpb-service-table">
						<?php 
							$services = $this->get_services($post_id);
							foreach ($services as $key => $service) {
								if($service['parent_cat']==$category_id){
							?>
								<tr data-id="<?php echo $key; ?>" data-cat-status="<?php echo $service['show_cat_status'];?>" data-parent-cat="<?php echo $service['parent_cat'];?>" data-sub-cat="<?php echo $service['sub_cat'];?>">
									<td>
										<?php  if(!empty($service['image'])): ?>
											<img src="<?php echo esc_attr(wp_get_attachment_url($service['image'])); ?>" alt="" data-imageId="<?php echo $service['image']; ?>">
										<?php  endif; ?>
										<?php  if(!empty($service['icon'])): ?>
											<i class="<?php echo $service['icon'] ? $service['icon'] : ''; ?>"></i>
										<?php  endif; ?>
									</td>
									<td style="text-align:left"><?php echo $service['name']; ?></td>
									<td><?php echo $service['price']; ?></td>
									<td><?php echo $service['duration']; ?></td>
									<td>
										<span class="mpwpb-service-edit" data-modal="mpwpb-service-new"><i class="fas fa-edit"></i></span>
										<span class="mpwpb-service-delete"><i class="fas fa-trash"></i></span>
									</td>
								</tr>
							<?php
								}
							}
						?>
					</tbody>
				</table>
			<?php
		}

		public function update_service(){
			$post_id = $_POST['service_postID'];
			$services = $this->get_services($post_id);
			$iconClass = '';
			$imageID = '';
			if(isset($_POST['service_image_icon'])){
				if(is_numeric($_POST['service_image_icon'])){
					$imageID = sanitize_text_field($_POST['service_image_icon']);
					$iconClass ='';
				}
				else{
					$iconClass = sanitize_text_field($_POST['service_image_icon']);
					$imageID = '';
				}
			}
			$parent_cat ='';
			$sub_cat ='';
			if($_POST['service_category_status']=='on'){
				$parent_cat = $_POST['service_parent_cat'];
				$sub_cat = $_POST['service_sub_cat'];
			}

			$new_data = [ 
				'name'=> sanitize_text_field($_POST['service_name']), 
				'price'=> sanitize_text_field($_POST['service_price']),
				'duration'=> sanitize_text_field($_POST['service_duration']),
				'details'=> sanitize_text_field($_POST['service_description']),
				'icon'=> $iconClass,
				'image'=> $imageID,
				'show_cat_status'=> $_POST['service_category_status'],
				'parent_cat'=> $parent_cat,
				'sub_cat'=> $sub_cat,
			];

			if( ! empty($services)){
				if(isset($_POST['service_itemId'])){
					$services[$_POST['service_itemId']]=$new_data;
				}
			}
			update_post_meta($post_id, 'mpwpb_service', $services);
			ob_start();
			$resultMessage = __('Data Updated Successfully', 'mptbm_plugin_pro');
			$this->show_service_items($post_id);
			$html_output = ob_get_clean();
			wp_send_json_success([
				'message' => $resultMessage,
				'html' => $html_output,
			]);
			die;
		}
		public function save_service(){
			$post_id = $_POST['service_postID'];
			$services = $this->get_services($post_id);
			$iconClass = '';
			$imageID = '';
			if(isset($_POST['service_image_icon'])){
				if(is_numeric($_POST['service_image_icon'])){
					$imageID = sanitize_text_field($_POST['service_image_icon']);
					$iconClass ='';
				}
				else{
					$iconClass = sanitize_text_field($_POST['service_image_icon']);
					$imageID = '';
				}
			}
			$parent_cat ='';
			$sub_cat ='';
			if($_POST['service_category_status']=='on'){
				$parent_cat = $_POST['service_parent_cat'];
				$sub_cat = $_POST['service_sub_cat'];
			}
			$new_data = [ 
				'name'=> sanitize_text_field($_POST['service_name']), 
				'price'=> sanitize_text_field($_POST['service_price']),
				'duration'=> sanitize_text_field($_POST['service_duration']),
				'details'=> sanitize_text_field($_POST['service_description']),
				'icon'=> $iconClass,
				'image'=> $imageID,
				'show_cat_status'=> $_POST['service_category_status'],
				'parent_cat'=> $parent_cat,
				'sub_cat'=> $sub_cat,
			];
			array_push($services,$new_data);
			update_post_meta($post_id, 'mpwpb_service', $services);
			ob_start();
			$resultMessage = __('Data Updated Successfully', 'mptbm_plugin_pro');
			$this->show_service_items($post_id);
			$html_output = ob_get_clean();
			wp_send_json_success([
				'message' => $resultMessage,
				'html' => $html_output,
			]);
			die;
		}

		public function delete_service(){
			$post_id = $_POST['service_postID'];
			$services = $this->get_services($post_id);
			
			if( ! empty($services)){
				if(isset($_POST['itemId'])){
					unset($services[$_POST['itemId']]);
					$services = array_values($services);
				}
			}
			$result = update_post_meta($post_id, 'mpwpb_service', $services);
			if($result){
				ob_start();
				$resultMessage = __('Data Deleted Successfully', 'mptbm_plugin_pro');
				$this->show_service_items($post_id);
				$html_output = ob_get_clean();
				wp_send_json_success([
					'message' => $resultMessage,
					'html' => $html_output,
				]);
			}
			else{
				wp_send_json_success([
					'message' => 'Data not deleted',
					'html' => '',
				]);
			}
			die;
		}

		public function get_services($post_id){
			$service = get_post_meta($post_id,'mpwpb_service',true);
			return $service;
		}

		public function show_service_items($post_id){
			$MPWPB_Category = new MPWPB_Service_Category();
			$services = $this->get_services($post_id);
			$categories = $MPWPB_Category->get_categories($post_id);
			$sub_categories = $MPWPB_Category->get_sub_categories($post_id);
			foreach ($services as $key => $service) {
				?>
				<tr data-id="<?php echo $key; ?>" data-cat-status="<?php echo $service['show_cat_status'];?>" data-parent-cat="<?php echo $service['parent_cat'];?>" data-sub-cat="<?php echo $service['sub_cat'];?>">
					<td>
						<?php  if(!empty($service['image'])): ?>
							<img src="<?php echo esc_attr(wp_get_attachment_url($service['image'])); ?>" alt="" data-imageId="<?php echo $service['image']; ?>">
						<?php  endif; ?>
						<?php  if(!empty($service['icon'])): ?>
							<i class="<?php echo $service['icon'] ? $service['icon'] : ''; ?>"></i>
						<?php  endif; ?>
					</td>
					<td style="text-align:left"><?php echo $service['name']; ?></td>
					<td><?php echo $service['price']; ?></td>
					<td><?php echo $service['duration']; ?></td>
					<td>
						<span class="mpwpb-service-edit" data-modal="mpwpb-service-new"><i class="fas fa-edit"></i></span>
						<span class="mpwpb-service-delete"><i class="fas fa-trash"></i></span>
					</td>
				</tr>
				<?php
			}
		}
	}
	$MPWPB_Services  = new MPWPB_Services();
}
