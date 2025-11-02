<?php

namespace APL\Classes;


class APL_Main {
    public function __construct() {
        $this->init();
    }

    public function init() {
        // Check if WooCommerce is active
        if (!$this->is_woocommerce_active()) {
            $this->add_woocommerce_notice();
            return; // Stop execution if WooCommerce is not active
        }
        
        $this->load_functions();
        $this->load_classes();
        \add_action('wp_enqueue_scripts', array($this, 'load_assets'));
        \add_action('admin_enqueue_scripts', array($this, 'load_assets'));
    }
    
    private function is_woocommerce_active() {
        return in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')));
    }
    
    private function add_woocommerce_notice() {
        \add_action('admin_notices', array($this, 'woocommerce_notice'));
    }
    
    public function woocommerce_notice() {
        ?>
        <div class="notice notice-error">
            <p>
                <strong><?php _e('Arta PoyeshLab', 'arta-poyeshlab'); ?></strong> - 
                <?php _e('این افزونه نیاز به افزونه ووکامرس دارد. لطفاً ابتدا افزونه ووکامرس را نصب و فعال کنید.', 'arta-poyeshlab'); ?>
            </p>
        </div>
        <?php
    }

    public function load_functions() {
        include_once ARTA_POYESHLAB_PLUGIN_DIR . 'include/function.php';
    }

    public function load_classes() {
        // Load classes in specific order to avoid dependency issues
        $classes_order = array(
            'apl-gregorian_jalali.php',
            'apl-logger.php',
            'apl-sms-handler.php', 
            'apl-cron.php',
            'apl-auth.php',
            'apl-ajax-handlers.php',
            'apl-admin-settings.php',
            'apl-product-fields.php',
            'apl-my-account.php',
            'apl-pdf-generator.php',
            'apl-appointments.php',
            'apl-order-meta.php',
            'apl-lab-test-results.php'
        );
        
        foreach ($classes_order as $class_file) {
            $file_path = ARTA_POYESHLAB_PLUGIN_DIR . 'include/classes/' . $class_file;
            if (file_exists($file_path)) {
                include_once $file_path;
            }
        }
    }
    public function load_assets($hook = '') {
        \wp_enqueue_style('apl-style', ARTA_POYESHLAB_PLUGIN_URL . 'assets/css/style.css');
        \wp_enqueue_script('apl-script', ARTA_POYESHLAB_PLUGIN_URL . 'assets/js/script.js', array('jquery'), '1.0.0', true);
        
        // Localize script for AJAX
        \wp_localize_script('apl-script', 'apl_ajax', array(
            'ajaxurl' => \admin_url('admin-ajax.php'),
            'login_nonce' => \wp_create_nonce('apl_login_nonce'),
            'register_nonce' => \wp_create_nonce('apl_register_nonce'),
            'profile_nonce' => \wp_create_nonce('apl_profile_nonce'),
            'dashboard_nonce' => \wp_create_nonce('apl_dashboard_nonce')
        ));
        
        // Enqueue admin insurance button script for WooCommerce admin pages
        if (is_admin()) {
            $this->enqueue_admin_insurance_script($hook);
        }
    }
    
    /**
     * Enqueue admin insurance button script for WooCommerce order pages
     */
    private function enqueue_admin_insurance_script($hook) {
        $screen = \get_current_screen();
        $is_order_page = false;
        
        // Check for WooCommerce order pages (classic and HPOS)
        if (($hook === 'post.php' || $hook === 'post-new.php') && isset($screen) && $screen->post_type === 'shop_order') {
            $is_order_page = true;
        } elseif (function_exists('wc_get_page_screen_id') && isset($screen) && $screen->id === \wc_get_page_screen_id('shop_order')) {
            $is_order_page = true;
        } elseif (isset($screen) && (strpos($screen->id, 'woocommerce_page_wc-orders') !== false || strpos($hook, 'woocommerce') !== false)) {
            $is_order_page = true;
        }
        
        if ($is_order_page) {
            // Enqueue the insurance button script with jQuery dependency
            \wp_enqueue_script(
                'apl-admin-insurance-button',
                ARTA_POYESHLAB_PLUGIN_URL . 'assets/js/admin-insurance-button.js',
                array('jquery'),
                ARTA_POYESHLAB_VERSION,
                true
            );
            
            // Localize script for AJAX
            \wp_localize_script('apl-admin-insurance-button', 'apl_ajax', array(
                'ajaxurl' => \admin_url('admin-ajax.php'),
                'dashboard_nonce' => \wp_create_nonce('apl_dashboard_nonce')
            ));
        }
    }
}
new APL_Main();