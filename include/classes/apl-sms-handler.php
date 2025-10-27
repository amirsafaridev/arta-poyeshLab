<?php
/**
 * SMS Handler Class for National Payamak
 * 
 * @package ArtaPoyeshLab
 * @since 1.0.0
 */

namespace APL\Classes;

if (!defined('ABSPATH')) {
    exit;
}

class APL_SMS_Handler {
    
    private $username;
    private $password;
    private $from_number;
    private $api_url = 'https://rest.payamak-panel.com/api/SendSMS/SendSMS';
    private $logger;
    
    public function __construct() {
        $this->load_settings();
        $this->logger = new APL_Logger();
    }
    
    /**
     * Load SMS settings from WordPress options
     */
    private function load_settings() {
        $this->username = get_option('apl_sms_username', '');
        $this->password = get_option('apl_sms_password', '');
        $this->from_number = get_option('apl_sms_from_number', '');
    }
    
    /**
     * Generate random 6-digit OTP code
     * 
     * @return string
     */
    public function generate_otp() {
        return str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
    }
    
    /**
     * Save OTP code to WordPress options
     * 
     * @param string $mobile_number
     * @param string $otp_code
     * @return bool
     */
    public function save_otp($mobile_number, $otp_code) {
        $otp_data = array(
            'code' => $otp_code,
            'expires' => time() + 120, // 2 minutes
            'attempts' => 0
        );
        
        return update_option('apl_otp_' . $mobile_number, $otp_data);
    }
    
    /**
     * Get OTP code from WordPress options
     * 
     * @param string $mobile_number
     * @return array|false
     */
    public function get_otp($mobile_number) {
        return get_option('apl_otp_' . $mobile_number, false);
    }
    
    /**
     * Verify OTP code
     * 
     * @param string $mobile_number
     * @param string $input_code
     * @return array
     */
    public function verify_otp($mobile_number, $input_code) {
        // Check for test code
        if ($input_code === '939393') {
            return array(
                'success' => true,
                'message' => 'کد تست تایید شد'
            );
        }
        
        $otp_data = $this->get_otp($mobile_number);
        
        if (!$otp_data) {
            return array(
                'success' => false,
                'message' => 'کد تایید یافت نشد'
            );
        }
        
        // Check if expired
        if (time() > $otp_data['expires']) {
            delete_option('apl_otp_' . $mobile_number);
            return array(
                'success' => false,
                'message' => 'کد تایید منقضی شده است'
            );
        }
        
        // Check attempts
        if ($otp_data['attempts'] >= 3) {
            delete_option('apl_otp_' . $mobile_number);
            return array(
                'success' => false,
                'message' => 'تعداد تلاش‌های مجاز تمام شده است'
            );
        }
        
        // Verify code
        if ($otp_data['code'] === $input_code) {
            delete_option('apl_otp_' . $mobile_number);
            return array(
                'success' => true,
                'message' => 'کد تایید صحیح است'
            );
        } else {
            // Increment attempts
            $otp_data['attempts']++;
            update_option('apl_otp_' . $mobile_number, $otp_data);
            
            return array(
                'success' => false,
                'message' => 'کد تایید اشتباه است'
            );
        }
    }
    
    /**
     * Send SMS using National Payamak API
     * 
     * @param string $mobile_number
     * @param string $message
     * @return array
     */
    public function send_sms($mobile_number, $message) {
        // Check if settings are configured
        if (empty($this->username) || empty($this->password) || empty($this->from_number)) {
            return array(
                'success' => false,
                'message' => 'تنظیمات پیامک تکمیل نشده است'
            );
        }
        
        // Prepare data for API
        $data = array(
            'username' => $this->username,
            'password' => $this->password,
            'to' => $mobile_number,
            'from' => $this->from_number,
            'text' => $message
        );
        
        // Send request to API
        $response = wp_remote_post($this->api_url, array(
            'body' => $data,
            'timeout' => 30,
            'headers' => array(
                'Content-Type' => 'application/x-www-form-urlencoded'
            )
        ));
        
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => 'خطا در ارسال پیامک: ' . $response->get_error_message()
            );
        }
        
        $body = wp_remote_retrieve_body($response);
        $result = json_decode($body, true);
        
        // Check API response
        if (isset($result['RetStatus']) && $result['RetStatus'] == 1) {
            return array(
                'success' => true,
                'message' => 'پیامک با موفقیت ارسال شد',
                'data' => $result
            );
        } else {
            $error_message = isset($result['StrRetStatus']) ? $result['StrRetStatus'] : 'خطای نامشخص';
            return array(
                'success' => false,
                'message' => 'خطا در ارسال پیامک: ' . $error_message
            );
        }
    }
    
    /**
     * Send OTP SMS
     * 
     * @param string $mobile_number
     * @return array
     */
    public function send_otp_sms($mobile_number) {
        $otp_code = $this->generate_otp();
        $message = "کد تایید شما: {$otp_code}\nآزمایشگاه پوش";
        
        // Save OTP to database
        $this->save_otp($mobile_number, $otp_code);
        
        // Log before sending
        $this->logger->log_sms_sent($mobile_number, 'pending', 'در حال ارسال پیامک');
        
        // Send SMS
        $result = $this->send_sms($mobile_number, $message);
        
        // Log result
        if ($result['success']) {
            $this->logger->log_sms_sent($mobile_number, 'success', 'پیامک با موفقیت ارسال شد');
        } else {
            $this->logger->log_sms_sent($mobile_number, 'failed', $result['message']);
        }
        
        return $result;
    }
    
    /**
     * Clean expired OTPs
     */
    public function clean_expired_otps() {
        global $wpdb;
        
        $options = $wpdb->get_results(
            "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE 'apl_otp_%'"
        );
        
        foreach ($options as $option) {
            $otp_data = get_option($option->option_name);
            if ($otp_data && time() > $otp_data['expires']) {
                delete_option($option->option_name);
            }
        }
    }
}
