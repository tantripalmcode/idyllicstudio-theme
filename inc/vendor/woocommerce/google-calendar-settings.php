<?php
defined('ABSPATH') || exit;

/**
 * Google Calendar Settings - Custom Admin Page
 */

/**
 * Get Google Calendar Settings page URL
 */
if (!function_exists('pc_get_gcal_settings_page_url')) {
    function pc_get_gcal_settings_page_url() {
        return admin_url('admin.php?page=pc-google-calendar-settings');
    }
}

/**
 * Add admin menu for Google Calendar Settings
 */
add_action('admin_menu', 'pc_add_google_calendar_settings_menu');
function pc_add_google_calendar_settings_menu() {
    add_options_page(
        __('Google Calendar Settings', 'palmcode-child'),
        __('Google Calendar', 'palmcode-child'),
        'manage_options',
        'pc-google-calendar-settings',
        'pc_google_calendar_settings_page'
    );
}

/**
 * Register settings
 */
add_action('admin_init', 'pc_register_google_calendar_settings');
function pc_register_google_calendar_settings() {
    register_setting('pc_gcal_settings_group', 'pc_gcal_client_id', array(
        'type' => 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'default' => ''
    ));
    
    register_setting('pc_gcal_settings_group', 'pc_gcal_client_secret', array(
        'type' => 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'default' => ''
    ));
    
    register_setting('pc_gcal_settings_group', 'pc_gcal_calendar_id', array(
        'type' => 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'default' => 'primary'
    ));
}

/**
 * Settings page content
 */
function pc_google_calendar_settings_page() {
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.', 'palmcode-child'));
    }
    
    $page_url = pc_get_gcal_settings_page_url();
    $client_id = get_option('pc_gcal_client_id', '');
    $client_secret = get_option('pc_gcal_client_secret', '');
    $calendar_id = get_option('pc_gcal_calendar_id', 'primary');
    $access_token = get_option('pc_gcal_access_token', '');
    $is_authorized = !empty($access_token);
    
    // Handle form submission
    if (isset($_POST['pc_gcal_save_settings']) && check_admin_referer('pc_gcal_settings_nonce')) {
        update_option('pc_gcal_client_id', sanitize_text_field($_POST['pc_gcal_client_id']));
        update_option('pc_gcal_client_secret', sanitize_text_field($_POST['pc_gcal_client_secret']));
        update_option('pc_gcal_calendar_id', sanitize_text_field($_POST['pc_gcal_calendar_id']));
        
        $client_id = get_option('pc_gcal_client_id', '');
        $client_secret = get_option('pc_gcal_client_secret', '');
        $calendar_id = get_option('pc_gcal_calendar_id', 'primary');
        
        echo '<div class="notice notice-success is-dismissible"><p>' . __('Settings saved successfully!', 'palmcode-child') . '</p></div>';
    }
    
    // Show success/error messages
    if (isset($_GET['authorized']) && $_GET['authorized'] == '1') {
        echo '<div class="notice notice-success is-dismissible"><p>' . __('Google Calendar authorization successful!', 'palmcode-child') . '</p></div>';
    }
    if (isset($_GET['revoked']) && $_GET['revoked'] == '1') {
        echo '<div class="notice notice-success is-dismissible"><p>' . __('Google Calendar access revoked successfully!', 'palmcode-child') . '</p></div>';
    }
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        
        <div style="padding: 15px; background: #f0f0f1; border-left: 4px solid #2271b1; margin: 20px 0;">
            <h3 style="margin-top: 0;"><?php _e('How to Setup Google Calendar Integration', 'palmcode-child'); ?></h3>
            <ol>
                <li><?php _e('Go to', 'palmcode-child'); ?> <a href="https://console.cloud.google.com/" target="_blank"><?php _e('Google Cloud Console', 'palmcode-child'); ?></a></li>
                <li><?php _e('Create a new project or select an existing one', 'palmcode-child'); ?></li>
                <li><?php _e('Enable the Google Calendar API', 'palmcode-child'); ?></li>
                <li><?php _e('Create OAuth 2.0 credentials (Web application)', 'palmcode-child'); ?></li>
                <li><?php _e('Add authorized redirect URI:', 'palmcode-child'); ?> <code><?php echo esc_url($page_url); ?></code></li>
                <li><?php _e('Download the credentials JSON file', 'palmcode-child'); ?></li>
                <li><?php _e('Enter the Client ID and Client Secret below', 'palmcode-child'); ?></li>
                <li><?php _e('Click "Authorize" to connect your Google account', 'palmcode-child'); ?></li>
            </ol>
        </div>
        
        <form method="post" action="">
            <?php wp_nonce_field('pc_gcal_settings_nonce'); ?>
            
            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row">
                            <label for="pc_gcal_client_id"><?php _e('Google Client ID', 'palmcode-child'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="pc_gcal_client_id" name="pc_gcal_client_id" value="<?php echo esc_attr($client_id); ?>" class="regular-text" />
                            <p class="description"><?php _e('Enter your Google OAuth 2.0 Client ID', 'palmcode-child'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="pc_gcal_client_secret"><?php _e('Google Client Secret', 'palmcode-child'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="pc_gcal_client_secret" name="pc_gcal_client_secret" value="<?php echo esc_attr($client_secret); ?>" class="regular-text" />
                            <p class="description"><?php _e('Enter your Google OAuth 2.0 Client Secret', 'palmcode-child'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="pc_gcal_calendar_id"><?php _e('Calendar ID', 'palmcode-child'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="pc_gcal_calendar_id" name="pc_gcal_calendar_id" value="<?php echo esc_attr($calendar_id); ?>" class="regular-text" />
                            <p class="description"><?php _e('Enter the Calendar ID where events should be created. Use "primary" for your primary calendar, or enter a specific calendar ID.', 'palmcode-child'); ?></p>
                        </td>
                    </tr>
                </tbody>
            </table>
            
            <?php submit_button(__('Save Settings', 'palmcode-child'), 'primary', 'pc_gcal_save_settings'); ?>
        </form>
        
        <hr style="margin: 30px 0;" />
        
        <h2><?php _e('Authorization', 'palmcode-child'); ?></h2>
        <div id="pc-gcal-auth-section" style="margin: 20px 0;">
            <?php if ($is_authorized): ?>
                <div class="notice notice-success inline" style="margin: 10px 0;">
                    <p><strong><?php _e('Status:', 'palmcode-child'); ?></strong> <?php _e('Connected to Google Calendar', 'palmcode-child'); ?></p>
                </div>
            <?php else: ?>
                <div class="notice notice-warning inline" style="margin: 10px 0;">
                    <p><strong><?php _e('Status:', 'palmcode-child'); ?></strong> <?php _e('Not connected to Google Calendar', 'palmcode-child'); ?></p>
                </div>
            <?php endif; ?>
            
            <p>
                <a href="<?php echo esc_url(add_query_arg('pc_gcal_authorize', '1', $page_url)); ?>" class="button button-primary" id="pc-gcal-authorize-btn"><?php _e('Authorize Google Calendar', 'palmcode-child'); ?></a>
                <?php if ($is_authorized): ?>
                    <a href="<?php echo esc_url(add_query_arg('pc_gcal_revoke', '1', $page_url)); ?>" class="button" id="pc-gcal-revoke-btn" style="margin-left: 10px;"><?php _e('Revoke Access', 'palmcode-child'); ?></a>
                <?php endif; ?>
            </p>
            <p class="description"><?php _e('Click "Authorize" to connect your Google account. You will be redirected to Google to grant permissions.', 'palmcode-child'); ?></p>
        </div>
    </div>
    <?php
}

/**
 * Helper function to get Google Calendar option (for backward compatibility with carbon_get_theme_option)
 */
if (!function_exists('pc_get_gcal_option')) {
    function pc_get_gcal_option($option_name, $default = '') {
        return get_option($option_name, $default);
    }
}

