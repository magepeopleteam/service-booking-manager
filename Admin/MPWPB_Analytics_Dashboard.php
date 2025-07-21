<?php
/*
* @Author 		engr.sumonazma@gmail.com
* Copyright: 	mage-people.com
*/
if (!defined('ABSPATH')) {
    die;
} // Cannot access pages directly.

if (!class_exists('MPWPB_Analytics_Dashboard')) {
    class MPWPB_Analytics_Dashboard {
        public function __construct() {
            add_action('admin_menu', array($this, 'analytics_menu'));
            add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
            add_action('wp_ajax_mpwpb_get_analytics_data', array($this, 'get_analytics_data'));
        }

        /**
         * Add Analytics Dashboard menu item
         */
        public function analytics_menu() {
            $cpt = MPWPB_Function::get_cpt();
            add_submenu_page(
                'edit.php?post_type=' . $cpt, 
                esc_html__('Analytics Dashboard', 'service-booking-manager'), 
                '<span style="color:#00c853">' . esc_html__('Analytics Dashboard', 'service-booking-manager') . '</span>', 
                'manage_options', 
                'mpwpb_analytics_dashboard', 
                array($this, 'analytics_dashboard_page')
            );
        }

        /**
         * Enqueue required scripts and styles
         */
        public function admin_enqueue_scripts($hook) {
            $cpt = MPWPB_Function::get_cpt();
            if ($hook == $cpt . '_page_mpwpb_analytics_dashboard') {
                // Enqueue Chart.js
                wp_enqueue_script('mpwpb-chartjs', 'https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js', array(), '3.9.1', true);
                // Enqueue Date Range Picker dependencies
                wp_enqueue_script('mpwpb-moment', 'https://cdn.jsdelivr.net/momentjs/latest/moment.min.js', array(), '2.29.4', true);
                wp_enqueue_script('mpwpb-daterangepicker', 'https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js', array('jquery', 'mpwpb-moment'), '3.1', true);
                wp_enqueue_style('mpwpb-daterangepicker', 'https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css', array(), '3.1');
                
                // Enqueue our custom scripts and styles
                wp_enqueue_script('mpwpb-analytics-js', plugins_url('/assets/admin/mpwpb_analytics.js', dirname(__FILE__)), array('jquery', 'mpwpb-chartjs', 'mpwpb-daterangepicker'), time(), true);
                wp_enqueue_style('mpwpb-analytics-css', plugins_url('/assets/admin/mpwpb_analytics.css', dirname(__FILE__)), array(), time());
                
                // Pass data to JavaScript
                wp_localize_script('mpwpb-analytics-js', 'mpwpb_analytics', array(
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('mpwpb_analytics_nonce'),
                    'currency_symbol' => get_woocommerce_currency_symbol(),
                    'labels' => array(
                        'revenue' => esc_html__('Revenue', 'service-booking-manager'),
                        'bookings' => esc_html__('Bookings', 'service-booking-manager'),
                        'services' => esc_html__('Services', 'service-booking-manager'),
                        'customers' => esc_html__('Customers', 'service-booking-manager'),
                    )
                ));
            }
        }

        /**
         * Render the Analytics Dashboard page
         */
        public function analytics_dashboard_page() {
            $label = MPWPB_Function::get_name();
            ?>
            <div class="wrap mpwpb-analytics-dashboard">
                <h1><?php echo esc_html($label) . ' ' . esc_html__('Analytics Dashboard', 'service-booking-manager'); ?></h1>
                
                <div class="mpwpb-dashboard-header">
                    <div class="mpwpb-date-filter">
                        <input type="text" id="mpwpb-date-range" class="mpwpb-date-range-picker" placeholder="<?php esc_attr_e('Select Date Range', 'service-booking-manager'); ?>">
                        <button id="mpwpb-apply-filter" class="button button-primary"><?php esc_html_e('Apply', 'service-booking-manager'); ?></button>
                    </div>
                    <div class="mpwpb-quick-filters">
                        <button class="button mpwpb-quick-filter" data-range="today"><?php esc_html_e('Today', 'service-booking-manager'); ?></button>
                        <button class="button mpwpb-quick-filter" data-range="yesterday"><?php esc_html_e('Yesterday', 'service-booking-manager'); ?></button>
                        <button class="button mpwpb-quick-filter" data-range="7days"><?php esc_html_e('Last 7 Days', 'service-booking-manager'); ?></button>
                        <button class="button mpwpb-quick-filter" data-range="30days"><?php esc_html_e('Last 30 Days', 'service-booking-manager'); ?></button>
                        <button class="button mpwpb-quick-filter" data-range="thismonth"><?php esc_html_e('This Month', 'service-booking-manager'); ?></button>
                        <button class="button mpwpb-quick-filter" data-range="lastmonth"><?php esc_html_e('Last Month', 'service-booking-manager'); ?></button>
                    </div>
                </div>
                
                <!-- KPI Cards -->
                <div class="mpwpb-kpi-cards">
                    <div class="mpwpb-kpi-card mpwpb-revenue-card">
                        <div class="mpwpb-kpi-icon">
                            <span class="dashicons dashicons-money-alt"></span>
                        </div>
                        <div class="mpwpb-kpi-content">
                            <h3><?php esc_html_e('Total Revenue', 'service-booking-manager'); ?></h3>
                            <div class="mpwpb-kpi-value" id="mpwpb-total-revenue">
                                <div class="mpwpb-loading-spinner"></div>
                            </div>
                            <div class="mpwpb-kpi-change" id="mpwpb-revenue-change"></div>
                        </div>
                    </div>
                    
                    <div class="mpwpb-kpi-card mpwpb-bookings-card">
                        <div class="mpwpb-kpi-icon">
                            <span class="dashicons dashicons-calendar-alt"></span>
                        </div>
                        <div class="mpwpb-kpi-content">
                            <h3><?php esc_html_e('Total Bookings', 'service-booking-manager'); ?></h3>
                            <div class="mpwpb-kpi-value" id="mpwpb-total-bookings">
                                <div class="mpwpb-loading-spinner"></div>
                            </div>
                            <div class="mpwpb-kpi-change" id="mpwpb-bookings-change"></div>
                        </div>
                    </div>
                    
                    <div class="mpwpb-kpi-card mpwpb-services-card">
                        <div class="mpwpb-kpi-icon">
                            <span class="dashicons dashicons-admin-generic"></span>
                        </div>
                        <div class="mpwpb-kpi-content">
                            <h3><?php esc_html_e('Popular Services', 'service-booking-manager'); ?></h3>
                            <div class="mpwpb-kpi-value" id="mpwpb-popular-services">
                                <div class="mpwpb-loading-spinner"></div>
                            </div>
                            <div class="mpwpb-kpi-change" id="mpwpb-services-change"></div>
                        </div>
                    </div>
                    
                    <div class="mpwpb-kpi-card mpwpb-customers-card">
                        <div class="mpwpb-kpi-icon">
                            <span class="dashicons dashicons-groups"></span>
                        </div>
                        <div class="mpwpb-kpi-content">
                            <h3><?php esc_html_e('New Customers', 'service-booking-manager'); ?></h3>
                            <div class="mpwpb-kpi-value" id="mpwpb-new-customers">
                                <div class="mpwpb-loading-spinner"></div>
                            </div>
                            <div class="mpwpb-kpi-change" id="mpwpb-customers-change"></div>
                        </div>
                    </div>
                </div>
                
                <!-- Charts Section -->
                <div class="mpwpb-charts-container">
                    <div class="mpwpb-chart-card mpwpb-revenue-chart-card">
                        <h2><?php esc_html_e('Revenue Trend', 'service-booking-manager'); ?></h2>
                        <div class="mpwpb-chart-container">
                            <canvas id="mpwpb-revenue-chart"></canvas>
                        </div>
                    </div>
                    
                    <div class="mpwpb-chart-card mpwpb-bookings-chart-card">
                        <h2><?php esc_html_e('Bookings Trend', 'service-booking-manager'); ?></h2>
                        <div class="mpwpb-chart-container">
                            <canvas id="mpwpb-bookings-chart"></canvas>
                        </div>
                    </div>
                </div>
                
                <!-- Services and Categories Analysis -->
                <div class="mpwpb-charts-container">
                    <div class="mpwpb-chart-card mpwpb-services-chart-card">
                        <h2><?php esc_html_e('Popular Services', 'service-booking-manager'); ?></h2>
                        <div class="mpwpb-chart-container">
                            <canvas id="mpwpb-services-chart"></canvas>
                        </div>
                    </div>
                    
                    <div class="mpwpb-chart-card mpwpb-categories-chart-card">
                        <h2><?php esc_html_e('Category Distribution', 'service-booking-manager'); ?></h2>
                        <div class="mpwpb-chart-container">
                            <canvas id="mpwpb-categories-chart"></canvas>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Bookings Table -->
                <div class="mpwpb-recent-bookings-container">
                    <h2><?php esc_html_e('Recent Bookings', 'service-booking-manager'); ?></h2>
                    <div class="mpwpb-table-container">
                        <table class="mpwpb-recent-bookings-table">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e('Booking ID', 'service-booking-manager'); ?></th>
                                    <th><?php esc_html_e('Order', 'service-booking-manager'); ?></th>
                                    <th><?php esc_html_e('Customer', 'service-booking-manager'); ?></th>
                                    <th><?php esc_html_e('Service', 'service-booking-manager'); ?></th>
                                    <th><?php esc_html_e('Date', 'service-booking-manager'); ?></th>
                                    <th><?php esc_html_e('Time', 'service-booking-manager'); ?></th>
                                    <th><?php esc_html_e('Status', 'service-booking-manager'); ?></th>
                                    <th><?php esc_html_e('Amount', 'service-booking-manager'); ?></th>
                                    <th><?php esc_html_e('Actions', 'service-booking-manager'); ?></th>
                                </tr>
                            </thead>
                            <tbody id="mpwpb-recent-bookings-body">
                                <tr>
                                    <td colspan="9" class="mpwpb-loading-row">
                                        <div class="mpwpb-loading-spinner"></div>
                                        <p><?php esc_html_e('Loading recent bookings...', 'service-booking-manager'); ?></p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php
        }

        /**
         * AJAX handler for getting analytics data
         */
        public function get_analytics_data() {
            // Check nonce for security
            check_ajax_referer('mpwpb_analytics_nonce', 'nonce');
            
            // Check user capabilities
            if (!current_user_can('manage_options')) {
                wp_send_json_error(array('message' => esc_html__('You do not have permission to access this data.', 'service-booking-manager')));
            }
            
            // Get date range parameters
            $start_date = isset($_POST['start_date']) ? sanitize_text_field($_POST['start_date']) : date('Y-m-d', strtotime('-30 days'));
            $end_date = isset($_POST['end_date']) ? sanitize_text_field($_POST['end_date']) : date('Y-m-d');
            
            // Get comparison date range (for calculating change percentages)
            $days_diff = (strtotime($end_date) - strtotime($start_date)) / (60 * 60 * 24);
            $comparison_end_date = date('Y-m-d', strtotime($start_date . ' -1 day'));
            $comparison_start_date = date('Y-m-d', strtotime($comparison_end_date . ' -' . $days_diff . ' days'));
            
            // Get analytics data
            $data = $this->get_analytics_data_for_period($start_date, $end_date);
            $comparison_data = $this->get_analytics_data_for_period($comparison_start_date, $comparison_end_date);
            
            // Calculate changes
            $revenue_change = $this->calculate_percentage_change($comparison_data['total_revenue'], $data['total_revenue']);
            $bookings_change = $this->calculate_percentage_change($comparison_data['total_bookings'], $data['total_bookings']);
            $customers_change = $this->calculate_percentage_change($comparison_data['new_customers'], $data['new_customers']);
            
            // Add change data
            $data['revenue_change'] = $revenue_change;
            $data['bookings_change'] = $bookings_change;
            $data['customers_change'] = $customers_change;
            
            // Send response
            wp_send_json_success($data);
        }
        
        /**
         * Get analytics data for a specific period
         */
        private function get_analytics_data_for_period($start_date, $end_date) {
            global $wpdb;

            // Initialize data array
            $data = array(
                'total_revenue' => 0,
                'total_bookings' => 0,
                'new_customers' => 0,
                'popular_services' => array(),
                'category_distribution' => array(),
                'daily_revenue' => array(),
                'daily_bookings' => array(),
                'recent_bookings' => array()
            );

            // Get booking status that should be counted
            $booking_statuses = MPWPB_Global_Function::get_settings('mp_global_settings', 'set_book_status', array('processing', 'completed'));
            if (empty($booking_statuses)) {
                $booking_statuses = array('processing', 'completed');
            }

            // Prepare date range
            $start_timestamp = strtotime($start_date);
            $end_timestamp = strtotime($end_date);

            // Generate daily data points for charts
            $current = $start_timestamp;
            while ($current <= $end_timestamp) {
                $date_key = date('Y-m-d', $current);
                $data['daily_revenue'][$date_key] = 0;
                $data['daily_bookings'][$date_key] = 0;
                $current = strtotime('+1 day', $current);
            }

            // Build meta query for booking statuses
            $status_meta_query = array('relation' => 'OR');
            foreach ($booking_statuses as $status) {
                $status_meta_query[] = array(
                    'key' => 'mpwpb_order_status',
                    'value' => $status,
                    'compare' => '='
                );
            }

            // Query for bookings in the date range
            $args = array(
                'post_type' => 'mpwpb_booking',
                'posts_per_page' => -1,
                'date_query' => array(
                    array(
                        'after' => $start_date,
                        'before' => $end_date,
                        'inclusive' => true,
                    ),
                ),
                'meta_query' => array(
                    $status_meta_query
                )
            );

            $booking_query = new WP_Query($args);
            $bookings = $booking_query->posts;

            // Process bookings data
            $service_counts = array();
            $category_counts = array();
            $customer_ids = array();
            $service_data = array();

            foreach ($bookings as $booking) {
                // Get booking metadata
                $booking_id = $booking->ID;
                $order_id = get_post_meta($booking_id, 'mpwpb_order_id', true);
                $service_id = get_post_meta($booking_id, 'mpwpb_id', true);
                $booking_date = get_post_meta($booking_id, 'mpwpb_date', true);
                $customer_id = get_post_meta($booking_id, 'mpwpb_user_id', true);
                $order_status = get_post_meta($booking_id, 'mpwpb_order_status', true);
                $total_price = get_post_meta($booking_id, 'mpwpb_tp', true);
                $service_info = get_post_meta($booking_id, 'mpwpb_service', true);
                $billing_name = get_post_meta($booking_id, 'mpwpb_billing_name', true);
                $billing_email = get_post_meta($booking_id, 'mpwpb_billing_email', true);

                // Count total bookings
                $data['total_bookings']++;

                // Sum total revenue - ensure we have a valid price
                $price = !empty($total_price) ? floatval($total_price) : 0;

                // If price is still 0, try to get it from the order
                if ($price == 0 && !empty($order_id)) {
                    $order = wc_get_order($order_id);
                    if ($order) {
                        $price = floatval($order->get_total());
                    }
                }

                // Make sure service_info is an array
                if (!empty($service_info) && !is_array($service_info)) {
                    // Try to unserialize if it's a string
                    if (is_string($service_info)) {
                        $maybe_unserialized = maybe_unserialize($service_info);
                        if (is_array($maybe_unserialized)) {
                            $service_info = $maybe_unserialized;
                        }
                    }
                }

                // If we have service info, try to calculate price from there
                if ($price == 0 && !empty($service_info) && is_array($service_info)) {
                    foreach ($service_info as $service) {
                        if (isset($service['price'])) {
                            $price += floatval($service['price']);
                        }
                    }
                }

                $data['total_revenue'] += $price;

                // Track unique customers
                if (!empty($customer_id) && !in_array($customer_id, $customer_ids)) {
                    $customer_ids[] = $customer_id;
                }

                // Track service popularity
                if (!empty($service_id)) {
                    if (!isset($service_counts[$service_id])) {
                        $service_counts[$service_id] = 0;
                        $service_data[$service_id] = get_the_title($service_id);
                    }
                    $service_counts[$service_id]++;

                    // Get service category
                    $service_categories = wp_get_post_terms($service_id, 'mpwpb_category', array('fields' => 'all'));
                    if (!empty($service_categories)) {
                        foreach ($service_categories as $category) {
                            $cat_id = $category->term_id;
                            if (!isset($category_counts[$cat_id])) {
                                $category_counts[$cat_id] = array(
                                    'count' => 0,
                                    'name' => $category->name
                                );
                            }
                            $category_counts[$cat_id]['count']++;
                        }
                    }
                }

                // Add to daily data
                $booking_day = !empty($booking_date) ? date('Y-m-d', strtotime($booking_date)) : date('Y-m-d', strtotime($booking->post_date));
                if (isset($data['daily_revenue'][$booking_day])) {
                    $data['daily_revenue'][$booking_day] += $price;
                    $data['daily_bookings'][$booking_day]++;
                }

                // Add to recent bookings (limit to 10)
                if (count($data['recent_bookings']) < 10) {
                    // Get customer name
                    $customer_name = !empty($billing_name) ? $billing_name : __('Guest', 'service-booking-manager');
                    if (empty($customer_name) && !empty($customer_id)) {
                        $customer_info = get_userdata($customer_id);
                        $customer_name = $customer_info ? $customer_info->display_name : __('Guest', 'service-booking-manager');
                    }

                    // Get service name
                    $service_name = !empty($service_id) ? get_the_title($service_id) : '';
                    if (empty($service_name) && !empty($service_info)) {
                        // Make sure service_info is an array if it's serialized
                        if (!is_array($service_info) && is_string($service_info)) {
                            $service_info = maybe_unserialize($service_info);
                        }

                        if (is_array($service_info)) {
                            $service_names = array();
                            foreach ($service_info as $service) {
                                if (isset($service['name'])) {
                                    $service_names[] = $service['name'];
                                }
                            }
                            if (!empty($service_names)) {
                                $service_name = implode(', ', $service_names);
                            }
                        }
                    }

                    // Format time from date
                    $booking_time = !empty($booking_date) ? date('H:i', strtotime($booking_date)) : '';

                    $data['recent_bookings'][] = array(
                        'id' => $booking_id,
                        'order_id' => $order_id,
                        'customer' => $customer_name,
                        'service' => $service_name,
                        'date' => !empty($booking_date) ? date('Y-m-d', strtotime($booking_date)) : '',
                        'time' => $booking_time,
                        'status' => $order_status,
                        'amount' => $price,
                        'edit_url' => admin_url('post.php?post=' . $booking_id . '&action=edit'),
                        'order_url' => !empty($order_id) ? admin_url('post.php?post=' . $order_id . '&action=edit') : ''
                    );
                }
            }

            // Count new customers (registered during the period)
            $new_customers_query = $wpdb->prepare(
                "SELECT COUNT(ID) FROM {$wpdb->users}
                WHERE user_registered BETWEEN %s AND %s",
                $start_date . ' 00:00:00', $end_date . ' 23:59:59'
            );
            $data['new_customers'] = intval($wpdb->get_var($new_customers_query));

            // Process popular services
            arsort($service_counts);
            $popular_services = array_slice($service_counts, 0, 5, true);
            foreach ($popular_services as $service_id => $count) {
                $service_name = isset($service_data[$service_id]) ? $service_data[$service_id] : get_the_title($service_id);
                $data['popular_services'][] = array(
                    'id' => $service_id,
                    'name' => $service_name,
                    'count' => $count
                );
            }

            // Process category distribution
            arsort($category_counts);
            foreach ($category_counts as $cat_id => $cat_data) {
                $data['category_distribution'][] = array(
                    'id' => $cat_id,
                    'name' => $cat_data['name'],
                    'count' => $cat_data['count']
                );
            }

            return $data;
        }
        
        /**
         * Calculate percentage change between two values
         */
        private function calculate_percentage_change($old_value, $new_value) {
            if ($old_value == 0) {
                return $new_value > 0 ? 100 : 0;
            }
            
            return round((($new_value - $old_value) / $old_value) * 100, 2);
        }
    }
    
    new MPWPB_Analytics_Dashboard();
}