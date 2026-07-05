<?php
	/*
	 * Modern-only "Categories & Services" UI for the Pricing step of the
	 * modern service editor. Reuses the EXISTING classic AJAX endpoints
	 * (MPWPB_Service_Category / MPWPB_Services in Admin/settings/Category.php
	 * and Service.php) for all persistence — this class only renders a fresh
	 * shell + the initial data payload; every interaction is then driven
	 * client-side by mpwpb-categories-services-modern.js against those same
	 * endpoints. The classic card-grid UI (MPWPB_Price_Settings) is untouched
	 * and still used as-is when the admin is in Classic mode.
	 */
	if (!defined('ABSPATH')) {
		die;
	} // Cannot access pages directly.
	if (!class_exists('MPWPB_Categories_Services_Modern')) {
		class MPWPB_Categories_Services_Modern {
			/**
			 * Reads the same three meta keys the classic Category/Service
			 * classes read, tagging every row with its real array-index as
			 * `id` (now a STABLE id post-fix — see the delete_* changes in
			 * Admin/settings/Category.php and Service.php).
			 */
			private function get_payload($post_id) {
				$categories = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_category_service', array());
				$categories = is_array($categories) ? $categories : array();
				$sub_categories = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_sub_category_service', array());
				$sub_categories = is_array($sub_categories) ? $sub_categories : array();
				$services = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_service', array());
				$services = is_array($services) ? $services : array();

				$out_categories = array();
				foreach ($categories as $id => $cat) {
					$out_categories[] = array(
						'id' => (string) $id,
						'name' => isset($cat['name']) ? $cat['name'] : '',
					);
				}
				$out_sub_categories = array();
				foreach ($sub_categories as $id => $sub) {
					$out_sub_categories[] = array(
						'id' => (string) $id,
						'name' => isset($sub['name']) ? $sub['name'] : '',
						'catId' => isset($sub['cat_id']) ? (string) $sub['cat_id'] : '',
					);
				}
				$out_services = array();
				foreach ($services as $id => $svc) {
					$out_services[] = array(
						'id' => (string) $id,
						'name' => isset($svc['name']) ? $svc['name'] : '',
						'desc' => isset($svc['details']) ? $svc['details'] : '',
						'price' => isset($svc['price']) ? $svc['price'] : '',
						'unit' => isset($svc['service_unit']) ? $svc['service_unit'] : '',
						'duration' => isset($svc['duration']) ? $svc['duration'] : '',
						'catStatus' => isset($svc['show_cat_status']) ? $svc['show_cat_status'] : '',
						'parentCat' => (isset($svc['parent_cat']) && $svc['parent_cat'] !== '') ? (string) $svc['parent_cat'] : '',
						'subCat' => (isset($svc['sub_cat']) && $svc['sub_cat'] !== '') ? (string) $svc['sub_cat'] : '',
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
					'categories' => $out_categories,
					'subCategories' => $out_sub_categories,
					'services' => $out_services,
					'i18n' => array(
						'allServices' => esc_html__('All services', 'service-booking-manager'),
						'uncategorized' => esc_html__('Uncategorized', 'service-booking-manager'),
						'newCategory' => esc_html__('New category', 'service-booking-manager'),
						'noCategory' => esc_html__('No category', 'service-booking-manager'),
						'addService' => esc_html__('Add service', 'service-booking-manager'),
						'editService' => esc_html__('Edit service', 'service-booking-manager'),
						'addCategory' => esc_html__('Add category', 'service-booking-manager'),
						'addSubcategory' => esc_html__('Add subcategory', 'service-booking-manager'),
						'deleteCategoryTitle' => esc_html__('Delete category?', 'service-booking-manager'),
						'deleteServiceTitle' => esc_html__('Delete service?', 'service-booking-manager'),
						'searchPlaceholder' => esc_html__('Search services', 'service-booking-manager'),
						'noServicesHere' => esc_html__('No services here yet', 'service-booking-manager'),
						'tryDifferentSearch' => esc_html__('Try a different search.', 'service-booking-manager'),
						'addServiceHint' => esc_html__('Add a service to this view — you can assign a category later.', 'service-booking-manager'),
						'noCategoriesYet' => esc_html__('No categories yet. Create one to start organizing.', 'service-booking-manager'),
						'countsHint' => esc_html__('Counts include services in subcategories. Deleting a category moves its services to Uncategorized — nothing is lost.', 'service-booking-manager'),
					),
				);
			}

			public function render($post_id) {
				$payload = $this->get_payload($post_id);
				wp_localize_script('mpwpb-categories-services-modern', 'mpwpbCsm', $payload);
				?>
				<div class="mpwpb-sme__postfields mpwpb-sme__postfields--full" data-sme-section="MPWPB_Categories_Services_Modern">
					<div class="mpwpb-sme__postfields-header">
						<div class="mpwpb-sme__postfields-header-title"><?php esc_html_e('Services & Pricing', 'service-booking-manager'); ?></div>
						<div class="mpwpb-sme__postfields-header-sub"><?php esc_html_e('Organize services into categories, or leave them uncategorized.', 'service-booking-manager'); ?></div>
					</div>
					<div class="mpwpb-sme__postfields-body">
						<div id="mpwpb-csm" class="mpwpb-csm"></div>
					</div>
				</div>
				<?php
			}
		}
	}
