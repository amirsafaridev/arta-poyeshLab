<?php
/**
 * Order Meta Display Class
 * 
 * @package ArtaPoyeshLab
 * @since 1.0.0
 */

namespace APL\Classes;

if (!defined('ABSPATH')) {
    exit;
}

class APL_Order_Meta {
    
    public function __construct() {
        // Use WooCommerce specific hook to display form data
        // This works with both classic post type and HPOS (High-Performance Order Storage)
        add_action('woocommerce_admin_order_data_after_order_details', array($this, 'display_order_form_data_wc'), 20);
        
        // Also try to add as meta box for classic post type
        add_action('add_meta_boxes', array($this, 'add_order_meta_box'), 10, 2);
        
        // Save order meta data
        add_action('woocommerce_process_shop_order_meta', array($this, 'save_order_form_data'), 30, 2);
        
        // Enqueue admin styles
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_styles'));
        add_action('admin_head', array($this, 'add_inline_styles_to_head'));
        
        // Enqueue admin scripts for dynamic field visibility
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }
    
    /**
     * Add meta box to order edit page (for classic post type)
     */
    public function add_order_meta_box($post_type, $post) {
        // Only add if it's shop_order post type
        if ($post_type === 'shop_order') {
            add_meta_box(
                'apl-order-form-data',
                '<span class="dashicons dashicons-clipboard" style="vertical-align: middle; margin-top: 3px;"></span> اطلاعات فرم سفارش',
                array($this, 'display_order_form_data'),
                'shop_order',
                'normal',
                'high'
            );
        }
    }
    
    /**
     * Add inline styles to head
     */
    public function add_inline_styles_to_head() {
        $screen = get_current_screen();
        if (!$screen) {
            return;
        }
        
        // Check if we're on WooCommerce order page
        $is_order_page = false;
        if (isset($screen->post_type) && $screen->post_type === 'shop_order') {
            $is_order_page = true;
        } elseif (function_exists('wc_get_page_screen_id') && $screen->id === wc_get_page_screen_id('shop_order')) {
            $is_order_page = true;
        }
        
        if ($is_order_page) {
            echo $this->get_inline_styles();
        }
    }
    
    /**
     * Enqueue admin styles
     */
    public function enqueue_admin_styles($hook) {
        // Check for WooCommerce order edit page
        $screen = get_current_screen();
        if (($hook === 'post.php' || $hook === 'post-new.php') && isset($screen) && $screen->post_type === 'shop_order') {
            wp_add_inline_style('wp-admin', $this->get_inline_styles());
        } elseif (function_exists('wc_get_page_screen_id') && isset($screen) && $screen->id === wc_get_page_screen_id('shop_order')) {
            wp_add_inline_style('wp-admin', $this->get_inline_styles());
        }
    }
    
    /**
     * Enqueue admin scripts for dynamic field visibility
     */
    public function enqueue_admin_scripts($hook) {
        // Check for WooCommerce order edit page
        $screen = get_current_screen();
        $is_order_page = false;
        
        if (($hook === 'post.php' || $hook === 'post-new.php') && isset($screen) && $screen->post_type === 'shop_order') {
            $is_order_page = true;
        } elseif (function_exists('wc_get_page_screen_id') && isset($screen) && $screen->id === wc_get_page_screen_id('shop_order')) {
            $is_order_page = true;
        }
        
        if ($is_order_page) {
            // Ensure jQuery is loaded
            wp_enqueue_script('jquery');
            wp_add_inline_script('jquery', $this->get_admin_script());
        }
    }
    
    /**
     * Get admin JavaScript for dynamic field visibility
     */
    private function get_admin_script() {
        return "
        jQuery(document).ready(function($) {
            // Handle delivery method change
            function toggleFieldsByDeliveryMethod() {
                var deliveryMethod = $('#_apl_delivery_method').val();
                
                // Show/hide city and address fields based on delivery method
                if (deliveryMethod === 'home_sampling') {
                    $('#apl_city_field_wrapper, #apl_address_field_wrapper').show();
                } else {
                    $('#apl_city_field_wrapper, #apl_address_field_wrapper').hide();
                }
            }
            
            // Handle request type change
            function toggleFieldsByRequestType() {
                var requestType = $('#_apl_request_type').val();
                
                // Show/hide electronic prescription fields
                if (requestType === 'electronic') {
                    $('#_apl_electronic_national_id_field, #_apl_doctor_name_field').show();
                } else {
                    $('#_apl_electronic_national_id_field, #_apl_doctor_name_field').hide();
                }
            }
            
            // Initial call
            toggleFieldsByDeliveryMethod();
            toggleFieldsByRequestType();
            
            // Bind change events
            $('#_apl_delivery_method').on('change', toggleFieldsByDeliveryMethod);
            $('#_apl_request_type').on('change', toggleFieldsByRequestType);
        });
        ";
    }
    
    /**
     * Display form data using WooCommerce hook (primary method - works with HPOS)
     */
    public function display_order_form_data_wc($order) {
        if (!is_a($order, 'WC_Order')) {
            return;
        }
        
        // Display the form data using WooCommerce structure
        $this->render_order_form_data($order);
    }
    
    /**
     * Display form data in meta box
     */
    public function display_order_form_data($post) {
        // Get order object
        $order_id = is_object($post) ? $post->ID : $post;
        $order = wc_get_order($order_id);
        
        if (!$order) {
            return;
        }
        
        $this->render_order_form_data($order);
    }
    
    /**
     * Render order form data (common method)
     */
    private function render_order_form_data($order) {
        if (!is_a($order, 'WC_Order')) {
            return;
        }
        
        // Get all APL order meta data
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
        $basic_insurance = $order->get_meta('_apl_basic_insurance');
        $supplementary_insurance = $order->get_meta('_apl_supplementary_insurance');
        $insurance_tracking_code = $order->get_meta('_apl_insurance_tracking_code');
        $discount_code = $order->get_meta('_apl_discount_code');
        $electronic_national_id = $order->get_meta('_apl_electronic_national_id');
        $doctor_name = $order->get_meta('_apl_doctor_name');
        $prescription_files = $order->get_meta('_apl_prescription_files');
        
        ?>
       
                
                <p class="form-field form-field-wide _apl_request_type_field">
                    <label for="_apl_request_type">نوع درخواست</label>
                    <select id="_apl_request_type" name="_apl_request_type" class="select short">
                        <option value="">--</option>
                        <option value="upload" <?php selected($request_type, 'upload'); ?>>بارگذاری نسخه</option>
                        <option value="electronic" <?php selected($request_type, 'electronic'); ?>>نسخه الکترونیک</option>
                        <option value="packages" <?php selected($request_type, 'packages'); ?>>بسته‌های آزمایش</option>
                    </select>
                </p>
                
                <p class="form-field form-field-wide _apl_delivery_method_field">
                    <label for="_apl_delivery_method">نحوه ارائه خدمات</label>
                    <select id="_apl_delivery_method" name="_apl_delivery_method" class="select short">
                        <option value="">--</option>
                        <option value="home_sampling" <?php selected($delivery_method, 'home_sampling'); ?>>نمونه‌گیری در منزل</option>
                        <option value="lab_visit" <?php selected($delivery_method, 'lab_visit'); ?>>مراجعه به آزمایشگاه</option>
                        <option value="sample_shipping" <?php selected($delivery_method, 'sample_shipping'); ?>>ارسال نمونه</option>
                    </select>
                </p>
                
                <p class="form-field form-field-wide _apl_patient_first_name_field">
                    <label for="_apl_patient_first_name">نام بیمار</label>
                    <input type="text" class="short" name="_apl_patient_first_name" id="_apl_patient_first_name" value="<?php echo esc_attr($patient_first_name); ?>" placeholder="">
                </p>
                
                <p class="form-field form-field-wide _apl_patient_last_name_field">
                    <label for="_apl_patient_last_name">نام خانوادگی بیمار</label>
                    <input type="text" class="short" name="_apl_patient_last_name" id="_apl_patient_last_name" value="<?php echo esc_attr($patient_last_name); ?>" placeholder="">
                </p>
                
                <p class="form-field form-field-wide _apl_patient_national_id_field">
                    <label for="_apl_patient_national_id">کد ملی بیمار</label>
                    <input type="text" class="short" name="_apl_patient_national_id" id="_apl_patient_national_id" value="<?php echo esc_attr($patient_national_id); ?>" placeholder="" maxlength="10">
                </p>
                
                <p class="form-field form-field-wide _apl_patient_mobile_field">
                    <label for="_apl_patient_mobile">شماره موبایل بیمار</label>
                    <input type="tel" class="short" name="_apl_patient_mobile" id="_apl_patient_mobile" value="<?php echo esc_attr($patient_mobile); ?>" placeholder="">
                </p>
                
                <p class="form-field form-field-wide _apl_appointment_date_field">
                    <label for="_apl_appointment_date">تاریخ نوبت</label>
                    <input type="text" class="short" name="_apl_appointment_date" id="_apl_appointment_date" value="<?php echo esc_attr($appointment_date); ?>" placeholder="" readonly>
                </p>
                
                <p class="form-field form-field-wide _apl_appointment_time_field">
                    <label for="_apl_appointment_time">ساعت نوبت</label>
                    <input type="text" class="short" name="_apl_appointment_time" id="_apl_appointment_time" value="<?php echo esc_attr($appointment_time); ?>" placeholder="" readonly>
                </p>
                
                <p class="form-field form-field-wide _apl_city_field" id="apl_city_field_wrapper" style="<?php echo ($delivery_method !== 'home_sampling') ? 'display:none;' : ''; ?>">
                    <label for="_apl_city">شهر</label>
                    <select id="_apl_city" name="_apl_city" class="select short">
                        <option value="">--</option>
                        <option value="ardabil" <?php selected($city, 'ardabil'); ?>>اردبیل</option>
                        <option value="namin" <?php selected($city, 'namin'); ?>>نمین</option>
                        <option value="astara" <?php selected($city, 'astara'); ?>>آستارا</option>
                        <option value="anbaran" <?php selected($city, 'anbaran'); ?>>عنبران</option>
                        <option value="abibiglu" <?php selected($city, 'abibiglu'); ?>>ابی بیگلو</option>
                    </select>
                </p>
                
                <p class="form-field form-field-wide _apl_address_field" id="apl_address_field_wrapper" style="<?php echo ($delivery_method !== 'home_sampling') ? 'display:none;' : ''; ?>">
                    <label for="_apl_address">آدرس کامل</label>
                    <textarea rows="3" class="short" name="_apl_address" id="_apl_address" placeholder=""><?php echo esc_textarea($address); ?></textarea>
                </p>
                
                <p class="form-field form-field-wide _apl_basic_insurance_field">
                    <label for="_apl_basic_insurance">بیمه پایه</label>
                    <select id="_apl_basic_insurance" name="_apl_basic_insurance" class="select short">
                        <option value="">ندارد</option>
                        <option value="tamin" <?php selected($basic_insurance, 'tamin'); ?>>تأمین اجتماعی</option>
                        <option value="salamat" <?php selected($basic_insurance, 'salamat'); ?>>سلامت ایران</option>
                        <option value="mosalah" <?php selected($basic_insurance, 'mosalah'); ?>>نیروهای مسلح</option>
                        <option value="other" <?php selected($basic_insurance, 'other'); ?>>سایر</option>
                    </select>
                </p>
                
                <p class="form-field form-field-wide _apl_supplementary_insurance_field">
                    <label for="_apl_supplementary_insurance">بیمه تکمیلی</label>
                    <select id="_apl_supplementary_insurance" name="_apl_supplementary_insurance" class="select short">
                        <option value="">ندارد</option>
                        <option value="day" <?php selected($supplementary_insurance, 'day'); ?>>بیمه دی</option>
                        <option value="alborz" <?php selected($supplementary_insurance, 'alborz'); ?>>بیمه البرز</option>
                        <option value="hafez" <?php selected($supplementary_insurance, 'hafez'); ?>>بیمه حافظ</option>
                        <option value="hekmat" <?php selected($supplementary_insurance, 'hekmat'); ?>>بیمه حکمت</option>
                        <option value="dana" <?php selected($supplementary_insurance, 'dana'); ?>>بیمه دانا</option>
                        <option value="asia" <?php selected($supplementary_insurance, 'asia'); ?>>بیمه آسیا</option>
                        <option value="iran" <?php selected($supplementary_insurance, 'iran'); ?>>بیمه ایران</option>
                        <option value="parsian" <?php selected($supplementary_insurance, 'parsian'); ?>>بیمه پارسیان</option>
                        <option value="pasargad" <?php selected($supplementary_insurance, 'pasargad'); ?>>بیمه پاسارگاد</option>
                        <option value="moalem" <?php selected($supplementary_insurance, 'moalem'); ?>>بیمه معلم</option>
                        <option value="saman" <?php selected($supplementary_insurance, 'saman'); ?>>بیمه سامان</option>
                        <option value="sina" <?php selected($supplementary_insurance, 'sina'); ?>>بیمه سینا</option>
                        <option value="karafarin" <?php selected($supplementary_insurance, 'karafarin'); ?>>بیمه کارآفرین</option>
                        <option value="novin" <?php selected($supplementary_insurance, 'novin'); ?>>بیمه نوین</option>
                        <option value="mellat" <?php selected($supplementary_insurance, 'mellat'); ?>>بیمه ملت</option>
                    </select>
                </p>
                
                <p class="form-field form-field-wide _apl_insurance_tracking_code_field">
                    <label for="_apl_insurance_tracking_code">کد رهگیری بیمه</label>
                    <input type="text" class="short" name="_apl_insurance_tracking_code" id="_apl_insurance_tracking_code" value="<?php echo esc_attr($insurance_tracking_code); ?>" placeholder="">
                </p>
                
                <p class="form-field form-field-wide _apl_electronic_national_id_field" id="_apl_electronic_national_id_field" style="<?php echo ($request_type !== 'electronic') ? 'display:none;' : ''; ?>">
                    <label for="_apl_electronic_national_id">کد ملی (نسخه الکترونیک)</label>
                    <input type="text" class="short" name="_apl_electronic_national_id" id="_apl_electronic_national_id" value="<?php echo esc_attr($electronic_national_id); ?>" placeholder="" maxlength="10">
                </p>
                
                <p class="form-field form-field-wide _apl_doctor_name_field" id="_apl_doctor_name_field" style="<?php echo ($request_type !== 'electronic') ? 'display:none;' : ''; ?>">
                    <label for="_apl_doctor_name">نام پزشک</label>
                    <input type="text" class="short" name="_apl_doctor_name" id="_apl_doctor_name" value="<?php echo esc_attr($doctor_name); ?>" placeholder="">
                </p>
                
                <?php if (!empty($prescription_files)): ?>
                    <p class="form-field form-field-wide _apl_prescription_files_field">
                        <label>فایل‌های نسخه</label>
                        <div>
                            <?php
                            $files = is_array($prescription_files) ? $prescription_files : array($prescription_files);
                            foreach ($files as $file_url):
                                if (!empty($file_url)):
                                    $file_name = basename($file_url);
                            ?>
                                <div style="margin-bottom: 5px;">
                                    <a href="<?php echo esc_url($file_url); ?>" target="_blank"><?php echo esc_html($file_name); ?></a>
                                </div>
                            <?php
                                endif;
                            endforeach;
                            ?>
                        </div>
                    </p>
                <?php endif; ?>
                
                <p class="form-field form-field-wide _apl_discount_code_field">
                    <label for="_apl_discount_code">کد تخفیف</label>
                    <input type="text" class="short" name="_apl_discount_code" id="_apl_discount_code" value="<?php echo esc_attr($discount_code); ?>" placeholder="">
                </p>
                
           
        <?php
    }
    
    /**
     * Save order form data when order is updated
     */
    public function save_order_form_data($order_id, $order) {
        // Get order object if not provided
        if (!is_a($order, 'WC_Order')) {
            $order = wc_get_order($order_id);
        }
        
        if (!$order) {
            return;
        }
        
        // Save all APL fields
        $fields = array(
            '_apl_request_type',
            '_apl_delivery_method',
            '_apl_patient_first_name',
            '_apl_patient_last_name',
            '_apl_patient_national_id',
            '_apl_patient_mobile',
            '_apl_appointment_date',
            '_apl_appointment_time',
            '_apl_city',
            '_apl_address',
            '_apl_basic_insurance',
            '_apl_supplementary_insurance',
            '_apl_insurance_tracking_code',
            '_apl_discount_code',
            '_apl_electronic_national_id',
            '_apl_doctor_name'
        );
        
        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                // Use sanitize_textarea_field for address field, sanitize_text_field for others
                if ($field === '_apl_address') {
                    $value = sanitize_textarea_field($_POST[$field]);
                } else {
                    $value = sanitize_text_field($_POST[$field]);
                }
                if (!empty($value)) {
                    $order->update_meta_data($field, $value);
                } else {
                    $order->delete_meta_data($field);
                }
            }
        }
        
        // Save order
        $order->save();
    }
    
    /**
     * Get file icon class based on extension
     */
    private function get_file_icon_class($ext) {
        $icons = array(
            'pdf' => 'dashicons-media-document',
            'jpg' => 'dashicons-format-image',
            'jpeg' => 'dashicons-format-image',
            'png' => 'dashicons-format-image',
            'gif' => 'dashicons-format-image'
        );
        return isset($icons[$ext]) ? $icons[$ext] : 'dashicons-media-default';
    }
    
    /**
     * Get inline styles (minimal - using WooCommerce default styles)
     */
    private function get_inline_styles() {
        return '';
    }
    
    /**
     * Get request type label
     */
    private function get_request_type_label($value) {
        $labels = array(
            'upload' => 'بارگذاری نسخه',
            'electronic' => 'نسخه الکترونیک',
            'packages' => 'بسته‌های آزمایش'
        );
        return isset($labels[$value]) ? $labels[$value] : $value;
    }
    
    /**
     * Get delivery method label
     */
    private function get_delivery_method_label($value) {
        $labels = array(
            'home_sampling' => 'نمونه‌گیری در منزل',
            'lab_visit' => 'مراجعه به آزمایشگاه',
            'sample_shipping' => 'ارسال نمونه'
        );
        return isset($labels[$value]) ? $labels[$value] : $value;
    }
    
    /**
     * Get city label
     */
    private function get_city_label($value) {
        $cities = array(
            'ardabil' => 'اردبیل',
            'namin' => 'نمین',
            'astara' => 'آستارا',
            'anbaran' => 'عنبران',
            'abibiglu' => 'ابی بیگلو'
        );
        return isset($cities[$value]) ? $cities[$value] : $value;
    }
    
    /**
     * Get insurance label
     */
    private function get_insurance_label($value) {
        $insurances = array(
            'tamin' => 'تأمین اجتماعی',
            'salamat' => 'سلامت ایران',
            'mosalah' => 'نیروهای مسلح',
            'other' => 'سایر',
            'day' => 'بیمه دی',
            'alborz' => 'بیمه البرز',
            'hafez' => 'بیمه حافظ',
            'hekmat' => 'بیمه حکمت',
            'dana' => 'بیمه دانا',
            'asia' => 'بیمه آسیا',
            'iran' => 'بیمه ایران',
            'parsian' => 'بیمه پارسیان',
            'pasargad' => 'بیمه پاسارگاد',
            'moalem' => 'بیمه معلم',
            'saman' => 'بیمه سامان',
            'sina' => 'بیمه سینا',
            'karafarin' => 'بیمه کارآفرین',
            'novin' => 'بیمه نوین',
            'mellat' => 'بیمه ملت'
        );
        return isset($insurances[$value]) ? $insurances[$value] : $value;
    }
}

// Initialize the class
new APL_Order_Meta();
