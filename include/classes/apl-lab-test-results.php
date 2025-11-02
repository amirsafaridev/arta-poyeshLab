<?php
/**
 * Lab Test Results Custom Post Type Class
 * 
 * @package ArtaPoyeshLab
 * @since 1.0.0
 */

namespace APL\Classes;

if (!defined('ABSPATH')) {
    exit;
}

class APL_Lab_Test_Results {
    
    private $post_type = 'apl_lab_test_result';
    private $upload_dir = 'arta-poyeshlab-test-results';
    
    public function __construct() {
        $this->init();
    }
    
    public function init() {
        // Register custom post type
        add_action('init', array($this, 'register_post_type'));
        
        // Add meta boxes
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        
        // Save meta data
        add_action('save_post_' . $this->post_type, array($this, 'save_meta_data'), 10, 2);
        
        // Enqueue admin scripts and styles
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        
        // Handle file uploads via AJAX
        add_action('wp_ajax_apl_upload_test_result_file', array($this, 'ajax_upload_file'));
        add_action('wp_ajax_apl_delete_test_result_file', array($this, 'ajax_delete_file'));
        
        // Create upload directory if it doesn't exist
        add_action('admin_init', array($this, 'create_upload_directory'));
        
        // Add custom columns to admin list
        add_filter('manage_' . $this->post_type . '_posts_columns', array($this, 'add_custom_columns'));
        add_action('manage_' . $this->post_type . '_posts_custom_column', array($this, 'render_custom_columns'), 10, 2);
    }
    
    /**
     * Register custom post type
     */
    public function register_post_type() {
        $labels = array(
            'name'                  => __('جواب آزمایش‌ها', 'arta-poyeshlab'),
            'singular_name'         => __('جواب آزمایش', 'arta-poyeshlab'),
            'menu_name'             => __('جواب آزمایش‌ها', 'arta-poyeshlab'),
            'add_new'               => __('افزودن جدید', 'arta-poyeshlab'),
            'add_new_item'          => __('افزودن جواب آزمایش جدید', 'arta-poyeshlab'),
            'edit_item'             => __('ویرایش جواب آزمایش', 'arta-poyeshlab'),
            'new_item'              => __('جواب آزمایش جدید', 'arta-poyeshlab'),
            'view_item'             => __('مشاهده جواب آزمایش', 'arta-poyeshlab'),
            'search_items'          => __('جستجوی جواب آزمایش', 'arta-poyeshlab'),
            'not_found'             => __('جواب آزمایشی یافت نشد', 'arta-poyeshlab'),
            'not_found_in_trash'    => __('جواب آزمایشی در سطل زباله یافت نشد', 'arta-poyeshlab'),
            'all_items'             => __('همه جواب آزمایش‌ها', 'arta-poyeshlab'),
        );
        
        $args = array(
            'labels'                => $labels,
            'public'                => false,
            'publicly_queryable'    => false,
            'show_ui'               => true,
            'show_in_menu'          => true,
            'menu_icon'             => 'dashicons-clipboard',
            'query_var'             => true,
            'rewrite'               => false,
            'capability_type'       => 'post',
            'has_archive'           => false,
            'hierarchical'          => false,
            'menu_position'         => 30,
            'supports'              => array('title'),
            'show_in_rest'          => false,
        );
        
        register_post_type($this->post_type, $args);
    }
    
    /**
     * Add meta boxes
     */
    public function add_meta_boxes() {
        add_meta_box(
            'apl_test_result_order',
            __('سفارش مربوطه', 'arta-poyeshlab'),
            array($this, 'render_order_meta_box'),
            $this->post_type,
            'normal',
            'high'
        );
        
        add_meta_box(
            'apl_test_result_files',
            __('فایل‌های جواب آزمایش', 'arta-poyeshlab'),
            array($this, 'render_files_meta_box'),
            $this->post_type,
            'normal',
            'high'
        );
    }
    
    /**
     * Render order meta box
     */
    public function render_order_meta_box($post) {
        wp_nonce_field('apl_test_result_meta', 'apl_test_result_nonce');
        
        $selected_order_id = get_post_meta($post->ID, '_apl_related_order_id', true);
        
        // Get all WooCommerce orders
        $orders = wc_get_orders(array(
            'limit' => -1,
            'orderby' => 'date',
            'order' => 'DESC',
            'return' => 'ids'
        ));
        
        ?>
        <div class="apl-test-result-order-field">
            <label for="apl_related_order_id" style="display: block; margin-bottom: 10px; font-weight: 600;">
                <?php _e('انتخاب سفارش:', 'arta-poyeshlab'); ?>
            </label>
            <select name="apl_related_order_id" id="apl_related_order_id" style="width: 100%; min-width: 300px;">
                <option value=""><?php _e('-- انتخاب سفارش --', 'arta-poyeshlab'); ?></option>
                <?php
                foreach ($orders as $order_id) {
                    $order = wc_get_order($order_id);
                    if (!$order) continue;
                    
                    $order_number = $order->get_order_number();
                    $customer_name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
                    $order_date = $order->get_date_created()->date_i18n('Y/m/d');
                    
                    $selected = selected($selected_order_id, $order_id, false);
                    echo '<option value="' . esc_attr($order_id) . '" ' . $selected . '>';
                    echo sprintf(
                        __('سفارش #%s - %s - %s', 'arta-poyeshlab'),
                        $order_number,
                        $customer_name,
                        $order_date
                    );
                    echo '</option>';
                }
                ?>
            </select>
            <?php if ($selected_order_id): ?>
                <p style="margin-top: 10px;">
                    <a href="<?php echo admin_url('post.php?post=' . $selected_order_id . '&action=edit'); ?>" target="_blank">
                        <?php _e('مشاهده سفارش', 'arta-poyeshlab'); ?>
                    </a>
                </p>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Render files meta box
     */
    public function render_files_meta_box($post) {
        $uploaded_files = get_post_meta($post->ID, '_apl_test_result_files', true);
        if (!is_array($uploaded_files)) {
            $uploaded_files = array();
        }
        
        ?>
        <div class="apl-test-result-files-field">
            <div id="apl_file_upload_container">
                <input type="file" id="apl_file_upload_input" multiple accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                <button type="button" id="apl_upload_files_btn" class="button button-primary" style="margin-top: 10px;">
                    <?php _e('بارگذاری فایل', 'arta-poyeshlab'); ?>
                </button>
                <span id="apl_upload_progress" style="display: none; margin-left: 10px;"></span>
            </div>
            
            <div id="apl_uploaded_files_list" style="margin-top: 20px;">
                <?php if (!empty($uploaded_files)): ?>
                    <?php foreach ($uploaded_files as $file_data): ?>
                        <?php if (isset($file_data['url']) && isset($file_data['name'])): ?>
                            <div class="apl-file-item" data-file-url="<?php echo esc_attr($file_data['url']); ?>" style="margin-bottom: 10px; padding: 10px; background: #f5f5f5; border: 1px solid #ddd; border-radius: 4px;">
                                <div style="display: flex; align-items: center; justify-content: space-between;">
                                    <div>
                                        <a href="<?php echo esc_url($file_data['url']); ?>" target="_blank" style="text-decoration: none;">
                                            <strong><?php echo esc_html($file_data['name']); ?></strong>
                                        </a>
                                        <?php if (isset($file_data['size'])): ?>
                                            <span style="color: #666; margin-left: 10px;">
                                                (<?php echo size_format($file_data['size']); ?>)
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <button type="button" class="button apl-delete-file-btn" style="margin-left: 10px;">
                                        <?php _e('حذف', 'arta-poyeshlab'); ?>
                                    </button>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <input type="hidden" name="apl_test_result_files_data" id="apl_test_result_files_data" value="<?php echo esc_attr(json_encode($uploaded_files)); ?>">
        </div>
        
        <style>
            .apl-file-item {
                transition: background-color 0.2s;
            }
            .apl-file-item:hover {
                background-color: #eee !important;
            }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            var postId = <?php echo $post->ID; ?>;
            var nonce = '<?php echo wp_create_nonce('apl_test_result_files'); ?>';
            var ajaxUrl = typeof ajaxurl !== 'undefined' ? ajaxurl : '<?php echo admin_url('admin-ajax.php'); ?>';
            
            // Handle file upload
            $('#apl_upload_files_btn').on('click', function() {
                var files = $('#apl_file_upload_input')[0].files;
                if (files.length === 0) {
                    alert('<?php _e('لطفاً یک یا چند فایل انتخاب کنید', 'arta-poyeshlab'); ?>');
                    return;
                }
                
                var formData = new FormData();
                formData.append('action', 'apl_upload_test_result_file');
                formData.append('post_id', postId);
                formData.append('nonce', nonce);
                
                for (var i = 0; i < files.length; i++) {
                    formData.append('files[]', files[i]);
                }
                
                $('#apl_upload_progress').show().text('<?php _e('در حال بارگذاری...', 'arta-poyeshlab'); ?>');
                $('#apl_upload_files_btn').prop('disabled', true);
                
                $.ajax({
                    url: ajaxUrl,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        $('#apl_upload_progress').hide();
                        $('#apl_upload_files_btn').prop('disabled', false);
                        $('#apl_file_upload_input').val('');
                        
                        if (response.success) {
                            updateFilesList(response.data.files);
                        } else {
                            alert(response.data.message || '<?php _e('خطا در بارگذاری فایل', 'arta-poyeshlab'); ?>');
                        }
                    },
                    error: function() {
                        $('#apl_upload_progress').hide();
                        $('#apl_upload_files_btn').prop('disabled', false);
                        alert('<?php _e('خطا در ارتباط با سرور', 'arta-poyeshlab'); ?>');
                    }
                });
            });
            
            // Handle file deletion
            $(document).on('click', '.apl-delete-file-btn', function() {
                if (!confirm('<?php _e('آیا از حذف این فایل مطمئن هستید؟', 'arta-poyeshlab'); ?>')) {
                    return;
                }
                
                var fileItem = $(this).closest('.apl-file-item');
                var fileUrl = fileItem.data('file-url');
                
                $.ajax({
                    url: ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'apl_delete_test_result_file',
                        post_id: postId,
                        file_url: fileUrl,
                        nonce: nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            fileItem.remove();
                            updateFilesList(response.data.files);
                        } else {
                            alert(response.data.message || '<?php _e('خطا در حذف فایل', 'arta-poyeshlab'); ?>');
                        }
                    },
                    error: function() {
                        alert('<?php _e('خطا در ارتباط با سرور', 'arta-poyeshlab'); ?>');
                    }
                });
            });
            
            function updateFilesList(files) {
                var html = '';
                if (files && files.length > 0) {
                    files.forEach(function(file) {
                        html += '<div class="apl-file-item" data-file-url="' + escapeHtml(file.url) + '" style="margin-bottom: 10px; padding: 10px; background: #f5f5f5; border: 1px solid #ddd; border-radius: 4px;">';
                        html += '<div style="display: flex; align-items: center; justify-content: space-between;">';
                        html += '<div>';
                        html += '<a href="' + escapeHtml(file.url) + '" target="_blank" style="text-decoration: none;">';
                        html += '<strong>' + escapeHtml(file.name) + '</strong>';
                        html += '</a>';
                        if (file.size) {
                            html += '<span style="color: #666; margin-left: 10px;">(' + file.size + ')</span>';
                        }
                        html += '</div>';
                        html += '<button type="button" class="button apl-delete-file-btn" style="margin-left: 10px;"><?php _e('حذف', 'arta-poyeshlab'); ?></button>';
                        html += '</div>';
                        html += '</div>';
                    });
                }
                $('#apl_uploaded_files_list').html(html);
                $('#apl_test_result_files_data').val(JSON.stringify(files));
            }
            
            function escapeHtml(text) {
                var map = {
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#039;'
                };
                return text.replace(/[&<>"']/g, function(m) { return map[m]; });
            }
        });
        </script>
        <?php
    }
    
    /**
     * Save meta data
     */
    public function save_meta_data($post_id, $post) {
        // Check nonce
        if (!isset($_POST['apl_test_result_nonce']) || !wp_verify_nonce($_POST['apl_test_result_nonce'], 'apl_test_result_meta')) {
            return;
        }
        
        // Check autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Check permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Save related order ID
        if (isset($_POST['apl_related_order_id'])) {
            $order_id = intval($_POST['apl_related_order_id']);
            if ($order_id > 0) {
                update_post_meta($post_id, '_apl_related_order_id', $order_id);
            } else {
                delete_post_meta($post_id, '_apl_related_order_id');
            }
        }
        
        // Save files data (from hidden input set by JavaScript)
        if (isset($_POST['apl_test_result_files_data'])) {
            $files_data = json_decode(stripslashes($_POST['apl_test_result_files_data']), true);
            if (is_array($files_data)) {
                update_post_meta($post_id, '_apl_test_result_files', $files_data);
            }
        }
    }
    
    /**
     * AJAX handler for file upload
     */
    public function ajax_upload_file() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'apl_test_result_files')) {
            wp_send_json_error(array('message' => __('خطای امنیتی', 'arta-poyeshlab')));
        }
        
        // Check permissions
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => __('شما دسترسی لازم را ندارید', 'arta-poyeshlab')));
        }
        
        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        if ($post_id <= 0) {
            wp_send_json_error(array('message' => __('شناسه پست نامعتبر است', 'arta-poyeshlab')));
        }
        
        if (!isset($_FILES['files']) || empty($_FILES['files']['name'][0])) {
            wp_send_json_error(array('message' => __('فایلی انتخاب نشده است', 'arta-poyeshlab')));
        }
        
        // Get existing files
        $existing_files = get_post_meta($post_id, '_apl_test_result_files', true);
        if (!is_array($existing_files)) {
            $existing_files = array();
        }
        
        $upload_dir = $this->get_upload_directory();
        if (!file_exists($upload_dir)) {
            wp_mkdir_p($upload_dir);
        }
        
        $uploaded_files = array();
        $files = $_FILES['files'];
        $file_count = count($files['name']);
        
        // Allowed file types
        $allowed_types = array(
            'image/jpeg',
            'image/jpg',
            'image/png',
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        );
        
        for ($i = 0; $i < $file_count; $i++) {
            if ($files['error'][$i] !== UPLOAD_ERR_OK) {
                continue;
            }
            
            // Check file type
            $file_type = wp_check_filetype($files['name'][$i]);
            $mime_type = $files['type'][$i];
            
            if (!in_array($mime_type, $allowed_types)) {
                continue;
            }
            
            // Generate unique filename
            $file_extension = $file_type['ext'];
            $file_name = sanitize_file_name($files['name'][$i]);
            $unique_name = time() . '_' . uniqid() . '_' . $file_name;
            $destination = $upload_dir . '/' . $unique_name;
            
            // Move uploaded file
            if (move_uploaded_file($files['tmp_name'][$i], $destination)) {
                $file_url = $this->get_upload_url() . '/' . $unique_name;
                
                $uploaded_files[] = array(
                    'name' => $file_name,
                    'url' => $file_url,
                    'path' => $destination,
                    'size' => size_format(filesize($destination)),
                    'size_bytes' => filesize($destination)
                );
            }
        }
        
        // Merge with existing files
        $all_files = array_merge($existing_files, $uploaded_files);
        update_post_meta($post_id, '_apl_test_result_files', $all_files);
        
        wp_send_json_success(array(
            'message' => __('فایل‌ها با موفقیت بارگذاری شدند', 'arta-poyeshlab'),
            'files' => $all_files
        ));
    }
    
    /**
     * AJAX handler for file deletion
     */
    public function ajax_delete_file() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'apl_test_result_files')) {
            wp_send_json_error(array('message' => __('خطای امنیتی', 'arta-poyeshlab')));
        }
        
        // Check permissions
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => __('شما دسترسی لازم را ندارید', 'arta-poyeshlab')));
        }
        
        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        $file_url = isset($_POST['file_url']) ? esc_url_raw($_POST['file_url']) : '';
        
        if ($post_id <= 0 || empty($file_url)) {
            wp_send_json_error(array('message' => __('اطلاعات نامعتبر', 'arta-poyeshlab')));
        }
        
        // Get existing files
        $existing_files = get_post_meta($post_id, '_apl_test_result_files', true);
        if (!is_array($existing_files)) {
            $existing_files = array();
        }
        
        // Find and remove file
        $file_to_remove = null;
        $remaining_files = array();
        
        foreach ($existing_files as $file_data) {
            if (isset($file_data['url']) && $file_data['url'] === $file_url) {
                $file_to_remove = $file_data;
                // Delete physical file
                if (isset($file_data['path']) && file_exists($file_data['path'])) {
                    @unlink($file_data['path']);
                }
            } else {
                $remaining_files[] = $file_data;
            }
        }
        
        // Update meta
        update_post_meta($post_id, '_apl_test_result_files', $remaining_files);
        
        wp_send_json_success(array(
            'message' => __('فایل با موفقیت حذف شد', 'arta-poyeshlab'),
            'files' => $remaining_files
        ));
    }
    
    /**
     * Get upload directory path
     */
    private function get_upload_directory() {
        $upload_dir = wp_upload_dir();
        return $upload_dir['basedir'] . '/' . $this->upload_dir;
    }
    
    /**
     * Get upload directory URL
     */
    private function get_upload_url() {
        $upload_dir = wp_upload_dir();
        return $upload_dir['baseurl'] . '/' . $this->upload_dir;
    }
    
    /**
     * Create upload directory
     */
    public function create_upload_directory() {
        $upload_dir = $this->get_upload_directory();
        if (!file_exists($upload_dir)) {
            wp_mkdir_p($upload_dir);
            // Create index.php to prevent directory listing
            $index_file = $upload_dir . '/index.php';
            if (!file_exists($index_file)) {
                file_put_contents($index_file, '<?php // Silence is golden');
            }
        }
    }
    
    /**
     * Add custom columns to admin list
     */
    public function add_custom_columns($columns) {
        $new_columns = array();
        $new_columns['cb'] = $columns['cb'];
        $new_columns['title'] = $columns['title'];
        $new_columns['related_order'] = __('سفارش مربوطه', 'arta-poyeshlab');
        $new_columns['files_count'] = __('تعداد فایل‌ها', 'arta-poyeshlab');
        $new_columns['date'] = $columns['date'];
        
        return $new_columns;
    }
    
    /**
     * Render custom columns
     */
    public function render_custom_columns($column, $post_id) {
        switch ($column) {
            case 'related_order':
                $order_id = get_post_meta($post_id, '_apl_related_order_id', true);
                if ($order_id) {
                    $order = wc_get_order($order_id);
                    if ($order) {
                        $order_url = admin_url('post.php?post=' . $order_id . '&action=edit');
                        echo '<a href="' . esc_url($order_url) . '">';
                        echo sprintf(__('سفارش #%s', 'arta-poyeshlab'), $order->get_order_number());
                        echo '</a>';
                    } else {
                        echo '—';
                    }
                } else {
                    echo '—';
                }
                break;
                
            case 'files_count':
                $files = get_post_meta($post_id, '_apl_test_result_files', true);
                if (is_array($files) && !empty($files)) {
                    echo count($files);
                } else {
                    echo '0';
                }
                break;
        }
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        $screen = get_current_screen();
        
        if (isset($screen->post_type) && $screen->post_type === $this->post_type) {
            // Scripts are already loaded inline in meta box
            // Add any additional styles if needed
        }
    }
}

// Initialize the class
new APL_Lab_Test_Results();

