<?php
if (!defined('ABSPATH')) {
	exit;
}

if (!class_exists('MPWPB_Help_Guide_Content')) {
	class MPWPB_Help_Guide_Content {
		private static function field($label, $description, $tip = '') {
			return array('label' => $label, 'description' => $description, 'tip' => $tip);
		}

		private static function topic($id, $title, $summary, $where, $fields = array(), $options = array()) {
			return array_merge(array(
				'id' => $id, 'title' => $title, 'summary' => $summary, 'where' => $where,
				'tier' => 'free', 'fields' => $fields, 'steps' => array(), 'note' => '',
				'keywords' => '', 'url_key' => '',
			), $options);
		}

		private static function section($id, $title, $description, $icon, $topics) {
			return array('id' => $id, 'title' => $title, 'description' => $description, 'icon' => $icon, 'tier' => 'free', 'topics' => $topics);
		}

		public static function get_sections() {
			return array(
				self::section('free-start', __('Start Here', 'service-booking-manager'), __('A practical path from installation to a successful test booking.', 'service-booking-manager'), 'start', array(
					self::topic('free-launch-checklist', __('Five-step launch checklist', 'service-booking-manager'), __('Complete the essential setup in the right order.', 'service-booking-manager'), __('WPBookingly menu', 'service-booking-manager'), array(), array(
						'steps' => array(
							__('Create a service and give it a clear public title.', 'service-booking-manager'),
							__('Add services, prices, duration, capacity, and optional extras.', 'service-booking-manager'),
							__('Set bookable dates, working hours, breaks, and unavailable dates.', 'service-booking-manager'),
							__('Choose WooCommerce or the available checkout mode and confirm its settings.', 'service-booking-manager'),
							__('Publish the service, open it as a customer, and complete a real test booking.', 'service-booking-manager'),
						),
						'url_key' => 'add_service', 'keywords' => 'setup wizard configure launch getting started',
					)),
					self::topic('free-first-test', __('Run your first test booking', 'service-booking-manager'), __('Test the full customer journey before sharing a service.', 'service-booking-manager'), __('WPBookingly → Service List → View a published service', 'service-booking-manager'), array(), array(
						'steps' => array(
							__('Use a future date that has available working hours.', 'service-booking-manager'),
							__('Select a service, staff member if enabled, and any extras.', 'service-booking-manager'),
							__('Complete checkout with a test-safe payment method.', 'service-booking-manager'),
							__('Confirm the booking appears in the appropriate order or booking view and that emails arrive.', 'service-booking-manager'),
						),
						'note' => __('Use a separate customer email address so you can check exactly what a real customer receives.', 'service-booking-manager'),
						'url_key' => 'services', 'keywords' => 'test booking publish preview customer journey email',
					)),
				)),

				self::section('free-admin-map', __('Admin Map', 'service-booking-manager'), __('Know which screen to open for each everyday task.', 'service-booking-manager'), 'map', array(
					self::topic('free-admin-pages', __('Where everything lives', 'service-booking-manager'), __('A quick map of the WPBookingly administration area.', 'service-booking-manager'), __('WordPress Admin → WPBookingly', 'service-booking-manager'), array(
						self::field(__('Service List', 'service-booking-manager'), __('Create, edit, duplicate, publish, and preview bookable services.', 'service-booking-manager')),
						self::field(__('Coupons', 'service-booking-manager'), __('Create promotional codes and control when, where, and how often each code works.', 'service-booking-manager')),
						self::field(__('Status', 'service-booking-manager'), __('Check server, WordPress, WooCommerce, and supporting-component health.', 'service-booking-manager')),
						self::field(__('Reviews', 'service-booking-manager'), __('Review customer feedback and moderate its visibility.', 'service-booking-manager')),
						self::field(__('Staff Members', 'service-booking-manager'), __('Create staff profiles, assign services, and manage personal working schedules.', 'service-booking-manager')),
						self::field(__('Settings', 'service-booking-manager'), __('Control global labels, URLs, checkout, privacy, display, timing, and appearance.', 'service-booking-manager')),
						self::field(__('Analytics', 'service-booking-manager'), __('Measure bookings and revenue, filter results, and export CSV data.', 'service-booking-manager')),
					), array('url_key' => 'services', 'keywords' => 'menu navigation find screen admin')),
				)),

				self::section('free-service', __('Creating a Service', 'service-booking-manager'), __('Build the information, choices, pricing, and presentation customers see.', 'service-booking-manager'), 'service', array(
					self::topic('free-service-general', __('General information and display', 'service-booking-manager'), __('Name the service and control its public presentation.', 'service-booking-manager'), __('WPBookingly → Service List → Add New or Edit → General', 'service-booking-manager'), array(
						self::field(__('Service title', 'service-booking-manager'), __('The main public name shown in WordPress and the booking page.', 'service-booking-manager'), __('Use a specific customer-friendly name such as “60-minute Consultation”.', 'service-booking-manager')),
						self::field(__('Shortcode title', 'service-booking-manager'), __('Optional heading displayed above the embedded booking interface.', 'service-booking-manager')),
						self::field(__('Shortcode subtitle', 'service-booking-manager'), __('Supporting sentence shown with the booking interface.', 'service-booking-manager')),
						self::field(__('Template', 'service-booking-manager'), __('Chooses the available frontend layout for this service.', 'service-booking-manager')),
						self::field(__('Featured image / thumbnail', 'service-booking-manager'), __('The primary visual used in service cards and the service page.', 'service-booking-manager')),
						self::field(__('Service overview', 'service-booking-manager'), __('Controls and supplies the longer introduction customers read before booking.', 'service-booking-manager')),
					), array('url_key' => 'add_service', 'keywords' => 'title subtitle thumbnail template overview shortcode')),
					self::topic('free-categories-services', __('Categories and service choices', 'service-booking-manager'), __('Organize related choices and define each billable service.', 'service-booking-manager'), __('Edit Service → Categories & Services', 'service-booking-manager'), array(
						self::field(__('Enable multiple categories', 'service-booking-manager'), __('Allows the booking form to present more than one category group.', 'service-booking-manager')),
						self::field(__('Enable multiple service selection', 'service-booking-manager'), __('Lets a customer choose more than one service in the same booking where supported.', 'service-booking-manager')),
						self::field(__('Category', 'service-booking-manager'), __('A top-level group such as Cleaning, Beauty, or Consultation.', 'service-booking-manager')),
						self::field(__('Subcategory', 'service-booking-manager'), __('An optional second level used to keep a large catalog easy to scan.', 'service-booking-manager')),
						self::field(__('Service name', 'service-booking-manager'), __('The choice the customer selects inside a category.', 'service-booking-manager')),
						self::field(__('Price', 'service-booking-manager'), __('Base cost for one unit of this service.', 'service-booking-manager')),
						self::field(__('Unit', 'service-booking-manager'), __('Explains what the price covers, such as person, room, hour, or item.', 'service-booking-manager')),
						self::field(__('Duration', 'service-booking-manager'), __('Customer-facing duration text for this choice.', 'service-booking-manager')),
						self::field(__('Description', 'service-booking-manager'), __('Short details that help the customer choose correctly.', 'service-booking-manager')),
						self::field(__('Image or icon', 'service-booking-manager'), __('Visual identifier shown with the choice.', 'service-booking-manager')),
					), array('url_key' => 'services', 'keywords' => 'category subcategory service price unit duration quantity multiple')),
					self::topic('free-extra-services', __('Extra services', 'service-booking-manager'), __('Offer optional add-ons alongside the main booking.', 'service-booking-manager'), __('Edit Service → Extra Services', 'service-booking-manager'), array(
						self::field(__('Extra service name', 'service-booking-manager'), __('The add-on label customers see, such as “Deep conditioning”.', 'service-booking-manager')),
						self::field(__('Extra price', 'service-booking-manager'), __('Additional amount charged for each selected extra.', 'service-booking-manager')),
						self::field(__('Available quantity', 'service-booking-manager'), __('Maximum quantity a customer may choose.', 'service-booking-manager')),
						self::field(__('Description', 'service-booking-manager'), __('Clarifies what the extra includes.', 'service-booking-manager')),
						self::field(__('Image or icon', 'service-booking-manager'), __('Makes the optional add-on easier to identify.', 'service-booking-manager')),
					), array('url_key' => 'services', 'keywords' => 'addon add-on extras upsell quantity')),
					self::topic('free-service-content', __('Details, features, FAQ, ratings, and gallery', 'service-booking-manager'), __('Add the information customers need before making a decision.', 'service-booking-manager'), __('Edit Service → Service Features / Details / FAQ / Gallery', 'service-booking-manager'), array(
						self::field(__('Feature or detail rows', 'service-booking-manager'), __('Structured highlights such as inclusions, facilities, or benefits.', 'service-booking-manager')),
						self::field(__('Displayed rating', 'service-booking-manager'), __('Optional rating value presented in the service information.', 'service-booking-manager')),
						self::field(__('Rating scale', 'service-booking-manager'), __('Explains the maximum score, for example “Out of 5”.', 'service-booking-manager')),
						self::field(__('Rating text', 'service-booking-manager'), __('Supporting text such as the number of ratings.', 'service-booking-manager')),
						self::field(__('FAQ question and answer', 'service-booking-manager'), __('Reusable customer guidance shown as common questions.', 'service-booking-manager')),
						self::field(__('Gallery images', 'service-booking-manager'), __('Additional photos that explain the location, result, or experience.', 'service-booking-manager')),
					), array('url_key' => 'services', 'keywords' => 'details feature faq question answer rating gallery images')),
				)),

				self::section('free-availability', __('Availability & Scheduling', 'service-booking-manager'), __('Control which dates and times customers can actually reserve.', 'service-booking-manager'), 'calendar', array(
					self::topic('free-service-schedule', __('Service dates, hours, slots, and capacity', 'service-booking-manager'), __('Build the service’s bookable calendar and prevent invalid slots.', 'service-booking-manager'), __('Edit Service → Date & Time', 'service-booking-manager'), array(
						self::field(__('Date type', 'service-booking-manager'), __('Choose a repeating schedule or a list of particular dates.', 'service-booking-manager')),
						self::field(__('Repeated start date', 'service-booking-manager'), __('First day from which the repeating schedule can be booked.', 'service-booking-manager')),
						self::field(__('Repeat for / repeated after', 'service-booking-manager'), __('Defines the repeating schedule window.', 'service-booking-manager')),
						self::field(__('Active days', 'service-booking-manager'), __('Limits how far or how many days the repeating availability remains active.', 'service-booking-manager')),
						self::field(__('Time-slot length', 'service-booking-manager'), __('Splits working hours into selectable appointment intervals.', 'service-booking-manager'), __('Match this to the real time needed for one appointment.', 'service-booking-manager')),
						self::field(__('Capacity per session', 'service-booking-manager'), __('Maximum bookings accepted for the same slot.', 'service-booking-manager')),
						self::field(__('Particular dates', 'service-booking-manager'), __('Explicit dates used when availability is not weekly or repeating.', 'service-booking-manager')),
						self::field(__('Off days / off dates', 'service-booking-manager'), __('Dates that must remain unavailable even if normal hours would allow booking.', 'service-booking-manager')),
						self::field(__('Weekday start and end time', 'service-booking-manager'), __('Opening and closing time for each active weekday.', 'service-booking-manager')),
						self::field(__('Break start and end time', 'service-booking-manager'), __('Removes a daily interval such as lunch from bookable time.', 'service-booking-manager')),
					), array('url_key' => 'services', 'keywords' => 'date time availability capacity slot repeat particular hours break off holiday')),
					self::topic('free-global-timing', __('Global booking timing rules', 'service-booking-manager'), __('Add preparation time and control how late customers may change bookings.', 'service-booking-manager'), __('WPBookingly → Settings → General', 'service-booking-manager'), array(
						self::field(__('Buffer time', 'service-booking-manager'), __('Adds protected time around appointments so bookings are not placed too close together.', 'service-booking-manager')),
						self::field(__('Cancellation lead time', 'service-booking-manager'), __('Minimum notice required before a customer can cancel.', 'service-booking-manager')),
						self::field(__('Reschedule lead time', 'service-booking-manager'), __('Minimum notice required before a customer can move a booking.', 'service-booking-manager')),
						self::field(__('Booked order statuses', 'service-booking-manager'), __('WooCommerce statuses counted as reserving capacity.', 'service-booking-manager'), __('Include only statuses that truly represent a confirmed or held booking.', 'service-booking-manager')),
					), array('url_key' => 'settings', 'keywords' => 'buffer lead cancellation reschedule booked status')),
				)),

				self::section('free-pricing', __('Pricing, Tax & Discounts', 'service-booking-manager'), __('Explain the total clearly and control tax and repeating-booking incentives.', 'service-booking-manager'), 'pricing', array(
					self::topic('free-tax', __('Tax settings', 'service-booking-manager'), __('Apply a percentage tax to a service when required.', 'service-booking-manager'), __('Edit Service → Tax Settings', 'service-booking-manager'), array(
						self::field(__('Enable tax', 'service-booking-manager'), __('Turns tax calculation on for this service.', 'service-booking-manager')),
						self::field(__('Tax class', 'service-booking-manager'), __('Associates the service with the available tax category.', 'service-booking-manager')),
						self::field(__('Tax rate', 'service-booking-manager'), __('Percentage added according to the configured calculation.', 'service-booking-manager'), __('Confirm local tax requirements with a qualified adviser.', 'service-booking-manager')),
					), array('url_key' => 'services', 'keywords' => 'tax vat percentage rate class')),
					self::topic('free-recurring', __('Recurring bookings', 'service-booking-manager'), __('Allow a customer to reserve a repeated series instead of one appointment.', 'service-booking-manager'), __('Edit Service → Recurring Booking', 'service-booking-manager'), array(
						self::field(__('Recurring types', 'service-booking-manager'), __('Permitted patterns: daily, weekly, bi-weekly, or monthly.', 'service-booking-manager')),
						self::field(__('Maximum recurring count', 'service-booking-manager'), __('Largest number of occurrences a customer may book at once.', 'service-booking-manager')),
						self::field(__('Recurring discount', 'service-booking-manager'), __('Percentage incentive applied to a repeating series.', 'service-booking-manager')),
					), array('url_key' => 'services', 'keywords' => 'recurring repeat daily weekly biweekly monthly discount')),
				)),

				self::section('free-staff', __('Staff', 'service-booking-manager'), __('Create team members, assign work, and give each person a realistic schedule.', 'service-booking-manager'), 'staff', array(
					self::topic('free-staff-management', __('Staff profiles and service assignment', 'service-booking-manager'), __('Connect WordPress users to the services they perform.', 'service-booking-manager'), __('WPBookingly → Staff Members', 'service-booking-manager'), array(
						self::field(__('Existing user', 'service-booking-manager'), __('Links the staff profile to an existing WordPress account.', 'service-booking-manager')),
						self::field(__('Username and password', 'service-booking-manager'), __('Creates login credentials when making a new staff account.', 'service-booking-manager')),
						self::field(__('Email, first name, last name', 'service-booking-manager'), __('Contact and identity details used for the staff profile.', 'service-booking-manager')),
						self::field(__('Profile image', 'service-booking-manager'), __('Picture displayed for the staff member where the selected layout supports it.', 'service-booking-manager')),
						self::field(__('Assigned services', 'service-booking-manager'), __('Limits the services for which this staff member can be selected.', 'service-booking-manager')),
						self::field(__('Holiday behavior', 'service-booking-manager'), __('Controls whether staff-specific unavailable dates modify booking availability.', 'service-booking-manager')),
					), array('url_key' => 'staff', 'keywords' => 'staff user employee assign profile')),
					self::topic('free-staff-schedule', __('Staff availability and dashboard', 'service-booking-manager'), __('Set individual hours and manage appointments without changing the service schedule.', 'service-booking-manager'), __('WPBookingly → Staff Members → Edit; staff users open their dashboard', 'service-booking-manager'), array(
						self::field(__('Date type and active range', 'service-booking-manager'), __('Defines the staff member’s repeating or date-specific availability.', 'service-booking-manager')),
						self::field(__('Off days and dates', 'service-booking-manager'), __('Blocks personal leave and other staff-only absences.', 'service-booking-manager')),
						self::field(__('Weekday hours and breaks', 'service-booking-manager'), __('Sets personal working and break times.', 'service-booking-manager')),
						self::field(__('Appointment filters', 'service-booking-manager'), __('Finds bookings by date, service, or status in the staff dashboard.', 'service-booking-manager')),
						self::field(__('Reschedule date and time', 'service-booking-manager'), __('Moves an eligible appointment to another available slot.', 'service-booking-manager')),
						self::field(__('Profile contact and preferences', 'service-booking-manager'), __('Lets staff maintain name, email, phone, address, city, password, and working preferences.', 'service-booking-manager')),
					), array('url_key' => 'staff', 'keywords' => 'staff schedule holiday dashboard appointment reschedule profile')),
				)),

				self::section('free-booking', __('Booking Experience', 'service-booking-manager'), __('Understand the customer journey and the controls that affect it.', 'service-booking-manager'), 'booking', array(
					self::topic('free-booking-options', __('Selection and customer dashboard', 'service-booking-manager'), __('Control how customers choose and later manage bookings.', 'service-booking-manager'), __('Edit Service and the customer account/dashboard pages', 'service-booking-manager'), array(
						self::field(__('Multiple categories', 'service-booking-manager'), __('Shows several category groups in one booking flow.', 'service-booking-manager')),
						self::field(__('Multiple services', 'service-booking-manager'), __('Allows several compatible choices in one booking.', 'service-booking-manager')),
						self::field(__('Sticky booking widget', 'service-booking-manager'), __('Keeps the booking panel visible while a customer scrolls on supported layouts.', 'service-booking-manager')),
						self::field(__('Single-page checkout', 'service-booking-manager'), __('Keeps supported selection and checkout steps together for a shorter journey.', 'service-booking-manager')),
						self::field(__('Cancel / reschedule', 'service-booking-manager'), __('Customer actions are allowed only when status and lead-time rules permit them.', 'service-booking-manager')),
						self::field(__('Waiting list', 'service-booking-manager'), __('The codebase contains waiting-list settings, but the current service-settings tab is not exposed; do not rely on it until a reachable control is provided.', 'service-booking-manager')),
					), array('url_key' => 'settings', 'keywords' => 'customer dashboard cancel reschedule waiting list multiple sticky checkout')),
				)),

				self::section('free-payments', __('Payments & Checkout', 'service-booking-manager'), __('Choose a checkout route and understand payment-related settings.', 'service-booking-manager'), 'payment', array(
					self::topic('free-payment-mode', __('WooCommerce and payment mode', 'service-booking-manager'), __('Choose the order system that matches your site.', 'service-booking-manager'), __('WPBookingly → Settings → Payment Settings', 'service-booking-manager'), array(
						self::field(__('WooCommerce payment mode', 'service-booking-manager'), __('Sends bookings through WooCommerce checkout, gateways, taxes, emails, and order management.', 'service-booking-manager'), __('Use this when your site already relies on WooCommerce gateways or order workflows.', 'service-booking-manager')),
						self::field(__('Custom/native payment mode', 'service-booking-manager'), __('Uses WPBookingly’s native checkout. Its Stripe, PayPal, and offline gateway controls require Pro.', 'service-booking-manager')),
						self::field(__('Checkout field controls', 'service-booking-manager'), __('WooCommerce billing, shipping, account, and order field visibility follows the available checkout settings screen.', 'service-booking-manager')),
					), array('url_key' => 'settings', 'keywords' => 'woocommerce payment mode gateway checkout order')),
					self::topic('free-partial-payment', __('Partial payment', 'service-booking-manager'), __('Collect a deposit first and leave a recorded balance for later payment.', 'service-booking-manager'), __('WPBookingly → Settings → Payment Settings → Partial Payment', 'service-booking-manager'), array(
						self::field(__('Enable partial payment', 'service-booking-manager'), __('Allows an initial amount smaller than the full booking total.', 'service-booking-manager')),
						self::field(__('Deposit type', 'service-booking-manager'), __('Chooses whether the first payment is a percentage or fixed amount where offered.', 'service-booking-manager')),
						self::field(__('Deposit amount', 'service-booking-manager'), __('Defines the amount collected during initial checkout.', 'service-booking-manager')),
						self::field(__('Balance payment', 'service-booking-manager'), __('The remaining amount must be collected and recorded through the supported order/customer workflow.', 'service-booking-manager')),
					), array('url_key' => 'settings', 'keywords' => 'partial payment deposit balance due first payment')),
				)),

				self::section('free-coupons', __('Coupons', 'service-booking-manager'), __('Create controlled promotions without unintentionally discounting every booking.', 'service-booking-manager'), 'coupon', array(
					self::topic('free-coupon-fields', __('Every coupon setting', 'service-booking-manager'), __('Define the offer, eligibility, schedule, staff, and usage limits.', 'service-booking-manager'), __('WPBookingly → Coupons → Add New or Edit', 'service-booking-manager'), array(
						self::field(__('Coupon name and status', 'service-booking-manager'), __('Internal campaign name and whether the coupon is active or draft.', 'service-booking-manager')),
						self::field(__('Coupon code', 'service-booking-manager'), __('The exact code customers enter; use a short memorable value.', 'service-booking-manager')),
						self::field(__('Description', 'service-booking-manager'), __('Internal explanation of the promotion.', 'service-booking-manager')),
						self::field(__('Start and expiry date', 'service-booking-manager'), __('Limits the calendar period in which the code can be used.', 'service-booking-manager')),
						self::field(__('Discount type and value', 'service-booking-manager'), __('Chooses a percentage or fixed reduction and its amount.', 'service-booking-manager')),
						self::field(__('Maximum discount', 'service-booking-manager'), __('Caps a percentage discount where this control is displayed.', 'service-booking-manager')),
						self::field(__('Service scope', 'service-booking-manager'), __('Applies the code to all services or only selected services.', 'service-booking-manager')),
						self::field(__('Minimum / maximum spend', 'service-booking-manager'), __('Requires the booking total to fall inside an allowed range.', 'service-booking-manager')),
						self::field(__('Individual use / sale restrictions', 'service-booking-manager'), __('Prevents incompatible promotion combinations where supported.', 'service-booking-manager')),
						self::field(__('Booking-day restriction', 'service-booking-manager'), __('Restricts the weekday on which the booked service occurs.', 'service-booking-manager')),
						self::field(__('Date mode and dates', 'service-booking-manager'), __('Includes or excludes specific booking dates.', 'service-booking-manager')),
						self::field(__('Time mode, bucket, and range', 'service-booking-manager'), __('Limits coupon use to selected times of day or a custom time range.', 'service-booking-manager')),
						self::field(__('Staff scope and selected staff', 'service-booking-manager'), __('Restricts the offer to bookings handled by particular staff members.', 'service-booking-manager')),
						self::field(__('Total usage limit', 'service-booking-manager'), __('Maximum successful uses across all customers.', 'service-booking-manager')),
						self::field(__('Per-customer usage limit', 'service-booking-manager'), __('Maximum successful uses for one customer.', 'service-booking-manager')),
					), array('url_key' => 'coupons', 'keywords' => 'coupon promo code discount restriction schedule staff limit')),
				)),

				self::section('free-reviews', __('Reviews & Follow-up', 'service-booking-manager'), __('Manage feedback and confirm customers receive the expected communication.', 'service-booking-manager'), 'review', array(
					self::topic('free-reviews-email', __('Reviews and email follow-up', 'service-booking-manager'), __('Moderate feedback and diagnose missing customer messages.', 'service-booking-manager'), __('WPBookingly → Reviews; order and WordPress email settings', 'service-booking-manager'), array(
						self::field(__('Review filters', 'service-booking-manager'), __('Narrow feedback by available service, rating, or moderation state.', 'service-booking-manager')),
						self::field(__('Moderation actions', 'service-booking-manager'), __('Approve, hide, or otherwise manage feedback using the controls displayed.', 'service-booking-manager')),
						self::field(__('Email delivery', 'service-booking-manager'), __('Booking messages depend on the chosen payment flow, WordPress mail delivery, and valid recipient addresses.', 'service-booking-manager'), __('Use an SMTP/mail logging plugin when messages do not arrive consistently.', 'service-booking-manager')),
					), array('url_key' => 'reviews', 'keywords' => 'review rating moderation follow up email notification smtp')),
				)),

				self::section('free-analytics', __('Analytics & CSV', 'service-booking-manager'), __('Read booking performance and export results for reporting.', 'service-booking-manager'), 'analytics', array(
					self::topic('free-analytics-dashboard', __('Dashboard filters, metrics, charts, and export', 'service-booking-manager'), __('Turn booking records into useful operational information.', 'service-booking-manager'), __('WPBookingly → Analytics', 'service-booking-manager'), array(
						self::field(__('Date range', 'service-booking-manager'), __('Limits calculations to the selected booking or reporting period.', 'service-booking-manager')),
						self::field(__('Service filter', 'service-booking-manager'), __('Shows results for all services or one selected service.', 'service-booking-manager')),
						self::field(__('Status filter', 'service-booking-manager'), __('Includes only records in the chosen booking/order state.', 'service-booking-manager')),
						self::field(__('Summary metrics and charts', 'service-booking-manager'), __('Present booking volume, revenue, service, and trend information available in the dashboard.', 'service-booking-manager')),
						self::field(__('CSV export', 'service-booking-manager'), __('Downloads the filtered data for spreadsheets and external reporting.', 'service-booking-manager')),
					), array('url_key' => 'analytics', 'keywords' => 'analytics dashboard report revenue chart csv export filters')),
				)),

				self::section('free-privacy', __('GDPR & Privacy', 'service-booking-manager'), __('Request consent clearly and handle customer privacy requests responsibly.', 'service-booking-manager'), 'privacy', array(
					self::topic('free-gdpr-settings', __('Privacy and consent settings', 'service-booking-manager'), __('Configure the customer-facing privacy messages.', 'service-booking-manager'), __('WPBookingly → Settings → GDPR / Privacy', 'service-booking-manager'), array(
						self::field(__('Enable GDPR', 'service-booking-manager'), __('Turns the plugin’s privacy/consent presentation on.', 'service-booking-manager')),
						self::field(__('Cookie banner message', 'service-booking-manager'), __('Explains why the site uses relevant cookies.', 'service-booking-manager')),
						self::field(__('Accept and reject text', 'service-booking-manager'), __('Labels the customer’s cookie choices.', 'service-booking-manager')),
						self::field(__('Privacy policy page', 'service-booking-manager'), __('Links customers to the site’s full privacy policy.', 'service-booking-manager')),
						self::field(__('Privacy consent text', 'service-booking-manager'), __('Explains agreement to the privacy policy during booking.', 'service-booking-manager')),
						self::field(__('Data consent text', 'service-booking-manager'), __('Explains why booking details are collected and processed.', 'service-booking-manager')),
						self::field(__('Privacy requests', 'service-booking-manager'), __('Use the available request screen to locate and track supported customer privacy requests.', 'service-booking-manager')),
					), array('url_key' => 'settings', 'keywords' => 'gdpr privacy cookie consent request policy data')),
				)),

				self::section('free-style', __('Labels, URLs & Styling', 'service-booking-manager'), __('Match the booking interface to your language, brand, and WordPress structure.', 'service-booking-manager'), 'style', array(
					self::topic('free-global-display', __('All global labels and appearance fields', 'service-booking-manager'), __('Customize terminology, permalinks, formats, media behavior, and colors.', 'service-booking-manager'), __('WPBookingly → Settings → General / Display / Style', 'service-booking-manager'), array(
						self::field(__('Service label, slug, and icon', 'service-booking-manager'), __('Changes the admin/public service name, URL base, and associated icon.', 'service-booking-manager'), __('After changing a slug, save WordPress Permalinks once.', 'service-booking-manager')),
						self::field(__('Category label and slug', 'service-booking-manager'), __('Changes category wording and its URL base.', 'service-booking-manager')),
						self::field(__('Organizer label and slug', 'service-booking-manager'), __('Changes staff/organizer terminology and URL base where used.', 'service-booking-manager')),
						self::field(__('Category, subcategory, and service text', 'service-booking-manager'), __('Replaces customer-facing selection instructions.', 'service-booking-manager')),
						self::field(__('Disable block editor', 'service-booking-manager'), __('Uses the plugin’s intended classic/custom service editing experience.', 'service-booking-manager')),
						self::field(__('Full and short date format', 'service-booking-manager'), __('Controls how dates appear in long and compact contexts.', 'service-booking-manager')),
						self::field(__('24-hour time format', 'service-booking-manager'), __('Switches supported time displays between 24-hour and 12-hour notation.', 'service-booking-manager')),
						self::field(__('Slider type and style', 'service-booking-manager'), __('Chooses the available gallery/slider behavior and visual treatment.', 'service-booking-manager')),
						self::field(__('Indicator visibility and type', 'service-booking-manager'), __('Controls navigation indicators shown with service media.', 'service-booking-manager')),
						self::field(__('Showcase visibility and position', 'service-booking-manager'), __('Controls whether and where the media showcase is presented.', 'service-booking-manager')),
						self::field(__('Popup image/icon indicator', 'service-booking-manager'), __('Chooses cues used for opening media in a popup.', 'service-booking-manager')),
						self::field(__('Theme and alternate color', 'service-booking-manager'), __('Applies the primary and supporting brand colors to supported booking components.', 'service-booking-manager')),
						self::field(__('Custom CSS', 'service-booking-manager'), __('Adds advanced site-specific style overrides.', 'service-booking-manager'), __('Use narrowly scoped selectors and keep a copy before changing themes.', 'service-booking-manager')),
					), array('url_key' => 'settings', 'keywords' => 'label slug permalink date time slider indicator showcase popup theme color css')),
				)),

				self::section('free-troubleshooting', __('Troubleshooting', 'service-booking-manager'), __('Resolve the most common setup problems in a safe order.', 'service-booking-manager'), 'tools', array(
					self::topic('free-common-problems', __('No slots, checkout, email, 404, and staff issues', 'service-booking-manager'), __('Use these checks before changing or reinstalling anything.', 'service-booking-manager'), __('WPBookingly → Status, Settings, Service editor, and Staff Members', 'service-booking-manager'), array(
						self::field(__('No available slots', 'service-booking-manager'), __('Check future dates, weekday hours, breaks, off dates, slot length, capacity, buffer time, staff assignment, and booked statuses.', 'service-booking-manager')),
						self::field(__('Checkout or payment fails', 'service-booking-manager'), __('Confirm the selected payment mode, WooCommerce pages/gateways when used, currency, HTTPS, and gateway credentials for enabled native gateways.', 'service-booking-manager')),
						self::field(__('Emails do not arrive', 'service-booking-manager'), __('Verify recipient addresses, order status, WordPress mail delivery, spam folders, and SMTP logs.', 'service-booking-manager')),
						self::field(__('Service page returns 404', 'service-booking-manager'), __('Open Settings → Permalinks and save once, especially after changing a service or taxonomy slug.', 'service-booking-manager')),
						self::field(__('Staff member not selectable', 'service-booking-manager'), __('Confirm the user is assigned to the service and has availability overlapping the service date and time.', 'service-booking-manager')),
					), array('url_key' => 'status', 'keywords' => 'troubleshoot error no slots checkout payment email 404 permalink staff unavailable')),
				)),
			);
		}
	}
}
