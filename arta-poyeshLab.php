<?php
/**
 * Plugin Name: Arta PoyeshLab
 * Plugin URI: https://artacode.net
 * Description: A comprehensive WordPress plugin for Arta PoyeshLab functionality
 * Version: 1.0.0
 * Author: ArtaCode
 * Author URI: https://artacode.net
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('ARTA_POYESHLAB_VERSION', '1.0.0');
define('ARTA_POYESHLAB_PLUGIN_FILE', __FILE__);
define('ARTA_POYESHLAB_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ARTA_POYESHLAB_PLUGIN_URL', plugin_dir_url(__FILE__));
define('ARTA_POYESHLAB_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Load the main plugin file
include_once ARTA_POYESHLAB_PLUGIN_DIR . 'include/apl-main.php';

// Handle PDF invoice requests
add_action('init', 'apl_handle_invoice_request');

function apl_handle_invoice_request() {
    if (isset($_GET['apl_action']) && $_GET['apl_action'] === 'view_invoice') {
        $order_id = intval($_GET['order_id']);
        $nonce = sanitize_text_field($_GET['nonce']);
        
        // Verify nonce
        if (!wp_verify_nonce($nonce, 'apl_invoice_' . $order_id)) {
            wp_die('Security check failed');
        }
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_die('کاربر وارد نشده است');
        }
        
        $current_user = wp_get_current_user();
        $order = wc_get_order($order_id);
        
        if (!$order || $order->get_customer_id() != $current_user->ID) {
            wp_die('شما دسترسی به این سفارش ندارید');
        }
        
        // Check if order is completed or processing
        if (!in_array($order->get_status(), array('completed', 'processing'))) {
            wp_die('فاکتور فقط برای سفارشات تکمیل شده یا در حال پردازش قابل دانلود است');
        }
        
        // Include PDF generator class
        if (!class_exists('APL\Classes\APL_PDF_Generator')) {
            require_once ARTA_POYESHLAB_PLUGIN_DIR . 'include/classes/apl-pdf-generator.php';
        }
        
        $pdf_generator = new \APL\Classes\APL_PDF_Generator();
        $pdf_generator->output_invoice_pdf($order_id);
        exit;
    }
}

// Register deactivation hook
register_deactivation_hook(__FILE__, array('APL\Classes\APL_Cron', 'deactivate'));


