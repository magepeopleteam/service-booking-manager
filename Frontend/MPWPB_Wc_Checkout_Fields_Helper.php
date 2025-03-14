<?php
	if (!defined('ABSPATH')) {
		die;
	} // Cannot access pages directly.
	/**
	 * Class MPWPB_Wc_Checkout_Fields_Helper
	 *
	 * @since 1.0
	 *
	 * */
	if (!class_exists('MPWPB_Wc_Checkout_Fields_Helper')) {
		class MPWPB_Wc_Checkout_Fields_Helper {
			private $error;
			public static $settings_options;
			public static $default_woocommerce_checkout_fields;
			public static $default_woocommerce_checkout_required_fields;
			public static $default_app_required_fields;
			private $allowed_extensions;
			private $allowed_mime_types;
			public function __construct() {
				add_action( 'woocommerce_after_order_notes', [$this,'add_nonce']);
				$this->error = new WP_Error();
				$this->init();
			}
            public function add_nonce() {
	            wp_nonce_field( 'mp_checkout_nonce_action', 'mp_checkout_nonce_action' );
            }
			public static function woocommerce_default_checkout_fields() {
				return array
				(
					"billing" => array(
						"billing_first_name" => array(
							"label" => "First name",
							"required" => "1",
							"class" => array(
								"0" => "form-row-first"
							),
							"autocomplete" => "given-name",
							"priority" => "10",
						),
						"billing_last_name" => array(
							"label" => "Last name",
							"required" => "1",
							"class" => array(
								"0" => "form-row-last"
							),
							"autocomplete" => "family-name",
							"priority" => "20",
						),
						"billing_company" => array(
							"label" => "Company name",
							"class" => array(
								"0" => "form-row-wide",
							),
							"autocomplete" => "organization",
							"priority" => "30",
							"required" => '',
						),
						"billing_country" => array(
							"type" => "country",
							"label" => "Country / Region",
							"required" => "1",
							"class" => array(
								"0" => "form-row-wide",
								"1" => "address-field",
								"2" => "update_totals_on_change",
							),
							"autocomplete" => "country",
							"priority" => "40",
						),
						"billing_address_1" => array(
							"label" => "Street address",
							"placeholder" => "House number and street name",
							"required" => "1",
							"class" => array(
								"0" => "form-row-wide",
								"1" => "address-field"
							),
							"autocomplete" => "address-line1",
							"priority" => "50"
						),
						"billing_address_2" => array(
							"label" => "Apartment, suite, unit, etc.",
							"label_class" => array(
								"0" => "screen-reader-text",
							),
							"placeholder" => "Apartment, suite, unit, etc. (optional)",
							"class" => array(
								"0" => "form-row-wide",
								"1" => "address-field"
							),
							"autocomplete" => "address-line2",
							"priority" => "60",
							"required" => "",
						),
						"billing_city" => array(
							"label" => "Town / City",
							"required" => "1",
							"class" => array(
								"0" => "form-row-wide",
								"1" => "address-field",
							),
							"autocomplete" => "address-level2",
							"priority" => "70",
						),
						"billing_state" => array(
							"type" => "state",
							"label" => "State / County",
							"required" => "",
							"class" => array(
								"0" => "form-row-wide",
								"1" => "address-field"
							),
							"validate" => array(
								"0" => "state"
							),
							"autocomplete" => "address-level1",
							"priority" => "80",
							"country_field" => "billing_country",
							"country" => "AF"
						),
						"billing_postcode" => array(
							"label" => "Postcode / ZIP",
							"required" => "1",
							"class" => array(
								"0" => "form-row-wide",
								"1" => "address-field"
							),
							"validate" => array(
								"0" => "postcode",
							),
							"autocomplete" => "postal-code",
							"priority" => "90"
						),
						"billing_phone" => array(
							"label" => "Phone",
							"required" => "1",
							"type" => "tel",
							"class" => array(
								"0" => "form-row-wide",
							),
							"validate" => array(
								"0" => "phone",
							),
							"autocomplete" => "tel",
							"priority" => "100"
						),
						'billing_email' => array(
							"label" => "Email address",
							"required" => "1",
							"type" => "email",
							"class" => array(
								"0" => "form-row-wide",
							),
							"validate" => array(
								"0" => "email",
							),
							"autocomplete" => "email username",
							"priority" => "110",
						)
					),
					'shipping' => array(
						'shipping_first_name' => array(
							"label" => "First name",
							"required" => "1",
							"class" => array(
								"0" => "form-row-first",
							),
							"autocomplete" => "given-name",
							"priority" => "10",
						),
						"shipping_last_name" => array(
							"label" => "Last name",
							"required" => "1",
							"class" => array(
								"0" => "form-row-last",
							),
							"autocomplete" => "family-name",
							"priority" => "20",
						),
						"shipping_company" => array(
							"label" => "Company name",
							"class" => array(
								"0" => "form-row-wide",
							),
							"autocomplete" => "organization",
							"priority" => "30",
							"required" => "",
						),
						"shipping_country" => array(
							"type" => "country",
							"label" => "Country / Region",
							"required" => "1",
							"class" => array(
								"0" => "form-row-wide",
								"1" => "address-field",
								"2" => "update_totals_on_change",
							),
							"autocomplete" => "country",
							"priority" => "40",
						),
						"shipping_address_1" => array(
							"label" => "Street address",
							"placeholder" => "House number and street name",
							"required" => "1",
							"class" => array(
								"0" => "form-row-wide",
								"1" => "address-field",
							),
							"autocomplete" => "address-line1",
							"priority" => "50",
						),
						"shipping_address_2" => array(
							"label" => "Apartment, suite, unit, etc.",
							"label_class" => array(
								"0" => "screen-reader-text",
							),
							"placeholder" => "Apartment, suite, unit, etc. (optional)",
							"class" => array(
								"0" => "form-row-wide",
								"1" => "address-field",
							),
							"autocomplete" => "address-line2",
							"priority" => "60",
							"required" => "",
						),
						"shipping_city" => array(
							"label" => "Town / City",
							"required" => "1",
							"class" => array(
								"0" => "form-row-wide",
								"1" => "address-field",
							),
							"autocomplete" => "address-level2",
							"priority" => "70"
						),
						"shipping_state" => array(
							"type" => "state",
							"label" => "State / County",
							"required" => "",
							"class" => array(
								"0" => "form-row-wide",
								"1" => "address-field",
							),
							"validate" => array(
								"0" => "state",
							),
							"autocomplete" => "address-level1",
							"priority" => "80",
							"country_field" => "shipping_country",
							"country" => "AF",
						),
						"shipping_postcode" => array(
							"label" => "Postcode / ZIP",
							"required" => "1",
							"class" => array(
								"0" => "form-row-wide",
								"1" => "address-field"
							),
							"validate" => array(
								"0" => "postcode",
							),
							"autocomplete" => "postal-code",
							"priority" => "90",
						),
					),
					"order" => array(
						"order_comments" => array(
							"type" => "textarea",
							"class" => array(
								"0" => "notes",
							),
							"label" => "Order notes",
							"placeholder" => "Notes about your order, e.g. special notes for delivery.",
						)
					),
				);
			}
			public function init() {
				self::$settings_options = get_option('mpwpb_custom_checkout_fields');
				self::$default_woocommerce_checkout_fields = self::woocommerce_default_checkout_fields();
				self::$default_woocommerce_checkout_required_fields = self::default_woocommerce_checkout_required_fields();
				self::$default_app_required_fields = self::default_app_required_fields();
				$this->allowed_extensions = array('jpg', 'jpeg', 'png', 'pdf');
				$this->allowed_mime_types = array(
					"jpg|jpeg|jpe" => "image/jpeg",
					"png" => "image/png",
					"pdf" => "application/pdf"
				);
				add_filter('woocommerce_checkout_fields', array($this, 'get_checkout_fields_for_checkout'), 10);
				add_action('woocommerce_after_checkout_billing_form', array($this, 'file_upload_field'));
				add_action('woocommerce_after_checkout_shipping_form', array($this, 'file_upload_field'));
				add_action('woocommerce_after_checkout_order_form', array($this, 'file_upload_field'));
				add_action('woocommerce_checkout_update_order_meta', array($this, 'save_custom_checkout_fields_to_order'), 99, 2);
				add_action('woocommerce_before_order_details', array($this, 'order_details'), 99, 1);
				add_action('woocommerce_admin_order_data_after_billing_address', array($this, 'order_details'), 99, 1);
				add_action('woocommerce_admin_order_data_after_shipping_address', array($this, 'order_details'), 99, 1);
			}
			public static function get_checkout_fields_for_list() {
				$fields = array();
				$checkout_fields = self::$default_woocommerce_checkout_fields;
				$fields['billing'] = $checkout_fields['billing'];
				$fields['shipping'] = $checkout_fields['shipping'];
				$fields['order'] = $checkout_fields['order'];
				if (isset(self::$settings_options) && is_array(self::$settings_options)) {
					foreach (self::$settings_options as $key => $key_fields) {
						if (is_array($key_fields)) {
							foreach ($key_fields as $name => $field_array) {
								if (self::check_deleted_field($key, $name)) {
									unset($fields[$key][$name]);
								} else {
									$fields[$key][$name] = $field_array;
								}
							}
						}
					}
				}
				if (isset($checkout_fields) && is_array($checkout_fields)) {
					foreach ($checkout_fields as $key => $key_fields) {
						if (is_array($key_fields)) {
							foreach ($key_fields as $name => $field_array) {
								if (self::check_disabled_field($key, $name)) {
									$fields[$key][$name]['disabled'] = '1';
								}
							}
						}
					}
				}
				return $fields;
			}
			public function get_checkout_fields_for_checkout() {
				$fields = array();
				$checkout_fields = WC()->checkout->get_checkout_fields();
				$fields['billing'] = $checkout_fields['billing'];
				$fields['shipping'] = $checkout_fields['shipping'];
				$fields['order'] = $checkout_fields['order'];
				if (isset($checkout_fields) && is_array($checkout_fields)) {
					foreach ($checkout_fields as $key => $key_fields) {
						if (is_array($key_fields)) {
							foreach ($key_fields as $name => $field_array) {
								if (self::check_deleted_field($key, $name) || self::check_disabled_field($key, $name)) {
									unset($fields[$key][$name]);
								}
							}
						}
					}
				}
				if (isset(self::$settings_options) && is_array(self::$settings_options)) {
					foreach (self::$settings_options as $key => $key_fields) {
						if (is_array($key_fields)) {
							foreach ($key_fields as $name => $field_array) {
								if (self::check_deleted_field($key, $name) || self::check_disabled_field($key, $name)) {
									unset($fields[$key][$name]);
								} else {
									$fields[$key][$name] = $field_array;
								}
							}
						}
					}
				}
				if (self::hide_checkout_order_review_section()) {
					remove_action('woocommerce_checkout_order_review', 'woocommerce_order_review', 10);
				}
				if (self::hide_checkout_order_additional_information_section() || (isset($fields['order']) && is_array($fields['order']) && count($fields['order']) == 0)) {
					add_filter('woocommerce_enable_order_notes_field', '__return_false');
				}
				return $fields;
			}
			public static function hide_checkout_order_additional_information_section() {
				if (!self::$settings_options || (is_array(self::$settings_options) && ((!array_key_exists('hide_checkout_order_additional_information', self::$settings_options)) || (array_key_exists('hide_checkout_order_additional_information', self::$settings_options) && self::$settings_options['hide_checkout_order_additional_information'] == 'on')))) {
					return true;
				}
			}
			public static function hide_checkout_order_review_section() {
				if (!self::$settings_options || (is_array(self::$settings_options) && ((!array_key_exists('hide_checkout_order_review', self::$settings_options)) || (array_key_exists('hide_checkout_order_review', self::$settings_options) && self::$settings_options['hide_checkout_order_review'] == 'on')))) {
					return true;
				}
			}
			public static function check_deleted_field($key, $name) {
				if ((isset(self::$settings_options[$key][$name]) && (isset(self::$settings_options[$key][$name]['deleted']) && self::$settings_options[$key][$name]['deleted'] == 'deleted'))) {
					return true;
				} else {
					return false;
				}
			}
			public static function check_disabled_field($key, $name) {
				$default_disabled_field = array('billing' => array('billing_company' => '', 'billing_country' => '', 'billing_address_1' => '', 'billing_address_2' => '', 'billing_city' => '', 'billing_state' => '', 'billing_postcode' => ''));
				if ((!isset(self::$settings_options[$key][$name]) && isset($default_disabled_field[$key][$name])) || (isset(self::$settings_options[$key][$name]) && (isset(self::$settings_options[$key][$name]['disabled']) && self::$settings_options[$key][$name]['disabled'] == '1'))) {
					return true;
				} else {
					return false;
				}
			}
			public static function default_woocommerce_checkout_required_fields() {
				return array(
					'billing' => array('billing_first_name' => array('required' => '1'), 'billing_last_name' => array('required' => '1'), 'billing_country' => array('required' => '1'), 'billing_address_1' => array('required' => '1'), 'billing_city' => array('required' => '1'), 'billing_state' => array('required' => '1'), 'billing_postcode' => array('required' => '1'), 'billing_phone' => array('required' => '1'), 'billing_email' => array('required' => '1')),
					'shipping' => array('shipping_first_name' => array('required' => '1'), 'shipping_last_name' => array('required' => '1'), 'shipping_country' => array('required' => '1'), 'shipping_address_1' => array('required' => '1'), 'shipping_city' => array('required' => '1'), 'shipping_state' => array('required' => '1')),
				);
			}
			public static function default_app_required_fields() {
				return array(
					'billing' => array('billing_first_name' => array('required' => '1'), 'billing_last_name' => array('required' => '1'), 'billing_phone' => array('required' => '1'), 'billing_email' => array('required' => '1')),
					'shipping' => array(),
				);
			}
			function file_upload_field() {
				$checkout_fields = $this->get_checkout_fields_for_checkout();
				$billing_fields = $checkout_fields['billing'];
				$shipping_fields = $checkout_fields['shipping'];
				$order_fields = $checkout_fields['order'];
				$current_action = current_filter();
				if ($current_action == 'woocommerce_after_checkout_billing_form') {
					if (in_array('file', array_column($billing_fields, 'type'))) {
						$billing_file_fields = array_filter($billing_fields, array($this, 'get_file_fields'));
						$this->file_upload_field_element($billing_file_fields);
					}
				} else if ($current_action == 'woocommerce_after_checkout_shipping_form') {
					if (in_array('file', array_column($shipping_fields, 'type'))) {
						$shipping_file_fields = array_filter($shipping_fields, array($this, 'get_file_fields'));
						$this->file_upload_field_element($shipping_file_fields);
					}
				} else if ($current_action == 'woocommerce_after_checkout_order_form') {
					if (in_array('file', array_column($order_fields, 'type'))) {
						$order_file_fields = array_filter($order_fields, array($this, 'get_file_fields'));
						$this->file_upload_field_element($order_file_fields);
					}
				}
			}
			public function get_other_fields($field) {
				return (is_array($field) && isset($field['custom_field']) && $field['custom_field'] == '1' && isset($field['type']) && $field['type'] != 'file');
			}
			public function get_file_fields($field) {
				return (is_array($field) && isset($field['custom_field']) && $field['custom_field'] == '1' && isset($field['type']) && $field['type'] == 'file');
			}
			public function hidden_element($key) {
				WC()->session->set_customer_session_cookie(true);
				$user_id = get_current_user_id();
				$user_data = get_userdata($user_id);
				if ($user_data) {
					$billing_address_1 = get_user_meta($user_id, 'billing_address_1', true);
					$billing_city = get_user_meta($user_id, 'billing_city', true);
					$billing_state = get_user_meta($user_id, 'billing_state', true);
					$billing_postcode = get_user_meta($user_id, 'billing_postcode', true);
					$billing_country = get_user_meta($user_id, 'billing_country', true);
				}
				$value = 'Software Made Data';
				if ($key == 'billing_address_1' || $key == 'shipping_address_1') {
					$value = isset($billing_address_1) ? $billing_address_1 : 'Software Made Data';
				} else if ($key == 'billing_country' || $key == 'shipping_country') {
					$value = isset($billing_country) ? $billing_country : 'BD';
				} else if ($key == 'billing_state' || $key == 'shipping_state') {
					$value = isset($billing_state) ? $billing_state : 'Dhaka';
				} else if ($key == 'billing_city' || $key == 'shipping_city') {
					$value = isset($billing_city) ? $billing_city : 'Dhaka';
				} else if ($key == 'billing_postcode' || $key == 'shipping_postcode') {
					$value = isset($billing_postcode) ? $billing_postcode : '1205';
				}
				?>
                <input type="hidden" class="<?php echo esc_attr($key . '-custom'); ?>" id="<?php echo esc_attr($key . '-custom'); ?>" name="<?php echo esc_attr($key); ?>" value="<?php echo esc_attr($value); ?>"/>
				<?php
			}
			public function file_upload_field_element($fields) {
				foreach ($fields as $key => $field) {
					?>
                    <p class="form-row form-row-wide <?php echo esc_attr(isset($field['required']) && $field['required'] == '1' ? ' validate-required ' : ''); ?> <?php echo esc_attr(isset($field['validate']) && is_array($field['validate']) && count($field['validate']) ? implode(' validate-', $field['validate']) : ''); ?>" id="<?php echo esc_attr($key . '_field'); ?>" data-priority="<?php echo esc_attr(isset($field['priority']) ? $field['priority'] : ''); ?>">
                        <label for="<?php echo esc_attr($key); ?>"><?php echo esc_html($field['label']); ?>

							<?php
								if (isset($field['required']) && $field['required'] == '1') {
									?><abbr class="required" title="required">*</abbr><?php
								}
							?>
                        </label>
                        <span class="woocommerce-input-wrapper">
                    <input type="file" id="<?php echo esc_attr($key . '_file'); ?>" name="<?php echo esc_attr($key . '_file'); ?>" <?php echo esc_attr(isset($field['required']) && $field['required'] == '1' ? 'required' : ''); ?> accept=".jpe?g,.png,.pdf"/>
                    <input type="hidden" id="<?php echo esc_attr($key); ?>" name="<?php echo esc_attr($key); ?>" <?php echo esc_attr(isset($field['required']) && $field['required'] == '1' ? 'required' : ''); ?> value=""/>
                    </span>
                    </p>
					<?php
				}
			}
			function save_custom_checkout_fields_to_order($order_id, $data) {
				if (!isset($_POST['mp_checkout_nonce_action']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['mp_checkout_nonce_action'])), 'mp_checkout_nonce_action')) {
					return;
				}
				$checkout_key_fields = $this->get_checkout_fields_for_checkout();
				foreach ($checkout_key_fields as $key => $checkout_fields) {
					if (is_array($checkout_fields) && count($checkout_fields)) {
						$checkout_other_fields = array_filter($checkout_fields, array($this, 'get_other_fields'));
						foreach ($checkout_other_fields as $q => $file_fields) {
                            $value=isset($_POST[$q])?sanitize_text_field(wp_unslash($_POST[$q])):'';
							update_post_meta($order_id, '_' . $q, $value);
						}
						if (in_array('file', array_column($checkout_fields, 'type'))) {
							$checkout_file_fields = array_filter($checkout_fields, array($this, 'get_file_fields'));
							foreach ($checkout_file_fields as $p => $file_fields) {
								$image_url = $this->get_uploaded_image_link($p . '_file');
								update_post_meta($order_id, '_' . $p, esc_url($image_url));
							}
						}
					}
				}
			}
			function get_post($order_id) {
				$args = array(
					'post_type' => 'mpwpb_booking',
					'posts_per_page' => -1,
					'meta_query' => array(
						array(
							'key' => 'link_mpwpb_id',
							'value' => $order_id,
							'compare' => '='
						),
					)
				);
				$query = new WP_Query($args);
				$post_ids = array();
				if ($query->have_posts()) {
					while ($query->have_posts()) {
						$query->the_post();
						$post_ids[] = get_the_ID();
					}
					wp_reset_postdata();
				}
				return $post_ids;
			}
			function get_uploaded_image_link($file_field_name) {
				if (!isset($_POST['mp_checkout_nonce_action']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['mp_checkout_nonce_action'])), 'mp_checkout_nonce_action')) {
					return false;
				}
				$file_field_name = sanitize_key($file_field_name);
				$upload_dir = wp_upload_dir();
				$image_url = '';
				if (isset($_FILES[$file_field_name]) && !empty($_FILES[$file_field_name]['name'])) {
					$file = array_map('sanitize_text_field',$_FILES[$file_field_name]);
					$file_name = $file['name'];
					$file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
					$file_type = wp_check_filetype($file_name, $this->allowed_mime_types);
					if (in_array($file_extension, $this->allowed_extensions) && $file_type['type']) {
						$path = $upload_dir['path'] . '/' . $file_name;
						if (wp_handle_upload($file['tmp_name'], $path)) {
							$image_url = $upload_dir['url'] . '/' . $file_name;
						}
					}
				}
				if ($image_url) {
					return $image_url;
				} else {
					return false;
				}
			}
			function order_details($order_id) {
				$order = wc_get_order($order_id);
				$checkout_fields = $this->get_checkout_fields_for_checkout();
				$billing_fields = $checkout_fields['billing'];
				$shipping_fields = $checkout_fields['shipping'];
				$order_fields = $checkout_fields['order'];
				$current_action = current_filter();
				if ($current_action == 'woocommerce_admin_order_data_after_billing_address') {
					$checkout_billing_other_fields = array_filter($billing_fields, array($this, 'get_other_fields'));
					$this->prepare_other_field($checkout_billing_other_fields, 'billing', $order);
					if (in_array('file', array_column($billing_fields, 'type'))) {
						$checkout_billing_file_fields = array_filter($billing_fields, array($this, 'get_file_fields'));
						$this->prepare_file_field($checkout_billing_file_fields, 'billing', $order);
					}
				} else if ($current_action == 'woocommerce_admin_order_data_after_shipping_address') {
					$checkout_shipping_other_fields = array_filter($shipping_fields, array($this, 'get_other_fields'));
					$this->prepare_other_field($checkout_shipping_other_fields, 'shipping', $order);
					if (in_array('file', array_column($shipping_fields, 'type'))) {
						$checkout_shipping_file_fields = array_filter($shipping_fields, array($this, 'get_file_fields'));
						$this->prepare_file_field($checkout_shipping_file_fields, 'shipping', $order);
					}
				} else if ($current_action == 'woocommerce_admin_order_data_after_order_address') {
					$checkout_order_other_fields = array_filter($order_fields, array($this, 'get_other_fields'));
					$this->prepare_other_field($checkout_order_other_fields, 'order', $order);
					if (in_array('file', array_column($order_fields, 'type'))) {
						$checkout_order_file_fields = array_filter($order_fields, array($this, 'get_file_fields'));
						$this->prepare_file_field($checkout_order_file_fields, 'order', $order);
					}
				}
			}
			function prepare_other_field($custom_fields, $key, $order) {
				?>
                <div class="order_data_column_container">
					<?php if (is_array($custom_fields) && count($custom_fields)) : ?>
                        <div class="order_data_column">
                            <h3><?php echo esc_html('Custom ' . $key); ?></h3>
							<?php foreach ($custom_fields as $name => $field_array): ?>
								<?php
								unset($key_value);
								$key_value = get_post_meta($order->get_id(), '_' . $name, true);
								$key_value = esc_html($key_value);
								$field_label = isset($field_array['label']) ? esc_html($field_array['label'] . ' :') : '';
								$field_name = esc_attr($name);
								?>
                                <p class="form-field form-field-wide">
                                    <strong><?php echo esc_html($field_label); ?></strong>
                                    <label for="<?php echo esc_attr($field_name); ?>"><?php echo esc_html($key_value); ?></label>
                                </p>
							<?php endforeach; ?>
                        </div>
					<?php endif; ?>
                </div>
				<?php
			}
			function prepare_file_field($custom_fields, $key, $order) {
				?>
                <div class="order_data_column_container">
					<?php if (is_array($custom_fields) && count($custom_fields)) : ?>
                        <div class="order_data_column">
                            <h3><?php echo esc_html('Custom ' . $key . ' File'); ?></h3>
							<?php foreach ($custom_fields as $name => $field_array) : ?>
								<?php
								unset($key_value);
								$key_value = get_post_meta($order->get_id(), '_' . $name, true);
								$key_value = esc_url($key_value);
								$field_label = isset($field_array['label']) ? esc_html($field_array['label'] . ' :') : '';
								$field_name = esc_attr($name);
								$file_extension = strtolower(pathinfo($key_value, PATHINFO_EXTENSION));
								$file_type = wp_check_filetype($key_value, $this->allowed_mime_types);
								?>
                                <p class="form-field form-field-wide">
                                    <strong><?php echo esc_html($field_label); ?></strong>
									<?php if (in_array($file_extension, $this->allowed_extensions) && $file_type['type']) : ?>
										<?php if ($file_extension !== 'pdf') : ?>
                                            <img src="<?php echo esc_url($key_value); ?>" alt="<?php echo esc_attr($field_name); ?> image" width="100" height="100">
                                            <a class="button button-tiny button-primary" href="<?php echo esc_url($key_value); ?>" download>Download</a>
										<?php else : ?>
                                            <a class="button button-tiny button-primary" href="<?php echo esc_url($key_value); ?>" download>Download PDF</a>
										<?php endif; ?>
									<?php endif; ?>
                                </p>
							<?php endforeach; ?>
                        </div>
					<?php endif; ?>
                </div>
				<?php
			}
		}
		new MPWPB_Wc_Checkout_Fields_Helper();
	}