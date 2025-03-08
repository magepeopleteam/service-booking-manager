/**
 * Service Booking Manager - Analytics Dashboard JavaScript
 */
(function($) {
    'use strict';

    // Chart instances
    let revenueChart, bookingsChart, servicesChart, categoriesChart;
    
    // Date range variables
    let startDate, endDate;
    
    // Color palettes
    const chartColors = {
        revenue: {
            primary: 'rgba(0, 200, 83, 0.7)',
            background: 'rgba(0, 200, 83, 0.1)'
        },
        bookings: {
            primary: 'rgba(33, 150, 243, 0.7)',
            background: 'rgba(33, 150, 243, 0.1)'
        },
        services: [
            'rgba(255, 99, 132, 0.8)',
            'rgba(54, 162, 235, 0.8)',
            'rgba(255, 206, 86, 0.8)',
            'rgba(75, 192, 192, 0.8)',
            'rgba(153, 102, 255, 0.8)',
            'rgba(255, 159, 64, 0.8)',
            'rgba(199, 199, 199, 0.8)',
            'rgba(83, 102, 255, 0.8)',
            'rgba(40, 159, 64, 0.8)',
            'rgba(210, 199, 199, 0.8)'
        ],
        categories: [
            'rgba(255, 99, 132, 0.8)',
            'rgba(54, 162, 235, 0.8)',
            'rgba(255, 206, 86, 0.8)',
            'rgba(75, 192, 192, 0.8)',
            'rgba(153, 102, 255, 0.8)',
            'rgba(255, 159, 64, 0.8)',
            'rgba(199, 199, 199, 0.8)',
            'rgba(83, 102, 255, 0.8)',
            'rgba(40, 159, 64, 0.8)',
            'rgba(210, 199, 199, 0.8)'
        ]
    };
    
    /**
     * Initialize the dashboard
     */
    function initDashboard() {
        // Initialize date range picker
        initDateRangePicker();
        
        // Initialize charts
        initCharts();
        
        // Set up event listeners
        setupEventListeners();
        
        // Load initial data (last 30 days by default)
        loadAnalyticsData('30days');
    }
    
    /**
     * Initialize the date range picker
     */
    function initDateRangePicker() {
        $('#mpwpb-date-range').daterangepicker({
            opens: 'left',
            autoApply: false,
            maxDate: new Date(),
            locale: {
                format: 'YYYY-MM-DD'
            },
            ranges: {
                'Today': [moment(), moment()],
                'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                'This Month': [moment().startOf('month'), moment().endOf('month')],
                'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
            }
        });
        
        // Set default date range (last 30 days)
        $('#mpwpb-date-range').data('daterangepicker').setStartDate(moment().subtract(29, 'days'));
        $('#mpwpb-date-range').data('daterangepicker').setEndDate(moment());
        
        startDate = moment().subtract(29, 'days').format('YYYY-MM-DD');
        endDate = moment().format('YYYY-MM-DD');
        
        // Update the input field with formatted date range
        updateDateRangeText();
    }
    
    /**
     * Update the date range text in the input field
     */
    function updateDateRangeText() {
        const formattedStart = moment(startDate).format('MMM D, YYYY');
        const formattedEnd = moment(endDate).format('MMM D, YYYY');
        $('#mpwpb-date-range').val(formattedStart + ' - ' + formattedEnd);
    }
    
    /**
     * Initialize chart instances
     */
    function initCharts() {
        // Set Chart.js defaults
        Chart.defaults.font.family = '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif';
        Chart.defaults.font.size = 12;
        Chart.defaults.color = '#666';
        
        // Revenue Chart
        const revenueCtx = document.getElementById('mpwpb-revenue-chart').getContext('2d');
        revenueChart = new Chart(revenueCtx, {
            type: 'line',
            data: {
                labels: [],
                datasets: [{
                    label: mpwpb_analytics.labels.revenue,
                    data: [],
                    backgroundColor: chartColors.revenue.background,
                    borderColor: chartColors.revenue.primary,
                    borderWidth: 2,
                    pointBackgroundColor: chartColors.revenue.primary,
                    pointBorderColor: '#fff',
                    pointBorderWidth: 1,
                    pointRadius: 3,
                    pointHoverRadius: 5,
                    tension: 0.3,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        callbacks: {
                            label: function(context) {
                                return mpwpb_analytics.currency_symbol + context.raw.toFixed(2);
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return mpwpb_analytics.currency_symbol + value;
                            }
                        }
                    }
                }
            }
        });
        
        // Bookings Chart
        const bookingsCtx = document.getElementById('mpwpb-bookings-chart').getContext('2d');
        bookingsChart = new Chart(bookingsCtx, {
            type: 'bar',
            data: {
                labels: [],
                datasets: [{
                    label: mpwpb_analytics.labels.bookings,
                    data: [],
                    backgroundColor: chartColors.bookings.background,
                    borderColor: chartColors.bookings.primary,
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
        
        // Services Chart
        const servicesCtx = document.getElementById('mpwpb-services-chart').getContext('2d');
        servicesChart = new Chart(servicesCtx, {
            type: 'doughnut',
            data: {
                labels: [],
                datasets: [{
                    data: [],
                    backgroundColor: chartColors.services,
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            boxWidth: 12,
                            padding: 15
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.raw;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = Math.round((value / total) * 100);
                                return label + ': ' + value + ' (' + percentage + '%)';
                            }
                        }
                    }
                }
            }
        });
        
        // Categories Chart
        const categoriesCtx = document.getElementById('mpwpb-categories-chart').getContext('2d');
        categoriesChart = new Chart(categoriesCtx, {
            type: 'pie',
            data: {
                labels: [],
                datasets: [{
                    data: [],
                    backgroundColor: chartColors.categories,
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            boxWidth: 12,
                            padding: 15
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.raw;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = Math.round((value / total) * 100);
                                return label + ': ' + value + ' (' + percentage + '%)';
                            }
                        }
                    }
                }
            }
        });
    }
    
    /**
     * Set up event listeners
     */
    function setupEventListeners() {
        // Apply date range filter
        $('#mpwpb-apply-filter').on('click', function() {
            const picker = $('#mpwpb-date-range').data('daterangepicker');
            startDate = picker.startDate.format('YYYY-MM-DD');
            endDate = picker.endDate.format('YYYY-MM-DD');
            
            loadAnalyticsData('custom');
            
            // Reset active state on quick filter buttons
            $('.mpwpb-quick-filter').removeClass('active');
        });
        
        // Quick filter buttons
        $('.mpwpb-quick-filter').on('click', function() {
            const range = $(this).data('range');
            
            // Set active state
            $('.mpwpb-quick-filter').removeClass('active');
            $(this).addClass('active');
            
            loadAnalyticsData(range);
        });
    }
    
    /**
     * Load analytics data based on date range
     */
    function loadAnalyticsData(range) {
        // Set date range based on selected filter
        if (range !== 'custom') {
            setDateRange(range);
        }
        
        // Show loading indicators
        showLoadingState();
        
        // Make AJAX request to get data
        $.ajax({
            url: mpwpb_analytics.ajax_url,
            type: 'POST',
            data: {
                action: 'mpwpb_get_analytics_data',
                nonce: mpwpb_analytics.nonce,
                start_date: startDate,
                end_date: endDate
            },
            success: function(response) {
                if (response.success) {
                    updateDashboard(response.data);
                } else {
                    console.error('Error loading analytics data:', response.data.message);
                    hideLoadingState();
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', error);
                hideLoadingState();
            }
        });
    }
    
    /**
     * Set date range based on quick filter selection
     */
    function setDateRange(range) {
        const picker = $('#mpwpb-date-range').data('daterangepicker');
        
        switch (range) {
            case 'today':
                startDate = moment().format('YYYY-MM-DD');
                endDate = moment().format('YYYY-MM-DD');
                break;
            case 'yesterday':
                startDate = moment().subtract(1, 'days').format('YYYY-MM-DD');
                endDate = moment().subtract(1, 'days').format('YYYY-MM-DD');
                break;
            case '7days':
                startDate = moment().subtract(6, 'days').format('YYYY-MM-DD');
                endDate = moment().format('YYYY-MM-DD');
                break;
            case '30days':
                startDate = moment().subtract(29, 'days').format('YYYY-MM-DD');
                endDate = moment().format('YYYY-MM-DD');
                break;
            case 'thismonth':
                startDate = moment().startOf('month').format('YYYY-MM-DD');
                endDate = moment().endOf('month').format('YYYY-MM-DD');
                break;
            case 'lastmonth':
                startDate = moment().subtract(1, 'month').startOf('month').format('YYYY-MM-DD');
                endDate = moment().subtract(1, 'month').endOf('month').format('YYYY-MM-DD');
                break;
        }
        
        // Update date range picker
        picker.setStartDate(startDate);
        picker.setEndDate(endDate);
        
        // Update the input field with formatted date range
        updateDateRangeText();
    }
    
    /**
     * Show loading state for all dashboard elements
     */
    function showLoadingState() {
        // KPI cards
        $('#mpwpb-total-revenue, #mpwpb-total-bookings, #mpwpb-popular-services, #mpwpb-new-customers').html('<div class="mpwpb-loading-spinner"></div>');
        $('#mpwpb-revenue-change, #mpwpb-bookings-change, #mpwpb-services-change, #mpwpb-customers-change').html('');
        
        // Recent bookings table
        $('#mpwpb-recent-bookings-body').html('<tr><td colspan="8" class="mpwpb-loading-row"><div class="mpwpb-loading-spinner"></div><p>' + 'Loading recent bookings...' + '</p></td></tr>');
    }
    
    /**
     * Hide loading state (called when data is loaded)
     */
    function hideLoadingState() {
        // This function is not explicitly needed as we replace content when data is loaded
    }
    
    /**
     * Update dashboard with new data
     */
    function updateDashboard(data) {
        // Update KPI cards
        updateKPICards(data);
        
        // Update charts
        updateCharts(data);
        
        // Update recent bookings table
        updateRecentBookings(data.recent_bookings);
    }
    
    /**
     * Update KPI cards with new data
     */
    function updateKPICards(data) {
        // Format currency
        const formattedRevenue = mpwpb_analytics.currency_symbol + data.total_revenue.toFixed(2);
        
        // Update values
        $('#mpwpb-total-revenue').text(formattedRevenue);
        $('#mpwpb-total-bookings').text(data.total_bookings);
        
        // Update popular services
        if (data.popular_services.length > 0) {
            $('#mpwpb-popular-services').text(data.popular_services[0].name);
        } else {
            $('#mpwpb-popular-services').text('-');
        }
        
        $('#mpwpb-new-customers').text(data.new_customers);
        
        // Update change indicators
        updateChangeIndicator('revenue', data.revenue_change);
        updateChangeIndicator('bookings', data.bookings_change);
        updateChangeIndicator('customers', data.customers_change);
    }
    
    /**
     * Update change indicator with percentage and arrow
     */
    function updateChangeIndicator(type, percentage) {
        let changeClass = 'neutral';
        let changeIcon = '';
        
        if (percentage > 0) {
            changeClass = 'positive';
            changeIcon = '<span class="dashicons dashicons-arrow-up-alt"></span>';
        } else if (percentage < 0) {
            changeClass = 'negative';
            changeIcon = '<span class="dashicons dashicons-arrow-down-alt"></span>';
        }
        
        const changeText = percentage !== 0 ? Math.abs(percentage) + '%' : '0%';
        $(`#mpwpb-${type}-change`).html(`${changeIcon} ${changeText}`).addClass(changeClass);
    }
    
    /**
     * Update charts with new data
     */
    function updateCharts(data) {
        // Update Revenue Chart
        updateTimeSeriesChart(revenueChart, data.daily_revenue);
        
        // Update Bookings Chart
        updateTimeSeriesChart(bookingsChart, data.daily_bookings);
        
        // Update Services Chart
        updatePieChart(servicesChart, data.popular_services);
        
        // Update Categories Chart
        updatePieChart(categoriesChart, data.category_distribution);
    }
    
    /**
     * Update time series charts (revenue and bookings)
     */
    function updateTimeSeriesChart(chart, dailyData) {
        const labels = [];
        const values = [];
        
        // Sort dates
        const sortedDates = Object.keys(dailyData).sort();
        
        // Format dates and collect values
        sortedDates.forEach(date => {
            // Format date for display (e.g., "Jan 1")
            const formattedDate = moment(date).format('MMM D');
            labels.push(formattedDate);
            values.push(dailyData[date]);
        });
        
        // Update chart data
        chart.data.labels = labels;
        chart.data.datasets[0].data = values;
        
        // Update chart
        chart.update();
    }
    
    /**
     * Update pie/doughnut charts (services and categories)
     */
    function updatePieChart(chart, items) {
        const labels = [];
        const values = [];
        
        // Extract data
        items.forEach(item => {
            labels.push(item.name);
            values.push(item.count);
        });
        
        // Update chart data
        chart.data.labels = labels;
        chart.data.datasets[0].data = values;
        
        // Update chart
        chart.update();
    }
    
    /**
     * Update recent bookings table
     */
    function updateRecentBookings(bookings) {
        if (bookings.length === 0) {
            $('#mpwpb-recent-bookings-body').html('<tr><td colspan="9" class="mpwpb-loading-row">' + 'No bookings found in the selected date range.' + '</td></tr>');
            return;
        }

        let html = '';

        bookings.forEach(booking => {
            const statusClass = 'mpwpb-status-' + booking.status;
            const orderLink = booking.order_id ? `<a href="${booking.order_url}" target="_blank">#${booking.order_id}</a>` : '-';
            const date = booking.date || '-';
            const time = booking.time || '-';
            const service = booking.service || '-';

            html += `
                <tr>
                    <td>#${booking.id}</td>
                    <td>${orderLink}</td>
                    <td>${booking.customer}</td>
                    <td>${service}</td>
                    <td>${date}</td>
                    <td>${time}</td>
                    <td><span class="mpwpb-booking-status ${statusClass}">${booking.status}</span></td>
                    <td>${mpwpb_analytics.currency_symbol}${booking.amount.toFixed(2)}</td>
                    <td>
                        <a href="${booking.edit_url}" class="mpwpb-action-button" target="_blank">View</a>
                    </td>
                </tr>
            `;
        });

        $('#mpwpb-recent-bookings-body').html(html);
    }
    
    // Initialize dashboard when document is ready
    $(document).ready(function() {
        initDashboard();
    });
    
})(jQuery);