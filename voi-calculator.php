<?php
/**
 * Plugin Name: VOI Calculator
 * Plugin URI: https://niftyfiftysolutions.com/
 * Description: ROI/Value Calculator for Visual One Intelligence with HubSpot integration and PDF generation
 * Version: 1.0.0
 * Author: Nifty Fifty Solutions
 * Author URI: https://niftyfiftysolutions.com/
 * License: GPL v2 or later
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('VOI_CALC_PLUGIN_URL', plugin_dir_url(__FILE__));
define('VOI_CALC_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('VOI_CALC_VERSION', '1.0.0');

class VOI_Calculator {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'create_tables'));
    }
    
    public function init() {
        // Add shortcode
        add_shortcode('voi_calculator', array($this, 'render_calculator'));
        
        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // Handle AJAX requests
        add_action('wp_ajax_voi_calculator_submit', array($this, 'handle_form_submission'));
        add_action('wp_ajax_nopriv_voi_calculator_submit', array($this, 'handle_form_submission'));
        
        // Debug AJAX handlers
        add_action('wp_ajax_voi_test_pdf', array($this, 'test_pdf_generation'));
        add_action('wp_ajax_voi_test_hubspot', array($this, 'test_hubspot_connection'));
        
        // Load admin functionality
        if (is_admin()) {
            if (file_exists(VOI_CALC_PLUGIN_PATH . 'admin/admin-page.php')) {
                require_once VOI_CALC_PLUGIN_PATH . 'admin/admin-page.php';
            }
            if (file_exists(VOI_CALC_PLUGIN_PATH . 'admin/settings-page.php')) {
                require_once VOI_CALC_PLUGIN_PATH . 'admin/settings-page.php';
            }
            if (file_exists(VOI_CALC_PLUGIN_PATH . 'admin/debug-page.php')) {
                require_once VOI_CALC_PLUGIN_PATH . 'admin/debug-page.php';
            }
        }
        
        // Load includes with error checking
        if (file_exists(VOI_CALC_PLUGIN_PATH . 'includes/class-pdf-generator.php')) {
            require_once VOI_CALC_PLUGIN_PATH . 'includes/class-pdf-generator.php';
            error_log('VOI Calculator: PDF Generator class loaded');
        } else {
            error_log('VOI Calculator: PDF Generator class file not found at ' . VOI_CALC_PLUGIN_PATH . 'includes/class-pdf-generator.php');
        }
        
        if (file_exists(VOI_CALC_PLUGIN_PATH . 'includes/class-hubspot-integration.php')) {
            require_once VOI_CALC_PLUGIN_PATH . 'includes/class-hubspot-integration.php';
            error_log('VOI Calculator: HubSpot Integration class loaded');
        } else {
            error_log('VOI Calculator: HubSpot Integration class file not found at ' . VOI_CALC_PLUGIN_PATH . 'includes/class-hubspot-integration.php');
        }
    }
    
    public function create_tables() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'voi_calculator_submissions';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            storage_tb varchar(20) NOT NULL,
            vm_count varchar(20) NOT NULL,
            first_name varchar(100) NOT NULL,
            last_name varchar(100) NOT NULL,
            email varchar(100) NOT NULL,
            company_name varchar(200) NOT NULL,
            company_url varchar(200) NOT NULL,
            submission_date datetime DEFAULT CURRENT_TIMESTAMP,
            hubspot_sent tinyint(1) DEFAULT 0,
            pdf_generated tinyint(1) DEFAULT 0,
            pdf_file_path varchar(255) DEFAULT NULL,
            calculated_roi decimal(10,2) DEFAULT NULL,
            annual_savings decimal(15,2) DEFAULT NULL,
            is_safe_range tinyint(1) DEFAULT 1,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    public function enqueue_scripts() {
        wp_enqueue_style('voi-calculator-style', VOI_CALC_PLUGIN_URL . 'assets/style.css', array(), VOI_CALC_VERSION);
        wp_enqueue_script('voi-calculator-script', VOI_CALC_PLUGIN_URL . 'assets/script.js', array('jquery'), VOI_CALC_VERSION, true);
        
        // Localize script for AJAX
        wp_localize_script('voi-calculator-script', 'voi_calc_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('voi_calc_nonce')
        ));
    }
    
    public function render_calculator($atts) {
        ob_start();
        ?>
        <div id="voi-calculator-container">
            <div class="voi-calc-header">
                <h2>Simplified Value Calculator</h2>
                <p>This calculator uses industry standard values to show the value of Visual One Intelligence. If you prefer to use your own industry values, then click <a href="#" class="detailed-link">HERE</a> for the Detailed Value Calculator.</p>
            </div>
            
            <form id="voi-calculator-form" class="voi-calc-form">
                <?php wp_nonce_field('voi_calc_nonce', 'voi_calc_nonce_field'); ?>
                
                <div class="voi-form-row">
                    <label for="storage_tb">Enter Total Sold TB of storage in your environment.</label>
                    <input type="number" id="storage_tb" name="storage_tb" min="1" step="0.1" required>
                    <span class="unit">TB</span>
                </div>
                
                <div class="voi-form-row">
                    <label for="vm_count">Enter Total Number of VMs in your environment.</label>
                    <input type="number" id="vm_count" name="vm_count" min="1" required>
                    <span class="unit">VMs</span>
                </div>
                
                <div class="voi-form-row">
                    <label for="company_name">Company Name</label>
                    <input type="text" id="company_name" name="company_name" required>
                </div>
                
                <div class="voi-form-row">
                    <label for="company_url">Company URL</label>
                    <input type="url" id="company_url" name="company_url" required>
                </div>
                
                <div class="voi-form-row-split">
                    <div class="voi-form-half">
                        <label for="first_name">First Name</label>
                        <input type="text" id="first_name" name="first_name" required>
                    </div>
                    <div class="voi-form-half">
                        <label for="last_name">Last Name</label>
                        <input type="text" id="last_name" name="last_name" required>
                    </div>
                </div>
                
                <div class="voi-form-row">
                    <label for="email">Email address to mail the generated value document</label>
                    <input type="email" id="email" name="email" required>
                </div>
                
                <div class="voi-form-submit">
                    <button type="submit" id="voi-submit-btn" class="voi-submit-button">
                        Generate Value Document and See results
                    </button>
                </div>
                
                <div id="voi-form-messages" class="voi-messages"></div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }
    
    public function handle_form_submission() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['voi_calc_nonce_field'], 'voi_calc_nonce')) {
            wp_die('Security check failed');
        }
        
        // Sanitize and validate input data
        $storage_tb = sanitize_text_field($_POST['storage_tb']);
        $vm_count = sanitize_text_field($_POST['vm_count']);
        $first_name = sanitize_text_field($_POST['first_name']);
        $last_name = sanitize_text_field($_POST['last_name']);
        $email = sanitize_email($_POST['email']);
        $company_name = sanitize_text_field($_POST['company_name']);
        $company_url = esc_url_raw($_POST['company_url']);
        
        // Validate required fields
        if (empty($storage_tb) || empty($vm_count) || empty($first_name) || empty($last_name) || empty($email) || empty($company_name) || empty($company_url)) {
            wp_send_json_error(array('message' => 'All fields are required.'));
            return;
        }
        
        // Validate email
        if (!is_email($email)) {
            wp_send_json_error(array('message' => 'Please enter a valid email address.'));
            return;
        }
        
        // Save to database
        global $wpdb;
        $table_name = $wpdb->prefix . 'voi_calculator_submissions';
        
        $result = $wpdb->insert(
            $table_name,
            array(
                'storage_tb' => $storage_tb,
                'vm_count' => $vm_count,
                'first_name' => $first_name,
                'last_name' => $last_name,
                'email' => $email,
                'company_name' => $company_name,
                'company_url' => $company_url,
                'submission_date' => current_time('mysql')
            ),
            array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
        );
        
        if ($result === false) {
            wp_send_json_error(array('message' => 'Failed to save submission. Please try again.'));
            return;
        }
        
        $submission_id = $wpdb->insert_id;
        
        // Initialize response data with basic info
        $response_data = array(
            'message' => 'Form submitted successfully!',
            'submission_id' => $submission_id,
            'roi' => 'N/A',
            'annual_savings' => 'N/A',
            'payback_months' => 'N/A',
            'safe_range' => true,
            'hubspot_sent' => false,
            'pdf_generated' => false
        );
        
        // Generate PDF and calculate ROI if class is available
        if (class_exists('VOI_PDF_Generator')) {
            error_log('VOI Calculator: PDF Generator class exists, creating instance');
            try {
                $pdf_generator = new VOI_PDF_Generator($storage_tb, $vm_count, $company_name);
                $calculations = $pdf_generator->get_calculations();
                $is_safe_range = $pdf_generator->is_safe_range();
                
                error_log('VOI Calculator: Calculations completed - ROI: ' . $calculations['totals']['annual_roi'] . '%');
                
                // Save PDF (in production, save to uploads directory)
                $pdf_html = $pdf_generator->generate_pdf();
                $pdf_saved = $this->save_pdf($submission_id, $pdf_html);
                
                error_log('VOI Calculator: PDF saved: ' . ($pdf_saved ? 'Success' : 'Failed'));
                
                // Update response data with calculations
                $response_data['roi'] = number_format($calculations['totals']['annual_roi'], 0);
                $response_data['annual_savings'] = number_format($calculations['totals']['total_annual_savings']);
                $response_data['payback_months'] = number_format($calculations['totals']['payback_months'], 1);
                $response_data['safe_range'] = $is_safe_range;
                $response_data['pdf_generated'] = $pdf_saved;
                
                // Send to HubSpot if class is available
                if (class_exists('VOI_HubSpot_Integration')) {
                    error_log('VOI Calculator: HubSpot Integration class exists, creating contact');
                    try {
                        $hubspot = new VOI_HubSpot_Integration();
                        $submission_data = array(
                            'storage_tb' => $storage_tb,
                            'vm_count' => $vm_count,
                            'first_name' => $first_name,
                            'last_name' => $last_name,
                            'email' => $email,
                            'company_name' => $company_name,
                            'company_url' => $company_url
                        );
                        
                        $hubspot_result = $hubspot->create_contact($submission_data, $calculations);
                        $hubspot_sent = $hubspot_result !== false;
                        $response_data['hubspot_sent'] = $hubspot_sent;
                        
                        error_log('VOI Calculator: HubSpot contact creation: ' . ($hubspot_sent ? 'Success' : 'Failed'));
                        
                        // Create deal for high-value prospects
                        if ($hubspot_result && $calculations['totals']['annual_roi'] > 200) {
                            $deal_result = $hubspot->create_deal($hubspot_result['id'], $submission_data, $calculations);
                            error_log('VOI Calculator: HubSpot deal creation: ' . ($deal_result ? 'Success' : 'Failed'));
                        }
                        
                        // Notify sales team
                        if ($hubspot_sent) {
                            $notification_result = $hubspot->notify_sales_team($submission_data, $calculations, $is_safe_range);
                            error_log('VOI Calculator: Sales team notification: ' . ($notification_result ? 'Success' : 'Failed'));
                        }
                    } catch (Exception $e) {
                        error_log('VOI Calculator: HubSpot error: ' . $e->getMessage());
                        $response_data['hubspot_error'] = $e->getMessage();
                    }
                } else {
                    error_log('VOI Calculator: HubSpot Integration class not found');
                }
                
                // Update database with status
                $wpdb->update(
                    $table_name,
                    array(
                        'hubspot_sent' => $response_data['hubspot_sent'] ? 1 : 0,
                        'pdf_generated' => $response_data['pdf_generated'] ? 1 : 0,
                        'calculated_roi' => $calculations['totals']['annual_roi'],
                        'annual_savings' => $calculations['totals']['total_annual_savings'],
                        'is_safe_range' => $is_safe_range ? 1 : 0
                    ),
                    array('id' => $submission_id),
                    array('%d', '%d', '%f', '%f', '%d'),
                    array('%d')
                );
                
            } catch (Exception $e) {
                error_log('VOI Calculator: PDF Generator error: ' . $e->getMessage());
                $response_data['pdf_error'] = $e->getMessage();
            }
        } else {
            error_log('VOI Calculator: PDF Generator class not found');
        }
        
        wp_send_json_success($response_data);
    }
    
    private function save_pdf($submission_id, $pdf_html) {
        // In production, you'd use a library like DOMPDF or wkhtmltopdf
        // For now, we'll save the HTML version
        $upload_dir = wp_upload_dir();
        $voi_dir = $upload_dir['basedir'] . '/voi-calculator/';
        
        if (!file_exists($voi_dir)) {
            wp_mkdir_p($voi_dir);
        }
        
        $filename = 'voi-report-' . $submission_id . '.html';
        $filepath = $voi_dir . $filename;
        
        $saved = file_put_contents($filepath, $pdf_html);
        
        if ($saved !== false) {
            // Save file reference to database for later retrieval
            global $wpdb;
            $table_name = $wpdb->prefix . 'voi_calculator_submissions';
            $wpdb->update(
                $table_name,
                array('pdf_file_path' => $filename),
                array('id' => $submission_id),
                array('%s'),
                array('%d')
            );
            return true;
        }
        
        return false;
    }
    
    public function test_pdf_generation() {
        if (!class_exists('VOI_PDF_Generator')) {
            wp_send_json_error('PDF Generator class not found');
            return;
        }
        
        try {
            $pdf_generator = new VOI_PDF_Generator(10, 300, 'Test Company');
            $calculations = $pdf_generator->get_calculations();
            $html = $pdf_generator->generate_pdf();
            
            wp_send_json_success(array(
                'message' => 'PDF generation successful',
                'roi' => $calculations['totals']['annual_roi'],
                'annual_savings' => $calculations['totals']['total_annual_savings'],
                'html_length' => strlen($html)
            ));
        } catch (Exception $e) {
            wp_send_json_error('PDF generation failed: ' . $e->getMessage());
        }
    }
    
    public function test_hubspot_connection() {
        if (!class_exists('VOI_HubSpot_Integration')) {
            wp_send_json_error('HubSpot Integration class not found');
            return;
        }
        
        try {
            $hubspot = new VOI_HubSpot_Integration();
            $test_result = $hubspot->test_connection();
            
            if ($test_result['success']) {
                wp_send_json_success($test_result);
            } else {
                wp_send_json_error($test_result);
            }
        } catch (Exception $e) {
            wp_send_json_error('HubSpot test failed: ' . $e->getMessage());
        }
    }
    }

// Initialize the plugin
new VOI_Calculator();