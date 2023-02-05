<?php
	if ( ! defined( 'ABSPATH' ) ) {
		die;
	} // Cannot access pages directly.
	if ( ! class_exists( 'MPWPB_Price_Settings' ) ) {
		class MPWPB_Price_Settings {
			public function __construct() {
				add_action( 'add_mpwpb_settings_tab_content', [ $this, 'price_settings' ], 10, 1 );
				add_action( 'mpwpb_settings_save', [ $this, 'save_price_settings' ], 10, 1 );
			}
			public function price_settings( $post_id ) {
				$service_type = MPWPB_Function::get_post_info( $post_id, 'mpwpb_service_type' );
				?>
				<div class="tabsItem mpwpb_price_settings" data-tabs="#mpwpb_price_settings">
					<h5><?php echo get_the_title( $post_id ) . ' ' . esc_html__( 'Price Settings', 'mpwpb_plugin' ); ?></h5>
					<div class="divider"></div>
					<label>
						<span class="min_200"><?php esc_html_e( 'Select Service Type', 'mpwpb_plugin' ); ?></span>
						<select class="formControl max_400" name="mpwpb_service_type">
							<option disabled selected><?php esc_html_e( 'Please select service type', 'mpwpb_plugin' ); ?></option>
							<option value="car_wash" <?php echo esc_attr( $service_type == 'car_wash' ? 'selected' : '' ); ?>><?php esc_html_e( 'Wash', 'mpwpb_plugin' ); ?></option>
						</select>
					</label>
					<?php $this->price( $post_id ); ?>
					<?php $this->extra_service_settings( $post_id ); ?>
				</div>
				<?php
			}
			public function price( $post_id ) {
				$category_infos = MPWPB_Function::get_post_info( $post_id, 'mpwpb_category_infos', array() );
				$service_type   = MPWPB_Function::get_post_info( $post_id, 'mpwpb_service_type' );
				?>
				<div class="mpPanel mT">
					<div class="mpPanelHeader bgTheme" data-collapse-target="#mpwpb_settings_pricing" data-open-icon="fa-minus" data-close-icon="fa-plus">
						<h6><span data-icon class="fas fa-minus mR_xs"></span><?php _e( 'Price Settings', 'mpwpb_plugin' ); ?></h6>
					</div>
					<div class="mpPanelBody mActive" data-collapse="#mpwpb_settings_pricing">
						<div class="mp_settings_area ovAuto <?php echo esc_attr( $service_type == 'car_wash' ? '' : 'dNone' ); ?>" data-service-type="car_wash">
							<table>
								<thead>
								<tr>
									<th class="w_125"><?php esc_html_e( 'Image', 'mpwpb_plugin' ); ?></th>
									<th colspan="2"><?php esc_html_e( 'Category Name', 'mpwpb_plugin' ); ?><span class="textRequired">&nbsp;*</span></th>
									<th colspan="6"><?php esc_html_e( 'Category Details', 'mpwpb_plugin' ); ?></th>
									<th class="w_100"><?php esc_html_e( 'Action', 'mpwpb_plugin' ); ?></th>
								</tr>
								</thead>
								<tbody class="mp_item_insert mp_sortable_area">
								<?php
									if ( sizeof( $category_infos ) > 0 ) {
										foreach ( $category_infos as $categories ) {
											$this->category_item( $categories );
										}
									}
								?>
								</tbody>
							</table>
							<?php MPWPB_Layout::add_new_button( esc_html__( 'Add New category', 'mpwpb_plugin' ), 'mpwpb_add_category' ); ?>
							<div class="mp_hidden_content">
								<table>
									<tbody class="mp_hidden_item">
									<?php $this->category_item(); ?>
									</tbody>
								</table>
							</div>
						</div>
					</div>
				</div>
				<?php
			}
			public function category_item( $categories = array() ) {
				$categories     = $categories && is_array( $categories ) ? $categories : array();
				$unique_name    = uniqid();
				$category_name  = array_key_exists( 'category', $categories ) ? $categories['category'] : '';
				$category_image = array_key_exists( 'img', $categories ) ? $categories['img'] : '';
				?>
				<tr class="mp_remove_area">
					<td><?php MPWPB_Layout::single_image_button( 'mpwpb_category_image[]', $category_image ); ?></td>
					<td colspan="2">
						<label>
							<input type="hidden" name="mpwpb_hidden_name[]" value="<?php echo esc_attr( $unique_name ); ?>"/>
							<input type="text" name="mpwpb_category_name[]" class="formControl mp_name_validation" value="<?php echo esc_attr( $category_name ); ?>" placeholder="<?php esc_attr_e( 'Category Name', 'mpwpb_plugin' ); ?>"/>
						</label>
					</td>
					<td colspan="6"><?php $this->service( $unique_name, $categories ); ?></td>
					<td><?php MPWPB_Layout::move_remove_button(); ?></td>
				</tr>
				<?php
			}
			public function service( $unique_name, $special_date = array() ) {
				$services = array_key_exists( 'service', $special_date ) ? $special_date['service'] : array();
				?>
				<div class="ovAuto mp_settings_area">
					<table>
						<thead>
						<tr>
							<th class="w_125"><?php _e( 'image', 'mpwpb_plugin' ); ?></th>
							<th><?php _e( 'service', 'mpwpb_plugin' ); ?><span class="textRequired">&nbsp;*</span></th>
							<th><?php _e( 'Service Details', 'mpwpb_plugin' ); ?></th>
							<th class="w_100"><?php _e( 'Price', 'mpwpb_plugin' ); ?><span class="textRequired">&nbsp;*</span></th>
							<th class="w_100"><?php _e( 'Action', 'mpwpb_plugin' ); ?></th>
						</tr>
						</thead>
						<tbody class="mp_sortable_area mp_item_insert">
						<?php
							if ( sizeof( $services ) > 0 ) {
								foreach ( $services as $service ) {
									$this->service_item( $unique_name, $service );
								}
							}
						?>
						</tbody>
					</table>
					<?php MPWPB_Layout::add_new_button( esc_html__( 'Add New Service', 'mpwpb_plugin' ), 'mpwpb_add_category' ); ?>
					<div class="mp_hidden_content">
						<table>
							<tbody class="mp_hidden_item">
							<?php $this->service_item( $unique_name ); ?>
							</tbody>
						</table>
					</div>
				</div>
				<?php
			}
			public function service_item( $unique_name, $service_info = array() ) {
				$image_name      = 'mpwpb_service_img_' . $unique_name . '[]';
				$image           = array_key_exists( 'img', $service_info ) ? $service_info['img'] : '';
				$service_name    = 'mpwpb_service_name_' . $unique_name . '[]';
				$service         = array_key_exists( 'name', $service_info ) ? $service_info['name'] : '';
				$details_name    = 'mpwpb_service_details_' . $unique_name . '[]';
				$details         = array_key_exists( 'details_id', $service_info ) ? $service_info['details_id'] : '';
				$price_name      = 'mpwpb_service_price_' . $unique_name . '[]';
				$price           = array_key_exists( 'price', $service_info ) ? $service_info['price'] : '';
				$service_details = MPWPB_Query::query_post_type( 'mpwpb_item_details')->posts;
				?>
				<tr class="mp_remove_area">
					<td><?php MPWPB_Layout::single_image_button( $image_name, $image ); ?></td>
					<td>
						<label>
							<input type="text" name="<?php echo esc_attr( $service_name ); ?>" class="formControl mp_name_validation" value="<?php echo esc_attr( $service ); ?>" placeholder="<?php _e( 'Service Name', 'mpwpb_plugin' ); ?>"/>
						</label>
					</td>
					<td>
						<label>
							<select name="<?php echo esc_attr( $details_name ); ?>" class='formControl '>
								<option value="" disabled selected><?php _e( 'Please select details', 'mpwpb_plugin' ); ?></option>
								<?php
									foreach ( $service_details as $service_detail ) {
										$service_id = $service_detail->ID;
										?>
										<option value="<?php echo esc_attr( $service_id ) ?>" <?php echo esc_attr( $service_id == $details ? 'selected' : '' ); ?>><?php echo get_the_title( $service_id ); ?></option>
									<?php } ?>
							</select>
						</label>
					</td>
					<td>
						<label>
							<input type="text" name="<?php echo esc_attr( $price_name ); ?>" class="formControl" value="<?php echo esc_attr( $price ); ?>"/>
						</label>
					</td>
					<td><?php MPWPB_Layout::move_remove_button(); ?></td>
				</tr>
				<?php
			}
			/******************************** Extra Service Settings************************************/
			public function extra_service_settings( $post_id ) {
				$extra_services = MPWPB_Function::get_post_info( $post_id, 'mpwpb_extra_service', array() );
				?>
				<div class="mpPanel mT">
					<div class="mpPanelHeader bgTheme" data-collapse-target="#mpwpb_extra_service_setting" data-open-icon="fa-minus" data-close-icon="fa-plus">
						<h6><span data-icon class="fas fa-minus mR_xs"></span><?php _e( 'Extra service Settings', 'mpwpb_plugin' ); ?></h6>
					</div>
					<div class="mpPanelBody mActive" data-collapse="#mpwpb_extra_service_setting">
						<div class="mp_settings_area ovAuto">
							<table>
								<thead>
								<tr>
									<th><?php esc_html_e( 'Service Group Name', 'mpwpb_plugin' ); ?><span class="textRequired">&nbsp;*</span></th>
									<th colspan="4"><?php esc_html_e( 'Details', 'mpwpb_plugin' ); ?></th>
									<th class="w_100"><?php esc_html_e( 'Action', 'mpwpb_plugin' ); ?></th>
								</tr>
								</thead>
								<tbody class="mp_item_insert mp_sortable_area ">
								<?php
									if ( sizeof( $extra_services ) > 0 ) {
										foreach ( $extra_services as $group_service ) {
											$this->extra_service_group( $group_service );
										}
									}
								?>
								</tbody>
							</table>
							<?php MPWPB_Layout::add_new_button( esc_html__( 'Add New extra service', 'mpwpb_plugin' ) ); ?>
							<div class="mp_hidden_content">
								<table>
									<tbody class="mp_hidden_item">
									<?php $this->extra_service_group(); ?>
									</tbody>
								</table>
							</div>
						</div>
					</div>
				</div>
				<?php
			}
			public function extra_service_group( $group_service = array() ) {
				$unique_name = uniqid();
				$services    = array_key_exists( 'group_service', $group_service ) ? $group_service['group_service'] : '';
				?>
				<tr class="mp_remove_area">
					<td>
						<label class="mb">
							<input type="hidden" name="mpwpb_extra_hidden_name[]" value="<?php echo esc_attr( $unique_name ); ?>"/>
							<input type="text" name="mpwpb_extra_group_service[]" class="formControl mp_name_validation" value="<?php echo esc_attr( $services ); ?>" placeholder="<?php esc_attr_e( 'Extra service Group Name', 'mpwpb_plugin' ); ?>"/>
						</label
					</td>
					<td colspan="4"><?php $this->extra_service( $unique_name, $group_service ); ?></td>
					<td><?php MPWPB_Layout::move_remove_button(); ?></td>
				</tr>
				<?php
			}
			public function extra_service( $unique_name, $group_service = array() ) {
				$services = array_key_exists( 'group_service_info', $group_service ) ? $group_service['group_service_info'] : array();
				?>
				<div class="ovAuto mp_settings_area">
					<table>
						<thead>
						<tr>
							<th class="w_125"><?php _e( 'Image', 'mpwpb_plugin' ); ?></th>
							<th><?php _e( 'Extra service', 'mpwpb_plugin' ); ?><span class="textRequired">&nbsp;*</span></th>
							<th><?php _e( 'Extra Service Details', 'mpwpb_plugin' ); ?></th>
							<th class="w_100"><?php _e( 'Price', 'mpwpb_plugin' ); ?><span class="textRequired">&nbsp;*</span></th>
							<th class="w_100"><?php _e( 'Action', 'mpwpb_plugin' ); ?></th>
						</tr>
						</thead>
						<tbody class="mp_item_insert mp_sortable_area ">
						<?php
							if ( sizeof( $services ) > 0 ) {
								foreach ( $services as $service ) {
									$this->extra_service_item( $unique_name, $service );
								}
							}
						?>
						</tbody>
					</table>
					<?php MPWPB_Layout::add_new_button( esc_html__( 'Add New service', 'mpwpb_plugin' ) ); ?>
					<div class="mp_hidden_content">
						<table>
							<tbody class="mp_hidden_item">
							<?php $this->extra_service_item( $unique_name ); ?>
							</tbody>
						</table>
					</div>
				</div>
				<?php
			}
			public function extra_service_item( $unique_name, $service_info = array() ) {
				$image_name   = 'mpwpb_extra_service_img_' . $unique_name . '[]';
				$image        = array_key_exists( 'img', $service_info ) ? $service_info['img'] : '';
				$service_name = 'mpwpb_extra_service_name_' . $unique_name . '[]';
				$service      = array_key_exists( 'name', $service_info ) ? $service_info['name'] : '';
				$details_name = 'mpwpb_extra_service_details_' . $unique_name . '[]';
				$details      = array_key_exists( 'details', $service_info ) ? $service_info['details'] : '';
				$price_name   = 'mpwpb_extra_service_price_' . $unique_name . '[]';
				$price        = array_key_exists( 'price', $service_info ) ? $service_info['price'] : '';
				?>
				<tr class="mp_remove_area">
					<td><?php MPWPB_Layout::single_image_button( $image_name, $image ); ?></td>
					<td>
						<label>
							<input type="text" name="<?php echo esc_attr( $service_name ); ?>" class="formControl mp_name_validation" value="<?php echo esc_attr( $service ); ?>" placeholder="<?php _e( 'Extra Service Name', 'mpwpb_plugin' ); ?>"/>
						</label>
					</td>
					<td>
						<label>
							<textarea name="<?php echo esc_attr( $details_name ); ?>" class='formControl ' cols="3"><?php echo esc_attr( $details ); ?></textarea>
						</label>
					</td>
					<td>
						<label>
							<input type="text" name="<?php echo esc_attr( $price_name ); ?>" class="formControl" value="<?php echo esc_attr( $price ); ?>"/>
						</label>
					</td>
					<td><?php MPWPB_Layout::move_remove_button(); ?></td>
				</tr>
				<?php
			}
			/*******************/
			public function save_price_settings( $post_id ) {
				if ( get_post_type( $post_id ) == MPWPB_Function::get_cpt_name() ) {
					$mpwpb_service_type = MPWPB_Function::get_submit_info( 'mpwpb_service_type' );
					update_post_meta( $post_id, 'mpwpb_service_type', $mpwpb_service_type );
					/****************************/
					$category_infos   = array();
					$hidden_name      = MPWPB_Function::get_submit_info( 'mpwpb_hidden_name', array() );
					$categories       = MPWPB_Function::get_submit_info( 'mpwpb_category_name', array() );
					$categories_image = MPWPB_Function::get_submit_info( 'mpwpb_category_image', array() );
					if ( count( $categories ) > 0 ) {
						for ( $i = 0; $i < count( $categories ); $i ++ ) {
							$service_names = MPWPB_Function::get_submit_info( 'mpwpb_service_name_' . $hidden_name[ $i ], array() );
							$service_price = MPWPB_Function::get_submit_info( 'mpwpb_service_price_' . $hidden_name[ $i ], array() );
							$images        = MPWPB_Function::get_submit_info( 'mpwpb_service_img_' . $hidden_name[ $i ], array() );
							$details        = MPWPB_Function::get_submit_info( 'mpwpb_service_details_' . $hidden_name[ $i ], array() );
							if ( sizeof( $service_names ) > 0 && sizeof( $service_price ) > 0 && $categories[ $i ] ) {
								$category_infos[ $i ]['category'] = $categories[ $i ];
								$category_infos[ $i ]['img']      = $categories_image[ $i ];
								$service_infos                    = array();
								for ( $j = 0; $j < count( $service_names ); $j ++ ) {
									if ( $service_names[ $j ] && $service_price[ $j ] != '' ) {
										$service_infos[ $j ]['name']       = $service_names[ $j ];
										$service_infos[ $j ]['price']      = $service_price[ $j ];
										$service_infos[ $j ]['img']        = $images[ $j ];
										$service_infos[ $j ]['details_id'] = $details[ $j ];
									}
								}
								$category_infos[ $i ]['service'] = $service_infos;
							}
						}
					}
					update_post_meta( $post_id, 'mpwpb_category_infos', $category_infos );
					//**********************//
					$extra_service     = array();
					$extra_hidden_name = MPWPB_Function::get_submit_info( 'mpwpb_extra_hidden_name', array() );
					$group_service     = MPWPB_Function::get_submit_info( 'mpwpb_extra_group_service', array() );
					if ( count( $group_service ) > 0 ) {
						for ( $i = 0; $i < count( $group_service ); $i ++ ) {
							$ex_service = MPWPB_Function::get_submit_info( 'mpwpb_extra_service_name_' . $extra_hidden_name[ $i ], array() );
							$ex_price   = MPWPB_Function::get_submit_info( 'mpwpb_extra_service_price_' . $extra_hidden_name[ $i ], array() );
							$images     = MPWPB_Function::get_submit_info( 'mpwpb_extra_service_img_' . $extra_hidden_name[ $i ], array() );
							$details     = MPWPB_Function::get_submit_info( 'mpwpb_extra_service_details_' . $extra_hidden_name[ $i ], array() );
							if ( sizeof( $ex_service ) > 0 && sizeof( $ex_price ) > 0 && $group_service[ $i ] ) {
								$extra_service[ $i ]['group_service'] = $group_service[ $i ];
								$ex_service_info                 = array();
								for ( $j = 0; $j < count( $ex_service ); $j ++ ) {
									if ( $ex_service[ $j ] && $ex_price[ $j ] != '' ) {
										$ex_service_info[ $j ]['name']    = $ex_service[ $j ];
										$ex_service_info[ $j ]['price']   = $ex_price[ $j ];
										$ex_service_info[ $j ]['img']     = $images[ $j ];
										$ex_service_info[ $j ]['details'] = $details[ $j ];
									}
								}
								$extra_service[ $i ]['group_service_info'] = $ex_service_info;
							}
						}
					}
					update_post_meta( $post_id, 'mpwpb_extra_service', $extra_service );
				}
			}
		}
		new MPWPB_Price_Settings();
	}