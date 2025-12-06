<?php
/**
 * Google Calendar API integration
 */

if (!defined('ABSPATH')) {
    exit;
}

class SOE_GCal_Google_API {
    
    private $client_id;
    private $client_secret;
    private $redirect_uri;
    private $token_option = 'soe_gcal_access_token';
    
    public function __construct() {
        $this->client_id = defined('SOE_GCAL_CLIENT_ID') ? SOE_GCAL_CLIENT_ID : '';
        $this->client_secret = defined('SOE_GCAL_CLIENT_SECRET') ? SOE_GCAL_CLIENT_SECRET : '';
        $this->redirect_uri = admin_url('admin.php?page=gcal-booking-callback');
        
        // Handle OAuth callback
        add_action('admin_init', [$this, 'handle_oauth_callback']);
    }
    
    /**
     * Get the OAuth authorization URL
     */
    public function get_auth_url() {
        $params = [
            'client_id' => $this->client_id,
            'redirect_uri' => $this->redirect_uri,
            'response_type' => 'code',
            'scope' => 'https://www.googleapis.com/auth/calendar.readonly',
            'access_type' => 'offline',
            'prompt' => 'consent'
        ];
        
        return 'https://accounts.google.com/o/oauth2/auth?' . http_build_query($params);
    }
    
    /**
     * Handle OAuth callback
     */
    public function handle_oauth_callback() {
        if (!isset($_GET['page']) || $_GET['page'] !== 'gcal-booking-callback') {
            return;
        }
        
        if (isset($_GET['code'])) {
            $token = $this->exchange_code_for_token($_GET['code']);
            
            if ($token && !is_wp_error($token)) {
                update_option($this->token_option, $token);
                wp_redirect(admin_url('admin.php?page=soe-gcal-booking&connected=1'));
                exit;
            }
        }
        
        wp_redirect(admin_url('admin.php?page=soe-gcal-booking&error=auth_failed'));
        exit;
    }
    
    /**
     * Exchange authorization code for access token
     */
    private function exchange_code_for_token($code) {
        $response = wp_remote_post('https://oauth2.googleapis.com/token', [
            'body' => [
                'code' => $code,
                'client_id' => $this->client_id,
                'client_secret' => $this->client_secret,
                'redirect_uri' => $this->redirect_uri,
                'grant_type' => 'authorization_code'
            ]
        ]);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($body['access_token'])) {
            $body['expires_at'] = time() + $body['expires_in'];
            return $body;
        }
        
        return new WP_Error('token_error', 'Failed to get access token');
    }
    
    /**
     * Get valid access token (refresh if needed)
     */
    public function get_access_token() {
        $token = get_option($this->token_option);
        
        if (!$token) {
            return null;
        }
        
        // Check if token is expired
        if (isset($token['expires_at']) && time() >= $token['expires_at'] - 60) {
            $token = $this->refresh_token($token);
            if ($token && !is_wp_error($token)) {
                update_option($this->token_option, $token);
            } else {
                return null;
            }
        }
        
        return $token['access_token'] ?? null;
    }
    
    /**
     * Refresh the access token
     */
    private function refresh_token($token) {
        if (!isset($token['refresh_token'])) {
            return new WP_Error('no_refresh_token', 'No refresh token available');
        }
        
        $response = wp_remote_post('https://oauth2.googleapis.com/token', [
            'body' => [
                'client_id' => $this->client_id,
                'client_secret' => $this->client_secret,
                'refresh_token' => $token['refresh_token'],
                'grant_type' => 'refresh_token'
            ]
        ]);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($body['access_token'])) {
            $body['expires_at'] = time() + $body['expires_in'];
            $body['refresh_token'] = $token['refresh_token']; // Keep refresh token
            return $body;
        }
        
        return new WP_Error('refresh_error', 'Failed to refresh token');
    }
    
    /**
     * Check if connected to Google Calendar
     */
    public function is_connected() {
        return $this->get_access_token() !== null;
    }
    
    /**
     * Disconnect from Google Calendar
     */
    public function disconnect() {
        delete_option($this->token_option);
    }
    
    /**
     * Sync calendar events to local database
     */
    public function sync_calendar_events() {
        $access_token = $this->get_access_token();
        if (!$access_token) {
            return new WP_Error('not_connected', 'Not connected to Google Calendar');
        }
        
        $calendar_id = get_option('soe_gcal_calendar_id');
        if (!$calendar_id) {
            return new WP_Error('no_calendar', 'No calendar ID configured');
        }
        
        // Fetch events from now to 30 days ahead
        $time_min = date('c');
        $time_max = date('c', strtotime('+30 days'));
        
        $url = sprintf(
            'https://www.googleapis.com/calendar/v3/calendars/%s/events?timeMin=%s&timeMax=%s&singleEvents=true&orderBy=startTime',
            urlencode($calendar_id),
            urlencode($time_min),
            urlencode($time_max)
        );
        
        $response = wp_remote_get($url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $access_token
            ]
        ]);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (!isset($body['items'])) {
            return new WP_Error('api_error', $body['error']['message'] ?? 'Failed to fetch events');
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'soe_gcal_classes';
        $count = 0;
        
        foreach ($body['items'] as $event) {
            $start = $event['start']['dateTime'] ?? $event['start']['date'];
            $end = $event['end']['dateTime'] ?? $event['end']['date'];
            
            $wpdb->replace($table, [
                'google_event_id' => $event['id'],
                'title' => $event['summary'] ?? 'Untitled Class',
                'description' => $event['description'] ?? '',
                'start_time' => date('Y-m-d H:i:s', strtotime($start)),
                'end_time' => date('Y-m-d H:i:s', strtotime($end)),
                'location' => $event['location'] ?? ''
            ]);
            $count++;
        }
        
        return $count;
    }
}

