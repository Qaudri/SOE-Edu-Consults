<?php
/**
 * Frontend booking form
 */

if (!defined('ABSPATH')) {
    exit;
}

class SOE_GCal_Frontend {
    
    /**
     * Initialize frontend hooks
     */
    public static function init() {
        add_action('wp_ajax_soe_gcal_book_class', [__CLASS__, 'ajax_book_class']);
        add_action('wp_ajax_nopriv_soe_gcal_book_class', [__CLASS__, 'ajax_book_class']);
    }
    
    /**
     * Render the booking form shortcode
     */
    public static function render_booking_form($atts = []) {
        $classes = SOE_GCal_Booking_Manager::get_upcoming_classes();
        
        ob_start();
        ?>
        <div class="soe-gcal-booking-container">
            <h2><?php _e('Upcoming Classes', 'soe-gcal-booking'); ?></h2>
            
            <?php if (empty($classes)): ?>
                <p class="soe-no-classes"><?php _e('No upcoming classes at the moment. Please check back later!', 'soe-gcal-booking'); ?></p>
            <?php else: ?>
                <div class="soe-classes-list">
                    <?php foreach ($classes as $class):
                        $display_name = $class->type_name ?: $class->title;
                        $display_description = $class->type_description ?: $class->description;
                        $color = $class->type_color ?: '#3182CE';
                    ?>
                        <div class="soe-class-card" data-class-id="<?php echo esc_attr($class->id); ?>" style="border-left: 4px solid <?php echo esc_attr($color); ?>;">
                            <div class="soe-class-info">
                                <span class="soe-class-type-badge" style="background: <?php echo esc_attr($color); ?>;">
                                    <?php echo esc_html($display_name); ?>
                                </span>
                                <div class="soe-class-datetime">
                                    <span class="soe-class-date">
                                        📅 <?php echo esc_html(date('l, F j, Y', strtotime($class->start_time))); ?>
                                    </span>
                                    <span class="soe-class-time">
                                        🕐 <?php echo esc_html(date('g:i A', strtotime($class->start_time))); ?> -
                                        <?php echo esc_html(date('g:i A', strtotime($class->end_time))); ?>
                                    </span>
                                </div>
                                <?php if ($class->location): ?>
                                    <div class="soe-class-location">
                                        📍 <?php echo esc_html($class->location); ?>
                                    </div>
                                <?php endif; ?>
                                <?php if ($display_description): ?>
                                    <div class="soe-class-description">
                                        <?php echo wp_kses_post(nl2br($display_description)); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <button type="button" class="soe-book-btn" data-class-id="<?php echo esc_attr($class->id); ?>" data-class-name="<?php echo esc_attr($display_name); ?>">
                                <?php _e('Book This Class', 'soe-gcal-booking'); ?>
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <!-- Booking Modal -->
            <div id="soe-booking-modal" class="soe-modal" style="display: none;">
                <div class="soe-modal-content">
                    <span class="soe-modal-close">&times;</span>
                    <h3><?php _e('Book Class', 'soe-gcal-booking'); ?></h3>
                    <p class="soe-modal-class-name"></p>
                    
                    <form id="soe-booking-form">
                        <input type="hidden" name="class_id" id="soe-booking-class-id">
                        
                        <div class="soe-form-group">
                            <label for="soe-customer-name"><?php _e('Your Name', 'soe-gcal-booking'); ?> *</label>
                            <input type="text" id="soe-customer-name" name="customer_name" required>
                        </div>
                        
                        <div class="soe-form-group">
                            <label for="soe-customer-email"><?php _e('Email Address', 'soe-gcal-booking'); ?> *</label>
                            <input type="email" id="soe-customer-email" name="customer_email" required>
                        </div>
                        
                        <div class="soe-form-group">
                            <label for="soe-customer-phone"><?php _e('Phone Number', 'soe-gcal-booking'); ?> *</label>
                            <input type="tel" id="soe-customer-phone" name="customer_phone" required>
                        </div>
                        
                        <div class="soe-form-actions">
                            <button type="submit" class="soe-submit-btn"><?php _e('Confirm Booking', 'soe-gcal-booking'); ?></button>
                        </div>
                        
                        <div class="soe-form-message" style="display: none;"></div>
                    </form>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Handle AJAX booking request
     */
    public static function ajax_book_class() {
        check_ajax_referer('soe_gcal_booking', 'nonce');
        
        $class_id = intval($_POST['class_id'] ?? 0);
        $customer_name = sanitize_text_field($_POST['customer_name'] ?? '');
        $customer_email = sanitize_email($_POST['customer_email'] ?? '');
        $customer_phone = sanitize_text_field($_POST['customer_phone'] ?? '');
        
        // Validation
        if (!$class_id || !$customer_name || !$customer_email || !$customer_phone) {
            wp_send_json_error(['message' => __('Please fill in all required fields.', 'soe-gcal-booking')]);
        }
        
        if (!is_email($customer_email)) {
            wp_send_json_error(['message' => __('Please enter a valid email address.', 'soe-gcal-booking')]);
        }
        
        // Check if already booked
        if (SOE_GCal_Booking_Manager::is_already_booked($class_id, $customer_email)) {
            wp_send_json_error(['message' => __('You have already booked this class.', 'soe-gcal-booking')]);
        }

        // Create booking
        $booking_id = SOE_GCal_Booking_Manager::create($class_id, $customer_name, $customer_email, $customer_phone);
        
        if ($booking_id) {
            wp_send_json_success([
                'message' => __('Booking confirmed! Check your email for details.', 'soe-gcal-booking'),
                'booking_id' => $booking_id
            ]);
        } else {
            wp_send_json_error(['message' => __('Failed to create booking. Please try again.', 'soe-gcal-booking')]);
        }
    }
}

// Initialize AJAX handlers
SOE_GCal_Frontend::init();

