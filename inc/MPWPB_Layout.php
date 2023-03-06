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
			/*****************************/
			public static function add_new_button( $button_text, $class = 'mp_add_item', $button_class = '_themeButton_xs_mT_xs', $icon_class = 'fas fa-plus-square' ) {
				?>
				<button class="<?php echo esc_attr( $button_class . ' ' . $class ); ?>" type="button">
					<span class="<?php echo esc_attr( $icon_class ); ?>"></span>
					<span class="mL_xs"><?php echo MPWPB_Function::esc_html( $button_text ); ?></span>
				</button>
				<?php
			}
			public static function move_remove_button() {
				?>
				<div class="allCenter">
					<div class="buttonGroup max_100">
						<?php
							self::remove_button();
							self::move_button();
						?>
					</div>
				</div>
				<?php
			}
			public static function remove_button() {
				?>
				<button class="_warningButton_xs mp_item_remove" type="button"><span class="fas fa-trash-alt mp_zero"></span></button>
				<?php
			}
			public static function move_button() {
				?>
				<div class="_mpBtn_navy_blueButton_xs mp_sortable_button" type=""><span class="fas fa-expand-arrows-alt mp_zero"></span></div>
				<?php
			}
			/*****************************/
			public static function qty_input( $input_name, $price, $available_seat = 1, $default_qty = 0, $min_qty = 0, $max_qty = '' ) {
				$min_qty = max( $default_qty, $min_qty );
				if ( $available_seat > $min_qty ) {
					?>
					<div class="groupContent qtyIncDec">
						<div class="decQty addonGroupContent"><span class="fas fa-minus"></span></div>
						<label>
							<input type="text"
								 class="formControl inputIncDec"
								 data-price="<?php echo esc_attr( $price ); ?>"
								 name="<?php echo esc_attr( $input_name ); ?>"
								 value="<?php echo esc_attr( max( 0, $default_qty ) ); ?>"
								 min="<?php echo esc_attr( $min_qty ); ?>"
								 max="<?php echo esc_attr( $max_qty > 0 ? $max_qty : $available_seat ); ?>"
							/>
						</label>
						<div class="incQty addonGroupContent"><span class="fas fa-plus"></span></div>
					</div>
					<?php
				}
			}
		}
		new MPWPB_Layout();
	}