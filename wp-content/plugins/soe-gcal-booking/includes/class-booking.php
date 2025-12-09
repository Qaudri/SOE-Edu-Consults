<?php
/**
 * Booking management
 */

if (!defined('ABSPATH')) {
    exit;
}

class SOE_GCal_Booking_Manager {

    /**
     * Create a new booking for a session
     */
    public static function create($session_id, $customer_name, $customer_email, $customer_phone) {
        global $wpdb;

        $table = $wpdb->prefix . 'soe_gcal_bookings';

        $result = $wpdb->insert($table, [
            'session_id' => intval($session_id),
            'customer_name' => sanitize_text_field($customer_name),
            'customer_email' => sanitize_email($customer_email),
            'customer_phone' => sanitize_text_field($customer_phone),
            'status' => 'confirmed',
            'created_at' => current_time('mysql')
        ]);

        if ($result) {
            $booking_id = $wpdb->insert_id;
            self::send_confirmation_email($booking_id);
            return $booking_id;
        }

        return false;
    }

    /**
     * Get a booking by ID
     */
    public static function get($booking_id) {
        global $wpdb;

        $bookings_table = $wpdb->prefix . 'soe_gcal_bookings';
        $sessions_table = $wpdb->prefix . 'soe_gcal_sessions';
        $classes_table = $wpdb->prefix . 'soe_gcal_classes';

        return $wpdb->get_row($wpdb->prepare("
            SELECT b.*, c.name as class_name, s.start_time, s.end_time, s.location
            FROM $bookings_table b
            LEFT JOIN $sessions_table s ON b.session_id = s.id
            LEFT JOIN $classes_table c ON s.class_id = c.id
            WHERE b.id = %d
        ", $booking_id));
    }

    /**
     * Get all classes (for frontend display)
     */
    public static function get_classes() {
        global $wpdb;

        $classes_table = $wpdb->prefix . 'soe_gcal_classes';
        $sessions_table = $wpdb->prefix . 'soe_gcal_sessions';
        $bookings_table = $wpdb->prefix . 'soe_gcal_bookings';

        // Get classes that have upcoming sessions with available capacity
        return $wpdb->get_results("
            SELECT c.*,
                   COUNT(DISTINCT CASE WHEN s.start_time > NOW() THEN s.id END) as available_sessions
            FROM $classes_table c
            LEFT JOIN $sessions_table s ON c.id = s.class_id
            GROUP BY c.id
            HAVING available_sessions > 0
            ORDER BY c.name ASC
        ");
    }

    /**
     * Get available sessions for a class
     */
    public static function get_class_sessions($class_id) {
        global $wpdb;

        $sessions_table = $wpdb->prefix . 'soe_gcal_sessions';
        $bookings_table = $wpdb->prefix . 'soe_gcal_bookings';

        return $wpdb->get_results($wpdb->prepare("
            SELECT s.*,
                   (SELECT COUNT(*) FROM $bookings_table WHERE session_id = s.id AND status = 'confirmed') as booking_count
            FROM $sessions_table s
            WHERE s.class_id = %d AND s.start_time > NOW()
            HAVING booking_count < s.max_capacity
            ORDER BY s.start_time ASC
        ", $class_id));
    }

    /**
     * Get a session by ID
     */
    public static function get_session($session_id) {
        global $wpdb;

        $sessions_table = $wpdb->prefix . 'soe_gcal_sessions';
        $classes_table = $wpdb->prefix . 'soe_gcal_classes';
        $bookings_table = $wpdb->prefix . 'soe_gcal_bookings';

        return $wpdb->get_row($wpdb->prepare("
            SELECT s.*, c.name as class_name, c.description as class_description, c.color as class_color,
                   (SELECT COUNT(*) FROM $bookings_table WHERE session_id = s.id AND status = 'confirmed') as booking_count
            FROM $sessions_table s
            LEFT JOIN $classes_table c ON s.class_id = c.id
            WHERE s.id = %d
        ", $session_id));
    }

    /**
     * Check if email already booked for a session
     */
    public static function is_already_booked($session_id, $email) {
        global $wpdb;

        $table = $wpdb->prefix . 'soe_gcal_bookings';

        $count = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM $table
            WHERE session_id = %d AND customer_email = %s AND status = 'confirmed'
        ", $session_id, $email));

        return $count > 0;
    }

    /**
     * Check if session has available capacity
     */
    public static function has_capacity($session_id) {
        global $wpdb;

        $sessions_table = $wpdb->prefix . 'soe_gcal_sessions';
        $bookings_table = $wpdb->prefix . 'soe_gcal_bookings';

        $session = $wpdb->get_row($wpdb->prepare("
            SELECT s.max_capacity,
                   (SELECT COUNT(*) FROM $bookings_table WHERE session_id = s.id AND status = 'confirmed') as booking_count
            FROM $sessions_table s
            WHERE s.id = %d
        ", $session_id));

        return $session && ($session->booking_count < $session->max_capacity);
    }

    /**
     * Send confirmation email to customer
     */
    private static function send_confirmation_email($booking_id) {
        $booking = self::get($booking_id);

        if (!$booking) {
            return false;
        }

        $to = $booking->customer_email;
        $subject = sprintf(__('Booking Confirmed: %s', 'soe-gcal-booking'), $booking->class_name);

        $message = sprintf(
            __("Hello %s,\n\nYour booking has been confirmed!\n\nClass: %s\nDate: %s\nTime: %s - %s\n%s\nThank you for booking with us!\n\nBest regards,\nSOE Edu Consults", 'soe-gcal-booking'),
            $booking->customer_name,
            $booking->class_name,
            date('l, F j, Y', strtotime($booking->start_time)),
            date('g:i A', strtotime($booking->start_time)),
            date('g:i A', strtotime($booking->end_time)),
            $booking->location ? sprintf(__("Location: %s\n", 'soe-gcal-booking'), $booking->location) : ''
        );

        $headers = ['Content-Type: text/plain; charset=UTF-8'];

        // Also notify admin
        $admin_email = get_option('admin_email');
        $admin_subject = sprintf(__('New Booking: %s - %s', 'soe-gcal-booking'), $booking->class_name, $booking->customer_name);
        $admin_message = sprintf(
            __("New booking received!\n\nClass: %s\nDate: %s\nTime: %s\n\nCustomer: %s\nEmail: %s\nPhone: %s", 'soe-gcal-booking'),
            $booking->class_name,
            date('l, F j, Y', strtotime($booking->start_time)),
            date('g:i A', strtotime($booking->start_time)),
            $booking->customer_name,
            $booking->customer_email,
            $booking->customer_phone
        );

        wp_mail($to, $subject, $message, $headers);
        wp_mail($admin_email, $admin_subject, $admin_message, $headers);

        return true;
    }

    /**
     * Cancel a booking
     */
    public static function cancel($booking_id) {
        global $wpdb;

        $table = $wpdb->prefix . 'soe_gcal_bookings';

        return $wpdb->update($table,
            ['status' => 'cancelled'],
            ['id' => $booking_id]
        );
    }
}

