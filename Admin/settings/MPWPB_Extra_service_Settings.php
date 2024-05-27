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
				add_action( 'mpwpb_settings_save', [ $this, 'save_ex_service_settings' ], 10, 1 );
			}
			public function extra_service_settings( $post_id ) {
				$extra_services                     = MP_Global_Function::get_post_info( $post_id, 'mpwpb_extra_service', array() );
				$extra_service_active               = MP_Global_Function::get_post_info( $post_id, 'mpwpb_extra_service_active', 'off' );
				$extra_service_active_class         = $extra_service_active == 'on' ? 'mActive' : '';
				$extra_service_active_checked       = $extra_service_active == 'on' ? 'checked' : '';
				$extra_service_group_active         = MP_Global_Function::get_post_info( $post_id, 'mpwpb_group_extra_service_active', 'off' );
				$extra_service_group_active_class   = $extra_service_group_active == 'on' ? 'mActive' : '';
				$extra_service_group_active_checked = $extra_service_group_active == 'on' ? 'checked' : '';
				$ex_count                           = 0;
				?>
				<div class="tabsItem mpwpb_extra_service_settings" data-tabs="#mpwpb_extra_service_settings">
					<header>
							<h2><?php esc_html_e('Extra Service Configuration', 'service-booking-manager'); ?></h2>
							<span><?php MPWPB_Settings::info_text('ex_service'); ?></span>
                    </header>
					<section class="section">
							<h2><?php esc_html_e('Extra Service Settings', 'service-booking-manager'); ?></h2>
							<span><?php MPWPB_Settings::info_text('ex_service_desc'); ?></span>
                    </section>

					<section>
						<label class="label">
							<div>
								<p><?php esc_html_e( 'Enable Extra Service', 'service-booking-manager' ); ?></p>
							</div>
							<div>
								<?php MP_Custom_Layout::switch_button( 'mpwpb_extra_service_active', $extra_service_active_checked ); ?>
							</div>
						</label>
                    </section>

					<section>
						<label class="label">
							<div>
								<p><?php esc_html_e( 'Enable Group Service', 'service-booking-manager' ); ?></p>
							</div>
							<div>
								<?php MP_Custom_Layout::switch_button( 'mpwpb_group_extra_service_active', $extra_service_group_active_checked ); ?>
							</div>
						</label>
                    </section>

					<section>
						<div class="mp_settings_area">
							<div class="_oAuto">
								<div class="mpwpb_category_area mpwpb_category_header">
									<div class="mpwpb_category_item <?php echo esc_attr( $extra_service_group_active_class ); ?>" data-collapse="#mpwpb_group_extra_service_active">
										<h6><?php esc_html_e( 'Group Service', 'service-booking-manager' ); ?><span class="textRequired">&nbsp;*</span></h6>
									</div>
									<div class="mpwpb_category_content">
										<div class="mpwpb_service_area">
											<div class="mpwpb_service_item"><h6><?php esc_html_e( 'Extra service', 'service-booking-manager' ); ?><span class="textRequired">&nbsp;*</span></h6></div>
											<div class="mpwpb_service_content"><h6><?php esc_html_e( 'Quantity', 'service-booking-manager' ); ?><span class="textRequired">&nbsp;*</span></h6></div>
											<div class="mpwpb_service_content"><h6><?php esc_html_e( 'Price', 'service-booking-manager' ); ?><span class="textRequired">&nbsp;*</span></h6></div>
											<div class="mpwpb_service_content"><h6><?php esc_html_e( 'image', 'service-booking-manager' ); ?></h6></div>
											<div class="mpwpb_service_item"><h6><?php esc_html_e( 'Details', 'service-booking-manager' ); ?></h6></div>
										</div>
									</div>
								</div>
								<div class="mp_item_insert mp_sortable_area">
									<?php
										if ( sizeof( $extra_services ) > 0 ) {
											foreach ( $extra_services as $group_service ) {
												$this->extra_service_group( $ex_count, $extra_service_group_active_class, $group_service );
												$ex_count ++;
											}
										} else {
											$this->extra_service_group( 0, $extra_service_group_active_class );
										}
									?>
								</div>
							</div>
							<div class="<?php echo esc_attr( $extra_service_group_active_class ); ?>" data-collapse="#mpwpb_group_extra_service_active">
								<?php MP_Custom_Layout::add_new_button( esc_html__( 'Add New Group service', 'service-booking-manager' ), 'mpwpb_add_group_service', '_successButton_xs_mT_xs my-2' ); ?>
								<div class="mp_hidden_content">
									<div class="mp_hidden_item">
										<?php $this->extra_service_group( 1, $extra_service_group_active_class ); ?>
									</div>
								</div>
							</div>
						</div>
					</section>
				</div>
				<?php
			}
			public function extra_service_group( $ex_count, $extra_service_group_active_class, $group_service = array() ) {
				$unique_name = uniqid();
				$services    = array_key_exists( 'group_service', $group_service ) ? $group_service['group_service'] : '';
				?>
				<div class="<?php echo esc_attr( $ex_count > 0 ? $extra_service_group_active_class : '' ); ?>" <?php if ( $ex_count > 0 ) { ?>  data-collapse="#mpwpb_group_extra_service_active" <?php } ?>>
					<div class="mpwpb_category_area mp_remove_area">
						<div class="mpwpb_category_item <?php echo esc_attr( $extra_service_group_active_class ); ?>" data-collapse="#mpwpb_group_extra_service_active">
							<div class="groupContent">
								<?php if ( $ex_count > 0 ) {
									MP_Custom_Layout::remove_button();
								} ?>
								<label class="fullWidth">
									<input type="hidden" name="mpwpb_extra_hidden_name[]" value="<?php echo esc_attr( $unique_name ); ?>"/>
									<input type="text" name="mpwpb_extra_group_service[]" class="formControl mp_name_validation" value="<?php echo esc_attr( $services ); ?>" placeholder="<?php esc_attr_e( 'service Group Name', 'service-booking-manager' ); ?>"/>
								</label>
							</div>
						</div>
						<div class="mpwpb_category_content">
							<?php $this->extra_service( $unique_name, $group_service ); ?>
						</div>
					</div>
				</div>
				<?php
			}
			public function extra_service( $unique_name, $group_service = array() ) {
				$services = array_key_exists( 'group_service_info', $group_service ) ? $group_service['group_service_info'] : array();
				?>
				<div class="mp_settings_area">
					<div class="mp_sortable_area mp_item_insert">
						<?php
							if ( sizeof( $services ) > 0 ) {
								foreach ( $services as $service ) {
									$this->extra_service_item( $unique_name, $service );
								}
							} else {
								$this->extra_service_item( $unique_name );
							}
						?>
					</div>

					<?php MP_Custom_Layout::add_new_button( esc_html__( 'Add New service', 'service-booking-manager' ), 'mpwpb_add_group_service', '_themeButton_xs _mT' ); ?>

					<div class="mp_hidden_content">
						<div class="mp_hidden_item">
							<?php $this->extra_service_item( $unique_name ); ?>
						</div>
					</div>
				</div>
				<?php
			}
			public function extra_service_item( $unique_name, $service_info = array() ) {
				$image_name   = 'mpwpb_extra_service_img_' . $unique_name . '[]';
				$image        = array_key_exists( 'image', $service_info ) ? $service_info['image'] : '';
				$icon         = array_key_exists( 'icon', $service_info ) ? $service_info['icon'] : '';
				$service_name = 'mpwpb_extra_service_name_' . $unique_name . '[]';
				$service      = array_key_exists( 'name', $service_info ) ? $service_info['name'] : '';
				$details_name = 'mpwpb_extra_service_details_' . $unique_name . '[]';
				$details      = array_key_exists( 'details', $service_info ) ? $service_info['details'] : '';
				$price_name   = 'mpwpb_extra_service_price_' . $unique_name . '[]';
				$price        = array_key_exists( 'price', $service_info ) ? $service_info['price'] : '';
				$qty_name     = 'mpwpb_extra_service_qty_' . $unique_name . '[]';
				$qty          = array_key_exists( 'qty', $service_info ) ? $service_info['price'] : '';
				?>
				<div class="mpwpb_service_area mp_remove_area">
					<div class="mpwpb_service_item">
						<div class="groupContent">
							<?php MP_Custom_Layout::remove_button(); ?>
							<?php MP_Custom_Layout::move_button(); ?>
							<label class="fullWidth">
								<input type="text" name="<?php echo esc_attr( $service_name ); ?>" class="formControl mp_name_validation" value="<?php echo esc_attr( $service ); ?>" placeholder="<?php _e( 'Extra Service Name', 'service-booking-manager' ); ?>"/>
							</label>
						</div>
					</div>
					<div class="mpwpb_service_content">
						<label class="fullWidth">
							<input type="text" name="<?php echo esc_attr( $qty_name ); ?>" class="formControl mp_number_validation" value="<?php echo esc_attr( $qty ); ?>"/>
						</label>
					</div>
					<div class="mpwpb_service_content">
						<label class="fullWidth">
							<input type="text" name="<?php echo esc_attr( $price_name ); ?>" class="formControl mp_price_validation" value="<?php echo esc_attr( $price ); ?>"/>
						</label>
					</div>
					<div class="mpwpb_service_content"><?php do_action( 'mp_add_icon_image', $image_name, $icon, $image ); ?></div>
					<div class="mpwpb_service_item">
						<label>
							<textarea name="<?php echo esc_attr( $details_name ); ?>" class='formControl ' cols="3"><?php echo esc_attr( $details ); ?></textarea>
						</label>
					</div>
				</div>
				<?php
			}
			public function save_ex_service_settings( $post_id ) {
				if ( get_post_type( $post_id ) == MPWPB_Function::get_cpt() ) {
					
					$active_extra_service = MP_Global_Function::get_submit_info( 'mpwpb_extra_service_active' ) ? 'on' : 'off';
					update_post_meta( $post_id, 'mpwpb_extra_service_active', $active_extra_service );
					$active_group_extra_service = MP_Global_Function::get_submit_info( 'mpwpb_group_extra_service_active' ) ? 'on' : 'off';
					update_post_meta( $post_id, 'mpwpb_group_extra_service_active', $active_group_extra_service );
					$extra_service       = array();
					$extra_hidden_name   = MP_Global_Function::get_submit_info( 'mpwpb_extra_hidden_name', array() );
					$group_service       = MP_Global_Function::get_submit_info( 'mpwpb_extra_group_service', array() );
					$count_group_service = $active_group_extra_service == 'on' ? count( $extra_hidden_name ) : 1;
					if ( $count_group_service > 0 ) {
						for ( $i = 0; $i < $count_group_service; $i ++ ) {
							$ex_service      = MP_Global_Function::get_submit_info( 'mpwpb_extra_service_name_' . $extra_hidden_name[ $i ], array() );
							$ex_qty          = MP_Global_Function::get_submit_info( 'mpwpb_extra_service_qty_' . $extra_hidden_name[ $i ], array() );
							$ex_price        = MP_Global_Function::get_submit_info( 'mpwpb_extra_service_price_' . $extra_hidden_name[ $i ], array() );
							$images          = MP_Global_Function::get_submit_info( 'mpwpb_extra_service_img_' . $extra_hidden_name[ $i ], array() );
							$details         = MP_Global_Function::get_submit_info( 'mpwpb_extra_service_details_' . $extra_hidden_name[ $i ], array() );
							$ex_service_info = array();
							if ( sizeof( $ex_service ) > 0 && sizeof( $ex_price ) > 0 ) {
								for ( $j = 0; $j < count( $ex_service ); $j ++ ) {
									if ( $ex_service[ $j ] && $ex_price[ $j ] != '' ) {
										$ex_service_info[ $j ]['name']    = $ex_service[ $j ];
										$ex_service_info[ $j ]['qty']     = $ex_qty[ $j ];
										$ex_service_info[ $j ]['price']   = $ex_price[ $j ];
										$ex_service_info[ $j ]['details'] = $details[ $j ];
										$current_image_icon               = $images[ $j ];
										$ex_service_info[ $j ]['icon']    = '';
										$ex_service_info[ $j ]['image']   = '';
										if ( $current_image_icon ) {
											if ( preg_match( '/\s/', $current_image_icon ) ) {
												$ex_service_info[ $j ]['icon'] = $current_image_icon;
											} else {
												$ex_service_info[ $j ]['image'] = $current_image_icon;
											}
										}
									}
								}
								if ( sizeof( $ex_service_info ) > 0 ) {
									$extra_service[ $i ]['group_service']      = $active_group_extra_service == 'on' ? $group_service[ $i ] : '';
									$extra_service[ $i ]['group_service_info'] = $ex_service_info;
								}
							}
						}
					}
					update_post_meta( $post_id, 'mpwpb_extra_service', $extra_service );
				}
			}
		}
		new MPWPB_Extra_service_Settings();
	}