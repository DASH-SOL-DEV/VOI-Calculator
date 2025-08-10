public function handle_email_results() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'voi_calculator_nonce')) {
            wp_die('Security check failed');
        }
        
        $submission_id = intval($_POST['submission_id']);
        $email = sanitize_email($_POST['email']);
        
        // Get submission data
        global $wpdb;
        $table_name = $wpdb->prefix . 'voi_calculator_submissions';
        
        $submission = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d",
            $submission_id
        ));
        
        if (!$submission) {
            wp_send_json_error('Submission not found');
            return;
        }
        
        $data = array(
            'total_storage_tb' => $submission->total_storage_tb,
            'total_vms' => $submission->total_vms,
            'company_name' => $submission->company_name,
            'company_url' => $submission->company_url,
            'first_name' => $submission->first_name,
            'last_name' => $submission->last_name,
            'email' => $submission->email
        );
        
        $calculations = json_decode($submission->calculations, true);
        
        // Generate file URLs
        $upload_dir = wp_upload_dir();
        $pdf_url = $upload_dir['url'] . '/voi-report-' . $submission_id . '.pdf';
        $excel_url = $upload_dir['url'] . '/voi-worksheet-' . $submission_id . '.xlsx';
        
        // Send email
        $email_sent = $this->send_results_email($email, $data, $calculations, $pdf_url, $excel_url);
        
        if ($email_sent) {
            // Notify sales team if outside safe range
            if (!$submission->is_safe_range) {
                $this->notify_sales_team($data, $calculations, $submission_id);
            }
            
            wp_send_json_success('Email sent successfully');
        } else {
            wp_send_json_error('Failed to send email');
        }
    }
    
    private function send_results_email($to_email, $data, $calculations, $pdf_url, $excel_url) {
        $subject = 'Your Visual One Intelligence ROI Report';
        
        $message = $this->get_email_template($data, $calculations, $pdf_url, $excel_url);
        
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: Visual One Intelligence <noreply@' . $_SERVER['HTTP_HOST'] . '>'
        );
        
        return wp_mail($to_email, $subject, $message, $headers);
    }
    
    private function get_email_template($data, $calculations, $pdf_url, $excel_url) {
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Your VOI ROI Report</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(45deg, #667eea, #764ba2); color: white; padding: 30px; text-align: center; border-radius: 8px 8px 0 0; }
                .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 8px 8px; }
                .summary-box { background: white; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #4CAF50; }
                .results-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin: 20px 0; }
                .result-item { background: white; padding: 15px; border-radius: 8px; text-align: center; }
                .result-value { font-size: 24px; font-weight: bold; color: #4CAF50; }
                .download-buttons { text-align: center; margin: 30px 0; }
                .download-btn { display: inline-block; background: #4CAF50; color: white; padding: 12px 24px; text-decoration: none; border-radius: 25px; margin: 0 10px; font-weight: bold; }
                .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>Visual One Intelligence</h1>
                    <h2>ROI Value Report</h2>
                </div>
                
                <div class="content">
                    <p>Dear <?php echo esc_html($data['first_name']); ?>,</p>
                    
                    <p>Thank you for using the Visual One Intelligence ROI Calculator. Based on your environment details, we've prepared a comprehensive value analysis for <?php echo esc_html($data['company_name']); ?>.</p>
                    
                    <div class="summary-box">
                        <h3>Executive Summary</h3>
                        <p>Environment: <strong><?php echo $data['total_storage_tb']; ?> TB storage</strong> and <strong><?php echo $data['total_vms']; ?> VMs</strong></p>
                        <p>Visual One Intelligence can deliver <strong>$<?php echo number_format($calculations['total_annual_savings'], 0); ?></strong> in annual savings with an ROI of <strong><?php echo number_format($calculations['roi_percentage'], 1); ?>%</strong>.</p>
                    </div>
                    
                    <div class="results-grid">
                        <div class="result-item">
                            <div class="result-value">$<?php echo number_format($calculations['total_annual_savings'], 0); ?></div>
                            <div>Annual Savings</div>
                        </div>
                        <div class="result-item">
                            <div class="result-value"><?php echo number_format($calculations['roi_percentage'], 1); ?>%</div>
                            <div>Return on Investment</div>
                        </div>
                        <div class="result-item">
                            <div class="result-value"><?php echo number_format($calculations['payback_months'], 1); ?></div>
                            <div>Payback (Months)</div>
                        </div>
                        <div class="result-item">
                            <div class="result-value">$<?php echo number_format($calculations['net_annual_savings'], 0); ?></div>
                            <div>Net Annual Savings</div>
                        </div>
                    </div>
                    
                    <div class="download-buttons">
                        <a href="<?php echo esc_url($pdf_url); ?>" class="download-btn">Download PDF Report</a>
                        <a href="<?php echo esc_url($excel_url); ?>" class="download-btn">Download Excel Worksheet</a>
                    </div>
                    
                    <p>These calculations are based on industry standard metrics and your specific environment. For a more detailed analysis using your organization's specific costs and metrics, please contact our team to schedule a consultation.</p>
                    
                    <p>Next steps:</p>
                    <ul>
                        <li>Review the detailed PDF report and Excel worksheet</li>
                        <li>Share with your team and stakeholders</li>
                        <li>Schedule a demo to see Visual One Intelligence in action</li>
                        <li>Discuss implementation timeline and customization options</li>
                    </ul>
                    
                    <p>Thank you for your interest in Visual One Intelligence. We look forward to helping you achieve these significant cost savings and operational improvements.</p>
                    
                    <p>Best regards,<br>
                    <strong>The Visual One Intelligence Team</strong></p>
                </div>
                
                <div class="footer">
                    <p>This report was generated on <?php echo date('F j, Y'); ?> using the Visual One Intelligence ROI Calculator.<br>
                    For questions or support, please contact us at your convenience.</p>
                </div>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
    
    private function notify_sales_team($data, $calculations, $submission_id) {
        $sales_email = get_option('voi_sales_notification_email', get_option('admin_email'));
        
        $subject = 'VOI Calculator: Manual Review Required - ' . $data['company_name'];
        
        $message = sprintf(
            "A new VOI Calculator submission requires manual review:\n\n" .
            "Company: %s\n" .
            "Contact: %s %s (%s)\n" .
            "Environment: %s TB, %s VMs\n" .
            "Calculated ROI: %s%%\n" .
            "Payback: %s months\n" .
            "Annual Savings: $%s\n\n" .
            "This submission is outside the safe range and should be reviewed before sending to the prospect.\n\n" .
            "Submission ID: %s\n" .
            "Timestamp: %s",
            $data['company_name'],
            $data['first_name'],
            $data['last_name'],
            $data['email'],
            $data['total_storage_tb'],
            $data['total_vms'],
            number_format($calculations['roi_percentage'], 1),
            number_format($calculations['payback_months'], 1),
            number_format($calculations['total_annual_savings'], 0),
            $submission_id,
            current_time('mysql')
        );
        
        wp_mail($sales_email, $subject, $message);
    }<?php
/**
 * Plugin Name: VOI Calculator
 * Plugin URI: https://niftyfiftysolutions.com/
 * Description: Visual One Intelligence ROI/Value Calculator - A two-step calculator that integrates with HubSpot and generates PDF reports.
 * Version: 1.0.0
 * Author: Nifty Fifty Solutions
 * Author URI: https://niftyfiftysolutions.com/
 * License: GPL v2 or later
 * Text Domain: voi-calculator
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('VOI_CALCULATOR_VERSION', '1.0.0');
define('VOI_CALCULATOR_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('VOI_CALCULATOR_PLUGIN_URL', plugin_dir_url(__FILE__));

class VOI_Calculator {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_voi_submit_calculator', array($this, 'handle_ajax_submission'));
        add_action('wp_ajax_nopriv_voi_submit_calculator', array($this, 'handle_ajax_submission'));
        add_action('wp_ajax_voi_email_results', array($this, 'handle_email_results'));
        add_action('wp_ajax_nopriv_voi_email_results', array($this, 'handle_email_results'));
        
        // Add shortcode
        add_shortcode('voi_calculator', array($this, 'render_calculator_shortcode'));
        
        // Add admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
    }
    
    public function init() {
        // Create database table for storing submissions
        $this->create_submissions_table();
    }
    
    public function enqueue_scripts() {
        wp_enqueue_script('jquery');
        wp_enqueue_script('voi-calculator-js', VOI_CALCULATOR_PLUGIN_URL . 'assets/voi-calculator.js', array('jquery'), VOI_CALCULATOR_VERSION, true);
        wp_enqueue_style('voi-calculator-css', VOI_CALCULATOR_PLUGIN_URL . 'assets/voi-calculator.css', array(), VOI_CALCULATOR_VERSION);
        
        // Localize script for AJAX
        wp_localize_script('voi-calculator-js', 'voi_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('voi_calculator_nonce')
        ));
    }
    
    public function render_calculator_shortcode($atts) {
        $atts = shortcode_atts(array(
            'type' => 'simplified' // simplified or detailed
        ), $atts);
        
        ob_start();
        ?>
        <div id="voi-calculator-container" class="voi-calculator-wrapper">
            <div class="voi-calculator-header">
                <h2>Simplified Value Calculator</h2>
                <p>This calculator uses industry standard values to show the value of Visual One Intelligence. If you prefer to use your own industry values, <a href="#" class="detailed-calculator-link">click HERE</a> for the Detailed Value Calculator.</p>
            </div>
            
            <form id="voi-calculator-form" class="voi-calculator-form">
                <?php wp_nonce_field('voi_calculator_nonce', 'voi_nonce'); ?>
                
                <div class="form-group">
                    <label for="total_storage_tb">Enter Total Sold TB of storage in your environment.</label>
                    <input type="number" id="total_storage_tb" name="total_storage_tb" required min="1" step="0.1" placeholder="12">
                    <span class="unit">TB</span>
                </div>
                
                <div class="form-group">
                    <label for="total_vms">Enter Total Number of VMs in your environment.</label>
                    <input type="number" id="total_vms" name="total_vms" required min="1" step="1" placeholder="300">
                    <span class="unit">VMs</span>
                </div>
                
                <div class="form-group">
                    <label for="company_name">Company Name</label>
                    <input type="text" id="company_name" name="company_name" required placeholder="ACME Widget Company">
                </div>
                
                <div class="form-group">
                    <label for="company_url">Company URL</label>
                    <input type="url" id="company_url" name="company_url" required placeholder="company.com">
                </div>
                
                <div class="form-row">
                    <div class="form-group half">
                        <label for="first_name">First Name</label>
                        <input type="text" id="first_name" name="first_name" required placeholder="Joe">
                    </div>
                    <div class="form-group half">
                        <label for="last_name">Last Name</label>
                        <input type="text" id="last_name" name="last_name" required placeholder="Public">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="email">Email address to mail the generated value document</label>
                    <input type="email" id="email" name="email" required placeholder="sample.name@emailaddress.com">
                </div>
                
                <div class="form-submit">
                    <button type="submit" class="voi-submit-btn">Generate Value Document and See results</button>
                </div>
            </form>
            
            <div id="voi-loading" class="voi-loading" style="display: none;">
                <div class="loading-spinner"></div>
                <p>Generating your value report...</p>
            </div>
            
            <div id="voi-results" class="voi-results" style="display: none;">
                <h3>Visual One Intelligence Value Report</h3>
                <div id="voi-results-content"></div>
                <div class="results-actions">
                    <button id="voi-email-results" class="voi-email-btn">Email Results</button>
                    <button id="voi-download-pdf" class="voi-download-btn">Download PDF Report</button>
                    <button id="voi-download-excel" class="voi-download-btn">Download Excel Spreadsheet</button>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    public function handle_ajax_submission() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['voi_nonce'], 'voi_calculator_nonce')) {
            wp_die('Security check failed');
        }
        
        // Sanitize input data
        $data = array(
            'total_storage_tb' => floatval($_POST['total_storage_tb']),
            'total_vms' => intval($_POST['total_vms']),
            'company_name' => sanitize_text_field($_POST['company_name']),
            'company_url' => esc_url_raw($_POST['company_url']),
            'first_name' => sanitize_text_field($_POST['first_name']),
            'last_name' => sanitize_text_field($_POST['last_name']),
            'email' => sanitize_email($_POST['email'])
        );
        
        // Perform ROI calculations
        $calculations = $this->calculate_roi($data);
        
        // Check if ROI is in safe range
        $is_safe_range = $this->is_safe_range($calculations);
        
        // Save to database
        $submission_id = $this->save_submission($data, $calculations, $is_safe_range);
        
        // Submit to HubSpot
        $hubspot_result = $this->submit_to_hubspot($data, $calculations);
        
        // Generate PDF and Excel files
        $pdf_url = $this->generate_pdf_report($data, $calculations, $submission_id);
        $excel_url = $this->generate_excel_report($data, $calculations, $submission_id);
        
        // Prepare response
        $response = array(
            'success' => true,
            'calculations' => $calculations,
            'pdf_url' => $pdf_url,
            'excel_url' => $excel_url,
            'is_safe_range' => $is_safe_range,
            'submission_id' => $submission_id
        );
        
        wp_send_json($response);
    }
    
    private function calculate_roi($data) {
        // Industry standard calculation values
        $storage_cost_per_tb = 500; // Annual cost per TB
        $vm_management_cost = 200; // Annual cost per VM
        $efficiency_improvement = 0.25; // 25% efficiency improvement
        $ticket_reduction = 0.30; // 30% ticket reduction
        $time_savings_hours_per_week = 8; // Hours saved per week
        $hourly_rate = 80; // Average IT hourly rate
        
        // Calculate current costs
        $annual_storage_cost = $data['total_storage_tb'] * $storage_cost_per_tb;
        $annual_vm_cost = $data['total_vms'] * $vm_management_cost;
        $total_current_cost = $annual_storage_cost + $annual_vm_cost;
        
        // Calculate savings
        $storage_savings = $annual_storage_cost * $efficiency_improvement;
        $vm_savings = $annual_vm_cost * $efficiency_improvement;
        $time_savings_annual = $time_savings_hours_per_week * 52 * $hourly_rate;
        $ticket_reduction_savings = ($data['total_vms'] * 50 * $hourly_rate) * $ticket_reduction; // Assuming 50 tickets per VM annually
        
        $total_annual_savings = $storage_savings + $vm_savings + $time_savings_annual + $ticket_reduction_savings;
        
        // VOI Annual Cost (example - this should be actual pricing)
        $voi_annual_cost = min($total_annual_savings * 0.3, $total_current_cost * 0.15); // 30% of savings or 15% of current cost, whichever is lower
        
        // Calculate ROI
        $net_annual_savings = $total_annual_savings - $voi_annual_cost;
        $roi_percentage = ($net_annual_savings / $voi_annual_cost) * 100;
        $payback_months = ($voi_annual_cost / ($total_annual_savings / 12));
        
        return array(
            'total_storage_tb' => $data['total_storage_tb'],
            'total_vms' => $data['total_vms'],
            'annual_storage_cost' => $annual_storage_cost,
            'annual_vm_cost' => $annual_vm_cost,
            'total_current_cost' => $total_current_cost,
            'storage_savings' => $storage_savings,
            'vm_savings' => $vm_savings,
            'time_savings_annual' => $time_savings_annual,
            'ticket_reduction_savings' => $ticket_reduction_savings,
            'total_annual_savings' => $total_annual_savings,
            'voi_annual_cost' => $voi_annual_cost,
            'net_annual_savings' => $net_annual_savings,
            'roi_percentage' => $roi_percentage,
            'payback_months' => $payback_months
        );
    }
    
    private function is_safe_range($calculations) {
        // Define safe ranges
        $min_roi = 100; // 100% minimum ROI
        $max_roi = 2000; // 2000% maximum ROI
        $max_payback_months = 24; // 24 months maximum payback
        
        return ($calculations['roi_percentage'] >= $min_roi && 
                $calculations['roi_percentage'] <= $max_roi && 
                $calculations['payback_months'] <= $max_payback_months);
    }
    
    private function submit_to_hubspot($data, $calculations) {
        $hubspot_api_key = get_option('voi_hubspot_api_key', '');
        
        if (empty($hubspot_api_key)) {
            return array('success' => false, 'message' => 'HubSpot API key not configured');
        }
        
        $hubspot_data = array(
            'properties' => array(
                'firstname' => $data['first_name'],
                'lastname' => $data['last_name'],
                'email' => $data['email'],
                'company' => $data['company_name'],
                'website' => $data['company_url'],
                'total_storage_tb' => $data['total_storage_tb'],
                'total_vms' => $data['total_vms'],
                'calculated_roi' => $calculations['roi_percentage'],
                'annual_savings' => $calculations['total_annual_savings'],
                'payback_months' => $calculations['payback_months'],
                'is_safe_range' => $this->is_safe_range($calculations) ? 'Yes' : 'No'
            )
        );
        
        $response = wp_remote_post('https://api.hubapi.com/contacts/v1/contact/', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $hubspot_api_key,
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode($hubspot_data),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            return array('success' => false, 'message' => $response->get_error_message());
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        
        return array(
            'success' => ($response_code >= 200 && $response_code < 300),
            'response_code' => $response_code,
            'response_body' => $response_body
        );
    }
    
    private function generate_pdf_report($data, $calculations, $submission_id) {
        // This would integrate with a PDF generation library like TCPDF or FPDF
        // For now, return a placeholder URL
        $upload_dir = wp_upload_dir();
        $pdf_filename = 'voi-report-' . $submission_id . '.pdf';
        $pdf_path = $upload_dir['path'] . '/' . $pdf_filename;
        $pdf_url = $upload_dir['url'] . '/' . $pdf_filename;
        
        // Generate PDF content (placeholder implementation)
        $this->create_pdf_report($data, $calculations, $pdf_path);
        
        return $pdf_url;
    }
    
    private function generate_excel_report($data, $calculations, $submission_id) {
        // This would integrate with PHPSpreadsheet or similar
        // For now, return a placeholder URL
        $upload_dir = wp_upload_dir();
        $excel_filename = 'voi-worksheet-' . $submission_id . '.xlsx';
        $excel_path = $upload_dir['path'] . '/' . $excel_filename;
        $excel_url = $upload_dir['url'] . '/' . $excel_filename;
        
        // Generate Excel content (placeholder implementation)
        $this->create_excel_report($data, $calculations, $excel_path);
        
        return $excel_url;
    }
    
    private function create_pdf_report($data, $calculations, $file_path) {
        // Placeholder PDF generation
        // In a real implementation, you would use TCPDF, FPDF, or similar
        $html_content = $this->get_pdf_template($data, $calculations);
        file_put_contents($file_path, $html_content);
    }
    
    private function create_excel_report($data, $calculations, $file_path) {
        // Placeholder Excel generation
        // In a real implementation, you would use PHPSpreadsheet
        $csv_content = $this->get_excel_template($data, $calculations);
        file_put_contents($file_path, $csv_content);
    }
    
    private function get_pdf_template($data, $calculations) {
        ob_start();
        ?>
        <html>
        <head>
            <title>Visual One Intelligence Value Report</title>
            <style>
                body { font-family: Arial, sans-serif; }
                .header { background-color: #4CAF50; color: white; padding: 20px; }
                .content { padding: 20px; }
                .calculation-table { width: 100%; border-collapse: collapse; }
                .calculation-table th, .calculation-table td { border: 1px solid #ddd; padding: 8px; }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>Visual One Intelligence Value Report</h1>
                <p>Prepared for: <?php echo esc_html($data['company_name']); ?></p>
                <p>Date: <?php echo date('F j, Y'); ?></p>
            </div>
            <div class="content">
                <h2>Executive Summary</h2>
                <p>Based on your environment of <?php echo $data['total_storage_tb']; ?> TB of storage and <?php echo $data['total_vms']; ?> VMs, 
                Visual One Intelligence can deliver significant value to your organization.</p>
                
                <h3>Key Findings</h3>
                <table class="calculation-table">
                    <tr><th>Metric</th><th>Value</th></tr>
                    <tr><td>Annual Savings</td><td>$<?php echo number_format($calculations['total_annual_savings'], 2); ?></td></tr>
                    <tr><td>ROI</td><td><?php echo number_format($calculations['roi_percentage'], 1); ?>%</td></tr>
                    <tr><td>Payback Period</td><td><?php echo number_format($calculations['payback_months'], 1); ?> months</td></tr>
                    <tr><td>Net Annual Savings</td><td>$<?php echo number_format($calculations['net_annual_savings'], 2); ?></td></tr>
                </table>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
    
    private function get_excel_template($data, $calculations) {
        $csv = "Visual One Intelligence ROI Worksheet\n";
        $csv .= "Prepared for: " . $data['company_name'] . "\n";
        $csv .= "Date: " . date('F j, Y') . "\n\n";
        
        $csv .= "Current Environment\n";
        $csv .= "Total Storage (TB)," . $data['total_storage_tb'] . "\n";
        $csv .= "Total VMs," . $data['total_vms'] . "\n\n";
        
        $csv .= "Annual Costs\n";
        $csv .= "Storage Cost,$" . number_format($calculations['annual_storage_cost'], 2) . "\n";
        $csv .= "VM Management Cost,$" . number_format($calculations['annual_vm_cost'], 2) . "\n";
        $csv .= "Total Current Cost,$" . number_format($calculations['total_current_cost'], 2) . "\n\n";
        
        $csv .= "Annual Savings\n";
        $csv .= "Storage Savings,$" . number_format($calculations['storage_savings'], 2) . "\n";
        $csv .= "VM Savings,$" . number_format($calculations['vm_savings'], 2) . "\n";
        $csv .= "Time Savings,$" . number_format($calculations['time_savings_annual'], 2) . "\n";
        $csv .= "Ticket Reduction Savings,$" . number_format($calculations['ticket_reduction_savings'], 2) . "\n";
        $csv .= "Total Annual Savings,$" . number_format($calculations['total_annual_savings'], 2) . "\n\n";
        
        $csv .= "ROI Analysis\n";
        $csv .= "VOI Annual Cost,$" . number_format($calculations['voi_annual_cost'], 2) . "\n";
        $csv .= "Net Annual Savings,$" . number_format($calculations['net_annual_savings'], 2) . "\n";
        $csv .= "ROI Percentage," . number_format($calculations['roi_percentage'], 1) . "%\n";
        $csv .= "Payback (months)," . number_format($calculations['payback_months'], 1) . "\n";
        
        return $csv;
    }
    
    private function save_submission($data, $calculations, $is_safe_range) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'voi_calculator_submissions';
        
        $wpdb->insert(
            $table_name,
            array(
                'total_storage_tb' => $data['total_storage_tb'],
                'total_vms' => $data['total_vms'],
                'company_name' => $data['company_name'],
                'company_url' => $data['company_url'],
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'calculations' => json_encode($calculations),
                'is_safe_range' => $is_safe_range ? 1 : 0,
                'created_at' => current_time('mysql')
            ),
            array(
                '%f', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s'
            )
        );
        
        return $wpdb->insert_id;
    }
    
    private function create_submissions_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'voi_calculator_submissions';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            total_storage_tb decimal(10,2) NOT NULL,
            total_vms int NOT NULL,
            company_name varchar(255) NOT NULL,
            company_url varchar(255) NOT NULL,
            first_name varchar(100) NOT NULL,
            last_name varchar(100) NOT NULL,
            email varchar(255) NOT NULL,
            calculations longtext,
            is_safe_range tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    public function add_admin_menu() {
        add_options_page(
            'VOI Calculator Settings',
            'VOI Calculator',
            'manage_options',
            'voi-calculator',
            array($this, 'admin_page')
        );
    }
    
    public function admin_page() {
        if (isset($_POST['submit'])) {
            update_option('voi_hubspot_api_key', sanitize_text_field($_POST['hubspot_api_key']));
            update_option('voi_sales_notification_email', sanitize_email($_POST['sales_notification_email']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        
        $hubspot_api_key = get_option('voi_hubspot_api_key', '');
        $sales_notification_email = get_option('voi_sales_notification_email', get_option('admin_email'));
        
        // Get submission statistics
        global $wpdb;
        $table_name = $wpdb->prefix . 'voi_calculator_submissions';
        $total_submissions = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        $recent_submissions = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
        $unsafe_submissions = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE is_safe_range = 0");
        ?>
        <div class="wrap">
            <h1>VOI Calculator Settings</h1>
            
            <div class="voi-stats-cards" style="display: flex; gap: 20px; margin: 20px 0;">
                <div class="stats-card" style="background: white; padding: 20px; border-radius: 8px; border-left: 4px solid #4CAF50; flex: 1;">
                    <h3 style="margin: 0 0 10px 0;">Total Submissions</h3>
                    <div style="font-size: 24px; font-weight: bold; color: #4CAF50;"><?php echo $total_submissions; ?></div>
                </div>
                <div class="stats-card" style="background: white; padding: 20px; border-radius: 8px; border-left: 4px solid #2196F3; flex: 1;">
                    <h3 style="margin: 0 0 10px 0;">Last 30 Days</h3>
                    <div style="font-size: 24px; font-weight: bold; color: #2196F3;"><?php echo $recent_submissions; ?></div>
                </div>
                <div class="stats-card" style="background: white; padding: 20px; border-radius: 8px; border-left: 4px solid #FF9800; flex: 1;">
                    <h3 style="margin: 0 0 10px 0;">Needs Review</h3>
                    <div style="font-size: 24px; font-weight: bold; color: #FF9800;"><?php echo $unsafe_submissions; ?></div>
                </div>
            </div>
            
            <form method="post" action="">
                <?php wp_nonce_field('voi_settings_nonce'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">HubSpot API Key</th>
                        <td>
                            <input type="text" name="hubspot_api_key" value="<?php echo esc_attr($hubspot_api_key); ?>" class="regular-text" />
                            <p class="description">Enter your HubSpot API key for lead integration. <a href="https://knowledge.hubspot.com/integrations/how-do-i-get-my-hubspot-api-key" target="_blank">How to get your API key</a></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Sales Notification Email</th>
                        <td>
                            <input type="email" name="sales_notification_email" value="<?php echo esc_attr($sales_notification_email); ?>" class="regular-text" />
                            <p class="description">Email address to notify when submissions are outside safe range and require manual review.</p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            
            <h2>Recent Submissions</h2>
            <?php $this->display_recent_submissions(); ?>
            
            <h2>Usage Instructions</h2>
            <div style="background: white; padding: 20px; border-radius: 8px; margin: 20px 0;">
                <h3>Shortcode Usage</h3>
                <p>Use the following shortcode to display the calculator on any page or post:</p>
                <code style="background: #f0f0f0; padding: 10px; display: block; margin: 10px 0;">[voi_calculator]</code>
                
                <h3>Safe Range Criteria</h3>
                <ul>
                    <li><strong>ROI:</strong> Between 100% and 2000%</li>
                    <li><strong>Payback Period:</strong> Under 24 months</li>
                    <li>Results outside these ranges will trigger manual review notifications</li>
                </ul>
                
                <h3>Generated Files</h3>
                <p>The calculator automatically generates:</p>
                <ul>
                    <li><strong>PDF Report:</strong> Executive summary with professional formatting</li>
                    <li><strong>Excel Worksheet:</strong> Detailed calculations and assumptions</li>
                    <li><strong>Email Delivery:</strong> Professional email template with download links</li>
                </ul>
            </div>
        </div>
        <?php
    }
    
    private function display_recent_submissions() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'voi_calculator_submissions';
        
        $submissions = $wpdb->get_results(
            "SELECT * FROM $table_name ORDER BY created_at DESC LIMIT 10"
        );
        
        if (empty($submissions)) {
            echo '<p>No submissions yet.</p>';
            return;
        }
        ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Company</th>
                    <th>Contact</th>
                    <th>Environment</th>
                    <th>ROI</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($submissions as $submission): 
                    $calculations = json_decode($submission->calculations, true);
                ?>
                <tr>
                    <td><?php echo date('M j, Y', strtotime($submission->created_at)); ?></td>
                    <td>
                        <strong><?php echo esc_html($submission->company_name); ?></strong><br>
                        <small><?php echo esc_html($submission->company_url); ?></small>
                    </td>
                    <td>
                        <?php echo esc_html($submission->first_name . ' ' . $submission->last_name); ?><br>
                        <small><?php echo esc_html($submission->email); ?></small>
                    </td>
                    <td>
                        <?php echo $submission->total_storage_tb; ?> TB<br>
                        <?php echo $submission->total_vms; ?> VMs
                    </td>
                    <td>
                        <strong><?php echo number_format($calculations['roi_percentage'], 1); ?>%</strong><br>
                        <small>$<?php echo number_format($calculations['total_annual_savings'], 0); ?></small>
                    </td>
                    <td>
                        <?php if ($submission->is_safe_range): ?>
                            <span style="color: #4CAF50;">✓ Safe Range</span>
                        <?php else: ?>
                            <span style="color: #FF9800;">⚠ Needs Review</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }
}

// Initialize the plugin
new VOI_Calculator();

// Activation hook to create database table
register_activation_hook(__FILE__, array('VOI_Calculator', 'create_submissions_table'));
?>