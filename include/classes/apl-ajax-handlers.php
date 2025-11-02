<?php
/**
 * AJAX Handlers Class
 * 
 * @package ArtaPoyeshLab
 * @since 1.0.0
 */

namespace APL\Classes;

if (!defined('ABSPATH')) {
    exit;
}

class APL_Ajax_Handlers {
    
    private $sms_handler;
    private $logger;
    
    public function __construct() {
        $this->sms_handler = new APL_SMS_Handler();
        $this->logger = new APL_Logger();
        
        // Register AJAX actions
        add_action('wp_ajax_apl_send_login_otp', array($this, 'send_login_otp'));
        add_action('wp_ajax_nopriv_apl_send_login_otp', array($this, 'send_login_otp'));
        
        add_action('wp_ajax_apl_verify_login_otp', array($this, 'verify_login_otp'));
        add_action('wp_ajax_nopriv_apl_verify_login_otp', array($this, 'verify_login_otp'));
        
        add_action('wp_ajax_apl_send_register_otp', array($this, 'send_register_otp'));
        add_action('wp_ajax_nopriv_apl_send_register_otp', array($this, 'send_register_otp'));
        
        add_action('wp_ajax_apl_verify_register_otp', array($this, 'verify_register_otp'));
        add_action('wp_ajax_nopriv_apl_verify_register_otp', array($this, 'verify_register_otp'));
        
        add_action('wp_ajax_apl_test_sms', array($this, 'test_sms'));
        add_action('wp_ajax_apl_clear_logs', array($this, 'clear_logs'));
        add_action('wp_ajax_apl_logout', array($this, 'logout'));
        add_action('wp_ajax_nopriv_apl_logout', array($this, 'logout'));
        add_action('wp_ajax_apl_update_profile', array($this, 'update_profile'));
        add_action('wp_ajax_apl_get_user_orders', array($this, 'get_user_orders'));
        add_action('wp_ajax_apl_download_invoice_pdf', array($this, 'download_invoice_pdf'));
        add_action('wp_ajax_apl_upload_profile_picture', array($this, 'upload_profile_picture'));
        add_action('wp_ajax_apl_remove_profile_picture', array($this, 'remove_profile_picture'));
        add_action('wp_ajax_apl_validate_discount_code', array($this, 'validate_discount_code'));
        add_action('wp_ajax_nopriv_apl_validate_discount_code', array($this, 'validate_discount_code'));
        add_action('wp_ajax_apl_create_order', array($this, 'create_order'));
        add_action('wp_ajax_nopriv_apl_create_order', array($this, 'create_order'));
        
        add_action('wp_ajax_apl_add_insurance_fee', array($this, 'add_insurance_fee'));
    }
    
    /**
     * Send login OTP
     */
    public function send_login_otp() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'apl_login_nonce')) {
            wp_die('Security check failed');
        }
        
        $mobile = sanitize_text_field($_POST['mobile']);
        
        // Validate mobile number
        if (empty($mobile) || !preg_match('/^09\d{9}$/', $mobile)) {
            wp_send_json_error(array(
                'message' => 'شماره موبایل معتبر نیست',
                'field' => 'loginMobileInput'
            ));
        }
        
        // Check if user exists
        $user = get_user_by('login', $mobile);
        if (!$user) {
            $this->logger->log_login_attempt($mobile, 'failed', 'کاربر یافت نشد');
            wp_send_json_error(array(
                'message' => 'کاربری با این شماره موبایل یافت نشد. لطفاً ابتدا ثبت‌نام کنید.',
                'field' => 'loginMobileInput'
            ));
        }
        
        // Log login attempt
        $this->logger->log_login_attempt($mobile, 'pending', 'درخواست ارسال کد تایید');
        
        // Send OTP
        $result = $this->sms_handler->send_otp_sms($mobile);
        
        if ($result['success']) {
            $this->logger->log_login_attempt($mobile, 'success', 'کد تایید ارسال شد');
            wp_send_json_success(array(
                'message' => 'کد تایید به شماره ' . $mobile . ' ارسال شد'
            ));
        } else {
            $this->logger->log_login_attempt($mobile, 'failed', 'خطا در ارسال کد: ' . $result['message']);
            // wp_send_json_error(array(
            //     'message' => $result['message'],
            //     'field' => 'loginMobileInput'
            // ));
            wp_send_json_success(array(
                'message' => 'کد تایید به شماره ' . $mobile . ' ارسال شد'
            ));
        }
    }
    
    /**
     * Verify login OTP
     */
    public function verify_login_otp() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'apl_login_nonce')) {
            wp_die('Security check failed');
        }
        
        $mobile = sanitize_text_field($_POST['mobile']);
        $otp = sanitize_text_field($_POST['otp']);
        
        // Validate inputs
        if (empty($mobile) || empty($otp)) {
            wp_send_json_error(array(
                'message' => 'اطلاعات ناقص است',
                'field' => 'otp-input-0'
            ));
        }
        
        // Log OTP verification attempt
        $this->logger->log_otp_verification($mobile, 'pending', 'در حال تایید کد');
        
        // Verify OTP
        $result = $this->sms_handler->verify_otp($mobile, $otp);
        
        if ($result['success']) {
            // Get user and login
            $user = get_user_by('login', $mobile);
            if ($user) {
                wp_set_current_user($user->ID);
                wp_set_auth_cookie($user->ID);
                
                $this->logger->log_login_attempt($mobile, 'success', 'لاگین موفق');
                $this->logger->log_otp_verification($mobile, 'success', 'کد تایید صحیح');
                
                wp_send_json_success(array(
                    'message' => 'ورود موفقیت‌آمیز',
                    'redirect' => home_url('/lab-portal')
                ));
            } else {
                $this->logger->log_login_attempt($mobile, 'failed', 'کاربر یافت نشد');
                wp_send_json_error(array(
                    'message' => 'خطا در ورود',
                    'field' => 'otp-input-0'
                ));
            }
        } else {
            $this->logger->log_otp_verification($mobile, 'failed', $result['message']);
            wp_send_json_error(array(
                'message' => $result['message'],
                'field' => 'otp-input-0'
            ));
        }
    }
    
    /**
     * Send register OTP
     */
    public function send_register_otp() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'apl_register_nonce')) {
            wp_die('Security check failed');
        }
        
        $first_name = sanitize_text_field($_POST['first_name']);
        $last_name = sanitize_text_field($_POST['last_name']);
        $mobile = sanitize_text_field($_POST['mobile']);
        $national_id = sanitize_text_field($_POST['national_id']);
        
        // Validate inputs
        if (empty($first_name)) {
            wp_send_json_error(array(
                'message' => 'نام الزامی است',
                'field' => 'registerFirstName'
            ));
        }
        
        if (empty($last_name)) {
            wp_send_json_error(array(
                'message' => 'نام خانوادگی الزامی است',
                'field' => 'registerLastName'
            ));
        }
        
        if (empty($mobile) || !preg_match('/^09\d{9}$/', $mobile)) {
            wp_send_json_error(array(
                'message' => 'شماره موبایل معتبر نیست',
                'field' => 'registerMobileInput'
            ));
        }
        
        if (empty($national_id) || !preg_match('/^\d{10}$/', $national_id)) {
            wp_send_json_error(array(
                'message' => 'کد ملی معتبر نیست',
                'field' => 'registerNationalId'
            ));
        }
        
        // Check if user already exists
        $existing_user = get_user_by('login', $mobile);
        if ($existing_user) {
            $this->logger->log_registration_attempt($mobile, 'failed', 'کاربر قبلاً ثبت‌نام کرده است');
            wp_send_json_error(array(
                'message' => 'کاربری با این شماره موبایل قبلاً ثبت‌نام کرده است',
                'field' => 'registerMobileInput'
            ));
        }
        
        // Log registration attempt
        $this->logger->log_registration_attempt($mobile, 'pending', 'درخواست ثبت‌نام');
        
        // Send OTP
        $result = $this->sms_handler->send_otp_sms($mobile);
        $reg_data = array(
            'first_name' => $first_name,
            'last_name' => $last_name,
            'mobile' => $mobile,
            'national_id' => $national_id,
            'timestamp' => time()
        );
        update_option('apl_reg_data_' . $mobile, $reg_data);
        if ($result['success']) {
            // Store registration data temporarily
            
            $this->logger->log_registration_attempt($mobile, 'success', 'کد تایید ثبت‌نام ارسال شد');
            wp_send_json_success(array(
                'message' => 'کد تایید به شماره ' . $mobile . ' ارسال شد'
            ));
        } else {
            $this->logger->log_registration_attempt($mobile, 'failed', ' خطا در ارسال کد برای: ' . $result['message']);
            // wp_send_json_error(array(
            //     'message' => $result['message'],
            //     'field' => 'registerMobileInput'
            // ));
            wp_send_json_success(array(
                'message' => 'کد تایید به شماره ' . $mobile . ' ارسال شد'
            ));
        }
    }
    
    /**
     * Verify register OTP
     */
    public function verify_register_otp() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'apl_register_nonce')) {
            wp_die('Security check failed');
        }
        
        $mobile = sanitize_text_field($_POST['mobile']);
        $otp = sanitize_text_field($_POST['otp']);
        
        // Validate inputs
        if (empty($mobile) || empty($otp)) {
            wp_send_json_error(array(
                'message' => 'اطلاعات ناقص است',
                'field' => 'register-otp-input-0'
            ));
        }
        
        // Get registration data
        $reg_data = get_option('apl_reg_data_' . $mobile);
        if (!$reg_data) {
            wp_send_json_error(array(
                'message' => 'اطلاعات ثبت‌نام یافت نشد',
                'field' => 'register-otp-input-0'
            ));
        }
        
        // Log OTP verification attempt
        $this->logger->log_otp_verification($mobile, 'pending', 'در حال تایید کد ثبت‌نام');
        
        // Verify OTP
        $result = $this->sms_handler->verify_otp($mobile, $otp);
        
        if ($result['success']) {
            // Create user
            $user_id = wp_create_user($mobile, wp_generate_password(), $mobile);
            
            if (is_wp_error($user_id)) {
                $this->logger->log_registration_attempt($mobile, 'failed', 'خطا در ایجاد کاربر: ' . $user_id->get_error_message());
                wp_send_json_error(array(
                    'message' => 'خطا در ایجاد حساب کاربری',
                    'field' => 'register-otp-input-0'
                ));
            }
            
            // Set user role to customer (WooCommerce default role)
            $user = new \WP_User($user_id);
            $user->set_role('customer');
            
            // Update user data
            wp_update_user(array(
                'ID' => $user_id,
                'first_name' => $reg_data['first_name'],
                'last_name' => $reg_data['last_name'],
                'display_name' => $reg_data['first_name'] . ' ' . $reg_data['last_name']
            ));
            
            // Add meta data
            update_user_meta($user_id, 'apl_mobile_number', $mobile);
            update_user_meta($user_id, 'apl_national_id', $reg_data['national_id']);
            
            // Login user
            wp_set_current_user($user_id);
            wp_set_auth_cookie($user_id);
            
            // Clean up temporary data
            delete_option('apl_reg_data_' . $mobile);
            
            $this->logger->log_registration_attempt($mobile, 'success', 'ثبت‌نام موفق');
            $this->logger->log_otp_verification($mobile, 'success', 'کد تایید ثبت‌نام صحیح');
            
            wp_send_json_success(array(
                'message' => 'ثبت‌نام و ورود موفقیت‌آمیز',
                'redirect' => home_url('/lab-portal')
            ));
        } else {
            $this->logger->log_otp_verification($mobile, 'failed', $result['message']);
            wp_send_json_error(array(
                'message' => $result['message'],
                'field' => 'register-otp-input-0'
            ));
        }
    }
    
    /**
     * Test SMS
     */
    public function test_sms() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'apl_test_sms')) {
            wp_die('Security check failed');
        }
        
        $mobile = sanitize_text_field($_POST['mobile']);
        
        if (empty($mobile) || !preg_match('/^09\d{9}$/', $mobile)) {
            wp_send_json_error(array(
                'message' => 'شماره موبایل معتبر نیست'
            ));
        }
        
        $result = $this->sms_handler->send_otp_sms($mobile);
        
        if ($result['success']) {
            wp_send_json_success(array(
                'message' => 'پیامک تست با موفقیت ارسال شد'
            ));
        } else {
            wp_send_json_error(array(
                'message' => $result['message']
            ));
        }
    }
    
    /**
     * Clear logs
     */
    public function clear_logs() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'apl_clear_logs')) {
            wp_die('Security check failed');
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => 'شما مجاز به انجام این عمل نیستید'
            ));
        }
        
        $this->logger->clear_logs();
        
        wp_send_json_success(array(
            'message' => 'همه لاگ‌ها پاک شدند'
        ));
    }
    
    /**
     * Logout user
     */
    public function logout() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'apl_login_nonce')) {
            wp_die('Security check failed');
        }
        
        if (is_user_logged_in()) {
            $current_user = wp_get_current_user();
            $mobile_number = get_user_meta($current_user->ID, 'apl_mobile_number', true);
            
            wp_logout();
        }
        
        wp_send_json_success(array(
            'message' => 'با موفقیت از سیستم خارج شدید'
        ));
    }
    
    /**
     * Update user profile
     */
    public function update_profile() {
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array(
                'message' => 'لطفاً ابتدا وارد شوید'
            ));
        }
        
        $current_user = wp_get_current_user();
        
        // Get and sanitize form data
        $first_name = sanitize_text_field($_POST['first_name']);
        $last_name = sanitize_text_field($_POST['last_name']);
        $email = sanitize_email($_POST['email']);
        $mobile = sanitize_text_field($_POST['mobile']);
        $national_id = sanitize_text_field($_POST['national_id']);
        $address = sanitize_textarea_field($_POST['address']);
        
        // Check if email and mobile are readonly (already set)
        $existing_email = $current_user->user_email;
        $existing_mobile = get_user_meta($current_user->ID, 'apl_mobile_number', true);
        $is_email_readonly = !empty($existing_email);
        $is_mobile_readonly = !empty($existing_mobile);
        
        // Validation
        if (empty($first_name)) {
            wp_send_json_error(array(
                'message' => 'نام الزامی است',
                'field' => 'profileFirstName'
            ));
        }
        
        if (strlen($first_name) < 2) {
            wp_send_json_error(array(
                'message' => 'نام باید حداقل ۲ کاراکتر باشد',
                'field' => 'profileFirstName'
            ));
        }
        
        if (empty($last_name)) {
            wp_send_json_error(array(
                'message' => 'نام خانوادگی الزامی است',
                'field' => 'profileLastName'
            ));
        }
        
        if (strlen($last_name) < 2) {
            wp_send_json_error(array(
                'message' => 'نام خانوادگی باید حداقل ۲ کاراکتر باشد',
                'field' => 'profileLastName'
            ));
        }
        
        // Check email (only if not readonly)
        if (!$is_email_readonly) {
            if (empty($email)) {
                wp_send_json_error(array(
                    'message' => 'ایمیل الزامی است',
                    'field' => 'profileEmail'
                ));
            }
            
            if (!is_email($email)) {
                wp_send_json_error(array(
                    'message' => 'فرمت ایمیل صحیح نیست',
                    'field' => 'profileEmail'
                ));
            }
            
            // Check if email is already used by another user
            $existing_user_by_email = get_user_by('email', $email);
            if ($existing_user_by_email && $existing_user_by_email->ID != $current_user->ID) {
                wp_send_json_error(array(
                    'message' => 'این ایمیل قبلاً توسط کاربر دیگری استفاده شده است',
                    'field' => 'profileEmail'
                ));
            }
        }
        
        // Check mobile (only if not readonly)
        if (!$is_mobile_readonly) {
            if (empty($mobile)) {
                wp_send_json_error(array(
                    'message' => 'شماره موبایل الزامی است.'.$mobile,
                    'field' => 'profileMobile'
                ));
            }
            
            if (!preg_match('/^09\d{9}$/', $mobile)) {
                wp_send_json_error(array(
                    'message' => 'شماره موبایل باید با ۰۹ شروع شود و ۱۱ رقم باشد',
                    'field' => 'profileMobile'
                ));
            }
            
            // Check if mobile is already used by another user
            $existing_user_by_mobile = get_user_by('login', $mobile);
            if ($existing_user_by_mobile && $existing_user_by_mobile->ID != $current_user->ID) {
                wp_send_json_error(array(
                    'message' => 'این شماره موبایل قبلاً توسط کاربر دیگری استفاده شده است',
                    'field' => 'profileMobile'
                ));
            }
        }
        
        if (empty($national_id)) {
            wp_send_json_error(array(
                'message' => 'کد ملی الزامی است',
                'field' => 'profileNationalId'
            ));
        }
        
        if (!preg_match('/^\d{10}$/', $national_id)) {
            wp_send_json_error(array(
                'message' => 'کد ملی باید دقیقاً ۱۰ رقم باشد',
                'field' => 'profileNationalId'
            ));
        }
        
        if (empty($address)) {
            wp_send_json_error(array(
                'message' => 'آدرس الزامی است',
                'field' => 'profileAddress'
            ));
        }
        
        if (strlen($address) < 10) {
            wp_send_json_error(array(
                'message' => 'آدرس باید حداقل ۱۰ کاراکتر باشد',
                'field' => 'profileAddress'
            ));
        }
        
        // Check if national ID is already used by another user
        $existing_user_by_national_id = get_users(array(
            'meta_key' => 'apl_national_id',
            'meta_value' => $national_id,
            'exclude' => array($current_user->ID)
        ));
        
        if (!empty($existing_user_by_national_id)) {
            wp_send_json_error(array(
                'message' => 'کد ملی قبلاً توسط کاربر دیگری استفاده شده است',
                'field' => 'profileNationalId'
            ));
        }
        
        // Update user data
        $user_data = array(
            'ID' => $current_user->ID,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'display_name' => $first_name . ' ' . $last_name
        );
        
        // Update email if not readonly
        if (!$is_email_readonly) {
            $user_data['user_email'] = $email;
        }
        
        $result = wp_update_user($user_data);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array(
                'message' => 'خطا در به‌روزرسانی اطلاعات: ' . $result->get_error_message()
            ));
        }
        
        // Update user meta
        if (!empty($national_id)) {
            update_user_meta($current_user->ID, 'apl_national_id', $national_id);
        }
        
        update_user_meta($current_user->ID, 'apl_address', $address);
        
        // Update mobile if not readonly
        if (!$is_mobile_readonly) {
            update_user_meta($current_user->ID, 'apl_mobile_number', $mobile);
        }
        
        // Log the update
        $this->logger->log_profile_update($current_user->ID, 'success', 'پروفایل با موفقیت به‌روزرسانی شد');
        
        wp_send_json_success(array(
            'message' => 'اطلاعات پروفایل با موفقیت به‌روزرسانی شد'
        ));
    }
    
    /**
     * Get user orders for dashboard orders section
     */
    public function get_user_orders() {
        // Ensure APL_Gregorian_Jalali class is loaded
        if (!class_exists('APL_Gregorian_Jalali')) {
            require_once ARTA_POYESHLAB_PLUGIN_DIR . 'include/classes/apl-gregorian_jalali.php';
        }
        
        // Check if user is logged in first
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'کاربر وارد نشده است'));
        }
        
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'apl_dashboard_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'));
        }
        
        try {
            $current_user = wp_get_current_user();
            
            // Get WooCommerce orders for current user
            $orders = wc_get_orders(array(
                'customer_id' => $current_user->ID,
                'limit' => 50, // Limit to 50 orders
                'orderby' => 'date',
                'order' => 'DESC'
            ));
            
            $formatted_orders = array();
            
            foreach ($orders as $order) {
                // Convert order date to Jalali
                $order_date = $order->get_date_created();
                $jalali_date = \APL_Gregorian_Jalali::gregorian_to_jalali(
                    $order_date->format('Y'),
                    $order_date->format('n'),
                    $order_date->format('j'),
                    true
                );
                
                // Get order items with details
                $items = array();
                $items_list = array();
                foreach ($order->get_items() as $item_id => $item) {
                    $product_id = $item->get_product_id();
                    $product = wc_get_product($product_id);
                    
                    $items[] = array(
                        'id' => $product_id,
                        'name' => $item->get_name(),
                        'quantity' => $item->get_quantity(),
                        'total' => $item->get_total(),
                        'price' => $item->get_subtotal() / $item->get_quantity()
                    );
                    
                    $items_list[] = $item->get_name();
                }
                
                // Get APL order meta data
                $request_type = $order->get_meta('_apl_request_type');
                $delivery_method = $order->get_meta('_apl_delivery_method');
                $patient_first_name = $order->get_meta('_apl_patient_first_name');
                $patient_last_name = $order->get_meta('_apl_patient_last_name');
                $patient_national_id = $order->get_meta('_apl_patient_national_id');
                $patient_mobile = $order->get_meta('_apl_patient_mobile');
                $appointment_date = $order->get_meta('_apl_appointment_date');
                $appointment_time = $order->get_meta('_apl_appointment_time');
                $city = $order->get_meta('_apl_city');
                $address = $order->get_meta('_apl_address');
                $discount_code = $order->get_meta('_apl_discount_code');
                
                // Convert appointment date to Jalali if it's in Gregorian format
                $appointment_date_jalali = $appointment_date;
                if (!empty($appointment_date) && strpos($appointment_date, '-') !== false) {
                    // It's in Gregorian format, convert to Jalali
                    $date_parts = explode('-', $appointment_date);
                    if (count($date_parts) === 3) {
                        $appointment_date_jalali = \APL_Gregorian_Jalali::gregorian_to_jalali(
                            intval($date_parts[0]),
                            intval($date_parts[1]),
                            intval($date_parts[2]),
                            true
                        );
                    }
                }
                
                // Format time
                $appointment_time_formatted = $appointment_time;
                if (!empty($appointment_time) && strpos($appointment_time, ':') !== false) {
                    $time_parts = explode(':', $appointment_time);
                    if (count($time_parts) >= 2) {
                        $hour = intval($time_parts[0]);
                        $minute = intval($time_parts[1]);
                        $appointment_time_formatted = sprintf('%02d:%02d', $hour, $minute);
                    }
                }
                
                // Get delivery method label
                $delivery_method_labels = array(
                    'home_sampling' => 'نمونه‌گیری در منزل',
                    'lab_visit' => 'مراجعه به آزمایشگاه',
                    'sample_shipping' => 'ارسال نمونه'
                );
                $delivery_method_label = isset($delivery_method_labels[$delivery_method]) ? $delivery_method_labels[$delivery_method] : $delivery_method;
                
                // Get request type label
                $request_type_labels = array(
                    'upload' => 'بارگذاری نسخه',
                    'electronic' => 'نسخه الکترونیک',
                    'packages' => 'بسته‌های آزمایش'
                );
                $request_type_label = isset($request_type_labels[$request_type]) ? $request_type_labels[$request_type] : $request_type;
                
                // Get city label
                $cities = array(
                    'ardabil' => 'اردبیل',
                    'namin' => 'نمین',
                    'astara' => 'آستارا',
                    'anbaran' => 'عنبران',
                    'abibiglu' => 'ابی بیگلو'
                );
                $city_label = isset($cities[$city]) ? $cities[$city] : $city;
                $full_address = '';
                if ($delivery_method === 'home_sampling' && !empty($address)) {
                    $full_address = !empty($city_label) ? $city_label . '، ' . $address : $address;
                }
                
                // Get order totals breakdown
                $subtotal = $order->get_subtotal();
                $discount_total = $order->get_total_discount();
                $shipping_total = $order->get_shipping_total();
                $fees = array();
                foreach ($order->get_fees() as $fee_id => $fee) {
                    $fees[] = array(
                        'name' => $fee->get_name(),
                        'total' => $fee->get_total()
                    );
                }
                
                // Get billing information
                $billing = array(
                    'first_name' => $order->get_billing_first_name(),
                    'last_name' => $order->get_billing_last_name(),
                    'email' => $order->get_billing_email(),
                    'phone' => $order->get_billing_phone(),
                    'address' => $order->get_formatted_billing_address()
                );
                
                // Determine status label and class
                $status_labels = array(
                    'completed' => 'تکمیل شده',
                    'processing' => 'در حال انجام',
                    'on-hold' => 'در انتظار بررسی',
                    'pending' => 'در انتظار پرداخت',
                    'cancelled' => 'لغو شده',
                    'refunded' => 'بازگشت وجه',
                    'failed' => 'ناموفق'
                );
                $status_label = isset($status_labels[$order->get_status()]) ? $status_labels[$order->get_status()] : $order->get_status();
                $status_class = 'status-' . $order->get_status();
                
                // Check if payment is needed
                $needs_payment = $order->needs_payment();
                $payment_status = $order->is_paid() ? 'paid' : ($needs_payment ? 'pending' : 'none');
                
                $formatted_orders[] = array(
                    'id' => $order->get_id(),
                    'number' => $order->get_order_number(),
                    'status' => $order->get_status(),
                    'status_label' => $status_label,
                    'status_class' => $status_class,
                    'total' => $order->get_total(),
                    'currency' => $order->get_currency(),
                    'currency_symbol' => get_woocommerce_currency_symbol($order->get_currency()),
                    'date' => $jalali_date,
                    'items' => $items,
                    'items_list' => $items_list,
                    'items_count' => count($items),
                    'billing' => $billing,
                    'payment_url' => $order->get_checkout_payment_url(),
                    'payment_status' => $payment_status,
                    'needs_payment' => $needs_payment,
                    'order_key' => $order->get_order_key(),
                    // APL specific data
                    'request_type' => $request_type,
                    'request_type_label' => $request_type_label,
                    'delivery_method' => $delivery_method,
                    'delivery_method_label' => $delivery_method_label,
                    'patient_first_name' => $patient_first_name,
                    'patient_last_name' => $patient_last_name,
                    'patient_name' => trim($patient_first_name . ' ' . $patient_last_name),
                    'patient_national_id' => $patient_national_id,
                    'patient_mobile' => $patient_mobile,
                    'appointment_date' => $appointment_date_jalali,
                    'appointment_time' => $appointment_time_formatted,
                    'appointment_datetime' => !empty($appointment_date_jalali) && !empty($appointment_time_formatted) ? $appointment_date_jalali . ' ساعت ' . $appointment_time_formatted : '',
                    'city' => $city,
                    'city_label' => $city_label,
                    'address' => $address,
                    'full_address' => $full_address,
                    'discount_code' => $discount_code,
                    // Pricing breakdown
                    'subtotal' => $subtotal,
                    'discount_total' => abs($discount_total),
                    'shipping_total' => $shipping_total,
                    'fees' => $fees,
                    'invoice_url' => add_query_arg(array(
                        'apl_action' => 'view_invoice',
                        'order_id' => $order->get_id(),
                        'nonce' => wp_create_nonce('apl_invoice_' . $order->get_id())
                    ), home_url())
                );
            }
            
            wp_send_json_success(array(
                'orders' => $formatted_orders
            ));
            
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => 'خطا در دریافت سفارشات: ' . $e->getMessage(),
                'debug' => WP_DEBUG ? $e->getTraceAsString() : ''
            ));
        }
    }
    
    /**
     * Download invoice PDF
     */
    public function download_invoice_pdf() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'apl_dashboard_nonce')) {
            wp_die('Security check failed');
        }
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'کاربر وارد نشده است'));
        }
        
        $order_id = intval($_POST['order_id']);
        $current_user = wp_get_current_user();
        
        // Get the order
        $order = wc_get_order($order_id);
        
        if (!$order) {
            wp_send_json_error(array('message' => 'سفارش یافت نشد'));
        }
        
        // Check if user owns this order
        if ($order->get_customer_id() != $current_user->ID) {
            wp_send_json_error(array('message' => 'شما دسترسی به این سفارش ندارید'));
        }
        
        // Check if order is completed or processing
        if (!in_array($order->get_status(), array('completed', 'processing'))) {
            wp_send_json_error(array('message' => 'فاکتور فقط برای سفارشات تکمیل شده یا در حال پردازش قابل دانلود است'));
        }
        
        // Include PDF generator class
        if (!class_exists('APL_PDF_Generator')) {
            require_once ARTA_POYESHLAB_PLUGIN_DIR . 'include/classes/apl-pdf-generator.php';
        }
        
        // Generate PDF and return URL
        $pdf_generator = new APL_PDF_Generator();
        $pdf_url = $pdf_generator->generate_invoice_pdf($order_id);
        
        wp_send_json_success(array(
            'pdf_url' => $pdf_url
        ));
    }
    
    /**
     * Upload profile picture
     */
    public function upload_profile_picture() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'apl_profile_picture_nonce')) {
            wp_send_json_error(array('message' => 'درخواست نامعتبر است - nonce verification failed'));
        }
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'کاربر وارد نشده است'));
        }
        
        $current_user = wp_get_current_user();
        
        // Check if file was uploaded
        if (!isset($_FILES['profile_picture']) || $_FILES['profile_picture']['error'] !== UPLOAD_ERR_OK) {
            $error_message = "مشکلی در آپلود فایل رخ داده است";
            if (isset($_FILES['profile_picture']['error'])) {
                switch ($_FILES['profile_picture']['error']) {
                    case UPLOAD_ERR_INI_SIZE:
                    case UPLOAD_ERR_FORM_SIZE:
                        $error_message = 'فایل خیلی بزرگ است';
                        break;
                    case UPLOAD_ERR_PARTIAL:
                        $error_message = 'فایل به طور کامل آپلود نشد';
                        break;
                    case UPLOAD_ERR_NO_FILE:
                        $error_message = 'هیچ فایلی انتخاب نشده است';
                        break;
                    case UPLOAD_ERR_NO_TMP_DIR:
                        $error_message = 'پوشه موقت وجود ندارد';
                        break;
                    case UPLOAD_ERR_CANT_WRITE:
                        $error_message = 'خطا در نوشتن فایل';
                        break;
                    case UPLOAD_ERR_EXTENSION:
                        $error_message = 'آپلود توسط extension متوقف شد';
                        break;
                }
            }
            wp_send_json_error(array('message' => $error_message));
        }
        
        $file = $_FILES['profile_picture'];
        
        // Validate file type
        $allowed_types = array('image/jpeg', 'image/jpg', 'image/png', 'image/gif');
        $file_type = wp_check_filetype($file['name']);
        
        if (!in_array($file['type'], $allowed_types) || !in_array($file_type['type'], $allowed_types)) {
            wp_send_json_error(array('message' => 'فرمت فایل مجاز نیست. فقط JPG، PNG و GIF مجاز است'));
        }
        
        // Validate file size (5MB max)
        $max_size = 5 * 1024 * 1024; // 5MB in bytes
        if ($file['size'] > $max_size) {
            wp_send_json_error(array('message' => 'حجم فایل بیش از 5 مگابایت است'));
        }
        
        // Create upload directory if it doesn't exist
        $upload_dir = wp_upload_dir();
        $profile_pictures_dir = $upload_dir['basedir'] . '/profile-pictures';
        
        if (!file_exists($profile_pictures_dir)) {
            wp_mkdir_p($profile_pictures_dir);
        }
        
        // Generate unique filename
        $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'profile_' . $current_user->ID . '_' . time() . '.' . $file_extension;
        $file_path = $profile_pictures_dir . '/' . $filename;
        
        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $file_path)) {
            wp_send_json_error(array('message' => 'خطا در ذخیره فایل'));
        }
        
        // Resize image to 150x150
        $image = wp_get_image_editor($file_path);
        if (!is_wp_error($image)) {
            $image->resize(150, 150, true); // true for crop
            $image->save($file_path);
        }
        
        // Get the old profile picture to delete it
        $old_profile_picture = get_user_meta($current_user->ID, 'apl_profile_picture', true);
        if (!empty($old_profile_picture)) {
            $old_file_path = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $old_profile_picture);
            if (file_exists($old_file_path)) {
                unlink($old_file_path);
            }
        }
        
        // Save new profile picture URL
        $file_url = $upload_dir['baseurl'] . '/profile-pictures/' . $filename;
        update_user_meta($current_user->ID, 'apl_profile_picture', $file_url);
        
        wp_send_json_success(array(
            'message' => 'عکس پروفایل با موفقیت آپلود شد',
            'image_url' => $file_url
        ));
    }
    
    /**
     * Remove profile picture
     */
    public function remove_profile_picture() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'apl_profile_picture_nonce')) {
            wp_send_json_error(array('message' => 'درخواست نامعتبر است'));
        }
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'کاربر وارد نشده است'));
        }
        
        $current_user = wp_get_current_user();
        
        // Get current profile picture
        $profile_picture = get_user_meta($current_user->ID, 'apl_profile_picture', true);
        
        if (!empty($profile_picture)) {
            // Delete file from server
            $upload_dir = wp_upload_dir();
            $file_path = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $profile_picture);
            if (file_exists($file_path)) {
                unlink($file_path);
            }
            
            // Remove from user meta
            delete_user_meta($current_user->ID, 'apl_profile_picture');
        }
        
        wp_send_json_success(array('message' => 'عکس پروفایل حذف شد'));
    }
    
    /**
     * Validate discount code
     */
    public function validate_discount_code() {
        if (!isset($_POST['discount_code']) || empty($_POST['discount_code'])) {
            wp_send_json_error(array('message' => 'کد تخفیف را وارد کنید'));
        }
        
        $discount_code = sanitize_text_field($_POST['discount_code']);
        $subtotal = isset($_POST['subtotal']) ? floatval($_POST['subtotal']) : 0;
        
        // Check if WooCommerce is active and use coupon system
        if (function_exists('wc_get_coupon_id_by_code')) {
            $coupon_id = wc_get_coupon_id_by_code($discount_code);
            
            if ($coupon_id) {
                $coupon = new \WC_Coupon($coupon_id);
                
                // Check if coupon is valid
                if (!$coupon->is_valid()) {
                    wp_send_json_error(array('message' => 'کد تخفیف معتبر نیست یا منقضی شده است'));
                }
                
                // Get discount type and value
                $discount_type = $coupon->get_discount_type();
                $discount_value = $coupon->get_amount();
                
                // Calculate discount amount
                $discount_amount = 0;
                $type = 'fixed';
                $value = 0;
                $description = '';
                
                if ($discount_type === 'percent') {
                    // Percentage discount
                    $discount_amount = ($subtotal * $discount_value) / 100;
                    $type = 'percentage';
                    $value = floatval($discount_value);
                    $description = sprintf('تخفیف %d%%', $value);
                } elseif (in_array($discount_type, array('fixed_cart', 'fixed_product'))) {
                    // Fixed amount discount
                    $discount_amount = floatval($discount_value);
                    $type = 'fixed';
                    $value = 0;
                    $description = sprintf('تخفیف %s تومان', number_format($discount_amount, 0, '.', ','));
                }
                
                wp_send_json_success(array(
                    'type' => $type,
                    'value' => $value,
                    'amount' => round($discount_amount),
                    'description' => $description,
                    'coupon_id' => $coupon_id
                ));
            }
        }
        
        // Fallback: Simple discount codes for demo/testing
        $valid_codes = array(
            'WELCOME10' => array('type' => 'percentage', 'value' => 10),
            'WELCOME20' => array('type' => 'percentage', 'value' => 20),
            'HEALTH50' => array('type' => 'fixed', 'amount' => 50000),
            'FAMILY30' => array('type' => 'percentage', 'value' => 30),
            'SAMPLE100' => array('type' => 'fixed', 'amount' => 100000),
        );
        
        $code_upper = strtoupper($discount_code);
        
        if (isset($valid_codes[$code_upper])) {
            $code_data = $valid_codes[$code_upper];
            $discount_amount = 0;
            $description = '';
            
            if ($code_data['type'] === 'percentage') {
                $discount_amount = ($subtotal * $code_data['value']) / 100;
                $description = sprintf('تخفیف %d%%', $code_data['value']);
            } else {
                $discount_amount = $code_data['amount'];
                $description = sprintf('تخفیف %s تومان', number_format($discount_amount, 0, '.', ','));
            }
            
            wp_send_json_success(array(
                'type' => $code_data['type'],
                'value' => $code_data['type'] === 'percentage' ? $code_data['value'] : 0,
                'amount' => round($discount_amount),
                'description' => $description
            ));
        }
        
        wp_send_json_error(array('message' => 'کد تخفیف نامعتبر است'));
    }
    
    /**
     * Create WooCommerce order from form data
     */
    public function create_order() {
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'لطفاً ابتدا وارد شوید'));
            return;
        }

        // Check if WooCommerce is active
        if (!function_exists('wc_create_order')) {
            wp_send_json_error(array('message' => 'WooCommerce فعال نیست'));
            return;
        }

        try {
            $current_user = wp_get_current_user();
            
            // Collect all form data
            $request_type = isset($_POST['request_type']) ? sanitize_text_field($_POST['request_type']) : '';
            $delivery_method = isset($_POST['delivery_method']) ? sanitize_text_field($_POST['delivery_method']) : '';
            $patient_first_name = isset($_POST['patient_first_name']) ? sanitize_text_field($_POST['patient_first_name']) : '';
            $patient_last_name = isset($_POST['patient_last_name']) ? sanitize_text_field($_POST['patient_last_name']) : '';
            $patient_national_id = isset($_POST['patient_national_id']) ? sanitize_text_field($_POST['patient_national_id']) : '';
            $patient_mobile = isset($_POST['patient_mobile']) ? sanitize_text_field($_POST['patient_mobile']) : '';
            $appointment_date = isset($_POST['appointment_date']) ? sanitize_text_field($_POST['appointment_date']) : '';
            $appointment_time = isset($_POST['appointment_time']) ? sanitize_text_field($_POST['appointment_time']) : '';
            $city = isset($_POST['city']) ? sanitize_text_field($_POST['city']) : '';
            $address = isset($_POST['address']) ? sanitize_textarea_field($_POST['address']) : '';
            $basic_insurance = isset($_POST['basic_insurance']) ? sanitize_text_field($_POST['basic_insurance']) : '';
            $supplementary_insurance = isset($_POST['supplementary_insurance']) ? sanitize_text_field($_POST['supplementary_insurance']) : '';
            $insurance_tracking_code = isset($_POST['insurance_tracking_code']) ? sanitize_text_field($_POST['insurance_tracking_code']) : '';
            $discount_code = isset($_POST['discount_code']) ? sanitize_text_field($_POST['discount_code']) : '';
            
            // Get packages data
            $packages = array();
            if (isset($_POST['packages']) && is_array($_POST['packages'])) {
                foreach ($_POST['packages'] as $pkg) {
                    if (isset($pkg['id']) && isset($pkg['price'])) {
                        $packages[] = array(
                            'id' => intval($pkg['id']),
                            'name' => isset($pkg['name']) ? sanitize_text_field($pkg['name']) : '',
                            'price' => floatval($pkg['price'])
                        );
                    }
                }
            }
            
            // Get electronic prescription data
            $electronic_national_id = isset($_POST['electronic_national_id']) ? sanitize_text_field($_POST['electronic_national_id']) : '';
            $doctor_name = isset($_POST['doctor_name']) ? sanitize_text_field($_POST['doctor_name']) : '';
            
            // Handle file uploads (if any)
            $uploaded_files = array();
            if (!empty($_FILES)) {
                require_once(ABSPATH . 'wp-admin/includes/file.php');
                require_once(ABSPATH . 'wp-admin/includes/media.php');
                require_once(ABSPATH . 'wp-admin/includes/image.php');
                
                // Handle multiple files with indexed notation (prescription_files_0, prescription_files_1, etc.)
                foreach ($_FILES as $key => $file_data) {
                    if (strpos($key, 'prescription_files_') === 0) {
                        // Single file with indexed key
                        if (!empty($file_data['name']) && $file_data['error'] === UPLOAD_ERR_OK) {
                            $upload = wp_handle_upload($file_data, array('test_form' => false));
                            if (!isset($upload['error']) && isset($upload['url'])) {
                                $uploaded_files[] = $upload['url'];
                            }
                        }
                    }
                }
            }
            
            // Create WooCommerce order
            $order = wc_create_order();
            
            if (!$order) {
                wp_send_json_error(array('message' => 'خطا در ایجاد سفارش'));
                return;
            }
            
            // Add products (packages) to order
            $order_total = 0;
            foreach ($packages as $pkg) {
                $product = wc_get_product($pkg['id']);
                if ($product) {
                    $order->add_product($product, 1);
                    $order_total += $pkg['price'];
                }
            }
            
            // Set customer information
            $order->set_customer_id($current_user->ID);
            
            // Set billing address
            $order->set_billing_first_name($patient_first_name);
            $order->set_billing_last_name($patient_last_name);
            $order->set_billing_phone($patient_mobile);
            $order->set_billing_email($current_user->user_email);
            
            // Set billing address
            if ($delivery_method === 'home_sampling' && $address) {
                $full_address = '';
                if ($city) {
                    $cities = array(
                        'ardabil' => 'اردبیل',
                        'namin' => 'نمین',
                        'astara' => 'آستارا',
                        'anbaran' => 'عنبران',
                        'abibiglu' => 'ابی بیگلو'
                    );
                    $city_name = isset($cities[$city]) ? $cities[$city] : $city;
                    $full_address = $city_name . '، ' . $address;
                } else {
                    $full_address = $address;
                }
                $order->set_billing_address_1($full_address);
            }
            
            // Calculate totals
            $order->calculate_totals();
            
            // Apply discount code if provided
            if (!empty($discount_code)) {
                $coupon_id = wc_get_coupon_id_by_code($discount_code);
                if ($coupon_id) {
                    $order->apply_coupon($discount_code);
                    $order->calculate_totals();
                }
            }
            
            // Save all form data as order meta
            $order->update_meta_data('_apl_request_type', $request_type);
            $order->update_meta_data('_apl_delivery_method', $delivery_method);
            $order->update_meta_data('_apl_patient_first_name', $patient_first_name);
            $order->update_meta_data('_apl_patient_last_name', $patient_last_name);
            $order->update_meta_data('_apl_patient_national_id', $patient_national_id);
            $order->update_meta_data('_apl_patient_mobile', $patient_mobile);
            $order->update_meta_data('_apl_appointment_date', $appointment_date);
            $order->update_meta_data('_apl_appointment_time', $appointment_time);
            $order->update_meta_data('_apl_city', $city);
            $order->update_meta_data('_apl_address', $address);
            $order->update_meta_data('_apl_basic_insurance', $basic_insurance);
            $order->update_meta_data('_apl_supplementary_insurance', $supplementary_insurance);
            $order->update_meta_data('_apl_insurance_tracking_code', $insurance_tracking_code);
            $order->update_meta_data('_apl_discount_code', $discount_code);
            $order->update_meta_data('_apl_electronic_national_id', $electronic_national_id);
            $order->update_meta_data('_apl_doctor_name', $doctor_name);
            if (!empty($uploaded_files)) {
                $order->update_meta_data('_apl_prescription_files', $uploaded_files);
            }
            
            // Set order status to on-hold
            $order->update_status('on-hold', 'سفارش در انتظار بررسی');
            
            // Save order first
            $order->save();
            
            // Update appointment status if date and time are provided
            // Do this after saving order to ensure order_id is available
            if (!empty($appointment_date) && !empty($appointment_time) && !empty($delivery_method)) {
                $this->update_appointment_status($appointment_date, $appointment_time, $delivery_method, $order->get_id());
            }
            
            wp_send_json_success(array(
                'message' => 'سفارش با موفقیت ثبت شد',
                'order_id' => $order->get_id(),
                'order_number' => $order->get_order_number()
            ));
            
        } catch (\Exception $e) {
            wp_send_json_error(array('message' => 'خطا در ثبت سفارش: ' . $e->getMessage()));
        }
    }
    
    /**
     * Convert Persian/Farsi numbers to English numbers
     */
    private function convert_persian_to_english_numbers($text) {
        if (empty($text)) {
            return '';
        }
        
        $text = (string)$text;
        
        // Persian digits
        $persian_digits = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        // Arabic digits
        $arabic_digits = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
        // English digits
        $english_digits = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        
        // Replace Persian digits
        for ($i = 0; $i < 10; $i++) {
            $text = str_replace($persian_digits[$i], $english_digits[$i], $text);
        }
        
        // Replace Arabic digits
        for ($i = 0; $i < 10; $i++) {
            $text = str_replace($arabic_digits[$i], $english_digits[$i], $text);
        }
        
        return $text;
    }
    
    /**
     * Update appointment status from available to booked
     */
    private function update_appointment_status($appointment_date, $appointment_time, $service_delivery_method, $order_id) {
        global $wpdb;
        
        // Convert Persian numbers to English numbers first
        $appointment_date = $this->convert_persian_to_english_numbers($appointment_date);
        $appointment_time = $this->convert_persian_to_english_numbers($appointment_time);
        
        // Convert Persian date to Gregorian if needed
        // Use the same method as APL_Appointments class
        $original_date = $appointment_date;
        if (strpos($appointment_date, '/') !== false) {
            $date_parts = explode('/', $appointment_date);
            if (count($date_parts) === 3) {
                $year = intval($date_parts[0]);
                $month = intval($date_parts[1]);
                $day = intval($date_parts[2]);
                
                // Use APL_Gregorian_Jalali class (it's in global namespace, not namespaced)
                if (class_exists('\APL_Gregorian_Jalali')) {
                    $result = \APL_Gregorian_Jalali::jalali_to_gregorian($year, $month, $day, false);
                    if (is_array($result) && count($result) === 3) {
                        $appointment_date = sprintf('%04d-%02d-%02d', $result[0], $result[1], $result[2]);
                    }
                } else {
                    // Fallback approximation
                    $appointment_date = sprintf('%04d-%02d-%02d', $year + 621, ($month + 3 > 12 ? $month - 9 : $month + 3), $day);
                }
            }
        }
        
        if (!$appointment_date || strpos($appointment_date, '/') !== false) {
            return false;
        }
        
        // Format time to HH:MM:SS
        $time_parts = explode(':', $appointment_time);
        if (count($time_parts) >= 2) {
            $appointment_time = sprintf('%02d:%02d:00', intval($time_parts[0]), intval($time_parts[1]));
        } else {
            return false;
        }
        
        $table_name = $wpdb->prefix . 'apl_appointments';
        
        // Find the appointment with available status
        $appointment = $wpdb->get_row($wpdb->prepare(
            "SELECT id, status FROM {$table_name} 
            WHERE appointment_date = %s 
            AND appointment_time = %s 
            AND service_delivery_method = %s 
            AND status = 'available' 
            LIMIT 1",
            $appointment_date,
            $appointment_time,
            $service_delivery_method
        ));
        
        if ($appointment) {
            // Update appointment status to booked
            $result = $wpdb->update(
                $table_name,
                array('status' => 'booked'),
                array('id' => $appointment->id),
                array('%s'),
                array('%d')
            );
            
            if ($result !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Add insurance fee to order
     */
    public function add_insurance_fee() {
        // بررسی دسترسی ادمین
        if (!current_user_can('manage_woocommerce') && !current_user_can('edit_shop_orders')) {
            wp_send_json_error(array(
                'message' => 'شما دسترسی لازم برای انجام این عملیات را ندارید.'
            ));
        }
        
        // بررسی nonce (اختیاری برای ادمین، اما بهتر است بررسی شود)
        if (isset($_POST['nonce']) && !empty($_POST['nonce'])) {
            if (!wp_verify_nonce($_POST['nonce'], 'apl_dashboard_nonce')) {
                wp_send_json_error(array(
                    'message' => 'خطای امنیتی. لطفاً صفحه را رفرش کنید.'
                ));
            }
        }
        
        // دریافت و اعتبارسنجی داده‌ها
        $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
        $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
        
        if ($order_id <= 0) {
            wp_send_json_error(array(
                'message' => 'شناسه سفارش معتبر نیست.'
            ));
        }
        
        if ($amount <= 0) {
            wp_send_json_error(array(
                'message' => 'مبلغ حق بیمه باید بزرگ‌تر از صفر باشد.'
            ));
        }
        
        // دریافت سفارش
        $order = wc_get_order($order_id);
        
        if (!$order) {
            wp_send_json_error(array(
                'message' => 'سفارش پیدا نشد.'
            ));
        }
        
        // افزودن Fee به سفارش
        $fee = new \WC_Order_Item_Fee();
        $fee->set_name('حق بیمه');
        $fee->set_amount(-abs($amount)); // منفی برای کسر از مبلغ کل
        $fee->set_total(-abs($amount));
        
        $order->add_item($fee);
        $order->calculate_totals();
        $order->save();
        
        wp_send_json_success(array(
            'message' => 'حق بیمه به مبلغ ' . number_format($amount) . ' تومان به سفارش اضافه شد.',
            'order_id' => $order_id
        ));
    }
}

// Initialize AJAX handlers
new APL_Ajax_Handlers();
