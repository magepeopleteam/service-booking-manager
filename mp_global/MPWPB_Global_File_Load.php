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
				add_action('admin_enqueue_scripts', array($this, 'admin_enqueue'), 80);
				add_action('wp_enqueue_scripts', array($this, 'frontend_enqueue'), 80);
				add_action('admin_head', array($this, 'add_admin_head'), 5);
				add_action('wp_head', array($this, 'add_frontend_head'), 5);
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
				wp_localize_script('mpwpb_ajax_url', 'mpwpb_ajax_url', array('url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('mpwpb-ajax-nonce')));
				wp_enqueue_style('mp_jquery_ui', MPWPB_GLOBAL_PLUGIN_URL . '/assets/jquery-ui.min.css', array(), '1.13.2');
				wp_enqueue_style('mp_font_awesome', MPWPB_GLOBAL_PLUGIN_URL . '/assets/admin/all.min.css', array(), '5.15.3');
				wp_enqueue_style('mp_select_2', MPWPB_GLOBAL_PLUGIN_URL . '/assets/select_2/select2.min.css', array(), '4.0.13');
				wp_enqueue_script('mp_select_2', MPWPB_GLOBAL_PLUGIN_URL . '/assets/select_2/select2.min.js', array(), '4.0.13',true);
				wp_enqueue_style('mp_owl_carousel', MPWPB_GLOBAL_PLUGIN_URL . '/assets/owl_carousel/owl.carousel.min.css', array(), '2.3.4');
				wp_enqueue_script('mp_owl_carousel', MPWPB_GLOBAL_PLUGIN_URL . '/assets/owl_carousel/owl.carousel.min.js', array(), '2.3.4',true);
				wp_enqueue_style('mpwpb_plugin_global', MPWPB_GLOBAL_PLUGIN_URL . '/assets/mp_style/mpwpb_plugin_global.css', array(), time());
				wp_enqueue_script('mpwpb_plugin_global', MPWPB_GLOBAL_PLUGIN_URL . '/assets/mp_style/mpwpb_plugin_global.js', array('jquery'), time());
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
				wp_enqueue_script('jquery.timepicker.min', MPWPB_GLOBAL_PLUGIN_URL . '/assets/admin/jquery.timepicker.min.js', array('jquery'), time(), true);
				wp_enqueue_style('jquery.timepicker.min', MPWPB_GLOBAL_PLUGIN_URL . '/assets/admin/jquery.timepicker.min.css', array(), time());
				//=====================//
				wp_enqueue_script('form-field-dependency', MPWPB_GLOBAL_PLUGIN_URL . '/assets/admin/form-field-dependency.js', array('jquery'), '1.0', true);
				// admin setting global
				wp_enqueue_script('mpwpb_admin_settings', MPWPB_GLOBAL_PLUGIN_URL . '/assets/admin/mpwpb_admin_settings.js', array('jquery'), time(), true);
				wp_enqueue_style('mpwpb_admin_settings', MPWPB_GLOBAL_PLUGIN_URL . '/assets/admin/mpwpb_admin_settings.css', array(), time());
				do_action('add_mpwpb_admin_enqueue');
			}
			public function frontend_enqueue() {
				$this->global_enqueue();
				do_action('add_mpwpb_frontend_enqueue');
			}
			public function add_admin_head() {
				$this->js_constant();
			}
			public function add_frontend_head() {
				$this->js_constant();
				$this->custom_css();
			}
			public function js_constant() {
				?>
				<script type="text/javascript">
					let mpwpb_currency_symbol = "";
					let mpwpb_currency_position = "";
					let mpwpb_currency_decimal = "";
					let mpwpb_currency_thousands_separator = "";
					let mpwpb_num_of_decimal = "";
					let mpwpb_ajax_url = "<?php echo esc_js(admin_url('admin-ajax.php')); ?>";
					let mpwpb_empty_image_url = "<?php echo esc_js(MPWPB_GLOBAL_PLUGIN_URL . '/assets/images/no_image.png'); ?>";
					let mpwpb_date_format = "<?php echo esc_js(MPWPB_Global_Function::get_settings('mpwpb_global_settings', 'date_format', 'D d M , yy')); ?>";
					let mpwpb_date_format_without_year = "<?php echo esc_js(MPWPB_Global_Function::get_settings('mpwpb_global_settings', 'date_format_without_year', 'D d M')); ?>";
				</script>
				<?php
				if (MPWPB_Global_Function::check_woocommerce() == 1) {
					?>
					<script type="text/javascript">
						mpwpb_currency_symbol = "<?php echo esc_js(get_woocommerce_currency_symbol()); ?>";
						mpwpb_currency_position = "<?php echo esc_js(get_option('woocommerce_currency_pos')); ?>";
						mpwpb_currency_decimal = "<?php echo esc_js(wc_get_price_decimal_separator()); ?>";
						mpwpb_currency_thousands_separator = "<?php echo esc_js(wc_get_price_thousand_separator()); ?>";
						mpwpb_num_of_decimal = "<?php echo esc_js(get_option('woocommerce_price_num_decimals', 2)); ?>";
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