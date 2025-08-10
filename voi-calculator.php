<?php
/**
 * Plugin Name:       VOI Calculator
 * Plugin URI:        https://niftyfiftysolutions.com/
 * Description:       A two-stage ROI calculator for Visual Storage Intelligence.
 * Version:           1.0.1
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

// Define plugin constants
define( 'VOI_CALCULATOR_VERSION', '1.0.1' );
define( 'VOI_CALCULATOR_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'VOI_CALCULATOR_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Include required files
require_once VOI_CALCULATOR_PLUGIN_DIR . 'includes/class-voi-calculator-admin.php';

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
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );
}
register_activation_hook( __FILE__, 'voi_calculator_activate' );

/**
 * Enqueue scripts and styles.
 */
function voi_calculator_enqueue_assets() {
    // Only load on pages with the shortcode to optimize performance.
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
    if ( ! check_ajax_referer( 'voi_calculator_nonce', 'nonce', false ) ) {
        wp_send_json_error( ['message' => 'Security check failed. Please refresh the page and try again.'], 403 );
        return;
    }

    $required_fields = ['total_tb', 'total_vms', 'company_name', 'company_url', 'full_name', 'email'];
    foreach($required_fields as $field) {
        if (empty($_POST[$field])) {
            wp_send_json_error(['message' => 'Please fill out all required fields.'], 400);
            return;
        }
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'voi_submissions';

    $data = [
        'time'         => current_time('mysql'),
        'total_tb'     => intval($_POST['total_tb']),
        'total_vms'    => intval($_POST['total_vms']),
        'company_name' => sanitize_text_field($_POST['company_name']),
        'company_url'  => esc_url_raw($_POST['company_url']),
        'full_name'    => sanitize_text_field($_POST['full_name']),
        'email'        => sanitize_email($_POST['email']),
    ];

    $result = $wpdb->insert($table_name, $data);

    if ($result) {
        // Here we will later add the PDF generation and email logic.
        // For now, we just confirm submission.
        wp_send_json_success(['message' => 'Thank you! Your submission has been received.']);
    } else {
        wp_send_json_error(['message' => 'There was an error saving your data. Please try again.'], 500);
    }
}
add_action( 'wp_ajax_voi_handle_form_submission', 'voi_handle_form_submission' );
add_action( 'wp_ajax_nopriv_voi_handle_form_submission', 'voi_handle_form_submission' );

// Initialize the admin page
$voi_admin = new VOI_Calculator_Admin();
$voi_admin->init();
