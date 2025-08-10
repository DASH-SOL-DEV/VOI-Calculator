<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class VOI_Calculator_Admin {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
    }
    
    public function add_admin_menu() {
        add_menu_page(
            'VOI Calculator',
            'VOI Calculator', 
            'manage_options',
            'voi-calculator',
            array($this, 'admin_page'),
            'dashicons-calculator',
            30
        );
        
        add_submenu_page(
            'voi-calculator',
            'Submissions',
            'Submissions',
            'manage_options',
            'voi-calculator-submissions',
            array($this, 'submissions_page')
        );
        
        // Hidden submenu for PDF viewing
        add_submenu_page(
            null, // No parent menu (hidden)
            'View PDF',
            'View PDF',
            'manage_options',
            'voi-calculator-view-pdf',
            array($this, 'view_pdf_page')
        );
    }
    
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>VOI Calculator Settings</h1>
            <div class="card">
                <h2>Plugin Information</h2>
                <p><strong>Plugin Version:</strong> <?php echo VOI_CALC_VERSION; ?></p>
                <p><strong>Author:</strong> Nifty Fifty Solutions</p>
                <p><strong>Shortcode:</strong> <code>[voi_calculator]</code></p>
            </div>
            
            <div class="card">
                <h2>Usage Instructions</h2>
                <p>To display the VOI Calculator on any page or post, use the shortcode:</p>
                <code>[voi_calculator]</code>
                
                <h3>Features:</h3>
                <ul>
                    <li>✅ Form submission and database storage</li>
                    <li>🔄 HubSpot integration (coming soon)</li>
                    <li>📄 PDF generation (coming soon)</li>
                </ul>
            </div>
            
            <div class="card">
                <h2>Recent Submissions</h2>
                <?php
                global $wpdb;
                $table_name = $wpdb->prefix . 'voi_calculator_submissions';
                $recent_submissions = $wpdb->get_results(
                    "SELECT * FROM $table_name ORDER BY submission_date DESC LIMIT 5"
                );
                
                if ($recent_submissions) {
                    echo '<table class="wp-list-table widefat fixed striped">';
                    echo '<thead><tr><th>Date</th><th>Company</th><th>Name</th><th>Email</th><th>Storage (TB)</th><th>VMs</th><th>Report</th></tr></thead>';
                    echo '<tbody>';
                    foreach ($recent_submissions as $submission) {
                        echo '<tr>';
                        echo '<td>' . date('M j, Y g:i A', strtotime($submission->submission_date)) . '</td>';
                        echo '<td>' . esc_html($submission->company_name) . '</td>';
                        echo '<td>' . esc_html($submission->first_name . ' ' . $submission->last_name) . '</td>';
                        echo '<td>' . esc_html($submission->email) . '</td>';
                        echo '<td>' . esc_html($submission->storage_tb) . '</td>';
                        echo '<td>' . esc_html($submission->vm_count) . '</td>';
                        if ($submission->pdf_generated && $submission->pdf_file_path) {
                            echo '<td><a href="' . admin_url('admin.php?page=voi-calculator-view-pdf&id=' . $submission->id) . '" target="_blank" class="button button-small">View Report</a></td>';
                        } else {
                            echo '<td>-</td>';
                        }
                        echo '</tr>';
                    }
                    echo '</tbody></table>';
                    echo '<p><a href="' . admin_url('admin.php?page=voi-calculator-submissions') . '" class="button">View All Submissions</a></p>';
                } else {
                    echo '<p>No submissions yet.</p>';
                }
                ?>
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
    
    public function submissions_page() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'voi_calculator_submissions';
        
        // Handle deletion
        if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
            $id = intval($_GET['id']);
            if (wp_verify_nonce($_GET['_wpnonce'], 'delete_submission_' . $id)) {
                $wpdb->delete($table_name, array('id' => $id), array('%d'));
                echo '<div class="notice notice-success"><p>Submission deleted successfully.</p></div>';
            }
        }
        
        // Handle PDF regeneration
        if (isset($_GET['action']) && $_GET['action'] === 'regenerate_pdf' && isset($_GET['id'])) {
            $id = intval($_GET['id']);
            if (wp_verify_nonce($_GET['_wpnonce'], 'regenerate_pdf_' . $id)) {
                $this->regenerate_pdf($id);
            }
        }
        
        // Get all submissions
        $submissions = $wpdb->get_results(
            "SELECT * FROM $table_name ORDER BY submission_date DESC"
        );
        ?>
        <div class="wrap">
            <h1>VOI Calculator Submissions</h1>
            
            <?php if ($submissions): ?>
                <div class="tablenav top">
                    <div class="alignleft">
                        <span class="displaying-num"><?php echo count($submissions); ?> items</span>
                    </div>
                </div>
                
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th scope="col" class="manage-column">ID</th>
                            <th scope="col" class="manage-column">Date</th>
                            <th scope="col" class="manage-column">Company</th>
                            <th scope="col" class="manage-column">Contact</th>
                            <th scope="col" class="manage-column">Email</th>
                            <th scope="col" class="manage-column">Storage (TB)</th>
                            <th scope="col" class="manage-column">VMs</th>
                            <th scope="col" class="manage-column">HubSpot</th>
                            <th scope="col" class="manage-column">PDF</th>
                            <th scope="col" class="manage-column">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($submissions as $submission): ?>
                        <tr>
                            <td><?php echo $submission->id; ?></td>
                            <td><?php echo date('M j, Y g:i A', strtotime($submission->submission_date)); ?></td>
                            <td>
                                <strong><?php echo esc_html($submission->company_name); ?></strong><br>
                                <small><a href="<?php echo esc_url($submission->company_url); ?>" target="_blank"><?php echo esc_html($submission->company_url); ?></a></small>
                            </td>
                            <td><?php echo esc_html($submission->first_name . ' ' . $submission->last_name); ?></td>
                            <td><a href="mailto:<?php echo esc_attr($submission->email); ?>"><?php echo esc_html($submission->email); ?></a></td>
                            <td><?php echo esc_html($submission->storage_tb); ?></td>
                            <td><?php echo esc_html($submission->vm_count); ?></td>
                            <td>
                                <?php if ($submission->hubspot_sent): ?>
                                    <span style="color: green;">✓ Sent</span>
                                <?php else: ?>
                                    <span style="color: orange;">⏳ Pending</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($submission->pdf_generated): ?>
                                    <span style="color: green;">✓ Generated</span><br>
                                    <?php if ($submission->pdf_file_path): ?>
                                        <a href="<?php echo admin_url('admin.php?page=voi-calculator-view-pdf&id=' . $submission->id); ?>" 
                                           class="button button-small" target="_blank">View PDF</a>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span style="color: orange;">⏳ Pending</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=voi-calculator-submissions&action=delete&id=' . $submission->id), 'delete_submission_' . $submission->id); ?>" 
                                   onclick="return confirm('Are you sure you want to delete this submission?')" 
                                   class="button button-small">Delete</a>
                                <?php if ($submission->pdf_generated && $submission->pdf_file_path): ?>
                                    <br><a href="<?php echo admin_url('admin.php?page=voi-calculator-view-pdf&id=' . $submission->id); ?>" 
                                           class="button button-small" target="_blank">View PDF</a>
                                <?php elseif (class_exists('VOI_PDF_Generator')): ?>
                                    <br><a href="<?php echo wp_nonce_url(admin_url('admin.php?page=voi-calculator-submissions&action=regenerate_pdf&id=' . $submission->id), 'regenerate_pdf_' . $submission->id); ?>" 
                                           class="button button-small button-primary">Generate PDF</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="notice notice-info">
                    <p>No submissions found.</p>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
    
    public function view_pdf_page() {
        if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
            wp_die('Invalid submission ID');
        }
        
        $submission_id = intval($_GET['id']);
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'voi_calculator_submissions';
        $submission = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d",
            $submission_id
        ));
        
        if (!$submission) {
            wp_die('Submission not found');
        }
        
        if (!$submission->pdf_generated || !$submission->pdf_file_path) {
            wp_die('PDF not available for this submission');
        }
        
        $upload_dir = wp_upload_dir();
        $pdf_file = $upload_dir['basedir'] . '/voi-calculator/' . $submission->pdf_file_path;
        
        if (!file_exists($pdf_file)) {
            // Try to regenerate the PDF
            if (class_exists('VOI_PDF_Generator')) {
                $pdf_generator = new VOI_PDF_Generator(
                    $submission->storage_tb, 
                    $submission->vm_count, 
                    $submission->company_name
                );
                $pdf_html = $pdf_generator->generate_pdf();
                file_put_contents($pdf_file, $pdf_html);
            } else {
                wp_die('PDF file not found and cannot be regenerated');
            }
        }
        
        // Check if we should download or display
        $action = isset($_GET['action']) ? $_GET['action'] : 'view';
        
        if ($action === 'download') {
            // Force download
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="voi-report-' . $submission->company_name . '.html"');
            header('Content-Length: ' . filesize($pdf_file));
            readfile($pdf_file);
            exit;
        } else {
            // Display in browser
            $pdf_content = file_get_contents($pdf_file);
            
            // Add some CSS for better PDF display
            $pdf_content = str_replace('</head>', '
                <style>
                    @media print {
                        body { margin: 0; }
                        .no-print { display: none; }
                    }
                    .pdf-toolbar {
                        background: #f1f1f1;
                        padding: 10px;
                        border-bottom: 1px solid #ddd;
                        text-align: center;
                    }
                    .pdf-toolbar a {
                        margin: 0 10px;
                        text-decoration: none;
                        color: #0073aa;
                    }
                </style>
                </head>', $pdf_content);
            
            // Add toolbar
            $toolbar = '
                <div class="pdf-toolbar no-print">
                    <a href="javascript:window.print()">🖨️ Print</a>
                    <a href="' . admin_url('admin.php?page=voi-calculator-view-pdf&id=' . $submission_id . '&action=download') . '">💾 Download</a>
                    <a href="' . admin_url('admin.php?page=voi-calculator-submissions') . '">← Back to Submissions</a>
                </div>
            ';
            
            $pdf_content = str_replace('<body>', '<body>' . $toolbar, $pdf_content);
            
            echo $pdf_content;
            exit;
        }
    }
    
    private function regenerate_pdf($submission_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'voi_calculator_submissions';
        
        $submission = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d",
            $submission_id
        ));
        
        if (!$submission) {
            echo '<div class="notice notice-error"><p>Submission not found.</p></div>';
            return;
        }
        
        if (!class_exists('VOI_PDF_Generator')) {
            echo '<div class="notice notice-error"><p>PDF Generator not available.</p></div>';
            return;
        }
        
        try {
            $pdf_generator = new VOI_PDF_Generator(
                $submission->storage_tb,
                $submission->vm_count,
                $submission->company_name
            );
            
            $calculations = $pdf_generator->get_calculations();
            $pdf_html = $pdf_generator->generate_pdf();
            
            // Save PDF
            $upload_dir = wp_upload_dir();
            $voi_dir = $upload_dir['basedir'] . '/voi-calculator/';
            
            if (!file_exists($voi_dir)) {
                wp_mkdir_p($voi_dir);
            }
            
            $filename = 'voi-report-' . $submission_id . '.html';
            $filepath = $voi_dir . $filename;
            
            $saved = file_put_contents($filepath, $pdf_html);
            
            if ($saved !== false) {
                // Update database
                $wpdb->update(
                    $table_name,
                    array(
                        'pdf_generated' => 1,
                        'pdf_file_path' => $filename,
                        'calculated_roi' => $calculations['totals']['annual_roi'],
                        'annual_savings' => $calculations['totals']['total_annual_savings'],
                        'is_safe_range' => $pdf_generator->is_safe_range() ? 1 : 0
                    ),
                    array('id' => $submission_id),
                    array('%d', '%s', '%f', '%f', '%d'),
                    array('%d')
                );
                
                echo '<div class="notice notice-success"><p>PDF generated successfully! <a href="' . admin_url('admin.php?page=voi-calculator-view-pdf&id=' . $submission_id) . '" target="_blank">View PDF</a></p></div>';
            } else {
                echo '<div class="notice notice-error"><p>Failed to save PDF file.</p></div>';
            }
            
        } catch (Exception $e) {
            echo '<div class="notice notice-error"><p>PDF generation failed: ' . esc_html($e->getMessage()) . '</p></div>';
        }
    }
}

// Initialize admin
new VOI_Calculator_Admin();