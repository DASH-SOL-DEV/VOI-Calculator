<?php
/**
 * Plugin Name:       VOI Calculator
 * Plugin URI:        https://niftyfiftysolutions.com/
 * Description:       A two-stage ROI calculator for Visual Storage Intelligence.
 * Version:           1.5.1
 * Author:            Nifty Fifty Solution
 * Author URI:        https://niftyfiftysolutions.com/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       voi-calculator
 */

if ( ! defined( 'WPINC' ) ) die;

define( 'VOI_CALCULATOR_VERSION', '1.5.1' );
define( 'VOI_CALCULATOR_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'VOI_CALCULATOR_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once VOI_CALCULATOR_PLUGIN_DIR . 'includes/class-voi-calculator-admin.php';
require_once VOI_CALCULATOR_PLUGIN_DIR . 'includes/class-voi-calculator-pdf-generator.php';
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
    $upload_dir = wp_upload_dir();
    $pdf_dir = $upload_dir['basedir'] . '/voi-calculator-pdfs';
    if (!is_dir($pdf_dir)) wp_mkdir_p($pdf_dir);
}

add_action( 'wp_enqueue_scripts', 'voi_calculator_enqueue_assets' );
function voi_calculator_enqueue_assets() {
    if ( is_a( get_post( get_the_ID() ), 'WP_Post' ) && has_shortcode( get_post( get_the_ID() )->post_content, 'voi_calculator' ) ) {
        wp_enqueue_style('voi-calculator-style', VOI_CALCULATOR_PLUGIN_URL . 'assets/css/voi-style.css', [], VOI_CALCULATOR_VERSION);
        wp_enqueue_script('voi-calculator-script', VOI_CALCULATOR_PLUGIN_URL . 'assets/js/voi-script.js', ['jquery'], VOI_CALCULATOR_VERSION, true);
        wp_localize_script('voi-calculator-script', 'voi_ajax', ['ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('voi_calculator_nonce')]);
    }
}

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

    $pdf_generator = new VOI_Calculator_PDF_Generator($form_data);
    $pdf_result = $pdf_generator->generate();

    if (is_wp_error($pdf_result)) {
        wp_send_json_error(['message' => $pdf_result->get_error_message()], 500);
        return;
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'voi_submissions';
    $db_data = $form_data;
    $db_data['time'] = current_time('mysql');
    $db_data['pdf_link'] = $pdf_result['url'];
    $result = $wpdb->insert($table_name, $db_data);
    $submission_id = $wpdb->insert_id;

    if ($result) {
        $_SESSION['voi_submission_id'] = $submission_id;
        // Force session data to be written immediately.
        session_write_close(); 
        
        wp_send_json_success([
            'submission_id' => $submission_id,
            'pdf_url' => $pdf_result['url'],
            'html_output' => $pdf_result['html']
        ]);
    } else {
        wp_send_json_error(['message' => 'There was an error saving your data.'], 500);
    }
}

$voi_admin = new VOI_Calculator_Admin();
$voi_admin->init();
