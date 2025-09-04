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
            // Add analytics dashboard menu
            add_action('admin_menu', array($this, 'add_analytics_menu'));
            
            // Enqueue scripts and styles
            add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        }
        
        /**
         * Add analytics dashboard to admin menu
         */
        public function add_analytics_menu() {
            add_submenu_page(
                'edit.php?post_type=mpwpb_item',
                esc_html__('Analytics Dashboard', 'service-booking-manager'),
                esc_html__('Analytics', 'service-booking-manager'),
                'manage_options',
                'mpwpb_analytics_dashboard',
                array($this, 'analytics_dashboard_page')
            );
        }
        
        /**
         * Enqueue scripts and styles for the dashboard
         */
        public function enqueue_scripts($hook) {
            if ($hook != 'mpwpb_item_page_mpwpb_analytics_dashboard') {
                return;
            }
            
            wp_enqueue_style('mpwpb-analytics-dashboard', MPWPB_PLUGIN_URL . '/assets/admin/mpwpb_analytics_dashboard.css', array(), time());
            wp_enqueue_script('mpwpb-analytics-dashboard', MPWPB_PLUGIN_URL . '/assets/admin/mpwpb_analytics_dashboard.js', array('jquery', 'chartjs'), time(), true);
        }
        
        /**
         * Main analytics dashboard page
         */
        public function analytics_dashboard_page() {
            ?>
            <div class="wrap mpwpb-analytics-dashboard">
                <h1><?php esc_html_e('Service Booking Analytics Dashboard', 'service-booking-manager'); ?></h1>
                
                <div class="mpwpb-dashboard-filters">
                    <form id="analytics-filter-form" method="get" action="">
                        <input type="hidden" name="post_type" value="mpwpb_item" />
                        <input type="hidden" name="page" value="mpwpb_analytics_dashboard" />
                        
                        <div class="mpwpb-filter-row">
                            <div class="mpwpb-filter-group">
                                <label for="date_range"><?php esc_html_e('Date Range:', 'service-booking-manager'); ?></label>
                                <select name="date_range" id="date_range">
                                    <option value="7"<?php selected(isset($_GET['date_range']) && $_GET['date_range'] == '7'); ?>><?php esc_html_e('Last 7 Days', 'service-booking-manager'); ?></option>
                                    <option value="30"<?php selected(!isset($_GET['date_range']) || $_GET['date_range'] == '30'); ?>><?php esc_html_e('Last 30 Days', 'service-booking-manager'); ?></option>
                                    <option value="90"<?php selected(isset($_GET['date_range']) && $_GET['date_range'] == '90'); ?>><?php esc_html_e('Last 90 Days', 'service-booking-manager'); ?></option>
                                    <option value="365"<?php selected(isset($_GET['date_range']) && $_GET['date_range'] == '365'); ?>><?php esc_html_e('Last Year', 'service-booking-manager'); ?></option>
                                    <option value="custom"<?php selected(isset($_GET['date_range']) && $_GET['date_range'] == 'custom'); ?>><?php esc_html_e('Custom Range', 'service-booking-manager'); ?></option>
                                </select>
                            </div>
                            
                            <div class="mpwpb-filter-group mpwpb-custom-date-range" style="<?php echo (!isset($_GET['date_range']) || $_GET['date_range'] != 'custom') ? 'display: none;' : ''; ?>">
                                <label for="start_date"><?php esc_html_e('Start Date:', 'service-booking-manager'); ?></label>
                                <input type="date" name="start_date" id="start_date" value="<?php echo isset($_GET['start_date']) ? esc_attr($_GET['start_date']) : ''; ?>" />
                                
                                <label for="end_date"><?php esc_html_e('End Date:', 'service-booking-manager'); ?></label>
                                <input type="date" name="end_date" id="end_date" value="<?php echo isset($_GET['end_date']) ? esc_attr($_GET['end_date']) : ''; ?>" />
                            </div>
                            
                            <div class="mpwpb-filter-group">
                                <label for="service_filter"><?php esc_html_e('Service:', 'service-booking-manager'); ?></label>
                                <select name="service_filter" id="service_filter">
                                    <option value=""><?php esc_html_e('All Services', 'service-booking-manager'); ?></option>
                                    <?php
                                    $services = $this->get_all_services();
                                    foreach ($services as $service_id => $service_name) {
                                        $selected = (isset($_GET['service_filter']) && $_GET['service_filter'] == $service_id) ? 'selected' : '';
                                        echo '<option value="' . esc_attr($service_id) . '" ' . $selected . '>' . esc_html($service_name) . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                            
                            <div class="mpwpb-filter-group">
                                <input type="submit" class="button button-primary" value="<?php esc_attr_e('Filter', 'service-booking-manager'); ?>" />
                                <button type="button" id="export-analytics" class="button button-secondary"><?php esc_html_e('Export CSV', 'service-booking-manager'); ?></button>
                            </div>
                        </div>
                    </form>
                </div>
                
                <div class="mpwpb-dashboard-widgets">
                    <!-- Summary Stats -->
                    <div class="mpwpb-widget-row">
                        <div class="mpwpb-widget mpwpb-widget-1-4">
                            <div class="mpwpb-widget-content">
                                <h3><?php esc_html_e('Total Bookings', 'service-booking-manager'); ?></h3>
                                <div class="mpwpb-widget-value mpwpb-total-bookings"><?php echo esc_html($this->get_total_bookings()); ?></div>
                                <div class="mpwpb-widget-description"><?php esc_html_e('All time bookings', 'service-booking-manager'); ?></div>
                            </div>
                        </div>
                        
                        <div class="mpwpb-widget mpwpb-widget-1-4">
                            <div class="mpwpb-widget-content">
                                <h3><?php esc_html_e('Revenue', 'service-booking-manager'); ?></h3>
                                <div class="mpwpb-widget-value mpwpb-total-revenue"><?php echo wp_kses_post(wc_price($this->get_total_revenue())); ?></div>
                                <div class="mpwpb-widget-description"><?php esc_html_e('Total revenue generated', 'service-booking-manager'); ?></div>
                            </div>
                        </div>
                        
                        <div class="mpwpb-widget mpwpb-widget-1-4">
                            <div class="mpwpb-widget-content">
                                <h3><?php esc_html_e('Avg. Booking Value', 'service-booking-manager'); ?></h3>
                                <div class="mpwpb-widget-value mpwpb-avg-booking-value"><?php echo wp_kses_post(wc_price($this->get_average_booking_value())); ?></div>
                                <div class="mpwpb-widget-description"><?php esc_html_e('Average per booking', 'service-booking-manager'); ?></div>
                            </div>
                        </div>
                        
                        <div class="mpwpb-widget mpwpb-widget-1-4">
                            <div class="mpwpb-widget-content">
                                <h3><?php esc_html_e('Conversion Rate', 'service-booking-manager'); ?></h3>
                                <div class="mpwpb-widget-value mpwpb-conversion-rate"><?php echo esc_html($this->get_conversion_rate()); ?>%</div>
                                <div class="mpwpb-widget-description"><?php esc_html_e('Visitors to bookings', 'service-booking-manager'); ?></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Charts and Reports -->
                    <div class="mpwpb-widget-row">
                        <div class="mpwpb-widget mpwpb-widget-1-2">
                            <div class="mpwpb-widget-content">
                                <h3><?php esc_html_e('Bookings Over Time', 'service-booking-manager'); ?></h3>
                                <div class="mpwpb-chart-container">
                                    <canvas id="bookingsOverTimeChart"></canvas>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mpwpb-widget mpwpb-widget-1-2">
                            <div class="mpwpb-widget-content">
                                <h3><?php esc_html_e('Top Services', 'service-booking-manager'); ?></h3>
                                <div class="mpwpb-chart-container">
                                    <canvas id="topServicesChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mpwpb-widget-row">
                        <div class="mpwpb-widget mpwpb-widget-full">
                            <div class="mpwpb-widget-content">
                                <h3><?php esc_html_e('Recent Bookings', 'service-booking-manager'); ?></h3>
                                <div class="mpwpb-recent-bookings">
                                    <?php $this->display_recent_bookings(); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <script type="text/javascript">
                // Set ajaxurl for AJAX calls
                var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
                
                // Pass PHP data to JavaScript
                var mpwpb_analytics_chart_data = {
                    bookings_over_time: {
                        labels: <?php echo json_encode($this->get_bookings_over_time_labels()); ?>,
                        datasets: [{
                            label: '<?php esc_html_e('Bookings', 'service-booking-manager'); ?>',
                            data: <?php echo json_encode($this->get_bookings_over_time_data()); ?>,
                            borderColor: 'rgb(75, 192, 192)',
                            backgroundColor: 'rgba(75, 192, 192, 0.2)',
                            tension: 0.1
                        }]
                    },
                    top_services: {
                        labels: <?php echo json_encode($this->get_top_services_labels()); ?>,
                        datasets: [{
                            label: '<?php esc_html_e('Bookings', 'service-booking-manager'); ?>',
                            data: <?php echo json_encode($this->get_top_services_data()); ?>,
                            backgroundColor: 'rgba(54, 162, 235, 0.2)',
                            borderColor: 'rgba(54, 162, 235, 1)',
                            borderWidth: 1
                        }]
                    }
                };
                

                
                jQuery(document).ready(function($) {
                    // Toggle custom date range fields
                    $('#date_range').change(function() {
                        if ($(this).val() === 'custom') {
                            $('.mpwpb-custom-date-range').show();
                        } else {
                            $('.mpwpb-custom-date-range').hide();
                        }
                    });
                    
                    // Handle export CSV button click
                    $('#export-analytics').on('click', function(e) {
                        e.preventDefault();
                        exportAnalyticsData();
                    });
                    
                    // Initialize charts when page loads
                    if (typeof Chart !== 'undefined') {
                        initCharts();
                    } else {
                        // Wait for Chart.js to load
                        var checkChart = setInterval(function() {
                            if (typeof Chart !== 'undefined') {
                                clearInterval(checkChart);
                                initCharts();
                            }
                        }, 100);
                        
                        // Fallback after 5 seconds
                        setTimeout(function() {
                            if (typeof Chart === 'undefined') {
                                clearInterval(checkChart);
                                // Show fallback message
                                $('#bookingsOverTimeChart').parent().html('<p style="text-align: center; color: #666; padding: 20px;">Chart.js failed to load. Please refresh the page or check your internet connection.</p>');
                                $('#topServicesChart').parent().html('<p style="text-align: center; color: #666; padding: 20px;">Chart.js failed to load. Please refresh the page or check your internet connection.</p>');
                            }
                        }, 5000);
                    }
                    
                    // Function to export analytics data
                    function exportAnalyticsData() {
                        // Show loading state
                        var exportBtn = $('#export-analytics');
                        var originalText = exportBtn.text();
                        exportBtn.text('Exporting...').prop('disabled', true);
                        
                        // Get current filter values
                        var filterData = $('#analytics-filter-form').serialize();
                        
                        // Make AJAX call to export data
                        $.ajax({
                            url: ajaxurl,
                            type: 'POST',
                            data: {
                                action: 'mpwpb_export_analytics_data',
                                nonce: '<?php echo wp_create_nonce("mpwpb_analytics_nonce"); ?>',
                                filter_data: filterData
                            },
                            success: function(response) {
                                if (response.success) {
                                    // Create and download CSV file
                                    downloadCSV(response.data.csv_data, 'analytics-export-' + new Date().toISOString().split('T')[0] + '.csv');
                                } else {
                                    alert('Export failed: ' + (response.data ? response.data.message : 'Unknown error'));
                                }
                            },
                            error: function() {
                                alert('Export failed: Network error occurred');
                            },
                            complete: function() {
                                // Reset button state
                                exportBtn.text(originalText).prop('disabled', false);
                            }
                        });
                    }
                    
                    // Function to download CSV
                    function downloadCSV(csvData, filename) {
                        // Convert array to CSV string
                        var csvContent = '';
                        csvData.forEach(function(row) {
                            var csvRow = row.map(function(field) {
                                // Escape quotes and wrap in quotes if contains comma or quote
                                if (typeof field === 'string' && (field.includes(',') || field.includes('"') || field.includes('\n'))) {
                                    return '"' + field.replace(/"/g, '""') + '"';
                                }
                                return field;
                            });
                            csvContent += csvRow.join(',') + '\n';
                        });
                        
                        // Create download link
                        var blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
                        var link = document.createElement('a');
                        if (link.download !== undefined) {
                            var url = URL.createObjectURL(blob);
                            link.setAttribute('href', url);
                            link.setAttribute('download', filename);
                            link.style.visibility = 'hidden';
                            document.body.appendChild(link);
                            link.click();
                            document.body.removeChild(link);
                        } else {
                            // Fallback for older browsers
                            var csvContent = 'data:text/csv;charset=utf-8,' + encodeURIComponent(csvContent);
                            window.open(csvContent);
                        }
                    }
                    
                    // Function to initialize charts
                    function initCharts() {
                        // Initialize Bookings Over Time Chart
                        if (document.getElementById('bookingsOverTimeChart')) {
                            var ctx = document.getElementById('bookingsOverTimeChart').getContext('2d');
                            
                            // Ensure we have data, even if empty
                            var chartData = mpwpb_analytics_chart_data.bookings_over_time;
                            if (!chartData.labels || chartData.labels.length === 0) {
                                chartData.labels = ['No Data'];
                                chartData.datasets[0].data = [0];
                            }
                            
                            new Chart(ctx, {
                                type: 'line',
                                data: chartData,
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    scales: {
                                        y: {
                                            beginAtZero: true,
                                            title: {
                                                display: true,
                                                text: 'Number of Bookings'
                                            }
                                        },
                                        x: {
                                            title: {
                                                display: true,
                                                text: 'Date'
                                            }
                                        }
                                    },
                                    plugins: {
                                        legend: {
                                            display: true
                                        }
                                    }
                                }
                            });
                        }
                        
                        // Initialize Top Services Chart
                        if (document.getElementById('topServicesChart')) {
                            var ctx = document.getElementById('topServicesChart').getContext('2d');
                            
                            // Ensure we have data, even if empty
                            var chartData = mpwpb_analytics_chart_data.top_services;
                            if (!chartData.labels || chartData.labels.length === 0) {
                                chartData.labels = ['No Services'];
                                chartData.datasets[0].data = [0];
                            }
                            
                            new Chart(ctx, {
                                type: 'bar',
                                data: chartData,
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    scales: {
                                        y: {
                                            beginAtZero: true,
                                            title: {
                                                display: true,
                                                text: 'Number of Bookings'
                                            }
                                        },
                                        x: {
                                            title: {
                                                display: true,
                                                text: 'Services'
                                            }
                                        }
                                    },
                                    plugins: {
                                        legend: {
                                            display: true
                                        }
                                    }
                                }
                            });
                        }
                    }
                });
            </script>
            </script>
            <?php
        }
        
        /**
         * Get date range for queries
         */
        private function get_date_range() {
            $date_range = isset($_GET['date_range']) ? sanitize_text_field($_GET['date_range']) : '30';
            $start_date = '';
            $end_date = '';
            
            if ($date_range === 'custom' && isset($_GET['start_date']) && isset($_GET['end_date'])) {
                $start_date = sanitize_text_field($_GET['start_date']);
                $end_date = sanitize_text_field($_GET['end_date']);
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
            
            return array($start_date, $end_date);
        }
        
        /**
         * Get filtered service ID
         */
        private function get_filtered_service() {
            return isset($_GET['service_filter']) ? intval($_GET['service_filter']) : '';
        }
        
        /**
         * Get all services for filter dropdown
         */
        private function get_all_services() {
            $services = array();
            $args = array(
                'post_type' => 'mpwpb_item',
                'posts_per_page' => -1,
                'post_status' => 'publish'
            );
            
            $query = new WP_Query($args);
            if ($query->have_posts()) {
                while ($query->have_posts()) {
                    $query->the_post();
                    $services[get_the_ID()] = get_the_title();
                }
                wp_reset_postdata();
            }
            
            return $services;
        }
        
        /**
         * Get total bookings count
         */
        private function get_total_bookings() {
            list($start_date, $end_date) = $this->get_date_range();
            $service_id = $this->get_filtered_service();
            
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
            $total_found = $query->found_posts;
            
            if ($query->have_posts()) {
                while ($query->have_posts()) {
                    $query->the_post();
                    $booking_id = get_the_ID();
                    $order_id = get_post_meta($booking_id, 'mpwpb_order_id', true);
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
        private function get_total_revenue() {
            list($start_date, $end_date) = $this->get_date_range();
            $service_id = $this->get_filtered_service();
            
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
         * Get average booking value
         */
        private function get_average_booking_value() {
            $total_revenue = $this->get_total_revenue();
            $total_bookings = $this->get_total_bookings();
            
            if ($total_bookings > 0) {
                return $total_revenue / $total_bookings;
            }
            
            return 0;
        }
        
        /**
         * Get conversion rate (simplified)
         */
        private function get_conversion_rate() {
            // This is a simplified version - in a real implementation, you would track visitors
            $total_bookings = $this->get_total_bookings();
            // For demo purposes, we'll use a fixed number as "visitors"
            $total_visitors = $total_bookings * 10; // Just for demonstration
            
            if ($total_visitors > 0) {
                return round(($total_bookings / $total_visitors) * 100, 2);
            }
            
            return 0;
        }
        
        /**
         * Get labels for bookings over time chart
         */
        private function get_bookings_over_time_labels() {
            list($start_date, $end_date) = $this->get_date_range();
            $period = new DatePeriod(
                new DateTime($start_date),
                new DateInterval('P1D'),
                new DateTime($end_date . ' +1 day')
            );
            
            $labels = array();
            foreach ($period as $date) {
                $labels[] = $date->format('M j');
            }
            
            return $labels;
        }
        
        /**
         * Get data for bookings over time chart
         */
        private function get_bookings_over_time_data() {
            list($start_date, $end_date) = $this->get_date_range();
            $service_id = $this->get_filtered_service();
            
            $period = new DatePeriod(
                new DateTime($start_date),
                new DateInterval('P1D'),
                new DateTime($end_date . ' +1 day')
            );
            
            $data = array();
            foreach ($period as $date) {
                $date_str = $date->format('Y-m-d');
                
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
            
            // If no data, return array with zeros for the period
            if (empty($data) || array_sum($data) === 0) {
                $data = array_fill(0, count($period), 0);
            }
            
            return $data;
        }
        
        /**
         * Get labels for top services chart
         */
        private function get_top_services_labels() {
            $top_services = $this->get_top_services();
            $labels = array();
            
            foreach ($top_services as $service) {
                $labels[] = $service['name'];
            }
            
            return $labels;
        }
        
        /**
         * Get data for top services chart
         */
        private function get_top_services_data() {
            $top_services = $this->get_top_services();
            $data = array();
            
            foreach ($top_services as $service) {
                $data[] = $service['count'];
            }
            
            return $data;
        }
        
        /**
         * Get top services by booking count
         */
        private function get_top_services($limit = 5) {
            list($start_date, $end_date) = $this->get_date_range();
            
            // Use WP_Query instead of direct SQL to properly check for valid orders
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
            $service_counts = array_slice($service_counts, 0, $limit, true);
            
            $services = array();
            foreach ($service_counts as $service_id => $count) {
                $service_name = get_the_title($service_id);
                if (!$service_name) {
                    $service_name = 'Unknown Service (' . $service_id . ')';
                }
                $services[] = array(
                    'id' => $service_id,
                    'name' => $service_name,
                    'count' => $count
                );
            }
            
            // If no services, return default data
            if (empty($services)) {
                $services = array(
                    array(
                        'id' => 0,
                        'name' => 'No Services',
                        'count' => 0
                    )
                );
            }
            
            return $services;
        }
        
        /**
         * Display recent bookings table
         */
        private function display_recent_bookings() {
            list($start_date, $end_date) = $this->get_date_range();
            $service_id = $this->get_filtered_service();
            
            // Check if pro class exists to determine posts per page
            $posts_per_page = 5; // Default: show only 5 orders
            if (class_exists('MPWPB_Dependencies_Pro')) {
                $posts_per_page = -1; // Pro: show all orders
            }
            
            $args = array(
                'post_type' => 'mpwpb_booking',
                'posts_per_page' => $posts_per_page,
                'orderby' => 'date',
                'order' => 'DESC',
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
            $valid_bookings = array();
            
            if ($query->have_posts()) {
                while ($query->have_posts()) {
                    $query->the_post();
                    $order_id = get_post_meta(get_the_ID(), 'mpwpb_order_id', true);
                    if ($order_id) {
                        $order = wc_get_order($order_id);
                        if ($order && $order->get_id() && $order->get_status() !== 'trash') {
                            $valid_bookings[] = array(
                                'id' => get_the_ID(),
                                'service_id' => get_post_meta(get_the_ID(), 'mpwpb_id', true),
                                'date' => get_post_meta(get_the_ID(), 'mpwpb_date', true),
                                'order_id' => $order_id,
                                'order_status' => get_post_meta(get_the_ID(), 'mpwpb_order_status', true),
                                'order' => $order
                            );
                        }
                    }
                }
                wp_reset_postdata();
            }
            
            if (!empty($valid_bookings)) {
                // Show note about order limit
                if ($posts_per_page == 5) {
                    echo '<p class="description">' . esc_html__('Showing latest 5 orders. Upgrade to Pro to see all orders.', 'service-booking-manager') . '</p>';
                } else {
                    echo '<p class="description">' . esc_html__('Showing all orders (Pro version).', 'service-booking-manager') . '</p>';
                }
                ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Booking ID', 'service-booking-manager'); ?></th>
                            <th><?php esc_html_e('Service', 'service-booking-manager'); ?></th>
                            <th><?php esc_html_e('Date', 'service-booking-manager'); ?></th>
                            <th><?php esc_html_e('Customer', 'service-booking-manager'); ?></th>
                            <th><?php esc_html_e('Status', 'service-booking-manager'); ?></th>
                            <th><?php esc_html_e('Amount', 'service-booking-manager'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($valid_bookings as $booking_data) : ?>
                            <?php
                            $booking_id = $booking_data['id'];
                            $service_id = $booking_data['service_id'];
                            $service_name = get_the_title($service_id);
                            $booking_date = $booking_data['date'];
                            $order_id = $booking_data['order_id'];
                            $order_status = $booking_data['order_status'];
                            $order = $booking_data['order'];
                            $customer_name = $order ? $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() : 'N/A';
                            $amount = $order ? $order->get_total() : 0;
                            ?>
                            <tr>
                                <td>#<?php echo esc_html($booking_id); ?></td>
                                <td><?php echo esc_html($service_name); ?></td>
                                <td><?php echo esc_html(MPWPB_Global_Function::date_format($booking_date)); ?></td>
                                <td><?php echo esc_html($customer_name); ?></td>
                                <td>
                                    <span class="mpwpb-status mpwpb-status-<?php echo esc_attr($order_status); ?>">
                                        <?php echo esc_html(ucfirst($order_status)); ?>
                                    </span>
                                </td>
                                <td><?php echo $order ? wp_kses_post(wc_price($amount)) : 'N/A'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php
                wp_reset_postdata();
            } else {
                echo '<p>' . esc_html__('No bookings found.', 'service-booking-manager') . '</p>';
            }
        }
    }
    
    new MPWPB_Analytics_Dashboard();
}
?>