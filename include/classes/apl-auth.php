<?php
/**
 * Authentication Class
 * 
 * @package ArtaPoyeshLab
 * @since 1.0.0
 */

namespace APL\Classes;

if (!defined('ABSPATH')) {
    exit;
}

class APL_Auth {
    
    private $logger;
    
    public function __construct() {
        $this->logger = new APL_Logger();
        add_action('init', array($this, 'init'));
        add_action('wp_logout', array($this, 'handle_logout'));
    }
    
    /**
     * Initialize authentication
     */
    public function init() {
        // Check if user is on lab-portal page
        if (is_page() || (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], 'lab-portal') !== false)) {
            $this->check_authentication();
        }
    }
    
    /**
     * Check user authentication status
     */
    public function check_authentication() {
        if (!is_user_logged_in()) {
            // User is not logged in, show auth screen
            return;
        }
        
        // User is logged in, check if they have required meta data
        $current_user = wp_get_current_user();
        $mobile_number = get_user_meta($current_user->ID, 'apl_mobile_number', true);
        
        if (empty($mobile_number)) {
            // User doesn't have mobile number, might be admin user
            // Allow them to access but log it
            $this->logger->add_log('auth_check', 'admin', 'info', 'دسترسی ادمین بدون شماره موبایل');
        }
    }
    
    /**
     * Handle user logout
     */
    public function handle_logout() {
        $current_user = wp_get_current_user();
        if ($current_user && $current_user->ID) {
            $mobile_number = get_user_meta($current_user->ID, 'apl_mobile_number', true);
            if ($mobile_number) {
                $this->logger->add_log('logout', $mobile_number, 'success', 'کاربر از سیستم خارج شد');
            }
        }
    }
    
    /**
     * Get current user mobile number
     * 
     * @return string|false
     */
    public function get_current_user_mobile() {
        if (!is_user_logged_in()) {
            return false;
        }
        
        $current_user = wp_get_current_user();
        return get_user_meta($current_user->ID, 'apl_mobile_number', true);
    }
    
    /**
     * Get current user national ID
     * 
     * @return string|false
     */
    public function get_current_user_national_id() {
        if (!is_user_logged_in()) {
            return false;
        }
        
        $current_user = wp_get_current_user();
        return get_user_meta($current_user->ID, 'apl_national_id', true);
    }
    
    /**
     * Check if user exists by mobile number
     * 
     * @param string $mobile_number
     * @return WP_User|false
     */
    public function get_user_by_mobile($mobile_number) {
        return get_user_by('login', $mobile_number);
    }
    
    /**
     * Create user with mobile number as username
     * 
     * @param string $mobile_number
     * @param string $first_name
     * @param string $last_name
     * @param string $national_id
     * @return int|WP_Error
     */
    public function create_user($mobile_number, $first_name, $last_name, $national_id) {
        // Generate random password
        $password = wp_generate_password(12, false);
        
        // Create user with mobile as username
        $user_id = wp_create_user($mobile_number, $password, $mobile_number);
        
        if (is_wp_error($user_id)) {
            return $user_id;
        }
        
        // Set user role to customer (WooCommerce default role)
        $user = new \WP_User($user_id);
        $user->set_role('customer');
        
        // Update user data
        wp_update_user(array(
            'ID' => $user_id,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'display_name' => $first_name . ' ' . $last_name,
            'nickname' => $first_name . ' ' . $last_name
        ));
        
        // Add meta data
        update_user_meta($user_id, 'apl_mobile_number', $mobile_number);
        update_user_meta($user_id, 'apl_national_id', $national_id);
        
        return $user_id;
    }
    
    /**
     * Login user by mobile number
     * 
     * @param string $mobile_number
     * @return bool
     */
    public function login_user_by_mobile($mobile_number) {
        $user = $this->get_user_by_mobile($mobile_number);
        
        if (!$user) {
            return false;
        }
        
        wp_set_current_user($user->ID);
        wp_set_auth_cookie($user->ID);
        
        return true;
    }
    
    /**
     * Logout current user
     */
    public function logout_user() {
        if (is_user_logged_in()) {
            wp_logout();
        }
    }
    
    /**
     * Check if current user is authenticated
     * 
     * @return bool
     */
    public function is_authenticated() {
        return is_user_logged_in();
    }
    
    /**
     * Get user display name
     * 
     * @return string
     */
    public function get_user_display_name() {
        if (!is_user_logged_in()) {
            return '';
        }
        
        $current_user = wp_get_current_user();
        return $current_user->display_name ?: $current_user->user_login;
    }
    
    /**
     * Get user first name
     * 
     * @return string
     */
    public function get_user_first_name() {
        if (!is_user_logged_in()) {
            return '';
        }
        
        $current_user = wp_get_current_user();
        return $current_user->first_name ?: '';
    }
    
    /**
     * Get user last name
     * 
     * @return string
     */
    public function get_user_last_name() {
        if (!is_user_logged_in()) {
            return '';
        }
        
        $current_user = wp_get_current_user();
        return $current_user->last_name ?: '';
    }
}

// Initialize authentication
new APL_Auth();
