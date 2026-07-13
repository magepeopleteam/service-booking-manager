<?php
	/*
	 * Modern-only "Extra Service" list for the Pricing step of the modern
	 * service editor. Reuses the EXISTING classic AJAX endpoints
	 * (MPWPB_Extra_service_Settings in Admin/settings/Extra_service.php) for
	 * all persistence — this class only renders a fresh list shell + the
	 * initial data payload; every interaction is driven client-side by
	 * mpwpb-extra-service-modern.js against those same endpoints. The
	 * classic card-grid UI is untouched and still used as-is in Classic mode.
	 *
	 * Visually reuses .mpwpb-csm__* classes from mpwpb-categories-services-
	 * modern.css (declared as a style dependency) rather than duplicating
	 * near-identical list/row/modal styles in a second stylesheet.
	 */
	if (!defined('ABSPATH')) {
		die;
	} // Cannot access pages directly.
	if (!class_exists('MPWPB_Extra_Service_Modern')) {
		class MPWPB_Extra_Service_Modern {
			/**
			 * A fresh instance (no constructor side effects) purely to reach
			 * the classic class's get_extra_services() — which also handles
			 * one-time migration of legacy-format data — rather than reading
			 * the raw meta directly and risking stale/pre-migration shape.
			 */
			private function classic_instance() {
				if (!class_exists('MPWPB_Extra_service_Settings')) {
					return null;
				}
				try {
					$ref = new ReflectionClass('MPWPB_Extra_service_Settings');
					return $ref->newInstanceWithoutConstructor();
				} catch (\ReflectionException $e) {
					return null;
				}
			}

			private function get_payload($post_id) {
				$instance = $this->classic_instance();
				$items = ($instance && method_exists($instance, 'get_extra_services')) ? $instance->get_extra_services($post_id) : array();
				$items = is_array($items) ? $items : array();

				$out = array();
				foreach ($items as $id => $item) {
					$out[] = array(
						'id' => (string) $id,
						'name' => isset($item['name']) ? $item['name'] : '',
						'desc' => isset($item['details']) ? $item['details'] : '',
						'price' => isset($item['price']) ? $item['price'] : '',
						'qty' => isset($item['qty']) ? $item['qty'] : '',
					);
				}

				$currency_symbol = MPWPB_Global_Function::is_wc_payment_mode()
					? get_woocommerce_currency_symbol()
					: MPWPB_Global_Function::native_currency_setting('symbol', '$');

				return array(
					'postId' => (int) $post_id,
					'ajaxUrl' => admin_url('admin-ajax.php'),
					'nonce' => wp_create_nonce('mpwpb_admin_nonce'),
					'currencySymbol' => $currency_symbol,
					'items' => $out,
					'i18n' => array(
						'addService' => esc_html__('Add Extra Service', 'service-booking-manager'),
						'editService' => esc_html__('Edit Extra Service', 'service-booking-manager'),
						'deleteTitle' => esc_html__('Delete extra service?', 'service-booking-manager'),
						'searchPlaceholder' => esc_html__('Search extra services', 'service-booking-manager'),
						'noneYet' => esc_html__('No extra services yet', 'service-booking-manager'),
						'addHint' => esc_html__('Add an optional paid add-on customers can choose during booking.', 'service-booking-manager'),
						'tryDifferentSearch' => esc_html__('Try a different search.', 'service-booking-manager'),
					),
				);
			}

			public function render($post_id) {
				$payload = $this->get_payload($post_id);
				wp_localize_script('mpwpb-extra-service-modern', 'mpwpbEsm', $payload);

				// The real "Enable Extra Service" checkbox still needs to exist
				// somewhere in the form for the main Update/Publish submit to
				// save it (MPWPB_Extra_service_Settings::save_extra_service_settings()
				// reads it directly from $_POST on the standard 'mpwpb_settings_save'
				// hook — this isn't one of the AJAX-saved list items). JS relocates
				// this same node up into the card header, same as before.
				$extra_service_active = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_extra_service_active', 'off');
				?>
				<div class="mpwpb-esm-toggle-slot">
					<label class="roundSwitchLabel">
						<input type="checkbox" name="mpwpb_extra_service_active" <?php checked($extra_service_active, 'on'); ?>/>
						<span class="roundSwitch"></span>
					</label>
				</div>
				<div id="mpwpb-esm" class="mpwpb-esm"></div>
				<?php
			}
		}
	}
