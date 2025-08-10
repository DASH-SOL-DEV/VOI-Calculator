<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class VOI_Calculator_Settings {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_settings_submenu'), 20);
        add_action('admin_init', array($this, 'register_settings'));
    }
    
    public function add_settings_submenu() {
        add_submenu_page(
            'voi-calculator',
            'Settings',
            'Settings',
            'manage_options',
            'voi-calculator-settings',
            array($this, 'settings_page')
        );
    }
    
    public function register_settings() {
        register_setting('voi_calculator_settings', 'voi_hubspot_api_key');
        register_setting('voi_calculator_settings', 'voi_safe_range_min');
        register_setting('voi_calculator_settings', 'voi_safe_range_max');
        register_setting('voi_calculator_settings', 'voi_sales_notification_email');
    }
    
    public function settings_page() {
        // Handle form submission
        if (isset($_POST['submit'])) {
            $this->save_settings();
        }
        
        // Handle connection test
        if (isset($_POST['test_hubspot'])) {
            $this->test_hubspot_connection();
        }
        
        $hubspot_api_key = get_option('voi_hubspot_api_key', '');
        $safe_range_min = get_option('voi_safe_range_min', 50);
        $safe_range_max = get_option('voi_safe_range_max', 1000);
        $sales_email = get_option('voi_sales_notification_email', '');
        ?>
        <div class="wrap">
            <h1>VOI Calculator Settings</h1>
            
            <?php if (isset($_GET['settings-updated'])): ?>
                <div class="notice notice-success is-dismissible">
                    <p>Settings saved successfully!</p>
                </div>
            <?php endif; ?>
            
            <form method="post" action="">
                <?php wp_nonce_field('voi_settings_nonce', 'voi_settings_nonce_field'); ?>
                
                <div class="card">
                    <h2>HubSpot Integration</h2>
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="voi_hubspot_api_key">HubSpot API Key</label>
                            </th>
                            <td>
                                <input type="password" 
                                       id="voi_hubspot_api_key" 
                                       name="voi_hubspot_api_key" 
                                       value="<?php echo esc_attr($hubspot_api_key); ?>"
                                       class="regular-text" />
                                <p class="description">
                                    Enter your HubSpot Private App API key. 
                                    <a href="https://developers.hubspot.com/docs/api/private-apps" target="_blank">Learn how to create one</a>
                                </p>
                                <?php if (!empty($hubspot_api_key)): ?>
                                    <p>
                                        <button type="submit" name="test_hubspot" class="button button-secondary">
                                            Test Connection
                                        </button>
                                    </p>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <div class="card">
                    <h2>ROI Safe Range</h2>
                    <p>Define the safe range for ROI calculations. Values outside this range will be flagged for manual review.</p>
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="voi_safe_range_min">Minimum ROI (%)</label>
                            </th>
                            <td>
                                <input type="number" 
                                       id="voi_safe_range_min" 
                                       name="voi_safe_range_min" 
                                       value="<?php echo esc_attr($safe_range_min); ?>"
                                       min="0" 
                                       max="10000"
                                       class="small-text" />
                                <span>%</span>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="voi_safe_range_max">Maximum ROI (%)</label>
                            </th>
                            <td>
                                <input type="number" 
                                       id="voi_safe_range_max" 
                                       name="voi_safe_range_max" 
                                       value="<?php echo esc_attr($safe_range_max); ?>"
                                       min="0" 
                                       max="10000"
                                       class="small-text" />
                                <span>%</span>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <div class="card">
                    <h2>Notifications</h2>
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="voi_sales_notification_email">Sales Team Email</label>
                            </th>
                            <td>
                                <input type="email" 
                                       id="voi_sales_notification_email" 
                                       name="voi_sales_notification_email" 
                                       value="<?php echo esc_attr($sales_email); ?>"
                                       class="regular-text" />
                                <p class="description">
                                    Email address to notify when submissions are outside the safe range.
                                </p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <?php submit_button(); ?>
            </form>
            
            <div class="card">
                <h2>Plugin Status</h2>
                <table class="wp-list-table widefat fixed striped">
                    <tbody>
                        <tr>
                            <td><strong>Database Tables</strong></td>
                            <td>
                                <?php
                                global $wpdb;
                                $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}voi_calculator_submissions'");
                                if ($table_exists) {
                                    echo '<span style="color: green;">✓ Created</span>';
                                } else {
                                    echo '<span style="color: red;">✗ Missing</span>';
                                }
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>HubSpot Integration</strong></td>
                            <td>
                                <?php
                                if (!empty($hubspot_api_key)) {
                                    echo '<span style="color: green;">✓ Configured</span>';
                                } else {
                                    echo '<span style="color: orange;">⚠ Not Configured</span>';
                                }
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>PDF Generation</strong></td>
                            <td>
                                <?php
                                $upload_dir = wp_upload_dir();
                                $voi_dir = $upload_dir['basedir'] . '/voi-calculator/';
                                if (is_writable($upload_dir['basedir'])) {
                                    echo '<span style="color: green;">✓ Ready</span>';
                                } else {
                                    echo '<span style="color: red;">✗ Upload directory not writable</span>';
                                }
                                ?>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        
        <style>
        .card {
            background: white;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            padding: 20px;
            margin: 20px 0;
            box-shadow: 0 1px 1px rgba(0,0,0,.04);
        }
        .card h2 {
            margin-top: 0;
        }
        </style>
        <?php
    }
    
    private function save_settings() {
        if (!wp_verify_nonce($_POST['voi_settings_nonce_field'], 'voi_settings_nonce')) {
            wp_die('Security check failed');
        }
        
        update_option('voi_hubspot_api_key', sanitize_text_field($_POST['voi_hubspot_api_key']));
        update_option('voi_safe_range_min', intval($_POST['voi_safe_range_min']));
        update_option('voi_safe_range_max', intval($_POST['voi_safe_range_max']));
        update_option('voi_sales_notification_email', sanitize_email($_POST['voi_sales_notification_email']));
        
        wp_redirect(admin_url('admin.php?page=voi-calculator-settings&settings-updated=1'));
        exit;
    }
    
    private function test_hubspot_connection() {
        if (!wp_verify_nonce($_POST['voi_settings_nonce_field'], 'voi_settings_nonce')) {
            wp_die('Security check failed');
        }
        
        require_once VOI_CALC_PLUGIN_PATH . 'includes/class-hubspot-integration.php';
        
        $hubspot = new VOI_HubSpot_Integration();
        if (!empty($_POST['voi_hubspot_api_key'])) {
            $hubspot->set_api_key(sanitize_text_field($_POST['voi_hubspot_api_key']));
        }
        
        $test_result = $hubspot->test_connection();
        
        if ($test_result['success']) {
            echo '<div class="notice notice-success is-dismissible"><p>HubSpot connection successful!</p></div>';
        } else {
            echo '<div class="notice notice-error is-dismissible"><p>HubSpot connection failed: ' . esc_html($test_result['message']) . '</p></div>';
        }
    }
}

// Initialize settings
new VOI_Calculator_Settings();