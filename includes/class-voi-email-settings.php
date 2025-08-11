<?php
/**
 * Email Settings Admin Page for VOI Calculator
 * 
 * Save this as: includes/class-voi-email-settings.php
 */

class VOI_Email_Settings {
    
    public function __construct() {
        add_action('admin_menu', [$this, 'add_settings_page']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('wp_ajax_voi_test_email', [$this, 'handle_test_email']);
        add_action('wp_ajax_voi_upload_pdf', [$this, 'handle_pdf_upload']);
    }
    
    /**
     * Add email settings page to admin menu
     */
    public function add_settings_page() {
        add_submenu_page(
            'voi-calculator',
            'Email Settings',
            'Email Settings',
            'manage_options',
            'voi-email-settings',
            [$this, 'render_settings_page']
        );
    }
    
    /**
     * Register WordPress settings
     */
    public function register_settings() {
        // Email settings
        register_setting('voi_email_settings', 'voi_email_enabled');
        register_setting('voi_email_settings', 'voi_email_subject');
        register_setting('voi_email_settings', 'voi_email_template');
        register_setting('voi_email_settings', 'voi_email_from_name');
        register_setting('voi_email_settings', 'voi_email_from_address');
        register_setting('voi_email_settings', 'voi_email_reply_to');
        register_setting('voi_email_settings', 'voi_email_additional_pdf');
    }
    
    /**
     * Render the settings page
     */
    public function render_settings_page() {
        // Handle form submission
        if (isset($_POST['submit']) && check_admin_referer('voi_email_settings')) {
            $this->save_settings();
        }
        
        $enabled = get_option('voi_email_enabled', true);
        $subject = get_option('voi_email_subject', 'Your Visual One Intelligence ROI Analysis');
        $template = get_option('voi_email_template', '');
        $from_name = get_option('voi_email_from_name', get_bloginfo('name'));
        $from_address = get_option('voi_email_from_address', get_option('admin_email'));
        $reply_to = get_option('voi_email_reply_to', '');
        $additional_pdf = get_option('voi_email_additional_pdf', '');
        
        // Get default template if none saved
        if (empty($template)) {
            $email_system = new VOI_Email_System();
            $template = $email_system->get_default_email_template();
        }
        
        ?>
        <div class="wrap">
            <h1>Email Settings</h1>
            
            <?php $this->render_status_notice(); ?>
            
            <form method="post" action="" enctype="multipart/form-data">
                <?php wp_nonce_field('voi_email_settings'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">Enable Email Notifications</th>
                        <td>
                            <label>
                                <input type="checkbox" name="voi_email_enabled" value="1" <?php checked($enabled); ?> />
                                Send email to users after form submission
                            </label>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">From Name</th>
                        <td>
                            <input type="text" name="voi_email_from_name" value="<?php echo esc_attr($from_name); ?>" class="regular-text" />
                            <p class="description">Name that appears in the "From" field</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">From Email Address</th>
                        <td>
                            <input type="email" name="voi_email_from_address" value="<?php echo esc_attr($from_address); ?>" class="regular-text" />
                            <p class="description">Email address that sends the notifications</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Reply-To Email (Optional)</th>
                        <td>
                            <input type="email" name="voi_email_reply_to" value="<?php echo esc_attr($reply_to); ?>" class="regular-text" />
                            <p class="description">Different email for replies (leave blank to use From address)</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Email Subject</th>
                        <td>
                            <input type="text" name="voi_email_subject" value="<?php echo esc_attr($subject); ?>" class="large-text" />
                            <p class="description">Use placeholders like {first_name}, {company_name}, etc.</p>
                        </td>
                    </tr>
                </table>
                
                <h3>Email Template</h3>
                <p>Customize the email message sent to users. You can use HTML and the placeholders listed below.</p>
                
                <?php
                wp_editor($template, 'voi_email_template', [
                    'textarea_name' => 'voi_email_template',
                    'textarea_rows' => 15,
                    'media_buttons' => false,
                    'teeny' => false,
                    'quicktags' => true
                ]);
                ?>
                
                <h3>Additional PDF Attachment</h3>
                <table class="form-table">
                    <tr>
                        <th scope="row">Upload Additional PDF</th>
                        <td>
                            <input type="file" name="additional_pdf" accept=".pdf" />
                            <?php if (!empty($additional_pdf)): ?>
                                <p>Current file: <strong><?php echo esc_html($additional_pdf); ?></strong>
                                <a href="<?php echo esc_url($this->get_pdf_url($additional_pdf)); ?>" target="_blank">Download</a>
                                </p>
                            <?php endif; ?>
                            <p class="description">Upload a PDF to attach to all emails (company brochure, additional info, etc.)</p>
                        </td>
                    </tr>
                </table>
                
                <div class="submit-buttons" style="margin-top: 30px;">
                    <?php submit_button('Save Settings', 'primary', 'submit', false); ?>
                    <button type="button" id="send-test-email" class="button button-secondary" style="margin-left: 10px;">Send Test Email</button>
                    <button type="button" id="restore-default-template" class="button button-secondary" style="margin-left: 10px;">Restore Default Template</button>
                </div>
            </form>
            
            <?php $this->render_placeholders_help(); ?>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Test email functionality
            $('#send-test-email').on('click', function() {
                var testEmail = prompt('Enter email address for test:');
                if (testEmail) {
                    $.post(ajaxurl, {
                        action: 'voi_test_email',
                        email: testEmail,
                        nonce: '<?php echo wp_create_nonce('voi_test_email'); ?>'
                    }, function(response) {
                        if (response.success) {
                            alert('Test email sent successfully!');
                        } else {
                            alert('Failed to send test email: ' + response.data);
                        }
                    });
                }
            });
            
            // Restore default template
            $('#restore-default-template').on('click', function() {
                if (confirm('This will replace your current template with the default. Continue?')) {
                    location.reload();
                }
            });
        });
        </script>
        
        <style>
        .placeholders-help { margin-top: 30px; }
        .placeholders-help table { background: #f9f9f9; }
        .placeholders-help th { background: #e9e9e9; }
        </style>
        <?php
    }
    
    /**
     * Save settings
     */
    private function save_settings() {
        $enabled = isset($_POST['voi_email_enabled']) ? 1 : 0;
        $subject = sanitize_text_field($_POST['voi_email_subject'] ?? '');
        $template = wp_kses_post($_POST['voi_email_template'] ?? '');
        $from_name = sanitize_text_field($_POST['voi_email_from_name'] ?? '');
        $from_address = sanitize_email($_POST['voi_email_from_address'] ?? '');
        $reply_to = sanitize_email($_POST['voi_email_reply_to'] ?? '');
        
        update_option('voi_email_enabled', $enabled);
        update_option('voi_email_subject', $subject);
        update_option('voi_email_template', $template);
        update_option('voi_email_from_name', $from_name);
        update_option('voi_email_from_address', $from_address);
        update_option('voi_email_reply_to', $reply_to);
        
        // Handle PDF upload
        if (!empty($_FILES['additional_pdf']['name'])) {
            $this->handle_pdf_upload_form();
        }
        
        echo '<div class="notice notice-success is-dismissible"><p>Settings saved successfully!</p></div>';
    }
    
    /**
     * Handle PDF upload from form
     */
    private function handle_pdf_upload_form() {
        if (!function_exists('wp_handle_upload')) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
        }
        
        // Create upload directory
        $upload_dir = wp_upload_dir();
        $pdf_dir = $upload_dir['basedir'] . '/voi-email-attachments';
        if (!is_dir($pdf_dir)) {
            wp_mkdir_p($pdf_dir);
        }
        
        $uploadedfile = $_FILES['additional_pdf'];
        $upload_overrides = [
            'test_form' => false,
            'mimes' => ['pdf' => 'application/pdf']
        ];
        
        $movefile = wp_handle_upload($uploadedfile, $upload_overrides);
        
        if ($movefile && !isset($movefile['error'])) {
            // Move to our specific directory
            $filename = basename($movefile['file']);
            $new_location = $pdf_dir . '/' . $filename;
            
            if (rename($movefile['file'], $new_location)) {
                update_option('voi_email_additional_pdf', $filename);
            }
        }
    }
    
    /**
     * Handle test email AJAX request
     */
    public function handle_test_email() {
        if (!check_ajax_referer('voi_test_email', 'nonce', false)) {
            wp_send_json_error('Security check failed');
            return;
        }
        
        $test_email = sanitize_email($_POST['email'] ?? '');
        if (empty($test_email)) {
            wp_send_json_error('Invalid email address');
            return;
        }
        
        $email_system = new VOI_Email_System();
        $sent = $email_system->send_test_email($test_email);
        
        if ($sent) {
            wp_send_json_success('Test email sent successfully');
        } else {
            wp_send_json_error('Failed to send test email');
        }
    }
    
    /**
     * Get PDF URL
     */
    private function get_pdf_url($filename) {
        $upload_dir = wp_upload_dir();
        return $upload_dir['baseurl'] . '/voi-email-attachments/' . $filename;
    }
    
    /**
     * Render status notice
     */
    private function render_status_notice() {
        $enabled = get_option('voi_email_enabled', true);
        
        if ($enabled) {
            echo '<div class="notice notice-info"><p><strong>Email notifications are enabled.</strong> Users will receive emails after form submission.</p></div>';
        } else {
            echo '<div class="notice notice-warning"><p><strong>Email notifications are disabled.</strong> No emails will be sent to users.</p></div>';
        }
    }
    
    /**
     * Render placeholders help section
     */
    private function render_placeholders_help() {
        $placeholders = VOI_Email_System::get_available_placeholders();
        ?>
        <div class="placeholders-help">
            <h3>Available Placeholders</h3>
            <p>You can use these placeholders in your email subject and template:</p>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width: 200px;">Placeholder</th>
                        <th>Description</th>
                        <th style="width: 150px;">Example</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($placeholders as $placeholder => $description): ?>
                    <tr>
                        <td><code><?php echo esc_html($placeholder); ?></code></td>
                        <td><?php echo esc_html($description); ?></td>
                        <td>
                            <?php
                            switch ($placeholder) {
                                case '{full_name}': echo 'John Doe'; break;
                                case '{first_name}': echo 'John'; break;
                                case '{company_name}': echo 'ACME Corp'; break;
                                case '{company_url}': echo 'acme.com'; break;
                                case '{total_tb}': echo '1,000'; break;
                                case '{total_vms}': echo '500'; break;
                                case '{email}': echo 'john@acme.com'; break;
                                case '{date}': echo date('F j, Y'); break;
                                case '{time}': echo date('g:i A'); break;
                                case '{site_name}': echo get_bloginfo('name'); break;
                                case '{site_url}': echo home_url(); break;
                                default: echo '—';
                            }
                            ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
}