<?php
/**
 * Plugin Name: SOE Google Calendar Booking
 * Description: Class booking system synced with Google Calendar
 * Version: 1.0.0
 * Author: SOE Edu Consults
 * Text Domain: soe-gcal-booking
 */

if (!defined('ABSPATH')) {
    exit;
}

// Plugin constants
define('SOE_GCAL_VERSION', '1.0.0');
define('SOE_GCAL_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SOE_GCAL_PLUGIN_URL', plugin_dir_url(__FILE__));

// Check for required credentials in wp-config.php
if (!defined('SOE_GCAL_CLIENT_ID') || !defined('SOE_GCAL_CLIENT_SECRET')) {
    add_action('admin_notices', function() {
        echo '<div class="notice notice-error"><p><strong>SOE GCal Booking:</strong> Please add your Google API credentials to wp-config.php. See plugin documentation.</p></div>';
    });
}

// Autoload classes
spl_autoload_register(function($class) {
    $prefix = 'SOE_GCal_';
    if (strpos($class, $prefix) !== 0) {
        return;
    }
    
    $class_name = str_replace($prefix, '', $class);
    $class_name = strtolower(str_replace('_', '-', $class_name));
    $file = SOE_GCAL_PLUGIN_DIR . 'includes/class-' . $class_name . '.php';
    
    if (file_exists($file)) {
        require_once $file;
    }
});

// Initialize plugin
class SOE_GCal_Booking {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
    }
    
    private function load_dependencies() {
        require_once SOE_GCAL_PLUGIN_DIR . 'includes/class-admin.php';
        require_once SOE_GCAL_PLUGIN_DIR . 'includes/class-google-api.php';
        require_once SOE_GCAL_PLUGIN_DIR . 'includes/class-booking.php';
        require_once SOE_GCAL_PLUGIN_DIR . 'includes/class-frontend.php';
    }
    
    private function init_hooks() {
        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);
        
        add_action('init', [$this, 'init']);
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'admin_assets']);
        add_action('wp_enqueue_scripts', [$this, 'frontend_assets']);
    }
    
    public function activate() {
        $this->create_tables();
        flush_rewrite_rules();
    }
    
    public function deactivate() {
        flush_rewrite_rules();
    }
    
    public function init() {
        // Register shortcode
        add_shortcode('soe_class_booking', ['SOE_GCal_Frontend', 'render_booking_form']);
    }
    
    public function add_admin_menu() {
        add_menu_page(
            __('Class Booking', 'soe-gcal-booking'),
            __('Class Booking', 'soe-gcal-booking'),
            'manage_options',
            'soe-gcal-booking',
            ['SOE_GCal_Admin', 'render_settings_page'],
            'dashicons-calendar-alt',
            30
        );

        add_submenu_page(
            'soe-gcal-booking',
            __('Class Types', 'soe-gcal-booking'),
            __('Class Types', 'soe-gcal-booking'),
            'manage_options',
            'soe-gcal-class-types',
            ['SOE_GCal_Admin', 'render_class_types_page']
        );

        add_submenu_page(
            'soe-gcal-booking',
            __('Sessions', 'soe-gcal-booking'),
            __('Sessions', 'soe-gcal-booking'),
            'manage_options',
            'soe-gcal-classes',
            ['SOE_GCal_Admin', 'render_classes_page']
        );

        add_submenu_page(
            'soe-gcal-booking',
            __('Bookings', 'soe-gcal-booking'),
            __('Bookings', 'soe-gcal-booking'),
            'manage_options',
            'soe-gcal-bookings',
            ['SOE_GCal_Admin', 'render_bookings_page']
        );
    }
    
    public function admin_assets($hook) {
        if (strpos($hook, 'soe-gcal') === false) {
            return;
        }
        wp_enqueue_style('soe-gcal-admin', SOE_GCAL_PLUGIN_URL . 'assets/css/admin.css', [], SOE_GCAL_VERSION);
        wp_enqueue_script('soe-gcal-admin', SOE_GCAL_PLUGIN_URL . 'assets/js/admin.js', ['jquery'], SOE_GCAL_VERSION, true);
    }
    
    public function frontend_assets() {
        wp_enqueue_style('soe-gcal-frontend', SOE_GCAL_PLUGIN_URL . 'assets/css/frontend.css', [], SOE_GCAL_VERSION);
        wp_enqueue_script('soe-gcal-frontend', SOE_GCAL_PLUGIN_URL . 'assets/js/frontend.js', ['jquery'], SOE_GCAL_VERSION, true);
        wp_localize_script('soe-gcal-frontend', 'soeGcalAjax', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('soe_gcal_booking')
        ]);
    }
    
    private function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        // Class Types table (defined by admin)
        $class_types_table = $wpdb->prefix . 'soe_gcal_class_types';
        $sql_class_types = "CREATE TABLE $class_types_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            description text,
            color varchar(7) DEFAULT '#3182CE',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";

        // Class Sessions table (synced from Google Calendar, linked to types)
        $classes_table = $wpdb->prefix . 'soe_gcal_classes';
        $sql_classes = "CREATE TABLE $classes_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            class_type_id bigint(20) DEFAULT NULL,
            google_event_id varchar(255) NOT NULL,
            title varchar(255) NOT NULL,
            description text,
            start_time datetime NOT NULL,
            end_time datetime NOT NULL,
            location varchar(255),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY google_event_id (google_event_id),
            KEY class_type_id (class_type_id)
        ) $charset_collate;";

        // Bookings table
        $bookings_table = $wpdb->prefix . 'soe_gcal_bookings';
        $sql_bookings = "CREATE TABLE $bookings_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            class_id bigint(20) NOT NULL,
            customer_name varchar(255) NOT NULL,
            customer_email varchar(255) NOT NULL,
            customer_phone varchar(50) NOT NULL,
            status varchar(20) DEFAULT 'confirmed',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY class_id (class_id)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql_class_types);
        dbDelta($sql_classes);
        dbDelta($sql_bookings);
    }
}

// Boot the plugin
add_action('plugins_loaded', function() {
    SOE_GCal_Booking::get_instance();
});

