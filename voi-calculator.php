<?php
/**
 * Plugin Name:       VOI Calculator
 * Plugin URI:        https://niftyfiftysolutions.com/
 * Description:       A two-stage ROI calculator for Visual Storage Intelligence.
 * Version:           1.1.2
 * Author:            Nifty Fifty Solution
 * Author URI:        https://niftyfiftysolutions.com/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       voi-calculator
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// --- DEBUGGING ---
// Enable WP_DEBUG mode
if ( ! defined( 'WP_DEBUG' ) ) {
    define( 'WP_DEBUG', true );
}
// Enable Debug logging to the /wp-content/debug.log file
if ( ! defined( 'WP_DEBUG_LOG' ) ) {
    define( 'WP_DEBUG_LOG', true );
}
// Disable display of errors and warnings
if ( ! defined( 'WP_DEBUG_DISPLAY' ) ) {
    define( 'WP_DEBUG_DISPLAY', false );
}
// --- END DEBUGGING ---


// Define plugin constants
define( 'VOI_CALCULATOR_VERSION', '1.1.2' );
define( 'VOI_CALCULATOR_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'VOI_CALCULATOR_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Include required files
require_once VOI_CALCULATOR_PLUGIN_DIR . 'includes/class-voi-calculator-admin.php';
require_once VOI_CALCULATOR_PLUGIN_DIR . 'includes/class-voi-calculator-pdf-generator.php';
require_once VOI_CALCULATOR_PLUGIN_DIR . 'vendor/tcpdf.php';

/**
 * The code that runs during plugin activation.
 */
function voi_calculator_activate() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'voi_submissions';
    $charset_collate = $wpdb->get_charset_collate();

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
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );

    // Create a directory for storing generated PDFs
    $upload_dir = wp_upload_dir();
    $pdf_dir = $upload_dir['basedir'] . '/voi-calculator-pdfs';
    if (!is_dir($pdf_dir)) {
        wp_mkdir_p($pdf_dir);
    }
}
register_activation_hook( __FILE__, 'voi_calculator_activate' );

/**
 * Enqueue scripts and styles.
 */
function voi_calculator_enqueue_assets() {
    if ( is_a( get_post( get_the_ID() ), 'WP_Post' ) && has_shortcode( get_post( get_the_ID() )->post_content, 'voi_calculator' ) ) {
        wp_enqueue_style(
            'voi-calculator-style',
            VOI_CALCULATOR_PLUGIN_URL . 'assets/css/voi-style.css',
            [],
            VOI_CALCULATOR_VERSION
        );

        wp_enqueue_script(
            'voi-calculator-script',
            VOI_CALCULATOR_PLUGIN_URL . 'assets/js/voi-script.js',
            ['jquery'],
            VOI_CALCULATOR_VERSION,
            true
        );

        wp_localize_script('voi-calculator-script', 'voi_ajax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('voi_calculator_nonce')
        ]);
    }
}
add_action( 'wp_enqueue_scripts', 'voi_calculator_enqueue_assets' );

/**
 * Register the shortcode to display the form.
 */
function voi_calculator_form_shortcode() {
    ob_start();
    include VOI_CALCULATOR_PLUGIN_DIR . 'public/partials/form-display.php';
    return ob_get_clean();
}
add_shortcode( 'voi_calculator', 'voi_calculator_form_shortcode' );

/**
 * Handle the AJAX form submission.
 */
function voi_handle_form_submission() {
    error_log('VOI Calculator: AJAX handler started.');

    if ( ! check_ajax_referer( 'voi_calculator_nonce', 'nonce', false ) ) {
        error_log('VOI Calculator: Nonce verification failed.');
        wp_send_json_error( ['message' => 'Security check failed.'], 403 );
        return;
    }
    error_log('VOI Calculator: Nonce verified.');

    $form_data = [
        'total_tb'     => isset($_POST['total_tb']) ? intval($_POST['total_tb']) : 0,
        'total_vms'    => isset($_POST['total_vms']) ? intval($_POST['total_vms']) : 0,
        'company_name' => isset($_POST['company_name']) ? sanitize_text_field($_POST['company_name']) : '',
        'company_url'  => isset($_POST['company_url']) ? esc_url_raw($_POST['company_url']) : '',
        'full_name'    => isset($_POST['full_name']) ? sanitize_text_field($_POST['full_name']) : '',
        'email'        => isset($_POST['email']) ? sanitize_email($_POST['email']) : '',
    ];
    error_log('VOI Calculator: Form data sanitized: ' . print_r($form_data, true));

    // Generate PDF
    error_log('VOI Calculator: Initializing PDF generator.');
    $pdf_generator = new VOI_Calculator_PDF_Generator($form_data);
    $pdf_result = $pdf_generator->generate();
    error_log('VOI Calculator: PDF generation attempted.');

    if (is_wp_error($pdf_result)) {
        error_log('VOI Calculator: PDF generation failed. Error: ' . $pdf_result->get_error_message());
        wp_send_json_error(['message' => 'PDF Generation Error: ' . $pdf_result->get_error_message()], 500);
        return;
    }
    error_log('VOI Calculator: PDF generated successfully. Path: ' . $pdf_result['url']);

    global $wpdb;
    $table_name = $wpdb->prefix . 'voi_submissions';

    $db_data = $form_data;
    $db_data['time'] = current_time('mysql');
    $db_data['pdf_link'] = $pdf_result['url'];

    error_log('VOI Calculator: Inserting data into database.');
    $result = $wpdb->insert($table_name, $db_data);

    if ($result) {
        error_log('VOI Calculator: Database insert successful.');
        wp_send_json_success([
            'message' => 'Your value document has been generated successfully!',
            'pdf_url' => $pdf_result['url']
        ]);
    } else {
        error_log('VOI Calculator: Database insert failed. DB Error: ' . $wpdb->last_error);
        wp_send_json_error(['message' => 'There was an error saving your data to the database.'], 500);
    }
    error_log('VOI Calculator: AJAX handler finished.');
}
add_action( 'wp_ajax_voi_handle_form_submission', 'voi_handle_form_submission' );
add_action( 'wp_ajax_nopriv_voi_handle_form_submission', 'voi_handle_form_submission' );

// Initialize the admin page
$voi_admin = new VOI_Calculator_Admin();
$voi_admin->init();
