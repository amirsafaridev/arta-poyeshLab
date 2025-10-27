<?php
/**
 * Admin Settings Class
 * 
 * @package ArtaPoyeshLab
 * @since 1.0.0
 */

namespace APL\Classes;

if (!defined('ABSPATH')) {
    exit;
}

class APL_Admin_Settings {
    
    private $logger;
    
    public function __construct() {
        $this->logger = new APL_Logger();
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            'تنظیمات آزمایشگاه پوش',
            'آزمایشگاه پوش',
            'manage_options',
            'apl-settings',
            array($this, 'settings_page'),
            'dashicons-admin-generic',
            30
        );
        
        add_submenu_page(
            'apl-settings',
            'تنظیمات پیامک',
            'تنظیمات پیامک',
            'manage_options',
            'apl-settings',
            array($this, 'settings_page')
        );
        
        add_submenu_page(
            'apl-settings',
            'تنظیمات صفحه لاگین',
            'تنظیمات صفحه لاگین',
            'manage_options',
            'apl-login-settings',
            array($this, 'login_settings_page')
        );
        
        add_submenu_page(
            'apl-settings',
            'لاگ سیستم',
            'لاگ سیستم',
            'manage_options',
            'apl-logs',
            array($this, 'logs_page')
        );
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        // SMS Settings
        register_setting('apl_sms_settings', 'apl_sms_username');
        register_setting('apl_sms_settings', 'apl_sms_password');
        register_setting('apl_sms_settings', 'apl_sms_from_number');
        
        // Login Page Settings
        register_setting('apl_login_settings', 'apl_login_logo');
        register_setting('apl_login_settings', 'apl_login_title');
        register_setting('apl_login_settings', 'apl_login_subtitle');
        register_setting('apl_login_settings', 'apl_login_terms_text');
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'apl-') === false) {
            return;
        }
        
        wp_enqueue_media();
        wp_enqueue_script('jquery');
    }
    
    /**
     * SMS Settings Page
     */
    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>تنظیمات پیامک</h1>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('apl_sms_settings');
                do_settings_sections('apl_sms_settings');
                ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">نام کاربری</th>
                        <td>
                            <input type="text" name="apl_sms_username" value="<?php echo esc_attr(get_option('apl_sms_username')); ?>" class="regular-text" />
                            <p class="description">نام کاربری پنل ملی پیامک</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">رمز عبور</th>
                        <td>
                            <input type="password" name="apl_sms_password" value="<?php echo esc_attr(get_option('apl_sms_password')); ?>" class="regular-text" />
                            <p class="description">رمز عبور پنل ملی پیامک</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">شماره ارسال‌کننده</th>
                        <td>
                            <input type="text" name="apl_sms_from_number" value="<?php echo esc_attr(get_option('apl_sms_from_number')); ?>" class="regular-text" />
                            <p class="description">شماره ارسال‌کننده پیامک (مثال: 10008663)</p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
            
            <div class="card" style="max-width: 600px; margin-top: 20px;">
                <h2>تست اتصال</h2>
                <p>برای تست اتصال به سامانه پیامکی، شماره موبایل خود را وارد کنید:</p>
                <input type="text" id="test-mobile" placeholder="09123456789" class="regular-text" />
                <button type="button" id="test-sms" class="button button-primary">ارسال پیامک تست</button>
                <div id="test-result" style="margin-top: 10px;"></div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('#test-sms').click(function() {
                var mobile = $('#test-mobile').val();
                if (!mobile) {
                    alert('لطفاً شماره موبایل را وارد کنید');
                    return;
                }
                
                $('#test-sms').prop('disabled', true).text('در حال ارسال...');
                $('#test-result').html('');
                
                $.post(ajaxurl, {
                    action: 'apl_test_sms',
                    mobile: mobile,
                    nonce: '<?php echo wp_create_nonce('apl_test_sms'); ?>'
                }, function(response) {
                    $('#test-sms').prop('disabled', false).text('ارسال پیامک تست');
                    if (response.success) {
                        $('#test-result').html('<div class="notice notice-success"><p>' + response.data.message + '</p></div>');
                    } else {
                        $('#test-result').html('<div class="notice notice-error"><p>' + response.data.message + '</p></div>');
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * Login Settings Page
     */
    public function login_settings_page() {
        ?>
        <div class="wrap">
            <h1>تنظیمات صفحه لاگین</h1>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('apl_login_settings');
                do_settings_sections('apl_login_settings');
                ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">لوگو</th>
                        <td>
                            <?php
                            $logo_id = get_option('apl_login_logo');
                            $logo_url = $logo_id ? wp_get_attachment_url($logo_id) : '';
                            ?>
                            <div class="logo-upload-container">
                                <div class="logo-preview" style="margin-bottom: 10px;">
                                    <?php if ($logo_url): ?>
                                        <img src="<?php echo esc_url($logo_url); ?>" style="max-width: 200px; max-height: 100px;" />
                                    <?php endif; ?>
                                </div>
                                <input type="hidden" name="apl_login_logo" id="apl_login_logo" value="<?php echo esc_attr($logo_id); ?>" />
                                <button type="button" class="button" id="upload-logo">انتخاب لوگو</button>
                                <button type="button" class="button" id="remove-logo" style="display: <?php echo $logo_url ? 'inline-block' : 'none'; ?>;">حذف لوگو</button>
                            </div>
                            <p class="description">لوگوی نمایش داده شده در صفحه لاگین</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">عنوان اصلی</th>
                        <td>
                            <input type="text" name="apl_login_title" value="<?php echo esc_attr(get_option('apl_login_title', 'آزمایشگاه پوش')); ?>" class="regular-text" />
                            <p class="description">عنوان نمایش داده شده در بالای صفحه لاگین</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">زیرعنوان</th>
                        <td>
                            <textarea name="apl_login_subtitle" rows="3" class="large-text"><?php echo esc_textarea(get_option('apl_login_subtitle', 'شریک مطمئن شما در تشخیص‌های پزشکی')); ?></textarea>
                            <p class="description">زیرعنوان نمایش داده شده در صفحه لاگین</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">متن قوانین و مقررات</th>
                        <td>
                            <textarea name="apl_login_terms_text" rows="3" class="large-text"><?php echo esc_textarea(get_option('apl_login_terms_text', 'با قوانین و مقررات و سیاست حفظ حریم خصوصی موافقم')); ?></textarea>
                            <p class="description">متن نمایش داده شده برای قوانین و مقررات</p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            var mediaUploader;
            
            $('#upload-logo').click(function(e) {
                e.preventDefault();
                
                if (mediaUploader) {
                    mediaUploader.open();
                    return;
                }
                
                mediaUploader = wp.media({
                    title: 'انتخاب لوگو',
                    button: {
                        text: 'انتخاب'
                    },
                    multiple: false
                });
                
                mediaUploader.on('select', function() {
                    var attachment = mediaUploader.state().get('selection').first().toJSON();
                    $('#apl_login_logo').val(attachment.id);
                    $('.logo-preview').html('<img src="' + attachment.url + '" style="max-width: 200px; max-height: 100px;" />');
                    $('#remove-logo').show();
                });
                
                mediaUploader.open();
            });
            
            $('#remove-logo').click(function(e) {
                e.preventDefault();
                $('#apl_login_logo').val('');
                $('.logo-preview').html('');
                $(this).hide();
            });
        });
        </script>
        <?php
    }
    
    /**
     * Logs Page
     */
    public function logs_page() {
        $logs = $this->logger->get_logs();
        $stats = $this->logger->get_statistics();
        ?>
        <div class="wrap">
            <h1>لاگ سیستم</h1>
            
            <!-- Statistics -->
            <div  style="margin-bottom: 20px;background: white;padding: 30px 60px;margin-top: 20px;">
                <h2>آمار کلی</h2>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                    <div>
                        <h3>تعداد کل لاگ‌ها</h3>
                        <p style="font-size: 24px; font-weight: bold; color: #0073aa;"><?php echo $stats['total']; ?></p>
                    </div>
                    <div>
                        <h3>امروز</h3>
                        <p style="font-size: 24px; font-weight: bold; color: #00a32a;"><?php echo $stats['today']; ?></p>
                    </div>
                    <div>
                        <h3>این هفته</h3>
                        <p style="font-size: 24px; font-weight: bold; color: #dba617;"><?php echo $stats['this_week']; ?></p>
                    </div>
                    <div>
                        <h3>این ماه</h3>
                        <p style="font-size: 24px; font-weight: bold; color: #d63638;"><?php echo $stats['this_month']; ?></p>
                    </div>
                </div>
            </div>
            
            <!-- Logs Table -->
            <div>
                <h2>لاگ‌های سیستم</h2>
                
                <?php if (empty($logs)): ?>
                    <p>هیچ لاگی یافت نشد.</p>
                <?php else: ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>تاریخ و زمان</th>
                                <th>نوع عملیات</th>
                                <th>شماره موبایل</th>
                                <th>وضعیت</th>
                                <th>پیام</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td><?php echo esc_html($log['date']); ?></td>
                                    <td>
                                        <?php
                                        $type_labels = array(
                                            'sms_sent' => 'ارسال پیامک',
                                            'login_attempt' => 'تلاش لاگین',
                                            'registration_attempt' => 'تلاش ثبت‌نام',
                                            'otp_verification' => 'تایید کد'
                                        );
                                        echo isset($type_labels[$log['type']]) ? $type_labels[$log['type']] : $log['type'];
                                        ?>
                                    </td>
                                    <td><?php echo esc_html($log['mobile_number']); ?></td>
                                    <td>
                                        <span class="status-<?php echo esc_attr($log['status']); ?>">
                                            <?php echo esc_html($log['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo esc_html($log['message']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
                
                <div style="margin-top: 20px;">
                    <button type="button" class="button" id="clear-logs">پاک کردن همه لاگ‌ها</button>
                </div>
            </div>
        </div>
        
        <style>
        .status-success { color: #00a32a; font-weight: bold; }
        .status-failed { color: #d63638; font-weight: bold; }
        .status-error { color: #d63638; font-weight: bold; }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            $('#clear-logs').click(function() {
                if (confirm('آیا مطمئن هستید که می‌خواهید همه لاگ‌ها را پاک کنید؟')) {
                    $.post(ajaxurl, {
                        action: 'apl_clear_logs',
                        nonce: '<?php echo wp_create_nonce('apl_clear_logs'); ?>'
                    }, function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert('خطا در پاک کردن لاگ‌ها');
                        }
                    });
                }
            });
        });
        </script>
        <?php
    }
}

// Initialize the admin settings
new APL_Admin_Settings();
