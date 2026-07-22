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
				<div class="mpwpb-sme__field-slot" data-sme-features-slot></div>
				<?php
			}
		}
	}
