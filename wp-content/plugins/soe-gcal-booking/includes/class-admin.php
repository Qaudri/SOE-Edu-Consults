<?php
/**
 * Admin functionality for SOE GCal Booking
 */

if (!defined('ABSPATH')) {
    exit;
}

class SOE_GCal_Admin {
    
    /**
     * Render the main settings page
     */
    public static function render_settings_page() {
        $google_api = new SOE_GCal_Google_API();
        $is_connected = $google_api->is_connected();
        $calendar_id = get_option('soe_gcal_calendar_id', '');

        // Handle create tables (for manual table creation)
        if (isset($_POST['soe_gcal_create_tables']) && wp_verify_nonce($_POST['_wpnonce'], 'soe_gcal_create_tables')) {
            self::create_plugin_tables();
            echo '<div class="notice notice-success"><p>Database tables created/updated successfully!</p></div>';
        }

        // Handle form submissions
        if (isset($_POST['soe_gcal_save_settings']) && wp_verify_nonce($_POST['_wpnonce'], 'soe_gcal_settings')) {
            update_option('soe_gcal_calendar_id', sanitize_text_field($_POST['calendar_id']));
            $calendar_id = $_POST['calendar_id'];
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }

        // Handle manual sync
        if (isset($_POST['soe_gcal_sync_now']) && wp_verify_nonce($_POST['_wpnonce'], 'soe_gcal_sync')) {
            $result = $google_api->sync_calendar_events();
            if (is_wp_error($result)) {
                echo '<div class="notice notice-error"><p>Sync failed: ' . esc_html($result->get_error_message()) . '</p></div>';
            } else {
                echo '<div class="notice notice-success"><p>Synced ' . intval($result) . ' classes from Google Calendar!</p></div>';
            }
        }

        // Handle disconnect
        if (isset($_POST['soe_gcal_disconnect']) && wp_verify_nonce($_POST['_wpnonce'], 'soe_gcal_disconnect')) {
            $google_api->disconnect();
            $is_connected = false;
            echo '<div class="notice notice-success"><p>Disconnected from Google Calendar.</p></div>';
        }
        
        ?>
        <div class="wrap">
            <h1><?php _e('Class Booking Settings', 'soe-gcal-booking'); ?></h1>
            
            <div class="soe-gcal-settings">
                <!-- Connection Status -->
                <div class="card">
                    <h2><?php _e('Google Calendar Connection', 'soe-gcal-booking'); ?></h2>
                    
                    <?php if ($is_connected): ?>
                        <p style="color: green;">✓ <?php _e('Connected to Google Calendar', 'soe-gcal-booking'); ?></p>
                        
                        <form method="post">
                            <?php wp_nonce_field('soe_gcal_disconnect'); ?>
                            <button type="submit" name="soe_gcal_disconnect" class="button">
                                <?php _e('Disconnect', 'soe-gcal-booking'); ?>
                            </button>
                        </form>
                    <?php else: ?>
                        <p style="color: orange;">○ <?php _e('Not connected', 'soe-gcal-booking'); ?></p>
                        
                        <?php if (defined('SOE_GCAL_CLIENT_ID') && defined('SOE_GCAL_CLIENT_SECRET')): ?>
                            <a href="<?php echo esc_url($google_api->get_auth_url()); ?>" class="button button-primary">
                                <?php _e('Connect Google Calendar', 'soe-gcal-booking'); ?>
                            </a>
                        <?php else: ?>
                            <p class="description" style="color: red;">
                                <?php _e('Please add SOE_GCAL_CLIENT_ID and SOE_GCAL_CLIENT_SECRET to wp-config.php', 'soe-gcal-booking'); ?>
                            </p>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
                
                <!-- Calendar Settings -->
                <div class="card" style="margin-top: 20px;">
                    <h2><?php _e('Calendar Settings', 'soe-gcal-booking'); ?></h2>
                    
                    <form method="post">
                        <?php wp_nonce_field('soe_gcal_settings'); ?>
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="calendar_id"><?php _e('Calendar ID', 'soe-gcal-booking'); ?></label>
                                </th>
                                <td>
                                    <input type="text" id="calendar_id" name="calendar_id" 
                                           value="<?php echo esc_attr($calendar_id); ?>" 
                                           class="regular-text" 
                                           placeholder="example@gmail.com or calendar-id@group.calendar.google.com">
                                    <p class="description">
                                        <?php _e('Find this in Google Calendar Settings → Integrate calendar', 'soe-gcal-booking'); ?>
                                    </p>
                                </td>
                            </tr>
                        </table>
                        
                        <p>
                            <button type="submit" name="soe_gcal_save_settings" class="button button-primary">
                                <?php _e('Save Settings', 'soe-gcal-booking'); ?>
                            </button>
                        </p>
                    </form>
                </div>
                
                <!-- Sync -->
                <?php if ($is_connected && $calendar_id): ?>
                <div class="card" style="margin-top: 20px;">
                    <h2><?php _e('Sync Classes', 'soe-gcal-booking'); ?></h2>
                    <p><?php _e('Manually sync upcoming classes from your Google Calendar.', 'soe-gcal-booking'); ?></p>
                    
                    <form method="post">
                        <?php wp_nonce_field('soe_gcal_sync'); ?>
                        <button type="submit" name="soe_gcal_sync_now" class="button button-secondary">
                            <?php _e('Sync Now', 'soe-gcal-booking'); ?>
                        </button>
                    </form>
                </div>
                <?php endif; ?>
                
                <!-- Shortcode Info -->
                <div class="card" style="margin-top: 20px;">
                    <h2><?php _e('Usage', 'soe-gcal-booking'); ?></h2>
                    <p><?php _e('Use this shortcode to display the booking form on any page:', 'soe-gcal-booking'); ?></p>
                    <code>[soe_class_booking]</code>
                </div>

                <!-- Database Tools -->
                <div class="card" style="margin-top: 20px;">
                    <h2><?php _e('Database Tools', 'soe-gcal-booking'); ?></h2>
                    <p><?php _e('If class types are not saving, click the button below to create/repair database tables:', 'soe-gcal-booking'); ?></p>
                    <form method="post">
                        <?php wp_nonce_field('soe_gcal_create_tables'); ?>
                        <button type="submit" name="soe_gcal_create_tables" class="button">
                            <?php _e('Create/Repair Tables', 'soe-gcal-booking'); ?>
                        </button>
                    </form>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Create plugin database tables
     */
    public static function create_plugin_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        // Class Types table
        $class_types_table = $wpdb->prefix . 'soe_gcal_class_types';
        $sql_class_types = "CREATE TABLE $class_types_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            description text,
            color varchar(7) DEFAULT '#3182CE',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";

        // Class Sessions table
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

    /**
     * Render the class types management page
     */
    public static function render_class_types_page() {
        global $wpdb;
        $table = $wpdb->prefix . 'soe_gcal_class_types';
        $editing = false;
        $edit_type = null;

        // Handle delete
        if (isset($_GET['delete']) && isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'delete_type_' . $_GET['delete'])) {
            $wpdb->delete($table, ['id' => intval($_GET['delete'])]);
            echo '<div class="notice notice-success"><p>Class type deleted.</p></div>';
        }

        // Handle edit mode
        if (isset($_GET['edit'])) {
            $edit_type = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", intval($_GET['edit'])));
            if ($edit_type) {
                $editing = true;
            }
        }

        // Handle form submission
        if (isset($_POST['soe_save_class_type']) && wp_verify_nonce($_POST['_wpnonce'], 'soe_save_class_type')) {
            $data = [
                'name' => sanitize_text_field($_POST['name']),
                'description' => sanitize_textarea_field($_POST['description']),
                'color' => sanitize_hex_color($_POST['color']) ?: '#3182CE'
            ];

            if (!empty($_POST['type_id'])) {
                $wpdb->update($table, $data, ['id' => intval($_POST['type_id'])]);
                echo '<div class="notice notice-success"><p>Class type updated!</p></div>';
                $editing = false;
                $edit_type = null;
            } else {
                $wpdb->insert($table, $data);
                echo '<div class="notice notice-success"><p>Class type created!</p></div>';
            }
        }

        $types = $wpdb->get_results("SELECT * FROM $table ORDER BY name ASC");

        ?>
        <div class="wrap">
            <h1><?php _e('Class Types', 'soe-gcal-booking'); ?></h1>
            <p class="description"><?php _e('Define your class types here. When syncing from Google Calendar, only events matching these names will be imported.', 'soe-gcal-booking'); ?></p>

            <div class="card" style="max-width: 500px; margin-bottom: 20px;">
                <h2><?php echo $editing ? __('Edit Class Type', 'soe-gcal-booking') : __('Add New Class Type', 'soe-gcal-booking'); ?></h2>

                <form method="post">
                    <?php wp_nonce_field('soe_save_class_type'); ?>
                    <?php if ($editing): ?>
                        <input type="hidden" name="type_id" value="<?php echo esc_attr($edit_type->id); ?>">
                    <?php endif; ?>

                    <table class="form-table">
                        <tr>
                            <th><label for="name"><?php _e('Class Name', 'soe-gcal-booking'); ?> *</label></th>
                            <td>
                                <input type="text" id="name" name="name" class="regular-text" required
                                       value="<?php echo $editing ? esc_attr($edit_type->name) : ''; ?>"
                                       placeholder="e.g., Math Tutoring, Yoga Class">
                                <p class="description"><?php _e('This must match the event title in Google Calendar exactly.', 'soe-gcal-booking'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="description"><?php _e('Description', 'soe-gcal-booking'); ?></label></th>
                            <td><textarea id="description" name="description" rows="3" class="large-text"><?php echo $editing ? esc_textarea($edit_type->description) : ''; ?></textarea></td>
                        </tr>
                        <tr>
                            <th><label for="color"><?php _e('Color', 'soe-gcal-booking'); ?></label></th>
                            <td><input type="color" id="color" name="color" value="<?php echo $editing ? esc_attr($edit_type->color) : '#3182CE'; ?>"></td>
                        </tr>
                    </table>

                    <p>
                        <button type="submit" name="soe_save_class_type" class="button button-primary">
                            <?php echo $editing ? __('Update Class Type', 'soe-gcal-booking') : __('Add Class Type', 'soe-gcal-booking'); ?>
                        </button>
                        <?php if ($editing): ?>
                            <a href="<?php echo admin_url('admin.php?page=soe-gcal-class-types'); ?>" class="button"><?php _e('Cancel', 'soe-gcal-booking'); ?></a>
                        <?php endif; ?>
                    </p>
                </form>
            </div>

            <h2><?php _e('Your Class Types', 'soe-gcal-booking'); ?></h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width: 30px;"><?php _e('Color', 'soe-gcal-booking'); ?></th>
                        <th><?php _e('Name', 'soe-gcal-booking'); ?></th>
                        <th><?php _e('Description', 'soe-gcal-booking'); ?></th>
                        <th><?php _e('Actions', 'soe-gcal-booking'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($types)): ?>
                        <tr><td colspan="4"><?php _e('No class types yet. Add one above.', 'soe-gcal-booking'); ?></td></tr>
                    <?php else: ?>
                        <?php foreach ($types as $type): ?>
                            <tr>
                                <td><span style="display: inline-block; width: 20px; height: 20px; background: <?php echo esc_attr($type->color); ?>; border-radius: 3px;"></span></td>
                                <td><strong><?php echo esc_html($type->name); ?></strong></td>
                                <td><?php echo esc_html($type->description ?: '-'); ?></td>
                                <td>
                                    <a href="<?php echo admin_url('admin.php?page=soe-gcal-class-types&edit=' . $type->id); ?>" class="button button-small"><?php _e('Edit', 'soe-gcal-booking'); ?></a>
                                    <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=soe-gcal-class-types&delete=' . $type->id), 'delete_type_' . $type->id); ?>" class="button button-small" onclick="return confirm('<?php _e('Delete this class type?', 'soe-gcal-booking'); ?>');"><?php _e('Delete', 'soe-gcal-booking'); ?></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * Render the sessions (class instances) page
     */
    public static function render_classes_page() {
        global $wpdb;
        $table = $wpdb->prefix . 'soe_gcal_classes';
        $types_table = $wpdb->prefix . 'soe_gcal_class_types';
        $bookings_table = $wpdb->prefix . 'soe_gcal_bookings';

        // Handle delete session
        if (isset($_GET['delete_session']) && isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'delete_session_' . $_GET['delete_session'])) {
            $wpdb->delete($table, ['id' => intval($_GET['delete_session'])]);
            echo '<div class="notice notice-success"><p>Session deleted.</p></div>';
        }

        // Handle add session form
        if (isset($_POST['soe_add_session']) && wp_verify_nonce($_POST['_wpnonce'], 'soe_add_session')) {
            $class_type_id = intval($_POST['class_type_id']);
            $class_type = $wpdb->get_row($wpdb->prepare("SELECT * FROM $types_table WHERE id = %d", $class_type_id));

            if ($class_type) {
                $session_date = sanitize_text_field($_POST['session_date']);
                $start_time = sanitize_text_field($_POST['start_time']);
                $end_time = sanitize_text_field($_POST['end_time']);
                $location = sanitize_text_field($_POST['location']);

                $start_datetime = $session_date . ' ' . $start_time . ':00';
                $end_datetime = $session_date . ' ' . $end_time . ':00';

                $wpdb->insert($table, [
                    'class_type_id' => $class_type_id,
                    'google_event_id' => 'manual_' . uniqid(),
                    'title' => $class_type->name,
                    'description' => $class_type->description,
                    'start_time' => $start_datetime,
                    'end_time' => $end_datetime,
                    'location' => $location,
                    'created_at' => current_time('mysql')
                ]);

                echo '<div class="notice notice-success"><p>Session added successfully!</p></div>';
            } else {
                echo '<div class="notice notice-error"><p>Please select a valid class type.</p></div>';
            }
        }

        // Get all class types for dropdown
        $class_types = $wpdb->get_results("SELECT * FROM $types_table ORDER BY name ASC");

        // Get all sessions with their class type
        $classes = $wpdb->get_results("
            SELECT c.*, ct.name as type_name, ct.color as type_color
            FROM $table c
            LEFT JOIN $types_table ct ON c.class_type_id = ct.id
            ORDER BY c.start_time DESC
        ");

        ?>
        <div class="wrap">
            <h1><?php _e('Class Sessions', 'soe-gcal-booking'); ?></h1>
            <p class="description"><?php _e('Manage individual class sessions. Add manually or sync from Google Calendar.', 'soe-gcal-booking'); ?></p>

            <!-- Add Session Form -->
            <div class="card" style="max-width: 600px; margin: 20px 0;">
                <h2><?php _e('Add New Session', 'soe-gcal-booking'); ?></h2>

                <?php if (empty($class_types)): ?>
                    <p class="description"><?php _e('Please create a Class Type first before adding sessions.', 'soe-gcal-booking'); ?>
                    <a href="<?php echo admin_url('admin.php?page=soe-gcal-class-types'); ?>"><?php _e('Create Class Type', 'soe-gcal-booking'); ?></a></p>
                <?php else: ?>
                    <form method="post">
                        <?php wp_nonce_field('soe_add_session'); ?>
                        <table class="form-table">
                            <tr>
                                <th><label for="class_type_id"><?php _e('Class Type', 'soe-gcal-booking'); ?> *</label></th>
                                <td>
                                    <select id="class_type_id" name="class_type_id" required style="min-width: 200px;">
                                        <option value=""><?php _e('-- Select Class Type --', 'soe-gcal-booking'); ?></option>
                                        <?php foreach ($class_types as $type): ?>
                                            <option value="<?php echo esc_attr($type->id); ?>"><?php echo esc_html($type->name); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="session_date"><?php _e('Date', 'soe-gcal-booking'); ?> *</label></th>
                                <td><input type="date" id="session_date" name="session_date" required min="<?php echo date('Y-m-d'); ?>"></td>
                            </tr>
                            <tr>
                                <th><label for="start_time"><?php _e('Start Time', 'soe-gcal-booking'); ?> *</label></th>
                                <td><input type="time" id="start_time" name="start_time" required></td>
                            </tr>
                            <tr>
                                <th><label for="end_time"><?php _e('End Time', 'soe-gcal-booking'); ?> *</label></th>
                                <td><input type="time" id="end_time" name="end_time" required></td>
                            </tr>
                            <tr>
                                <th><label for="location"><?php _e('Location', 'soe-gcal-booking'); ?></label></th>
                                <td><input type="text" id="location" name="location" class="regular-text" placeholder="e.g., Room 101, Online, Zoom"></td>
                            </tr>
                        </table>
                        <p>
                            <button type="submit" name="soe_add_session" class="button button-primary">
                                <?php _e('Add Session', 'soe-gcal-booking'); ?>
                            </button>
                        </p>
                    </form>
                <?php endif; ?>
            </div>

            <h2><?php _e('Upcoming & Past Sessions', 'soe-gcal-booking'); ?></h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Class Type', 'soe-gcal-booking'); ?></th>
                        <th><?php _e('Date', 'soe-gcal-booking'); ?></th>
                        <th><?php _e('Time', 'soe-gcal-booking'); ?></th>
                        <th><?php _e('Location', 'soe-gcal-booking'); ?></th>
                        <th><?php _e('Bookings', 'soe-gcal-booking'); ?></th>
                        <th><?php _e('Actions', 'soe-gcal-booking'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($classes)): ?>
                        <tr><td colspan="6"><?php _e('No sessions yet. Add one above or sync from Google Calendar.', 'soe-gcal-booking'); ?></td></tr>
                    <?php else: ?>
                        <?php foreach ($classes as $class):
                            $booking_count = $wpdb->get_var($wpdb->prepare(
                                "SELECT COUNT(*) FROM $bookings_table WHERE class_id = %d AND status = 'confirmed'",
                                $class->id
                            ));
                            $is_past = strtotime($class->start_time) < time();
                        ?>
                            <tr style="<?php echo $is_past ? 'opacity: 0.6;' : ''; ?>">
                                <td>
                                    <?php if ($class->type_color): ?>
                                        <span style="display: inline-block; width: 12px; height: 12px; background: <?php echo esc_attr($class->type_color); ?>; border-radius: 2px; margin-right: 8px;"></span>
                                    <?php endif; ?>
                                    <strong><?php echo esc_html($class->type_name ?: $class->title); ?></strong>
                                    <?php if ($is_past): ?><em>(past)</em><?php endif; ?>
                                </td>
                                <td><?php echo esc_html(date('M j, Y', strtotime($class->start_time))); ?></td>
                                <td><?php echo esc_html(date('g:i A', strtotime($class->start_time))); ?> - <?php echo esc_html(date('g:i A', strtotime($class->end_time))); ?></td>
                                <td><?php echo esc_html($class->location ?: '-'); ?></td>
                                <td><?php echo intval($booking_count); ?> <?php _e('bookings', 'soe-gcal-booking'); ?></td>
                                <td>
                                    <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=soe-gcal-classes&delete_session=' . $class->id), 'delete_session_' . $class->id); ?>"
                                       class="button button-small"
                                       onclick="return confirm('<?php _e('Delete this session?', 'soe-gcal-booking'); ?>');">
                                        <?php _e('Delete', 'soe-gcal-booking'); ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * Render the bookings list page
     */
    public static function render_bookings_page() {
        global $wpdb;
        
        $bookings_table = $wpdb->prefix . 'soe_gcal_bookings';
        $classes_table = $wpdb->prefix . 'soe_gcal_classes';
        
        $bookings = $wpdb->get_results("
            SELECT b.*, c.title as class_title, c.start_time, c.end_time
            FROM $bookings_table b
            LEFT JOIN $classes_table c ON b.class_id = c.id
            ORDER BY c.start_time DESC, b.created_at DESC
            LIMIT 100
        ");
        
        ?>
        <div class="wrap">
            <h1><?php _e('Bookings', 'soe-gcal-booking'); ?></h1>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Class', 'soe-gcal-booking'); ?></th>
                        <th><?php _e('Date/Time', 'soe-gcal-booking'); ?></th>
                        <th><?php _e('Customer Name', 'soe-gcal-booking'); ?></th>
                        <th><?php _e('Email', 'soe-gcal-booking'); ?></th>
                        <th><?php _e('Phone', 'soe-gcal-booking'); ?></th>
                        <th><?php _e('Status', 'soe-gcal-booking'); ?></th>
                        <th><?php _e('Booked On', 'soe-gcal-booking'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($bookings)): ?>
                        <tr>
                            <td colspan="7"><?php _e('No bookings yet.', 'soe-gcal-booking'); ?></td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($bookings as $booking): ?>
                            <tr>
                                <td><?php echo esc_html($booking->class_title); ?></td>
                                <td>
                                    <?php 
                                    if ($booking->start_time) {
                                        echo esc_html(date('M j, Y g:i A', strtotime($booking->start_time)));
                                    }
                                    ?>
                                </td>
                                <td><?php echo esc_html($booking->customer_name); ?></td>
                                <td><a href="mailto:<?php echo esc_attr($booking->customer_email); ?>"><?php echo esc_html($booking->customer_email); ?></a></td>
                                <td><?php echo esc_html($booking->customer_phone); ?></td>
                                <td><?php echo esc_html(ucfirst($booking->status)); ?></td>
                                <td><?php echo esc_html(date('M j, Y', strtotime($booking->created_at))); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
}

