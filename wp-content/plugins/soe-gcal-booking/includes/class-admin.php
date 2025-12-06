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
     * Render the classes management page
     */
    public static function render_classes_page() {
        global $wpdb;
        $table = $wpdb->prefix . 'soe_gcal_classes';
        $editing = false;
        $edit_class = null;

        // Handle delete
        if (isset($_GET['delete']) && isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'delete_class_' . $_GET['delete'])) {
            $wpdb->delete($table, ['id' => intval($_GET['delete'])]);
            echo '<div class="notice notice-success"><p>Class deleted.</p></div>';
        }

        // Handle edit mode
        if (isset($_GET['edit'])) {
            $edit_class = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", intval($_GET['edit'])));
            if ($edit_class) {
                $editing = true;
            }
        }

        // Handle form submission (add/edit)
        if (isset($_POST['soe_save_class']) && wp_verify_nonce($_POST['_wpnonce'], 'soe_save_class')) {
            $data = [
                'title' => sanitize_text_field($_POST['title']),
                'description' => sanitize_textarea_field($_POST['description']),
                'start_time' => sanitize_text_field($_POST['start_date'] . ' ' . $_POST['start_time']),
                'end_time' => sanitize_text_field($_POST['end_date'] . ' ' . $_POST['end_time']),
                'location' => sanitize_text_field($_POST['location']),
                'google_event_id' => 'manual_' . time() . '_' . wp_rand()
            ];

            if (!empty($_POST['class_id'])) {
                // Update existing
                unset($data['google_event_id']);
                $wpdb->update($table, $data, ['id' => intval($_POST['class_id'])]);
                echo '<div class="notice notice-success"><p>Class updated!</p></div>';
                $editing = false;
                $edit_class = null;
            } else {
                // Insert new
                $wpdb->insert($table, $data);
                echo '<div class="notice notice-success"><p>Class created!</p></div>';
            }
        }

        // Get all classes
        $classes = $wpdb->get_results("SELECT * FROM $table ORDER BY start_time DESC");

        ?>
        <div class="wrap">
            <h1><?php _e('Manage Classes', 'soe-gcal-booking'); ?></h1>

            <!-- Add/Edit Form -->
            <div class="card" style="max-width: 600px; margin-bottom: 20px;">
                <h2><?php echo $editing ? __('Edit Class', 'soe-gcal-booking') : __('Add New Class', 'soe-gcal-booking'); ?></h2>

                <form method="post">
                    <?php wp_nonce_field('soe_save_class'); ?>
                    <?php if ($editing): ?>
                        <input type="hidden" name="class_id" value="<?php echo esc_attr($edit_class->id); ?>">
                    <?php endif; ?>

                    <table class="form-table">
                        <tr>
                            <th><label for="title"><?php _e('Class Title', 'soe-gcal-booking'); ?> *</label></th>
                            <td><input type="text" id="title" name="title" class="regular-text" required value="<?php echo $editing ? esc_attr($edit_class->title) : ''; ?>"></td>
                        </tr>
                        <tr>
                            <th><label for="description"><?php _e('Description', 'soe-gcal-booking'); ?></label></th>
                            <td><textarea id="description" name="description" rows="3" class="large-text"><?php echo $editing ? esc_textarea($edit_class->description) : ''; ?></textarea></td>
                        </tr>
                        <tr>
                            <th><label><?php _e('Start', 'soe-gcal-booking'); ?> *</label></th>
                            <td>
                                <input type="date" name="start_date" required value="<?php echo $editing ? esc_attr(date('Y-m-d', strtotime($edit_class->start_time))) : ''; ?>">
                                <input type="time" name="start_time" required value="<?php echo $editing ? esc_attr(date('H:i', strtotime($edit_class->start_time))) : ''; ?>">
                            </td>
                        </tr>
                        <tr>
                            <th><label><?php _e('End', 'soe-gcal-booking'); ?> *</label></th>
                            <td>
                                <input type="date" name="end_date" required value="<?php echo $editing ? esc_attr(date('Y-m-d', strtotime($edit_class->end_time))) : ''; ?>">
                                <input type="time" name="end_time" required value="<?php echo $editing ? esc_attr(date('H:i', strtotime($edit_class->end_time))) : ''; ?>">
                            </td>
                        </tr>
                        <tr>
                            <th><label for="location"><?php _e('Location', 'soe-gcal-booking'); ?></label></th>
                            <td><input type="text" id="location" name="location" class="regular-text" value="<?php echo $editing ? esc_attr($edit_class->location) : ''; ?>"></td>
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

            <!-- Classes List -->
            <h2><?php _e('All Classes', 'soe-gcal-booking'); ?></h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Title', 'soe-gcal-booking'); ?></th>
                        <th><?php _e('Date', 'soe-gcal-booking'); ?></th>
                        <th><?php _e('Time', 'soe-gcal-booking'); ?></th>
                        <th><?php _e('Location', 'soe-gcal-booking'); ?></th>
                        <th><?php _e('Source', 'soe-gcal-booking'); ?></th>
                        <th><?php _e('Actions', 'soe-gcal-booking'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($classes)): ?>
                        <tr><td colspan="6"><?php _e('No classes yet. Add one above or sync from Google Calendar.', 'soe-gcal-booking'); ?></td></tr>
                    <?php else: ?>
                        <?php foreach ($classes as $class): ?>
                            <tr>
                                <td><strong><?php echo esc_html($class->title); ?></strong></td>
                                <td><?php echo esc_html(date('M j, Y', strtotime($class->start_time))); ?></td>
                                <td><?php echo esc_html(date('g:i A', strtotime($class->start_time))); ?> - <?php echo esc_html(date('g:i A', strtotime($class->end_time))); ?></td>
                                <td><?php echo esc_html($class->location ?: '-'); ?></td>
                                <td><?php echo strpos($class->google_event_id, 'manual_') === 0 ? __('Manual', 'soe-gcal-booking') : __('Google Calendar', 'soe-gcal-booking'); ?></td>
                                <td>
                                    <a href="<?php echo admin_url('admin.php?page=soe-gcal-classes&edit=' . $class->id); ?>" class="button button-small"><?php _e('Edit', 'soe-gcal-booking'); ?></a>
                                    <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=soe-gcal-classes&delete=' . $class->id), 'delete_class_' . $class->id); ?>" class="button button-small" onclick="return confirm('<?php _e('Delete this class?', 'soe-gcal-booking'); ?>');"><?php _e('Delete', 'soe-gcal-booking'); ?></a>
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

