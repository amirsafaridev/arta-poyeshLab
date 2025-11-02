<?php
/**
 * Appointments Management Class
 * 
 * @package ArtaPoyeshLab
 * @since 1.0.0
 */

namespace APL\Classes;

if (!defined('ABSPATH')) {
    exit;
}

class APL_Appointments {
    
    private $table_name;
    private $logger;
    
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'apl_appointments';
        $this->logger = new APL_Logger();
        
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_apl_create_appointments', array($this, 'ajax_create_appointments'));
        add_action('wp_ajax_apl_update_appointment', array($this, 'ajax_update_appointment'));
        add_action('wp_ajax_apl_delete_appointment', array($this, 'ajax_delete_appointment'));
        add_action('wp_ajax_apl_bulk_delete_appointments', array($this, 'ajax_bulk_delete_appointments'));
        add_action('wp_ajax_apl_get_appointment', array($this, 'ajax_get_appointment'));
        add_action('wp_ajax_apl_get_available_hours', array($this, 'ajax_get_available_hours'));
        add_action('wp_ajax_nopriv_apl_get_available_hours', array($this, 'ajax_get_available_hours'));
        
        // Add nonce to script localization
        add_action('admin_enqueue_scripts', array($this, 'localize_scripts'));
        
        // Create table on activation
        register_activation_hook(ARTA_POYESHLAB_PLUGIN_FILE, array($this, 'create_appointments_table'));
        add_action('admin_init', array($this, 'create_appointments_table'));
    }
    
    /**
     * Create appointments table
     */
    public function create_appointments_table() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$this->table_name} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            service_delivery_method varchar(255) DEFAULT NULL,
            appointment_date date NOT NULL,
            appointment_time time NOT NULL,
            appointment_timestamp datetime NOT NULL,
            status varchar(100) DEFAULT 'available',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY appointment_date (appointment_date),
            KEY appointment_timestamp (appointment_timestamp),
            KEY status (status),
            KEY service_delivery_method (service_delivery_method)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'apl-settings',
            'نوبت‌ها',
            'نوبت‌ها',
            'manage_options',
            'apl-appointments',
            array($this, 'appointments_page')
        );
    }
    
    /**
     * Reorder submenu to make appointments first
     */
    public function reorder_submenu() {
        global $submenu;
        
        if (!isset($submenu['apl-settings'])) {
            return;
        }
        
        // Find appointments menu item
        $appointments_index = false;
        foreach ($submenu['apl-settings'] as $index => $item) {
            if (isset($item[2]) && $item[2] === 'apl-appointments') {
                $appointments_index = $index;
                break;
            }
        }
        
        // Move appointments to first position (after the default first item which has same slug as parent)
        if ($appointments_index !== false && $appointments_index > 1) {
            $appointments_item = $submenu['apl-settings'][$appointments_index];
            unset($submenu['apl-settings'][$appointments_index]);
            
            // Re-index array
            $submenu['apl-settings'] = array_values($submenu['apl-settings']);
            
            // Insert appointments as second item (first submenu item)
            // The first item (index 0) is the default parent menu item
            array_splice($submenu['apl-settings'], 1, 0, array($appointments_item));
        }
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'apl-appointments') === false) {
            return;
        }
        
        // Enqueue our custom Persian Date Picker
        wp_enqueue_script('persian-datepicker', ARTA_POYESHLAB_PLUGIN_URL . 'assets/js/persian-datepicker.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('persian-datepicker', ARTA_POYESHLAB_PLUGIN_URL . 'assets/css/persian-datepicker.css', array(), '1.0.0');
        
        // No additional CSS needed - using our custom stylesheet
    }
    
    /**
     * Localize scripts
     */
    public function localize_scripts($hook) {
        if (strpos($hook, 'apl-appointments') === false) {
            return;
        }
        
        wp_localize_script('jquery', 'apl_appointments_ajax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('apl_appointments_nonce')
        ));
    }
    
    /**
     * Appointments admin page
     */
    public function appointments_page() {
        ?>
        <div class="wrap">
            <h1>مدیریت نوبت‌ها</h1>
            
            <!-- Add Appointment Button -->
            <div style="margin: 20px 0;">
                <button type="button" class="button button-primary" id="add-appointment-btn">افزودن نوبت جدید</button>
            </div>
            
            <!-- Messages -->
            <div id="page-messages" class="apl-messages" style="display: none;"></div>
            
            <!-- Table Navigation with Filters -->
            <div class="tablenav top">
                <div class="alignleft actions bulkactions">
                    <select name="bulk_action" id="bulk-action-selector">
                        <option value="">عملیات همگانی</option>
                        <option value="delete">حذف</option>
                    </select>
                    <button type="button" class="button action" id="do-bulk-action">اعمال</button>
                </div>
                
                <div class="alignright actions">
                    <form method="get" id="appointments-filter-form" style="display: inline-block;">
                        <input type="hidden" name="page" value="apl-appointments">
                        
                        <select name="status" id="status-filter" style="margin-left: 10px;">
                            <option value="">همه وضعیت‌ها</option>
                            <option value="available" <?php selected(isset($_GET['status']) ? $_GET['status'] : '', 'available'); ?>>آزاد</option>
                            <option value="booked" <?php selected(isset($_GET['status']) ? $_GET['status'] : '', 'booked'); ?>>رزرو شده</option>
                            <option value="completed" <?php selected(isset($_GET['status']) ? $_GET['status'] : '', 'completed'); ?>>انجام شده</option>
                            <option value="cancelled" <?php selected(isset($_GET['status']) ? $_GET['status'] : '', 'cancelled'); ?>>لغو شده</option>
                        </select>
                        
                        <select name="service" id="service-filter" style="margin-left: 10px;">
                            <option value="">همه خدمات</option>
                            <option value="home_sampling" <?php selected(isset($_GET['service']) ? $_GET['service'] : '', 'home_sampling'); ?>>نمونه‌گیری در منزل</option>
                            <option value="lab_visit" <?php selected(isset($_GET['service']) ? $_GET['service'] : '', 'lab_visit'); ?>>مراجعه به آزمایشگاه</option>
                            <option value="sample_shipping" <?php selected(isset($_GET['service']) ? $_GET['service'] : '', 'sample_shipping'); ?>>ارسال نمونه</option>
                        </select>
                        
                        <select name="month" id="month-filter" style="margin-left: 10px;">
                            <option value="">همه ماه‌ها</option>
                            <?php
                            $persian_months = array(
                                1 => 'فروردین',
                                2 => 'اردیبهشت', 
                                3 => 'خرداد',
                                4 => 'تیر',
                                5 => 'مرداد',
                                6 => 'شهریور',
                                7 => 'مهر',
                                8 => 'آبان',
                                9 => 'آذر',
                                10 => 'دی',
                                11 => 'بهمن',
                                12 => 'اسفند'
                            );
                            
                            $selected_month = isset($_GET['month']) ? intval($_GET['month']) : '';
                            
                            foreach ($persian_months as $month_num => $month_name) {
                                $selected = ($selected_month == $month_num) ? 'selected' : '';
                                echo '<option value="' . $month_num . '" ' . $selected . '>' . $month_name . '</option>';
                            }
                            ?>
                        </select>
                        
                        <select name="per_page" id="per-page-select" style="margin-left: 10px;">
                            <option value="10" <?php selected(isset($_GET['per_page']) ? $_GET['per_page'] : 20, 10); ?>>10</option>
                            <option value="20" <?php selected(isset($_GET['per_page']) ? $_GET['per_page'] : 20, 20); ?>>20</option>
                            <option value="50" <?php selected(isset($_GET['per_page']) ? $_GET['per_page'] : 20, 50); ?>>50</option>
                            <option value="100" <?php selected(isset($_GET['per_page']) ? $_GET['per_page'] : 20, 100); ?>>100</option>
                        </select>
                        
                        <button type="submit" class="button" style="margin-left: 10px;">فیلتر</button>
                        <a href="?page=apl-appointments" class="button" style="margin-left: 5px;">پاک کردن</a>
                    </form>
                </div>
            </div>
            
            <!-- Appointments Table -->
            <div id="appointments-table-container">
                <?php $this->display_appointments_table(); ?>
            </div>
        </div>
        
        <!-- Add Appointment Modal -->
        <div id="add-appointment-modal" class="apl-modal" style="display: none;">
            <div class="apl-modal-content">
                <div class="apl-modal-header">
                    <h2>افزودن نوبت جدید</h2>
                    <span class="apl-modal-close">&times;</span>
                </div>
                <div class="apl-modal-body">
                    <div id="add-appointment-messages" class="apl-messages" style="display: none;"></div>
                    <form id="add-appointment-form">
                        <table class="form-table">
                            <tr>
                                <th scope="row">بازه تاریخ</th>
                                <td>
                                    <label>از تاریخ:</label>
                                    <input type="text" name="start_date" id="start_date" class="persian-datepicker" required readonly>
                                    <br><br>
                                    <label>تا تاریخ:</label>
                                    <input type="text" name="end_date" id="end_date" class="persian-datepicker" required readonly>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">نوع ارائه خدمت</th>
                                <td>
                                    <select name="service_delivery_method" id="service_delivery_method" required>
                                        <option value="">انتخاب کنید</option>
                                        <option value="home_sampling">نمونه‌گیری در منزل</option>
                                        <option value="lab_visit">مراجعه به آزمایشگاه</option>
                                        <option value="sample_shipping">ارسال نمونه</option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">ساعت‌ها</th>
                                <td>
                                    <div id="time-slots-container">
                                        <div class="time-slot">
                                            <select name="hours[]" class="hour-select">
                                                <?php for($i = 0; $i < 24; $i++): ?>
                                                    <option value="<?php echo sprintf('%02d', $i); ?>"><?php echo sprintf('%02d', $i); ?></option>
                                                <?php endfor; ?>
                                            </select>
                                            <span>:</span>
                                            <select name="minutes[]" class="minute-select">
                                                <option value="00">00</option>
                                                <option value="15">15</option>
                                                <option value="30">30</option>
                                                <option value="45">45</option>
                                            </select>
                                            <button type="button" class="button remove-time-slot">حذف</button>
                                        </div>
                                    </div>
                                    <button type="button" class="button" id="add-time-slot">افزودن ساعت</button>
                                </td>
                            </tr>
                        </table>
                    </form>
                </div>
                <div class="apl-modal-footer">
                    <button type="button" class="button button-primary" id="save-appointments">
                        <span class="button-text">ذخیره نوبت‌ها</span>
                        <span class="button-loading" style="display: none;">در حال ذخیره...</span>
                    </button>
                    <button type="button" class="button apl-modal-close">انصراف</button>
                </div>
            </div>
        </div>
        
        <!-- Edit Appointment Modal -->
        <div id="edit-appointment-modal" class="apl-modal" style="display: none;">
            <div class="apl-modal-content">
                <div class="apl-modal-header">
                    <h2>ویرایش نوبت</h2>
                    <span class="apl-modal-close">&times;</span>
                </div>
                <div class="apl-modal-body">
                    <div id="edit-appointment-messages" class="apl-messages" style="display: none;"></div>
                    <form id="edit-appointment-form">
                        <input type="hidden" name="appointment_id" id="edit_appointment_id">
                        <table class="form-table">
                            <tr>
                                <th scope="row">تاریخ</th>
                                <td>
                                    <input type="text" name="appointment_date" id="edit_appointment_date" class="persian-datepicker" required readonly>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">ساعت</th>
                                <td>
                                    <select name="appointment_hour" id="edit_appointment_hour">
                                        <?php for($i = 0; $i < 24; $i++): ?>
                                            <option value="<?php echo sprintf('%02d', $i); ?>"><?php echo sprintf('%02d', $i); ?></option>
                                        <?php endfor; ?>
                                    </select>
                                    <span>:</span>
                                    <select name="appointment_minute" id="edit_appointment_minute">
                                        <option value="00">00</option>
                                        <option value="15">15</option>
                                        <option value="30">30</option>
                                        <option value="45">45</option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">نوع ارائه خدمت</th>
                                <td>
                                    <select name="service_delivery_method" id="edit_service_delivery_method">
                                        <option value="">انتخاب کنید</option>
                                        <option value="home_sampling">نمونه‌گیری در منزل</option>
                                        <option value="lab_visit">مراجعه به آزمایشگاه</option>
                                        <option value="sample_shipping">ارسال نمونه</option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">وضعیت</th>
                                <td>
                                    <select name="status" id="edit_status">
                                        <option value="available">آزاد</option>
                                        <option value="booked">رزرو شده</option>
                                        <option value="completed">انجام شده</option>
                                        <option value="cancelled">لغو شده</option>
                                    </select>
                                </td>
                            </tr>
                        </table>
                    </form>
                </div>
                <div class="apl-modal-footer">
                    <button type="button" class="button button-primary" id="update-appointment">
                        <span class="button-text">ذخیره تغییرات</span>
                        <span class="button-loading" style="display: none;">در حال ذخیره...</span>
                    </button>
                    <button type="button" class="button apl-modal-close">انصراف</button>
                </div>
            </div>
        </div>
        
        <style>
        table{
            padding: 15px;
        }
        .tablenav.top {
            margin: 20px 0;
            padding: 10px 0;
        }
        
        .tablenav.top .bulkactions {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .tablenav.top .bulkactions select {
            margin-right: 5px;
        }
        
        .check-column {
            width: 30px;
        }
        
        .check-column input[type="checkbox"] {
            margin: 0;
        }
        
        .apl-modal {
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        
        .apl-modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 0;
            border: 1px solid #888;
            width: 80%;
            max-width: 600px;
            border-radius: 5px;
        }
        
        .apl-modal-header {
            padding: 20px;
            background: #f1f1f1;
            border-bottom: 1px solid #ddd;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .apl-modal-close {
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .apl-modal-body {
            padding: 20px;
        }
        
        .apl-modal-footer {
            padding: 20px;
            background: #f1f1f1;
            border-top: 1px solid #ddd;
            text-align: left;
        }
        
        .time-slot {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }
        
        .time-slot select {
            width: 80px;
        }
        
        .status-available { color: #00a32a; font-weight: bold; }
        .status-booked { color: #dba617; font-weight: bold; }
        .status-completed { color: #0073aa; font-weight: bold; }
        .status-cancelled { color: #d63638; font-weight: bold; }
        
        .wp-list-table .column-date { width: 15%; }
        .wp-list-table .column-time { width: 10%; }
        .wp-list-table .column-service { width: 20%; }
        .wp-list-table .column-status { width: 15%; }
        .wp-list-table .column-actions { width: 20%; }
        
        /* Message display styles */
        .apl-messages {
            margin-bottom: 15px;
            padding: 12px 15px;
            border-radius: 4px;
            font-size: 14px;
            line-height: 1.5;
        }
        
        .apl-messages.error {
            background-color: #fef0f0;
            border: 1px solid #dc3232;
            color: #dc3232;
        }
        
        .apl-messages.success {
            background-color: #f0f9ff;
            border: 1px solid #00a32a;
            color: #00a32a;
        }
        
        /* Button loading state */
        .button.loading {
            opacity: 0.7;
            cursor: not-allowed;
            pointer-events: none;
        }
        
        .button.loading .button-text {
            display: none;
        }
        
        .button.loading .button-loading {
            display: inline-block !important;
        }
        
        /* Disabled elements styling */
        .appointment-checkbox:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // Message display function
            function showMessage(containerId, message, type) {
                var $container = $('#' + containerId);
                $container.removeClass('error success').addClass(type);
                $container.text(message).slideDown();
                
                // Auto hide after 5 seconds for success messages
                if (type === 'success') {
                    setTimeout(function() {
                        $container.slideUp();
                    }, 5000);
                }
            }
            
            function hideMessage(containerId) {
                $('#' + containerId).slideUp();
            }
            
            // Button loading state management
            function setButtonLoading($button, loading) {
                if (loading) {
                    $button.addClass('loading').prop('disabled', true);
                } else {
                    $button.removeClass('loading').prop('disabled', false);
                }
            }
            
            // Initialize Persian Date Pickers
            function initPersianDatepickers() {
                if (typeof $.fn.persianDatepicker !== 'undefined') {
                    $('.persian-datepicker').persianDatepicker({
                        format: 'YYYY/MM/DD',
                        autoClose: true,
                        showToday: true,
                        showClear: true,
                        onSelect: function(formattedDate, dateObj) {
                            // Date selected
                        }
                    });
                } else {
                    // Retry after a short delay
                    setTimeout(initPersianDatepickers, 100);
                }
            }
            
            // Initialize datepickers
            initPersianDatepickers();
            
            // Add appointment modal
            $('#add-appointment-btn').click(function() {
                $('#add-appointment-modal').show();
                hideMessage('add-appointment-messages');
                // Re-initialize datepickers when modal opens
                setTimeout(function() {
                    initPersianDatepickers();
                }, 200);
            });
            
            // Close modals
            $('.apl-modal-close').click(function() {
                $('.apl-modal').hide();
                hideMessage('add-appointment-messages');
                hideMessage('edit-appointment-messages');
            });
            
            // Add time slot
            $('#add-time-slot').click(function() {
                var timeSlot = $('.time-slot').first().clone();
                timeSlot.find('select').val('');
                $('#time-slots-container').append(timeSlot);
            });
            
            // Remove time slot
            $(document).on('click', '.remove-time-slot', function() {
                if ($('.time-slot').length > 1) {
                    $(this).closest('.time-slot').remove();
                }
            });
            
            // Save appointments
            $('#save-appointments').click(function() {
                var $button = $(this);
                var startDate = $('#start_date').val();
                var endDate = $('#end_date').val();
                
                hideMessage('add-appointment-messages');
                
                if (!startDate || !endDate) {
                    showMessage('add-appointment-messages', 'لطفاً تاریخ‌ها را به درستی وارد کنید', 'error');
                    return;
                }
                
                var hours = $('select[name="hours[]"]').map(function() { 
                    return $(this).val(); 
                }).get().filter(function(val) { return val !== ''; });
                
                var minutes = $('select[name="minutes[]"]').map(function() { 
                    return $(this).val(); 
                }).get().filter(function(val) { return val !== ''; });
                
                if (hours.length === 0 || minutes.length === 0) {
                    showMessage('add-appointment-messages', 'لطفاً حداقل یک ساعت انتخاب کنید', 'error');
                    return;
                }
                
                // Set loading state
                setButtonLoading($button, true);
                
                // Send data to server
                $.post(apl_appointments_ajax.ajaxurl, {
                    action: 'apl_create_appointments',
                    start_date: startDate,
                    end_date: endDate,
                    service_delivery_method: $('#service_delivery_method').val(),
                    hours: hours,
                    minutes: minutes,
                    nonce: apl_appointments_ajax.nonce
                }, function(response) {
                    setButtonLoading($button, false);
                    if (response.success) {
                        showMessage('add-appointment-messages', response.data.message || 'نوبت‌ها با موفقیت ایجاد شدند', 'success');
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    } else {
                        showMessage('add-appointment-messages', response.data ? response.data.message : 'خطای نامشخص', 'error');
                    }
                }).fail(function(xhr, status, error) {
                    setButtonLoading($button, false);
                    showMessage('add-appointment-messages', 'خطا در ارتباط با سرور: ' + error, 'error');
                });
            });
            
            // Edit appointment
            $(document).on('click', '.edit-appointment', function() {
                var $button = $(this);
                var appointmentId = $button.data('id');
                
                hideMessage('edit-appointment-messages');
                setButtonLoading($button, true);
                
                $.post(apl_appointments_ajax.ajaxurl, {
                    action: 'apl_get_appointment',
                    appointment_id: appointmentId,
                    nonce: apl_appointments_ajax.nonce
                }, function(response) {
                    setButtonLoading($button, false);
                    if (response.success) {
                        var appointment = response.data;
                        $('#edit_appointment_id').val(appointment.id);
                        
                        // Use Persian date from server
                        if (appointment.appointment_date_persian) {
                            $('#edit_appointment_date').val(appointment.appointment_date_persian);
                        } else {
                            $('#edit_appointment_date').val(appointment.appointment_date);
                        }
                        
                        $('#edit_appointment_hour').val(appointment.hour);
                        $('#edit_appointment_minute').val(appointment.minute);
                        $('#edit_service_delivery_method').val(appointment.service_delivery_method);
                        $('#edit_status').val(appointment.status);
                        $('#edit-appointment-modal').show();
                        
                        // Re-initialize datepicker when modal opens
                        setTimeout(function() {
                            initPersianDatepickers();
                        }, 200);
                    } else {
                        showMessage('edit-appointment-messages', 'خطا در دریافت اطلاعات نوبت', 'error');
                    }
                }).fail(function(xhr, status, error) {
                    setButtonLoading($button, false);
                    showMessage('edit-appointment-messages', 'خطا در ارتباط با سرور: ' + error, 'error');
                });
            });
            
            // Update appointment
            $('#update-appointment').click(function() {
                var $button = $(this);
                var appointmentDate = $('#edit_appointment_date').val();
                
                hideMessage('edit-appointment-messages');
                
                if (!appointmentDate) {
                    showMessage('edit-appointment-messages', 'لطفاً تاریخ را به درستی وارد کنید', 'error');
                    return;
                }
                
                // Set loading state
                setButtonLoading($button, true);
                
                $.post(apl_appointments_ajax.ajaxurl, {
                    action: 'apl_update_appointment',
                    appointment_id: $('#edit_appointment_id').val(),
                    appointment_date: appointmentDate,
                    appointment_hour: $('#edit_appointment_hour').val(),
                    appointment_minute: $('#edit_appointment_minute').val(),
                    service_delivery_method: $('#edit_service_delivery_method').val(),
                    status: $('#edit_status').val(),
                    nonce: apl_appointments_ajax.nonce
                }, function(response) {
                    setButtonLoading($button, false);
                    if (response.success) {
                        showMessage('edit-appointment-messages', response.data.message || 'نوبت با موفقیت به‌روزرسانی شد', 'success');
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    } else {
                        showMessage('edit-appointment-messages', response.data ? response.data.message : 'خطای نامشخص', 'error');
                    }
                }).fail(function(xhr, status, error) {
                    setButtonLoading($button, false);
                    showMessage('edit-appointment-messages', 'خطا در ارتباط با سرور: ' + error, 'error');
                });
            });
            
            // Delete appointment
            $(document).on('click', '.delete-appointment', function() {
                if (confirm('آیا مطمئن هستید که می‌خواهید این نوبت را حذف کنید؟')) {
                    var appointmentId = $(this).data('id');
                    var $button = $(this);
                    
                    hideMessage('page-messages');
                    setButtonLoading($button, true);
                    
                    $.post(apl_appointments_ajax.ajaxurl, {
                        action: 'apl_delete_appointment',
                        appointment_id: appointmentId,
                        nonce: apl_appointments_ajax.nonce
                    }, function(response) {
                        setButtonLoading($button, false);
                        if (response.success) {
                            location.reload();
                        } else {
                            var errorMsg = response.data ? response.data.message : 'خطای نامشخص';
                            showMessage('page-messages', errorMsg, 'error');
                        }
                    }).fail(function(xhr, status, error) {
                        setButtonLoading($button, false);
                        showMessage('page-messages', 'خطا در ارتباط با سرور: ' + error, 'error');
                    });
                }
            });
            
            // Select all checkbox
            $('#cb-select-all').change(function() {
                $('.appointment-checkbox:not(:disabled)').prop('checked', $(this).prop('checked'));
            });
            
            // Update select all checkbox when individual checkboxes change
            $(document).on('change', '.appointment-checkbox', function() {
                var totalCheckboxes = $('.appointment-checkbox:not(:disabled)').length;
                var checkedCheckboxes = $('.appointment-checkbox:checked').length;
                $('#cb-select-all').prop('checked', totalCheckboxes > 0 && totalCheckboxes === checkedCheckboxes);
            });
            
            // Bulk action
            $('#do-bulk-action').click(function() {
                var $button = $(this);
                var action = $('#bulk-action-selector').val();
                
                hideMessage('page-messages');
                
                if (!action) {
                    showMessage('page-messages', 'لطفاً یک عملیات را انتخاب کنید', 'error');
                    return;
                }
                
                var selectedIds = [];
                $('.appointment-checkbox:checked:not(:disabled)').each(function() {
                    selectedIds.push($(this).val());
                });
                
                if (selectedIds.length === 0) {
                    showMessage('page-messages', 'لطفاً حداقل یک نوبت را انتخاب کنید', 'error');
                    return;
                }
                
                if (action === 'delete') {
                    if (confirm('آیا مطمئن هستید که می‌خواهید ' + selectedIds.length + ' نوبت را حذف کنید؟')) {
                        setButtonLoading($button, true);
                        
                        $.post(apl_appointments_ajax.ajaxurl, {
                            action: 'apl_bulk_delete_appointments',
                            appointment_ids: selectedIds,
                            nonce: apl_appointments_ajax.nonce
                        }, function(response) {
                            setButtonLoading($button, false);
                            if (response.success) {
                                location.reload();
                            } else {
                                var errorMsg = response.data ? response.data.message : 'خطای نامشخص';
                                showMessage('page-messages', errorMsg, 'error');
                            }
                        }).fail(function(xhr, status, error) {
                            setButtonLoading($button, false);
                            showMessage('page-messages', 'خطا در ارتباط با سرور: ' + error, 'error');
                        });
                    }
                }
            });
        });
        </script>
        <?php
    }
    
    /**
     * Display appointments table
     */
    private function display_appointments_table() {
        // Get pagination parameters
        $per_page = isset($_GET['per_page']) ? intval($_GET['per_page']) : 20;
        $current_page = isset($_GET['paged']) ? intval($_GET['paged']) : 1;
        
        // Validate per_page
        $allowed_per_page = array(10, 20, 50, 100);
        if (!in_array($per_page, $allowed_per_page)) {
            $per_page = 20;
        }
        
        // Get appointments with pagination
        $data = $this->get_appointments_with_filters($per_page, $current_page);
        $appointments = $data['appointments'];
        $total_items = $data['total_items'];
        $total_pages = $data['total_pages'];
        ?>
        <form id="appointments-form" method="post">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <td class="check-column">
                            <input type="checkbox" id="cb-select-all">
                        </td>
                        <th class="column-id">شناسه</th>
                        <th class="column-date">تاریخ</th>
                        <th class="column-time">ساعت</th>
                        <th class="column-service">نوع خدمت</th>
                        <th class="column-status">وضعیت</th>
                        <th class="column-actions">عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($appointments)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 20px;">هیچ نوبتی یافت نشد</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($appointments as $appointment): ?>
                            <tr>
                                <th scope="row" class="check-column">
                                    <?php if ($appointment->status === 'available'): ?>
                                        <input type="checkbox" name="appointment_ids[]" value="<?php echo esc_attr($appointment->id); ?>" class="appointment-checkbox">
                                    <?php else: ?>
                                        <input type="checkbox" disabled title="فقط نوبت‌های آزاد قابل حذف هستند">
                                    <?php endif; ?>
                                </th>
                                <td>#<?php echo esc_html($appointment->id); ?></td>
                                <td><?php echo $this->convert_to_jalali($appointment->appointment_date); ?></td>
                                <td><?php echo esc_html($appointment->appointment_time); ?></td>
                                <td><?php echo $this->get_service_label($appointment->service_delivery_method); ?></td>
                                <td>
                                    <span class="status-<?php echo esc_attr($appointment->status); ?>">
                                        <?php echo $this->get_status_label($appointment->status); ?>
                                    </span>
                                </td>
                                <td>
                                    <button type="button" class="button button-small edit-appointment" data-id="<?php echo esc_attr($appointment->id); ?>">ویرایش</button>
                                    <?php if ($appointment->status === 'available'): ?>
                                        <button type="button" class="button button-small delete-appointment" data-id="<?php echo esc_attr($appointment->id); ?>">حذف</button>
                                    <?php else: ?>
                                        <button type="button" class="button button-small" disabled title="فقط نوبت‌های آزاد قابل حذف هستند">حذف</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </form>
        
        <!-- Pagination Navigation -->
        <?php if ($total_pages > 1): ?>
        <div class="tablenav bottom">
            <div class="alignleft actions">
                <span class="displaying-num"><?php echo $total_items; ?> مورد</span>
            </div>
            
            <div class="tablenav-pages">
                <span class="displaying-num">
                    <?php
                    $start_item = ($current_page - 1) * $per_page + 1;
                    $end_item = min($current_page * $per_page, $total_items);
                    echo "نمایش {$start_item} تا {$end_item} از {$total_items} مورد";
                    ?>
                </span>
                
                <?php if ($total_pages > 1): ?>
                    <span class="pagination-links">
                        <?php
                        // Previous page
                        if ($current_page > 1):
                            $prev_url = add_query_arg(array('paged' => $current_page - 1));
                            echo '<a class="prev-page button" href="' . esc_url($prev_url) . '">&laquo;</a>';
                        else:
                            echo '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&laquo;</span>';
                        endif;
                        
                        // Page numbers
                        $start_page = max(1, $current_page - 2);
                        $end_page = min($total_pages, $current_page + 2);
                        
                        if ($start_page > 1):
                            echo '<a class="first-page button" href="' . esc_url(add_query_arg(array('paged' => 1))) . '">1</a>';
                            if ($start_page > 2):
                                echo '<span class="paging-input">…</span>';
                            endif;
                        endif;
                        
                        for ($i = $start_page; $i <= $end_page; $i++):
                            if ($i == $current_page):
                                echo '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">' . $i . '</span>';
                            else:
                                echo '<a class="button" href="' . esc_url(add_query_arg(array('paged' => $i))) . '">' . $i . '</a>';
                            endif;
                        endfor;
                        
                        if ($end_page < $total_pages):
                            if ($end_page < $total_pages - 1):
                                echo '<span class="paging-input">…</span>';
                            endif;
                            echo '<a class="last-page button" href="' . esc_url(add_query_arg(array('paged' => $total_pages))) . '">' . $total_pages . '</a>';
                        endif;
                        
                        // Next page
                        if ($current_page < $total_pages):
                            $next_url = add_query_arg(array('paged' => $current_page + 1));
                            echo '<a class="next-page button" href="' . esc_url($next_url) . '">&raquo;</a>';
                        else:
                            echo '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&raquo;</span>';
                        endif;
                        ?>
                    </span>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
        <?php
    }
    
    /**
     * Get appointments with filters and pagination
     */
    public function get_appointments_with_filters($per_page = 20, $current_page = 1) {
        global $wpdb;
        
        $where_conditions = array('1=1');
        $where_values = array();
        
        // Status filter
        if (isset($_GET['status']) && !empty($_GET['status'])) {
            $where_conditions[] = 'status = %s';
            $where_values[] = sanitize_text_field($_GET['status']);
        }
        
        // Service filter
        if (isset($_GET['service']) && !empty($_GET['service'])) {
            $where_conditions[] = 'service_delivery_method = %s';
            $where_values[] = sanitize_text_field($_GET['service']);
        }
        
        // Month filter (Persian month)
        if (isset($_GET['month']) && !empty($_GET['month'])) {
            $persian_month = intval($_GET['month']);
            if ($persian_month >= 1 && $persian_month <= 12) {
                // Get current Persian year
                $current_gregorian = new \DateTime();
                $current_persian = $this->convert_gregorian_to_persian(
                    $current_gregorian->format('Y'),
                    $current_gregorian->format('n'),
                    $current_gregorian->format('j')
                );
                $current_persian_year = $current_persian['year'];
                
                // Get date range for the Persian month
                $month_range = $this->get_persian_month_date_range($current_persian_year, $persian_month);
                
                if ($month_range) {
                    $where_conditions[] = 'appointment_date >= %s AND appointment_date <= %s';
                    $where_values[] = $month_range['start'];
                    $where_values[] = $month_range['end'];
                }
            }
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        
        // Get total count
        $count_sql = "SELECT COUNT(*) FROM {$this->table_name} WHERE {$where_clause}";
        if (!empty($where_values)) {
            $count_sql = $wpdb->prepare($count_sql, $where_values);
        }
        $total_items = $wpdb->get_var($count_sql);
        
        // Calculate pagination
        $total_pages = ceil($total_items / $per_page);
        $offset = ($current_page - 1) * $per_page;
        
        // Get appointments with pagination
        $sql = "SELECT * FROM {$this->table_name} WHERE {$where_clause} ORDER BY appointment_timestamp DESC LIMIT %d OFFSET %d";
        $where_values[] = $per_page;
        $where_values[] = $offset;
        
        $appointments = $wpdb->get_results($wpdb->prepare($sql, $where_values));
        
        return array(
            'appointments' => $appointments,
            'total_items' => $total_items,
            'total_pages' => $total_pages,
            'current_page' => $current_page,
            'per_page' => $per_page
        );
    }
    
    /**
     * Get single appointment
     */
    public function get_appointment($id) {
        global $wpdb;
        
        $sql = $wpdb->prepare("SELECT * FROM {$this->table_name} WHERE id = %d", $id);
        $appointment = $wpdb->get_row($sql);
        
        if ($appointment) {
            // Split time into hour and minute
            $time_parts = explode(':', $appointment->appointment_time);
            $appointment->hour = $time_parts[0];
            $appointment->minute = $time_parts[1];
        }
        
        return $appointment;
    }
    
    /**
     * Create appointments
     */
    public function create_appointments($data) {
        global $wpdb;
        
        $start_date = sanitize_text_field($data['start_date']);
        $end_date = sanitize_text_field($data['end_date']);
        $service_method = sanitize_text_field($data['service_delivery_method']);
        $hours = isset($data['hours']) ? $data['hours'] : array();
        $minutes = isset($data['minutes']) ? $data['minutes'] : array();
        
        $created_count = 0;
        $errors = array();
        
        // Convert Persian dates to Gregorian
        $start_gregorian = $this->convert_persian_to_gregorian($start_date);
        $end_gregorian = $this->convert_persian_to_gregorian($end_date);
        
        if (!$start_gregorian || !$end_gregorian) {
            return array(
                'success' => false,
                'created_count' => 0,
                'errors' => array('خطا در تبدیل تاریخ‌ها')
            );
        }
        
        // Validate dates (using global DateTime class)
        try {
            $current_date = new \DateTime($start_gregorian);
            $end_datetime = new \DateTime($end_gregorian);
        } catch (\Exception $e) {
            error_log('Error creating DateTime: ' . $e->getMessage());
            return array(
                'success' => false,
                'created_count' => 0,
                'errors' => array('خطا در ایجاد تاریخ: ' . $e->getMessage())
            );
        }
        
        while ($current_date <= $end_datetime) {
            $date_str = $current_date->format('Y-m-d');
            
            foreach ($hours as $index => $hour) {
                if (isset($minutes[$index])) {
                    $time_str = sprintf('%02d:%02d:00', $hour, $minutes[$index]);
                    $timestamp = $date_str . ' ' . $time_str;
                    
                    // Check if appointment already exists
                    $existing = $wpdb->get_var($wpdb->prepare(
                        "SELECT id FROM {$this->table_name} WHERE appointment_date = %s AND appointment_time = %s AND service_delivery_method = %s",
                        $date_str, $time_str, $service_method
                    ));
                    
                    if (!$existing) {
                        $result = $wpdb->insert(
                            $this->table_name,
                            array(
                                'service_delivery_method' => $service_method,
                                'appointment_date' => $date_str,
                                'appointment_time' => $time_str,
                                'appointment_timestamp' => $timestamp,
                                'status' => 'available'
                            ),
                            array('%s', '%s', '%s', '%s', '%s')
                        );
                        
                        if ($result) {
                            $created_count++;
                        } else {
                            $errors[] = "خطا در ایجاد نوبت برای {$date_str} {$time_str}";
                        }
                    }
                }
            }
            
            $current_date->add(new \DateInterval('P1D'));
        }
        
        return array(
            'success' => true,
            'created_count' => $created_count,
            'errors' => $errors
        );
    }
    
    /**
     * Update appointment
     */
    public function update_appointment($id, $data) {
        global $wpdb;
        
        $appointment_date = sanitize_text_field($data['appointment_date']);
        $hour = intval($data['appointment_hour']);
        $minute = intval($data['appointment_minute']);
        $service_method = sanitize_text_field($data['service_delivery_method']);
        $status = sanitize_text_field($data['status']);
        
        // Convert Persian date to Gregorian if needed
        if (strpos($appointment_date, '/') !== false) {
            $appointment_date = $this->convert_persian_to_gregorian($appointment_date);
        }
        
        $time_str = sprintf('%02d:%02d:00', $hour, $minute);
        $timestamp = $appointment_date . ' ' . $time_str;
        
        $result = $wpdb->update(
            $this->table_name,
            array(
                'service_delivery_method' => $service_method,
                'appointment_date' => $appointment_date,
                'appointment_time' => $time_str,
                'appointment_timestamp' => $timestamp,
                'status' => $status
            ),
            array('id' => $id),
            array('%s', '%s', '%s', '%s', '%s'),
            array('%d')
        );
        
        return $result !== false;
    }
    
    /**
     * Delete appointment
     */
    public function delete_appointment($id) {
        global $wpdb;
        
        $result = $wpdb->delete(
            $this->table_name,
            array('id' => $id),
            array('%d')
        );
        
        return $result !== false;
    }
    
    /**
     * Bulk delete appointments
     */
    public function bulk_delete_appointments($ids) {
        global $wpdb;
        
        if (empty($ids) || !is_array($ids)) {
            return false;
        }
        
        // Sanitize IDs
        $ids = array_map('absint', $ids);
        $ids = array_unique($ids);
        $ids = array_filter($ids);
        
        if (empty($ids)) {
            return false;
        }
        
        // Build query with proper escaping
        $ids_string = implode(',', $ids);
        $query = "DELETE FROM {$this->table_name} WHERE id IN ($ids_string)";
        
        $result = $wpdb->query($query);
        
        return $result !== false ? $result : false;
    }
    
    /**
     * Convert date to Jalali
     */
    private function convert_to_jalali($date) {
        if (empty($date)) return '';
        
        $date_parts = explode('-', $date);
        if (count($date_parts) !== 3) return $date;
        
        $year = intval($date_parts[0]);
        $month = intval($date_parts[1]);
        $day = intval($date_parts[2]);
        
        if (class_exists('\APL_Gregorian_Jalali')) {
            $jalali = \APL_Gregorian_Jalali::gregorian_to_jalali($year, $month, $day, true);
        } else {
            // Fallback
            $jalali = $date;
        }
        return $jalali;
    }
    
    /**
     * Convert Gregorian date to Persian
     */
    private function convert_gregorian_to_persian($gy, $gm, $gd) {
        try {
            if (class_exists('\APL_Gregorian_Jalali')) {
                $result = \APL_Gregorian_Jalali::gregorian_to_jalali($gy, $gm, $gd, false);
                if (is_array($result) && count($result) === 3) {
                    return array(
                        'year' => $result[0],
                        'month' => $result[1], 
                        'day' => $result[2]
                    );
                }
            }
        } catch (\Exception $e) {
            error_log('Error converting Gregorian to Persian: ' . $e->getMessage());
        }
        
        return null;
    }
    
    /**
     * Get Persian month date range in Gregorian
     */
    private function get_persian_month_date_range($persian_year, $persian_month) {
        // Get first day of Persian month
        $first_day_gregorian = $this->convert_persian_to_gregorian($persian_year . '/' . $persian_month . '/1');
        
        // Get last day of Persian month
        $last_day = $this->get_persian_month_last_day($persian_year, $persian_month);
        $last_day_gregorian = $this->convert_persian_to_gregorian($persian_year . '/' . $persian_month . '/' . $last_day);
        
        if (!$first_day_gregorian || !$last_day_gregorian) {
            return null;
        }
        
        return array(
            'start' => $first_day_gregorian,
            'end' => $last_day_gregorian
        );
    }
    
    /**
     * Get last day of Persian month
     */
    private function get_persian_month_last_day($year, $month) {
        $days_in_month = array(31, 31, 31, 31, 31, 31, 30, 30, 30, 30, 30, 29);
        
        // Check if it's a leap year
        if ($month == 12 && $this->is_persian_leap_year($year)) {
            return 30;
        }
        
        return $days_in_month[$month - 1];
    }
    
    /**
     * Check if Persian year is leap year
     */
    private function is_persian_leap_year($year) {
        // Persian leap year calculation
        $a = $year - 979;
        $leap = (($a + 2346) % 128) < 29;
        return $leap;
    }
    
    /**
     * Convert Persian date to Gregorian
     */
    private function convert_persian_to_gregorian($persian_date) {
        if (empty($persian_date)) return '';
        
        $date_parts = explode('/', $persian_date);
        if (count($date_parts) !== 3) return '';
        
        $year = intval($date_parts[0]);
        $month = intval($date_parts[1]);
        $day = intval($date_parts[2]);
        
        if ($year <= 0 || $month <= 0 || $month > 12 || $day <= 0 || $day > 31) {
            return '';
        }
        
        // Use APL_Gregorian_Jalali class (it's in global namespace)
        try {
            if (class_exists('\APL_Gregorian_Jalali')) {
                $result = \APL_Gregorian_Jalali::jalali_to_gregorian($year, $month, $day, false);
                if (is_array($result) && count($result) === 3) {
                    return sprintf('%04d-%02d-%02d', $result[0], $result[1], $result[2]);
                }
            }
        } catch (\Exception $e) {
            error_log('Error converting Persian date using APL_Gregorian_Jalali: ' . $e->getMessage());
        }
        
        // Fallback: simple approximation
        $gregorian_year = $year + 621;
        $gregorian_month = $month + 3;
        $gregorian_day = $day;
        
        if ($gregorian_month > 12) {
            $gregorian_month -= 12;
            $gregorian_year++;
        }
        
        return sprintf('%04d-%02d-%02d', $gregorian_year, $gregorian_month, $gregorian_day);
    }
    
    /**
     * Get service label
     */
    private function get_service_label($service) {
        $labels = array(
            'home_sampling' => 'نمونه‌گیری در منزل',
            'lab_visit' => 'مراجعه به آزمایشگاه',
            'sample_shipping' => 'ارسال نمونه'
        );
        
        return isset($labels[$service]) ? $labels[$service] : $service;
    }
    
    /**
     * Get status label
     */
    private function get_status_label($status) {
        $labels = array(
            'available' => 'آزاد',
            'booked' => 'رزرو شده',
            'completed' => 'انجام شده',
            'cancelled' => 'لغو شده'
        );
        
        return isset($labels[$status]) ? $labels[$status] : $status;
    }
    
    /**
     * AJAX: Create appointments
     */
    public function ajax_create_appointments() {
        try {
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'apl_appointments_nonce')) {
                wp_send_json_error(array('message' => 'خطا در تایید امنیتی'));
            }
            
            if (!current_user_can('manage_options')) {
                wp_send_json_error(array('message' => 'شما دسترسی لازم را ندارید'));
            }
            
            // Check if required data exists
            if (!isset($_POST['start_date']) || !isset($_POST['end_date']) || !isset($_POST['service_delivery_method'])) {
                wp_send_json_error(array('message' => 'داده‌های مورد نیاز ارسال نشده است'));
            }
            
            // Prepare data array
            $data = array(
                'start_date' => sanitize_text_field($_POST['start_date']),
                'end_date' => sanitize_text_field($_POST['end_date']),
                'service_delivery_method' => sanitize_text_field($_POST['service_delivery_method']),
                'hours' => isset($_POST['hours']) ? array_map('intval', $_POST['hours']) : array(),
                'minutes' => isset($_POST['minutes']) ? array_map('intval', $_POST['minutes']) : array()
            );
            
            $result = $this->create_appointments($data);
            
            if ($result['success']) {
                wp_send_json_success(array(
                    'message' => "{$result['created_count']} نوبت با موفقیت ایجاد شد",
                    'created_count' => $result['created_count']
                ));
            } else {
                wp_send_json_error(array('message' => 'خطا در ایجاد نوبت‌ها: ' . implode(', ', $result['errors'])));
            }
        } catch (\Exception $e) {
            error_log('Error in ajax_create_appointments: ' . $e->getMessage());
            wp_send_json_error(array('message' => 'خطا در پردازش: ' . $e->getMessage()));
        }
    }
    
    /**
     * AJAX: Get appointment
     */
    public function ajax_get_appointment() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'apl_appointments_nonce')) {
            wp_send_json_error(array('message' => 'خطا در تایید امنیتی'));
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'شما دسترسی لازم را ندارید'));
        }
        
        $appointment_id = intval($_POST['appointment_id']);
        $appointment = $this->get_appointment($appointment_id);
        
        if ($appointment) {
            // Convert Gregorian date to Persian for display
            $appointment->appointment_date_persian = $this->convert_to_jalali($appointment->appointment_date);
            wp_send_json_success($appointment);
        } else {
            wp_send_json_error(array('message' => 'نوبت یافت نشد'));
        }
    }
    
    /**
     * AJAX: Update appointment
     */
    public function ajax_update_appointment() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'apl_appointments_nonce')) {
            wp_send_json_error(array('message' => 'خطا در تایید امنیتی'));
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'شما دسترسی لازم را ندارید'));
        }
        
        $appointment_id = intval($_POST['appointment_id']);
        $data = array(
            'appointment_date' => sanitize_text_field($_POST['appointment_date']),
            'appointment_hour' => intval($_POST['appointment_hour']),
            'appointment_minute' => intval($_POST['appointment_minute']),
            'service_delivery_method' => sanitize_text_field($_POST['service_delivery_method']),
            'status' => sanitize_text_field($_POST['status'])
        );
        
        $result = $this->update_appointment($appointment_id, $data);
        
        if ($result) {
            wp_send_json_success(array('message' => 'نوبت با موفقیت به‌روزرسانی شد'));
        } else {
            wp_send_json_error(array('message' => 'خطا در به‌روزرسانی نوبت'));
        }
    }
    
    /**
     * AJAX: Delete appointment
     */
    public function ajax_delete_appointment() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'apl_appointments_nonce')) {
            wp_send_json_error(array('message' => 'خطا در تایید امنیتی'));
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'شما دسترسی لازم را ندارید'));
        }
        
        $appointment_id = intval($_POST['appointment_id']);
        $result = $this->delete_appointment($appointment_id);
        
        if ($result) {
            wp_send_json_success(array('message' => 'نوبت با موفقیت حذف شد'));
        } else {
            wp_send_json_error(array('message' => 'خطا در حذف نوبت'));
        }
    }
    
    /**
     * AJAX handler for bulk delete appointments
     */
    public function ajax_bulk_delete_appointments() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'apl_appointments_nonce')) {
            wp_send_json_error(array('message' => 'خطا در تایید امنیتی'));
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'شما دسترسی لازم را ندارید'));
        }
        
        if (!isset($_POST['appointment_ids']) || !is_array($_POST['appointment_ids'])) {
            wp_send_json_error(array('message' => 'هیچ نوبتی انتخاب نشده است'));
        }
        
        $appointment_ids = array_map('intval', $_POST['appointment_ids']);
        $deleted_count = $this->bulk_delete_appointments($appointment_ids);
        
        if ($deleted_count !== false) {
            wp_send_json_success(array(
                'message' => sprintf('تعداد %d نوبت با موفقیت حذف شد', count($appointment_ids)),
                'deleted_count' => count($appointment_ids)
            ));
        } else {
            wp_send_json_error(array('message' => 'خطا در حذف نوبت‌ها'));
        }
    }
    
    /**
     * Get available appointment hours for a specific date and service delivery method
     */
    public function get_available_hours($appointment_date, $service_delivery_method) {
        global $wpdb;
        
       
        
        // Convert Persian date to Gregorian if needed
        if (strpos($appointment_date, '/') !== false) {
            $appointment_date = $this->convert_persian_to_gregorian($appointment_date);
            error_log('APL: Converted date: ' . $appointment_date);
        }
        
        if (!$appointment_date) {
            error_log('APL: Invalid date after conversion');
            return array();
        }
        
        $sql = $wpdb->prepare(
            "SELECT appointment_time, status FROM {$this->table_name} 
            WHERE appointment_date = %s 
            AND service_delivery_method = %s 
            AND status = 'available'
            ORDER BY appointment_time ASC",
            $appointment_date,
            $service_delivery_method
        );
        
        
        $results = $wpdb->get_results($sql);
        
        
        $hours = array();
        foreach ($results as $result) {
            $time_parts = explode(':', $result->appointment_time);
            $hour = intval($time_parts[0]);
            $minute = intval($time_parts[1]);
            $time_str = sprintf('%02d:%02d', $hour, $minute);
            
            // Convert time to Persian numbers for label
            $persian_digits = array('۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹');
            $english_digits = array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9');
            $hour_label = str_replace($english_digits, $persian_digits, $time_str);
            
            $hours[] = array(
                'time' => $time_str,
                'hour' => $hour,
                'minute' => $minute,
                'label' => $hour_label
            );
        }
        
        return $hours;
    }
    
    /**
     * Format time in Persian format with readable label
     */
    private function format_persian_time($start_time, $end_time) {
        // Convert to Persian numbers
        $persian_digits = array('۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹');
        $english_digits = array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9');
        
        $start_parts = explode(':', $start_time);
        $end_parts = explode(':', $end_time);
        
        $start_hour = str_replace($english_digits, $persian_digits, $start_parts[0]);
        $end_hour = str_replace($english_digits, $persian_digits, $end_parts[0]);
        
        // Determine time period (صبح/ظهر/بعدازظهر)
        $hour = intval($start_parts[0]);
        $period = '';
        if ($hour < 12) {
            $period = 'صبح';
        } elseif ($hour < 14) {
            $period = 'ظهر';
        } else {
            $period = 'بعدازظهر';
        }
        
        return "{$start_hour}:۰۰ - {$end_hour}:۰۰ {$period}";
    }
    
    /**
     * AJAX: Get available appointment hours
     */
    public function ajax_get_available_hours() {
        // Check if required data exists
        if (!isset($_POST['appointment_date']) || !isset($_POST['service_delivery_method'])) {
            wp_send_json_error(array('message' => 'داده‌های مورد نیاز ارسال نشده است'));
        }
        
        $appointment_date = sanitize_text_field($_POST['appointment_date']);
        $service_delivery_method = sanitize_text_field($_POST['service_delivery_method']);
        
         
        $hours = $this->get_available_hours($appointment_date, $service_delivery_method);
        
        
        if (empty($hours)) {
            wp_send_json_success(array(
                'hours' => array(),
                'message' => 'هیچ ساعت خالی برای این تاریخ یافت نشد'
            ));
        }
        
        wp_send_json_success(array(
            'hours' => $hours,
            'message' => sprintf('تعداد %d ساعت خالی یافت شد', count($hours))
        ));
    }
}

// Initialize the appointments class
new APL_Appointments();
