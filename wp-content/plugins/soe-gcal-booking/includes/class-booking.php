<?php
/**
 * Booking management
 */

if (!defined('ABSPATH')) {
    exit;
}

class SOE_GCal_Booking {
    
    /**
     * Create a new booking
     */
    public static function create($class_id, $customer_name, $customer_email, $customer_phone) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'soe_gcal_bookings';
        
        $result = $wpdb->insert($table, [
            'class_id' => intval($class_id),
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
        $classes_table = $wpdb->prefix . 'soe_gcal_classes';
        
        return $wpdb->get_row($wpdb->prepare("
            SELECT b.*, c.title as class_title, c.start_time, c.end_time, c.location
            FROM $bookings_table b
            LEFT JOIN $classes_table c ON b.class_id = c.id
            WHERE b.id = %d
        ", $booking_id));
    }
    
    /**
     * Get upcoming classes
     */
    public static function get_upcoming_classes($limit = 20) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'soe_gcal_classes';
        
        return $wpdb->get_results($wpdb->prepare("
            SELECT * FROM $table
            WHERE start_time > %s
            ORDER BY start_time ASC
            LIMIT %d
        ", current_time('mysql'), $limit));
    }
    
    /**
     * Get bookings for a class
     */
    public static function get_class_bookings($class_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'soe_gcal_bookings';
        
        return $wpdb->get_results($wpdb->prepare("
            SELECT * FROM $table
            WHERE class_id = %d
            ORDER BY created_at ASC
        ", $class_id));
    }
    
    /**
     * Check if email already booked for a class
     */
    public static function is_already_booked($class_id, $email) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'soe_gcal_bookings';
        
        $count = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM $table
            WHERE class_id = %d AND customer_email = %s AND status = 'confirmed'
        ", $class_id, $email));
        
        return $count > 0;
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
        $subject = sprintf(__('Booking Confirmed: %s', 'soe-gcal-booking'), $booking->class_title);
        
        $message = sprintf(
            __("Hello %s,\n\nYour booking has been confirmed!\n\nClass: %s\nDate: %s\nTime: %s - %s\n%s\nThank you for booking with us!\n\nBest regards,\nSOE Edu Consults", 'soe-gcal-booking'),
            $booking->customer_name,
            $booking->class_title,
            date('l, F j, Y', strtotime($booking->start_time)),
            date('g:i A', strtotime($booking->start_time)),
            date('g:i A', strtotime($booking->end_time)),
            $booking->location ? sprintf(__("Location: %s\n", 'soe-gcal-booking'), $booking->location) : ''
        );
        
        $headers = ['Content-Type: text/plain; charset=UTF-8'];
        
        // Also notify admin
        $admin_email = get_option('admin_email');
        $admin_subject = sprintf(__('New Booking: %s - %s', 'soe-gcal-booking'), $booking->class_title, $booking->customer_name);
        $admin_message = sprintf(
            __("New booking received!\n\nClass: %s\nDate: %s\nTime: %s\n\nCustomer: %s\nEmail: %s\nPhone: %s", 'soe-gcal-booking'),
            $booking->class_title,
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

