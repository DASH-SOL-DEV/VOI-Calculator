<?php
/**
 * Plugin Name:       VOI Calculator
 * Plugin URI:        https://niftyfiftysolutions.com/
 * Description:       A two-stage ROI calculator for Visual Storage Intelligence with HubSpot integration, email notifications, and advanced customizable calculator.
 * Version:           1.8.0
 * Author:            Nifty Fifty Solution
 * Author URI:        https://niftyfiftysolutions.com/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       voi-calculator
 */

if ( ! defined( 'WPINC' ) ) die;

define( 'VOI_CALCULATOR_VERSION', '1.8.0' );
define( 'VOI_CALCULATOR_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'VOI_CALCULATOR_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Include all required classes
require_once VOI_CALCULATOR_PLUGIN_DIR . 'includes/class-voi-calculator-admin.php';
require_once VOI_CALCULATOR_PLUGIN_DIR . 'includes/class-voi-calculator-pdf-generator.php';
require_once VOI_CALCULATOR_PLUGIN_DIR . 'includes/class-voi-hubspot-integration.php';
require_once VOI_CALCULATOR_PLUGIN_DIR . 'includes/class-voi-hubspot-settings.php';
require_once VOI_CALCULATOR_PLUGIN_DIR . 'includes/class-voi-email-system.php';
require_once VOI_CALCULATOR_PLUGIN_DIR . 'includes/class-voi-email-settings.php';
require_once VOI_CALCULATOR_PLUGIN_DIR . 'includes/class-voi-advanced-calculator.php';
require_once VOI_CALCULATOR_PLUGIN_DIR . 'vendor/tcpdf.php';

// Start session on init
add_action('init', 'voi_calculator_session_start');
function voi_calculator_session_start() {
    if (!session_id()) {
        session_start();
    }
}

register_activation_hook( __FILE__, 'voi_calculator_activate' );
function voi_calculator_activate() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    
    // Create basic submissions table (Phase 1)
    $table_name = $wpdb->prefix . 'voi_submissions';
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        total_tb int(11) NOT NULL,
        total_vms int(11) NOT NULL,
        company_name varchar(255) NOT NULL,
        company_url varchar(255) DEFAULT '' NOT NULL,
        full_name varchar(255) NOT NULL,
        email varchar(100) NOT NULL,
        pdf_link varchar(255) DEFAULT '' NOT NULL,
        hubspot_contact_id varchar(50) DEFAULT '' NOT NULL,
        hubspot_sent datetime DEFAULT NULL,
        email_sent tinyint(1) DEFAULT 0,
        email_sent_time datetime DEFAULT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    
    // Create advanced submissions table (Phase 2)
    $advanced_table_name = $wpdb->prefix . 'voi_advanced_submissions';
    $advanced_sql = "CREATE TABLE $advanced_table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        full_name varchar(255) NOT NULL,
        email varchar(100) NOT NULL,
        company_name varchar(255) NOT NULL,
        company_url varchar(255) DEFAULT '' NOT NULL,
        input_data longtext NOT NULL,
        calculations longtext NOT NULL,
        total_annual_savings decimal(15,2) DEFAULT 0,
        annual_roi decimal(8,2) DEFAULT 0,
        payback_months decimal(6,2) DEFAULT 0,
        pdf_link varchar(255) DEFAULT '' NOT NULL,
        hubspot_contact_id varchar(50) DEFAULT '' NOT NULL,
        hubspot_sent datetime DEFAULT NULL,
        email_sent tinyint(1) DEFAULT 0,
        email_sent_time datetime DEFAULT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );
    dbDelta( $advanced_sql );
    
    // Create upload directories
    $upload_dir = wp_upload_dir();
    $pdf_dir = $upload_dir['basedir'] . '/voi-calculator-pdfs';
    $email_dir = $upload_dir['basedir'] . '/voi-email-attachments';
    if (!is_dir($pdf_dir)) wp_mkdir_p($pdf_dir);
    if (!is_dir($email_dir)) wp_mkdir_p($email_dir);
}

// Enqueue assets for Phase 1 (Simple Calculator)
add_action( 'wp_enqueue_scripts', 'voi_calculator_enqueue_assets' );
function voi_calculator_enqueue_assets() {
    if ( is_a( get_post( get_the_ID() ), 'WP_Post' ) && has_shortcode( get_post( get_the_ID() )->post_content, 'voi_calculator' ) ) {
        wp_enqueue_style('voi-calculator-style', VOI_CALCULATOR_PLUGIN_URL . 'assets/css/voi-style.css', [], VOI_CALCULATOR_VERSION);
        wp_enqueue_script('voi-calculator-script', VOI_CALCULATOR_PLUGIN_URL . 'assets/js/voi-script.js', ['jquery'], VOI_CALCULATOR_VERSION, true);
        wp_localize_script('voi-calculator-script', 'voi_ajax', ['ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('voi_calculator_nonce')]);
    }
}

// Enqueue assets for Phase 2 (Advanced Calculator)
add_action( 'wp_enqueue_scripts', 'voi_advanced_calculator_enqueue_assets' );
function voi_advanced_calculator_enqueue_assets() {
    if ( is_a( get_post( get_the_ID() ), 'WP_Post' ) && has_shortcode( get_post( get_the_ID() )->post_content, 'voi_advanced_calculator' ) ) {
        // Enqueue advanced calculator assets
        wp_enqueue_style('voi-advanced-style', VOI_CALCULATOR_PLUGIN_URL . 'assets/css/voi-advanced-style.css', [], VOI_CALCULATOR_VERSION);
        wp_enqueue_script('voi-advanced-script', VOI_CALCULATOR_PLUGIN_URL . 'assets/js/voi-advanced-script.js', ['jquery'], VOI_CALCULATOR_VERSION, true);
        
        // Localize script with AJAX URL and nonce
        wp_localize_script('voi-advanced-script', 'voi_advanced_ajax', [
            'ajax_url' => admin_url('admin-ajax.php'), 
            'nonce' => wp_create_nonce('voi_calculator_nonce')
        ]);
    }
}

// Phase 1 Shortcode - Simple Calculator
add_shortcode( 'voi_calculator', 'voi_calculator_form_shortcode' );
function voi_calculator_form_shortcode() {
    $submission_id = isset($_GET['submission_id']) ? intval($_GET['submission_id']) : 0;
    $session_id = isset($_SESSION['voi_submission_id']) ? intval($_SESSION['voi_submission_id']) : 0;
    
    $show_results = false;
    $results_data = [];

    if ($submission_id > 0 && $submission_id === $session_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'voi_submissions';
        $submission = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $submission_id), ARRAY_A);

        if ($submission) {
            $show_results = true;
            $generator = new VOI_Calculator_PDF_Generator($submission);
            $pdf_result = $generator->generate(); 
            $results_data['roi_html'] = $pdf_result['html'];
            $results_data['pdf_url'] = $submission['pdf_link'];
        }
    }

    ob_start();
    include VOI_CALCULATOR_PLUGIN_DIR . 'public/partials/form-display.php';
    return ob_get_clean();
}

// Phase 2 Shortcode - Advanced Calculator
add_shortcode( 'voi_advanced_calculator', 'voi_advanced_calculator_shortcode' );
function voi_advanced_calculator_shortcode($atts = []) {
    // Parse attributes
    $atts = shortcode_atts([
        'title' => 'Advanced ROI Calculator',
        'description' => 'Create a custom analysis using your own parameters'
    ], $atts);
    
    ob_start();
    include VOI_CALCULATOR_PLUGIN_DIR . 'public/partials/advanced-form-display.php';
    return ob_get_clean();
}

// Phase 1 AJAX Handler - Simple Calculator Form Submission
add_action( 'wp_ajax_voi_handle_form_submission', 'voi_handle_form_submission' );
add_action( 'wp_ajax_nopriv_voi_handle_form_submission', 'voi_handle_form_submission' );
function voi_handle_form_submission() {
    if ( ! check_ajax_referer( 'voi_calculator_nonce', 'nonce', false ) ) {
        wp_send_json_error( ['message' => 'Security check failed.'], 403 );
        return;
    }

    $form_data = [
        'total_tb'     => isset($_POST['total_tb']) ? intval($_POST['total_tb']) : 0,
        'total_vms'    => isset($_POST['total_vms']) ? intval($_POST['total_vms']) : 0,
        'company_name' => isset($_POST['company_name']) ? sanitize_text_field($_POST['company_name']) : '',
        'company_url'  => isset($_POST['company_url']) ? esc_url_raw($_POST['company_url']) : '',
        'full_name'    => isset($_POST['full_name']) ? sanitize_text_field($_POST['full_name']) : '',
        'email'        => isset($_POST['email']) ? sanitize_email($_POST['email']) : '',
    ];

    // Generate PDF
    $pdf_generator = new VOI_Calculator_PDF_Generator($form_data);
    $pdf_result = $pdf_generator->generate();

    if (is_wp_error($pdf_result)) {
        wp_send_json_error(['message' => $pdf_result->get_error_message()], 500);
        return;
    }

    // Save to database
    global $wpdb;
    $table_name = $wpdb->prefix . 'voi_submissions';
    $db_data = $form_data;
    $db_data['time'] = current_time('mysql');
    $db_data['pdf_link'] = $pdf_result['url'];
    $result = $wpdb->insert($table_name, $db_data);
    $submission_id = $wpdb->insert_id;

    if ($result) {
        $_SESSION['voi_submission_id'] = $submission_id;
        session_write_close();
        
        // Send to HubSpot if enabled
        $hubspot_enabled = get_option('voi_hubspot_enabled', false);
        if ($hubspot_enabled) {
            $hubspot = new VOI_HubSpot_Integration();
            $hubspot_result = $hubspot->create_or_update_contact($form_data);
            
            if (is_wp_error($hubspot_result)) {
                error_log('VOI Calculator - HubSpot integration failed for ' . $form_data['email'] . ': ' . $hubspot_result->get_error_message());
            } else {
                $hubspot_contact_id = isset($hubspot_result['id']) ? $hubspot_result['id'] : '';
                error_log('VOI Calculator - Successfully sent data to HubSpot for: ' . $form_data['email']);
                
                // Update database with HubSpot info
                $wpdb->update(
                    $table_name,
                    [
                        'hubspot_contact_id' => $hubspot_contact_id,
                        'hubspot_sent' => current_time('mysql')
                    ],
                    ['id' => $submission_id]
                );
            }
        }
        
        // Trigger email notification
        do_action('voi_form_submitted', $form_data, $pdf_result['path'], $submission_id);
        
        wp_send_json_success([
            'submission_id' => $submission_id,
            'pdf_url' => $pdf_result['url'],
            'html_output' => $pdf_result['html']
        ]);
    } else {
        wp_send_json_error(['message' => 'There was an error saving your data.'], 500);
    }
}

// Hook advanced form submission to existing integrations
add_action('voi_advanced_form_submitted', 'handle_advanced_form_integrations', 10, 4);
function handle_advanced_form_integrations($form_data, $pdf_path, $submission_id, $calculations) {
    // Send to HubSpot if enabled
    $hubspot_enabled = get_option('voi_hubspot_enabled', false);
    if ($hubspot_enabled && class_exists('VOI_HubSpot_Integration')) {
        $hubspot = new VOI_HubSpot_Integration();
        $hubspot_result = $hubspot->create_or_update_contact($form_data);
        
        if (!is_wp_error($hubspot_result)) {
            // Update advanced submissions table with HubSpot info
            global $wpdb;
            $table_name = $wpdb->prefix . 'voi_advanced_submissions';
            $wpdb->update(
                $table_name,
                [
                    'hubspot_contact_id' => isset($hubspot_result['id']) ? $hubspot_result['id'] : '',
                    'hubspot_sent' => current_time('mysql')
                ],
                ['id' => $submission_id]
            );
            
            error_log('VOI Advanced Calculator - Successfully sent to HubSpot: ' . $form_data['email']);
        } else {
            error_log('VOI Advanced Calculator - HubSpot failed: ' . $hubspot_result->get_error_message());
        }
    }
    
    // Send email notification if enabled
    $email_enabled = get_option('voi_email_enabled', false);
    if ($email_enabled && class_exists('VOI_Email_System')) {
        $email_system = new VOI_Email_System();
        $email_sent = $email_system->send_submission_email($form_data, $pdf_path, $submission_id);
        
        if ($email_sent) {
            // Update advanced submissions table with email status
            global $wpdb;
            $table_name = $wpdb->prefix . 'voi_advanced_submissions';
            $wpdb->update(
                $table_name,
                [
                    'email_sent' => 1,
                    'email_sent_time' => current_time('mysql')
                ],
                ['id' => $submission_id]
            );
        }
    }
}

// Admin page for advanced submissions
add_action('admin_menu', 'voi_add_advanced_admin_menu');
function voi_add_advanced_admin_menu() {
    add_submenu_page(
        'voi-calculator',
        'Advanced Submissions',
        'Advanced Submissions',
        'manage_options',
        'voi-advanced-submissions',
        'voi_render_advanced_submissions_page'
    );
}

function voi_render_advanced_submissions_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'voi_advanced_submissions';
    
    // Handle actions
    if (isset($_GET['action']) && $_GET['action'] === 'view' && isset($_GET['id'])) {
        $submission_id = intval($_GET['id']);
        $submission = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $submission_id), ARRAY_A);
        
        if ($submission) {
            voi_render_advanced_submission_detail($submission);
            return;
        }
    }
    
    // Get all submissions
    $submissions = $wpdb->get_results("SELECT * FROM $table_name ORDER BY time DESC LIMIT 50", ARRAY_A);
    
    ?>
    <div class="wrap">
        <h1>Advanced Calculator Submissions</h1>
        <div class="tablenav top">
            <div class="alignleft actions">
                <span class="displaying-num"><?php echo count($submissions); ?> submissions</span>
            </div>
        </div>
        
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Company</th>
                    <th>Contact</th>
                    <th>Email</th>
                    <th>Annual Savings</th>
                    <th>ROI</th>
                    <th>Payback</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($submissions as $submission): ?>
                <tr>
                    <td><strong><?php echo esc_html($submission['company_name']); ?></strong></td>
                    <td><?php echo esc_html($submission['full_name']); ?></td>
                    <td><?php echo esc_html($submission['email']); ?></td>
                    <td>$<?php echo number_format($submission['total_annual_savings']); ?></td>
                    <td><?php echo number_format($submission['annual_roi'], 1); ?>%</td>
                    <td><?php echo number_format($submission['payback_months'], 1); ?> mo</td>
                    <td><?php echo date('M j, Y', strtotime($submission['time'])); ?></td>
                    <td>
                        <a href="<?php echo admin_url('admin.php?page=voi-advanced-submissions&action=view&id=' . $submission['id']); ?>" 
                           class="button button-small">View Details</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}

function voi_render_advanced_submission_detail($submission) {
    $input_data = json_decode($submission['input_data'], true);
    $calculations = json_decode($submission['calculations'], true);
    
    ?>
    <div class="wrap">
        <h1>Advanced Submission Details</h1>
        <a href="<?php echo admin_url('admin.php?page=voi-advanced-submissions'); ?>" class="button">&larr; Back to List</a>
        
        <div class="postbox-container" style="margin-top: 20px;">
            <div class="postbox">
                <h2 class="hndle">Contact Information</h2>
                <div class="inside">
                    <table class="form-table">
                        <tr><th>Company:</th><td><?php echo esc_html($submission['company_name']); ?></td></tr>
                        <tr><th>Contact:</th><td><?php echo esc_html($submission['full_name']); ?></td></tr>
                        <tr><th>Email:</th><td><?php echo esc_html($submission['email']); ?></td></tr>
                        <tr><th>Website:</th><td><?php echo esc_html($submission['company_url']); ?></td></tr>
                        <tr><th>Submitted:</th><td><?php echo date('F j, Y g:i A', strtotime($submission['time'])); ?></td></tr>
                    </table>
                </div>
            </div>
            
            <div class="postbox">
                <h2 class="hndle">ROI Summary</h2>
                <div class="inside">
                    <table class="form-table">
                        <tr><th>Total Annual Savings:</th><td><strong>$<?php echo number_format($submission['total_annual_savings']); ?></strong></td></tr>
                        <tr><th>Annual ROI:</th><td><strong><?php echo number_format($submission['annual_roi'], 1); ?>%</strong></td></tr>
                        <tr><th>Payback Period:</th><td><strong><?php echo number_format($submission['payback_months'], 1); ?> months</strong></td></tr>
                        <?php if ($calculations): ?>
                        <tr><th>Net Annual Benefit:</th><td>$<?php echo number_format($calculations['summary']['net_benefit'] ?? 0); ?></td></tr>
                        <tr><th>VOI Annual Cost:</th><td>$<?php echo number_format($calculations['summary']['voi_annual_cost'] ?? 0); ?></td></tr>
                        <?php endif; ?>
                    </table>
                </div>
            </div>
            
            <?php if ($input_data): ?>
            <div class="postbox">
                <h2 class="hndle">Custom Parameters</h2>
                <div class="inside">
                    <table class="form-table">
                        <tr><th>Total Storage (TB):</th><td><?php echo number_format($input_data['total_tb'] ?? 0); ?></td></tr>
                        <tr><th>Cost per TB:</th><td>$<?php echo number_format($input_data['cost_per_tb'] ?? 0); ?></td></tr>
                        <tr><th>Total VMs:</th><td><?php echo number_format($input_data['total_vms'] ?? 0); ?></td></tr>
                        <tr><th>Employee Yearly Cost:</th><td>$<?php echo number_format($input_data['employee_yearly_cost'] ?? 0); ?></td></tr>
                        <tr><th>Work Hours/Year:</th><td><?php echo number_format($input_data['work_hours_yearly'] ?? 0); ?></td></tr>
                        <tr><th>Orphaned Space %:</th><td><?php echo number_format($input_data['reuse_orphaned_percent'] ?? 0, 1); ?>%</td></tr>
                        <tr><th>Process Improvement %:</th><td><?php echo number_format($input_data['improved_processes_percent'] ?? 0, 1); ?>%</td></tr>
                        <tr><th>Buying Accuracy %:</th><td><?php echo number_format($input_data['buying_accuracy_percent'] ?? 0, 1); ?>%</td></tr>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php
}

// Initialize all systems
add_action('plugins_loaded', 'voi_calculator_init_systems');
function voi_calculator_init_systems() {
    // Initialize admin system
    $voi_admin = new VOI_Calculator_Admin();
    $voi_admin->init();
    
    // Initialize HubSpot system
    if (class_exists('VOI_HubSpot_Settings')) {
        new VOI_HubSpot_Settings();
    }
    
    // Initialize email system
    if (class_exists('VOI_Email_System')) {
        new VOI_Email_System();
    }
    
    if (class_exists('VOI_Email_Settings')) {
        new VOI_Email_Settings();
    }
    
    // Initialize advanced calculator system
    if (class_exists('VOI_Advanced_Calculator')) {
        new VOI_Advanced_Calculator();
    }
}