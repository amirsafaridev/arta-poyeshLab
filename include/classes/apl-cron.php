<?php
/**
 * Cron Jobs Class
 * 
 * @package ArtaPoyeshLab
 * @since 1.0.0
 */

namespace APL\Classes;

if (!defined('ABSPATH')) {
    exit;
}

class APL_Cron {
    
    private $sms_handler;
    
    public function __construct() {
        $this->sms_handler = new APL_SMS_Handler();
        add_action('init', array($this, 'init_cron'));
        add_action('apl_cleanup_otps', array($this, 'cleanup_expired_otps'));
    }
    
    /**
     * Initialize cron jobs
     */
    public function init_cron() {
        // Schedule cleanup of expired OTPs every hour
        if (!wp_next_scheduled('apl_cleanup_otps')) {
            wp_schedule_event(time(), 'hourly', 'apl_cleanup_otps');
        }
    }
    
    /**
     * Clean up expired OTPs
     */
    public function cleanup_expired_otps() {
        $this->sms_handler->clean_expired_otps();
    }
    
    /**
     * Clean up on deactivation
     */
    public static function deactivate() {
        wp_clear_scheduled_hook('apl_cleanup_otps');
    }
}

// Initialize cron jobs
new APL_Cron();
