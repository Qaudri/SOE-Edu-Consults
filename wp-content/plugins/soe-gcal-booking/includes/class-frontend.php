<?php
/**
 * Frontend booking form - Multi-step flow
 */

if (!defined('ABSPATH')) {
    exit;
}

class SOE_GCal_Frontend {

    /**
     * Initialize frontend hooks
     */
    public static function init() {
        add_action('wp_ajax_soe_gcal_get_sessions', [__CLASS__, 'ajax_get_sessions']);
        add_action('wp_ajax_nopriv_soe_gcal_get_sessions', [__CLASS__, 'ajax_get_sessions']);
        add_action('wp_ajax_soe_gcal_book_session', [__CLASS__, 'ajax_book_session']);
        add_action('wp_ajax_nopriv_soe_gcal_book_session', [__CLASS__, 'ajax_book_session']);
    }

    /**
     * Render the booking form shortcode - Multi-step flow
     */
    public static function render_booking_form($atts = []) {
        $classes = SOE_GCal_Booking_Manager::get_classes();

        ob_start();
        ?>
        <div class="soe-booking-container" data-nonce="<?php echo wp_create_nonce('soe_gcal_booking'); ?>">

            <!-- Step Indicator -->
            <div class="soe-steps">
                <div class="soe-step active" data-step="1">
                    <span class="soe-step-number">1</span>
                    <span class="soe-step-label"><?php _e('Select Class', 'soe-gcal-booking'); ?></span>
                </div>
                <div class="soe-step" data-step="2">
                    <span class="soe-step-number">2</span>
                    <span class="soe-step-label"><?php _e('Choose Time', 'soe-gcal-booking'); ?></span>
                </div>
                <div class="soe-step" data-step="3">
                    <span class="soe-step-number">3</span>
                    <span class="soe-step-label"><?php _e('Your Details', 'soe-gcal-booking'); ?></span>
                </div>
                <div class="soe-step" data-step="4">
                    <span class="soe-step-number">4</span>
                    <span class="soe-step-label"><?php _e('Confirmed', 'soe-gcal-booking'); ?></span>
                </div>
            </div>

            <!-- Step 1: Select Class -->
            <div class="soe-step-content active" data-step="1">
                <h2><?php _e('Choose a Class', 'soe-gcal-booking'); ?></h2>

                <?php if (empty($classes)): ?>
                    <p class="soe-no-classes"><?php _e('No classes available at the moment. Please check back later!', 'soe-gcal-booking'); ?></p>
                <?php else: ?>
                    <div class="soe-classes-grid">
                        <?php foreach ($classes as $class): ?>
                            <div class="soe-class-card"
                                 data-class-id="<?php echo esc_attr($class->id); ?>"
                                 data-class-name="<?php echo esc_attr($class->name); ?>"
                                 style="border-top: 4px solid <?php echo esc_attr($class->color ?: '#3182CE'); ?>;">
                                <h3 class="soe-class-name"><?php echo esc_html($class->name); ?></h3>
                                <?php if ($class->description): ?>
                                    <p class="soe-class-desc"><?php echo esc_html($class->description); ?></p>
                                <?php endif; ?>
                                <div class="soe-class-meta">
                                    <span class="soe-class-duration">⏱ <?php echo intval($class->duration); ?> min</span>
                                    <span class="soe-class-sessions"><?php echo intval($class->available_sessions); ?> <?php _e('sessions available', 'soe-gcal-booking'); ?></span>
                                </div>
                                <button type="button" class="soe-select-class-btn"><?php _e('Select', 'soe-gcal-booking'); ?></button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Step 2: Select Session -->
            <div class="soe-step-content" data-step="2">
                <button type="button" class="soe-back-btn" data-goto="1">← <?php _e('Back to Classes', 'soe-gcal-booking'); ?></button>
                <h2><?php _e('Choose a Time Slot', 'soe-gcal-booking'); ?></h2>
                <p class="soe-selected-class-name"></p>
                <div class="soe-sessions-list">
                    <div class="soe-loading"><?php _e('Loading available times...', 'soe-gcal-booking'); ?></div>
                </div>
            </div>

            <!-- Step 3: Booking Form -->
            <div class="soe-step-content" data-step="3">
                <button type="button" class="soe-back-btn" data-goto="2">← <?php _e('Back to Sessions', 'soe-gcal-booking'); ?></button>
                <h2><?php _e('Your Details', 'soe-gcal-booking'); ?></h2>

                <div class="soe-booking-summary">
                    <p><strong><?php _e('Class:', 'soe-gcal-booking'); ?></strong> <span class="soe-summary-class"></span></p>
                    <p><strong><?php _e('Date:', 'soe-gcal-booking'); ?></strong> <span class="soe-summary-date"></span></p>
                    <p><strong><?php _e('Time:', 'soe-gcal-booking'); ?></strong> <span class="soe-summary-time"></span></p>
                </div>

                <form id="soe-booking-form">
                    <input type="hidden" name="session_id" id="soe-booking-session-id">

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

            <!-- Step 4: Confirmation -->
            <div class="soe-step-content" data-step="4">
                <div class="soe-confirmation">
                    <div class="soe-confirmation-icon">✓</div>
                    <h2><?php _e('Booking Confirmed!', 'soe-gcal-booking'); ?></h2>
                    <p><?php _e('Thank you for your booking. A confirmation email has been sent to your email address.', 'soe-gcal-booking'); ?></p>
                    <div class="soe-confirmation-details"></div>
                    <button type="button" class="soe-new-booking-btn"><?php _e('Book Another Class', 'soe-gcal-booking'); ?></button>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * AJAX: Get sessions for a class
     */
    public static function ajax_get_sessions() {
        check_ajax_referer('soe_gcal_booking', 'nonce');

        $class_id = intval($_POST['class_id'] ?? 0);

        if (!$class_id) {
            wp_send_json_error(['message' => __('Invalid class.', 'soe-gcal-booking')]);
        }

        $sessions = SOE_GCal_Booking_Manager::get_class_sessions($class_id);

        $formatted = [];
        foreach ($sessions as $session) {
            $spots_left = $session->max_capacity - $session->booking_count;
            $formatted[] = [
                'id' => $session->id,
                'date' => date('l, F j, Y', strtotime($session->start_time)),
                'date_short' => date('M j', strtotime($session->start_time)),
                'start_time' => date('g:i A', strtotime($session->start_time)),
                'end_time' => date('g:i A', strtotime($session->end_time)),
                'location' => $session->location ?: '',
                'spots_left' => $spots_left,
                'max_capacity' => $session->max_capacity
            ];
        }

        wp_send_json_success(['sessions' => $formatted]);
    }

    /**
     * AJAX: Book a session
     */
    public static function ajax_book_session() {
        check_ajax_referer('soe_gcal_booking', 'nonce');

        $session_id = intval($_POST['session_id'] ?? 0);
        $customer_name = sanitize_text_field($_POST['customer_name'] ?? '');
        $customer_email = sanitize_email($_POST['customer_email'] ?? '');
        $customer_phone = sanitize_text_field($_POST['customer_phone'] ?? '');

        // Validation
        if (!$session_id || !$customer_name || !$customer_email || !$customer_phone) {
            wp_send_json_error(['message' => __('Please fill in all required fields.', 'soe-gcal-booking')]);
        }

        if (!is_email($customer_email)) {
            wp_send_json_error(['message' => __('Please enter a valid email address.', 'soe-gcal-booking')]);
        }

        // Check capacity
        if (!SOE_GCal_Booking_Manager::has_capacity($session_id)) {
            wp_send_json_error(['message' => __('Sorry, this session is now full. Please choose another time.', 'soe-gcal-booking')]);
        }

        // Check if already booked
        if (SOE_GCal_Booking_Manager::is_already_booked($session_id, $customer_email)) {
            wp_send_json_error(['message' => __('You have already booked this session.', 'soe-gcal-booking')]);
        }

        // Create booking
        $booking_id = SOE_GCal_Booking_Manager::create($session_id, $customer_name, $customer_email, $customer_phone);

        if ($booking_id) {
            $booking = SOE_GCal_Booking_Manager::get($booking_id);
            wp_send_json_success([
                'message' => __('Booking confirmed! Check your email for details.', 'soe-gcal-booking'),
                'booking_id' => $booking_id,
                'details' => [
                    'class_name' => $booking->class_name,
                    'date' => date('l, F j, Y', strtotime($booking->start_time)),
                    'time' => date('g:i A', strtotime($booking->start_time)) . ' - ' . date('g:i A', strtotime($booking->end_time)),
                    'location' => $booking->location
                ]
            ]);
        } else {
            wp_send_json_error(['message' => __('Failed to create booking. Please try again.', 'soe-gcal-booking')]);
        }
    }
}

// Initialize AJAX handlers
SOE_GCal_Frontend::init();

