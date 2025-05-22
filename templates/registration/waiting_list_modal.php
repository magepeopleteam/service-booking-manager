<?php
/*
* @Author 		MagePeople Team
* Copyright: 	mage-people.com
*/
if (!defined('ABSPATH')) {
    die;
} // Cannot access pages directly.
?>
<div class="mpwpb_waiting_list_modal" style="display: none;">
    <div class="mpwpb_modal_content">
        <div class="mpwpb_modal_header">
            <h3><i class="fas fa-user-clock"></i> <?php esc_html_e('Join Waiting List', 'service-booking-manager'); ?></h3>
            <span class="mpwpb_close_modal">&times;</span>
        </div>
        
        <div class="mpwpb_modal_body">
            <div class="mpwpb_info_box">
                <i class="fas fa-info-circle"></i>
                <p><?php esc_html_e('This time slot is currently fully booked. Join our waiting list to be notified if a spot becomes available.', 'service-booking-manager'); ?></p>
            </div>
            
            <form id="mpwpb_waiting_list_form">
                <input type="hidden" name="post_id" id="waiting_list_post_id" value="">
                <input type="hidden" name="date" id="waiting_list_date" value="">
                <input type="hidden" name="nonce" value="<?php echo esc_attr(wp_create_nonce('mpwpb_nonce')); ?>">
                
                <div class="mpwpb_form_group">
                    <label for="waiting_list_name">
                        <i class="fas fa-user"></i> <?php esc_html_e('Your Name', 'service-booking-manager'); ?> <span class="required">*</span>
                    </label>
                    <input type="text" id="waiting_list_name" name="name" placeholder="<?php esc_attr_e('Enter your full name', 'service-booking-manager'); ?>" required>
                </div>
                
                <div class="mpwpb_form_group">
                    <label for="waiting_list_email">
                        <i class="fas fa-envelope"></i> <?php esc_html_e('Your Email', 'service-booking-manager'); ?> <span class="required">*</span>
                    </label>
                    <input type="email" id="waiting_list_email" name="email" placeholder="<?php esc_attr_e('Enter your email address', 'service-booking-manager'); ?>" required>
                </div>
                
                <div class="mpwpb_form_group">
                    <label for="waiting_list_phone">
                        <i class="fas fa-phone"></i> <?php esc_html_e('Your Phone', 'service-booking-manager'); ?>
                    </label>
                    <input type="tel" id="waiting_list_phone" name="phone" placeholder="<?php esc_attr_e('Enter your phone number', 'service-booking-manager'); ?>">
                </div>
                
                <div class="mpwpb_form_footer">
                    <div class="mpwpb_waiting_list_message"></div>
                    <button type="submit" class="mpwpb_submit_waiting_list _mpBtn">
                        <i class="fas fa-paper-plane"></i> <?php esc_html_e('Join Waiting List', 'service-booking-manager'); ?>
                    </button>
                </div>
            </form>
        </div>
        
        <div class="mpwpb_modal_footer">
            <p class="mpwpb_privacy_note">
                <i class="fas fa-shield-alt"></i> <?php esc_html_e('Your information will only be used to notify you about availability.', 'service-booking-manager'); ?>
            </p>
        </div>
    </div>
</div>

<style>
/* Enhanced Waiting List Modal Styles */
.mpwpb_waiting_list_modal {
    display: none;
    position: fixed;
    z-index: 9999;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(3px);
    animation: mpwpb_fade_in 0.3s ease-out;
}

@keyframes mpwpb_fade_in {
    from { opacity: 0; }
    to { opacity: 1; }
}

.mpwpb_modal_content {
    background-color: #fff;
    margin: 5% auto;
    width: 90%;
    max-width: 500px;
    border-radius: 8px;
    box-shadow: 0 5px 25px rgba(0, 0, 0, 0.2);
    position: relative;
    overflow: hidden;
    transform: translateY(20px);
    animation: mpwpb_slide_up 0.4s ease-out forwards;
}

@keyframes mpwpb_slide_up {
    to { transform: translateY(0); }
}

.mpwpb_modal_header {
    background: var(--mpwpb-theme-color, #0073aa);
    color: white;
    padding: 15px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-top-left-radius: 8px;
    border-top-right-radius: 8px;
}

.mpwpb_modal_header h3 {
    margin: 0;
    font-size: 1.3em;
    display: flex;
    align-items: center;
    gap: 10px;
    color: white;
}

.mpwpb_close_modal {
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
    color: rgba(255, 255, 255, 0.8);
    transition: color 0.2s;
}

.mpwpb_close_modal:hover {
    color: white;
}

.mpwpb_modal_body {
    padding: 20px;
}

.mpwpb_info_box {
    background-color: #f8f9fa;
    border-left: 4px solid var(--mpwpb-theme-color, #0073aa);
    padding: 15px;
    margin-bottom: 20px;
    border-radius: 4px;
    display: flex;
    align-items: flex-start;
    gap: 12px;
}

.mpwpb_info_box i {
    color: var(--mpwpb-theme-color, #0073aa);
    font-size: 1.2em;
    margin-top: 3px;
}

.mpwpb_info_box p {
    margin: 0;
    color: #555;
    line-height: 1.5;
}

.mpwpb_form_group {
    margin-bottom: 20px;
}

.mpwpb_form_group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: #333;
}

.mpwpb_form_group label i {
    color: var(--mpwpb-theme-color, #0073aa);
    margin-right: 5px;
}

.mpwpb_form_group label .required {
    color: #e53935;
}

.mpwpb_form_group input {
    width: 100%;
    padding: 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
    transition: border-color 0.3s, box-shadow 0.3s;
}

.mpwpb_form_group input:focus {
    border-color: var(--mpwpb-theme-color, #0073aa);
    box-shadow: 0 0 0 2px rgba(0, 115, 170, 0.2);
    outline: none;
}

.mpwpb_form_footer {
    margin-top: 25px;
}

.mpwpb_submit_waiting_list {
    background-color: var(--mpwpb-theme-color, #0073aa);
    color: white;
    border: none;
    padding: 12px 20px;
    border-radius: 4px;
    cursor: pointer;
    font-weight: 600;
    font-size: 15px;
    transition: background-color 0.3s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    width: 100%;
}

.mpwpb_submit_waiting_list:hover {
    background-color: var(--mpwpb-theme-hover-color, #005177);
}

.mpwpb_waiting_list_message {
    margin-bottom: 15px;
    padding: 0;
    min-height: 24px;
}

.mpwpb_waiting_list_message .success {
    color: #2e7d32;
    background-color: #e8f5e9;
    border: 1px solid #c8e6c9;
    padding: 10px 15px;
    border-radius: 4px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.mpwpb_waiting_list_message .success:before {
    content: '\f058';
    font-family: 'Font Awesome 5 Free';
    font-weight: 900;
}

.mpwpb_waiting_list_message .error {
    color: #c62828;
    background-color: #ffebee;
    border: 1px solid #ffcdd2;
    padding: 10px 15px;
    border-radius: 4px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.mpwpb_waiting_list_message .error:before {
    content: '\f057';
    font-family: 'Font Awesome 5 Free';
    font-weight: 900;
}

.mpwpb_modal_footer {
    background-color: #f8f9fa;
    padding: 12px 20px;
    border-top: 1px solid #eee;
}

.mpwpb_privacy_note {
    color: #666;
    font-size: 13px;
    margin: 0;
    text-align: center;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.mpwpb_privacy_note i {
    color: #888;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .mpwpb_modal_content {
        width: 95%;
        margin: 10% auto;
    }
    
    .mpwpb_modal_header h3 {
        font-size: 1.1em;
    }
}

/* Loading indicator for form submission */
.mpwpb_form_loading .mpwpb_submit_waiting_list {
    position: relative;
    color: transparent;
}

.mpwpb_form_loading .mpwpb_submit_waiting_list:after {
    content: '';
    position: absolute;
    width: 20px;
    height: 20px;
    border: 3px solid rgba(255, 255, 255, 0.3);
    border-radius: 50%;
    border-top-color: white;
    animation: mpwpb_spin 1s ease-in-out infinite;
}

@keyframes mpwpb_spin {
    to { transform: rotate(360deg); }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Add loading state to form on submission
    $('#mpwpb_waiting_list_form').on('submit', function() {
        $(this).addClass('mpwpb_form_loading');
    });
    
    // Enhance form field focus effects
    $('.mpwpb_form_group input').on('focus', function() {
        $(this).parent().addClass('input-focused');
    }).on('blur', function() {
        $(this).parent().removeClass('input-focused');
    });
});
</script>
