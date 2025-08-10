<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class VOI_Calculator_Debug {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_debug_submenu'), 25);
    }
    
    public function add_debug_submenu() {
        add_submenu_page(
            'voi-calculator',
            'Debug',
            'Debug',
            'manage_options',
            'voi-calculator-debug',
            array($this, 'debug_page')
        );
    }
    
    public function debug_page() {
        ?>
        <div class="wrap">
            <h1>VOI Calculator Debug Information</h1>
            
            <div class="card">
                <h2>File Status</h2>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>File</th>
                            <th>Status</th>
                            <th>Path</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $required_files = [
                            'Main Plugin' => 'voi-calculator.php',
                            'CSS' => 'assets/style.css',
                            'JavaScript' => 'assets/script.js', 
                            'Admin Page' => 'admin/admin-page.php',
                            'PDF Generator' => 'includes/class-pdf-generator.php',
                            'HubSpot Integration' => 'includes/class-hubspot-integration.php',
                            'Settings Page' => 'admin/settings-page.php'
                        ];
                        
                        foreach ($required_files as $name => $file) {
                            $path = VOI_CALC_PLUGIN_PATH . $file;
                            $exists = file_exists($path);
                            echo '<tr>';
                            echo '<td>' . $name . '</td>';
                            echo '<td>' . ($exists ? '<span style="color: green;">✓ Found</span>' : '<span style="color: red;">✗ Missing</span>') . '</td>';
                            echo '<td><code>' . $path . '</code></td>';
                            echo '</tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
            
            <div class="card">
                <h2>Class Status</h2>
                <table class="wp-list-table widefat fixed striped">
                    <tbody>
                        <tr>
                            <td><strong>VOI_PDF_Generator</strong></td>
                            <td><?php echo class_exists('VOI_PDF_Generator') ? '<span style="color: green;">✓ Loaded</span>' : '<span style="color: red;">✗ Not Loaded</span>'; ?></td>
                        </tr>
                        <tr>
                            <td><strong>VOI_HubSpot_Integration</strong></td>
                            <td><?php echo class_exists('VOI_HubSpot_Integration') ? '<span style="color: green;">✓ Loaded</span>' : '<span style="color: red;">✗ Not Loaded</span>'; ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div class="card">
                <h2>Database Status</h2>
                <?php
                global $wpdb;
                $table_name = $wpdb->prefix . 'voi_calculator_submissions';
                $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
                
                if ($table_exists) {
                    echo '<p><span style="color: green;">✓</span> Table exists: <code>' . $table_name . '</code></p>';
                    
                    // Show table structure
                    $columns = $wpdb->get_results("DESCRIBE $table_name");
                    echo '<h3>Table Structure:</h3>';
                    echo '<table class="wp-list-table widefat fixed striped">';
                    echo '<thead><tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr></thead>';
                    echo '<tbody>';
                    foreach ($columns as $column) {
                        echo '<tr>';
                        echo '<td>' . $column->Field . '</td>';
                        echo '<td>' . $column->Type . '</td>';
                        echo '<td>' . $column->Null . '</td>';
                        echo '<td>' . $column->Key . '</td>';
                        echo '<td>' . $column->Default . '</td>';
                        echo '</tr>';
                    }
                    echo '</tbody></table>';
                    
                    $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
                    echo '<p><strong>Total Submissions:</strong> ' . $count . '</p>';
                    
                } else {
                    echo '<p><span style="color: red;">✗</span> Table missing: <code>' . $table_name . '</code></p>';
                    echo '<p><button onclick="recreateTable()" class="button button-primary">Recreate Table</button></p>';
                }
                ?>
            </div>
            
            <div class="card">
                <h2>Test PDF Generation</h2>
                <?php if (class_exists('VOI_PDF_Generator')): ?>
                    <p><button onclick="testPDF()" class="button button-secondary">Test PDF Generation</button></p>
                    <div id="pdf-test-result"></div>
                <?php else: ?>
                    <p><span style="color: red;">PDF Generator class not available</span></p>
                <?php endif; ?>
            </div>
            
            <div class="card">
                <h2>Test HubSpot Connection</h2>
                <?php if (class_exists('VOI_HubSpot_Integration')): ?>
                    <p><button onclick="testHubSpot()" class="button button-secondary">Test HubSpot Connection</button></p>
                    <div id="hubspot-test-result"></div>
                <?php else: ?>
                    <p><span style="color: red;">HubSpot Integration class not available</span></p>
                <?php endif; ?>
            </div>
            
            <div class="card">
                <h2>Recent Error Log Entries</h2>
                <?php
                $log_file = ini_get('error_log');
                if ($log_file && file_exists($log_file)) {
                    $lines = file($log_file);
                    $voi_lines = array_filter($lines, function($line) {
                        return strpos($line, 'VOI Calculator') !== false;
                    });
                    
                    if ($voi_lines) {
                        echo '<pre style="background: #f1f1f1; padding: 10px; max-height: 300px; overflow-y: auto;">';
                        echo htmlspecialchars(implode('', array_slice($voi_lines, -10)));
                        echo '</pre>';
                    } else {
                        echo '<p>No VOI Calculator entries found in error log.</p>';
                    }
                } else {
                    echo '<p>Error log not accessible.</p>';
                }
                ?>
            </div>
            
            <div class="card">
                <h2>Plugin Constants</h2>
                <table class="wp-list-table widefat fixed striped">
                    <tbody>
                        <tr>
                            <td><strong>VOI_CALC_PLUGIN_URL</strong></td>
                            <td><code><?php echo defined('VOI_CALC_PLUGIN_URL') ? VOI_CALC_PLUGIN_URL : 'Not defined'; ?></code></td>
                        </tr>
                        <tr>
                            <td><strong>VOI_CALC_PLUGIN_PATH</strong></td>
                            <td><code><?php echo defined('VOI_CALC_PLUGIN_PATH') ? VOI_CALC_PLUGIN_PATH : 'Not defined'; ?></code></td>
                        </tr>
                        <tr>
                            <td><strong>VOI_CALC_VERSION</strong></td>
                            <td><code><?php echo defined('VOI_CALC_VERSION') ? VOI_CALC_VERSION : 'Not defined'; ?></code></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        
        <script>
        function recreateTable() {
            if (confirm('This will recreate the database table. Are you sure?')) {
                // AJAX call to recreate table
                alert('Table recreation not implemented in this debug version.');
            }
        }
        
        function testPDF() {
            document.getElementById('pdf-test-result').innerHTML = 'Testing...';
            // AJAX call to test PDF generation
            jQuery.post(ajaxurl, {
                action: 'voi_test_pdf'
            }, function(response) {
                document.getElementById('pdf-test-result').innerHTML = 
                    '<pre>' + JSON.stringify(response, null, 2) + '</pre>';
            });
        }
        
        function testHubSpot() {
            document.getElementById('hubspot-test-result').innerHTML = 'Testing...';
            // AJAX call to test HubSpot
            jQuery.post(ajaxurl, {
                action: 'voi_test_hubspot'
            }, function(response) {
                document.getElementById('hubspot-test-result').innerHTML = 
                    '<pre>' + JSON.stringify(response, null, 2) + '</pre>';
            });
        }
        </script>
        
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
}

// Initialize debug page
new VOI_Calculator_Debug();