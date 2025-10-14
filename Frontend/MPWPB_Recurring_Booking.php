<?php
/*
* @Author 		MagePeople Team
* Copyright: 	mage-people.com
*/
if (!defined('ABSPATH')) {
    die;
} // Cannot access pages directly.

if (!class_exists('MPWPB_Recurring_Booking')) {
    class MPWPB_Recurring_Booking {
        public function __construct() {
            // Enqueue scripts and styles
            add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
            
            // AJAX handlers
            add_action('wp_ajax_mpwpb_save_recurring_booking', array($this, 'generate_recurring_dates'));
            add_action('wp_ajax_nopriv_mpwpb_save_recurring_booking', array($this, 'generate_recurring_dates'));
            
            // Filter cart item data to include recurring booking information
            add_filter('mpwpb_add_cart_item', array($this, 'add_recurring_data_to_cart'), 10, 2);
            
            // Display recurring booking information in cart and checkout
            add_action('mpwpb_show_cart_item', array($this, 'show_recurring_info_in_cart'), 10, 2);
            
            // Add recurring booking data to order
            add_action('mpwpb_checkout_create_order_line_item', array($this, 'add_recurring_data_to_order'), 10, 2);
            
            // Process recurring bookings after order is completed
            add_filter('add_mpwpb_booking_data', array($this, 'process_recurring_bookings'), 10, 2);
        }
        
        /**
         * Enqueue scripts and styles for recurring bookings
         */
        public function enqueue_scripts() {
//            if (is_singular(MPWPB_Function::get_cpt()) || (is_a(get_post(), 'WP_Post') && has_shortcode(get_post()->post_content, 'mpwpb-registration'))) {
                global $post;

                wp_enqueue_script('mpwpb-recurring-booking', MPWPB_PLUGIN_URL . '/assets/frontend/mpwpb_recurring_booking.js', array('jquery'),  true);
                wp_enqueue_style('mpwpb-recurring-booking', MPWPB_PLUGIN_URL . '/assets/frontend/mpwpb_recurring_booking.css', array(), true);

                // Pass post ID to JavaScript
                wp_localize_script('mpwpb-recurring-booking', 'mpwpb_recurring_data', array(
                    'post_id' => is_object($post) ? $post->ID : 0,
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('mpwpb_nonce'),
                    'plugin_url' => MPWPB_PLUGIN_URL,
                    'use_24hour' => MPWPB_Global_Function::get_settings('mpwpb_global_settings', 'time_format_24hour', 'no')
                ));
//            }
        }
        
        /**
         * Generate recurring dates based on the selected date and recurring options
         */
        public function generate_recurring_dates() {
            // Check nonce for security
            if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'mpwpb_nonce')) {
                wp_send_json_error(['message' => __('Security check failed', 'service-booking-manager')]);
                return;
            }

            // Get and sanitize input data
            $post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
            $recurring_type = isset($_POST['recurring_type']) ? sanitize_text_field(wp_unslash($_POST['recurring_type'])) : '';
            $recurring_count = isset($_POST['recurring_count']) ? absint($_POST['recurring_count']) : 0;
            $dates = isset($_POST['dates']) ? array_map('sanitize_text_field', wp_unslash($_POST['dates'])) : [];

            // Validate the data
            if (!$post_id || !$recurring_type || $recurring_count < 2 || empty($dates)) {
                wp_send_json_error([
                    'message' => __('Invalid data provided', 'service-booking-manager'),
                    'data' => [
                        'post_id' => $post_id,
                        'recurring_type' => $recurring_type,
                        'recurring_count' => $recurring_count,
                        'dates' => $dates
                    ]
                ]);
                return;
            }

            // Generate recurring dates
            $recurring_dates = $this->calculate_recurring_dates($recurring_type, $recurring_count, $dates[0]);

            // Return success response with dates
            wp_send_json_success([
                'dates' => $recurring_dates,
                'message' => __('Recurring dates generated successfully', 'service-booking-manager')
            ]);
        }
        
        /**
         * Calculate recurring dates based on the recurring type and count
         * FIXED: Off day setting not working in frontend - 2025-01-14 by Shahnur Alam
         * Now properly filters out off days and off dates when generating recurring dates
         *
         * @param string $recurring_type
         * @param int $recurring_count
         * @param string $start_date
         * @return array
         */
        private function calculate_recurring_dates($recurring_type, $recurring_count, $start_date) {
            $dates = [];
            $dates[] = $start_date; // Add the initial date
            // Make sure we have a valid date format
            if (!strtotime($start_date)) {
                // error_log('Invalid start date format: ' . $start_date);
                return $dates;
            }
            // Parse the date and time
            $date_parts = date_parse($start_date);
            if ($date_parts === false) {
                // error_log('Failed to parse date: ' . $start_date);
                return $dates;
            }

            // Get post ID from the AJAX request to fetch off days and off dates
            $post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
            
            // Get off days and off dates for this service - FIXED: Added off day filtering
            $off_days = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_off_days', '');
            $all_off_days = !empty($off_days) ? explode(',', $off_days) : [];
            $all_off_dates = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_off_dates', array());
            
            // Format off dates for comparison
            $off_dates = [];
            if (is_array($all_off_dates)) {
                foreach ($all_off_dates as $off_date) {
                    if (!empty($off_date)) {
                        $off_dates[] = date_i18n('Y-m-d', strtotime($off_date));
                    }
                }
            }

            // Create a DateTime object from the start date
            try {
                $date_obj = new DateTime($start_date);
                $time_format = 'H:i:s';
                $time_string = $date_obj->format($time_format);

                $current_date = strtotime($start_date);
                $generated_count = 1; // We already have the start date

                // Keep generating dates until we have the required count, skipping off days/dates
                while ($generated_count < $recurring_count) {
                    switch ($recurring_type) {
                        case 'weekly':
                            $current_date = strtotime('+1 week', $current_date);
                            break;
                        case 'bi-weekly':
                            $current_date = strtotime('+2 weeks', $current_date);
                            break;
                        case 'monthly':
                            $current_date = strtotime('+1 month', $current_date);
                            break;
                        default:
                            break;
                    }

                    // Format the date for checking
                    $date_string = date('Y-m-d', $current_date);
                    $day_name = strtolower(date_i18n('l', $current_date));
                    
                    // FIXED: Check if this date is an off day or off date
                    $is_off_day = in_array($day_name, $all_off_days);
                    $is_off_date = in_array($date_string, $off_dates);
                    
                    // Only add the date if it's not an off day or off date
                    if (!$is_off_day && !$is_off_date) {
                        $dates[] = $date_string . ' ' . $time_string;
                        $generated_count++;
                    }
                    
                    // Safety check to prevent infinite loop (max 365 days ahead)
                    if ($current_date > strtotime('+365 days', strtotime($start_date))) {
                        break;
                    }
                }
            } catch (Exception $e) {
                // error_log('Error creating DateTime object: ' . $e->getMessage());
                return [$start_date];
            }

            return $dates;
        }
        
        /**
         * Add recurring booking data to cart item
         * 
         * @param array $cart_item_data
         * @param int $product_id
         * @return array
         */
        public function add_recurring_data_to_cart($cart_item_data, $product_id) {
            if (isset($_POST['mpwpb_is_recurring']) && $_POST['mpwpb_is_recurring'] == 1) {
                $recurring_type = isset($_POST['mpwpb_recurring_type']) ? sanitize_text_field(wp_unslash($_POST['mpwpb_recurring_type'])) : '';
                $recurring_count = isset($_POST['mpwpb_recurring_count']) ? absint($_POST['mpwpb_recurring_count']) : 0;
                
                if ($recurring_type && $recurring_count >= 2) {
                    $start_date = $cart_item_data['mpwpb_date'];
                    
                    // Calculate recurring dates
                    $recurring_dates = $this->calculate_recurring_dates($recurring_type, $recurring_count, $start_date);
                    
                    // Apply recurring discount if available
                    $recurring_discount = MPWPB_Global_Function::get_post_info($product_id, 'mpwpb_recurring_discount', 0);
                    if ($recurring_discount > 0) {
                        $discount_amount = ($cart_item_data['mpwpb_tp'] * $recurring_discount) / 100;
                        $discounted_price = $cart_item_data['mpwpb_tp'] - $discount_amount;
                        
                        // Calculate total price for all recurring bookings with discount
                        $total_price = $cart_item_data['mpwpb_tp'] + ($discounted_price * ($recurring_count - 1));
                        
                        $cart_item_data['mpwpb_recurring_discount'] = $recurring_discount;
                        $cart_item_data['mpwpb_recurring_discount_amount'] = $discount_amount;
                    } else {
                        // Calculate total price for all recurring bookings without discount
                        $total_price = $cart_item_data['mpwpb_tp'] * $recurring_count;
                    }
                    
                    // Add recurring data to cart item
                    $cart_item_data['mpwpb_is_recurring'] = 1;
                    $cart_item_data['mpwpb_recurring_type'] = $recurring_type;
                    $cart_item_data['mpwpb_recurring_count'] = $recurring_count;
                    $cart_item_data['mpwpb_recurring_dates'] = $recurring_dates;
                    $cart_item_data['mpwpb_tp'] = $total_price;
                    $cart_item_data['line_total'] = $total_price;
                    $cart_item_data['line_subtotal'] = $total_price;
                }
            }
            
            return $cart_item_data;
        }
        
        /**
         * Display recurring booking information in cart
         * 
         * @param array $cart_item
         * @param int $post_id
         */
        public function show_recurring_info_in_cart($cart_item, $post_id) {
            if (isset($cart_item['mpwpb_is_recurring']) && $cart_item['mpwpb_is_recurring'] == 1) {
                $recurring_type = $cart_item['mpwpb_recurring_type'];
                $recurring_count = $cart_item['mpwpb_recurring_count'];
                $recurring_dates = $cart_item['mpwpb_recurring_dates'];
                
                $type_label = '';
                switch ($recurring_type) {
                    case 'weekly':
                        $type_label = __('Weekly', 'service-booking-manager');
                        break;
                    case 'bi-weekly':
                        $type_label = __('Bi-Weekly', 'service-booking-manager');
                        break;
                    case 'monthly':
                        $type_label = __('Monthly', 'service-booking-manager');
                        break;
                }
                
                ?>
                <div class="mpwpb_recurring_info">
                    <h5><?php esc_html_e('Recurring Booking', 'service-booking-manager'); ?></h5>
                    <ul>
                        <li>
                            <strong><?php esc_html_e('Type:', 'service-booking-manager'); ?></strong>
                            <?php echo esc_html($type_label); ?>
                        </li>
                        <li>
                            <strong><?php esc_html_e('Occurrences:', 'service-booking-manager'); ?></strong>
                            <?php echo esc_html($recurring_count); ?>
                        </li>
                        <?php if (isset($cart_item['mpwpb_recurring_discount']) && $cart_item['mpwpb_recurring_discount'] > 0) { ?>
                            <li>
                                <strong><?php esc_html_e('Discount:', 'service-booking-manager'); ?></strong>
                                <?php echo esc_html($cart_item['mpwpb_recurring_discount']); ?>%
                            </li>
                        <?php } ?>
                    </ul>
                    
                    <div class="mpwpb_recurring_dates_info">
                        <strong><?php esc_html_e('Scheduled Dates:', 'service-booking-manager'); ?></strong>
                        <ol>
                            <?php foreach ($recurring_dates as $date) { ?>
                                <li>
                                    <?php echo esc_html(MPWPB_Global_Function::date_format($date)); ?>
                                    <?php echo esc_html(MPWPB_Global_Function::date_format($date, 'time')); ?>
                                </li>
                            <?php } ?>
                        </ol>
                    </div>
                </div>
                <?php
            }
        }
        
        /**
         * Add recurring booking data to order line item
         * 
         * @param WC_Order_Item_Product $item
         * @param array $values
         */
        public function add_recurring_data_to_order($item, $values) {
            if (isset($values['mpwpb_is_recurring']) && $values['mpwpb_is_recurring'] == 1) {
                $recurring_type = $values['mpwpb_recurring_type'];
                $recurring_count = $values['mpwpb_recurring_count'];
                $recurring_dates = $values['mpwpb_recurring_dates'];
                
                $type_label = '';
                switch ($recurring_type) {
                    case 'weekly':
                        $type_label = __('Weekly', 'service-booking-manager');
                        break;
                    case 'bi-weekly':
                        $type_label = __('Bi-Weekly', 'service-booking-manager');
                        break;
                    case 'monthly':
                        $type_label = __('Monthly', 'service-booking-manager');
                        break;
                }
                
                // Add recurring data to order item meta
                $item->add_meta_data(__('Recurring Booking', 'service-booking-manager'), __('Yes', 'service-booking-manager'));
                $item->add_meta_data(__('Recurring Type', 'service-booking-manager'), $type_label);
                $item->add_meta_data(__('Occurrences', 'service-booking-manager'), $recurring_count);
                
                if (isset($values['mpwpb_recurring_discount']) && $values['mpwpb_recurring_discount'] > 0) {
                    $item->add_meta_data(__('Recurring Discount', 'service-booking-manager'), $values['mpwpb_recurring_discount'] . '%');
                }
                
                // Add hidden meta data for processing
                $item->add_meta_data('_mpwpb_is_recurring', 1);
                $item->add_meta_data('_mpwpb_recurring_type', $recurring_type);
                $item->add_meta_data('_mpwpb_recurring_count', $recurring_count);
                $item->add_meta_data('_mpwpb_recurring_dates', $recurring_dates);
                
                if (isset($values['mpwpb_recurring_discount'])) {
                    $item->add_meta_data('_mpwpb_recurring_discount', $values['mpwpb_recurring_discount']);
                }
            }
        }
        
        /**
         * Process recurring bookings after order is completed
         *
         * @param array $booking_data
         * @param int $post_id
         * @return array
         */
        public function process_recurring_bookings($booking_data, $post_id) {
            $order_id = $booking_data['mpwpb_order_id'];
            $order = wc_get_order($order_id);

            foreach ($order->get_items() as $item_id => $item) {
                $is_recurring = wc_get_order_item_meta($item_id, '_mpwpb_is_recurring', true);

                if ($is_recurring) {
                    $recurring_dates = wc_get_order_item_meta($item_id, '_mpwpb_recurring_dates', true);
                    $recurring_type = wc_get_order_item_meta($item_id, '_mpwpb_recurring_type', true);
                    $recurring_count = wc_get_order_item_meta($item_id, '_mpwpb_recurring_count', true);
                    $recurring_discount = wc_get_order_item_meta($item_id, '_mpwpb_recurring_discount', true);

                    // Add recurring information to the main booking
                    $booking_data['mpwpb_is_recurring'] = 1;
                    $booking_data['mpwpb_recurring_type'] = $recurring_type;
                    $booking_data['mpwpb_recurring_count'] = $recurring_count;
                    $booking_data['mpwpb_recurring_dates'] = $recurring_dates;
                    $booking_data['mpwpb_recurring_index'] = 1; // First occurrence

                    if ($recurring_discount) {
                        $booking_data['mpwpb_recurring_discount'] = $recurring_discount;
                    }

                    if (is_array($recurring_dates) && count($recurring_dates) > 1) {
                        // The first date is already processed by the main booking system
                        // We need to process the remaining dates
                        for ($i = 1; $i < count($recurring_dates); $i++) {
                            $recurring_date = $recurring_dates[$i];

                            // Create a copy of the booking data with the new date
                            $recurring_booking_data = $booking_data;
                            $recurring_booking_data['mpwpb_date'] = $recurring_date;
                            $recurring_booking_data['mpwpb_recurring_index'] = $i + 1;

                            // Apply discount to recurring bookings if applicable
                            if ($recurring_discount && isset($recurring_booking_data['mpwpb_tp'])) {
                                $original_price = $recurring_booking_data['mpwpb_tp'];
                                $discount_amount = ($original_price * $recurring_discount) / 100;
                                $discounted_price = $original_price - $discount_amount;
                                $recurring_booking_data['mpwpb_tp'] = $discounted_price;
                                $recurring_booking_data['mpwpb_original_price'] = $original_price;
                                $recurring_booking_data['mpwpb_discount_amount'] = $discount_amount;
                            }

                            // Create a new booking entry for this recurring date
                            $booking_title = $booking_data['mpwpb_billing_name'] . ' - Recurring #' . ($i + 1);
                            MPWPB_Woocommerce::add_cpt_data('mpwpb_booking', $booking_title, $recurring_booking_data);

                            // Also create entries for extra services if any
                            if (isset($booking_data['mpwpb_extra_service_info']) && is_array($booking_data['mpwpb_extra_service_info']) && !empty($booking_data['mpwpb_extra_service_info'])) {
                                foreach ($booking_data['mpwpb_extra_service_info'] as $ex_service_info) {
                                    $ex_data = array(
                                        'mpwpb_id' => $post_id,
                                        'mpwpb_date' => $recurring_date,
                                        'mpwpb_order_id' => $order_id,
                                        'mpwpb_order_status' => $booking_data['mpwpb_order_status'],
                                        'mpwpb_ex_name' => $ex_service_info['ex_name'],
                                        'mpwpb_ex_price' => $ex_service_info['ex_price'],
                                        'mpwpb_ex_qty' => $ex_service_info['ex_qty'],
                                        'mpwpb_payment_method' => $booking_data['mpwpb_payment_method'],
                                        'mpwpb_user_id' => $booking_data['mpwpb_user_id'],
                                        'mpwpb_recurring_index' => $i + 1
                                    );

                                    if (isset($ex_service_info['ex_group_name'])) {
                                        $ex_data['mpwpb_ex_group_name'] = $ex_service_info['ex_group_name'];
                                    }

                                    MPWPB_Woocommerce::add_cpt_data('mpwpb_extra_service_booking', '#' . $order_id . $ex_data['mpwpb_ex_name'] . '-Recurring-' . ($i + 1), $ex_data);
                                }
                            }
                        }
                    }
                }
            }
            
            return $booking_data;
        }


        public static function get_time_from_datetime( $all_datetime_slots ){
            $times = [];
            foreach ($all_datetime_slots as $slot) {
                $parts = explode(' ', $slot);
                if (isset($parts[1])) {
                    $times[] = $parts[1];
                }
            }

            return $times;
        }
        public static function get_time_slot_on_date( $post_id, $start_date ){
            $all_datetime_slots = MPWPB_Function::get_time_slot( $post_id, $start_date );

            return self::get_time_from_datetime( $all_datetime_slots );
        }

        public static function wp_get_order_time_by_dates( $target_date, $mpwpb_id ) {
            $raw_results = [];

            $args = [
                'post_type'      => 'mpwpb_booking',
                'post_status'    => 'publish',
                'posts_per_page' => -1,
                'meta_query'     => [
                    'relation' => 'AND',
                    [
                        'key'     => 'mpwpb_id',
                        'value'   => $mpwpb_id,
                        'compare' => '='
                    ],
                    [
                        'key'     => 'mpwpb_date',
                        'value'   => $target_date,
                        'compare' => 'LIKE'
                    ]
                ],
            ];

            $query = new WP_Query($args);
            if ( $query->have_posts() ) {
                foreach ( $query->posts as $post ) {
                    $meta_value = get_post_meta( $post->ID, 'mpwpb_date', true );

                    if ( $meta_value ) {
                        $raw_results[] = $meta_value;
                    }
                }
            }
            $ordered_times = [];

            foreach ( $raw_results as $value ) {
                $datetime_array = explode( ',', $value );

                foreach ( $datetime_array as $datetime ) {
                    $datetime = trim( $datetime );

                    if ( strpos( $datetime, $target_date ) === 0 ) {
                        $parts = explode( ' ', $datetime );
                        if ( isset( $parts[1] ) ) {
                            $ordered_times[] = $parts[1];
                        }
                    }
                }
            }

            $ordered_times = array_unique( $ordered_times );
            sort( $ordered_times );

            return $ordered_times;
        }
        public static function get_all_future_booking_order_date_times( $mpwpb_id ) {
            $meta_key = 'mpwpb_date';
            $now = current_time('Y-m-d H:i:s');
            $future_slots = [];

            $args = [
                'post_type'      => 'mpwpb_booking',
                'post_status'    => 'publish',
                'posts_per_page' => -1,
                'meta_query'     => [
                    [
                        'key'     => 'mpwpb_id',
                        'value'   => $mpwpb_id,
                        'compare' => '='
                    ]
                ]
            ];

            $query = new WP_Query($args);

            if ( $query->have_posts() ) {
                foreach ( $query->posts as $post ) {
                    $meta_value = get_post_meta( $post->ID, $meta_key, true );
                    if ( $meta_value ) {
                        $date_times = explode(',', $meta_value);
                        foreach ( $date_times as $datetime ) {
                            $datetime = trim( $datetime );

                            // Ensure format is YYYY-MM-DD HH:MM or HH:MM:SS
                            if ( preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}(:\d{2})?$/', $datetime ) ) {
                                if ( strlen( $datetime ) === 16 ) {
                                    $datetime .= ':00'; // Add seconds if missing
                                }

                                if ( strtotime( $datetime ) >= strtotime( $now ) ) {
                                    $future_slots[] = $datetime;
                                }
                            }
                        }
                    }
                }
            }

            sort($future_slots );

            return $future_slots;
        }


    }
    
    new MPWPB_Recurring_Booking();
}