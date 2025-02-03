<?php
	if (!defined('ABSPATH')) {
		die;
	} // Cannot access pages directly.
	/**
	 * Class MPWPB_Wc_Checkout_Fields
	 *
	 * @since 1.0
	 *
	 * */
	if (!class_exists('MPWPB_Wc_Checkout_Fields')) {
		class MPWPB_Wc_Checkout_Fields {
			private $error;
			private $settings_options;
			public function __construct() {
				$this->error = new WP_Error();
				add_action('init', array($this, 'get_settings_options'));
				add_action('add_mpwpb_admin_script', array($this, 'admin_enqueue'));
				add_action('add_mpwpb_frontend_script', array($this, 'frontend_enqueue'), 99);
				add_action('admin_menu', array($this, 'checkout_menu'));
				add_action('admin_notices', array($this, 'mp_admin_notice'));
				add_action('add_switch_button', array($this, 'switch_button'), 10, 3);
				add_action('wp_ajax_mpwpb_disable_field', array($this, 'mpwpb_disable_field'));
				add_action('wp_ajax_nopriv_mpwpb_disable_field', [$this, 'mpwpb_disable_field']);
			}
			public function mpwpb_disable_field() {
				if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'mpwpb_admin_nonce')) {
					wp_send_json_error('Invalid nonce!'); // Prevent unauthorized access
				}
				$response = 'failed';
				$key = isset($_POST['key']) ? sanitize_text_field(wp_unslash($_POST['key'])) : null;
				$name = isset($_POST['name']) ? sanitize_text_field(wp_unslash($_POST['name'])) : null;
				$isChecked = isset($_POST['isChecked']) ? sanitize_text_field(wp_unslash($_POST['isChecked'])) : null;
				$checkout_fields = MPWPB_Wc_Checkout_Fields_Helper::get_checkout_fields_for_list();
				$custom_checkout_fields = get_option('mpwpb_custom_checkout_fields');
				if (isset($checkout_fields[$key][$name])) {
					unset($custom_checkout_fields[$key][$name]);
					if ($isChecked == 'true') {
						$custom_checkout_fields[$key][$name] = $checkout_fields[$key][$name];
						$custom_checkout_fields[$key][$name]['disabled'] = '';
					} elseif ($isChecked == 'false') {
						$custom_checkout_fields[$key][$name] = $checkout_fields[$key][$name];
						$custom_checkout_fields[$key][$name]['disabled'] = '1';
					}
					update_option('mpwpb_custom_checkout_fields', $custom_checkout_fields);
					$response = 'success';
				}
				echo esc_html($response);
				die();
			}
			public static function switch_button($id, $class, $name, $status, $data) {
				$str_data = '';
				if (is_array($data) && count($data)) {
					foreach ($data as $key => $name) {
						$str_data .= 'data-' . $key . '="' . $name . '"';
					}
				}
				?>
                <label class="switch">
                    <input type="checkbox" id="<?php echo esc_attr($id); ?>" class="<?php echo esc_attr($class); ?>" name="<?php echo esc_attr($name); ?>" <?php echo esc_attr($status); ?>  <?php echo esc_attr($str_data); ?> >
                    <span class="slider"></span>
                </label>
				<?php
			}
			public function get_settings_options() {
				$this->settings_options = get_option('mpwpb_custom_checkout_fields');
			}
			public function admin_enqueue() {
				wp_enqueue_style('mpwpb_checkout', MPWPB_PLUGIN_URL . '/assets/checkout/css/mpwpb-pro-checkout.css', array(), time());
				wp_enqueue_script('mpwpb_checkout', MPWPB_PLUGIN_URL . '/assets/checkout/js/mpwpb-pro-checkout.js', array('jquery'), time(), true);
			}
			public function frontend_enqueue() {
				wp_enqueue_style('mpwpb_checkout_front_style', MPWPB_PLUGIN_URL . '/assets/checkout/front/css/mpwpb-pro-checkout-front-style.css', array(), time());
				wp_enqueue_script('mpwpb_checkout_front_script', MPWPB_PLUGIN_URL . '/assets/checkout/front/js/mpwpb-pro-checkout-front-script.js', array('jquery'), time(), true);
			}
			public function checkout_menu() {
				$cpt = MPWPB_Function::get_cpt();
				add_submenu_page('edit.php?post_type=' . $cpt, esc_html__('Checkout Fields', 'service-booking-manager'), esc_html__('Checkout Fields', 'service-booking-manager'), 'manage_options', 'mpwpb_wc_checkout_fields', array($this, 'wc_checkout_fields'));
			}
			public function wc_checkout_fields() {
				if (!current_user_can('administrator')) {
					wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'service-booking-manager'));
				}
				do_action('mpwpb_save_checkout_fields_settings');
				do_action('mpwpb_wc_checkout_fields');
				self::checkout_field_list();
			}
			public function checkout_field_list() {
				?>
                <div class="mpStyles">
                    <div class="checkout">
                        <div class="modal-container">
                            <div class="modal" id="field-modal">
                                <div class="modal-content">
                                    <span class="close">&times;</span>
                                    <div class="custom-form-container">
                                        <div class="custom-form">
                                            <h2>Checkout Field</h2>
                                            <form method="post">
                                                <input type="hidden" name="action" required>
                                                <input type="hidden" name="key" required>
                                                <input type="hidden" name="old_name">
                                                <input type="hidden" name="new_name">
                                                <input type="hidden" name="new_type">
                                                <label for="type">Type:</label>
                                                <select name="type" id="type" required>
                                                    <option value="" disabled>Select an option</option>
                                                    <option value="text">Text</option>
                                                    <option value="select">Select</option>
                                                    <option value="file">Image</option>
                                                </select>
                                                <label for="name">Name:</label>
                                                <input type="text" name="name" id="name" required>
                                                <label for="label">Label:</label>
                                                <input type="text" name="label" id="label" required>
                                                <label for="priority">Position:( >= 0 )</label>
                                                <input type="text" pattern="[0-9]+" name="priority" id="priority">
                                                <label for="name">Class:</label>
                                                <input type="text" name="class" id="class">
                                                <label for="name">Validation:</label>
                                                <input type="text" name="validate" id="validate">
                                                <div class="custom-var-attr-section">
                                                    <label for="placeholder">Placeholder:</label>
                                                    <input type="text" name="placeholder" id="placeholder">
                                                </div>
                                                <label><input type="checkbox" name="required"> Required</label>
                                                <label><input type="checkbox" name="disabled"> Disabled</label>
                                                <p class="add-nonce"><?php wp_nonce_field('mpwpb_checkout_field_add', 'mpwpb_checkout_field_add_nonce'); ?></p>
                                                <p class="edit-nonce"><?php wp_nonce_field('mpwpb_checkout_field_edit', 'mpwpb_checkout_field_edit_nonce'); ?></p>
                                                <button type="submit">Submit</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mpStyles">
                    <div class="checkout">
                        <div class="tab-container">
                            <ul class="tab-menu">
                                <h3>CHECKOUT FIELDS</h3>
                                <!-- <div class="hl"></div> -->
								<?php do_action('mpwpb_wc_checkout_tab'); ?>
                            </ul>
                            <div class="tab-content-container">
								<?php do_action('mpwpb_wc_checkout_tab_content', MPWPB_Wc_Checkout_Fields_Helper::get_checkout_fields_for_list()); ?>
                            </div>
                        </div>
                    </div>
                </div>
				<?php
			}
			public function mp_admin_notice() {
				self::mp_error_notice($this->error);
			}
			public static function mp_error_notice($error) {
				if ($error->has_errors()) {
					foreach ($error->get_error_messages() as $error) {
						$class = 'notice notice-error';
						printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class), wp_kses_post($error));
					}
				}
			}
		}
		new MPWPB_Wc_Checkout_Fields();
	}