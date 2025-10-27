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
        
        // Load WooCommerce product types after WooCommerce is loaded
        add_action('woocommerce_loaded', array($this, 'load_wc_product_types'));
        
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
            'apl-my-account.php',
            'apl-pdf-generator.php'
        );
        
        foreach ($classes_order as $class_file) {
            $file_path = ARTA_POYESHLAB_PLUGIN_DIR . 'include/classes/' . $class_file;
            if (file_exists($file_path)) {
                include_once $file_path;
            }
        }
    }
    
    /**
     * Load WooCommerce product types after WooCommerce is loaded
     */
    public function load_wc_product_types() {
        // Load WooCommerce product type classes
        $wc_classes = array(
            'apl-product-lab-test.php',
            'apl-product-lab-package.php',
            'apl-wc-product-types.php'
        );
        
        foreach ($wc_classes as $class_file) {
            $file_path = ARTA_POYESHLAB_PLUGIN_DIR . 'include/classes/' . $class_file;
            if (file_exists($file_path)) {
                include_once $file_path;
            }
        }
        
        // Initialize WooCommerce Product Types Manager
        if (class_exists('APL_WC_Product_Types')) {
            new \APL_WC_Product_Types();
        }
    }
    public function load_assets() {
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
    }
}
new APL_Main();