<?php
	/*
   * @Author 		engr.sumonazma@gmail.com
   * Copyright: 	mage-people.com
   */
	if (!defined('ABSPATH')) {
		die;
	} // Cannot access pages directly.
	if (!class_exists('MPWPB_Coupon_Function')) {
		class MPWPB_Coupon_Function {
			public static function normalize_code($code): string {
				return strtoupper(trim((string) $code));
			}
			/**
			 * Looks up a published coupon by its code. Returns 0 if not found.
			 */
			public static function find_by_code($code): int {
				$code = self::normalize_code($code);
				if (!$code) {
					return 0;
				}
				$query = new WP_Query([
					'post_type' => 'mpwpb_coupon',
					'post_status' => 'publish',
					'posts_per_page' => 1,
					'no_found_rows' => true,
					'meta_query' => [
						[
							'key' => 'mpwpb_coupon_code',
							'value' => $code,
						],
					],
				]);
				$posts = $query->posts;
				return !empty($posts) ? (int) $posts[0]->ID : 0;
			}
			/**
			 * Flat list of every service across every mpwpb_item post, for the
			 * coupon "Services" tab's picker. Selector format "{item_post_id}:
			 * {service_index}" mirrors the 1-based indexing MPWPB_Function::
			 * get_service_name()/get_price() already use.
			 */
			public static function get_all_services_flat(): array {
				$options = [];
				$items = get_posts([
					'post_type' => MPWPB_Function::get_cpt(),
					'post_status' => ['publish', 'draft'],
					'posts_per_page' => -1,
					'orderby' => 'title',
					'order' => 'ASC',
				]);
				foreach ($items as $item) {
					$services = MPWPB_Global_Function::get_post_info($item->ID, 'mpwpb_service', []);
					if (!is_array($services)) {
						continue;
					}
					foreach ($services as $key => $service) {
						$name = is_array($service) && array_key_exists('name', $service) ? $service['name'] : '';
						if ($name === '') {
							continue;
						}
						$options[] = [
							'value' => $item->ID . ':' . ($key + 1),
							'label' => $name . ' — ' . $item->post_title,
						];
					}
				}
				return $options;
			}
		}
	}
