<?php
/*
* @Author 		engr.sumonazma@gmail.com
* Copyright: 	mage-people.com
*/
	if (!defined('ABSPATH')) {
		die;
	} // Cannot access pages directly.
	if (!class_exists('MPWPB_Global_File_Load')) {
		class MPWPB_Global_File_Load {
			public function __construct() {
				$this->define_constants();
				$this->load_global_file();
				// WooCommerce-optional safety net. The Pro add-ons call several
				// WooCommerce functions (wc_price(), get_woocommerce_currency_symbol(),
				// etc.) directly in dozens of places (Order List / Service Queue /
				// Calendar / mails). When WooCommerce is inactive (e.g. Custom Payment
				// mode) those are undefined and fatal the whole admin screen. Provide
				// native-currency fallbacks so those keep working. Hooked on
				// plugins_loaded at a late priority so that when WooCommerce IS active
				// its own functions are already defined by then and these guards never
				// redeclare them.
				add_action('plugins_loaded', array($this, 'define_wc_fallbacks'), 100);
				add_action('admin_enqueue_scripts', array($this, 'admin_enqueue'), 80);
				add_action('wp_enqueue_scripts', array($this, 'frontend_enqueue'), 80);
				add_action('admin_head', array($this, 'add_admin_head'), 5);
				add_action('wp_head', array($this, 'add_frontend_head'), 5);
			}
			/**
			 * Declares native fallbacks for the read-only WooCommerce helpers the
			 * add-ons call directly, but ONLY for the ones WooCommerce hasn't
			 * already defined (i.e. it's inactive). Each mirrors WooCommerce's own
			 * signature and returns a safe native-currency value / empty result, so
			 * admin screens (Order List / Service Queue / Calendar / mails) render
			 * instead of fataling in Custom Payment mode. Behaviour functions
			 * (wc_create_order, checkout actions, etc.) are deliberately NOT stubbed
			 * -- those only run in real WooCommerce flows.
			 */
			public function define_wc_fallbacks() {
				if (!function_exists('wc_price')) {
					function wc_price($price, $args = array()) {
						return MPWPB_Global_Function::native_price_html($price);
					}
				}
				if (!function_exists('get_woocommerce_currency_symbol')) {
					function get_woocommerce_currency_symbol($currency = '') {
						return MPWPB_Global_Function::native_currency_setting('symbol', '$');
					}
				}
				if (!function_exists('get_woocommerce_currency')) {
					function get_woocommerce_currency() {
						return MPWPB_Global_Function::native_currency_setting('currency_code', 'USD');
					}
				}
				if (!function_exists('wc_get_price_decimal_separator')) {
					function wc_get_price_decimal_separator() {
						return MPWPB_Global_Function::native_currency_setting('decimal_separator', '.');
					}
				}
				if (!function_exists('wc_get_price_thousand_separator')) {
					function wc_get_price_thousand_separator() {
						return MPWPB_Global_Function::native_currency_setting('thousand_separator', ',');
					}
				}
				if (!function_exists('wc_get_price_decimals')) {
					function wc_get_price_decimals() {
						return (int) MPWPB_Global_Function::native_currency_setting('decimals', 2);
					}
				}
				// No WooCommerce orders exist without WooCommerce (native bookings are
				// the mpwpb_order CPT, not WC orders), so this returns false exactly
				// like wc_get_order() does for an unknown id -- every caller already
				// guards on that (e.g. `if (!$order) return;`).
				if (!function_exists('wc_get_order')) {
					function wc_get_order($the_order = false) {
						return false;
					}
				}
				if (!function_exists('wc_get_orders')) {
					function wc_get_orders($args = array()) {
						return array();
					}
				}
				if (!function_exists('wc_get_order_item_meta')) {
					function wc_get_order_item_meta($item_id, $key, $single = true) {
						return $single ? '' : array();
					}
				}
				if (!function_exists('wc_get_order_status_name')) {
					function wc_get_order_status_name($status) {
						$status = (string) $status;
						return ucwords(str_replace(array('wc-', '-', '_'), array('', ' ', ' '), $status));
					}
				}
			}
			public function define_constants() {
				if (!defined('MPWPB_GLOBAL_PLUGIN_DIR')) {
					define('MPWPB_GLOBAL_PLUGIN_DIR', dirname(__FILE__));
				}
				if (!defined('MPWPB_GLOBAL_PLUGIN_URL')) {
					define('MPWPB_GLOBAL_PLUGIN_URL', plugins_url() . '/' . plugin_basename(dirname(__FILE__)));
				}
			}
			public function load_global_file() {
				require_once MPWPB_GLOBAL_PLUGIN_DIR . '/class/MPWPB_Global_Function.php';
				require_once MPWPB_GLOBAL_PLUGIN_DIR . '/class/MPWPB_Booking_History.php';
				require_once MPWPB_GLOBAL_PLUGIN_DIR . '/class/MPWPB_Cancellation.php';
				require_once MPWPB_GLOBAL_PLUGIN_DIR . '/class/MPWPB_Booking_Notes.php';
				require_once MPWPB_GLOBAL_PLUGIN_DIR . '/class/MPWPB_Partial_Payment.php';
				require_once MPWPB_GLOBAL_PLUGIN_DIR . '/class/MPWPB_Tax_Helper.php';
				require_once MPWPB_GLOBAL_PLUGIN_DIR . '/class/MPWPB_Happy_Hours_Helper.php';
				require_once MPWPB_GLOBAL_PLUGIN_DIR . '/class/MPWPB_Global_Style.php';
				require_once MPWPB_GLOBAL_PLUGIN_DIR . '/class/MPWPB_Custom_Layout.php';
				require_once MPWPB_GLOBAL_PLUGIN_DIR . '/class/MPWPB_Custom_Slider.php';
				require_once MPWPB_GLOBAL_PLUGIN_DIR . '/class/MPWPB_Select_Icon_image.php';
				require_once MPWPB_GLOBAL_PLUGIN_DIR . '/class/MPWPB_Setting_API.php';
			}
			public function global_enqueue() {
				wp_enqueue_script('jquery');
				wp_enqueue_script('jquery-ui-core');
				wp_enqueue_script('jquery-ui-datepicker');
				// Removed duplicate mpwpb_ajax_url localization to prevent conflicts
				// wp_localize_script('mpwpb_ajax_url', 'mpwpb_ajax_url', array('url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('mpwpb-ajax-nonce')));
				wp_enqueue_style('mp_jquery_ui', MPWPB_GLOBAL_PLUGIN_URL . '/assets/jquery-ui.min.css', array(), '1.13.2');
				wp_enqueue_style('mp_font_awesome', MPWPB_GLOBAL_PLUGIN_URL . '/assets/admin/all.min.css', array(), '5.15.3');
				wp_enqueue_style('mp_select_2', MPWPB_GLOBAL_PLUGIN_URL . '/assets/select_2/select2.min.css', array(), '4.0.13');
				wp_enqueue_script('mp_select_2', MPWPB_GLOBAL_PLUGIN_URL . '/assets/select_2/select2.min.js', array(), '4.0.13',true);
				wp_enqueue_style('mp_owl_carousel', MPWPB_GLOBAL_PLUGIN_URL . '/assets/owl_carousel/owl.carousel.min.css', array(), '2.3.4');
				wp_enqueue_script('mp_owl_carousel', MPWPB_GLOBAL_PLUGIN_URL . '/assets/owl_carousel/owl.carousel.min.js', array(), '2.3.4',true);
				wp_enqueue_style('mpwpb_plugin_global', MPWPB_GLOBAL_PLUGIN_URL . '/assets/mp_style/mpwpb_plugin_global.css', array(), MPWPB_VERSION);
				wp_enqueue_script('mpwpb_plugin_global', MPWPB_GLOBAL_PLUGIN_URL . '/assets/mp_style/mpwpb_plugin_global.js', array('jquery'), MPWPB_VERSION);
				do_action('add_mpwpb_global_enqueue');
			}
			public function admin_enqueue() {
				$this->global_enqueue();
				wp_enqueue_editor();
				wp_enqueue_media();
				//admin script
				wp_enqueue_script('jquery-ui-sortable');
				wp_enqueue_style('wp-color-picker');
				wp_enqueue_script('wp-color-picker');
				wp_enqueue_style('wp-codemirror');
				wp_enqueue_script('wp-codemirror');;
				//loading Time picker
				wp_enqueue_script('jquery.timepicker.min', MPWPB_GLOBAL_PLUGIN_URL . '/assets/admin/jquery.timepicker.min.js', array('jquery'), MPWPB_VERSION, true);
				wp_enqueue_style('jquery.timepicker.min', MPWPB_GLOBAL_PLUGIN_URL . '/assets/admin/jquery.timepicker.min.css', array(), MPWPB_VERSION);
				//=====================//
				wp_enqueue_script('form-field-dependency', MPWPB_GLOBAL_PLUGIN_URL . '/assets/admin/form-field-dependency.js', array('jquery'), '1.0', true);
				// admin setting global
				wp_enqueue_script('mpwpb_admin_settings', MPWPB_GLOBAL_PLUGIN_URL . '/assets/admin/mpwpb_admin_settings.js', array('jquery'), MPWPB_VERSION, true);
				wp_enqueue_style('mpwpb_admin_settings', MPWPB_GLOBAL_PLUGIN_URL . '/assets/admin/mpwpb_admin_settings.css', array(), MPWPB_VERSION);
				do_action('add_mpwpb_admin_enqueue');
			}
			public function frontend_enqueue() {
				if (!MPWPB_Global_Function::is_booking_frontend_context()) {
					return;
				}
				$this->global_enqueue();
				do_action('add_mpwpb_frontend_enqueue');
			}
			public function add_admin_head() {
				$this->js_constant();
			}
			public function add_frontend_head() {
				if (!MPWPB_Global_Function::is_booking_frontend_context()) {
					return;
				}
				$this->js_constant();
				$this->custom_css();
			}
			public function js_constant() {
				?>
				<script type="text/javascript">
					// Declare variables only if they don't already exist to prevent duplicate declaration errors
					if (typeof mpwpb_currency_symbol === 'undefined') {
						var mpwpb_currency_symbol = "";
					}
					if (typeof mpwpb_currency_position === 'undefined') {
						var mpwpb_currency_position = "";
					}
					if (typeof mpwpb_currency_decimal === 'undefined') {
						var mpwpb_currency_decimal = "";
					}
					if (typeof mpwpb_currency_thousands_separator === 'undefined') {
						var mpwpb_currency_thousands_separator = "";
					}
					if (typeof mpwpb_num_of_decimal === 'undefined') {
						var mpwpb_num_of_decimal = "";
					}
					if (typeof mpwpb_price_suffix === 'undefined') {
						var mpwpb_price_suffix="<?php echo get_option('woocommerce_price_display_suffix'); ?>";
					}
					if (typeof mpwpb_ajax_url === 'undefined') {
						var mpwpb_ajax_url = "<?php echo esc_js(admin_url('admin-ajax.php')); ?>";
					}
					if (typeof mpwpb_empty_image_url === 'undefined') {
						var mpwpb_empty_image_url = "<?php echo esc_js(MPWPB_GLOBAL_PLUGIN_URL . '/assets/images/no_image.png'); ?>";
					}
					if (typeof mpwpb_date_format === 'undefined') {
						var mpwpb_date_format = "<?php echo esc_js(MPWPB_Global_Function::get_settings('mpwpb_global_settings', 'date_format', 'D d M , yy')); ?>";
					}
					if (typeof mpwpb_date_format_without_year === 'undefined') {
						var mpwpb_date_format_without_year = "<?php echo esc_js(MPWPB_Global_Function::get_settings('mpwpb_global_settings', 'date_format_without_year', 'D d M')); ?>";
					}
				</script>
				<?php
				if (MPWPB_Global_Function::is_wc_payment_mode()) {
					?>
					<script type="text/javascript">
						// Set WooCommerce-specific values
						mpwpb_currency_symbol = "<?php echo esc_js(get_woocommerce_currency_symbol()); ?>";
						mpwpb_currency_position = "<?php echo esc_js(get_option('woocommerce_currency_pos')); ?>";
						mpwpb_currency_decimal = "<?php echo esc_js(wc_get_price_decimal_separator()); ?>";
						mpwpb_currency_thousands_separator = "<?php echo esc_js(wc_get_price_thousand_separator()); ?>";
						mpwpb_num_of_decimal = "<?php echo esc_js(get_option('woocommerce_price_num_decimals', 2)); ?>";
					</script>
					<?php
				} else {
					?>
					<script type="text/javascript">
						// Set native (non-WooCommerce) currency values
						mpwpb_currency_symbol = "<?php echo esc_js(MPWPB_Global_Function::native_currency_setting('symbol', '$')); ?>";
						mpwpb_currency_position = "<?php echo esc_js(MPWPB_Global_Function::native_currency_setting('position', 'left')); ?>";
						mpwpb_currency_decimal = "<?php echo esc_js(MPWPB_Global_Function::native_currency_setting('decimal_separator', '.')); ?>";
						mpwpb_currency_thousands_separator = "<?php echo esc_js(MPWPB_Global_Function::native_currency_setting('thousand_separator', ',')); ?>";
						mpwpb_num_of_decimal = "<?php echo esc_js(MPWPB_Global_Function::native_currency_setting('decimals', 2)); ?>";
					</script>
					<?php
				}
			}
			public function custom_css() {
				$custom_css = MPWPB_Global_Function::get_settings('mpwpb_custom_css', 'custom_css');
				ob_start();
				?>
				<style>
					<?php echo wp_kses_post($custom_css); ?>
				</style>
				<?php
				echo wp_kses_post(ob_get_clean());
			}
		}
		new MPWPB_Global_File_Load();
	}
?>
