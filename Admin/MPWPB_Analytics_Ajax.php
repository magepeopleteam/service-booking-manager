<?php
/*
* @Author 		engr.sumonazma@gmail.com
* Copyright: 	mage-people.com
*/
if (!defined('ABSPATH')) {
    die;
} // Cannot access pages directly.

if (!class_exists('MPWPB_Analytics_Ajax')) {
    class MPWPB_Analytics_Ajax {
        public function __construct() {
            // AJAX handlers
            add_action('wp_ajax_mpwpb_load_analytics_data', array($this, 'load_analytics_data'));
            add_action('wp_ajax_mpwpb_export_analytics_data', array($this, 'export_analytics_data'));
        }
        
        /**
         * AJAX handler for loading analytics data
         */
        public function load_analytics_data() {
            // Verify nonce
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mpwpb_analytics_nonce')) {
                wp_send_json_error(array('message' => esc_html__('Security check failed', 'service-booking-manager')));
            }
            
            // Check user capabilities
            if (!current_user_can('manage_options')) {
                wp_send_json_error(array('message' => esc_html__('Insufficient permissions', 'service-booking-manager')));
            }
            
            // Get filter data
            $filter_data = isset($_POST['filter_data']) ? sanitize_text_field($_POST['filter_data']) : '';
            
            // Parse filter data
            parse_str($filter_data, $filters);
            
            // Prepare response data
            $response_data = array(
                'summary' => $this->get_summary_data($filters),
                'bookings_over_time' => $this->get_bookings_over_time_data($filters),
                'top_services' => $this->get_top_services_data($filters),
                'recent_bookings' => $this->get_recent_bookings_data($filters)
            );
            
            wp_send_json_success($response_data);
        }
        
        /**
         * AJAX handler for exporting analytics data
         */
        public function export_analytics_data() {
            // Verify nonce
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mpwpb_analytics_nonce')) {
                wp_send_json_error(array('message' => esc_html__('Security check failed', 'service-booking-manager')));
            }
            
            // Check user capabilities
            if (!current_user_can('manage_options')) {
                wp_send_json_error(array('message' => esc_html__('Insufficient permissions', 'service-booking-manager')));
            }
            
            // Get filter data
            $filter_data = isset($_POST['filter_data']) ? sanitize_text_field($_POST['filter_data']) : '';
            
            // Parse filter data
            parse_str($filter_data, $filters);
            
            // Generate CSV data
            $csv_data = $this->generate_csv_data($filters);
            
            // Return CSV download URL or data
            wp_send_json_success(array(
                'csv_data' => $csv_data,
                'message' => esc_html__('Data exported successfully', 'service-booking-manager')
            ));
        }
        
        /**
         * Get summary data based on filters
         */
        private function get_summary_data($filters) {
            // Extract filter values
            $date_range = isset($filters['date_range']) ? $filters['date_range'] : '30';
            $start_date = isset($filters['start_date']) ? $filters['start_date'] : '';
            $end_date = isset($filters['end_date']) ? $filters['end_date'] : '';
            $service_id = isset($filters['service_filter']) ? intval($filters['service_filter']) : '';
            
            // Process date range
            if ($date_range === 'custom' && $start_date && $end_date) {
                // Use custom dates
            } else {
                $end_date = date('Y-m-d');
                switch ($date_range) {
                    case '7':
                        $start_date = date('Y-m-d', strtotime('-7 days'));
                        break;
                    case '90':
                        $start_date = date('Y-m-d', strtotime('-90 days'));
                        break;
                    case '365':
                        $start_date = date('Y-m-d', strtotime('-365 days'));
                        break;
                    default: // 30 days
                        $start_date = date('Y-m-d', strtotime('-30 days'));
                        break;
                }
            }
            
            // Get data based on filters
            $total_bookings = $this->get_total_bookings($start_date, $end_date, $service_id);
            $total_revenue = $this->get_total_revenue($start_date, $end_date, $service_id);
            $avg_booking_value = $total_bookings > 0 ? $total_revenue / $total_bookings : 0;
            $conversion_rate = $this->get_conversion_rate($start_date, $end_date, $service_id);
            
            return array(
                'total_bookings' => $total_bookings,
                            'total_revenue' => wp_kses_post(wc_price($total_revenue)),
            'avg_booking_value' => wp_kses_post(wc_price($avg_booking_value)),
                'conversion_rate' => round($conversion_rate, 2) . '%'
            );
        }
        
        /**
         * Get bookings over time data for chart
         */
        private function get_bookings_over_time_data($filters) {
            // Extract filter values
            $date_range = isset($filters['date_range']) ? $filters['date_range'] : '30';
            $start_date = isset($filters['start_date']) ? $filters['start_date'] : '';
            $end_date = isset($filters['end_date']) ? $filters['end_date'] : '';
            $service_id = isset($filters['service_filter']) ? intval($filters['service_filter']) : '';
            
            // Process date range
            if ($date_range === 'custom' && $start_date && $end_date) {
                // Use custom dates
            } else {
                $end_date = date('Y-m-d');
                switch ($date_range) {
                    case '7':
                        $start_date = date('Y-m-d', strtotime('-7 days'));
                        break;
                    case '90':
                        $start_date = date('Y-m-d', strtotime('-90 days'));
                        break;
                    case '365':
                        $start_date = date('Y-m-d', strtotime('-365 days'));
                        break;
                    default: // 30 days
                        $start_date = date('Y-m-d', strtotime('-30 days'));
                        break;
                }
            }
            
            // Generate date periods
            $period = new DatePeriod(
                new DateTime($start_date),
                new DateInterval('P1D'),
                new DateTime($end_date . ' +1 day')
            );
            
            $labels = array();
            $data = array();
            
            foreach ($period as $date) {
                $date_str = $date->format('Y-m-d');
                $labels[] = $date->format('M j');
                
                $args = array(
                    'post_type' => 'mpwpb_booking',
                    'posts_per_page' => -1,
                    'date_query' => array(
                        array(
                            'year' => $date->format('Y'),
                            'month' => $date->format('m'),
                            'day' => $date->format('d')
                        )
                    ),
                    'meta_query' => array()
                );
                
                // Add service filter if specified
                if ($service_id) {
                    $args['meta_query'][] = array(
                        'key' => 'mpwpb_id',
                        'value' => $service_id,
                        'compare' => '='
                    );
                }
                
                $query = new WP_Query($args);
                $valid_bookings = 0;
                
                if ($query->have_posts()) {
                    while ($query->have_posts()) {
                        $query->the_post();
                        $order_id = get_post_meta(get_the_ID(), 'mpwpb_order_id', true);
                        if ($order_id) {
                            $order = wc_get_order($order_id);
                                                    if ($order && $order->get_id() && $order->get_status() !== 'trash') {
                            $valid_bookings++;
                        }
                        }
                    }
                    wp_reset_postdata();
                }
                
                $data[] = $valid_bookings;
            }
            
            return array(
                'labels' => $labels,
                'datasets' => array(
                    array(
                        'label' => esc_html__('Bookings', 'service-booking-manager'),
                        'data' => $data,
                        'borderColor' => 'rgb(75, 192, 192)',
                        'backgroundColor' => 'rgba(75, 192, 192, 0.2)',
                        'tension' => 0.1
                    )
                )
            );
        }
        
        /**
         * Get top services data for chart
         */
        private function get_top_services_data($filters) {
            // Extract filter values
            $date_range = isset($filters['date_range']) ? $filters['date_range'] : '30';
            $start_date = isset($filters['start_date']) ? $filters['start_date'] : '';
            $end_date = isset($filters['end_date']) ? $filters['end_date'] : '';
            $service_id = isset($filters['service_filter']) ? intval($filters['service_filter']) : '';
            
            // Process date range
            if ($date_range === 'custom' && $start_date && $end_date) {
                // Use custom dates
            } else {
                $end_date = date('Y-m-d');
                switch ($date_range) {
                    case '7':
                        $start_date = date('Y-m-d', strtotime('-7 days'));
                        break;
                    case '90':
                        $start_date = date('Y-m-d', strtotime('-90 days'));
                        break;
                    case '365':
                        $start_date = date('Y-m-d', strtotime('-365 days'));
                        break;
                    default: // 30 days
                        $start_date = date('Y-m-d', strtotime('-30 days'));
                        break;
                }
            }
            
            // Use WP_Query instead of direct SQL to properly check for valid orders
            $args = array(
                'post_type' => 'mpwpb_booking',
                'posts_per_page' => -1,
                'meta_query' => array()
            );
            
            // Add date filter
            if ($start_date && $end_date) {
                $args['date_query'] = array(
                    array(
                        'after' => $start_date,
                        'before' => $end_date,
                        'inclusive' => true
                    )
                );
            }
            
            // Add service filter if specified
            if ($service_id) {
                $args['meta_query'][] = array(
                    'key' => 'mpwpb_id',
                    'value' => $service_id,
                    'compare' => '='
                );
            }
            
            $query = new WP_Query($args);
            $service_counts = array();
            
            if ($query->have_posts()) {
                while ($query->have_posts()) {
                    $query->the_post();
                    $order_id = get_post_meta(get_the_ID(), 'mpwpb_order_id', true);
                    if ($order_id) {
                        $order = wc_get_order($order_id);
                        if ($order && $order->get_id() && $order->get_status() !== 'trash') {
                            $service_id = get_post_meta(get_the_ID(), 'mpwpb_id', true);
                            if ($service_id) {
                                if (!isset($service_counts[$service_id])) {
                                    $service_counts[$service_id] = 0;
                                }
                                $service_counts[$service_id]++;
                            }
                        }
                    }
                }
                wp_reset_postdata();
            }
            
            // Sort by count and limit results
            arsort($service_counts);
            $service_counts = array_slice($service_counts, 0, 5, true);
            
            $labels = array();
            $data = array();
            
            foreach ($service_counts as $service_id => $count) {
                $service_name = get_the_title($service_id);
                if (!$service_name) {
                    $service_name = 'Unknown Service (' . $service_id . ')';
                }
                
                $labels[] = $service_name;
                $data[] = $count;
            }
            
            return array(
                'labels' => $labels,
                'datasets' => array(
                    array(
                        'label' => esc_html__('Bookings', 'service-booking-manager'),
                        'data' => $data,
                        'backgroundColor' => 'rgba(54, 162, 235, 0.2)',
                        'borderColor' => 'rgba(54, 162, 235, 1)',
                        'borderWidth' => 1
                    )
                )
            );
        }
        
        /**
         * Get recent bookings data
         */
        private function get_recent_bookings_data($filters) {
            // Extract filter values
            $date_range = isset($filters['date_range']) ? $filters['date_range'] : '30';
            $start_date = isset($filters['start_date']) ? $filters['start_date'] : '';
            $end_date = isset($filters['end_date']) ? $filters['end_date'] : '';
            $service_id = isset($filters['service_filter']) ? intval($filters['service_filter']) : '';
            
            // Process date range
            if ($date_range === 'custom' && $start_date && $end_date) {
                // Use custom dates
            } else {
                $end_date = date('Y-m-d');
                switch ($date_range) {
                    case '7':
                        $start_date = date('Y-m-d', strtotime('-7 days'));
                        break;
                    case '90':
                        $start_date = date('Y-m-d', strtotime('-90 days'));
                        break;
                    case '365':
                        $start_date = date('Y-m-d', strtotime('-365 days'));
                        break;
                    default: // 30 days
                        $start_date = date('Y-m-d', strtotime('-30 days'));
                        break;
                }
            }
            
            $args = array(
                'post_type' => 'mpwpb_booking',
                'posts_per_page' => 10,
                'orderby' => 'date',
                'order' => 'DESC',
                'meta_query' => array()
            );
            
            // Add date filter
            if ($start_date && $end_date) {
                $args['date_query'] = array(
                    array(
                        'after' => $start_date,
                        'before' => $end_date,
                        'inclusive' => true
                    )
                );
            }
            
            // Add service filter if specified
            if ($service_id) {
                $args['meta_query'][] = array(
                    'key' => 'mpwpb_id',
                    'value' => $service_id,
                    'compare' => '='
                );
            }
            
            $query = new WP_Query($args);
            $bookings = array();
            
            if ($query->have_posts()) {
                while ($query->have_posts()) {
                    $query->the_post();
                    $booking_id = get_the_ID();
                    $service_id = get_post_meta($booking_id, 'mpwpb_id', true);
                    $service_name = get_the_title($service_id);
                    $booking_date = get_post_meta($booking_id, 'mpwpb_date', true);
                    $order_id = get_post_meta($booking_id, 'mpwpb_order_id', true);
                    $order_status = get_post_meta($booking_id, 'mpwpb_order_status', true);
                    $order = $order_id ? wc_get_order($order_id) : null;
                    // Only include bookings with valid, non-trashed orders
                    if ($order && $order->get_id() && $order->get_status() !== 'trash') {
                        $customer_name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
                        $amount = $order->get_total();
                        
                        $bookings[] = array(
                            'id' => $booking_id,
                            'service' => $service_name,
                            'date' => MPWPB_Global_Function::date_format($booking_date),
                            'customer' => $customer_name,
                            'status' => $order_status,
                            'amount' => wp_kses_post(wc_price($amount))
                        );
                    }
                }
                wp_reset_postdata();
            }
            
            return $bookings;
        }
        
        /**
         * Generate CSV data for export
         */
        private function generate_csv_data($filters) {
            // Extract filter values
            $date_range = isset($filters['date_range']) ? $filters['date_range'] : '30';
            $start_date = isset($filters['start_date']) ? $filters['start_date'] : '';
            $end_date = isset($filters['end_date']) ? $filters['end_date'] : '';
            $service_id = isset($filters['service_filter']) ? intval($filters['service_filter']) : '';
            
            // Process date range
            if ($date_range === 'custom' && $start_date && $end_date) {
                // Use custom dates
            } else {
                $end_date = date('Y-m-d');
                switch ($date_range) {
                    case '7':
                        $start_date = date('Y-m-d', strtotime('-7 days'));
                        break;
                    case '90':
                        $start_date = date('Y-m-d', strtotime('-90 days'));
                        break;
                    case '365':
                        $start_date = date('Y-m-d', strtotime('-365 days'));
                        break;
                    default: // 30 days
                        $start_date = date('Y-m-d', strtotime('-30 days'));
                        break;
                }
            }
            
            $args = array(
                'post_type' => 'mpwpb_booking',
                'posts_per_page' => -1,
                'orderby' => 'date',
                'order' => 'DESC',
                'meta_query' => array()
            );
            
            // Add date filter
            if ($start_date && $end_date) {
                $args['date_query'] = array(
                    array(
                        'after' => $start_date,
                        'before' => $end_date,
                        'inclusive' => true
                    )
                );
            }
            
            // Add service filter if specified
            if ($service_id) {
                $args['meta_query'][] = array(
                    'key' => 'mpwpb_id',
                    'value' => $service_id,
                    'compare' => '='
                );
            }
            
            $query = new WP_Query($args);
            $csv_data = array();
            
            // Add header row
            $csv_data[] = array(
                'Booking ID',
                'Service',
                'Date',
                'Customer',
                'Status',
                'Amount'
            );
            
            if ($query->have_posts()) {
                while ($query->have_posts()) {
                    $query->the_post();
                    $booking_id = get_the_ID();
                    $order_id = get_post_meta($booking_id, 'mpwpb_order_id', true);
                    
                    // Only include bookings with valid, non-trashed orders
                    if ($order_id) {
                        $order = wc_get_order($order_id);
                        if ($order && $order->get_id() && $order->get_status() !== 'trash') {
                            $service_id = get_post_meta($booking_id, 'mpwpb_id', true);
                            $service_name = get_the_title($service_id);
                            $booking_date = get_post_meta($booking_id, 'mpwpb_date', true);
                            $order_status = get_post_meta($booking_id, 'mpwpb_order_status', true);
                            $customer_name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
                            $amount = $order->get_total();
                            
                            $csv_data[] = array(
                                $booking_id,
                                $service_name,
                                MPWPB_Global_Function::date_format($booking_date),
                                $customer_name,
                                $order_status,
                                $amount
                            );
                        }
                    }
                }
                wp_reset_postdata();
            }
            
            return $csv_data;
        }
        
        /**
         * Get total bookings count
         */
        private function get_total_bookings($start_date = '', $end_date = '', $service_id = '') {
            $args = array(
                'post_type' => 'mpwpb_booking',
                'posts_per_page' => -1,
                'meta_query' => array()
            );
            
            // Add date filter if specified
            if ($start_date && $end_date) {
                $args['date_query'] = array(
                    array(
                        'after' => $start_date,
                        'before' => $end_date,
                        'inclusive' => true
                    )
                );
            }
            
            // Add service filter if specified
            if ($service_id) {
                $args['meta_query'][] = array(
                    'key' => 'mpwpb_id',
                    'value' => $service_id,
                    'compare' => '='
                );
            }
            
            $query = new WP_Query($args);
            $valid_bookings = 0;
            
            if ($query->have_posts()) {
                while ($query->have_posts()) {
                    $query->the_post();
                    $order_id = get_post_meta(get_the_ID(), 'mpwpb_order_id', true);
                    if ($order_id) {
                        $order = wc_get_order($order_id);
                        if ($order && $order->get_id() && $order->get_status() !== 'trash') {
                            $valid_bookings++;
                        }
                    }
                }
                wp_reset_postdata();
            }
            
            return $valid_bookings;
        }
        
        /**
         * Get total revenue
         */
        private function get_total_revenue($start_date = '', $end_date = '', $service_id = '') {
            $args = array(
                'post_type' => 'mpwpb_booking',
                'posts_per_page' => -1,
                'meta_query' => array(
                    'relation' => 'AND',
                    array(
                        'key' => 'mpwpb_order_status',
                        'value' => array('completed', 'processing'),
                        'compare' => 'IN'
                    )
                )
            );
            
            // Add date filter if specified
            if ($start_date && $end_date) {
                $args['date_query'] = array(
                    array(
                        'after' => $start_date,
                        'before' => $end_date,
                        'inclusive' => true
                    )
                );
            }
            
            // Add service filter if specified
            if ($service_id) {
                $args['meta_query'][] = array(
                    'key' => 'mpwpb_id',
                    'value' => $service_id,
                    'compare' => '='
                );
            }
            
            $query = new WP_Query($args);
            $total_revenue = 0;
            
            if ($query->have_posts()) {
                while ($query->have_posts()) {
                    $query->the_post();
                    $order_id = get_post_meta(get_the_ID(), 'mpwpb_order_id', true);
                    if ($order_id) {
                        $order = wc_get_order($order_id);
                        if ($order && $order->get_status() !== 'trash' && ($order->get_status() === 'completed' || $order->get_status() === 'processing')) {
                            $total_revenue += $order->get_total();
                        }
                    }
                }
                wp_reset_postdata();
            }
            
            return $total_revenue;
        }
        
        /**
         * Get conversion rate (simplified)
         */
        private function get_conversion_rate($start_date = '', $end_date = '', $service_id = '') {
            // This is a simplified version - in a real implementation, you would track visitors
            $total_bookings = $this->get_total_bookings($start_date, $end_date, $service_id);
            // For demo purposes, we'll use a fixed number as "visitors"
            $total_visitors = $total_bookings * 10; // Just for demonstration
            
            if ($total_visitors > 0) {
                return ($total_bookings / $total_visitors) * 100;
            }
            
            return 0;
        }
    }
    
    new MPWPB_Analytics_Ajax();
}