<?php
	if (!defined('ABSPATH')) {
		die;
	} // Cannot access pages directly.
	if (!class_exists('MPWPB_Dependencies')) {
		class MPWPB_Dependencies {
			public function __construct() {
				add_action('init', [$this, 'language_load']);
				$this->load_file();
				add_action('wp_enqueue_scripts', [$this, 'frontend_script'], 90);
				add_action('admin_enqueue_scripts', [$this, 'admin_scripts'], 90);
				add_action('admin_head', [$this, 'js_constant'], 5);
				add_action('wp_head', [$this, 'js_constant'], 5);
			}
			public function language_load(): void {
				$plugin_dir = basename(dirname(__DIR__)) . "/languages/";
				load_plugin_textdomain('service-booking-manager', false, $plugin_dir);
			}
			private function load_file(): void {
				require_once MPWPB_PLUGIN_DIR . '/inc/MPWPB_Function.php';
				require_once MPWPB_PLUGIN_DIR . '/inc/MPWPB_Query.php';
				require_once MPWPB_PLUGIN_DIR . '/inc/MPWPB_Layout.php';
				require_once MPWPB_PLUGIN_DIR . '/Admin/MPWPB_Admin.php';
				require_once MPWPB_PLUGIN_DIR . '/Frontend/MPWPB_Frontend.php';
				require_once MPWPB_PLUGIN_DIR . '/Frontend/MPWPB_Order_layout.php';
			}
			public function global_enqueue() {
				wp_enqueue_script('jquery');
				wp_enqueue_script('jquery-ui-core');
				wp_enqueue_script('jquery-ui-datepicker');
				wp_localize_script('jquery', 'mpwpb_ajax', ['mpwpb_ajax' => admin_url('admin-ajax.php')]);
				wp_enqueue_style('mp_jquery_ui', MPWPB_PLUGIN_URL . '/assets/helper/jquery-ui.min.css', [], '1.13.2');
				wp_enqueue_style('mp_font_awesome', '//cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@5.15.4/css/all.min.css', [], '5.15.4');
				wp_enqueue_style('mp_select_2', MPWPB_PLUGIN_URL . '/assets/helper/select_2/select2.min.css', [], '4.0.13');
				wp_enqueue_script('mp_select_2', MPWPB_PLUGIN_URL . '/assets/helper/select_2/select2.min.js', [], '4.0.13');
				wp_enqueue_style('mp_owl_carousel', MPWPB_PLUGIN_URL . '/assets/helper/owl_carousel/owl.carousel.min.css', [], '2.3.4');
				wp_enqueue_script('mp_owl_carousel', MPWPB_PLUGIN_URL . '/assets/helper/owl_carousel/owl.carousel.min.js', [], '2.3.4');
				wp_enqueue_style('mp_plugin_global', MPWPB_PLUGIN_URL . '/assets/helper/mp_style/mp_style.css', [], time());
				wp_enqueue_script('mp_plugin_global', MPWPB_PLUGIN_URL . '/assets/helper/mp_style/mp_script.js', [], time(), true);
				do_action('add_mpwpb_common_script');
			}
			public function admin_scripts() {
				$this->global_enqueue();
				wp_enqueue_editor();
				//Admin script
				wp_enqueue_script('jquery-ui-sortable');
				wp_enqueue_style('wp-color-picker');
				wp_enqueue_script('wp-color-picker');
				wp_enqueue_style('wp-codemirror');
				wp_enqueue_script('wp-codemirror');
				//**********************//
				wp_enqueue_style('mp_admin_settings', MPWPB_PLUGIN_URL . '/assets/admin/mp_admin_settings.css', [], time());
				wp_enqueue_script('mp_admin_settings', MPWPB_PLUGIN_URL . '/assets/admin/mp_admin_settings.js', ['jquery'], time(), true);
				// ****custom************//
				wp_enqueue_style('mpwpb_admin', MPWPB_PLUGIN_URL . '/assets/admin/mpwpb_admin.css', [], time());
				wp_enqueue_script('mpwpb_admin', MPWPB_PLUGIN_URL . '/assets/admin/mpwpb_admin.js', ['jquery'], time(), true);
				do_action('add_mpwpb_admin_script');
			}
			public function frontend_script() {
				$this->global_enqueue();
				// custom
				wp_enqueue_style('mpwpb', MPWPB_PLUGIN_URL . '/assets/frontend/mpwpb.css', [], time());
				wp_enqueue_script('mpwpb', MPWPB_PLUGIN_URL . '/assets/frontend/mpwpb.js', ['jquery'], time(), true);
				wp_enqueue_style('mpwpb_registration', MPWPB_PLUGIN_URL . '/assets/frontend/mpwpb_registration.css', [], time());
				//wp_enqueue_script('mpwpb_registration', MPWPB_PLUGIN_URL . '/assets/frontend/mpwpb_registration.js', ['jquery'], time(), true);
				wp_enqueue_script('mpwpb_registration', MPWPB_PLUGIN_URL . '/assets/frontend/mpwpb_registration.js', ['jquery'], time(), true);
				do_action('add_mpwpb_frontend_script');
			}
			public function js_constant() {
				?>
				<script type="text/javascript">
					let mp_ajax_url = "<?php echo admin_url('admin-ajax.php'); ?>";
					let mp_currency_symbol = "<?php echo get_woocommerce_currency_symbol(); ?>";
					let mp_currency_position = "<?php echo get_option('woocommerce_currency_pos'); ?>";
					let mp_currency_decimal = "<?php echo wc_get_price_decimal_separator(); ?>";
					let mp_currency_thousands_separator = "<?php echo wc_get_price_thousand_separator(); ?>";
					let mp_num_of_decimal = "<?php echo get_option('woocommerce_price_num_decimals', 2); ?>";
					let mp_empty_image_url = "<?php echo esc_attr(MPWPB_PLUGIN_URL . '/assets/helper/images/no_image.png'); ?>";
					let mp_date_format = "<?php echo esc_attr(MPWPB_Function::get_general_settings('date_format', 'D d M , yy')); ?>";
				</script>
				<?php
			}
		}
		new MPWPB_Dependencies();
	}