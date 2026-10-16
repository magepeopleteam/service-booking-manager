<?php
	/*
	 * Modern-only "Service Feature Details" card for the General step.
	 * Renders an empty slot; the real feature-list repeater (and its
	 * "Enable Service Features" toggle) is relocated here by JS from its
	 * original spot inside the Service Details card, where
	 * MPWPB_Service_Details::service_details() — untouched — still renders
	 * it normally first. Real DOM nodes are moved, not duplicated, so
	 * mpwpb_features_status / mpwpb_features[] still submit exactly once.
	 * Classic mode keeps Service Features inside its own Service Details
	 * tab, unchanged.
	 */
	if (!defined('ABSPATH')) {
		die;
	} // Cannot access pages directly.
	if (!class_exists('MPWPB_Service_Features_Modern')) {
		class MPWPB_Service_Features_Modern {
			public function render($post_id) {
				?>
				<div class="mpwpb-sme__field-slot" data-sme-features-slot
				     data-one="<?php esc_attr_e('1 feature', 'service-booking-manager'); ?>"
				     data-many="<?php /* translators: %d: number of features */ esc_attr_e('%d features', 'service-booking-manager'); ?>"
				     data-placeholder="<?php esc_attr_e('Add a feature, e.g. Free first consultation', 'service-booking-manager'); ?>"
				     data-add="<?php esc_attr_e('Add', 'service-booking-manager'); ?>"
				     data-hint="<?php esc_attr_e('Press Enter to add. Drag the handle to change the order.', 'service-booking-manager'); ?>"
				     data-empty="<?php esc_attr_e('No features yet. Add a few short points customers care about.', 'service-booking-manager'); ?>"
				     data-drag="<?php esc_attr_e('Drag to reorder', 'service-booking-manager'); ?>"
				     data-remove="<?php esc_attr_e('Remove feature', 'service-booking-manager'); ?>"
				     data-feature="<?php esc_attr_e('Feature', 'service-booking-manager'); ?>"></div>
				<?php
			}
		}
	}
