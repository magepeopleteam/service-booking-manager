<?php
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
				$all_post = MPWPB_Query::query_post_type('mpwpb_item');
				if ($all_post->post_count == 0 && $dummy_post != 'yes') {
					$dummy_data = $this->dummy_data();
					foreach ($dummy_data as $type => $dummy) {
						if ($type == 'taxonomy') {
							foreach ($dummy as $taxonomy => $dummy_taxonomy) {
								$check_taxonomy = MP_Global_Function::get_taxonomy($taxonomy);
								if (is_string($check_taxonomy) || sizeof($check_taxonomy) == 0) {
									foreach ($dummy_taxonomy as $taxonomy_data) {
										wp_insert_term($taxonomy_data['name'], $taxonomy);
									}
								}
								//echo '<pre>'; print_r( $query); echo '</pre>';
							}
						}
						if ($type == 'custom_post') {
							foreach ($dummy as $custom_post => $dummy_post) {
								$post = MPWPB_Query::query_post_type($custom_post);
								if ($post->post_count == 0) {
									foreach ($dummy_post as $dummy_data) {
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
									}
								}
							}
						}
					}
					update_option('mptbm_dummy_already_inserted', 'yes');
				}
			}
			public function dummy_data(): array {
				return [
					'taxonomy' => [
						'ttbm_tour_cat' => [
							0 => ['name' => 'Fixed Tour'],
							1 => ['name' => 'Flexible Tour']
						],
					],
					'custom_post' => [
						'ttbm_tour' => [
							0 => [
								'name' => 'The Mentalist Tickets: Las Vegas',
								'post_data' => [
									//General_settings
									'ttbm_travel_duration' => 2,
									'ttbm_travel_duration_type' => 'day',
									'ttbm_display_duration_night' => 'on',
									'ttbm_travel_duration_night' => 3,
									'ttbm_display_price_start' => 'on',
									'ttbm_travel_start_price' => 250,
									'ttbm_display_max_people' => 'on',
									'ttbm_display_min_age' => 'on',
									'ttbm_travel_min_age' => 10,
									'ttbm_display_start_location' => 'on',
									'ttbm_travel_start_place' => 'Las Vegas',
									'ttbm_display_location' => 'off',
									'ttbm_location_name' => '',
									'ttbm_display_map' => 'off',
									'ttbm_display_description' => 'on',
									'ttbm_short_description' => 'Watch Gerry McCambridge perform comedy, magic, and mind reading live on stage at the amazing 75-minute Las Vegas show, The Mentalist! McCambridge has been nominated “Best Magician in Las Vegas”, so come and see him live for a mind-blowing night.',
									//date_settings
									'ttbm_travel_type' => 'fixed',
									'ttbm_travel_start_date' => date('Y-m-d', strtotime(' +25 day')),
									'ttbm_travel_reg_end_date' => date('Y-m-d', strtotime(' +30 day')),
									//price_settings
									'ttbm_display_registration' => 'on',
									'ttbm_display_advance' => 'off',
									'ttbm_type' => 'general',
									'ttbm_hotels' => array(),
									'ttbm_ticket_type' => [
										0 => [
											'ticket_type_icon' => 'fas fa-user-tie',
											'ticket_type_name' => 'Adult',
											'ticket_type_price' => 15,
											'sale_price' => 10,
											'ticket_type_qty' => 150,
											'ticket_type_default_qty' => 0,
											'ticket_type_resv_qty' => 5,
											'ticket_type_qty_type' => 'inputbox',
											'ticket_type_description' => '',
										],
										1 => [
											'ticket_type_icon' => 'fas fa-snowman',
											'ticket_type_name' => 'Child',
											'ticket_type_price' => 10,
											'sale_price' => 7,
											'ticket_type_qty' => 100,
											'ticket_type_default_qty' => 0,
											'ticket_type_resv_qty' => 25,
											'ticket_type_qty_type' => 'inputbox',
											'ticket_type_description' => '',
										]
									],
									'ttbm_extra_service_data' => [
										0 => [
											'service_icon' => 'fas fa-graduation-cap',
											'service_name' => 'Cap',
											'service_price' => 6,
											'service_qty' => 500,
											'service_qty_type' => 'inputbox',
											'extra_service_description' => '',
										],
										1 => [
											'service_icon' => 'fas fa-coffee',
											'service_name' => 'Coffe',
											'service_price' => 4,
											'service_qty' => 1500,
											'service_qty_type' => 'inputbox',
											'extra_service_description' => '',
										],
									],
									'ttbm_display_include_service' => 'on',
									'ttbm_service_included_in_price' => [
										0 => 'Accommodation',
										1 => 'Breakfast',
										2 => 'Welcome Drinks',
										3 => 'Lunch',
										4 => 'Transport',
									],
									'ttbm_service_excluded_in_price' => [
										0 => 'Airport Transfer',
										1 => 'BBQ Night',
										2 => 'Guide',
										3 => 'Insurance',
										4 => 'Outing Ticket',
									],
									//Place_you_see_settings
									//day wise details_settings
									//faq_settings
									'ttbm_display_faq' => 'on',
									'mep_event_faq' => [
										0 => [
											'ttbm_faq_title' => 'What can I expect to see at The Mentalist at Planet Hollywood Resort and Casino?',
											'ttbm_faq_content' => 'Comedy, magic and mind-reading! The Mentalist has the ability to get inside the minds of audience members, revealing everything from their names, hometowns and anniversaries to their wildest wishes.',
										],
										1 => [
											'ttbm_faq_title' => 'Where is The Mentalist located?',
											'ttbm_faq_content' => 'The V Theater is located inside the Miracle Mile Shops at the Planet Hollywood Resort & Casino.',
										],
										2 => [
											'ttbm_faq_title' => 'Can I purchase alcohol at the venue during The Mentalist!?',
											'ttbm_faq_content' => 'Absolutely! Drinks are available for purchase at the Showgirl Bar outside of the theater and may be brought into the showroom, however, no other outside food or drink will be allowed in the theater.',
										],
										3 => [
											'ttbm_faq_title' => 'Is The Mentalist appropriate for children?',
											'ttbm_faq_content' => 'Due to language, this show is recommended for guests 16 years old and over.',
										],
										4 => [
											'ttbm_faq_title' => 'Do I need to exchange my ticket upon arrival at The Mentalist!?',
											'ttbm_faq_content' => 'Please pick up your tickets at the V Theater Box Office with a valid photo ID for the lead traveler at least 30 minutes prior to show time (box office opens at 11 am). Seating will begin 15 minutes before showtime.',
										],
									],
									//why chose us_settings
									'ttbm_why_choose_us_texts' => [
										0 => 'Enjoy a taste of Las Vegas glitz at the mind-bending magic show',
										1 => 'Enjoy a taste of Las Vegas glitz at the mind-bending magic show',
										2 => 'Watch as Gerry McCambridge performs comedy and magic',
									],
									//activities_settings
									'ttbm_display_activities' => 'on',
									'ttbm_tour_activities' => [
										0 => 'Beach',
										1 => 'Hiking',
										2 => 'Snow & Ice',
									],
									//gallery_settings
									'ttbm_gallery_images' => [120, 130, 140, 150, 160, 170, 180, 190, 200, 210, 220, 230, 240, 250, 260, 270, 280, 290, 300],
									//extras_settings
									'ttbm_display_get_question' => 'on',
									'ttbm_contact_email' => 'example.gmail.com',
									'ttbm_contact_phone' => '123456789',
									'ttbm_contact_text' => 'Do not hesitage to give us a call. We are an expert team and we are happy to talk to you.',
									'ttbm_display_tour_guide' => 'on',
									//Related tour_settings
									//Display_settings
									'ttbm_section_title_style' => 'ttbm_title_style_2',
									'ttbm_ticketing_system' => 'availability_section',
									'ttbm_display_seat_details' => 'on',
									'ttbm_display_sidebar' => 'off',
									'ttbm_display_tour_type' => 'on',
									'ttbm_display_hotels' => 'on',
									'ttbm_display_duration' => 'on',
								]
							],
							1 => [
								'name' => 'Highlights of Naples and the Amalfi Coast',
								'post_data' => [
									//General_settings
									'ttbm_travel_duration' => 1,
									'ttbm_travel_duration_type' => 'day',
									'ttbm_display_duration_night' => 'on',
									'ttbm_travel_duration_night' => 1,
									'ttbm_display_price_start' => 'on',
									'ttbm_travel_start_price' => 180,
									'ttbm_display_max_people' => 'on',
									'ttbm_display_min_age' => 'on',
									'ttbm_travel_min_age' => 5,
									'ttbm_display_start_location' => 'on',
									'ttbm_travel_start_place' => 'Naple',
									'ttbm_display_location' => 'off',
									'ttbm_location_name' => '',
									'ttbm_display_map' => 'off',
									'ttbm_display_description' => 'on',
									'ttbm_short_description' => '',
									//date_settings
									'ttbm_travel_type' => 'fixed',
									'ttbm_travel_start_date' => date('Y-m-d', strtotime(' +35 day')),
									'ttbm_travel_reg_end_date' => date('Y-m-d', strtotime(' +36 day')),
									//price_settings
									'ttbm_display_registration' => 'on',
									'ttbm_display_advance' => 'off',
									'ttbm_type' => 'general',
									'ttbm_hotels' => array(),
									'ttbm_ticket_type' => [
										0 => [
											'ticket_type_icon' => 'fas fa-user-tie',
											'ticket_type_name' => 'Adult',
											'ticket_type_price' => 55,
											'sale_price' => 40,
											'ticket_type_qty' => 220,
											'ticket_type_default_qty' => 0,
											'ticket_type_resv_qty' => 5,
											'ticket_type_qty_type' => 'inputbox',
											'ticket_type_description' => '',
										],
										1 => [
											'ticket_type_icon' => 'fas fa-snowman',
											'ticket_type_name' => 'Child',
											'ticket_type_price' => 100,
											'sale_price' => 70,
											'ticket_type_qty' => 100,
											'ticket_type_default_qty' => 0,
											'ticket_type_resv_qty' => 20,
											'ticket_type_qty_type' => 'inputbox',
											'ticket_type_description' => '',
										]
									],
									'ttbm_extra_service_data' => [
										0 => [
											'service_icon' => 'fas fa-graduation-cap',
											'service_name' => 'Cap',
											'service_price' => 6,
											'service_qty' => 500,
											'service_qty_type' => 'inputbox',
											'extra_service_description' => '',
										],
										1 => [
											'service_icon' => 'fas fa-coffee',
											'service_name' => 'Coffe',
											'service_price' => 4,
											'service_qty' => 1500,
											'service_qty_type' => 'inputbox',
											'extra_service_description' => '',
										],
									],
									'ttbm_display_include_service' => 'on',
									'ttbm_service_included_in_price' => [
										0 => 'Accommodation',
										1 => 'Breakfast',
										2 => 'Welcome Drinks',
										3 => 'Lunch',
										4 => 'Transport',
									],
									'ttbm_display_exclude_service' => 'on',
									'ttbm_service_excluded_in_price' => [
										0 => 'Airport Transfer',
										1 => 'BBQ Night',
										2 => 'Guide',
										3 => 'Insurance',
										4 => 'Outing Ticket',
									],
									//Place_you_see_settings
									//day wise details_settings
									//faq_settings
									'ttbm_display_faq' => 'on',
									'mep_event_faq' => [
										0 => [
											'ttbm_faq_title' => 'What can I expect to see at The Mentalist at Planet Hollywood Resort and Casino?',
											'ttbm_faq_content' => 'Comedy, magic and mind-reading! The Mentalist has the ability to get inside the minds of audience members, revealing everything from their names, hometowns and anniversaries to their wildest wishes.',
										],
										1 => [
											'ttbm_faq_title' => 'Where is The Mentalist located?',
											'ttbm_faq_content' => 'The V Theater is located inside the Miracle Mile Shops at the Planet Hollywood Resort & Casino.',
										],
										2 => [
											'ttbm_faq_title' => 'Can I purchase alcohol at the venue during The Mentalist!?',
											'ttbm_faq_content' => 'Absolutely! Drinks are available for purchase at the Showgirl Bar outside of the theater and may be brought into the showroom, however, no other outside food or drink will be allowed in the theater.',
										],
										3 => [
											'ttbm_faq_title' => 'Is The Mentalist appropriate for children?',
											'ttbm_faq_content' => 'Due to language, this show is recommended for guests 16 years old and over.',
										],
										4 => [
											'ttbm_faq_title' => 'Do I need to exchange my ticket upon arrival at The Mentalist!?',
											'ttbm_faq_content' => 'Please pick up your tickets at the V Theater Box Office with a valid photo ID for the lead traveler at least 30 minutes prior to show time (box office opens at 11 am). Seating will begin 15 minutes before showtime.',
										],
									],
									//why chose us_settings
									'ttbm_why_choose_us_texts' => [
										0 => 'Enjoy a taste of Las Vegas glitz at the mind-bending magic show',
										1 => 'Enjoy a taste of Las Vegas glitz at the mind-bending magic show',
										2 => 'Watch as Gerry McCambridge performs comedy and magic',
									],
									//activities_settings
									'ttbm_display_activities' => 'on',
									'ttbm_tour_activities' => [
										0 => 'City Tours',
										1 => 'Hiking',
										2 => 'Rural',
									],
									//gallery_settings
									'ttbm_gallery_images' => [120, 130, 140, 150, 160, 170, 180, 190, 200, 210, 220, 230, 240, 250, 260, 270, 280, 290, 300],
									//extras_settings
									'ttbm_display_get_question' => 'on',
									'ttbm_contact_email' => 'example.gmail.com',
									'ttbm_contact_phone' => '123456789',
									'ttbm_contact_text' => 'Do not hesitate to give us a call. We are an expert team and we are happy to talk to you.',
									'ttbm_display_tour_guide' => 'on',
									//Related tour_settings
									//Display_settings
									'ttbm_section_title_style' => 'ttbm_title_style_2',
									'ttbm_ticketing_system' => 'availability_section',
									'ttbm_display_seat_details' => 'on',
									'ttbm_display_sidebar' => 'off',
									'ttbm_display_tour_type' => 'on',
									'ttbm_display_hotels' => 'on',
									'ttbm_display_duration' => 'on',
								]
							],
							2 => [
								'name' => 'Deep-Sea Exploration on a Shampan',
								'post_data' => [
									//General_settings
									'ttbm_travel_duration' => 1,
									'ttbm_travel_duration_type' => 'day',
									'ttbm_display_duration_night' => 'on',
									'ttbm_travel_duration_night' => 1,
									'ttbm_display_price_start' => 'on',
									'ttbm_travel_start_price' => '',
									'ttbm_display_max_people' => 'on',
									'ttbm_display_min_age' => 'on',
									'ttbm_travel_min_age' => 5,
									'ttbm_display_start_location' => 'on',
									'ttbm_travel_start_place' => '',
									'ttbm_display_location' => 'off',
									'ttbm_location_name' => '',
									'ttbm_display_map' => 'off',
									'ttbm_display_description' => 'on',
									'ttbm_short_description' => '',
									//date_settings
									'ttbm_travel_type' => 'repeated',
									'ttbm_travel_repeated_after' => '4',
									'ttbm_travel_repeated_start_date' => date('Y-m-d', strtotime(' +15 day')),
									'ttbm_travel_repeated_end_date' => date('Y-m-d', strtotime(' +365 day')),
									//price_settings
									'ttbm_display_registration' => 'on',
									'ttbm_display_advance' => 'off',
									'ttbm_type' => 'general',
									'ttbm_hotels' => array(),
									'ttbm_ticket_type' => [
										0 => [
											'ticket_type_icon' => 'fas fa-user-tie',
											'ticket_type_name' => 'Adult',
											'ticket_type_price' => 55,
											'sale_price' => 40,
											'ticket_type_qty' => 220,
											'ticket_type_default_qty' => 0,
											'ticket_type_resv_qty' => 5,
											'ticket_type_qty_type' => 'inputbox',
											'ticket_type_description' => '',
										],
										1 => [
											'ticket_type_icon' => 'fas fa-snowman',
											'ticket_type_name' => 'Child',
											'ticket_type_price' => 100,
											'sale_price' => 70,
											'ticket_type_qty' => 100,
											'ticket_type_default_qty' => 0,
											'ticket_type_resv_qty' => 20,
											'ticket_type_qty_type' => 'inputbox',
											'ticket_type_description' => '',
										]
									],
									'ttbm_extra_service_data' => [
										0 => [
											'service_icon' => 'fas fa-graduation-cap',
											'service_name' => 'Cap',
											'service_price' => 6,
											'service_qty' => 500,
											'service_qty_type' => 'inputbox',
											'extra_service_description' => '',
										],
										1 => [
											'service_icon' => 'fas fa-coffee',
											'service_name' => 'Coffe',
											'service_price' => 4,
											'service_qty' => 1500,
											'service_qty_type' => 'inputbox',
											'extra_service_description' => '',
										],
									],
									'ttbm_display_include_service' => 'on',
									'ttbm_service_included_in_price' => [
										0 => 'Accommodation',
										1 => 'Breakfast',
										2 => 'Welcome Drinks',
										3 => 'Lunch',
										4 => 'Transport',
									],
									'ttbm_display_exclude_service' => 'on',
									'ttbm_service_excluded_in_price' => [
										0 => 'Airport Transfer',
										1 => 'BBQ Night',
										2 => 'Guide',
										3 => 'Insurance',
										4 => 'Outing Ticket',
									],
									//Place_you_see_settings
									//day wise details_settings
									//faq_settings
									'ttbm_display_faq' => 'on',
									'mep_event_faq' => [
										0 => [
											'ttbm_faq_title' => 'What can I expect to see at The Mentalist at Planet Hollywood Resort and Casino?',
											'ttbm_faq_content' => 'Comedy, magic and mind-reading! The Mentalist has the ability to get inside the minds of audience members, revealing everything from their names, hometowns and anniversaries to their wildest wishes.',
										],
										1 => [
											'ttbm_faq_title' => 'Where is The Mentalist located?',
											'ttbm_faq_content' => 'The V Theater is located inside the Miracle Mile Shops at the Planet Hollywood Resort & Casino.',
										],
										2 => [
											'ttbm_faq_title' => 'Can I purchase alcohol at the venue during The Mentalist!?',
											'ttbm_faq_content' => 'Absolutely! Drinks are available for purchase at the Showgirl Bar outside of the theater and may be brought into the showroom, however, no other outside food or drink will be allowed in the theater.',
										],
										3 => [
											'ttbm_faq_title' => 'Is The Mentalist appropriate for children?',
											'ttbm_faq_content' => 'Due to language, this show is recommended for guests 16 years old and over.',
										],
										4 => [
											'ttbm_faq_title' => 'Do I need to exchange my ticket upon arrival at The Mentalist!?',
											'ttbm_faq_content' => 'Please pick up your tickets at the V Theater Box Office with a valid photo ID for the lead traveler at least 30 minutes prior to show time (box office opens at 11 am). Seating will begin 15 minutes before showtime.',
										],
									],
									//why chose us_settings
									'ttbm_why_choose_us_texts' => [
										0 => 'Enjoy a taste of Las Vegas glitz at the mind-bending magic show',
										1 => 'Enjoy a taste of Las Vegas glitz at the mind-bending magic show',
										2 => 'Watch as Gerry McCambridge performs comedy and magic',
									],
									//activities_settings
									'ttbm_display_activities' => 'on',
									'ttbm_tour_activities' => [
										0 => 'City Tours',
										1 => 'Hiking',
										2 => 'Rural',
									],
									//gallery_settings
									'ttbm_gallery_images' => [120, 130, 140, 150, 160, 170, 180, 190, 200, 210, 220, 230, 240, 250, 260, 270, 280, 290, 300],
									//extras_settings
									'ttbm_display_get_question' => 'on',
									'ttbm_contact_email' => 'example.gmail.com',
									'ttbm_contact_phone' => '123456789',
									'ttbm_contact_text' => 'Do not hesitate to give us a call. We are an expert team and we are happy to talk to you.',
									'ttbm_display_tour_guide' => 'on',
									//Related tour_settings
									//Display_settings
									'ttbm_section_title_style' => 'ttbm_title_style_2',
									'ttbm_ticketing_system' => 'regular_ticket',
									'ttbm_display_seat_details' => 'on',
									'ttbm_display_sidebar' => 'off',
									'ttbm_display_tour_type' => 'on',
									'ttbm_display_hotels' => 'on',
									'ttbm_display_duration' => 'on',
								]
							],
							3 => [
								'name' => 'Beach Hopping at Inani, Himchari, Patuartek',
								'post_data' => [
									//General_settings
									'ttbm_travel_duration' => 2,
									'ttbm_travel_duration_type' => 'day',
									'ttbm_display_duration_night' => 'on',
									'ttbm_travel_duration_night' => 1,
									'ttbm_display_price_start' => 'on',
									'ttbm_travel_start_price' => '',
									'ttbm_display_max_people' => 'on',
									'ttbm_display_min_age' => 'on',
									'ttbm_travel_min_age' => 12,
									'ttbm_display_start_location' => 'on',
									'ttbm_travel_start_place' => '',
									'ttbm_display_location' => 'off',
									'ttbm_location_name' => '',
									'ttbm_display_map' => 'off',
									'ttbm_display_description' => 'on',
									'ttbm_short_description' => '',
									//date_settings
									'ttbm_travel_type' => 'repeated',
									'ttbm_travel_repeated_after' => '7',
									'ttbm_travel_repeated_start_date' => date('Y-m-d', strtotime(' +25 day')),
									'ttbm_travel_repeated_end_date' => date('Y-m-d', strtotime(' +365 day')),
									//price_settings
									'ttbm_display_registration' => 'on',
									'ttbm_display_advance' => 'off',
									'ttbm_type' => 'general',
									'ttbm_hotels' => array(),
									'ttbm_ticket_type' => [
										0 => [
											'ticket_type_icon' => 'fas fa-user-tie',
											'ticket_type_name' => 'Adult',
											'ticket_type_price' => 105,
											'sale_price' => 100,
											'ticket_type_qty' => 200,
											'ticket_type_default_qty' => 0,
											'ticket_type_resv_qty' => 2,
											'ticket_type_qty_type' => 'inputbox',
											'ticket_type_description' => '',
										],
										1 => [
											'ticket_type_icon' => 'fas fa-snowman',
											'ticket_type_name' => 'Child',
											'ticket_type_price' => 100,
											'sale_price' => 90,
											'ticket_type_qty' => 100,
											'ticket_type_default_qty' => 0,
											'ticket_type_resv_qty' => 20,
											'ticket_type_qty_type' => 'inputbox',
											'ticket_type_description' => '',
										]
									],
									'ttbm_extra_service_data' => [
										0 => [
											'service_icon' => 'fas fa-graduation-cap',
											'service_name' => 'Cap',
											'service_price' => 6,
											'service_qty' => 500,
											'service_qty_type' => 'inputbox',
											'extra_service_description' => '',
										],
										1 => [
											'service_icon' => 'fas fa-coffee',
											'service_name' => 'Coffe',
											'service_price' => 4,
											'service_qty' => 1500,
											'service_qty_type' => 'inputbox',
											'extra_service_description' => '',
										],
									],
									'ttbm_display_include_service' => 'on',
									'ttbm_service_included_in_price' => [
										0 => 'Accommodation',
										1 => 'BBQ Night',
										2 => 'Welcome Drinks',
										3 => 'Lunch',
										4 => 'Transport',
									],
									'ttbm_display_exclude_service' => 'on',
									'ttbm_service_excluded_in_price' => [
										0 => 'Airport Transfer',
										1 => 'Breakfast',
										2 => 'Guide',
										3 => 'Insurance',
										4 => 'Outing Ticket',
									],
									//Place_you_see_settings
									//day wise details_settings
									//faq_settings
									'ttbm_display_faq' => 'on',
									'mep_event_faq' => [
										0 => [
											'ttbm_faq_title' => 'What can I expect to see at The Mentalist at Planet Hollywood Resort and Casino?',
											'ttbm_faq_content' => 'Comedy, magic and mind-reading! The Mentalist has the ability to get inside the minds of audience members, revealing everything from their names, hometowns and anniversaries to their wildest wishes.',
										],
										1 => [
											'ttbm_faq_title' => 'Where is The Mentalist located?',
											'ttbm_faq_content' => 'The V Theater is located inside the Miracle Mile Shops at the Planet Hollywood Resort & Casino.',
										],
										2 => [
											'ttbm_faq_title' => 'Can I purchase alcohol at the venue during The Mentalist!?',
											'ttbm_faq_content' => 'Absolutely! Drinks are available for purchase at the Showgirl Bar outside of the theater and may be brought into the showroom, however, no other outside food or drink will be allowed in the theater.',
										],
										3 => [
											'ttbm_faq_title' => 'Is The Mentalist appropriate for children?',
											'ttbm_faq_content' => 'Due to language, this show is recommended for guests 16 years old and over.',
										],
										4 => [
											'ttbm_faq_title' => 'Do I need to exchange my ticket upon arrival at The Mentalist!?',
											'ttbm_faq_content' => 'Please pick up your tickets at the V Theater Box Office with a valid photo ID for the lead traveler at least 30 minutes prior to show time (box office opens at 11 am). Seating will begin 15 minutes before showtime.',
										],
									],
									//why chose us_settings
									'ttbm_why_choose_us_texts' => [
										0 => 'Enjoy a taste of Las Vegas glitz at the mind-bending magic show',
										1 => 'Enjoy a taste of Las Vegas glitz at the mind-bending magic show',
										2 => 'Watch as Gerry McCambridge performs comedy and magic',
									],
									//activities_settings
									'ttbm_display_activities' => 'on',
									'ttbm_tour_activities' => [
										0 => 'City Tours',
										1 => 'Hiking',
										2 => 'Rural',
									],
									//gallery_settings
									'ttbm_gallery_images' => [120, 130, 140, 150, 160, 170, 180, 190, 200, 210, 220, 230, 240, 250, 260, 270, 280, 290, 300],
									//extras_settings
									'ttbm_display_get_question' => 'on',
									'ttbm_contact_email' => 'example.gmail.com',
									'ttbm_contact_phone' => '123456789',
									'ttbm_contact_text' => 'Do not hesitate to give us a call. We are an expert team and we are happy to talk to you.',
									'ttbm_display_tour_guide' => 'on',
									//Related tour_settings
									//Display_settings
									'ttbm_section_title_style' => 'ttbm_title_style_2',
									'ttbm_ticketing_system' => 'regular_ticket',
									'ttbm_display_seat_details' => 'on',
									'ttbm_display_sidebar' => 'off',
									'ttbm_display_tour_type' => 'on',
									'ttbm_display_hotels' => 'on',
									'ttbm_display_duration' => 'on',
								]
							],
							4 => [
								'name' => 'Boga Lake : A Relaxing Gateway Tour',
								'post_data' => [
									//General_settings
									'ttbm_travel_duration' => 4,
									'ttbm_travel_duration_type' => 'day',
									'ttbm_display_duration_night' => 'on',
									'ttbm_travel_duration_night' => 5,
									'ttbm_display_price_start' => 'on',
									'ttbm_travel_start_price' => '',
									'ttbm_display_max_people' => 'on',
									'ttbm_display_min_age' => 'on',
									'ttbm_travel_min_age' => 18,
									'ttbm_display_start_location' => 'on',
									'ttbm_travel_start_place' => '',
									'ttbm_display_location' => 'off',
									'ttbm_location_name' => '',
									'ttbm_display_map' => 'off',
									'ttbm_display_description' => 'on',
									'ttbm_short_description' => '',
									//date_settings
									'ttbm_travel_type' => 'repeated',
									'ttbm_travel_repeated_after' => '15',
									'ttbm_travel_repeated_start_date' => date('Y-m-d', strtotime(' +35 day')),
									'ttbm_travel_repeated_end_date' => date('Y-m-d', strtotime(' +365 day')),
									//price_settings
									'ttbm_display_registration' => 'on',
									'ttbm_display_advance' => 'off',
									'ttbm_type' => 'general',
									'ttbm_hotels' => array(),
									'ttbm_ticket_type' => [
										0 => [
											'ticket_type_icon' => 'fas fa-user-tie',
											'ticket_type_name' => 'Adult',
											'ticket_type_price' => 105,
											'sale_price' => 100,
											'ticket_type_qty' => 200,
											'ticket_type_default_qty' => 0,
											'ticket_type_resv_qty' => 2,
											'ticket_type_qty_type' => 'inputbox',
											'ticket_type_description' => '',
										],
										1 => [
											'ticket_type_icon' => 'fas fa-snowman',
											'ticket_type_name' => 'Child',
											'ticket_type_price' => 100,
											'sale_price' => 90,
											'ticket_type_qty' => 100,
											'ticket_type_default_qty' => 0,
											'ticket_type_resv_qty' => 20,
											'ticket_type_qty_type' => 'inputbox',
											'ticket_type_description' => '',
										]
									],
									'ttbm_extra_service_data' => [
										0 => [
											'service_icon' => 'fas fa-graduation-cap',
											'service_name' => 'Cap',
											'service_price' => 6,
											'service_qty' => 500,
											'service_qty_type' => 'inputbox',
											'extra_service_description' => '',
										],
										1 => [
											'service_icon' => 'fas fa-coffee',
											'service_name' => 'Coffe',
											'service_price' => 4,
											'service_qty' => 1500,
											'service_qty_type' => 'inputbox',
											'extra_service_description' => '',
										],
									],
									'ttbm_display_include_service' => 'on',
									'ttbm_service_included_in_price' => [
										0 => 'Accommodation',
										1 => 'BBQ Night',
										2 => 'Welcome Drinks',
										3 => 'Lunch',
										4 => 'Transport',
									],
									'ttbm_display_exclude_service' => 'on',
									'ttbm_service_excluded_in_price' => [
										0 => 'Airport Transfer',
										1 => 'Breakfast',
										2 => 'Guide',
										3 => 'Insurance',
										4 => 'Outing Ticket',
									],
									//Place_you_see_settings
									//day wise details_settings
									//faq_settings
									'ttbm_display_faq' => 'on',
									'mep_event_faq' => [
										0 => [
											'ttbm_faq_title' => 'What can I expect to see at The Mentalist at Planet Hollywood Resort and Casino?',
											'ttbm_faq_content' => 'Comedy, magic and mind-reading! The Mentalist has the ability to get inside the minds of audience members, revealing everything from their names, hometowns and anniversaries to their wildest wishes.',
										],
										1 => [
											'ttbm_faq_title' => 'Where is The Mentalist located?',
											'ttbm_faq_content' => 'The V Theater is located inside the Miracle Mile Shops at the Planet Hollywood Resort & Casino.',
										],
										2 => [
											'ttbm_faq_title' => 'Can I purchase alcohol at the venue during The Mentalist!?',
											'ttbm_faq_content' => 'Absolutely! Drinks are available for purchase at the Showgirl Bar outside of the theater and may be brought into the showroom, however, no other outside food or drink will be allowed in the theater.',
										],
										3 => [
											'ttbm_faq_title' => 'Is The Mentalist appropriate for children?',
											'ttbm_faq_content' => 'Due to language, this show is recommended for guests 16 years old and over.',
										],
										4 => [
											'ttbm_faq_title' => 'Do I need to exchange my ticket upon arrival at The Mentalist!?',
											'ttbm_faq_content' => 'Please pick up your tickets at the V Theater Box Office with a valid photo ID for the lead traveler at least 30 minutes prior to show time (box office opens at 11 am). Seating will begin 15 minutes before showtime.',
										],
									],
									//why chose us_settings
									'ttbm_why_choose_us_texts' => [
										0 => 'Enjoy a taste of Las Vegas glitz at the mind-bending magic show',
										1 => 'Enjoy a taste of Las Vegas glitz at the mind-bending magic show',
										2 => 'Watch as Gerry McCambridge performs comedy and magic',
									],
									//activities_settings
									'ttbm_display_activities' => 'on',
									'ttbm_tour_activities' => [
										0 => 'Hiking',
										1 => 'Rural',
									],
									//gallery_settings
									'ttbm_gallery_images' => [120, 130, 140, 150, 160, 170, 180, 190, 200, 210, 220, 230, 240, 250, 260, 270, 280, 290, 300],
									//extras_settings
									'ttbm_display_get_question' => 'on',
									'ttbm_contact_email' => 'example.gmail.com',
									'ttbm_contact_phone' => '123456789',
									'ttbm_contact_text' => 'Do not hesitate to give us a call. We are an expert team and we are happy to talk to you.',
									'ttbm_display_tour_guide' => 'on',
									//Related tour_settings
									//Display_settings
									'ttbm_section_title_style' => 'ttbm_title_style_2',
									'ttbm_ticketing_system' => 'regular_ticket',
									'ttbm_display_seat_details' => 'on',
									'ttbm_display_sidebar' => 'off',
									'ttbm_display_tour_type' => 'on',
									'ttbm_display_hotels' => 'on',
									'ttbm_display_duration' => 'on',
								]
							],
						]
					]
				];
			}
		}
	}