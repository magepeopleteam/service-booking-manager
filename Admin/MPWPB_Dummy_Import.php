<?php
	/*
   * @Author 		engr.sumonazma@gmail.com
   * Copyright: 	mage-people.com
   */
	if (!defined('ABSPATH')) {
		die;
	} // Cannot access pages directly.
	if (!class_exists('MPWPB_Dummy_Import')) {
		class MPWPB_Dummy_Import {
			public function __construct() {
				$this->dummy_import();
			}
			private function dummy_import() {
				$dummy_post = get_option('mpwpb_dummy_already_inserted');
				$all_post = MPWPB_Global_Function::query_post_type('mpwpb_item');
				if ($all_post->post_count == 0 && $dummy_post != 'yes') {
					$dummy_data = $this->dummy_data();
					foreach ($dummy_data as $type => $dummy) {
						if ($type == 'taxonomy') {
							foreach ($dummy as $taxonomy => $dummy_taxonomy) {
								$check_taxonomy = MPWPB_Global_Function::get_taxonomy($taxonomy);
								if (is_string($check_taxonomy) || sizeof($check_taxonomy) == 0) {
									foreach ($dummy_taxonomy as $taxonomy_data) {
										wp_insert_term($taxonomy_data['name'], $taxonomy);
									}
								}
							}
						}
						if ($type == 'custom_post') {
							foreach ($dummy as $custom_post => $dummy_post) {
								$post = MPWPB_Global_Function::query_post_type($custom_post);
								if ($post->post_count == 0) {
									foreach ($dummy_post as $key => $dummy_data) {
										$title = $dummy_data['name'];
										$post_id = wp_insert_post([
											'post_title' => $title,
											'post_status' => 'publish',
											'post_type' => $custom_post
										]);
										if (array_key_exists('post_data', $dummy_data)) {
											foreach ($dummy_data['post_data'] as $meta_key => $data) {
												update_post_meta($post_id, $meta_key, $data);
											}
										}
										require_once(ABSPATH . "wp-admin" . '/includes/image.php');
										$image = MPWPB_PLUGIN_DIR . '/assets/images/dummy-image-' . $key . '.png';
										$image_attached = self::insert_media($image);
										set_post_thumbnail($post_id, $image_attached['id'] ?? '');
									}
								}
							}
						}
					}
					update_option('mpwpb_dummy_already_inserted', 'yes');
				}
			}
			public function dummy_data(): array {
				return [
					'taxonomy' => [],
					'custom_post' => [
						'mpwpb_item' => [
							0 => [
								'name' => 'Hair Cut Salon Booking',
								'post_data' => [
									// General_settings
									'mpwpb_shortcode_title' => 'Hair Cut Service',
									'mpwpb_shortcode_sub_title' => 'Cut your hair beautifully with affordable price',
									// Date_settings
									//'mpwpb_service_start_date' => '2023-03-01',
									'mpwpb_template' => 'static.php',
									'mpwpb_time_slot_length' => '60',
									'mpwpb_capacity_per_session' => '1',
									'mpwpb_default_start_time' => '10',
									'mpwpb_default_end_time' => '16',
									'mpwpb_default_start_break_time' => '13',
									'mpwpb_default_end_break_time' => '15',
									'mpwpb_monday_start_time' => '',
									'mpwpb_monday_end_time' => '',
									'mpwpb_monday_start_break_time' => '',
									'mpwpb_monday_end_break_time' => '',
									'mpwpb_tuesday_start_time' => '',
									'mpwpb_tuesday_end_time' => '',
									'mpwpb_tuesday_start_break_time' => '',
									'mpwpb_tuesday_end_break_time' => '',
									'mpwpb_wednesday_start_time' => '',
									'mpwpb_wednesday_end_time' => '',
									'mpwpb_wednesday_start_break_time' => '',
									'mpwpb_wednesday_end_break_time' => '',
									'mpwpb_thursday_start_time' => '',
									'mpwpb_thursday_end_time' => '',
									'mpwpb_thursday_start_break_time' => '',
									'mpwpb_thursday_end_break_time' => '',
									'mpwpb_off_days' => 'saturday,sunday',
									'mpwpb_off_dates' => [],
									'mpwpb_service_type' => 'car_wash',
									// Price_settings
									'mpwpb_service_details_active' => 'on',
									'mpwpb_service_duration_active' => 'on',
									'mpwpb_category_text' => 'Category',
									'mpwpb_sub_category_text' => 'Sub-Category',
									'mpwpb_service_text' => 'Service',
									'mpwpb_category_service' => [],
									'mpwpb_sub_category_service'=>[],
									'mpwpb_service'=>[
										[
											'name' => 'Fade Haircut',
											'price' => '10',
											'details' => 'Shampooing can strip your hair of all its natural oils, leaving it dry and brittle. Pre-pooing acts as a base or protective barrier against over-cleansing',
											'duration' => '60m',
											'icon' => '',
											'image' => '',
											'show_cat_status' => 'on',
											'parent_cat' => '',
											'sub_cat' => '',
										],
										[
											'name' => 'Taper Haircut',
											'price' => '15',
											'details' => 'Shampooing can strip your hair of all its natural oils, leaving it dry and brittle. Pre-pooing acts as a base or protective barrier against over-cleansing',
											'duration' => '60m',
											'icon' => '',
											'image' => '',
											'show_cat_status' => 'on',
											'parent_cat' =>'',
											'sub_cat' => '',
										],
										[
											'name' => 'Buzz Cut',
											'price' => '20',
											'details' => 'Shampooing can strip your hair of all its natural oils, leaving it dry and brittle. Pre-pooing acts as a base or protective barrier against over-cleansing',
											'duration' => '60m',
											'icon' => '',
											'image' => '',
											'show_cat_status' => 'on',
											'parent_cat' => '',
											'sub_cat' => '',
										],
										[
											'name' => 'Crew Cut',
											'price' => '30',
											'details' => 'Shampooing can strip your hair of all its natural oils, leaving it dry and brittle. Pre-pooing acts as a base or protective barrier against over-cleansing',
											'duration' => '60m',
											'icon' => '',
											'image' => '',
											'show_cat_status' => 'on',
											'parent_cat' => '',
											'sub_cat' => '',
										]
									],
									'mpwpb_extra_service_active' => 'on',
									'mpwpb_service_multiple_category_check' => 'on',
									'mpwpb_multiple_service_select' => 'on',
									'mpwpb_group_extra_service_active' => 'off',
									'mpwpb_extra_service' => [
										[
											'name' => 'Pre Hair Wash',
											'qty' => '10',
											'price' => '10',
											'details' => 'A pre-shampoo treatment (also referred to as a pre-poo) is exactly what it says on the tin, a treatment that is applied to the hair before jumping into the shower to give your hair a good suds and rinse.',
											'icon' => '',
											'image' => ''
										],
											[
												'name' => 'After Hair Wash',
												'qty' => '15',
												'price' => '15',
												'details' => 'A pre-shampoo treatment (also referred to as a pre-poo) is exactly what it says on the tin, a treatment that is applied to the hair before jumping into the shower to give your hair a good suds and rinse.',
												'icon' => '',
												'image' => ''
											],
											[
												'name' => 'Face Wash',
												'qty' => '45',
												'price' => '45',
												'details' => 'A pre-shampoo treatment (also referred to as a pre-poo) is exactly what it says on the tin, a treatment that is applied to the hair before jumping into the shower to give your hair a good suds and rinse.',
												'icon' => '',
												'image' => ''
											]
									],
									// Gallery Settings
									'mpwpb_display_slider' => 'on',
									'mpwpb_slider_images' => [
										0 => ''
									],
									'mpwpb_faq_active' => 'on',
									'mpwpb_faq' => [
										[
											'title' => 'What services can I book?',
											'content' => '<p>You can book a variety of services, including haircuts, coloring, styling, manicures, pedicures, facials, and massages.</p>',
										],
										[
											'title' => 'Is the booking system easy to use?',
											'content' => '<p>Yes, our online booking system is user-friendly, allowing you to navigate and schedule appointments effortlessly.</p>',
										],
										[
											'title' => 'Can I choose my stylist?',
											'content' => '<ul><li><p>Yes, you can select your preferred stylist based on their expertise and availability.</p></li></ul>',
										],
										[
											'title' => 'What if I need to cancel or reschedule my appointment?',
											'content' => '<ul><li><p>You can easily cancel or reschedule your appointment online, ideally 24 hours in advance.</p></li></ul>',
										]
									],
									'mpwpb_features_status' => 'on',
									'mpwpb_features' => [
										"Wide Range of Courses",
										"General Health Checkups",
										"What types of repair services can I book?",
										"Wide Range of Courses",
										"Wide Range of Courses"
									],
									'mpwpb_service_overview_status' => 'on',
									'mpwpb_service_overview_content' => '<ol>
										<li><strong>Select Service</strong>: Choose from our extensive list of salon services.</li>
										<li><strong>Choose Date and Time</strong>: Pick a date and time that works best for you.</li>
										<li><strong>Select Stylist</strong>: If desired, select your preferred stylist based on their availability and expertise.</li>
										<li><strong>Confirm Appointment</strong>: Review your booking details and confirm your appointment.</li>
										<li><strong>Receive Confirmation</strong>: Get an email or text confirmation with your appointment details.</li>
									</ol>
									With our <strong>Salon Booking</strong> system, experiencing top-notch beauty and wellness services is just a few clicks away. Enjoy a seamless appointment process and a relaxing salon experience tailored to your needs!',
									'mpwpb_service_details_status' => 'on',
									'mpwpb_service_details_content' => '<strong>Salon Booking</strong> is a streamlined and user-friendly system designed to enhance your salon experience by making it easy to schedule appointments for a wide range of beauty and wellness services. Whether you need a haircut, manicure, facial, or massage, our platform simplifies the booking process, allowing you to secure your desired time and service with just a few clicks.
									<h3>Key Features:</h3>
									<ol>
										<li><strong>User-Friendly Interface</strong>
									Our online booking system is designed for ease of use, allowing clients to navigate effortlessly through available services, appointment slots, and stylist profiles.</li>
										<li><strong>Comprehensive Service Menu</strong>
									Browse a full range of salon services, including haircuts, coloring, styling, manicures, pedicures, facials, massages, and more. Detailed descriptions help you choose the right service for your needs.</li>
										<li><strong>Flexible Appointment Scheduling</strong>
									Schedule your appointments at your convenience, with options for same-day, next-day, or future bookings. You can easily select a date and time that fits your schedule.</li>
										<li><strong>Stylist Selection</strong>
									Choose your preferred stylist based on their expertise, availability, and client reviews. Each stylist\'s profile includes their specialties and portfolio to help you make an informed choice.</li>
										<li><strong>Confirmation and Reminders</strong>
									Once your appointment is booked, you’ll receive a confirmation email or text, along with reminders as your appointment date approaches, so you never forget a scheduled visit.</li>
										<li><strong>Cancellation and Rescheduling Options</strong>
									Easily manage your appointments by canceling or rescheduling as needed, with a simple online process. We encourage you to do this at least 24 hours in advance to avoid cancellation fees.</li>
										<li><strong>Special Promotions and Packages</strong>
									Through the booking platform, you can access exclusive offers, discounts, and package deals to enhance your salon experience while saving money.</li>
										<li><strong>Secure Payment Options</strong>
									Enjoy the convenience of online payments through secure methods, including credit/debit cards and digital wallets, ensuring a hassle-free checkout process.</li>
										<li><strong>Customer Support</strong>
									Our dedicated customer support team can assist you with any booking inquiries, service questions, or technical issues you may encounter.</li>
										<li><strong>Feedback and Reviews</strong>
									After your appointment, you have the opportunity to leave feedback and reviews for your stylist and the services received, helping us maintain high standards and improve our offerings.</li>
									</ol>',
									'mpwpb_service_review_ratings' => '4.8',
									'mpwpb_service_rating_scale' => 'out of 5',
									'mpwpb_service_rating_text' => 'total 200 review',
									'_thumbnail_id' => '597',
								],
							],
							1 => [
								'name' => 'Car Wash',
								'post_data' => [
									// General_settings
									'mpwpb_shortcode_title' => 'Car Wash Service',
									'mpwpb_shortcode_sub_title' => 'Wash your car easily with affordable price',
									// Date_settings
									'mpwpb_template' => 'static.php',
									'mpwpb_service_start_date' => '2023-03-01',
									'mpwpb_time_slot_length' => '60',
									'mpwpb_capacity_per_session' => '1',
									'mpwpb_default_start_time' => '10',
									'mpwpb_default_end_time' => '16',
									'mpwpb_default_start_break_time' => '13',
									'mpwpb_default_end_break_time' => '15',
									'mpwpb_monday_start_time' => '',
									'mpwpb_monday_end_time' => '',
									'mpwpb_monday_start_break_time' => '',
									'mpwpb_monday_end_break_time' => '',
									'mpwpb_tuesday_start_time' => '',
									'mpwpb_tuesday_end_time' => '',
									'mpwpb_tuesday_start_break_time' => '',
									'mpwpb_tuesday_end_break_time' => '',
									'mpwpb_wednesday_start_time' => '',
									'mpwpb_wednesday_end_time' => '',
									'mpwpb_wednesday_start_break_time' => '',
									'mpwpb_wednesday_end_break_time' => '',
									'mpwpb_thursday_start_time' => '',
									'mpwpb_thursday_end_time' => '',
									'mpwpb_thursday_start_break_time' => '',
									'mpwpb_thursday_end_break_time' => '',
									'mpwpb_off_days' => 'saturday,sunday',
									'mpwpb_off_dates' => [],
									// Price_settings
									'mpwpb_service_details_active' => 'on',
									'mpwpb_service_duration_active' => 'on',
									'mpwpb_category_text' => 'Wash Type',
									'mpwpb_sub_category_text' => 'Car Type',
									'mpwpb_service_text' => 'Service',
									'mpwpb_category_service' => [
										[
											'name'  => 'Car Wash Polish',
											'icon'  => '',
											'image' => '',
										],
										[
											'name'  => 'Car Detailing',
											'icon'  => '',
											'image' => '',
										],
									],
									'mpwpb_sub_category_service'=>[
										[
											'name'  => 'Car Type SUV',
											'icon'  => 'fas fa-dog',
											'image' => '',
											'cat_id'=> 0,
										],
										[
											'name'  => 'Car Type Zeep',
											'icon'  => 'fas fa-dragon',
											'image' => '',
											'cat_id'=> 0,
										],
										[
											'name'  => 'Car Type Sedan',
											'icon'  => 'fas fa-truck-monster',
											'image' => '',
											'cat_id'=> 0,
										],
										[
											'name'  => 'Car Type Sedan',
											'icon'  => 'fas fa-otter',
											'image' => '',
											'cat_id'=> 1,
										]
									],
									'mpwpb_service'=>[
										[
											'name' => 'Hand Wash',
											'price' => 450,
											'details' => 'Lorem Ipsum is simply dummy text of the printing and typesetting industry.',
											'duration' => '1h 30m',
											'icon' => 'fas fa-frog',
											'image' => '',
											'show_cat_status' => 'on',
											'parent_cat' => 0,
											'sub_cat' => 0,
										],
										[
											'name' => 'Exterior Handwax',
											'price' => 200,
											'details' => 'Lorem Ipsum is simply dummy text of the printing and typesetting industry.',
											'duration' => '1 Hour',
											'icon' => 'fas fa-paw',
											'image' => '',
											'show_cat_status' => 'on',
											'parent_cat' => 0,
											'sub_cat' => 0,
										],
										[
											'name' => 'Hand Wash Wax',
											'price' => 650,
											'details' => 'Lorem Ipsum is simply dummy text of the printing and typesetting industry.',
											'duration' => '1h 30m',
											'icon' => 'fas fa-dragon',
											'image' => '',
											'show_cat_status' => 'on',
											'parent_cat' => 0,
											'sub_cat' => 0,
										],
										[
											'name' => 'Hand Wash',
											'price' => 450,
											'details' => 'Lorem Ipsum is simply dummy text of the printing and typesetting industry.',
											'duration' => '',
											'icon' => 'fas fa-spider',
											'image' => '',
											'show_cat_status' => 'on',
											'parent_cat' => 0,
											'sub_cat' => 1,
										],
										[
											'name' => 'Exterior Handwax',
											'price' => 600,
											'details' => 'Lorem Ipsum is simply dummy text of the printing and typesetting industry.',
											'duration' => '',
											'icon' => 'fas fa-crow',
											'image' => '',
											'show_cat_status' => 'on',
											'parent_cat' => 0,
											'sub_cat' => 1,
										],
										[
											'name' => 'Hand Wash Wax',
											'price' => 500,
											'details' => 'Lorem Ipsum is simply dummy text of the printing and typesetting industry.',
											'duration' => '',
											'icon' => 'fas fa-dog',
											'image' => '',
											'show_cat_status' => 'on',
											'parent_cat' => 0,
											'sub_cat' => 1,
										],
										[
											'name' => 'Hand Wash',
											'price' => 450,
											'details' => 'Lorem Ipsum is simply dummy text of the printing and typesetting industry.',
											'duration' => '',
											'icon' => 'fas fa-microphone-alt',
											'image' => '',
											'show_cat_status' => 'on',
											'parent_cat' => 0,
											'sub_cat' => 2,
										],
										[
											'name' => 'Exterior Handwax',
											'price' => 450,
											'details' => 'Lorem Ipsum is simply dummy text of the printing and typesetting industry.',
											'duration' => '',
											'icon' => 'fas fa-cocktail',
											'image' => '',
											'show_cat_status' => 'on',
											'parent_cat' => 0,
											'sub_cat' => 2,
										],
										[
											'name' => 'Hand Wash Wax',
											'price' => 500,
											'details' => 'Lorem Ipsum is simply dummy text of the printing and typesetting industry.',
											'duration' => '',
											'icon' => 'fas fa-tractor',
											'image' => '',
											'show_cat_status' => 'on',
											'parent_cat' => 0,
											'sub_cat' => 2,
										],
										[
											'name' => 'Standard Interior',
											'price' => '750',
											'details' => 'Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry\'s standard dummy text ever since the 1500s...',
											'duration' => '1h 30m',
											'icon' => 'fas fa-taxi',
											'image' => '',
											'show_cat_status' => 'on',
											'parent_cat' => 1,
											'sub_cat' => 0,
										],
										[
											'name' => 'Premium Interior',
											'price' => '600',
											'details' => 'Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry\'s standard dummy text ever since the 1500s...',
											'duration' => '1h 30m',
											'icon' => 'fas fa-church',
											'image' => '',
											'show_cat_status' => 'on',
											'parent_cat' => 1,
											'sub_cat' => 0,
										],
										[
											'name' => 'Complete Detail',
											'price' => '500',
											'details' => 'Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry\'s standard dummy text ever since the 1500s...',
											'duration' => '1h 30m',
											'icon' => 'fas fa-place-of-worship',
											'image' => '',
											'show_cat_status' => 'on',
											'parent_cat' => 1,
											'sub_cat' => 0,
										],
									],									
									'mpwpb_extra_service_active' => 'on',
                                    'mpwpb_service_multiple_category_check' => 'on',
                                    'mpwpb_multiple_service_select' => 'on',
									'mpwpb_group_extra_service_active' => 'off',
									'mpwpb_extra_service' => [
										[
											'name' => 'Tyre Pressure Checking',
											'qty' => 10,
											'price' => 10,
											'details' => 'A gentle but detailed hand wash procedure that keeps your car looking its best, longer.',
											'icon' => 'fas fa-tractor',
											'image' => ''
										],
										[
											'name' => 'Tyre Changing',
											'qty' => 5,
											'price' => 5,
											'details' => 'A gentle but detailed hand wash procedure that keeps your car looking its best, longer.',
											'icon' => 'fas fa-torii-gate',
											'image' => ''
										],
										[
											'name' => 'Odor Removal',
											'qty' => 10,
											'price' => 10,
											'details' => 'A gentle but detailed hand wash procedure that keeps your car looking its best, longer.',
											'icon' => 'fas fa-user-secret',
											'image' => ''
										]
									],
									// Gallery Settings
									'mpwpb_display_slider' => 'on',
									'mpwpb_slider_images' => [
										0 => ''
									],
									'mpwpb_faq_active' => 'on',
									'mpwpb_faq' => [
										[
											"title" => "What types of car wash services do you offer?",
											"content" => ""
										],
										[
											"title" => "Do I need an appointment for a car wash?",
											"content" => ""
										],
										[
											"title" => "How long does a car wash take?",
											"content" => ""
										],
										[
											"title" => "Do you offer mobile car wash services?",
											"content" => ""
										],
										[
											"title" => "What’s the difference between a regular wash and detailing?",
											"content" => ""
										]
									],
									'mpwpb_features_status' => 'on',
									'mpwpb_features' => [
										"Hand Wash Expertise",
										"Eco-Friendly Cleaning Products",
										"Mobile Car Wash Convenience",
										"Full Detailing Services"
									],
									'mpwpb_service_overview_status' => 'on',
									'mpwpb_service_overview_content' => '
										Our car wash service is dedicated to providing top-tier care for your vehicle, offering a wide range of cleaning options to suit all needs. Whether you\'re looking for a quick wash to maintain your car’s shine or comprehensive detailing to restore your vehicle’s interior and exterior, we have the expertise and equipment to deliver outstanding results.
										<strong>Services We Offer:</strong>
										<ol>
											<li><strong>Exterior Hand Wash:</strong>
										<ul>
											<li>A thorough hand wash using premium soaps that remove dirt, dust, and road grime without damaging the paint.</li>
											<li>We take special care of delicate finishes, ensuring a spotless exterior and leaving your car with a brilliant shine.</li>
										</ul>
										</li>
											<li><strong>Full-Service Car Wash (Interior &amp; Exterior):</strong>
										<ul>
											<li>Includes exterior hand wash plus an interior vacuum, wipe-down of surfaces, window cleaning, and tire dressing.</li>
											<li>It’s the perfect option for regular upkeep, leaving both the inside and outside of your vehicle clean and fresh.</li>
										</ul>
										</li>
											<li><strong>Detailing Services:</strong>
										<ul>
											<li><strong>Interior Detailing:</strong> A deep cleaning of the car’s cabin, including vacuuming, stain removal, conditioning of leather or vinyl surfaces, and deodorizing to leave the interior looking and smelling like new.</li>
											<li><strong>Exterior Detailing:</strong> Polishing, waxing, and clay bar treatments to restore your vehicle’s paint and remove imperfections such as scratches, swirl marks, and water spots.</li>
											<li><strong>Full Detailing:</strong> Combines both interior and exterior detailing for comprehensive vehicle care.</li>
										</ul>
										</li>
											<li><strong>Wax &amp; Polish:</strong>
										<ul>
											<li>A high-quality wax application to protect your paint from environmental factors and bring out the vehicle’s natural gloss.</li>
											<li>Polishing is available to remove minor scratches and restore the car’s original shine.</li>
										</ul>
										</li>
											<li><strong>Engine Cleaning:</strong>
										<ul>
											<li>Gentle, thorough cleaning of the engine bay to remove dust, oil, and grime, improving both appearance and performance.</li>
										</ul>
										</li>
											<li><strong>Tire &amp; Wheel Cleaning:</strong>
										<ul>
											<li>Deep cleaning of wheels and tires, including tire shine application for a polished look.</li>
										</ul>
										</li>
											<li><strong>Undercarriage Cleaning:</strong>
										<ul>
											<li>Essential for vehicles exposed to salt, mud, or other corrosive elements, our undercarriage cleaning helps protect your vehicle from long-term damage.</li>
										</ul>
										</li>
											<li><strong>Mobile Car Wash Services:</strong>
										<ul>
											<li>We bring our car wash services to your location! Whether at home or the office, enjoy the convenience of a professional clean without the hassle of travel.</li>
										</ul>
										</li>
										</ol>
									',
									'mpwpb_service_details_status' => 'on',
									'mpwpb_service_details_content' => '
									<strong>Why Choose Us?</strong>
									<ul>
										<li><strong>Eco-Friendly Products:</strong> We use biodegradable, non-toxic cleaning agents to ensure the safety of your vehicle and the environment.</li>
										<li><strong>Experienced Staff:</strong> Our skilled technicians are trained to handle all types of vehicles with care and precision.</li>
										<li><strong>State-of-the-Art Equipment:</strong> We use modern tools and techniques to provide an efficient and thorough cleaning experience.</li>
										<li><strong>Convenient Options:</strong> Choose from our in-shop service or mobile car wash options. We also offer monthly memberships for regular maintenance.</li>
										<li><strong>Customer Satisfaction:</strong> We are committed to delivering exceptional results and ensuring that every customer leaves with a car that looks and feels brand new.</li>
									</ul>
									',
									'mpwpb_service_review_ratings' => '4.5',
									'mpwpb_service_rating_scale' => 'out of 5',
									'mpwpb_service_rating_text' => '2888 total customer reviews',
									'_thumbnail_id' => '1431',
								],
							],
							2 => [
								'name' => 'Repair service Booking Online',
								'post_data' => [
									// General_settings
									'mpwpb_shortcode_title' => 'Vehicle Repair Service',
									'mpwpb_shortcode_sub_title' => 'Repair your vehicle easily with effordable price',
									// Date_settings
									'mpwpb_template' => 'static.php',
									'mpwpb_service_start_date' => '2023-02-01',
									'mpwpb_time_slot_length' => '60',
									'mpwpb_capacity_per_session' => '1',
									'mpwpb_default_start_time' => '10',
									'mpwpb_default_end_time' => '18',
									'mpwpb_default_start_break_time' => '13',
									'mpwpb_default_end_break_time' => '15',
									'mpwpb_monday_start_time' => '',
									'mpwpb_monday_end_time' => '',
									'mpwpb_monday_start_break_time' => '',
									'mpwpb_monday_end_break_time' => '',
									'mpwpb_tuesday_start_time' => '',
									'mpwpb_tuesday_end_time' => '',
									'mpwpb_tuesday_start_break_time' => '',
									'mpwpb_tuesday_end_break_time' => '',
									'mpwpb_wednesday_start_time' => '',
									'mpwpb_wednesday_end_time' => '',
									'mpwpb_wednesday_start_break_time' => '',
									'mpwpb_wednesday_end_break_time' => '',
									'mpwpb_thursday_start_time' => '',
									'mpwpb_thursday_end_time' => '',
									'mpwpb_thursday_start_break_time' => '',
									'mpwpb_thursday_end_break_time' => '',
									'mpwpb_off_days' => 'saturday,sunday',
									'mpwpb_off_dates' => [],
									// Price_settings
									'mpwpb_service_details_active' => 'on',
									'mpwpb_service_duration_active' => 'on',
									'mpwpb_category_text' => 'Service Type',
									'mpwpb_sub_category_text' => 'Sub-Category',
									'mpwpb_service_text' => 'Service Details',
									'mpwpb_category_service' => [
										[
											'name'  => 'Car Maintenance',
											'icon'  => '',
											'image' => '',
										],
										[
											'name'  => 'Car Repair',
											'icon'  => '',
											'image' => '',
										],
									],
									'mpwpb_sub_category_service'=>[],
									'mpwpb_service'=>[
										[
											'name' => 'Auto Maintenance Services​',
											'price' => '500',
											'details' => 'Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry\'s standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book',
											'duration' => '1 hour',
											'icon' => 'fas fa-air-freshener',
											'image' => '',
											'show_cat_status' => 'on',
											'parent_cat' => 0,
											'sub_cat' => '',
										],
										[
											'name' => 'Oil Filter Change',
											'price' => '300',
											'details' => 'Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry\'s standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book',
											'duration' => '1 hour',
											'icon' => 'fas fa-tape',
											'image' => '',
											'show_cat_status' => 'on',
											'parent_cat' => 0,
											'sub_cat' => '',
										],
										[
											'name' => 'Cabin Air Filter Replacement',
											'price' => '200',
											'details' => 'Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry\'s standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book',
											'duration' => '1 hour',
											'icon' => 'fas fa-ship',
											'image' => '',
											'show_cat_status' => 'on',
											'parent_cat' => 0,
											'sub_cat' => '',
										],
										[
											'name' => 'Engine Performance',
											'price' => '300',
											'details' => 'Lorem Ipsum is simply dummy text of the printing and typesetting industry.',
											'duration' => '30m',
											'icon' => 'fas fa-truck-monster',
											'image' => '',
											'show_cat_status' => 'on',
											'parent_cat' => 0,
											'sub_cat' => '',
										],
										[
											'name' => 'Brake Repair Pads Rotors',
											'price' => '200',
											'details' => 'Lorem Ipsum is simply dummy text of the printing and typesetting industry.',
											'duration' => '30m',
											'icon' => '',
											'image' => '',
											'show_cat_status' => 'on',
											'parent_cat' => 1,
											'sub_cat' => '',
										],
										[
											'name' => 'Air Conditioning Services​​',
											'price' => '100',
											'details' => 'Lorem Ipsum is simply dummy text of the printing and typesetting industry.',
											'duration' => '30m',
											'icon' => '',
											'image' => '',
											'show_cat_status' => 'on',
											'parent_cat' => 1,
											'sub_cat' => '',
										],
										[
											'name' => 'Body Repair Painting',
											'price' => '30',
											'details' => 'Lorem Ipsum is simply dummy text of the printing and typesetting industry.',
											'duration' => '30m',
											'icon' => '',
											'image' => '',
											'show_cat_status' => 'on',
											'parent_cat' => 1,
											'sub_cat' => '',
										]
									],									
									'mpwpb_extra_service_active' => 'on',
                                    'mpwpb_service_multiple_category_check' => 'on',
                                    'mpwpb_multiple_service_select' => 'on',
									'mpwpb_group_extra_service_active' => 'off',
									'mpwpb_extra_service' => [
										[
											'name' => 'Driver Seating Chair',
											'qty' => '200',
											'price' => '200',
											'details' => 'Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry\'s standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book',
											'icon' => 'fas fa-ship',
											'image' => ''
										],
										[
											'name' => 'Lunch box for driver',
											'qty' => '300',
											'price' => '300',
											'details' => 'Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry\'s standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book',
											'icon' => 'fas fa-hamburger',
											'image' => ''
										]
									],
									// Gallery Settings
									'mpwpb_display_slider' => 'on',
									'mpwpb_slider_images' => [
										0 => ''
									],
									'mpwpb_faq_active' => 'on',
									'mpwpb_faq' => [
										[
											'title' => 'What types of repair services can I book?',
											'content' => '<p>We offer a wide range of repair services, including appliance repairs, electronics troubleshooting, plumbing fixes, HVAC servicing, and automotive repairs. You can select the specific service you need during the booking process.</p>'
										],
										[
											'title' => 'How do I book a repair service?',
											'content' => '<p>Booking a repair service is simple! Just visit our website, select the type of service you need, choose your preferred date and time, and provide any relevant details about the issue.</p>'
										],
										[
											'title' => 'Is there a fee for booking an appointment?',
											'content' => '<p>There is no fee for booking an appointment. You will only be charged for the service provided once the repair is completed.</p>'
										],
										[
											'title' => 'Can I reschedule or cancel my appointment?',
											'content' => '<p>Yes, you can reschedule or cancel your appointment through our online booking system. Please do so at least 24 hours in advance to avoid any cancellation fees.</p>'
										]
									],
									'mpwpb_features_status' => 'on',
									'mpwpb_features' => [
										'Wide Range of Courses',
										'General Health Checkups',
										'What types of repair services can I book?',
										'General Health Checkups'
									],
									'mpwpb_service_overview_status' => 'on',
									'mpwpb_service_overview_content' => 'Experience hassle-free repair services with our <strong>Repair Service Booking</strong> system, designed to cater to your repair needs quickly and efficiently. Whether you’re dealing with a malfunctioning appliance, electronics, plumbing issues, or vehicle repairs, our user-friendly platform makes scheduling a repair simple and convenient.',
									'mpwpb_service_details_status' => 'on',
									'mpwpb_service_details_content' => '<ol>
											<li><strong>Select Service</strong>: Choose the type of repair service you need.</li>
											<li><strong>Schedule Appointment</strong>: Pick a date and time that works for you.</li>
											<li><strong>Provide Details</strong>: Share relevant information about the repair issue.</li>
											<li><strong>Confirmation</strong>: Receive a confirmation of your appointment via email or text.</li>
											<li><strong>Service Completion</strong>: Our technician will arrive at your scheduled time to complete the repair.</li>
										</ol>
										With our <strong>Repair Service Booking</strong>, getting the help you need is easier than ever. Say goodbye to stress and hello to quick, reliable repairs!',
									'mpwpb_service_review_ratings' => '4.5',
									'mpwpb_service_rating_scale' => 'out of 5',
									'mpwpb_service_rating_text' => '2888 total customer reviews',
									'_thumbnail_id' => '261',
								],
							],
							3 => [
								'name' => 'Music Learning Online',
								'post_data' => [
									// General_settings
									'mpwpb_shortcode_title' => 'Musical Service',
									'mpwpb_shortcode_sub_title' => 'Find your musical instructor easily with affordable price.',
									// Date_settings
									'mpwpb_template' => 'static.php',
									'mpwpb_service_start_date' => '2023-02-01',
									'mpwpb_time_slot_length' => '60',
									'mpwpb_capacity_per_session' => '1',
									'mpwpb_default_start_time' => '10',
									'mpwpb_default_end_time' => '18',
									'mpwpb_default_start_break_time' => '13',
									'mpwpb_default_end_break_time' => '15',
									'mpwpb_monday_start_time' => '',
									'mpwpb_monday_end_time' => '',
									'mpwpb_monday_start_break_time' => '',
									'mpwpb_monday_end_break_time' => '',
									'mpwpb_tuesday_start_time' => '',
									'mpwpb_tuesday_end_time' => '',
									'mpwpb_tuesday_start_break_time' => '',
									'mpwpb_tuesday_end_break_time' => '',
									'mpwpb_wednesday_start_time' => '',
									'mpwpb_wednesday_end_time' => '',
									'mpwpb_wednesday_start_break_time' => '',
									'mpwpb_wednesday_end_break_time' => '',
									'mpwpb_thursday_start_time' => '',
									'mpwpb_thursday_end_time' => '',
									'mpwpb_thursday_start_break_time' => '',
									'mpwpb_thursday_end_break_time' => '',
									'mpwpb_off_days' => 'saturday,sunday',
									'mpwpb_off_dates' => [],
									// Price_settings
									'mpwpb_service_details_active' => 'on',
									'mpwpb_service_duration_active' => 'on',
									'mpwpb_category_text' => 'Category',
									'mpwpb_sub_category_text' => 'Sub-Category',
									'mpwpb_service_text' => 'Service',
									'mpwpb_category_service' => [],
									'mpwpb_sub_category_service'=>[],
									'mpwpb_service'=>[
										[
											'name' => 'Classical Class (3 Months)',
											'price' => '10',
											'details' => 'derived from the Latin word classics, which originally referred to the highest class of Ancient Roman citizens',
											'duration' => '30m',
											'icon' => '',
											'image' => '',
											'show_cat_status' => 'off',
											'parent_cat' => '',
											'sub_cat' => '',
										],
										[
											'name' => 'Jazz Classes (2 Months)',
											'price' => '15',
											'details' => 'Classical music, strictly defined, means music produced in the Western',
											'duration' => '30m',
											'icon' => '',
											'image' => '',
											'show_cat_status' => 'off',
											'parent_cat' => '',
											'sub_cat' => '',
										],
										[
											'name' => 'Classical Private Tutor',
											'price' => '20',
											'details' => 'derived from the Latin word classics, which originally referred to the highest class of Ancient Roman citizens',
											'duration' => '30m',
											'icon' => '',
											'image' => '',
											'show_cat_status' => 'off',
											'parent_cat' => '',
											'sub_cat' => '',
										],
										[
											'name' => 'Pop Songs Classes',
											'price' => '30',
											'details' => 'Classical music, strictly defined, means music produced in the Western',
											'duration' => '30m',
											'icon' => '',
											'image' => '',
											'show_cat_status' => 'off',
											'parent_cat' => '',
											'sub_cat' => '',
										],
										[
											'name' => 'Rock Music Piano Class',
											'price' => '40',
											'details' => 'derived from the Latin word classics, which originally referred to the highest class of Ancient Roman citizens',
											'duration' => '30m',
											'icon' => '',
											'image' => '',
											'show_cat_status' => 'off',
											'parent_cat' => '',
											'sub_cat' => '',
										],
										[
											'name' => 'Classical Advance Class',
											'price' => '78',
											'details' => 'Classical music, strictly defined, means music produced in the Western',
											'duration' => '30m',
											'icon' => '',
											'image' => '',
											'show_cat_status' => 'off',
											'parent_cat' => '',
											'sub_cat' => '',
										],
										[
											'name' => 'Classical Private tutor',
											'price' => '10',
											'details' => 'derived from the Latin word classics, which originally referred to the highest class of Ancient Roman citizens',
											'duration' => '30m',
											'icon' => '',
											'image' => '',
											'show_cat_status' => 'off',
											'parent_cat' => '',
											'sub_cat' => '',
										],
										[
											'name' => 'Pop Songs Advance (Annually)',
											'price' => '10',
											'details' => 'Classical music, strictly defined, means music produced in the Western',
											'duration' => '30m',
											'icon' => '',
											'image' => '',
											'show_cat_status' => 'off',
											'parent_cat' => '',
											'sub_cat' => '',
										],
										[
											'name' => 'Rock Music Piano Advance',
											'price' => '45',
											'details' => 'Classical music, strictly defined, means music produced in the Western',
											'duration' => '30m',
											'icon' => '',
											'image' => '',
											'show_cat_status' => 'off',
											'parent_cat' => '',
											'sub_cat' => '',
										],
									],									
									'mpwpb_extra_service_active' => 'off',
                                    'mpwpb_service_multiple_category_check' => 'on',
                                    'mpwpb_multiple_service_select' => 'on',
									'mpwpb_group_extra_service_active' => 'off',
									'mpwpb_extra_service' => [],
									// Gallery Settings
									'mpwpb_display_slider' => 'on',
									'mpwpb_slider_images' => [
										0 => ''
									],
									'mpwpb_faq_active' => 'on',
									'mpwpb_faq' => [
										[
											'title' => 'What instruments can I learn on the platform?',
											'content' => '<p>We offer a variety of instrument courses, including piano, guitar, drums, violin, flute, saxophone, and more. You can also learn music theory, composition, vocal training, and music production.</p>'
										],
										[
											'title' => 'Is Music Learning Online suitable for beginners?',
											'content' => '<p>Absolutely! We have beginner-friendly courses for each instrument, as well as lessons for intermediate and advanced learners. You can start with the basics and progress at your own pace.</p>'
										],
										[
											'title' => 'Do I need any prior musical knowledge to join?',
											'content' => '<p>No prior experience is necessary. Our courses are designed for learners at every skill level, with step-by-step instructions to help you build your skills from the ground up.</p>'
										],
										[
											'title' => 'How do I access the lessons?',
											'content' => '<p>All lessons are available online, so you can access them anytime from your computer, tablet, or smartphone. There are no fixed schedules, allowing you to learn at your convenience.</p>'
										]
									],
									'mpwpb_features_status' => 'on',
									'mpwpb_features' => [
										'Wide Range of Courses',
										'Expert Instructors',
										'Flexible, Self-Paced Learning',
										'Interactive Tools',
										'Progress Tracking'
									],
									'mpwpb_service_overview_status' => 'on',
									'mpwpb_service_overview_content' => '<strong>Music Learning Online</strong> is a dynamic and user-friendly platform designed to help learners of all levels develop their musical skills from anywhere in the world. Whether you’re a beginner eager to pick up an instrument or a seasoned musician looking to refine your abilities, our online courses cater to every type of student.',
									'mpwpb_service_details_status' => 'on',
									'mpwpb_service_details_content' => 'Discover the joy of learning music from the comfort of your home with <strong>Music Learning Online</strong>. Our platform offers a comprehensive, flexible, and interactive way to master your favorite instrument, improve your vocals, or explore music theory—all at your own pace.
										<h3>Key Features:</h3>
										<ul>
											<li><strong>Wide Range of Courses</strong>: Learn various instruments, including guitar, piano, drums, violin, and more. Plus, access courses on music theory, songwriting, music production, and vocal training.</li>
											<li><strong>Expert Instructors</strong>: Receive instruction from experienced, professional musicians who guide you through step-by-step lessons and personalized feedback.</li>
											<li><strong>Flexible Learning</strong>: Study at your own pace, with lessons available 24/7. Whether you\'re a beginner or looking to advance your skills, our platform adapts to your schedule and skill level.</li>
											<li><strong>Interactive Tools</strong>: Practice with interactive exercises, video tutorials, sheet music, and backing tracks to enhance your learning experience.</li>
											<li><strong>Community Support</strong>: Join a global community of music learners, where you can share progress, ask questions, and collaborate with fellow musicians.</li>
											<li><strong>Progress Tracking</strong>: Monitor your growth with structured lessons, quizzes, and performance tracking to stay motivated and on course.</li>
										</ul>
										With <strong>Music Learning Online</strong>, you can cultivate your musical talents and achieve your goals, whether you\'re looking to play for fun, perform professionally, or simply expand your creative horizons.',
									'mpwpb_service_review_ratings' => '4.5',
									'mpwpb_service_rating_scale' => 'out of 5',
									'mpwpb_service_rating_text' => '2888 total customer reviews',
									'_thumbnail_id' => '261',
								],
							],
							4 => [
								'name' => 'Medical & Dental',
								'post_data' => [
									// General_settings
									'mpwpb_shortcode_title' => 'Medical & Dental Service',
									'mpwpb_shortcode_sub_title' => 'Choose your medical and dental services easily with affordable price.',
									// Date_settings
									'mpwpb_template' => 'static.php',
									'mpwpb_service_start_date' => '2023-02-01',
									'mpwpb_time_slot_length' => '60',
									'mpwpb_capacity_per_session' => '1',
									'mpwpb_default_start_time' => '10',
									'mpwpb_default_end_time' => '18',
									'mpwpb_default_start_break_time' => '13',
									'mpwpb_default_end_break_time' => '15',
									'mpwpb_monday_start_time' => '',
									'mpwpb_monday_end_time' => '',
									'mpwpb_monday_start_break_time' => '',
									'mpwpb_monday_end_break_time' => '',
									'mpwpb_tuesday_start_time' => '',
									'mpwpb_tuesday_end_time' => '',
									'mpwpb_tuesday_start_break_time' => '',
									'mpwpb_tuesday_end_break_time' => '',
									'mpwpb_wednesday_start_time' => '',
									'mpwpb_wednesday_end_time' => '',
									'mpwpb_wednesday_start_break_time' => '',
									'mpwpb_wednesday_end_break_time' => '',
									'mpwpb_thursday_start_time' => '',
									'mpwpb_thursday_end_time' => '',
									'mpwpb_thursday_start_break_time' => '',
									'mpwpb_thursday_end_break_time' => '',
									'mpwpb_off_days' => 'saturday,sunday',
									'mpwpb_off_dates' => [],
									// Price_settings
									'mpwpb_service_details_active' => 'on',
									'mpwpb_service_duration_active' => 'on',
									'mpwpb_category_text' => 'Category',
									'mpwpb_sub_category_text' => 'Sub-Category',
									'mpwpb_service_text' => 'Service',
									'mpwpb_category_service' => [],
									'mpwpb_sub_category_service'=>[],
									'mpwpb_service'=>[
										[
											'name' => 'Fever​',
											'price' => '10',
											'details' => 'Nisl tempus, sollicitudin amet, porttitor erat magna congue dui malesuada vestibulum.',
											'duration' => '30m',
											'icon' => '',
											'image' => '',
											'show_cat_status' => 'off',
											'parent_cat' =>'',
											'sub_cat' => '',
										],
										[
											'name' => 'Dry Cough​',
											'price' => '30',
											'details' => 'Nisl tempus, metus, sollicitudin amet, porttitor erat magna congue dui malesuada vestibulum.',
											'duration' => '30m',
											'icon' => '',
											'image' => '',
											'show_cat_status' => 'off',
											'parent_cat' =>'',
											'sub_cat' => '',
										],
										[
											'name' => 'Shortness of Breath​',
											'price' => '10',
											'details' => 'Nisl tempus, metus, sollicitudin amet, porttitor erat magna congue dui malesuada vestibulum.',
											'duration' => '30m',
											'icon' => '',
											'image' => '',
											'show_cat_status' => 'off',
											'parent_cat' =>'',
											'sub_cat' => '',
										],
										[
											'name' => 'Aches and Pains​',
											'price' => '20',
											'details' => 'Nisl tempus, metus, sollicitudin amet, porttitor erat magna congue dui malesuada vestibulum.',
											'duration' => '30m',
											'icon' => '',
											'image' => '',
											'show_cat_status' => 'off',
											'parent_cat' =>'',
											'sub_cat' => '',
										],
										[
											'name' => 'Sore Throat​',
											'price' => '30',
											'details' => 'Nisl tempus, metus, sollicitudin amet, porttitor erat magna congue dui malesuada vestibulum.',
											'duration' => '30m',
											'icon' => '',
											'image' => '',
											'show_cat_status' => 'off',
											'parent_cat' =>'',
											'sub_cat' => '',
										],
										[
											'name' => 'Sexual Disease',
											'price' => '30',
											'details' => 'Nisl tempus, metus, sollicitudin amet, porttitor erat magna congue dui malesuada vestibulum.',
											'duration' => '30m',
											'icon' => '',
											'image' => '',
											'show_cat_status' => 'off',
											'parent_cat' =>'',
											'sub_cat' => '',
										],
										[
											'name' => 'Sleep Apnea',
											'price' => '50',
											'details' => 'Nisl tempus, metus, sollicitudin amet, porttitor erat magna congue dui malesuada vestibulum.',
											'duration' => '60m',
											'icon' => '',
											'image' => '',
											'show_cat_status' => 'off',
											'parent_cat' =>'',
											'sub_cat' => '',
										],
									],
									'mpwpb_extra_service_active' => 'off',
                                    'mpwpb_service_multiple_category_check' => 'on',
                                    'mpwpb_multiple_service_select' => 'on',
									'mpwpb_group_extra_service_active' => 'off',
									'mpwpb_extra_service' => [],
									// Gallery Settings
									'mpwpb_display_slider' => 'on',
									'mpwpb_slider_images' => [
										0 => ''
									],
									'mpwpb_faq_active' => 'on',
									'mpwpb_faq' => [
										0 => [
											'title' => 'What types of medical services are available?',
											'content' => '<p>Medical services include primary care, emergency services, specialty care, preventive care, diagnostic services, surgical services, rehabilitation, mental health services, pediatric care, geriatric care, home healthcare, pharmacy services, and nutritional counseling.</p>'
										],
										1 => [
											'title' => 'How do I choose a primary care provider?',
											'content' => '<p>When selecting a primary care provider, consider factors such as location, insurance coverage, provider specialties, availability, and personal recommendations. Many facilities also offer online directories to help you find a suitable provider.</p>'
										],
										2 => [
											'title' => 'What should I expect during a primary care visit?',
											'content' => '<p>During a primary care visit, you will typically undergo a health assessment, discuss any health concerns or symptoms, receive preventive care (such as vaccinations), and may be referred to specialists if necessary.</p>'
										],
										3 => [
											'title' => 'How do I know if I need to go to the emergency room?',
											'content' => '<p>You should go to the emergency room for severe or life-threatening conditions, such as difficulty breathing, chest pain, severe bleeding, head injuries, or signs of stroke. If unsure, consider calling your healthcare provider or a telehealth service for guidance.</p>'
										]
									],
									'mpwpb_features_status' => 'on',
									'mpwpb_features' => [
										'Wide Range of Courses',
										'Expert Instructors',
										'Flexible, Self-Paced Learning',
										'Interactive Tools',
										'Progress Tracking'
									],
									'mpwpb_service_overview_status' => 'on',
									'mpwpb_service_overview_content' => '<ul>
										<li><strong>Comprehensive Care</strong>: Medical services cover a broad spectrum of health needs, ensuring holistic care for patients.</li>
										<li><strong>Access to Expertise</strong>: Patients receive specialized care from qualified professionals, improving health outcomes.</li>
										<li><strong>Preventive Focus</strong>: Emphasis on prevention helps reduce the risk of diseases and promotes healthier lifestyles.</li>
										<li><strong>Convenience</strong>: Services are offered in various settings, making it easier for patients to access the care they need.</li>
									</ul>
									Overall, medical services are essential for maintaining health, preventing illness, and providing treatment and support for various medical conditions, ensuring that individuals receive comprehensive and quality healthcare throughout their lives.',
									'mpwpb_service_details_status' => 'on',
									'mpwpb_service_details_content' => '<ul>
										<li><strong>Comprehensive Care</strong>: Medical services cover a broad spectrum of health needs, ensuring holistic care for patients.</li>
										<li><strong>Access to Expertise</strong>: Patients receive specialized care from qualified professionals, improving health outcomes.</li>
										<li><strong>Preventive Focus</strong>: Emphasis on prevention helps reduce the risk of diseases and promotes healthier lifestyles.</li>
										<li><strong>Convenience</strong>: Services are offered in various settings, making it easier for patients to access the care they need.</li>
									</ul>
									Overall, medical services are essential for maintaining health, preventing illness, and providing treatment and support for various medical conditions, ensuring that individuals receive comprehensive and quality healthcare throughout their lives.',
									'mpwpb_service_review_ratings' => '4.5',
									'mpwpb_service_rating_scale' => 'out of 5',
									'mpwpb_service_rating_text' => '2888 total customer reviews',
									'_thumbnail_id' => '1450',
								],
							],
							5 => [
								'name' => 'Rent Your Dream Car for Single Day long tour',
								'post_data' => [
									// General_settings
									'mpwpb_shortcode_title' => 'Rent-A-Car Service',
									'mpwpb_shortcode_sub_title' => 'Rent your dream car easily with affordable price',
									// Date_settings
									'mpwpb_template' => 'static.php',
									'mpwpb_service_start_date' => '2023-02-01',
									'mpwpb_time_slot_length' => '60',
									'mpwpb_capacity_per_session' => '1',
									'mpwpb_default_start_time' => '10',
									'mpwpb_default_end_time' => '18',
									'mpwpb_default_start_break_time' => '13',
									'mpwpb_default_end_break_time' => '15',
									'mpwpb_monday_start_time' => '',
									'mpwpb_monday_end_time' => '',
									'mpwpb_monday_start_break_time' => '',
									'mpwpb_monday_end_break_time' => '',
									'mpwpb_tuesday_start_time' => '',
									'mpwpb_tuesday_end_time' => '',
									'mpwpb_tuesday_start_break_time' => '',
									'mpwpb_tuesday_end_break_time' => '',
									'mpwpb_wednesday_start_time' => '',
									'mpwpb_wednesday_end_time' => '',
									'mpwpb_wednesday_start_break_time' => '',
									'mpwpb_wednesday_end_break_time' => '',
									'mpwpb_thursday_start_time' => '',
									'mpwpb_thursday_end_time' => '',
									'mpwpb_thursday_start_break_time' => '',
									'mpwpb_thursday_end_break_time' => '',
									'mpwpb_off_days' => 'saturday,sunday',
									'mpwpb_off_dates' => [],
									// Price_settings
									'mpwpb_service_details_active' => 'on',
									'mpwpb_service_duration_active' => 'on',
									'mpwpb_category_text' => 'Car Type',
									'mpwpb_sub_category_text' => 'Sub-Category',
									'mpwpb_service_text' => 'Service',
									'mpwpb_category_service' => [
										[
											'name'  => 'Economy Car',
											'icon'  => '',
											'image' => '',
										],
										[
											'name'  => 'Standard Car',
											'icon'  => '',
											'image' => '',
										],
										[
											'name'  => 'SUV Car',
											'icon'  => '',
											'image' => '',
										],
									],
									'mpwpb_sub_category_service'=>[],
									'mpwpb_service'=>[
										[
											'name' => 'Casinos',
											'price' => '10',
											'details' => '',
											'duration' => '30m',
											'icon' => '',
											'image' => '',
											'show_cat_status' => 'on',
											'parent_cat' => 0,
											'sub_cat' => '',
										],
										[
											'name' => 'Birthdays',
											'price' => '20',
											'details' => '',
											'duration' => '30m',
											'icon' => '',
											'image' => '',
											'show_cat_status' => 'on',
											'parent_cat' => 0,
											'sub_cat' => '',
										],
										[
											'name' => 'Airport Transfer',
											'price' => '20',
											'details' => '',
											'duration' => '30m',
											'icon' => '',
											'image' => '',
											'show_cat_status' => 'on',
											'parent_cat' => 0,
											'sub_cat' => '',
										],
										[
											'name' => 'Weddings',
											'price' => '30',
											'details' => '',
											'duration' => '30m',
											'icon' => '',
											'image' => '',
											'show_cat_status' => 'on',
											'parent_cat' => 1,
											'sub_cat' => '',
										],
										[
											'name' => 'Night Parties Long Drive',
											'price' => '30',
											'details' => '',
											'duration' => '30m',
											'icon' => '',
											'image' => '',
											'show_cat_status' => 'on',
											'parent_cat' => 2,
											'sub_cat' => '',
										]
									],
									'mpwpb_extra_service_active' => 'off',
                                    'mpwpb_service_multiple_category_check' => 'on',
                                    'mpwpb_multiple_service_select' => 'on',
									'mpwpb_group_extra_service_active' => 'off',
									'mpwpb_extra_service' => [],
									// Gallery Settings
									'mpwpb_display_slider' => 'on',
									'mpwpb_slider_images' => [
										0 => ''
									],
									'mpwpb_faq_active' => 'on',
									'mpwpb_faq' => [
										0 => [
											'title' => 'What types of cars are available for rent?',
											'content' => '<p>We offer a wide range of luxury vehicles, including high-performance sports cars, sleek sedans, and premium SUVs. Our fleet features top brands like Ferrari, Lamborghini, Porsche, Mercedes, BMW, and more.</p>'
										],
										1 => [
											'title' => 'How long can I rent a car for a day tour?',
											'content' => '<p>Our day tour rentals are typically available for a 24-hour period. However, we can accommodate shorter or longer rentals depending on your needs—just contact us for details.</p>'
										],
										2 => [
											'title' => 'Do I need a special license to rent a luxury car?',
											'content' => '<p>No, a standard driver\'s license is typically sufficient. However, drivers must meet age requirements (usually 25+), have a clean driving record, and present valid identification.</p>'
										],
										3 => [
											'title' => 'Is insurance included in the rental?',
											'content' => '<p>Basic insurance is included with all rentals, but we highly recommend upgrading to full coverage for added protection during your tour.</p>'
										],
										4 => [
											'title' => 'Can I choose my own route and destinations?',
											'content' => '<p>Yes! Our rentals offer complete flexibility for you to create your own itinerary. Explore scenic routes, city sights, or countryside escapes at your own pace.</p>'
										]
									],
									'mpwpb_features_status' => 'on',
									'mpwpb_features' => [
										"Exclusive Luxury Fleet",
										"Flexible Itinerary",
										"24-Hour Rental Period",
										"Hassle-Free Booking",
										"Insurance and Safety Coverage"
									],
									'mpwpb_service_overview_status' => 'on',
									'mpwpb_service_overview_content' => 'Transform your day trip into an extraordinary adventure with our exclusive <strong>Rent Your Dream Car for Day Tour</strong> service. Whether you want to indulge in a luxurious drive along scenic coastal roads, explore a bustling city in style, or enjoy a smooth countryside cruise, our premium car rental service is designed to provide an unparalleled experience.
									<h3>Key Features:</h3>
									<ul>
										<li><strong>Luxury Fleet</strong>: Choose from an array of high-end vehicles, including sports cars, executive sedans, and spacious SUVs from world-renowned brands like Ferrari, Lamborghini, Porsche, Mercedes, BMW, and more.</li>
										<li><strong>Flexible Itineraries</strong>: Enjoy complete freedom to create your own route. Whether it’s for a special occasion, a romantic getaway, or simply for fun, you can take the wheel and explore at your own pace.</li>
										<li><strong>24-Hour Rentals</strong>: Our day tour package typically includes 24-hour rental periods. This allows you to maximize your experience, from sunrise to sunset, or even into the evening.</li>
										<li><strong>Seamless Booking</strong>: Our user-friendly booking process allows you to easily select your vehicle, choose your desired dates, and confirm your rental in just a few steps. Book online or contact our team for personalized service.</li>
										<li><strong>Insurance and Protection</strong>: We provide basic insurance with every rental and offer optional premium coverage for added peace of mind.</li>
									</ul>',
									'mpwpb_service_details_status' => 'on',
									'mpwpb_service_details_content' => 'Our <strong>Rent Your Dream Car for Day Tour</strong> service is perfect for anyone looking to elevate their travel experience. Whether it\'s your first time behind the wheel of a luxury vehicle or you\'re a seasoned car enthusiast, we provide a unique opportunity to enjoy a world-class driving experience. With our top-tier service, impeccable fleet, and commitment to customer satisfaction, we aim to make your dream car tour truly unforgettable.',
									'mpwpb_service_review_ratings' => '4.5',
									'mpwpb_service_rating_scale' => 'out of 5',
									'mpwpb_service_rating_text' => '2888 total customer reviews',
									'_thumbnail_id' => '1450',
								],
							]
						]
					]
				];
			}
			public static function insert_media($file_path) {
				$attachment = self::does_attachment_exist(basename($file_path));
				if (empty($attachment)) {
					// Load necessary WordPress files
					require_once(ABSPATH . 'wp-admin/includes/file.php');
					require_once(ABSPATH . 'wp-admin/includes/media.php');
					require_once(ABSPATH . 'wp-admin/includes/image.php');
					// Prepare the file
					$file = array(
						'name' => basename($file_path),
						'type' => mime_content_type($file_path),
						'tmp_name' => $file_path,
						'error' => 0,
						'size' => filesize($file_path),
					);
					// Handle file upload
					$upload = wp_handle_sideload($file, array('test_form' => false));
					if (isset($upload['error'])) {
						return 'File upload failed: ' . $upload['error'];
					}
					// Add the file to the media library
					$attachment = array(
						'post_mime_type' => $upload['type'],
						'post_title' => sanitize_file_name(pathinfo($file_path, PATHINFO_FILENAME)),
						'post_content' => '',
						'post_status' => 'inherit',
					);
					$attachment_id = wp_insert_attachment($attachment, $upload['file']);
					if (is_wp_error($attachment_id)) {
						return 'Attachment insert failed: ' . $attachment_id->get_error_message();
					}
					// Generate metadata and update attachment
					$attachment_data = wp_generate_attachment_metadata($attachment_id, $upload['file']);
					wp_update_attachment_metadata($attachment_id, $attachment_data);
					$attachment = array(
						'id' => $attachment_id,
						'url' => wp_get_attachment_url($attachment_id),
					);
					return $attachment;
				}
				return $attachment;
			}
			public static function does_attachment_exist($filename) {
				global $wpdb;

				// Properly sanitize the filename
				$filename = sanitize_file_name($filename);
				$like_filename = '%' . $wpdb->esc_like($filename) . '%';

				// Prepare the SQL query using placeholders
				// Prepare search arguments
				$args = [
					'post_type'      => 'attachment',
					'posts_per_page' => 1, // We only need one result
					'fields'         => 'ids', // Get only post IDs
					'meta_query'     => [
						'relation' => 'OR',
						[
							'key'     => '_wp_attached_file',
							'value'   => $like_filename,
							'compare' => 'LIKE',
						],
					],
					's' => $like_filename, // Search in GUID (not ideal, but works)
				];

				// Try to fetch cached result first
				$cache_key = 'attachment_id_' . md5(json_encode($args));
				$attachment_id = wp_cache_get($cache_key, 'custom_cache_group');

				if ($attachment_id === false) {
					$attachments 	= get_posts($args);
					$attachment_id 	= !empty($attachments) ? $attachments[0] : null;

					// Store result in cache for 1 hour
					wp_cache_set($cache_key, $attachment_id, 'custom_cache_group', HOUR_IN_SECONDS);
				}

				// If attachment exists, return its details
				if ($attachment_id) {
					return [
						'id'  => (int) $attachment_id,
						'url' => wp_get_attachment_url($attachment_id),
					];
				}

				return false;
			}
		}
	}