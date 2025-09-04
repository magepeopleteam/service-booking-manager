jQuery(document).ready(function($) {
    // Initialize Chart.js if it's not already loaded
    if (typeof Chart === 'undefined') {
        console.warn('Chart.js is not loaded. Analytics charts will not be displayed.');
        return;
    }
    
    // Toggle custom date range fields
    $('#date_range').on('change', function() {
        if ($(this).val() === 'custom') {
            $('.mpwpb-custom-date-range').show();
        } else {
            $('.mpwpb-custom-date-range').hide();
        }
    });
    
    // Handle form submission
    $('form').on('submit', function(e) {
        e.preventDefault();
        loadAnalyticsData();
    });
    
    // Handle export button
    $('#export-analytics').on('click', function() {
        exportAnalyticsData();
    });
    
    // Initialize charts if elements exist
    if ($('#bookingsOverTimeChart').length > 0) {
        initBookingsOverTimeChart();
    }
    
    if ($('#topServicesChart').length > 0) {
        initTopServicesChart();
    }
    
    // Date picker initialization for custom date range
    if ($('#start_date').length > 0 && $('#end_date').length > 0) {
        // You can add date picker initialization here if needed
        // For now, we'll use the browser's built-in date picker
    }
});

// Chart initialization functions remain the same
function initBookingsOverTimeChart() {
    var ctx = document.getElementById('bookingsOverTimeChart').getContext('2d');
    
    // Default chart data - this would typically be populated by PHP
    var chartData = {
        labels: [],
        datasets: [{
            label: 'Bookings',
            data: [],
            borderColor: 'rgb(75, 192, 192)',
            backgroundColor: 'rgba(75, 192, 192, 0.2)',
            tension: 0.1
        }]
    };
    
    // Check if we have data from PHP
    if (typeof mpwpb_analytics_chart_data !== 'undefined' && 
        typeof mpwpb_analytics_chart_data.bookings_over_time !== 'undefined') {
        chartData = mpwpb_analytics_chart_data.bookings_over_time;
    }
    
    var bookingsChart = new Chart(ctx, {
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
            }
        }
    });
}

function initTopServicesChart() {
    var ctx = document.getElementById('topServicesChart').getContext('2d');
    
    // Default chart data - this would typically be populated by PHP
    var chartData = {
        labels: [],
        datasets: [{
            label: 'Bookings',
            data: [],
            backgroundColor: 'rgba(54, 162, 235, 0.2)',
            borderColor: 'rgba(54, 162, 235, 1)',
            borderWidth: 1
        }]
    };
    
    // Check if we have data from PHP
    if (typeof mpwpb_analytics_chart_data !== 'undefined' && 
        typeof mpwpb_analytics_chart_data.top_services !== 'undefined') {
        chartData = mpwpb_analytics_chart_data.top_services;
    }
    
    var servicesChart = new Chart(ctx, {
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
            }
        }
    });
}

// AJAX functions for dynamic data loading
function loadAnalyticsData() {
    if (typeof mpwpb_analytics === 'undefined') {
        console.warn('Analytics data not available');
        return;
    }
    
    var formData = $('form').serialize();
    
    $.ajax({
        url: mpwpb_analytics.ajax_url,
        type: 'POST',
        data: {
            action: 'mpwpb_load_analytics_data',
            nonce: mpwpb_analytics.nonce,
            filter_data: formData
        },
        success: function(response) {
            if (response.success) {
                // Update dashboard with new data
                updateDashboard(response.data);
            } else {
                console.error('Error loading analytics data:', response.data);
                alert('Error loading analytics data: ' + response.data.message);
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', error);
            alert('AJAX Error: ' + error);
        }
    });
}

function exportAnalyticsData() {
    if (typeof mpwpb_analytics === 'undefined') {
        console.warn('Analytics data not available');
        return;
    }
    
    var formData = $('form').serialize();
    
    $.ajax({
        url: mpwpb_analytics.ajax_url,
        type: 'POST',
        data: {
            action: 'mpwpb_export_analytics_data',
            nonce: mpwpb_analytics.nonce,
            filter_data: formData
        },
        success: function(response) {
            if (response.success) {
                // Create CSV file and download
                downloadCSV(response.data.csv_data, 'analytics-export.csv');
            } else {
                console.error('Error exporting analytics data:', response.data);
                alert('Error exporting analytics data: ' + response.data.message);
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', error);
            alert('AJAX Error: ' + error);
        }
    });
}

function updateDashboard(data) {
    // Update summary stats
    if (data.summary) {
        $('.mpwpb-total-bookings').text(data.summary.total_bookings);
        $('.mpwpb-total-revenue').text(data.summary.total_revenue);
        $('.mpwpb-avg-booking-value').text(data.summary.avg_booking_value);
        $('.mpwpb-conversion-rate').text(data.summary.conversion_rate);
    }
    
    // Update charts if they exist
    // This would require re-initializing the charts with new data
}

function downloadCSV(csvData, filename) {
    // Convert array of arrays to CSV string
    var csvContent = '';
    csvData.forEach(function(rowArray) {
        var row = rowArray.join(',');
        csvContent += row + '\n';
    });
    
    // Create download link
    var blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    var link = document.createElement('a');
    var url = URL.createObjectURL(blob);
    link.setAttribute('href', url);
    link.setAttribute('download', filename);
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}    // Update summary stats
    if (data.summary) {
        $('.mpwpb-total-bookings').text(data.summary.total_bookings);
        $('.mpwpb-total-revenue').text(data.summary.total_revenue);
        $('.mpwpb-avg-booking-value').text(data.summary.avg_booking_value);
        $('.mpwpb-conversion-rate').text(data.summary.conversion_rate);
    }
    
    // Update charts if they exist
    // This would require re-initializing the charts with new data
}