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
				$all_post = MP_Global_Function::query_post_type('mpwpb_item');
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
							}
						}
						if ($type == 'custom_post') {
							foreach ($dummy as $custom_post => $dummy_post) {
								$post = MP_Global_Function::query_post_type($custom_post);
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
					update_option('mpwpb_dummy_already_inserted', 'yes');
				}
			}
			public function dummy_data(): array {
				return [
					'taxonomy' => [
						// 'mpwpb_category' => [
						// 	0 => ['name' => 'Fixed Tour'],
						// 	1 => ['name' => 'Flexible Tour']
						// ],
					],
					'custom_post' => [
						'mpwpb_item' => [
							0 => [
								'name' => 'Rent Your Dream Car',
								'post_data' => [
									//General_settings
									'mpwpb_shortcode_title' => 'Rent-A-Car Service',
									'mpwpb_shortcode_sub_title' => 'Rent your dream car easily with affordable price',
									//date_settings
									'mpwpb_service_start_date' => '2023-03-01',
									'mpwpb_service_end_date' => '2023-08-25',
									'mpwpb_time_slot_length' => '60',
									'mpwpb_capacity_per_session' => '1',
									'mpwpb_default_start_time' => '10',
									'mpwpb_default_end_time' => '18',
									'mpwpb_default_start_break_time' => '13',
									'mpwpb_default_end_break_time' => '15',
									'mpwpb_monday_start_time' => '10',
									'mpwpb_monday_end_time' => '18',
									'mpwpb_monday_start_break_time' => '13',
									'mpwpb_monday_end_break_time' => '15',
									'mpwpb_tuesday_start_time' => '10.5',
									'mpwpb_tuesday_end_time' => '18.5',
									'mpwpb_tuesday_start_break_time' => '13.5',
									'mpwpb_tuesday_end_break_time' => '15.5',
									'mpwpb_wednesday_start_time' => '11',
									'mpwpb_wednesday_end_time' => '19',
									'mpwpb_wednesday_start_break_time' => '14',
									'mpwpb_wednesday_end_break_time' => '16',
									'mpwpb_thursday_start_time' => '10',
									'mpwpb_thursday_end_time' => '18',
									'mpwpb_thursday_start_break_time' => '13',
									'mpwpb_thursday_end_break_time' => '15',
									'mpwpb_off_days' => 'saturday,sunday',
									'mpwpb_off_dates' => array(
										0 => '2023-03-07',
										1 => '2023-03-15',
									),
									//price_settings
									'mpwpb_category_active' => 'on',
									'mpwpb_sub_category_active' => 'off',
									'mpwpb_service_details_active' => 'off',
									'mpwpb_service_duration_active' => 'on',
									'mpwpb_category_text' => 'Car Type',
									'mpwpb_sub_category_text' => '',
									'mpwpb_service_text' => 'Service',
									'mpwpb_category_infos' => array(
										0 => array(
											'icon' => 'fas fa-car',
											'image' => '',
											'category' => 'Economy Car',
											'sub_category' => array(
												0 => array(
													'icon' => '',
													'image' => '',
													'name' => '',
													'service' => array(
														0 => array(
															'name' => 'Casinos',
															'price' => '10',
															'details' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vivamus vitae fringilla velit. Maecenas in purus ipsum. Integer euismod dui risus, eget porttitor enim molestie ac. Nunc ac sem a mauris vestibulum vestibulum. Duis orci massa, venenatis a gravida eget, convallis ac sapien',
															'duration' => '1h',
															'icon' => 'fas fa-gifts',
															'image' => '',
														),
														1 => array(
															'name' => 'Birthdays',
															'price' => '10',
															'details' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vivamus vitae fringilla velit. Maecenas in purus ipsum. Integer euismod dui risus, eget porttitor enim molestie ac. Nunc ac sem a mauris vestibulum vestibulum. Duis orci massa, venenatis a gravida eget, convallis ac sapien',
															'duration' => '1h',
															'icon' => 'fas fa-coffee',
															'image' => '',
														),
														2 => array(
															'name' => 'Airport Transfer',
															'price' => '10',
															'details' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vivamus vitae fringilla velit. Maecenas in purus ipsum. Integer euismod dui risus, eget porttitor enim molestie ac. Nunc ac sem a mauris vestibulum vestibulum. Duis orci massa, venenatis a gravida eget, convallis ac sapien',
															'duration' => '1h',
															'icon' => 'fas fa-car-side',
															'image' => '',
														),
													)
												)
											)
										),
										1 => array(
											'icon' => 'fas fa-shuttle-van',
											'image' => '',
											'category' => 'Standard Car',
											'sub_category' => array(
												0 => array(
													'icon' => '',
													'image' => '',
													'name' => '',
													'service' => array(
														0 => array(
															'name' => 'Weddings',
															'price' => '10',
															'details' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vivamus vitae fringilla velit. Maecenas in purus ipsum. Integer euismod dui risus, eget porttitor enim molestie ac. Nunc ac sem a mauris vestibulum vestibulum. Duis orci massa, venenatis a gravida eget, convallis ac sapien',
															'duration' => '1h',
															'icon' => 'fas fa-gifts',
															'image' => '',
														),
													)
												)
											)
										),
										2 => array(
											'icon' => 'fas fa-car-side',
											'image' => '',
											'category' => 'SUV Car',
											'sub_category' => array(
												0 => array(
													'icon' => '',
													'image' => '',
													'name' => '',
													'service' => array(
														0 => array(
															'name' => 'Night Parties Long Drive',
															'price' => '10',
															'details' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vivamus vitae fringilla velit. Maecenas in purus ipsum. Integer euismod dui risus, eget porttitor enim molestie ac. Nunc ac sem a mauris vestibulum vestibulum. Duis orci massa, venenatis a gravida eget, convallis ac sapien',
															'duration' => '1h',
															'icon' => 'fas fa-gifts',
															'image' => '',
														),
													)
												)
											)
										),
									),
									'mpwpb_extra_service_active' => 'on',
									'mpwpb_group_extra_service_active' => 'off',
									'mpwpb_extra_service' => array(
										0 => array(
											'group_service' => '',
											'group_service_info' => array(
												0 => array(
													'name' => 'Baby Seats',
													'qty' => 5,
													'price' => 5,
													'details' => 'you will be provided a baby seat for our baby inside car',
													'icon' => 'fas fa-baby-carriage',
													'image' => '',
												),
												1 => array(
													'name' => 'Birthday Cake',
													'qty' => 10,
													'price' => 10,
													'details' => 'you will be provided a birthday Cake 1 pound',
													'icon' => 'fas fa-boxes',
													'image' => '',
												),
												2 => array(
													'name' => 'Campaig',
													'qty' => 10,
													'price' => 10,
													'details' => 'you will get 1 bottle Campaig with giftbox',
													'icon' => 'fas fa-wheelchair',
													'image' => '',
												)
											)
										)
									),
									//Galary Settings
									'mpwpb_display_slider' => 'off',
									'mpwpb_slider_images' => 'off',
								],
							],
							1 => [
								'name' => 'YOGA INSTRUCTOR',
								'post_data' => [//General_settings
									'mpwpb_shortcode_title' => 'Yoga Instructor',
									'mpwpb_shortcode_sub_title' => 'Choose your yoga instructor easily with effordable price',
									//date_settings
									'mpwpb_service_start_date' => '2023-03-01',
									'mpwpb_service_end_date' => '2023-08-25',
									'mpwpb_time_slot_length' => '60',
									'mpwpb_capacity_per_session' => '1',
									'mpwpb_default_start_time' => '10',
									'mpwpb_default_end_time' => '18',
									'mpwpb_default_start_break_time' => '13',
									'mpwpb_default_end_break_time' => '15',
									'mpwpb_monday_start_time' => '10',
									'mpwpb_monday_end_time' => '18',
									'mpwpb_monday_start_break_time' => '13',
									'mpwpb_monday_end_break_time' => '15',
									'mpwpb_tuesday_start_time' => '10.5',
									'mpwpb_tuesday_end_time' => '18.5',
									'mpwpb_tuesday_start_break_time' => '13.5',
									'mpwpb_tuesday_end_break_time' => '15.5',
									'mpwpb_wednesday_start_time' => '11',
									'mpwpb_wednesday_end_time' => '19',
									'mpwpb_wednesday_start_break_time' => '14',
									'mpwpb_wednesday_end_break_time' => '16',
									'mpwpb_thursday_start_time' => '10',
									'mpwpb_thursday_end_time' => '18',
									'mpwpb_thursday_start_break_time' => '13',
									'mpwpb_thursday_end_break_time' => '15',
									'mpwpb_off_days' => 'saturday,sunday',
									'mpwpb_off_dates' => array(
										0 => '2023-03-07',
										1 => '2023-03-15',
									),
									//price_settings
									'mpwpb_category_active' => 'on',
									'mpwpb_sub_category_active' => 'off',
									'mpwpb_service_details_active' => 'on',
									'mpwpb_service_duration_active' => 'on',
									'mpwpb_category_text' => 'Yoga Styles',
									'mpwpb_sub_category_text' => '',
									'mpwpb_service_text' => 'Classes',
									'mpwpb_category_infos' => array(
										0 => array(
											'icon' => 'fas fa-running',
											'image' => '',
											'category' => 'Hatha Yoga',
											'sub_category' => array(
												0 => array(
													'icon' => '',
													'image' => '',
													'name' => '',
													'service' => array(
														0 => array(
															'name' => 'Back Body Space Posture',
															'price' => '10',
															'details' => 'Learn process about Back Body Space Posture',
															'duration' => '1h',
															'icon' => 'fas fa-gifts',
															'image' => '',
														),
														1 => array(
															'name' => 'Hatha-Yin Stretch',
															'price' => '12',
															'details' => 'Learn process about Hatha-Yin Stretch',
															'duration' => '1h',
															'icon' => 'fas fa-wheelchair',
															'image' => '',
														),
														2 => array(
															'name' => 'Hands Free Yoga',
															'price' => '14',
															'details' => 'Learn process about Hands Free Yoga',
															'duration' => '1h',
															'icon' => 'fas fa-thermometer',
															'image' => '',
														),
														3 => array(
															'name' => 'Shake It Off',
															'price' => '15',
															'details' => 'Learn process about Shake It Off',
															'duration' => '1h',
															'icon' => 'fas fa-id-card-alt',
															'image' => '',
														),
														4 => array(
															'name' => 'Rotation Stretch',
															'price' => '20',
															'details' => 'Learn process about Rotation Stretch',
															'duration' => '1h',
															'icon' => 'fas fa-soundcloud',
															'image' => '',
														),
														5 => array(
															'name' => 'Stretch Assist',
															'price' => '22',
															'details' => 'Learn process about Stretch Assist',
															'duration' => '1h',
															'icon' => 'fas fa-record-vinyl',
															'image' => '',
														),
													)
												),
											)
										),
										1 => array(
											'icon' => 'fas fa-user-md',
											'image' => '',
											'category' => 'Vinyasa Yoga',
											'sub_category' => array(
												0 => array(
													'icon' => '',
													'image' => '',
													'name' => '',
													'service' => array(
														0 => array(
															'name' => 'Vinyasa For Backbends',
															'price' => '10',
															'details' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vivamus vitae fringilla velit. Maecenas in purus ipsum. Integer euismod dui risus, eget porttitor enim molestie ac. Nunc ac sem a mauris vestibulum vestibulum. Duis orci massa, venenatis a gravida eget, convallis ac sapien',
															'duration' => '1h',
															'icon' => 'fas fa-gifts',
															'image' => '',
														),
														1 => array(
															'name' => 'Full Body Power Flow',
															'price' => '25',
															'details' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vivamus vitae fringilla velit. Maecenas in purus ipsum. Integer euismod dui risus, eget porttitor enim molestie ac. Nunc ac sem a mauris vestibulum vestibulum. Duis orci massa, venenatis a gravida eget, convallis ac sapien',
															'duration' => '1h',
															'icon' => 'fas fa-x-ray',
															'image' => '',
														),
														2 => array(
															'name' => 'Strong Flow',
															'price' => '30',
															'details' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vivamus vitae fringilla velit. Maecenas in purus ipsum. Integer euismod dui risus, eget porttitor enim molestie ac. Nunc ac sem a mauris vestibulum vestibulum. Duis orci massa, venenatis a gravida eget, convallis ac sapien',
															'duration' => '1h',
															'icon' => 'fas fa-dolly-flatbed',
															'image' => '',
														),
														3 => array(
															'name' => 'Vinyasa Flow',
															'price' => '35',
															'details' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vivamus vitae fringilla velit. Maecenas in purus ipsum. Integer euismod dui risus, eget porttitor enim molestie ac. Nunc ac sem a mauris vestibulum vestibulum. Duis orci massa, venenatis a gravida eget, convallis ac sapien',
															'duration' => '1h',
															'icon' => 'fas fa-anchor',
															'image' => '',
														),
														4 => array(
															'name' => 'Intuitive Flexibility',
															'price' => '40',
															'details' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vivamus vitae fringilla velit. Maecenas in purus ipsum. Integer euismod dui risus, eget porttitor enim molestie ac. Nunc ac sem a mauris vestibulum vestibulum. Duis orci massa, venenatis a gravida eget, convallis ac sapien',
															'duration' => '1h',
															'icon' => 'fas fa-compass',
															'image' => '',
														),
														5 => array(
															'name' => 'Sweat Ladder Flow',
															'price' => '45',
															'details' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vivamus vitae fringilla velit. Maecenas in purus ipsum. Integer euismod dui risus, eget porttitor enim molestie ac. Nunc ac sem a mauris vestibulum vestibulum. Duis orci massa, venenatis a gravida eget, convallis ac sapien',
															'duration' => '1h',
															'icon' => 'fas fa-record-vinyl',
															'image' => '',
														),
													)
												),
											)
										),
										2 => array(
											'icon' => 'fas fa-play',
											'image' => '',
											'category' => 'Kids Yoga',
											'sub_category' => array(
												0 => array(
													'icon' => '',
													'image' => '',
													'name' => '',
													'service' => array(
														0 => array(
															'name' => 'Tree Power',
															'price' => '10',
															'details' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vivamus vitae fringilla velit. Maecenas in purus ipsum. Integer euismod dui risus, eget porttitor enim molestie ac. Nunc ac sem a mauris vestibulum vestibulum. Duis orci massa, venenatis a gravida eget, convallis ac sapien',
															'duration' => '1h',
															'icon' => 'fas fa-gifts',
															'image' => '',
														),
														1 => array(
															'name' => 'Strong Inside',
															'price' => '25',
															'details' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vivamus vitae fringilla velit. Maecenas in purus ipsum. Integer euismod dui risus, eget porttitor enim molestie ac. Nunc ac sem a mauris vestibulum vestibulum. Duis orci massa, venenatis a gravida eget, convallis ac sapien',
															'duration' => '1h',
															'icon' => 'fas fa-dolly-flatbed',
															'image' => '',
														),
														2 => array(
															'name' => 'Rainbow Power',
															'price' => '30',
															'details' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vivamus vitae fringilla velit. Maecenas in purus ipsum. Integer euismod dui risus, eget porttitor enim molestie ac. Nunc ac sem a mauris vestibulum vestibulum. Duis orci massa, venenatis a gravida eget, convallis ac sapien',
															'duration' => '1h',
															'icon' => 'fas fa-warehouse',
															'image' => '',
														),
														3 => array(
															'name' => 'Mind Muscle',
															'price' => '35',
															'details' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vivamus vitae fringilla velit. Maecenas in purus ipsum. Integer euismod dui risus, eget porttitor enim molestie ac. Nunc ac sem a mauris vestibulum vestibulum. Duis orci massa, venenatis a gravida eget, convallis ac sapien',
															'duration' => '1h',
															'icon' => 'fas fa-wind',
															'image' => '',
														),
													)
												),
											)
										),
										3 => array(
											'icon' => 'fas fa-x-ray',
											'image' => '',
											'category' => 'Kundalini Yoga',
											'sub_category' => array(
												0 => array(
													'icon' => '',
													'image' => '',
													'name' => '',
													'service' => array(
														0 => array(
															'name' => 'Mental Balance',
															'price' => '10',
															'details' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vivamus vitae fringilla velit. Maecenas in purus ipsum. Integer euismod dui risus, eget porttitor enim molestie ac. Nunc ac sem a mauris vestibulum vestibulum. Duis orci massa, venenatis a gravida eget, convallis ac sapien',
															'duration' => '1h',
															'icon' => 'fas fa-gifts',
															'image' => '',
														),
														1 => array(
															'name' => 'Access Your Inner Power',
															'price' => '25',
															'details' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vivamus vitae fringilla velit. Maecenas in purus ipsum. Integer euismod dui risus, eget porttitor enim molestie ac. Nunc ac sem a mauris vestibulum vestibulum. Duis orci massa, venenatis a gravida eget, convallis ac sapien',
															'duration' => '1h',
															'icon' => 'fas fa-thermometer',
															'image' => '',
														),
														2 => array(
															'name' => 'Uplift Your Energy',
															'price' => '30',
															'details' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vivamus vitae fringilla velit. Maecenas in purus ipsum. Integer euismod dui risus, eget porttitor enim molestie ac. Nunc ac sem a mauris vestibulum vestibulum. Duis orci massa, venenatis a gravida eget, convallis ac sapien',
															'duration' => '1h',
															'icon' => 'fas fa-soundcloud',
															'image' => '',
														),
														3 => array(
															'name' => 'Clean Sweep Your Mind',
															'price' => '35',
															'details' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vivamus vitae fringilla velit. Maecenas in purus ipsum. Integer euismod dui risus, eget porttitor enim molestie ac. Nunc ac sem a mauris vestibulum vestibulum. Duis orci massa, venenatis a gravida eget, convallis ac sapien',
															'duration' => '1h',
															'icon' => 'fas fa-play',
															'image' => '',
														),
													)
												),
											)
										),
									),
									'mpwpb_extra_service_active' => 'off',
									'mpwpb_group_extra_service_active' => 'off',
									'mpwpb_extra_service' => array(),
									//Galary Settings
									'mpwpb_display_slider' => 'off',
									'mpwpb_slider_images' => 'off',
								],
							],
							2 => [
								'name' => 'Medical & Dental',
								'post_data' => [//General_settings
									'mpwpb_shortcode_title' => 'Medical & Dental Service',
									'mpwpb_shortcode_sub_title' => 'Choose your medical and dental services easily with affordable price.',
									//date_settings
									'mpwpb_service_start_date' => '2023-03-01',
									'mpwpb_service_end_date' => '2023-08-25',
									'mpwpb_time_slot_length' => '60',
									'mpwpb_capacity_per_session' => '1',
									'mpwpb_default_start_time' => '10',
									'mpwpb_default_end_time' => '18',
									'mpwpb_default_start_break_time' => '13',
									'mpwpb_default_end_break_time' => '15',
									'mpwpb_monday_start_time' => '10',
									'mpwpb_monday_end_time' => '18',
									'mpwpb_monday_start_break_time' => '13',
									'mpwpb_monday_end_break_time' => '15',
									'mpwpb_tuesday_start_time' => '10.5',
									'mpwpb_tuesday_end_time' => '18.5',
									'mpwpb_tuesday_start_break_time' => '13.5',
									'mpwpb_tuesday_end_break_time' => '15.5',
									'mpwpb_wednesday_start_time' => '11',
									'mpwpb_wednesday_end_time' => '19',
									'mpwpb_wednesday_start_break_time' => '14',
									'mpwpb_wednesday_end_break_time' => '16',
									'mpwpb_thursday_start_time' => '10',
									'mpwpb_thursday_end_time' => '18',
									'mpwpb_thursday_start_break_time' => '13',
									'mpwpb_thursday_end_break_time' => '15',
									'mpwpb_off_days' => 'saturday,sunday',
									'mpwpb_off_dates' => array(
										0 => '2023-03-07',
										1 => '2023-03-15',
									),
									//price_settings
									'mpwpb_category_active' => 'off',
									'mpwpb_sub_category_active' => 'off',
									'mpwpb_service_details_active' => 'on',
									'mpwpb_service_duration_active' => 'off',
									'mpwpb_category_text' => '',
									'mpwpb_sub_category_text' => '',
									'mpwpb_service_text' => 'Service',
									'mpwpb_category_infos' => array(
										0 => array(
											'icon' => '',
											'image' => '',
											'category' => '',
											'sub_category' => array(
												0 => array(
													'icon' => '',
													'image' => '',
													'name' => '',
													'service' => array(
														0 => array(
															'name' => 'Fever​',
															'price' => '10',
															'details' => 'Nisl tempus, sollicitudin amet, porttitor erat magna congue dui malesuada vestibulum.',
															'duration' => '',
															'icon' => 'fas fa-ambulance',
															'image' => '',
														),
														1 => array(
															'name' => 'Tiredness',
															'price' => '12',
															'details' => 'Ultrices et ultrices enim nunc, quis pellentesque sit mauris',
															'duration' => '',
															'icon' => 'fas fa-user-md',
															'image' => '',
														),
														2 => array(
															'name' => 'Dry Cough​',
															'price' => '14',
															'details' => 'Nisl tempus, metus, sollicitudin amet, porttitor erat magna congue dui malesuada vestibulum.',
															'duration' => '',
															'icon' => 'fas fa-id-card-alt',
															'image' => '',
														),
														3 => array(
															'name' => 'Shortness of Breath​',
															'price' => '15',
															'details' => 'Nisl tempus, metus, sollicitudin amet, porttitor erat magna congue dui malesuada vestibulum.',
															'duration' => '',
															'icon' => 'fas fa-x-ray',
															'image' => '',
														),
														4 => array(
															'name' => 'Aches and Pains​',
															'price' => '20',
															'details' => 'Nisl tempus, metus, sollicitudin amet, porttitor erat magna congue dui malesuada vestibulum.',
															'duration' => '',
															'icon' => 'fas fa-thermometer',
															'image' => '',
														),
														5 => array(
															'name' => 'Sore Throat​',
															'price' => '22',
															'details' => 'Nisl tempus, metus, sollicitudin amet, porttitor erat magna congue dui malesuada vestibulum.',
															'duration' => '',
															'icon' => 'fas fa-record-vinyl',
															'image' => '',
														),
													)
												),
											)
										),
									),
									'mpwpb_extra_service_active' => 'off',
									'mpwpb_group_extra_service_active' => 'off',
									'mpwpb_extra_service' => array(),
									//Galary Settings
									'mpwpb_display_slider' => 'off',
									'mpwpb_slider_images' => 'off',
								],
							],
							3 => [
								'name' => 'Musical Class',
								'post_data' => [//General_settings
									'mpwpb_shortcode_title' => 'Musical Service',
									'mpwpb_shortcode_sub_title' => 'Find your musical instructor easily with affordable price.',
									//date_settings
									'mpwpb_service_start_date' => '2023-03-01',
									'mpwpb_service_end_date' => '2023-08-25',
									'mpwpb_time_slot_length' => '60',
									'mpwpb_capacity_per_session' => '1',
									'mpwpb_default_start_time' => '10',
									'mpwpb_default_end_time' => '18',
									'mpwpb_default_start_break_time' => '13',
									'mpwpb_default_end_break_time' => '15',
									'mpwpb_monday_start_time' => '10',
									'mpwpb_monday_end_time' => '18',
									'mpwpb_monday_start_break_time' => '13',
									'mpwpb_monday_end_break_time' => '15',
									'mpwpb_tuesday_start_time' => '10.5',
									'mpwpb_tuesday_end_time' => '18.5',
									'mpwpb_tuesday_start_break_time' => '13.5',
									'mpwpb_tuesday_end_break_time' => '15.5',
									'mpwpb_wednesday_start_time' => '11',
									'mpwpb_wednesday_end_time' => '19',
									'mpwpb_wednesday_start_break_time' => '14',
									'mpwpb_wednesday_end_break_time' => '16',
									'mpwpb_thursday_start_time' => '10',
									'mpwpb_thursday_end_time' => '18',
									'mpwpb_thursday_start_break_time' => '13',
									'mpwpb_thursday_end_break_time' => '15',
									'mpwpb_off_days' => 'saturday,sunday',
									'mpwpb_off_dates' => array(
										0 => '2023-03-07',
										1 => '2023-03-15',
									),
									//price_settings
									'mpwpb_category_active' => 'off',
									'mpwpb_sub_category_active' => 'off',
									'mpwpb_service_details_active' => 'on',
									'mpwpb_service_duration_active' => 'on',
									'mpwpb_category_text' => '',
									'mpwpb_sub_category_text' => '',
									'mpwpb_service_text' => 'Service',
									'mpwpb_category_infos' => array(
										0 => array(
											'icon' => '',
											'image' => '',
											'category' => '',
											'sub_category' => array(
												0 => array(
													'icon' => '',
													'image' => '',
													'name' => '',
													'service' => array(
														0 => array(
															'name' => 'Classical Class (3 Months)',
															'price' => '10',
															'details' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vivamus vitae fringilla velit. Maecenas in purus ipsum. Integer euismod dui risus, eget porttitor enim molestie ac. Nunc ac sem a mauris vestibulum vestibulum. Duis orci massa, venenatis a gravida eget, convallis ac sapien',
															'duration' => '1h',
															'icon' => 'fas fa-music',
															'image' => '',
														),
														1 => array(
															'name' => 'Jazz Classes (2 Months)',
															'price' => '12',
															'details' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vivamus vitae fringilla velit. Maecenas in purus ipsum. Integer euismod dui risus, eget porttitor enim molestie ac. Nunc ac sem a mauris vestibulum vestibulum. Duis orci massa, venenatis a gravida eget, convallis ac sapien',
															'duration' => '1h',
															'icon' => 'fas fa-soundcloud',
															'image' => '',
														),
														2 => array(
															'name' => 'Classical Private Tutor​',
															'price' => '14',
															'details' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vivamus vitae fringilla velit. Maecenas in purus ipsum. Integer euismod dui risus, eget porttitor enim molestie ac. Nunc ac sem a mauris vestibulum vestibulum. Duis orci massa, venenatis a gravida eget, convallis ac sapien',
															'duration' => '1h',
															'icon' => 'fas fa-record-vinyl',
															'image' => '',
														),
														3 => array(
															'name' => 'Pop Songs Classes​',
															'price' => '15',
															'details' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vivamus vitae fringilla velit. Maecenas in purus ipsum. Integer euismod dui risus, eget porttitor enim molestie ac. Nunc ac sem a mauris vestibulum vestibulum. Duis orci massa, venenatis a gravida eget, convallis ac sapien',
															'duration' => '1h',
															'icon' => 'fas fa-play',
															'image' => '',
														),
														4 => array(
															'name' => 'Rock Music Piano Class​',
															'price' => '20',
															'details' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vivamus vitae fringilla velit. Maecenas in purus ipsum. Integer euismod dui risus, eget porttitor enim molestie ac. Nunc ac sem a mauris vestibulum vestibulum. Duis orci massa, venenatis a gravida eget, convallis ac sapien',
															'duration' => '1h',
															'icon' => 'fas fa-dolly-flatbed',
															'image' => '',
														),
														5 => array(
															'name' => 'Classical Advance Class​',
															'price' => '22',
															'details' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vivamus vitae fringilla velit. Maecenas in purus ipsum. Integer euismod dui risus, eget porttitor enim molestie ac. Nunc ac sem a mauris vestibulum vestibulum. Duis orci massa, venenatis a gravida eget, convallis ac sapien',
															'duration' => '1h',
															'icon' => 'fas fa-warehouse',
															'image' => '',
														),
														6 => array(
															'name' => 'Classical Private tutor​',
															'price' => '22',
															'details' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vivamus vitae fringilla velit. Maecenas in purus ipsum. Integer euismod dui risus, eget porttitor enim molestie ac. Nunc ac sem a mauris vestibulum vestibulum. Duis orci massa, venenatis a gravida eget, convallis ac sapien',
															'duration' => '1h',
															'icon' => 'fas fa-wind',
															'image' => '',
														),
														7 => array(
															'name' => 'Pop Songs Advance (Annually)​',
															'price' => '22',
															'details' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vivamus vitae fringilla velit. Maecenas in purus ipsum. Integer euismod dui risus, eget porttitor enim molestie ac. Nunc ac sem a mauris vestibulum vestibulum. Duis orci massa, venenatis a gravida eget, convallis ac sapien',
															'duration' => '1h',
															'icon' => 'fas fa-anchor',
															'image' => '',
														),
														8 => array(
															'name' => 'Rock Music Piano Advance​',
															'price' => '22',
															'details' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vivamus vitae fringilla velit. Maecenas in purus ipsum. Integer euismod dui risus, eget porttitor enim molestie ac. Nunc ac sem a mauris vestibulum vestibulum. Duis orci massa, venenatis a gravida eget, convallis ac sapien',
															'duration' => '1h',
															'icon' => 'fas fa-compass',
															'image' => '',
														),
													)
												),
											)
										),
									),
									'mpwpb_extra_service_active' => 'off',
									'mpwpb_group_extra_service_active' => 'off',
									'mpwpb_extra_service' => array(),
									//Galary Settings
									'mpwpb_display_slider' => 'off',
									'mpwpb_slider_images' => 'off',
								],
							],
							4 => [
								'name' => 'Car Wash',
								'post_data' => [//General_settings
									'mpwpb_shortcode_title' => 'Car Wash Service',
									'mpwpb_shortcode_sub_title' => 'Wash your car easily with affordable price',
									//date_settings
									'mpwpb_service_start_date' => '2023-03-01',
									'mpwpb_service_end_date' => '2023-08-25',
									'mpwpb_time_slot_length' => '60',
									'mpwpb_capacity_per_session' => '1',
									'mpwpb_default_start_time' => '10',
									'mpwpb_default_end_time' => '18',
									'mpwpb_default_start_break_time' => '13',
									'mpwpb_default_end_break_time' => '15',
									'mpwpb_monday_start_time' => '10',
									'mpwpb_monday_end_time' => '18',
									'mpwpb_monday_start_break_time' => '13',
									'mpwpb_monday_end_break_time' => '15',
									'mpwpb_tuesday_start_time' => '10.5',
									'mpwpb_tuesday_end_time' => '18.5',
									'mpwpb_tuesday_start_break_time' => '13.5',
									'mpwpb_tuesday_end_break_time' => '15.5',
									'mpwpb_wednesday_start_time' => '11',
									'mpwpb_wednesday_end_time' => '19',
									'mpwpb_wednesday_start_break_time' => '14',
									'mpwpb_wednesday_end_break_time' => '16',
									'mpwpb_thursday_start_time' => '10',
									'mpwpb_thursday_end_time' => '18',
									'mpwpb_thursday_start_break_time' => '13',
									'mpwpb_thursday_end_break_time' => '15',
									'mpwpb_off_days' => 'saturday,sunday',
									'mpwpb_off_dates' => array(
										0 => '2023-03-07',
										1 => '2023-03-15',
									),
									//price_settings
									'mpwpb_category_active' => 'on',
									'mpwpb_sub_category_active' => 'on',
									'mpwpb_service_details_active' => 'on',
									'mpwpb_service_duration_active' => 'on',
									'mpwpb_category_text' => 'Wash Type',
									'mpwpb_sub_category_text' => 'Car Type',
									'mpwpb_service_text' => 'Service',
									'mpwpb_category_infos' => array(
										0 => array(
											'icon' => 'fas fa-luggage-cart',
											'image' => '',
											'category' => 'Car Wash Polish',
											'sub_category' => array(
												0 => array(
													'icon' => 'fas fa-car-side',
													'image' => '',
													'name' => 'Car Type SUV',
													'service' => array(
														0 => array(
															'name' => 'Hand Wash',
															'price' => '100',
															'details' => 'Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry\'s standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960s with the release of Letraset sheets containing Lorem Ipsum passages, and more recently with desktop publishing software like Aldus PageMaker including versions of Lorem Ipsum.',
															'duration' => '1h',
															'icon' => 'fas fa-gifts',
															'image' => '',
														),
														1 => array(
															'name' => 'Exterior Handwax',
															'price' => '200',
															'details' => 'Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry\'s standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960s with the release of Letraset sheets containing Lorem Ipsum passages, and more recently with desktop publishing software like Aldus PageMaker including versions of Lorem Ipsum.',
															'duration' => '1h',
															'icon' => 'fas fa-soundcloud',
															'image' => '',
														),
														2 => array(
															'name' => 'Hand Wash Wax',
															'price' => '300',
															'details' => 'Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry\'s standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960s with the release of Letraset sheets containing Lorem Ipsum passages, and more recently with desktop publishing software like Aldus PageMaker including versions of Lorem Ipsum.',
															'duration' => '1h',
															'icon' => 'fas fa-play',
															'image' => '',
														),
													)
												),
												1 => array(
													'icon' => 'fas fa-car',
													'image' => '',
													'name' => 'Car Type Zeep',
													'service' => array(
														0 => array(
															'name' => 'Hand Wash',
															'price' => '130',
															'details' => 'Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry\'s standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960s with the release of Letraset sheets containing Lorem Ipsum passages, and more recently with desktop publishing software like Aldus PageMaker including versions of Lorem Ipsum.',
															'duration' => '1h',
															'icon' => 'fas fa-gifts',
															'image' => '',
														),
														1 => array(
															'name' => 'Exterior Handwax',
															'price' => '250',
															'details' => 'Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry\'s standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960s with the release of Letraset sheets containing Lorem Ipsum passages, and more recently with desktop publishing software like Aldus PageMaker including versions of Lorem Ipsum.',
															'duration' => '1h',
															'icon' => 'fas fa-dolly-flatbed',
															'image' => '',
														),
														2 => array(
															'name' => 'Hand Wash Wax',
															'price' => '350',
															'details' => 'Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry\'s standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960s with the release of Letraset sheets containing Lorem Ipsum passages, and more recently with desktop publishing software like Aldus PageMaker including versions of Lorem Ipsum.',
															'duration' => '1h',
															'icon' => 'fas fa-wind',
															'image' => '',
														),
													)
												),
												2 => array(
													'icon' => 'fas fa-compass',
													'image' => '',
													'name' => 'Car Type Sedan',
													'service' => array(
														0 => array(
															'name' => 'Hand Wash',
															'price' => '130',
															'details' => 'Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry\'s standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960s with the release of Letraset sheets containing Lorem Ipsum passages, and more recently with desktop publishing software like Aldus PageMaker including versions of Lorem Ipsum.',
															'duration' => '1h',
															'icon' => 'fas fa-gifts',
															'image' => '',
														),
														1 => array(
															'name' => 'Exterior Handwax',
															'price' => '250',
															'details' => 'Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry\'s standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960s with the release of Letraset sheets containing Lorem Ipsum passages, and more recently with desktop publishing software like Aldus PageMaker including versions of Lorem Ipsum.',
															'duration' => '1h',
															'icon' => 'fas fa-coffee',
															'image' => '',
														),
														2 => array(
															'name' => 'Hand Wash Wax',
															'price' => '350',
															'details' => 'Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry\'s standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960s with the release of Letraset sheets containing Lorem Ipsum passages, and more recently with desktop publishing software like Aldus PageMaker including versions of Lorem Ipsum.',
															'duration' => '1h',
															'icon' => 'fas fa-shuttle-van',
															'image' => '',
														),
													)
												),
											)
										),
										1 => array(
											'icon' => 'fas fa-ambulance',
											'image' => '',
											'category' => 'Car Detailing',
											'sub_category' => array(
												0 => array(
													'icon' => 'fas fa-car-side',
													'image' => '',
													'name' => 'Car Type Sedan',
													'service' => array(
														0 => array(
															'name' => 'Standard Interior',
															'price' => '700',
															'details' => 'Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry\'s standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960s with the release of Letraset sheets containing Lorem Ipsum passages, and more recently with desktop publishing software like Aldus PageMaker including versions of Lorem Ipsum.',
															'duration' => '1h',
															'icon' => 'fas fa-gifts',
															'image' => '',
														),
														1 => array(
															'name' => 'Premium Interior',
															'price' => '750',
															'details' => 'Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry\'s standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960s with the release of Letraset sheets containing Lorem Ipsum passages, and more recently with desktop publishing software like Aldus PageMaker including versions of Lorem Ipsum.',
															'duration' => '1h',
															'icon' => 'fas fa-user-md',
															'image' => '',
														),
														2 => array(
															'name' => 'Complete Detail',
															'price' => '900',
															'details' => 'Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry\'s standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960s with the release of Letraset sheets containing Lorem Ipsum passages, and more recently with desktop publishing software like Aldus PageMaker including versions of Lorem Ipsum.',
															'duration' => '1h',
															'icon' => 'fas fa-id-card-alt',
															'image' => '',
														),
													)
												),
											)
										),
									),
									'mpwpb_extra_service_active' => 'on',
									'mpwpb_group_extra_service_active' => 'off',
									'mpwpb_extra_service' => array(
										0 => array(
											'group_service' => '',
											'group_service_info' => array(
												0 => array(
													'name' => 'Tyre Pressure Checking',
													'qty' => 5,
													'price' => 5,
													'details' => 'A gentle but detailed hand wash procedure that keeps your car looking its best, longer. This service includes: Hand wash of the body Windows and mirrors Rims Tire & Wheel shine',
													'icon' => 'fas fa-boxes',
													'image' => '',
												),
												1 => array(
													'name' => 'Tyre Changing',
													'qty' => 10,
													'price' => 10,
													'details' => 'A gentle but detailed hand wash procedure that keeps your car looking its best, longer. This service includes: Hand wash of the body Windows and mirrors Rims Tire & Wheel shine',
													'icon' => 'fas fa-baby-carriage',
													'image' => '',
												),
												2 => array(
													'name' => 'Odor Removal',
													'qty' => 10,
													'price' => 10,
													'details' => 'A gentle but detailed hand wash procedure that keeps your car looking its best, longer. This service includes: Hand wash of the body Windows and mirrors Rims Tire & Wheel shine',
													'icon' => 'fas fa-wheelchair',
													'image' => '',
												),
											)
										)
									),
									//Galary Settings
									'mpwpb_display_slider' => 'off',
									'mpwpb_slider_images' => 'off',
								],
							],
							5 => [
								'name' => 'Repair service',
								'post_data' => [//General_settings
									'mpwpb_shortcode_title' => 'Repair Service',
									'mpwpb_shortcode_sub_title' => 'Repair anything easily with effordable price',
									//date_settings
									'mpwpb_service_start_date' => '2023-03-01',
									'mpwpb_service_end_date' => '2023-08-25',
									'mpwpb_time_slot_length' => '60',
									'mpwpb_capacity_per_session' => '1',
									'mpwpb_default_start_time' => '10',
									'mpwpb_default_end_time' => '18',
									'mpwpb_default_start_break_time' => '13',
									'mpwpb_default_end_break_time' => '15',
									'mpwpb_monday_start_time' => '10',
									'mpwpb_monday_end_time' => '18',
									'mpwpb_monday_start_break_time' => '13',
									'mpwpb_monday_end_break_time' => '15',
									'mpwpb_tuesday_start_time' => '10.5',
									'mpwpb_tuesday_end_time' => '18.5',
									'mpwpb_tuesday_start_break_time' => '13.5',
									'mpwpb_tuesday_end_break_time' => '15.5',
									'mpwpb_wednesday_start_time' => '11',
									'mpwpb_wednesday_end_time' => '19',
									'mpwpb_wednesday_start_break_time' => '14',
									'mpwpb_wednesday_end_break_time' => '16',
									'mpwpb_thursday_start_time' => '10',
									'mpwpb_thursday_end_time' => '18',
									'mpwpb_thursday_start_break_time' => '13',
									'mpwpb_thursday_end_break_time' => '15',
									'mpwpb_off_days' => 'saturday,sunday',
									'mpwpb_off_dates' => array(
										0 => '2023-03-07',
										1 => '2023-03-15',
									),
									//price_settings
									'mpwpb_category_active' => 'on',
									'mpwpb_sub_category_active' => 'off',
									'mpwpb_service_details_active' => 'on',
									'mpwpb_service_duration_active' => 'on',
									'mpwpb_category_text' => 'Service Type',
									'mpwpb_sub_category_text' => '',
									'mpwpb_service_text' => 'Service Details',
									'mpwpb_category_infos' => array(
										0 => array(
											'icon' => 'fas fa-car-side',
											'image' => '',
											'category' => 'Car Maintenance',
											'sub_category' => array(
												0 => array(
													'icon' => '',
													'image' => '',
													'name' => '',
													'service' => array(
														0 => array(
															'name' => 'Auto Maintenance Services​',
															'price' => '100',
															'details' => 'Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry\'s standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960s with the release of Letraset sheets containing Lorem Ipsum passages, and more recently with desktop publishing software like Aldus PageMaker including versions of Lorem Ipsum.',
															'duration' => '1h',
															'icon' => 'fas fa-gifts',
															'image' => '',
														),
														1 => array(
															'name' => 'Oil Filter Change',
															'price' => '200',
															'details' => 'Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry\'s standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960s with the release of Letraset sheets containing Lorem Ipsum passages, and more recently with desktop publishing software like Aldus PageMaker including versions of Lorem Ipsum.',
															'duration' => '1h',
															'icon' => 'fas fa-ambulance',
															'image' => '',
														),
														2 => array(
															'name' => 'Cabin Air Filter Replacement',
															'price' => '300',
															'details' => 'Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry\'s standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960s with the release of Letraset sheets containing Lorem Ipsum passages, and more recently with desktop publishing software like Aldus PageMaker including versions of Lorem Ipsum.',
															'duration' => '1h',
															'icon' => 'fas fa-x-ray',
															'image' => '',
														),
														3 => array(
															'name' => 'Engine Performance',
															'price' => '300',
															'details' => 'Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry\'s standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960s with the release of Letraset sheets containing Lorem Ipsum passages, and more recently with desktop publishing software like Aldus PageMaker including versions of Lorem Ipsum.',
															'duration' => '1h',
															'icon' => 'fas fa-thermometer',
															'image' => '',
														),
													)
												),
											)
										),
										1 => array(
											'icon' => 'fas fa-running',
											'image' => '',
											'category' => 'Car Repair',
											'sub_category' => array(
												0 => array(
													'icon' => '',
													'image' => '',
													'name' => '',
													'service' => array(
														0 => array(
															'name' => 'Brake Repair Pads Rotors',
															'price' => '700',
															'details' => 'Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry\'s standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960s with the release of Letraset sheets containing Lorem Ipsum passages, and more recently with desktop publishing software like Aldus PageMaker including versions of Lorem Ipsum.',
															'duration' => '1h',
															'icon' => 'fas fa-gifts',
															'image' => '',
														),
														1 => array(
															'name' => 'Air Conditioning Services​​',
															'price' => '750',
															'details' => 'Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry\'s standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960s with the release of Letraset sheets containing Lorem Ipsum passages, and more recently with desktop publishing software like Aldus PageMaker including versions of Lorem Ipsum.',
															'duration' => '1h',
															'icon' => 'fas fa-user-md',
															'image' => '',
														),
														2 => array(
															'name' => 'Body Repair Painting',
															'price' => '900',
															'details' => 'Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry\'s standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960s with the release of Letraset sheets containing Lorem Ipsum passages, and more recently with desktop publishing software like Aldus PageMaker including versions of Lorem Ipsum.',
															'duration' => '1h',
															'icon' => 'fas fa-thermometer',
															'image' => '',
														),
													)
												),
											)
										),
									),
									'mpwpb_extra_service_active' => 'on',
									'mpwpb_group_extra_service_active' => 'off',
									'mpwpb_extra_service' => array(
										0 => array(
											'group_service' => '',
											'group_service_info' => array(
												0 => array(
													'name' => 'Driver Seating Chair',
													'qty' => 5,
													'price' => 5,
													'details' => 'A gentle but detailed hand wash procedure that keeps your car looking its best, longer. This service includes: Hand wash of the body Windows and mirrors Rims Tire & Wheel shine',
													'icon' => 'fas fa-boxes',
													'image' => '',
												),
												1 => array(
													'name' => 'Lunch box for driver',
													'qty' => 10,
													'price' => 10,
													'details' => 'A gentle but detailed hand wash procedure that keeps your car looking its best, longer. This service includes: Hand wash of the body Windows and mirrors Rims Tire & Wheel shine',
													'icon' => 'fas fa-wheelchair',
													'image' => '',
												),
											)
										)
									),
									//Galary Settings
									'mpwpb_display_slider' => 'off',
									'mpwpb_slider_images' => 'off',
								],
							],
							6 => [
								'name' => 'Hair Cut',
								'post_data' => [//General_settings
									'mpwpb_shortcode_title' => 'Hair Cut Service',
									'mpwpb_shortcode_sub_title' => 'Cut your hair beautifully with affordable price',
									//date_settings
									'mpwpb_service_start_date' => '2023-03-01',
									'mpwpb_service_end_date' => '2023-08-25',
									'mpwpb_time_slot_length' => '60',
									'mpwpb_capacity_per_session' => '1',
									'mpwpb_default_start_time' => '10',
									'mpwpb_default_end_time' => '18',
									'mpwpb_default_start_break_time' => '13',
									'mpwpb_default_end_break_time' => '15',
									'mpwpb_monday_start_time' => '10',
									'mpwpb_monday_end_time' => '18',
									'mpwpb_monday_start_break_time' => '13',
									'mpwpb_monday_end_break_time' => '15',
									'mpwpb_tuesday_start_time' => '10.5',
									'mpwpb_tuesday_end_time' => '18.5',
									'mpwpb_tuesday_start_break_time' => '13.5',
									'mpwpb_tuesday_end_break_time' => '15.5',
									'mpwpb_wednesday_start_time' => '11',
									'mpwpb_wednesday_end_time' => '19',
									'mpwpb_wednesday_start_break_time' => '14',
									'mpwpb_wednesday_end_break_time' => '16',
									'mpwpb_thursday_start_time' => '10',
									'mpwpb_thursday_end_time' => '18',
									'mpwpb_thursday_start_break_time' => '13',
									'mpwpb_thursday_end_break_time' => '15',
									'mpwpb_off_days' => 'saturday,sunday',
									'mpwpb_off_dates' => array(
										0 => '2023-03-07',
										1 => '2023-03-15',
									),
									//price_settings
									'mpwpb_category_active' => 'off',
									'mpwpb_sub_category_active' => 'off',
									'mpwpb_service_details_active' => 'on',
									'mpwpb_service_duration_active' => 'on',
									'mpwpb_category_text' => '',
									'mpwpb_sub_category_text' => '',
									'mpwpb_service_text' => 'Service Details',
									'mpwpb_category_infos' => array(
										0 => array(
											'icon' => '',
											'image' => '',
											'category' => '',
											'sub_category' => array(
												0 => array(
													'icon' => '',
													'image' => '',
													'name' => '',
													'service' => array(
														0 => array(
															'name' => 'Fade Haircut​',
															'price' => '100',
															'details' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vivamus vitae fringilla velit. Maecenas in purus ipsum. Integer euismod dui risus, eget porttitor enim molestie ac. Nunc ac sem a mauris vestibulum vestibulum. Duis orci massa, venenatis a gravida eget, convallis ac sapien',
															'duration' => '1h',
															'icon' => 'fas fa-gifts',
															'image' => '',
														),
														1 => array(
															'name' => 'Taper Haircut',
															'price' => '200',
															'details' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vivamus vitae fringilla velit. Maecenas in purus ipsum. Integer euismod dui risus, eget porttitor enim molestie ac. Nunc ac sem a mauris vestibulum vestibulum. Duis orci massa, venenatis a gravida eget, convallis ac sapien',
															'duration' => '1h',
															'icon' => 'fas fa-baby-carriage',
															'image' => '',
														),
														2 => array(
															'name' => 'Buzz Cut',
															'price' => '300',
															'details' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vivamus vitae fringilla velit. Maecenas in purus ipsum. Integer euismod dui risus, eget porttitor enim molestie ac. Nunc ac sem a mauris vestibulum vestibulum. Duis orci massa, venenatis a gravida eget, convallis ac sapien',
															'duration' => '1h',
															'icon' => 'fas fa-running',
															'image' => '',
														),
														3 => array(
															'name' => 'Crew Cut',
															'price' => '300',
															'details' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vivamus vitae fringilla velit. Maecenas in purus ipsum. Integer euismod dui risus, eget porttitor enim molestie ac. Nunc ac sem a mauris vestibulum vestibulum. Duis orci massa, venenatis a gravida eget, convallis ac sapien',
															'duration' => '1h',
															'icon' => 'fas fa-thermometer',
															'image' => '',
														),
													)
												),
											)
										),
									),
									'mpwpb_extra_service_active' => 'on',
									'mpwpb_group_extra_service_active' => 'off',
									'mpwpb_extra_service' => array(
										0 => array(
											'group_service' => '',
											'group_service_info' => array(
												0 => array(
													'name' => 'Pre Hair Wash',
													'qty' => 5,
													'price' => 5,
													'details' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vivamus vitae fringilla velit. Maecenas in purus ipsum. Integer euismod dui risus, eget porttitor enim molestie ac. Nunc ac sem a mauris vestibulum vestibulum. Duis orci massa, venenatis a gravida eget, convallis ac sapien',
													'icon' => 'fas fa-boxes',
													'image' => '',
												),
												1 => array(
													'name' => 'After Hair Wash',
													'qty' => 10,
													'price' => 10,
													'details' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vivamus vitae fringilla velit. Maecenas in purus ipsum. Integer euismod dui risus, eget porttitor enim molestie ac. Nunc ac sem a mauris vestibulum vestibulum. Duis orci massa, venenatis a gravida eget, convallis ac sapien',
													'icon' => 'fas fa-wheelchair',
													'image' => '',
												),
												2 => array(
													'name' => 'Face Wash',
													'qty' => 10,
													'price' => 10,
													'details' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vivamus vitae fringilla velit. Maecenas in purus ipsum. Integer euismod dui risus, eget porttitor enim molestie ac. Nunc ac sem a mauris vestibulum vestibulum. Duis orci massa, venenatis a gravida eget, convallis ac sapien',
													'icon' => 'fas fa-thermometer',
													'image' => '',
												),
											)
										)
									),
									//Galary Settings
									'mpwpb_display_slider' => 'off',
									'mpwpb_slider_images' => 'off',
								],
							],
						]
					]
				];
			}
		}
	}


	