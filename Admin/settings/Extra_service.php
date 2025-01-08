<?php
	/*
* @Author 		engr.sumonazma@gmail.com
* Copyright: 	mage-people.com
*/
	if ( ! defined( 'ABSPATH' ) ) {
		die;
	} // Cannot access pages directly.
	if ( ! class_exists('MPWPB_Extra_service_Settings') ) {
		class MPWPB_Extra_service_Settings {
			public function __construct() {
				add_action( 'add_mpwpb_settings_tab_content', [ $this, 'extra_service_settings' ], 10, 1 );
				// save extra service
				add_action('wp_ajax_mpwpb_save_ex_service', [ $this,'save_ex_service']);
				add_action('wp_ajax_nopriv_mpwpb_save_ex_service', [ $this,'save_ex_service']);

				// mpwpb update extra service
				add_action('wp_ajax_mpwpb_ext_service_update', [$this, 'ext_service_update_item']);
				add_action('wp_ajax_nopriv_mpwpb_ext_service_update', [$this, 'ext_service_update_item']);
				
				// mpwpb delete extra service
				add_action('wp_ajax_mpwpb_ext_service_delete_item', [$this, 'extra_service_delete_item']);
				add_action('wp_ajax_nopriv_mpwpb_ext_service_delete_item', [$this, 'extra_service_delete_item']);
			}
			
			public function ext_service_update_item() {
				$post_id = $_POST['service_postID'];
				$ext_services = $this->get_extra_services($post_id);
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

				$new_data = [ 
					'name'=> sanitize_text_field($_POST['service_name']), 
					'price'=> sanitize_text_field($_POST['service_price']),
					'qty'=> sanitize_text_field($_POST['service_qty']),
					'details'=> sanitize_text_field($_POST['service_description']),
					'icon'=> $iconClass,
					'image'=> $imageID,
				];

				if( ! empty($ext_services)){
					if(isset($_POST['service_itemId'])){
						$ext_services[$_POST['service_itemId']]=$new_data;
					}
				}

				update_post_meta($post_id, 'mpwpb_extra_service', $ext_services);
				ob_start();
				$resultMessage = __('Data Updated Successfully', 'mptbm_plugin_pro');
				$this->show_extra_service($post_id);
				$html_output = ob_get_clean();
				wp_send_json_success([
					'message' => $resultMessage,
					'html' => $html_output,
				]);
				die;
			}
			
			public function save_ex_service() {
				$post_id = $_POST['service_postID'];
				update_post_meta($post_id, 'mpwpb_extra_service_active', 'on');
				$extra_services = $this->get_extra_services($post_id);
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

				$new_data = [ 
					'name'=> sanitize_text_field($_POST['service_name']), 
					'price'=> sanitize_text_field($_POST['service_price']),
					'qty'=> sanitize_text_field($_POST['service_qty']),
					'details'=> sanitize_text_field($_POST['service_description']),
					'icon'=> $iconClass,
					'image'=> $imageID,
				];
				array_push($extra_services,$new_data);
				update_post_meta($post_id, 'mpwpb_extra_service', $extra_services);
				ob_start();
				$resultMessage = __('Data Updated Successfully', 'mptbm_plugin_pro');
				$this->show_extra_service($post_id);
				$html_output = ob_get_clean();
				wp_send_json_success([
					'message' => $resultMessage,
					'html' => $html_output,
				]);
				die;
			}

			public function get_extra_services($post_id){
				$extra_services = MP_Global_Function::get_post_info( $post_id, 'mpwpb_extra_service',[]);
				$services = [];
				foreach ( $extra_services as $value ) {
					if (isset($value['group_service_info'])) {
						$services = array_merge($services, $value['group_service_info']);
					}
				}
				if(!empty($services)){
					update_post_meta($post_id, 'mpwpb_extra_service', $services);
					return $services;
				}else{
					return $extra_services;
				}
				
			}

			public function extra_service_settings( $post_id ) {
				$extra_service_active               = MP_Global_Function::get_post_info( $post_id, 'mpwpb_extra_service_active', 'off' );
				$active_class         				= $extra_service_active == 'on' ? 'mActive' : '';
				$extra_service_checked       		= $extra_service_active == 'on' ? 'checked' : '';
				?>
				<div class="tabsItem mpwpb_extra_service_settings" data-tabs="#mpwpb_extra_service_settings">
					<header>
						<h2><?php esc_html_e('Extra Service Configuration', 'service-booking-manager'); ?></h2>
						<span><?php esc_html_e('Here you can configure Extra Service.', 'service-booking-manager'); ?></span>
					</header>
					<section class="section">
							<h2><?php esc_html_e('Extra Service Settings', 'service-booking-manager'); ?></h2>
							<span><?php esc_html_e('Extra Service Settings', 'service-booking-manager'); ?></span>
					</section>

					<section>
						<label class="label">
							<div>
								<p><?php esc_html_e( 'Enable Extra Service', 'service-booking-manager' ); ?></p>
								<span><?php esc_html_e('Enable Extra Service.', 'service-booking-manage'); ?></span>
							</div>
							<div>
								<?php MP_Custom_Layout::switch_button( 'mpwpb_extra_service_active', $extra_service_checked); ?>
							</div>
						</label>
                    </section>
					<section class="mpwpb-extra-section <?php echo $active_class; ?>" data-collapse="#mpwpb_extra_service_active">
						<table class="table extra-service-table mB">
							<thead>
								<tr>
									<th style="width:66px"><?php _e('Image','service-booking-manager'); ?></th>
									<th style="width:150px"><?php _e('Service Title','service-booking-manager'); ?></th>
									<th><?php _e('Description','service-booking-manager'); ?></th>
									<th style="width:90px"><?php _e('Quantity','service-booking-manager'); ?></th>
									<th style="width:90px"><?php _e('Price','service-booking-manager'); ?></th>
									<th style="width:92px"><?php _e('Action','service-booking-manager'); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php $this->show_extra_service($post_id); ?>
							</tbody>
						</table>
						<button class="button mpwpb-extra-service-new" data-modal="mpwpb-extra-service-new" type="button"><?php _e('Add Extra Service','service-booking-manager'); ?></button>
					</section>
					<!-- sidebar collapse open -->
					<div class="mpwpb-modal-container" data-modal-target="mpwpb-extra-service-new">
						<div class="mpwpb-modal-content">
							<span class="mpwpb-modal-close"><i class="fas fa-times"></i></span>
							<div class="title">
								<h3><?php _e('Add Extra Service','service-booking-manager'); ?></h3>
								<div id="mpwpb-service-msg"></div>
							</div>
							<div class="content">
								<div id="mpwpb-ex-service-msg"></div>
								<input type="hidden" name="mpwpb_ext_post_id" value="<?php echo $post_id; ?>"> 
								<input type="hidden" name="mpwpb_ext_service_item_id" value="">
								<label>
									<?php _e('Service Name','service-booking-manager'); ?>
									<input type="text"   name="mpwpb_ext_service_name"> 
								</label>

								<label>
									<?php _e('Price','service-booking-manager'); ?>
									<input type="number"   name="mpwpb_ext_service_price"> 
								</label>

								<label>
									<?php _e('Quantity','service-booking-manager'); ?>
									<input type="number"   name="mpwpb_ext_service_qty"> 
								</label>

								<label>
									<?php _e('Description','service-booking-manager'); ?>
									<textarea name="mpwpb_ext_service_description" rows="5"></textarea> 
								</label>

								<label>
									<?php _e('Image/Icon','service-booking-manager'); ?>
								</label>
								<div class="mp_add_icon_image_area">
									<input type="hidden" name="mpwpb_ext_service_image_icon" value="">
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

								<div class="mpwpb_ex_service_save_button">
									<p><button id="mpwpb_ex_service_save" class="button button-primary button-large"><?php _e('Save','service-booking-manager'); ?></button> <button id="mpwpb_ex_service_save_close" class="button button-primary button-large">save close</button><p>
								</div>
								<div class="mpwpb_ex_service_update_button" style="display: none;">
									<p><button id="mpwpb_ex_service_update" class="button button-primary button-large"><?php _e('Update and Close','service-booking-manager'); ?></button><p>
								</div>
							</div>
						</div>
					</div>
				</div>
				<?php
			}

			public function show_extra_service($post_id){
				$extra_services  = $this->get_extra_services($post_id);
				if( ! empty($extra_services)):
					foreach ($extra_services as $key => $value) : 
				?>
					<tr data-id='<?php echo $key; ?>'>
						<td>
							<?php  if(!empty($value['image'])): ?>
								<img src="<?php echo esc_attr(wp_get_attachment_url($value['image'])); ?>" alt="" data-imageId="<?php echo $value['image']; ?>">
							<?php  endif; ?>
							<?php  if(!empty($value['icon'])): ?>
								<i class="<?php echo $value['icon'] ? $value['icon'] : ''; ?>"></i>
							<?php  endif; ?>
						</td>
						<td><?php echo $value['name']; ?></td>
						<td><?php echo $value['details']; ?></td>
						<td><?php echo $value['qty']; ?></td>
						<td><?php echo $value['price']; ?></td>
						<td>
							<span class="mpwpb-ext-service-edit" data-modal="mpwpb-extra-service-new"><i class="fas fa-edit"></i></span>
                            <span class="mpwpb-ext-service-delete"><i class="fas fa-trash"></i></span>
						</td>
					</tr>
				<?php
					endforeach;
				endif;
			}

			public function extra_service_delete_item(){
				$post_id = $_POST['service_postID'];
				$extra_services = $this->get_extra_services($post_id);

				if( ! empty($extra_services)){
					if(isset($_POST['itemId'])){
						unset($extra_services[$_POST['itemId']]);
						$extra_services = array_values($extra_services);
					}
				}
				$result = update_post_meta($post_id, 'mpwpb_extra_service', $extra_services);
				if($result){
					ob_start();
					$resultMessage = __('Data Deleted Successfully', 'mptbm_plugin_pro');
					$this->show_extra_service($post_id);
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

		}
		new MPWPB_Extra_service_Settings();
	}