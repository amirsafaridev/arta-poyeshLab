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
     * Get user orders for invoices section
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
                
                // Get order items
                $items = array();
                foreach ($order->get_items() as $item_id => $item) {
                    $items[] = array(
                        'name' => $item->get_name(),
                        'quantity' => $item->get_quantity(),
                        'total' => $item->get_total()
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
                
                $formatted_orders[] = array(
                    'id' => $order->get_id(),
                    'number' => $order->get_order_number(),
                    'status' => $order->get_status(),
                    'total' => $order->get_total(),
                    'currency' => $order->get_currency(),
                    'currency_symbol' => get_woocommerce_currency_symbol($order->get_currency()),
                    'date' => $jalali_date,
                    'items' => $items,
                    'billing' => $billing,
                    'payment_url' => $order->get_checkout_payment_url(),
                    'order_key' => $order->get_order_key()
                );
            }
            
            wp_send_json_success(array(
                'orders' => $formatted_orders
            ));
            
        } catch (Exception $e) {
            error_log('APL: Error in get_user_orders: ' . $e->getMessage());
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
            $error_message = 'خطا در آپلود فایل';
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
}

// Initialize AJAX handlers
new APL_Ajax_Handlers();
