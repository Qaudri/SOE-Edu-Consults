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
            </div>
        </div>
        <?php
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
            <p class="description"><?php _e('These are individual class sessions synced from Google Calendar. Sync from Settings page.', 'soe-gcal-booking'); ?></p>

            <table class="wp-list-table widefat fixed striped" style="margin-top: 20px;">
                <thead>
                    <tr>
                        <th><?php _e('Class Type', 'soe-gcal-booking'); ?></th>
                        <th><?php _e('Date', 'soe-gcal-booking'); ?></th>
                        <th><?php _e('Time', 'soe-gcal-booking'); ?></th>
                        <th><?php _e('Location', 'soe-gcal-booking'); ?></th>
                        <th><?php _e('Bookings', 'soe-gcal-booking'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($classes)): ?>
                        <tr><td colspan="5"><?php _e('No sessions yet. Connect Google Calendar and sync from Settings page.', 'soe-gcal-booking'); ?></td></tr>
                    <?php else: ?>
                        <?php
                        global $wpdb;
                        $bookings_table = $wpdb->prefix . 'soe_gcal_bookings';
                        foreach ($classes as $class):
                            $booking_count = $wpdb->get_var($wpdb->prepare(
                                "SELECT COUNT(*) FROM $bookings_table WHERE class_id = %d AND status = 'confirmed'",
                                $class->id
                            ));
                        ?>
                            <tr>
                                <td>
                                    <?php if ($class->type_color): ?>
                                        <span style="display: inline-block; width: 12px; height: 12px; background: <?php echo esc_attr($class->type_color); ?>; border-radius: 2px; margin-right: 8px;"></span>
                                    <?php endif; ?>
                                    <strong><?php echo esc_html($class->type_name ?: $class->title); ?></strong>
                                </td>
                                <td><?php echo esc_html(date('M j, Y', strtotime($class->start_time))); ?></td>
                                <td><?php echo esc_html(date('g:i A', strtotime($class->start_time))); ?> - <?php echo esc_html(date('g:i A', strtotime($class->end_time))); ?></td>
                                <td><?php echo esc_html($class->location ?: '-'); ?></td>
                                <td><?php echo intval($booking_count); ?> <?php _e('bookings', 'soe-gcal-booking'); ?></td>
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

