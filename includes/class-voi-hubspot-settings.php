<?php
/**
 * HubSpot Settings Page Class for VOI Calculator
 * 
 * Save this as: includes/class-voi-hubspot-settings.php
 */

class VOI_HubSpot_Settings {
    
    public function __construct() {
        add_action('admin_menu', [$this, 'add_settings_page']);
        add_action('admin_init', [$this, 'register_settings']);
    }
    
    /**
     * Add HubSpot settings page to admin menu
     */
    public function add_settings_page() {
        add_submenu_page(
            'voi-calculator',
            'HubSpot Integration',
            'HubSpot Settings',
            'manage_options',
            'voi-hubspot-settings',
            [$this, 'render_settings_page']
        );
    }
    
    /**
     * Register WordPress settings
     */
    public function register_settings() {
        register_setting('voi_hubspot_settings', 'voi_hubspot_api_key');
        register_setting('voi_hubspot_settings', 'voi_hubspot_portal_id');
        register_setting('voi_hubspot_settings', 'voi_hubspot_enabled');
    }
    
    /**
     * Render the settings page
     */
    public function render_settings_page() {
        // Handle form submission
        if (isset($_POST['submit']) && check_admin_referer('voi_hubspot_settings')) {
            $this->save_settings();
        }
        
        // Handle connection test
        if (isset($_POST['test_connection']) && check_admin_referer('voi_hubspot_settings')) {
            $this->test_hubspot_connection();
        }
        
        $api_key = get_option('voi_hubspot_api_key', '');
        $portal_id = get_option('voi_hubspot_portal_id', '');
        $enabled = get_option('voi_hubspot_enabled', false);
        
        ?>
        <div class="wrap">
            <h1>HubSpot Integration Settings</h1>
            
            <?php $this->render_status_notice(); ?>
            
            <form method="post" action="">
                <?php wp_nonce_field('voi_hubspot_settings'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">Enable HubSpot Integration</th>
                        <td>
                            <label>
                                <input type="checkbox" name="voi_hubspot_enabled" value="1" <?php checked($enabled); ?> />
                                Send form data to HubSpot
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">HubSpot API Key</th>
                        <td>
                            <input type="password" name="voi_hubspot_api_key" value="<?php echo esc_attr($api_key); ?>" class="regular-text" />
                            <p class="description">Your HubSpot Private App Access Token</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Portal ID (Optional)</th>
                        <td>
                            <input type="text" name="voi_hubspot_portal_id" value="<?php echo esc_attr($portal_id); ?>" class="regular-text" />
                            <p class="description">Your HubSpot Portal ID for reference</p>
                        </td>
                    </tr>
                </table>
                
                <div class="submit-buttons" style="margin-top: 20px;">
                    <?php submit_button('Save Settings', 'primary', 'submit', false); ?>
                    <?php if (!empty($api_key)): ?>
                        <button type="submit" name="test_connection" class="button button-secondary" style="margin-left: 10px;">Test Connection</button>
                    <?php endif; ?>
                </div>
            </form>
            
            <?php $this->render_setup_instructions(); ?>
            <?php $this->render_custom_properties_info(); ?>
        </div>
        <?php
    }
    
    /**
     * Save settings
     */
    private function save_settings() {
        $api_key = sanitize_text_field($_POST['voi_hubspot_api_key'] ?? '');
        $portal_id = sanitize_text_field($_POST['voi_hubspot_portal_id'] ?? '');
        $enabled = isset($_POST['voi_hubspot_enabled']) ? 1 : 0;
        
        update_option('voi_hubspot_api_key', $api_key);
        update_option('voi_hubspot_portal_id', $portal_id);
        update_option('voi_hubspot_enabled', $enabled);
        
        echo '<div class="notice notice-success is-dismissible"><p>Settings saved successfully!</p></div>';
    }
    
    /**
     * Test HubSpot connection
     */
    private function test_hubspot_connection() {
        $hubspot = new VOI_HubSpot_Integration();
        $result = $hubspot->test_connection();
        
        if (is_wp_error($result)) {
            echo '<div class="notice notice-error is-dismissible"><p><strong>Connection Failed:</strong> ' . esc_html($result->get_error_message()) . '</p></div>';
        } else {
            echo '<div class="notice notice-success is-dismissible"><p><strong>Connection Successful!</strong> HubSpot API is working correctly.</p></div>';
        }
    }
    
    /**
     * Render status notice
     */
    private function render_status_notice() {
        $enabled = get_option('voi_hubspot_enabled', false);
        $api_key = get_option('voi_hubspot_api_key', '');
        
        if ($enabled && empty($api_key)) {
            echo '<div class="notice notice-warning"><p><strong>Warning:</strong> HubSpot integration is enabled but no API key is configured.</p></div>';
        } elseif ($enabled && !empty($api_key)) {
            echo '<div class="notice notice-info"><p><strong>Info:</strong> HubSpot integration is active. Form submissions will be sent to HubSpot.</p></div>';
        }
    }
    
    /**
     * Render setup instructions
     */
    private function render_setup_instructions() {
        ?>
        <div class="card" style="margin-top: 30px;">
            <h2>Setup Instructions</h2>
            <ol>
                <li><strong>Create a HubSpot Private App:</strong>
                    <ul>
                        <li>Go to your HubSpot account → Settings → Integrations → Private Apps</li>
                        <li>Click "Create a private app"</li>
                        <li>Give it a name like "VOI Calculator Integration"</li>
                    </ul>
                </li>
                <li><strong>Configure Scopes:</strong>
                    <ul>
                        <li>In the Scopes tab, enable: <code>crm.objects.contacts.write</code></li>
                        <li>Also enable: <code>crm.objects.contacts.read</code></li>
                    </ul>
                </li>
                <li><strong>Get Your Access Token:</strong>
                    <ul>
                        <li>Copy the "Access token" from your private app</li>
                        <li>Paste it in the "HubSpot API Key" field above</li>
                    </ul>
                </li>
                <li><strong>Create Custom Properties (see below)</strong></li>
                <li><strong>Test the connection using the button above</strong></li>
            </ol>
        </div>
        <?php
    }
    
    /**
     * Render custom properties information
     */
    private function render_custom_properties_info() {
        $properties = VOI_HubSpot_Integration::get_required_custom_properties();
        ?>
        <div class="card" style="margin-top: 20px;">
            <h2>Required Custom Properties</h2>
            <p>You need to create these custom properties in HubSpot for contacts:</p>
            <p><strong>Path:</strong> Settings → Properties → Contact Properties → "Create property"</p>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Property Name</th>
                        <th>Label</th>
                        <th>Type</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($properties as $property): ?>
                    <tr>
                        <td><code><?php echo esc_html($property['name']); ?></code></td>
                        <td><?php echo esc_html($property['label']); ?></td>
                        <td><?php echo esc_html($property['type']); ?></td>
                        <td>
                            <?php
                            switch ($property['name']) {
                                case 'voi_total_tb':
                                    echo 'Total TB of storage from calculator';
                                    break;
                                case 'voi_total_vms':
                                    echo 'Total number of VMs from calculator';
                                    break;
                                case 'voi_annual_savings':
                                    echo 'Calculated annual savings amount';
                                    break;
                                case 'voi_annual_roi':
                                    echo 'Calculated annual ROI percentage';
                                    break;
                                case 'voi_payback_months':
                                    echo 'Calculated payback period in months';
                                    break;
                                case 'voi_submission_date':
                                    echo 'Date when calculator form was submitted';
                                    break;
                                case 'voi_calculated_date':
                                    echo 'Date and time when ROI was calculated';
                                    break;
                                default:
                                    echo 'VOI Calculator data';
                            }
                            ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <p><strong>Note:</strong> The integration will work without these custom properties, but the VOI-specific data won't be stored in HubSpot.</p>
        </div>
        <?php
    }
}