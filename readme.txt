=== WPBookingly – Appointment Booking Plugin for WooCommerce | Online Booking Calendar & Service Manager ===
Contributors: aamahin, magepeopleteam
Tags: appointment booking, booking calendar, service booking, online booking, woocommerce booking
Requires at least: 5.3
Tested up to: 7.0
Requires PHP: 7.0
Stable tag: 1.3.1
WC requires at least: 3.0
WC tested up to: 10.9.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Turn any WordPress site into an online appointment booking system. Real-time calendar, staff scheduling, WooCommerce payments, coupons, and a customer account dashboard — all in one plugin.

== Description ==

**WPBookingly** is a modern, mobile-ready **appointment booking plugin for WordPress** that makes it easy to manage services, staff, and customer appointments without writing a single line of code. Whether you run a salon, clinic, consultancy, gym, car rental, or tutoring business, WPBookingly gives you a professional [online booking calendar](https://mage-people.com/product/service-booking-plugin-wpbookingly/) your customers can use from any device.

Customers browse real-time availability, pick a service and time slot, and confirm their appointment instantly — right on your website. With a lightweight codebase, shortcode support, and compatibility with any WordPress theme or page builder, WPBookingly is built to help you organize bookings, cut down on no-shows, and save time every day.

▶️ [Watch the Quick Overview Video](https://youtu.be/46_WERFyyGc)
🌐 [Live Frontend Demo](https://wpbookingly.com/)
📘 [Full Documentation](https://docs.mage-people.com/docs/plugins/wpbookingly/overview)
⬇️ [Free Download on WordPress.org](https://downloads.wordpress.org/plugin/service-booking-manager.zip)

= Why Choose WPBookingly as Your WordPress Booking Plugin? =

* **No coding required** — set up a complete service booking website in minutes
* **Works with or without WooCommerce** — choose the checkout that fits your business
* **Mobile-first design** — fast, responsive booking on desktop, tablet, and phone
* **Built for every industry** — salons, clinics, consultants, rentals, tutors, and more
* **SEO-friendly and lightweight** — won't slow your site down

= Who Uses WPBookingly? =

💇‍♀️ **Salons & Spas** — Let clients pick a stylist, service, and time slot online.
🧹 **Cleaning & Repair Services** — Assign jobs, set availability, confirm bookings instantly.
💼 **Consultants & Coaches** — Schedule sessions, send reminders, manage payments.
🏥 **Clinics & Therapists** — Handle multi-staff schedules and patient appointments.
📸 **Photographers** — Show available dates, manage session types, collect deposits.
🚗 **Car Rental & Automotive Services** — Display vehicles, set pricing, take instant reservations.
🎓 **Tutors & Training Centers** — Run recurring classes and accept payments with ease.

= Core Features (Free) =

**Booking & Scheduling**
* Real-time calendar sync to prevent double bookings
* Custom staff schedules and working hours
* Configurable service durations and buffer time
* Automated time-slot management
* Group booking support
* Recurring appointment controls

**Checkout & Payments**
* Two full checkout modes: **WooCommerce mode** (taxes, coupons, gateways, emails, orders) or **Custom/Native mode** (WPBookingly's own cart, checkout, and orders — no WooCommerce required)
* Inline WooCommerce checkout inside the booking drawer with gateway redirects and automatic retry on temporary failures
* Advanced booking coupons with service, price, quantity, date, staff, and usage restrictions
* Partial-payment support across native and WooCommerce checkout flows

**Customer Experience**
* Modern customer account dashboard (native mode) or enhanced WooCommerce My Account (WooCommerce mode)
* View, reorder, reschedule, and request cancellation of bookings
* Recurring-series management for repeat appointments
* Customizable input fields to collect extra client details

**Admin Tools**
* Modern service editor: categories, extras, pricing, schedules, staff, taxes, waiting lists, FAQs, galleries
* One-Click Business Templates to launch a ready-to-book service in seconds
* Booking administration, staff dashboard, reviews, and cancellation-request management
* Analytics and reporting overview

**Security & Compliance**
* Server-side validation, nonces, rate limiting, and capability/ownership checks
* Protected booking and account access with verified payment references
* GDPR consent and data export/erasure tools

= Shortcodes =

| Shortcode | Purpose |
|---|---|
| `[service-booking post_id="123"]` | Display the booking interface for a specific service |
| `[mpwpb-user-dashboard]` | Display the customer booking dashboard |
| `[custom_payment_my_account]` | Display the Custom/Native payment My Account area |
| `[mpwpb_booking_confirmation]` | Display a protected order confirmation screen |

= Every Booking Website Needs These Features =

📅 Real-time calendar sync.
🔁 Recurring & group bookings· 
💳 Secure online payments (Stripe, PayPal, and more)· 
📨 Automated email/SMS notifications· 
🧩 Flexible booking-form placement via shortcode · 
🌍 Multilingual & theme compatibility· 
📊 CSV data export and reporting

= Upgrade to WPBookingly Pro =

Take your booking system further with:

* Real-time Google Calendar sync
* Advanced staff management and multi-staff assignment
* Recurring appointments and automated SMS reminders
* PDF invoices and downloadable booking tickets
* CSV data export
* Custom client forms
* Group & multi-service booking
* Advanced analytics and reporting
* Extended WooCommerce payment gateways

👉 Try the [WPBookingly Pro Demo](https://mage-people.com/product/wordpress-service-booking-plugin-all-kind-of-service-booking-solution/)

= Step-by-Step Industry Guides =

Setting up a booking site for your industry takes just a few steps: install WordPress, pick a compatible theme, install WPBookingly, configure WooCommerce (optional), add your services, publish the booking shortcode, and start accepting appointments. Detailed walkthroughs are available for:

* [Salon & Spa Booking Website](https://mage-people.com/wordpress-salon-booking-plugin/)
* [Music & Educational Class Booking](https://mage-people.com/how-to-set-up-an-online-musical-class-appointment-system-using-wpbookingly/)
* [Healthcare Appointment System](https://mage-people.com/set-up-an-online-medical-appointment-system/)
* [Fitness & Gym Booking System](https://mage-people.com/yoga-class-booking-system-with-wpbookingly/)
* [Car Rental & Repair Booking](https://mage-people.com/car-rental-plugin-for-wordpress/)
* [Restaurant & Hospitality Booking](https://mage-people.com/booking-uc/wordpress-medical-service-appointment-booking-solution/)

== Installation ==

1. Upload the `service-booking-manager` folder to `/wp-content/plugins/`, or install WPBookingly directly from **Plugins > Add New** in your WordPress dashboard.
2. Activate the plugin. Activation automatically registers the service post type and refreshes permalink rules.
3. Go to **WPBookingly > Quick Setup** and choose a payment mode:
   * **Custom/Native mode** — no WooCommerce required. WPBookingly provides its own cart, checkout, orders, confirmation screen, and My Account page.
   * **WooCommerce mode** — requires WooCommerce installed and active. Uses WooCommerce's taxes, checkout, coupons, gateways, emails, and order management.
4. Configure business hours, payment settings, booking policies, and account pages under **WPBookingly > Settings**.
5. Create a service, add service items and a schedule, then publish it.
6. Display the booking form using the service permalink or the `[service-booking post_id="123"]` shortcode on any page.

== Frequently Asked Questions ==

= Is WPBookingly free to use? =
Yes. The core WPBookingly plugin is completely free. WPBookingly Pro adds premium features such as Google Calendar sync, advanced staff management, PDF invoices, and SMS notifications for businesses that need a more advanced setup.

= Do I need WooCommerce to use WPBookingly? =
No. WPBookingly works with or without WooCommerce. Choose **Custom/Native mode** to run bookings, checkout, and customer accounts without WooCommerce, or choose **WooCommerce mode** to use WooCommerce's checkout, taxes, coupons, gateways, and order management.

= What shortcode do I use to display a booking form? =
Use `[service-booking post_id="123"]` and replace `123` with your service's post ID. The older `[bookingplus]` shortcode is obsolete and should not be used.

= Why does my service page show a 404 error? =
Current versions register the service post type and flush rewrite rules automatically on activation. If you upgraded from an older version, deactivate and reactivate WPBookingly, or go to **Settings > Permalinks** and click Save Changes. If the issue persists, check your web server's rewrite rules and file permissions.

= Which payment gateways does WPBookingly support? =
In WooCommerce mode, WPBookingly uses any gateway enabled in WooCommerce, including Stripe and PayPal. Custom/Native mode offers its own gateway options based on your installed edition and Payment Settings. Always test gateway credentials over HTTPS before going live.

= Can customers manage their own bookings? =
Yes. Customers can view booking details, reorder a service, request cancellation, and reschedule eligible appointments from their account area. WooCommerce-mode bookings appear under WooCommerce **My Account > Orders**; Custom/Native bookings appear on the automatically created WPBookingly My Account page, or on any page using the `[mpwpb-user-dashboard]` shortcode.

= Can customers cancel or reschedule an appointment? =
Eligible bookings display cancellation and reschedule controls in the customer account area. Cancellation is submitted as a request and stays pending until an administrator approves it. Availability, ownership, and your configured lead-time policy are always verified on the server.

= Does WPBookingly support recurring appointments and series management? =
Yes, when recurring booking is enabled for a service. Customers can view the full series, edit eligible appointments, add a new appointment, and request cancellation of the entire series from their account.

= How does the booking calendar decide which time slots are available? =
The calendar calculates available dates and times using the service's working days, operating hours, duration, capacity, holidays, staff availability, existing bookings, and any booking rules you've configured.

= Can I create custom booking forms with extra fields? =
Yes. Each service can include its own pricing, schedule, availability, extra services, staff assignments, FAQs, and custom client input fields.

= Is WPBookingly compatible with any WordPress theme? =
Yes. WPBookingly is built to work with any properly coded WordPress theme and popular page builders, and it's fully translation-ready for multilingual sites.

= Where do I report a security vulnerability? =
Please report security issues through the official [Patchstack Vulnerability Disclosure Program](https://patchstack.com/database/vdp/9e5fc652-d645-44ea-8d5a-0947db247fec). Patchstack assists with verification, CVE assignment, and notifying the plugin developers.

== Screenshots ==

1. Real-time booking calendar and service selection interface.
2. Modern service editor with categories, pricing, schedules, and staff.
3. Custom/Native checkout inside the booking drawer.
4. Customer My Account dashboard with orders and booking management.
5. Admin analytics, booking administration, and staff dashboard.

== Changelog ==

= 1.3.1 =
* Added a modern service creation and editing workflow covering categories, extras, schedules, recurring rules, tax, staff, waiting lists, FAQs, galleries, and formatted service details.
* Added One-Click Business Templates that generate an editable, ready-to-book service with sample pricing, content, FAQs, schedules, and staff.
* Added an improved quick-setup experience, expanded service list, analytics, staff tools, reviews management, and a searchable in-plugin help guide.
* Added partial-payment support across Custom/Native, classic WooCommerce, and WooCommerce Checkout Blocks flows.
* Added a complete Custom/Native checkout with protected confirmations, native orders, offline payments, and Stripe/PayPal controls.
* Added an inline WooCommerce checkout inside the booking drawer, including billing fields, taxes, coupons, terms, gateway redirects, and checkout-state recovery.
* Added reliable retry handling for temporary checkout failures so customers keep their booking selections.
* Added a modern Custom/Native My Account area with dashboard, orders, billing, and profile security.
* Added booking actions to WooCommerce My Account: View, Reorder, Request Cancellation, Reschedule, Manage Recurring, and Cancel Series.
* Added customer cancellation requests with administrator approval, booking history, and policy lead times.
* Added recurring-series management for eligible customers.
* Added advanced booking coupons with service, value, quantity, schedule, customer, and staff restrictions.
* Added GDPR consent, cookie controls, and data export/erasure tools.
* Improved the service page, booking drawer, checkout, account tables, and dialogs with responsive styling.
* Fixed service permalinks after activation.
* Fixed Service Details formatting so rich text from the WordPress editor renders correctly on the frontend.
* Fixed intermittent WooCommerce checkout loading, duplicate cart/order attempts, and gateway redirect handling.
* Hardened checkout, booking, account, and upload requests with validation, sanitisation, nonces, and rate limits.

= 1.2.2 =
* Added recurring appointment controls, staff assignment and schedules, and responsive booking layouts.
* Added 24-hour time-format support and improved availability handling.
* Added assisted WooCommerce installation with AJAX setup and demo import.
* Improved permalink sanitisation and request authorisation.

= 1.2.0 =
* Added analytics, recurring booking, waiting-list, and customer-dashboard foundations.
* Added sortable service content and refreshed date/time selection.
* Fixed zero-price, extra-service, and order-display issues.

= 1.1.8 =
* Improved the booking shortcode and resolved service-booking and checkout issues.

= 1.1.3 =
* Added sortable service categories, subcategories, and FAQs.
* Improved service import, icon/image display, and AJAX loading states.

= 1.1.2 =
* Added buffer-time support and improved startup compatibility checks.

= 1.1.1 =
* Improved staff visibility rules and service updates.

= 1.1.0 =
* Improved the service-booking workflow and checkout configuration.

= 1.0.0 =
Initial release of Service Booking Manager (WPBookingly). No upgrade necessary at this time.

== Upgrade Notice ==

= 1.3.1 =
Back up your site before updating. After updating, review Payment Settings and booking policies, clear any page/cache optimisation caches, and test one complete booking in your selected checkout mode. Existing service, booking, and order data remains fully intact.
