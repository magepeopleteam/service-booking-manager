<?php
	/*
   * @Author 		rubelcuet10@gmail.com
   * Copyright: 	mage-people.com
   */
	if (!defined('ABSPATH')) {
		die;
	} // Cannot access pages directly.

	if (!class_exists('MPWPB_Booking_Notes')) {
		/**
		 * Internal note thread attached to a booking -- private communication
		 * between admin and the staff member assigned to it (never shown to
		 * the customer). Read separately from MPWPB_Booking_History: history
		 * is a compact audit log of field changes, this is a full two-way
		 * conversation with its own read/unread state per side, so it gets
		 * its own table rather than being squeezed into that one.
		 *
		 * Shared by MPWPB_Order_List / MPWPB_Service_Queue (wp-admin) and
		 * MPWPB_Staff_DashBoard's "My Appointment" tab (front-end) -- the
		 * AJAX handlers live here, once, rather than being duplicated in
		 * each of those classes, since the read/write logic is identical
		 * regardless of which screen opened the thread.
		 */
		class MPWPB_Booking_Notes {

			const ROLE_ADMIN = 'admin';
			const ROLE_STAFF = 'staff';

			// Bump whenever the CREATE TABLE below changes shape -- see the
			// identical reasoning in MPWPB_Booking_History::DB_VERSION.
			const DB_VERSION = '1.0';

			public function __construct() {
				add_action('wp_ajax_mpwpb_get_booking_notes', array($this, 'ajax_get_notes'));
				add_action('wp_ajax_mpwpb_add_booking_note', array($this, 'ajax_add_note'));
			}

			private static function maybe_create_table(): void {
				global $wpdb;
				$table_name = $wpdb->prefix . 'mpwpb_booking_notes';
				$installed_version = get_option('mpwpb_booking_notes_db_version');

				if ($installed_version !== self::DB_VERSION || $wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
					$charset_collate = $wpdb->get_charset_collate();

					$sql = "CREATE TABLE $table_name (
						id bigint(20) NOT NULL AUTO_INCREMENT,
						booking_id bigint(20) NOT NULL,
						sender_user_id bigint(20) NOT NULL,
						sender_role varchar(20) NOT NULL,
						message text NOT NULL,
						is_read_by_admin tinyint(1) NOT NULL DEFAULT 0,
						is_read_by_staff tinyint(1) NOT NULL DEFAULT 0,
						created_at datetime NOT NULL,
						PRIMARY KEY  (id),
						KEY booking_id (booking_id)
					) $charset_collate;";

					require_once ABSPATH . 'wp-admin/includes/upgrade.php';
					dbDelta($sql);
					update_option('mpwpb_booking_notes_db_version', self::DB_VERSION);
				}
			}

			/**
			 * @param string $role self::ROLE_ADMIN|self::ROLE_STAFF
			 * @return int|false New note ID, or false on failure.
			 */
			public static function add_note($booking_id, $user_id, $role, $message) {
				global $wpdb;
				self::maybe_create_table();
				$is_admin = $role === self::ROLE_ADMIN;
				$inserted = $wpdb->insert(
					$wpdb->prefix . 'mpwpb_booking_notes',
					array(
						'booking_id'       => (int) $booking_id,
						'sender_user_id'   => (int) $user_id,
						'sender_role'      => $role,
						'message'          => $message,
						// The sender has, by definition, already read their
						// own message; it's unread for whoever's on the
						// other side until they open the thread.
						'is_read_by_admin' => $is_admin ? 1 : 0,
						'is_read_by_staff' => $is_admin ? 0 : 1,
						'created_at'       => current_time('mysql'),
					),
					array('%d', '%d', '%s', '%s', '%d', '%d', '%s')
				);
				return $inserted ? $wpdb->insert_id : false;
			}

			public static function get_for_booking($booking_id): array {
				global $wpdb;
				self::maybe_create_table();
				return $wpdb->get_results($wpdb->prepare(
					"SELECT * FROM {$wpdb->prefix}mpwpb_booking_notes WHERE booking_id = %d ORDER BY created_at ASC, id ASC",
					(int) $booking_id
				));
			}

			/**
			 * Marks every note in this booking's thread as read for the
			 * given side -- called when that side actually opens the thread,
			 * not merely when a badge is rendered.
			 */
			public static function mark_read($booking_id, $role): void {
				global $wpdb;
				self::maybe_create_table();
				$column = $role === self::ROLE_ADMIN ? 'is_read_by_admin' : 'is_read_by_staff';
				$wpdb->query($wpdb->prepare(
					"UPDATE {$wpdb->prefix}mpwpb_booking_notes SET {$column} = 1 WHERE booking_id = %d AND {$column} = 0",
					(int) $booking_id
				));
			}

			public static function get_unread_count($booking_id, $role): int {
				global $wpdb;
				self::maybe_create_table();
				$column = $role === self::ROLE_ADMIN ? 'is_read_by_admin' : 'is_read_by_staff';
				return (int) $wpdb->get_var($wpdb->prepare(
					"SELECT COUNT(*) FROM {$wpdb->prefix}mpwpb_booking_notes WHERE booking_id = %d AND {$column} = 0",
					(int) $booking_id
				));
			}

			/**
			 * Unread counts for many bookings in one query -- Order List/
			 * Service Queue/My Appointment all render a whole page of rows
			 * at once, so a per-row query here would be an N+1.
			 *
			 * @return array<int,int> booking_id => unread count (bookings
			 *                        with zero unread are simply absent).
			 */
			public static function get_unread_counts_bulk(array $booking_ids, $role): array {
				global $wpdb;
				self::maybe_create_table();
				$booking_ids = array_values(array_filter(array_map('intval', $booking_ids)));
				if (empty($booking_ids)) {
					return array();
				}
				$column = $role === self::ROLE_ADMIN ? 'is_read_by_admin' : 'is_read_by_staff';
				$placeholders = implode(',', array_fill(0, count($booking_ids), '%d'));
				$sql = "SELECT booking_id, COUNT(*) as unread FROM {$wpdb->prefix}mpwpb_booking_notes WHERE {$column} = 0 AND booking_id IN ($placeholders) GROUP BY booking_id";
				$rows = $wpdb->get_results($wpdb->prepare($sql, $booking_ids));
				$out = array();
				foreach ($rows as $row) {
					$out[(int) $row->booking_id] = (int) $row->unread;
				}
				return $out;
			}

			/**
			 * Resolves what role (if any) the current user may read/write
			 * this booking's notes as. Deliberately mirrors the ownership
			 * check added to MPWPB_Order_List::update_service_status() --
			 * an admin may act on any booking, a staff member only on one
			 * actually assigned to them, everyone else gets nothing (this is
			 * never shown to customers).
			 *
			 * @return string|false self::ROLE_ADMIN|self::ROLE_STAFF|false
			 */
			private function resolve_role_for_booking($booking_id) {
				if (!$booking_id || get_post_type($booking_id) !== 'mpwpb_booking') {
					return false;
				}
				if (current_user_can('manage_options')) {
					return self::ROLE_ADMIN;
				}
				$user = wp_get_current_user();
				if (in_array('mpwpb_staff', (array) $user->roles, true)) {
					$assigned_staff_id = (int) get_post_meta($booking_id, 'mpwpb_staff_term_id', true);
					if ($assigned_staff_id && $assigned_staff_id === (int) $user->ID) {
						return self::ROLE_STAFF;
					}
				}
				return false;
			}

			/**
			 * Accepts either of the two nonce actions already localized for
			 * these two contexts (mpwpb_admin_ajax on wp-admin pages,
			 * mpwpb_dashboard on the front-end staff dashboard) rather than
			 * introducing a third, since this endpoint is reachable from
			 * both.
			 */
			private function verify_nonce_or_die(): void {
				$nonce = isset($_REQUEST['nonce']) ? sanitize_text_field(wp_unslash($_REQUEST['nonce'])) : '';
				if (!wp_verify_nonce($nonce, 'mpwpb_admin_nonce') && !wp_verify_nonce($nonce, 'mpwpb_dashboard_nonce')) {
					wp_send_json_error(array('message' => esc_html__('Security check failed.', 'service-booking-manager')));
				}
			}

			private static function format_notes_for_js($notes): array {
				$out = array();
				foreach ($notes as $note) {
					$user = get_userdata($note->sender_user_id);
					$out[] = array(
						'id'          => (int) $note->id,
						'role'        => $note->sender_role,
						'sender_name' => $user ? $user->display_name : esc_html__('Unknown', 'service-booking-manager'),
						'message'     => $note->message,
						'created_at'  => MPWPB_Global_Function::date_format($note->created_at) . ' ' . MPWPB_Global_Function::date_format($note->created_at, 'time'),
					);
				}
				return $out;
			}

			/**
			 * Fetches a booking's thread and, as a side effect, marks it
			 * read for whichever side is asking -- opening the thread *is*
			 * the "read" action here, there's no separate mark-read step.
			 */
			public function ajax_get_notes(): void {
				$this->verify_nonce_or_die();
				$booking_id = isset($_REQUEST['booking_id']) ? absint($_REQUEST['booking_id']) : 0;
				$role = $this->resolve_role_for_booking($booking_id);
				if (!$role) {
					wp_send_json_error(array('message' => esc_html__('You do not have permission to view this.', 'service-booking-manager')));
				}
				self::mark_read($booking_id, $role);
				wp_send_json_success(array(
					'notes'       => self::format_notes_for_js(self::get_for_booking($booking_id)),
					'viewer_role' => $role,
				));
			}

			public function ajax_add_note(): void {
				$this->verify_nonce_or_die();
				$booking_id = isset($_REQUEST['booking_id']) ? absint($_REQUEST['booking_id']) : 0;
				$message = isset($_REQUEST['message']) ? sanitize_textarea_field(wp_unslash($_REQUEST['message'])) : '';
				$role = $this->resolve_role_for_booking($booking_id);
				if (!$role) {
					wp_send_json_error(array('message' => esc_html__('You do not have permission to do this.', 'service-booking-manager')));
				}
				if ($message === '') {
					wp_send_json_error(array('message' => esc_html__('Please enter a message.', 'service-booking-manager')));
				}
				self::add_note($booking_id, get_current_user_id(), $role, $message);
				// The sender has already seen everything in the thread up to
				// and including what they just sent.
				self::mark_read($booking_id, $role);
				wp_send_json_success(array(
					'notes'       => self::format_notes_for_js(self::get_for_booking($booking_id)),
					'viewer_role' => $role,
				));
			}
		}
		new MPWPB_Booking_Notes();
	}
