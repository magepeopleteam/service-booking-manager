/**
 * Off Days Filter JavaScript
 * FIXED: Off day setting not working in frontend - 2025-01-14 by Shahnur Alam
 * This file handles filtering of off days and off dates in the frontend date selection
 */
(function($) {
    "use strict";

    /**
     * Filter dates based on off days and off dates settings
     */
    function filterOffDaysAndDates() {
        // Get off days and off dates data from hidden inputs
        var offDaysData = $('#mpwpb_off_days_data').val();
        var offDatesData = $('#mpwpb_off_dates_data').val();
        
        if (!offDaysData && !offDatesData) {
            return; // No off days/dates configured
        }
        
        // Parse off days (comma-separated day names)
        var offDays = offDaysData ? offDaysData.split(',').map(function(day) {
            return day.trim().toLowerCase();
        }) : [];
        
        // Parse off dates (comma-separated dates)
        var offDates = offDatesData ? offDatesData.split(',').map(function(date) {
            return date.trim();
        }) : [];
        
        // Filter date elements in the carousel
        $('.mpwpb_date_carousel .to-book').each(function() {
            var $dateElement = $(this);
            var dateTime = $dateElement.attr('data-date');
            
            if (!dateTime) {
                return;
            }
            
            // Parse the date
            var dateObj = new Date(dateTime);
            var dateString = dateObj.getFullYear() + '-' + 
                           String(dateObj.getMonth() + 1).padStart(2, '0') + '-' + 
                           String(dateObj.getDate()).padStart(2, '0');
            var dayName = dateObj.toLocaleDateString('en-US', { weekday: 'long' }).toLowerCase();
            
            // Check if this date should be filtered out
            var isOffDay = offDays.indexOf(dayName) !== -1;
            var isOffDate = offDates.indexOf(dateString) !== -1;
            
            if (isOffDay || isOffDate) {
                // Hide or disable the date element
                $dateElement.addClass('mpwpb-off-day').prop('disabled', true);
                $dateElement.closest('.owl-item').hide(); // Hide the entire carousel item
                
                // Add visual indication that this is an off day
                if (!$dateElement.find('.mpwpb-off-day-indicator').length) {
                    $dateElement.append('<span class="mpwpb-off-day-indicator">Off Day</span>');
                }
            }
        });
        
        // Also filter any date picker inputs if they exist
        if (typeof $.fn.datepicker !== 'undefined') {
            $('.date_type_edit_recurring').datepicker('option', 'beforeShowDay', function(date) {
                var dateString = date.getFullYear() + '-' + 
                               String(date.getMonth() + 1).padStart(2, '0') + '-' + 
                               String(date.getDate()).padStart(2, '0');
                var dayName = date.toLocaleDateString('en-US', { weekday: 'long' }).toLowerCase();
                
                var isOffDay = offDays.indexOf(dayName) !== -1;
                var isOffDate = offDates.indexOf(dateString) !== -1;
                
                // Return [selectable, css_class, tooltip]
                if (isOffDay || isOffDate) {
                    return [false, 'mpwpb-off-day', 'This date is not available'];
                }
                return [true, '', ''];
            });
        }
    }
    
    /**
     * Initialize off days filtering when document is ready
     */
    $(document).ready(function() {
        // Apply filtering after a short delay to ensure all elements are loaded
        setTimeout(function() {
            filterOffDaysAndDates();
        }, 500);
        
        // Re-apply filtering when carousel is updated or dates are loaded via AJAX
        $(document).on('mpwpb_dates_loaded', function() {
            filterOffDaysAndDates();
        });
        
        // Re-apply filtering when recurring booking area is shown
        $(document).on('click', '.to-book', function() {
            setTimeout(function() {
                filterOffDaysAndDates();
            }, 100);
        });
    });
    
    /**
     * Add CSS styles for off days
     */
    function addOffDayStyles() {
        if ($('#mpwpb-off-day-styles').length === 0) {
            $('head').append(`
                <style id="mpwpb-off-day-styles">
                    .mpwpb-off-day {
                        opacity: 0.5 !important;
                        pointer-events: none !important;
                        background-color: #f5f5f5 !important;
                        color: #999 !important;
                    }
                    .mpwpb-off-day-indicator {
                        position: absolute;
                        top: 50%;
                        left: 50%;
                        transform: translate(-50%, -50%);
                        background: rgba(255, 0, 0, 0.8);
                        color: white;
                        padding: 2px 6px;
                        border-radius: 3px;
                        font-size: 10px;
                        font-weight: bold;
                        z-index: 10;
                    }
                    .ui-datepicker .mpwpb-off-day {
                        background-color: #f5f5f5 !important;
                        color: #999 !important;
                    }
                </style>
            `);
        }
    }
    
    // Add styles when document is ready
    $(document).ready(function() {
        addOffDayStyles();
    });

})(jQuery);