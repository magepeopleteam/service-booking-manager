<?php
	if ( ! defined( 'ABSPATH' ) ) {
		die;
	} // Cannot access pages directly.
	if ( ! class_exists( 'MPWPB_Layout' ) ) {
		class MPWPB_Layout {
			public function __construct() {
				add_action( 'mp_hidden_item_table', array( $this, 'hidden_item_table' ), 10, 2 );
			}
			/****************************/
			public function hidden_item_table( $hook_name, $data = array() ) {
				?>
				<div class="mp_hidden_content">
					<table>
						<tbody class="mp_hidden_item">
						<?php do_action( $hook_name, $data ); ?>
						</tbody>
					</table>
				</div>
				<?php
			}
			/*****************************/
			public static function switch_button( $name, $checked = '' ) {
				?>
				<label class="roundSwitchLabel">
					<input type="checkbox" name="<?php echo esc_attr( $name ); ?>" <?php echo esc_attr( $checked ); ?>>
					<span class="roundSwitch" data-collapse-target="#<?php echo esc_attr( $name ); ?>"></span>
				</label>
				<?php
			}
			public static function popup_button( $target_popup_id, $text ) {
				?>
				<button type="button" class="_dButton_bgBlue" data-target-popup="<?php echo esc_attr( $target_popup_id ); ?>">
					<span class="fas fa-plus-square"></span>
					<?php echo esc_html( $text ); ?>
				</button>
				<?php
			}
			public static function popup_button_xs( $target_popup_id, $text ) {
				?>
				<button type="button" class="_dButton_xs_bgBlue" data-target-popup="<?php echo esc_attr( $target_popup_id ); ?>">
					<span class="fas fa-plus-square"></span>
					<?php echo esc_html( $text ); ?>
				</button>
				<?php
			}
			public static function single_image_button( $name, $image_id = '' ) {
				?>
				<div class="mp_add_single_image">
					<input type="hidden" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr($image_id); ?>"/>
					<?php if ( $image_id ) { ?>
						<div class="mp_single_image_item" data-image-id="<?php echo esc_attr($image_id); ?>'">
							<span class="fas fa-times circleIcon_xs mp_remove_single_image"></span>
							<img src="<?php echo wp_get_attachment_image_url( $image_id, 'medium' ) ?>" alt="<?php echo esc_attr($image_id); ?>"/>
						</div>
					<?php }  ?>
					<button type="button" class="mpBtn_xs <?php echo esc_attr($image_id ? 'dNone' : ''); ?>">
						<span class="fas fa-images mR_xs"></span><?php esc_html_e( 'Image', 'mpwpb_plugin' ); ?>
					</button>
				</div>
				<?php
			}
			public static function add_multi_image( $name, $images ) {
				$images = is_array( $images ) ? MPWPB_Function::array_to_string( $images ) : $images;
				?>
				<div class="mp_multi_image_area">
					<input type="hidden" class="mp_multi_image_value" name="<?php echo esc_attr( $name ); ?>" value="<?php esc_attr_e( $images ); ?>"/>
					<div class="mp_multi_image">
						<?php
							$all_images = explode( ',', $images );
							if ( $images && sizeof( $all_images ) > 0 ) {
								foreach ( $all_images as $image ) {
									?>
									<div class="mp_multi_image_item" data-image-id="<?php esc_attr_e( $image ); ?>">
										<span class="fas fa-times circleIcon_xs mp_remove_multi_image"></span>
										<img src="<?php echo MPWPB_Function::get_image_url( '', $image, 'medium' ); ?>" alt="<?php esc_attr_e( $image ); ?>"/>
									</div>
									<?php
								}
							}
						?>
					</div>
					<button type="button" class="mpBtn_xs add_multi_image">
						<span class="fas fa-images"></span><?php esc_html_e( 'Image', 'mpwpb_plugin' ); ?>
					</button>
					<?php //MPWPB_Layout::add_new_button( esc_html__( 'Add Image', 'mpwpb_plugin' ), 'add_multi_image', '_dButton_bgColor_1' ); ?>
				</div>
				<?php
			}
			/*****************************/
			public static function add_new_button( $button_text, $class = 'mp_add_item', $button_class = '_themeButton_xs_mT_xs', $icon_class = 'fas fa-plus-square' ) {
				?>
				<button class="<?php echo esc_attr( $button_class . ' ' . $class ); ?>" type="button">
					<span class="<?php echo esc_attr( $icon_class ); ?>"></span>
					<span class="ml_xs"><?php echo esc_html( $button_text ); ?></span>
				</button>
				<?php
			}
			public static function move_remove_button() {
				?>
				<div class="allCenter">
					<div class="buttonGroup max_100">
						<button class="_warningButton_xs mp_item_remove" type="button"><span class="fas fa-trash-alt mp_zero"></span></button>
						<div class="_mpBtn_themeButton_xs mp_sortable_button" type=""><span class="fas fa-expand-arrows-alt mp_zero"></span></div>
					</div>
				</div>
				<?php
			}
		}
		new MPWPB_Layout();
	}