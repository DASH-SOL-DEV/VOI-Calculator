<?php
/**
 * Email System Class for VOI Calculator
 * 
 * Save this as: includes/class-voi-email-system.php
 */

class VOI_Email_System {
    
    public function __construct() {
        add_action('init', [$this, 'init']);
    }
    
    public function init() {
        // Hook into form submission to send email
        add_action('voi_form_submitted', [$this, 'send_submission_email'], 10, 3);
    }
    
    /**
     * Send email to user after form submission
     * 
     * @param array $form_data
     * @param string $generated_pdf_path
     * @param int $submission_id
     */
    public function send_submission_email($form_data, $generated_pdf_path, $submission_id) {
        // Check if email sending is enabled
        if (!get_option('voi_email_enabled', true)) {
            return;
        }
        
        $to = $form_data['email'];
        $subject = $this->get_email_subject($form_data);
        $message = $this->get_email_message($form_data);
        $headers = $this->get_email_headers();
        $attachments = $this->get_email_attachments($generated_pdf_path);
        
        // Log email attempt
        error_log('VOI Email - Sending to: ' . $to);
        error_log('VOI Email - Attachments: ' . print_r($attachments, true));
        
        $sent = wp_mail($to, $subject, $message, $headers, $attachments);
        
        if ($sent) {
            error_log('VOI Email - Successfully sent to: ' . $to);
            $this->update_submission_email_status($submission_id, true);
        } else {
            error_log('VOI Email - Failed to send to: ' . $to);
            $this->update_submission_email_status($submission_id, false);
        }
        
        return $sent;
    }
    
    /**
     * Get email subject with placeholder replacement
     * 
     * @param array $form_data
     * @return string
     */
    private function get_email_subject($form_data) {
        $subject_template = get_option('voi_email_subject', 'Your Visual One Intelligence ROI Analysis');
        
        return $this->replace_placeholders($subject_template, $form_data);
    }
    
    /**
     * Get email message with placeholder replacement
     * 
     * @param array $form_data
     * @return string
     */
    private function get_email_message($form_data) {
        $message_template = get_option('voi_email_template', $this->get_default_email_template());
        
        return $this->replace_placeholders($message_template, $form_data);
    }
    
    /**
     * Replace placeholders in email templates
     * 
     * @param string $template
     * @param array $form_data
     * @return string
     */
    private function replace_placeholders($template, $form_data) {
        $first_name = explode(' ', $form_data['full_name'])[0];
        
        $placeholders = [
            '{full_name}' => $form_data['full_name'],
            '{first_name}' => $first_name,
            '{company_name}' => $form_data['company_name'],
            '{company_url}' => $form_data['company_url'],
            '{total_tb}' => number_format($form_data['total_tb']),
            '{total_vms}' => number_format($form_data['total_vms']),
            '{email}' => $form_data['email'],
            '{date}' => date('F j, Y'),
            '{time}' => date('g:i A'),
            '{site_name}' => get_bloginfo('name'),
            '{site_url}' => home_url()
        ];
        
        return str_replace(array_keys($placeholders), array_values($placeholders), $template);
    }
    
    /**
     * Get email headers
     * 
     * @return array
     */
    private function get_email_headers() {
        $from_name = get_option('voi_email_from_name', get_bloginfo('name'));
        $from_email = get_option('voi_email_from_address', get_option('admin_email'));
        
        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $from_name . ' <' . $from_email . '>'
        ];
        
        // Add reply-to if different
        $reply_to = get_option('voi_email_reply_to', '');
        if (!empty($reply_to)) {
            $headers[] = 'Reply-To: ' . $reply_to;
        }
        
        return $headers;
    }
    
    /**
     * Get email attachments
     * 
     * @param string $generated_pdf_path
     * @return array
     */
    private function get_email_attachments($generated_pdf_path) {
        $attachments = [];
        
        // Add generated PDF
        if (file_exists($generated_pdf_path)) {
            $attachments[] = $generated_pdf_path;
        }
        
        // Add uploaded additional PDF
        $additional_pdf = get_option('voi_email_additional_pdf', '');
        if (!empty($additional_pdf)) {
            $upload_dir = wp_upload_dir();
            $pdf_path = $upload_dir['basedir'] . '/voi-email-attachments/' . $additional_pdf;
            
            if (file_exists($pdf_path)) {
                $attachments[] = $pdf_path;
            }
        }
        
        return $attachments;
    }
    
    /**
     * Update submission with email status
     * 
     * @param int $submission_id
     * @param bool $sent
     */
    private function update_submission_email_status($submission_id, $sent) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'voi_submissions';
        
        $wpdb->update(
            $table_name,
            [
                'email_sent' => $sent ? 1 : 0,
                'email_sent_time' => current_time('mysql')
            ],
            ['id' => $submission_id]
        );
    }
    
    /**
     * Get default email template
     * 
     * @return string
     */
    public function get_default_email_template() {
        return '
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>VOI Analysis Results</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%); color: white; padding: 30px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { background: #f8f9fa; padding: 30px; border-radius: 0 0 8px 8px; }
        .highlight { background: #e3f2fd; padding: 15px; border-radius: 5px; margin: 20px 0; }
        .footer { text-align: center; margin-top: 30px; font-size: 12px; color: #666; }
        .logo { font-size: 24px; font-weight: bold; margin-bottom: 10px; }
        .tagline { font-size: 14px; opacity: 0.9; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">🔵 Visual One Intelligence</div>
            <div class="tagline">Your Storage Intelligence Analysis</div>
        </div>
        
        <div class="content">
            <h2>Hi {first_name},</h2>
            
            <p>Thank you for using our Visual One Intelligence ROI Calculator! We\'ve prepared your personalized analysis based on the information you provided.</p>
            
            <div class="highlight">
                <h3>Your Submission Details:</h3>
                <ul>
                    <li><strong>Company:</strong> {company_name}</li>
                    <li><strong>Total Storage:</strong> {total_tb} TB</li>
                    <li><strong>Total VMs:</strong> {total_vms}</li>
                    <li><strong>Analysis Date:</strong> {date}</li>
                </ul>
            </div>
            
            <h3>What\'s Included:</h3>
            <p>We\'ve attached two important documents to this email:</p>
            <ul>
                <li><strong>Your Custom ROI Analysis</strong> - Personalized calculations based on your environment</li>
                <li><strong>Visual One Intelligence Overview</strong> - Detailed information about our platform</li>
            </ul>
            
            <h3>Next Steps:</h3>
            <p>Our team will review your analysis and may reach out to discuss how Visual One Intelligence can help optimize your storage infrastructure and reduce costs.</p>
            
            <p>If you have any questions about your analysis or would like to schedule a demo, please don\'t hesitate to contact us.</p>
            
            <p>Best regards,<br>
            The Visual One Intelligence Team</p>
        </div>
        
        <div class="footer">
            <p>This email was sent from {site_name} | <a href="{site_url}">Visit our website</a></p>
            <p>© 2025 Visual One Intelligence. All rights reserved.</p>
        </div>
    </div>
</body>
</html>';
    }
    
    /**
     * Test email functionality
     * 
     * @param string $test_email
     * @return bool
     */
    public function send_test_email($test_email) {
        $test_form_data = [
            'full_name' => 'Test User',
            'email' => $test_email,
            'company_name' => 'Test Company',
            'company_url' => 'https://testcompany.com',
            'total_tb' => 500,
            'total_vms' => 100
        ];
        
        $subject = '[TEST] ' . $this->get_email_subject($test_form_data);
        $message = $this->get_email_message($test_form_data);
        $headers = $this->get_email_headers();
        
        // No attachments for test email
        return wp_mail($test_email, $subject, $message, $headers);
    }
    
    /**
     * Get available email placeholders
     * 
     * @return array
     */
    public static function get_available_placeholders() {
        return [
            '{full_name}' => 'Full name from form',
            '{first_name}' => 'First name only',
            '{company_name}' => 'Company name',
            '{company_url}' => 'Company website',
            '{total_tb}' => 'Total TB (formatted)',
            '{total_vms}' => 'Total VMs (formatted)',
            '{email}' => 'User email address',
            '{date}' => 'Current date',
            '{time}' => 'Current time',
            '{site_name}' => 'Website name',
            '{site_url}' => 'Website URL'
        ];
    }
}