<?php
/**
 * Admin functionality for SOE Class Booking
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
        $google_enabled = defined('SOE_GCAL_CLIENT_ID') && defined('SOE_GCAL_CLIENT_SECRET');

        // Handle create tables
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
                echo '<div class="notice notice-success"><p>Synced ' . intval($result) . ' sessions from Google Calendar!</p></div>';
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
                <!-- Shortcode Info -->
                <div class="card">
                    <h2><?php _e('Usage', 'soe-gcal-booking'); ?></h2>
                    <p><?php _e('Use this shortcode to display the booking form on any page:', 'soe-gcal-booking'); ?></p>
                    <code style="font-size: 14px; padding: 8px 12px; background: #f0f0f0; display: inline-block;">[soe_class_booking]</code>
                </div>

                <!-- Google Calendar Integration (Optional) -->
                <div class="card" style="margin-top: 20px;">
                    <h2><?php _e('Google Calendar Integration', 'soe-gcal-booking'); ?> <small style="font-weight: normal; color: #666;">(Optional)</small></h2>

                    <?php if (!$google_enabled): ?>
                        <p class="description"><?php _e('To enable Google Calendar sync, add SOE_GCAL_CLIENT_ID and SOE_GCAL_CLIENT_SECRET to wp-config.php', 'soe-gcal-booking'); ?></p>
                    <?php elseif ($is_connected): ?>
                        <p style="color: green;">✓ <?php _e('Connected to Google Calendar', 'soe-gcal-booking'); ?></p>

                        <form method="post" style="margin-bottom: 15px;">
                            <?php wp_nonce_field('soe_gcal_settings'); ?>
                            <label for="calendar_id"><?php _e('Calendar ID:', 'soe-gcal-booking'); ?></label><br>
                            <input type="text" id="calendar_id" name="calendar_id" value="<?php echo esc_attr($calendar_id); ?>" class="regular-text" style="margin: 5px 0;">
                            <button type="submit" name="soe_gcal_save_settings" class="button"><?php _e('Save', 'soe-gcal-booking'); ?></button>
                        </form>

                        <?php if ($calendar_id): ?>
                        <form method="post" style="display: inline-block; margin-right: 10px;">
                            <?php wp_nonce_field('soe_gcal_sync'); ?>
                            <button type="submit" name="soe_gcal_sync_now" class="button button-primary"><?php _e('Sync Sessions from Calendar', 'soe-gcal-booking'); ?></button>
                        </form>
                        <?php endif; ?>

                        <form method="post" style="display: inline-block;">
                            <?php wp_nonce_field('soe_gcal_disconnect'); ?>
                            <button type="submit" name="soe_gcal_disconnect" class="button"><?php _e('Disconnect', 'soe-gcal-booking'); ?></button>
                        </form>
                    <?php else: ?>
                        <a href="<?php echo esc_url($google_api->get_auth_url()); ?>" class="button button-primary"><?php _e('Connect Google Calendar', 'soe-gcal-booking'); ?></a>
                    <?php endif; ?>
                </div>

                <!-- Database Tools -->
                <div class="card" style="margin-top: 20px;">
                    <h2><?php _e('Database Tools', 'soe-gcal-booking'); ?></h2>
                    <p><?php _e('If classes are not saving, click below to create/repair database tables:', 'soe-gcal-booking'); ?></p>
                    <form method="post">
                        <?php wp_nonce_field('soe_gcal_create_tables'); ?>
                        <button type="submit" name="soe_gcal_create_tables" class="button"><?php _e('Create/Repair Tables', 'soe-gcal-booking'); ?></button>
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

        // Classes table (the services/courses offered)
        $classes_table = $wpdb->prefix . 'soe_gcal_classes';
        $sql_classes = "CREATE TABLE $classes_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            description text,
            duration int DEFAULT 60,
            color varchar(7) DEFAULT '#3182CE',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";

        // Sessions table (bookable time slots for each class)
        $sessions_table = $wpdb->prefix . 'soe_gcal_sessions';
        $sql_sessions = "CREATE TABLE $sessions_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            class_id bigint(20) NOT NULL,
            google_event_id varchar(255) DEFAULT NULL,
            start_time datetime NOT NULL,
            end_time datetime NOT NULL,
            max_capacity int DEFAULT 1,
            location varchar(255),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY class_id (class_id),
            KEY google_event_id (google_event_id)
        ) $charset_collate;";

        // Bookings table
        $bookings_table = $wpdb->prefix . 'soe_gcal_bookings';
        $sql_bookings = "CREATE TABLE $bookings_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            session_id bigint(20) NOT NULL,
            customer_name varchar(255) NOT NULL,
            customer_email varchar(255) NOT NULL,
            customer_phone varchar(50) NOT NULL,
            status varchar(20) DEFAULT 'confirmed',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY session_id (session_id)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql_classes);
        dbDelta($sql_sessions);
        dbDelta($sql_bookings);
    }

    /**
     * Render the Classes management page
     */
    public static function render_classes_page() {
        global $wpdb;
        $table = $wpdb->prefix . 'soe_gcal_classes';
        $sessions_table = $wpdb->prefix . 'soe_gcal_sessions';
        $editing = false;
        $edit_class = null;

        // Handle delete
        if (isset($_GET['delete']) && isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'delete_class_' . $_GET['delete'])) {
            $class_id = intval($_GET['delete']);
            // Delete associated sessions first
            $wpdb->delete($sessions_table, ['class_id' => $class_id]);
            $wpdb->delete($table, ['id' => $class_id]);
            echo '<div class="notice notice-success"><p>Class deleted.</p></div>';
        }

        // Handle edit mode
        if (isset($_GET['edit'])) {
            $edit_class = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", intval($_GET['edit'])));
            if ($edit_class) {
                $editing = true;
            }
        }

        // Handle form submission
        if (isset($_POST['soe_save_class']) && wp_verify_nonce($_POST['_wpnonce'], 'soe_save_class')) {
            $data = [
                'name' => sanitize_text_field($_POST['name']),
                'description' => sanitize_textarea_field($_POST['description']),
                'duration' => intval($_POST['duration']) ?: 60,
                'color' => sanitize_hex_color($_POST['color']) ?: '#3182CE'
            ];

            if (!empty($_POST['class_id'])) {
                $wpdb->update($table, $data, ['id' => intval($_POST['class_id'])]);
                echo '<div class="notice notice-success"><p>Class updated!</p></div>';
                $editing = false;
                $edit_class = null;
            } else {
                $wpdb->insert($table, $data);
                echo '<div class="notice notice-success"><p>Class created!</p></div>';
            }
        }

        // Get classes with session counts
        $classes = $wpdb->get_results("
            SELECT c.*,
                   COUNT(DISTINCT s.id) as session_count,
                   COUNT(DISTINCT CASE WHEN s.start_time > NOW() THEN s.id END) as upcoming_sessions
            FROM $table c
            LEFT JOIN $sessions_table s ON c.id = s.class_id
            GROUP BY c.id
            ORDER BY c.name ASC
        ");

        ?>
        <div class="wrap">
            <h1><?php _e('Classes', 'soe-gcal-booking'); ?></h1>
            <p class="description"><?php _e('Define your classes here. Each class can have multiple sessions (time slots) that students can book.', 'soe-gcal-booking'); ?></p>

            <div class="card" style="max-width: 500px; margin-bottom: 20px;">
                <h2><?php echo $editing ? __('Edit Class', 'soe-gcal-booking') : __('Add New Class', 'soe-gcal-booking'); ?></h2>

                <form method="post">
                    <?php wp_nonce_field('soe_save_class'); ?>
                    <?php if ($editing): ?>
                        <input type="hidden" name="class_id" value="<?php echo esc_attr($edit_class->id); ?>">
                    <?php endif; ?>

                    <table class="form-table">
                        <tr>
                            <th><label for="name"><?php _e('Class Name', 'soe-gcal-booking'); ?> *</label></th>
                            <td>
                                <input type="text" id="name" name="name" class="regular-text" required
                                       value="<?php echo $editing ? esc_attr($edit_class->name) : ''; ?>"
                                       placeholder="e.g., Math Tutoring, Yoga Class">
                            </td>
                        </tr>
                        <tr>
                            <th><label for="description"><?php _e('Description', 'soe-gcal-booking'); ?></label></th>
                            <td><textarea id="description" name="description" rows="3" class="large-text"><?php echo $editing ? esc_textarea($edit_class->description) : ''; ?></textarea></td>
                        </tr>
                        <tr>
                            <th><label for="duration"><?php _e('Duration (minutes)', 'soe-gcal-booking'); ?></label></th>
                            <td><input type="number" id="duration" name="duration" min="15" step="15" value="<?php echo $editing ? esc_attr($edit_class->duration) : '60'; ?>"></td>
                        </tr>
                        <tr>
                            <th><label for="color"><?php _e('Color', 'soe-gcal-booking'); ?></label></th>
                            <td><input type="color" id="color" name="color" value="<?php echo $editing ? esc_attr($edit_class->color) : '#3182CE'; ?>"></td>
                        </tr>
                    </table>

                    <p>
                        <button type="submit" name="soe_save_class" class="button button-primary">
                            <?php echo $editing ? __('Update Class', 'soe-gcal-booking') : __('Add Class', 'soe-gcal-booking'); ?>
                        </button>
                        <?php if ($editing): ?>
                            <a href="<?php echo admin_url('admin.php?page=soe-gcal-classes'); ?>" class="button"><?php _e('Cancel', 'soe-gcal-booking'); ?></a>
                        <?php endif; ?>
                    </p>
                </form>
            </div>

            <h2><?php _e('Your Classes', 'soe-gcal-booking'); ?></h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width: 30px;"><?php _e('Color', 'soe-gcal-booking'); ?></th>
                        <th><?php _e('Name', 'soe-gcal-booking'); ?></th>
                        <th><?php _e('Duration', 'soe-gcal-booking'); ?></th>
                        <th><?php _e('Sessions', 'soe-gcal-booking'); ?></th>
                        <th><?php _e('Actions', 'soe-gcal-booking'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($classes)): ?>
                        <tr><td colspan="5"><?php _e('No classes yet. Add one above.', 'soe-gcal-booking'); ?></td></tr>
                    <?php else: ?>
                        <?php foreach ($classes as $class): ?>
                            <tr>
                                <td><span style="display: inline-block; width: 20px; height: 20px; background: <?php echo esc_attr($class->color); ?>; border-radius: 3px;"></span></td>
                                <td><strong><?php echo esc_html($class->name); ?></strong></td>
                                <td><?php echo intval($class->duration); ?> min</td>
                                <td>
                                    <?php echo intval($class->upcoming_sessions); ?> upcoming
                                    <a href="<?php echo admin_url('admin.php?page=soe-gcal-sessions&class_id=' . $class->id); ?>" style="margin-left: 5px;"><?php _e('Manage', 'soe-gcal-booking'); ?></a>
                                </td>
                                <td>
                                    <a href="<?php echo admin_url('admin.php?page=soe-gcal-classes&edit=' . $class->id); ?>" class="button button-small"><?php _e('Edit', 'soe-gcal-booking'); ?></a>
                                    <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=soe-gcal-classes&delete=' . $class->id), 'delete_class_' . $class->id); ?>" class="button button-small" onclick="return confirm('<?php _e('Delete this class and all its sessions?', 'soe-gcal-booking'); ?>');"><?php _e('Delete', 'soe-gcal-booking'); ?></a>
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
     * Render the Sessions management page (with bulk creation)
     */
    public static function render_sessions_page() {
        global $wpdb;
        $sessions_table = $wpdb->prefix . 'soe_gcal_sessions';
        $classes_table = $wpdb->prefix . 'soe_gcal_classes';
        $bookings_table = $wpdb->prefix . 'soe_gcal_bookings';

        // Filter by class
        $filter_class_id = isset($_GET['class_id']) ? intval($_GET['class_id']) : 0;

        // Handle delete session
        if (isset($_GET['delete_session']) && isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'delete_session_' . $_GET['delete_session'])) {
            $wpdb->delete($sessions_table, ['id' => intval($_GET['delete_session'])]);
            echo '<div class="notice notice-success"><p>Session deleted.</p></div>';
        }

        // Handle quick single session creation
        if (isset($_POST['soe_add_single_session']) && wp_verify_nonce($_POST['_wpnonce'], 'soe_add_single_session')) {
            $class_id = intval($_POST['class_id']);
            $class = $wpdb->get_row($wpdb->prepare("SELECT * FROM $classes_table WHERE id = %d", $class_id));

            if ($class) {
                $date = sanitize_text_field($_POST['session_date']);
                $start_time = sanitize_text_field($_POST['start_time']);
                $location = sanitize_text_field($_POST['location']);
                $max_capacity = intval($_POST['max_capacity']) ?: 1;

                $start_datetime = $date . ' ' . $start_time;
                $end_datetime = date('Y-m-d H:i:s', strtotime($start_datetime) + ($class->duration * 60));

                $wpdb->insert($sessions_table, [
                    'class_id' => $class_id,
                    'start_time' => $start_datetime,
                    'end_time' => $end_datetime,
                    'max_capacity' => $max_capacity,
                    'location' => $location,
                    'created_at' => current_time('mysql')
                ]);

                echo '<div class="notice notice-success"><p>' . __('Session created!', 'soe-gcal-booking') . '</p></div>';
            }
        }

        // Handle bulk session creation
        if (isset($_POST['soe_add_sessions']) && wp_verify_nonce($_POST['_wpnonce'], 'soe_add_sessions')) {
            $class_id = intval($_POST['bulk_class_id']);
            $class = $wpdb->get_row($wpdb->prepare("SELECT * FROM $classes_table WHERE id = %d", $class_id));

            if ($class) {
                $dates = array_filter(array_map('trim', explode(',', sanitize_text_field($_POST['session_dates']))));
                $time_slots = array_filter(array_map('trim', explode("\n", sanitize_textarea_field($_POST['time_slots']))));
                $location = sanitize_text_field($_POST['bulk_location']);
                $max_capacity = intval($_POST['bulk_max_capacity']) ?: 1;

                $created = 0;
                foreach ($dates as $date) {
                    foreach ($time_slots as $slot) {
                        $start_time = trim($slot);
                        if (empty($start_time)) continue;

                        // Calculate end time based on class duration
                        $start_datetime = $date . ' ' . $start_time;
                        $end_datetime = date('Y-m-d H:i:s', strtotime($start_datetime) + ($class->duration * 60));

                        $wpdb->insert($sessions_table, [
                            'class_id' => $class_id,
                            'start_time' => $start_datetime,
                            'end_time' => $end_datetime,
                            'max_capacity' => $max_capacity,
                            'location' => $location,
                            'created_at' => current_time('mysql')
                        ]);
                        $created++;
                    }
                }

                echo '<div class="notice notice-success"><p>' . sprintf(__('%d sessions created!', 'soe-gcal-booking'), $created) . '</p></div>';
            }
        }

        // Get all classes for dropdown
        $classes = $wpdb->get_results("SELECT * FROM $classes_table ORDER BY name ASC");

        // Get sessions with their class info
        $where = $filter_class_id ? $wpdb->prepare("WHERE s.class_id = %d", $filter_class_id) : "";
        $sessions = $wpdb->get_results("
            SELECT s.*, c.name as class_name, c.color as class_color, c.duration
            FROM $sessions_table s
            LEFT JOIN $classes_table c ON s.class_id = c.id
            $where
            ORDER BY s.start_time ASC
        ");

        ?>
        <div class="wrap">
            <h1><?php _e('Sessions', 'soe-gcal-booking'); ?></h1>
            <p class="description"><?php _e('Create and manage bookable time slots for your classes.', 'soe-gcal-booking'); ?></p>

            <?php if (empty($classes)): ?>
                <div class="card" style="max-width: 650px; margin: 20px 0;">
                    <p class="description"><?php _e('Please create a Class first before adding sessions.', 'soe-gcal-booking'); ?>
                    <a href="<?php echo admin_url('admin.php?page=soe-gcal-classes'); ?>"><?php _e('Create Class', 'soe-gcal-booking'); ?></a></p>
                </div>
            <?php else: ?>

            <!-- Quick Add Single Session -->
            <div class="card" style="max-width: 800px; margin: 20px 0;">
                <h2><?php _e('Quick Add Session', 'soe-gcal-booking'); ?></h2>
                <form method="post" style="display: flex; flex-wrap: wrap; gap: 15px; align-items: flex-end;">
                    <?php wp_nonce_field('soe_add_single_session'); ?>
                    <div>
                        <label for="class_id" style="display: block; margin-bottom: 5px; font-weight: 600;"><?php _e('Class', 'soe-gcal-booking'); ?></label>
                        <select id="class_id" name="class_id" required style="min-width: 180px;">
                            <option value=""><?php _e('-- Select --', 'soe-gcal-booking'); ?></option>
                            <?php foreach ($classes as $class): ?>
                                <option value="<?php echo esc_attr($class->id); ?>" <?php selected($filter_class_id, $class->id); ?>>
                                    <?php echo esc_html($class->name); ?> (<?php echo intval($class->duration); ?>m)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="session_date" style="display: block; margin-bottom: 5px; font-weight: 600;"><?php _e('Date', 'soe-gcal-booking'); ?></label>
                        <input type="date" id="session_date" name="session_date" required value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div>
                        <label for="start_time" style="display: block; margin-bottom: 5px; font-weight: 600;"><?php _e('Start Time', 'soe-gcal-booking'); ?></label>
                        <input type="time" id="start_time" name="start_time" required value="09:00">
                    </div>
                    <div>
                        <label for="max_capacity" style="display: block; margin-bottom: 5px; font-weight: 600;"><?php _e('Capacity', 'soe-gcal-booking'); ?></label>
                        <input type="number" id="max_capacity" name="max_capacity" min="1" value="1" style="width: 70px;">
                    </div>
                    <div>
                        <label for="location" style="display: block; margin-bottom: 5px; font-weight: 600;"><?php _e('Location', 'soe-gcal-booking'); ?></label>
                        <input type="text" id="location" name="location" placeholder="Optional" style="width: 140px;">
                    </div>
                    <div>
                        <button type="submit" name="soe_add_single_session" class="button button-primary">
                            <?php _e('Add Session', 'soe-gcal-booking'); ?>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Bulk Add Sessions (Collapsible) -->
            <div class="card" style="max-width: 650px; margin: 20px 0;">
                <h2 style="cursor: pointer;" onclick="document.getElementById('bulk-form').style.display = document.getElementById('bulk-form').style.display === 'none' ? 'block' : 'none';">
                    <?php _e('Bulk Create Sessions', 'soe-gcal-booking'); ?>
                    <span style="font-size: 0.8em; color: #666;">▼</span>
                </h2>
                <p class="description"><?php _e('Create multiple sessions at once for a regular schedule.', 'soe-gcal-booking'); ?></p>

                <div id="bulk-form" style="display: none; margin-top: 15px;">
                    <form method="post">
                        <?php wp_nonce_field('soe_add_sessions'); ?>
                        <table class="form-table" style="margin-top: 0;">
                            <tr>
                                <th><label for="bulk_class_id"><?php _e('Class', 'soe-gcal-booking'); ?> *</label></th>
                                <td>
                                    <select id="bulk_class_id" name="bulk_class_id" required style="min-width: 200px;">
                                        <option value=""><?php _e('-- Select Class --', 'soe-gcal-booking'); ?></option>
                                        <?php foreach ($classes as $class): ?>
                                            <option value="<?php echo esc_attr($class->id); ?>">
                                                <?php echo esc_html($class->name); ?> (<?php echo intval($class->duration); ?> min)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="session_dates"><?php _e('Dates', 'soe-gcal-booking'); ?> *</label></th>
                                <td>
                                    <input type="text" id="session_dates" name="session_dates" class="regular-text" required
                                           placeholder="2024-12-26, 2024-12-27, 2024-12-28">
                                    <p class="description"><?php _e('Comma-separated dates (YYYY-MM-DD)', 'soe-gcal-booking'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="time_slots"><?php _e('Time Slots', 'soe-gcal-booking'); ?> *</label></th>
                                <td>
                                    <textarea id="time_slots" name="time_slots" rows="3" class="regular-text" required
                                              placeholder="09:00&#10;10:30&#10;14:00"></textarea>
                                    <p class="description"><?php _e('One start time per line (HH:MM)', 'soe-gcal-booking'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="bulk_max_capacity"><?php _e('Capacity', 'soe-gcal-booking'); ?></label></th>
                                <td><input type="number" id="bulk_max_capacity" name="bulk_max_capacity" min="1" value="1" style="width: 80px;"></td>
                            </tr>
                            <tr>
                                <th><label for="bulk_location"><?php _e('Location', 'soe-gcal-booking'); ?></label></th>
                                <td><input type="text" id="bulk_location" name="bulk_location" class="regular-text" placeholder="e.g., Room 101, Online"></td>
                            </tr>
                        </table>
                        <p>
                            <button type="submit" name="soe_add_sessions" class="button button-primary">
                                <?php _e('Create All Sessions', 'soe-gcal-booking'); ?>
                            </button>
                        </p>
                    </form>
                </div>
            </div>
            <?php endif; ?>

            <!-- Filter -->
            <form method="get" style="margin-bottom: 15px;">
                <input type="hidden" name="page" value="soe-gcal-sessions">
                <select name="class_id" onchange="this.form.submit()">
                    <option value=""><?php _e('All Classes', 'soe-gcal-booking'); ?></option>
                    <?php foreach ($classes as $class): ?>
                        <option value="<?php echo esc_attr($class->id); ?>" <?php selected($filter_class_id, $class->id); ?>>
                            <?php echo esc_html($class->name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Class', 'soe-gcal-booking'); ?></th>
                        <th><?php _e('Date', 'soe-gcal-booking'); ?></th>
                        <th><?php _e('Time', 'soe-gcal-booking'); ?></th>
                        <th><?php _e('Location', 'soe-gcal-booking'); ?></th>
                        <th><?php _e('Capacity', 'soe-gcal-booking'); ?></th>
                        <th><?php _e('Actions', 'soe-gcal-booking'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($sessions)): ?>
                        <tr><td colspan="6"><?php _e('No sessions yet. Add some above.', 'soe-gcal-booking'); ?></td></tr>
                    <?php else: ?>
                        <?php foreach ($sessions as $session):
                            $booking_count = $wpdb->get_var($wpdb->prepare(
                                "SELECT COUNT(*) FROM $bookings_table WHERE session_id = %d AND status = 'confirmed'",
                                $session->id
                            ));
                            $is_past = strtotime($session->start_time) < time();
                            $is_full = $booking_count >= $session->max_capacity;
                        ?>
                            <tr style="<?php echo $is_past ? 'opacity: 0.6;' : ''; ?>">
                                <td>
                                    <span style="display: inline-block; width: 12px; height: 12px; background: <?php echo esc_attr($session->class_color ?: '#3182CE'); ?>; border-radius: 2px; margin-right: 8px;"></span>
                                    <strong><?php echo esc_html($session->class_name); ?></strong>
                                    <?php if ($is_past): ?><em style="color: #999;">(past)</em><?php endif; ?>
                                </td>
                                <td><?php echo esc_html(date('M j, Y (D)', strtotime($session->start_time))); ?></td>
                                <td><?php echo esc_html(date('g:i A', strtotime($session->start_time))); ?> - <?php echo esc_html(date('g:i A', strtotime($session->end_time))); ?></td>
                                <td><?php echo esc_html($session->location ?: '-'); ?></td>
                                <td>
                                    <?php echo intval($booking_count); ?>/<?php echo intval($session->max_capacity); ?>
                                    <?php if ($is_full): ?><span style="color: #d63638;">(Full)</span><?php endif; ?>
                                </td>
                                <td>
                                    <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=soe-gcal-sessions&delete_session=' . $session->id . ($filter_class_id ? '&class_id=' . $filter_class_id : '')), 'delete_session_' . $session->id); ?>"
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
        $sessions_table = $wpdb->prefix . 'soe_gcal_sessions';
        $classes_table = $wpdb->prefix . 'soe_gcal_classes';

        $bookings = $wpdb->get_results("
            SELECT b.*, c.name as class_name, c.color as class_color, s.start_time, s.end_time, s.location
            FROM $bookings_table b
            LEFT JOIN $sessions_table s ON b.session_id = s.id
            LEFT JOIN $classes_table c ON s.class_id = c.id
            ORDER BY s.start_time DESC, b.created_at DESC
            LIMIT 100
        ");

        ?>
        <div class="wrap">
            <h1><?php _e('Bookings', 'soe-gcal-booking'); ?></h1>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Class', 'soe-gcal-booking'); ?></th>
                        <th><?php _e('Session Date/Time', 'soe-gcal-booking'); ?></th>
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
                                <td>
                                    <span style="display: inline-block; width: 10px; height: 10px; background: <?php echo esc_attr($booking->class_color ?: '#3182CE'); ?>; border-radius: 2px; margin-right: 6px;"></span>
                                    <?php echo esc_html($booking->class_name ?: 'Unknown'); ?>
                                </td>
                                <td>
                                    <?php
                                    if ($booking->start_time) {
                                        echo esc_html(date('M j, Y', strtotime($booking->start_time)));
                                        echo '<br><small>' . esc_html(date('g:i A', strtotime($booking->start_time))) . '</small>';
                                    }
                                    ?>
                                </td>
                                <td><?php echo esc_html($booking->customer_name); ?></td>
                                <td><a href="mailto:<?php echo esc_attr($booking->customer_email); ?>"><?php echo esc_html($booking->customer_email); ?></a></td>
                                <td><?php echo esc_html($booking->customer_phone); ?></td>
                                <td>
                                    <span style="padding: 2px 8px; border-radius: 3px; background: <?php echo $booking->status === 'confirmed' ? '#d4edda' : '#f8d7da'; ?>; color: <?php echo $booking->status === 'confirmed' ? '#155724' : '#721c24'; ?>;">
                                        <?php echo esc_html(ucfirst($booking->status)); ?>
                                    </span>
                                </td>
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

