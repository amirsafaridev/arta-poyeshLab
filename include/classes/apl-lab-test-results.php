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
        <style>
            .apl-order-select-wrapper {
                position: relative;
                width: 100%;
                max-width: 500px;
            }
            .apl-order-select-display {
                position: relative;
                width: 100%;
                padding: 8px 35px 8px 12px;
                border: 1px solid #8c8f94;
                border-radius: 4px;
                background: #fff;
                cursor: pointer;
                font-size: 14px;
                line-height: 20px;
                min-height: 36px;
                box-sizing: border-box;
                transition: border-color 0.2s ease;
            }
            .apl-order-select-display:hover {
                border-color: #2271b1;
            }
            .apl-order-select-display.open {
                border-color: #2271b1;
                box-shadow: 0 0 0 1px #2271b1;
            }
            .apl-order-select-display-placeholder {
                color: #646970;
            }
            .apl-order-select-arrow {
                position: absolute;
                right: 12px;
                top: 50%;
                transform: translateY(-50%);
                color: #646970;
                pointer-events: none;
                transition: transform 0.2s ease;
            }
            .apl-order-select-display.open .apl-order-select-arrow {
                transform: translateY(-50%) rotate(180deg);
            }
            .apl-order-dropdown {
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                margin-top: 4px;
                border: 1px solid #8c8f94;
                border-radius: 4px;
                background: #fff;
                box-shadow: 0 2px 8px rgba(0,0,0,0.15);
                z-index: 1000;
                display: none;
                overflow: hidden;
            }
            .apl-order-dropdown.open {
                display: block;
            }
            .apl-order-search-box {
                padding: 8px;
                border-bottom: 1px solid #f0f0f1;
            }
            .apl-order-search-input {
                width: 100%;
                padding: 6px 30px 6px 8px;
                border: 1px solid #8c8f94;
                border-radius: 3px;
                font-size: 14px;
                box-sizing: border-box;
            }
            .apl-order-search-input:focus {
                border-color: #2271b1;
                outline: none;
                box-shadow: 0 0 0 1px #2271b1;
            }
            .apl-order-search-icon {
                position: absolute;
                right: 16px;
                top: 16px;
                color: #646970;
                pointer-events: none;
                font-size: 14px;
            }
            .apl-order-options {
                max-height: 250px;
                overflow-y: auto;
                overflow-x: hidden;
            }
            .apl-order-option {
                padding: 8px 12px;
                cursor: pointer;
                font-size: 14px;
                line-height: 20px;
                transition: background-color 0.15s ease;
                display: block;
            }
            .apl-order-option:hover {
                background-color: #f6f7f7;
            }
            .apl-order-option.selected {
                background-color: #2271b1;
                color: #fff;
            }
            .apl-order-option.hidden {
                display: none;
            }
            .apl-order-no-results {
                padding: 12px;
                text-align: center;
                color: #646970;
                font-style: italic;
                font-size: 14px;
                display: none;
            }
            .apl-order-no-results.show {
                display: block;
            }
            .apl-order-select-wrapper select {
                display: none;
            }
        </style>
        
        <div class="apl-test-result-order-field">
            <label for="apl_order_select_display" style="display: block; margin-bottom: 10px; font-weight: 600;">
                <?php _e('انتخاب سفارش:', 'arta-poyeshlab'); ?>
            </label>
            
            <div class="apl-order-select-wrapper" id="apl_order_select_wrapper">
                <div class="apl-order-select-display" id="apl_order_select_display">
                    <?php
                    $display_text = __('-- انتخاب سفارش --', 'arta-poyeshlab');
                    if ($selected_order_id) {
                        $order = wc_get_order($selected_order_id);
                        if ($order) {
                            $order_number = $order->get_order_number();
                            $customer_name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
                            $order_date = $order->get_date_created()->date_i18n('Y/m/d');
                            $display_text = sprintf(
                                __('سفارش #%s - %s - %s', 'arta-poyeshlab'),
                                $order_number,
                                $customer_name,
                                $order_date
                            );
                        }
                    }
                    ?>
                    <span class="<?php echo !$selected_order_id ? 'apl-order-select-display-placeholder' : ''; ?>">
                        <?php echo esc_html($display_text); ?>
                    </span>
                    <span class="apl-order-select-arrow">▼</span>
                </div>
                
                <div class="apl-order-dropdown" id="apl_order_dropdown">
                    <div class="apl-order-search-box" style="position: relative;">
                        <input 
                            type="text" 
                            id="apl_order_search_input" 
                            class="apl-order-search-input" 
                            placeholder="<?php _e('جستجو...', 'arta-poyeshlab'); ?>"
                            autocomplete="off"
                        />
                       
                    </div>
                    <div class="apl-order-options" id="apl_order_options">
                        <?php
                        foreach ($orders as $order_id) {
                            $order = wc_get_order($order_id);
                            if (!$order) continue;
                            
                            $order_number = $order->get_order_number();
                            $customer_name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
                            $order_date = $order->get_date_created()->date_i18n('Y/m/d');
                            
                            // Create searchable text
                            $search_text = sprintf(
                                '%s %s %s %s %s',
                                $order_number,
                                $customer_name,
                                $order_date,
                                $order->get_billing_phone(),
                                $order->get_billing_email()
                            );
                            $search_text = strtolower($search_text);
                            
                            $selected_class = ($selected_order_id == $order_id) ? 'selected' : '';
                            $display_text = sprintf(
                                __('سفارش #%s - %s - %s', 'arta-poyeshlab'),
                                $order_number,
                                $customer_name,
                                $order_date
                            );
                            
                            echo '<div class="apl-order-option ' . $selected_class . '" data-value="' . esc_attr($order_id) . '" data-search="' . esc_attr($search_text) . '">';
                            echo esc_html($display_text);
                            echo '</div>';
                        }
                        ?>
                    </div>
                    <div class="apl-order-no-results" id="apl_order_no_results">
                        <?php _e('نتیجه‌ای یافت نشد', 'arta-poyeshlab'); ?>
                    </div>
                </div>
                
                <select name="apl_related_order_id" id="apl_related_order_id">
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
            </div>
            
            <?php if ($selected_order_id): ?>
                <p style="margin-top: 10px;">
                    <a href="<?php echo admin_url('post.php?post=' . $selected_order_id . '&action=edit'); ?>" target="_blank">
                        <?php _e('مشاهده سفارش', 'arta-poyeshlab'); ?>
                    </a>
                </p>
            <?php endif; ?>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            var wrapper = $('#apl_order_select_wrapper');
            var display = $('#apl_order_select_display');
            var dropdown = $('#apl_order_dropdown');
            var searchInput = $('#apl_order_search_input');
            var selectElement = $('#apl_related_order_id');
            var optionsContainer = $('#apl_order_options');
            var options = optionsContainer.find('.apl-order-option');
            var noResults = $('#apl_order_no_results');
            var isOpen = false;
            
            // Get selected option text
            function getSelectedText() {
                var selectedValue = selectElement.val();
                if (selectedValue) {
                    var selectedOption = options.filter('[data-value="' + selectedValue + '"]');
                    if (selectedOption.length) {
                        return selectedOption.text().trim();
                    }
                }
                return '<?php _e('-- انتخاب سفارش --', 'arta-poyeshlab'); ?>';
            }
            
            // Update display
            function updateDisplay() {
                var selectedValue = selectElement.val();
                var text = getSelectedText();
                display.find('span').first().text(text);
                if (selectedValue) {
                    display.find('span').first().removeClass('apl-order-select-display-placeholder');
                } else {
                    display.find('span').first().addClass('apl-order-select-display-placeholder');
                }
            }
            
            // Toggle dropdown
            function toggleDropdown() {
                isOpen = !isOpen;
                if (isOpen) {
                    display.addClass('open');
                    dropdown.addClass('open');
                    searchInput.val('').focus();
                    filterOptions('');
                } else {
                    display.removeClass('open');
                    dropdown.removeClass('open');
                }
            }
            
            // Close dropdown
            function closeDropdown() {
                if (isOpen) {
                    isOpen = false;
                    display.removeClass('open');
                    dropdown.removeClass('open');
                    searchInput.val('');
                }
            }
            
            // Filter options
            function filterOptions(searchTerm) {
                var term = searchTerm.toLowerCase().trim();
                var visibleCount = 0;
                
                if (term === '') {
                    options.removeClass('hidden');
                    noResults.removeClass('show');
                } else {
                    options.each(function() {
                        var $option = $(this);
                        var searchText = $option.data('search') || '';
                        
                        if (searchText.indexOf(term) !== -1) {
                            $option.removeClass('hidden');
                            visibleCount++;
                        } else {
                            $option.addClass('hidden');
                        }
                    });
                    
                    if (visibleCount === 0) {
                        noResults.addClass('show');
                    } else {
                        noResults.removeClass('show');
                    }
                }
            }
            
            // Open/close on display click
            display.on('click', function(e) {
                e.stopPropagation();
                toggleDropdown();
            });
            
            // Prevent dropdown close when clicking inside
            dropdown.on('click', function(e) {
                e.stopPropagation();
            });
            
            // Close on outside click
            $(document).on('click', function(e) {
                if (!$(e.target).closest(wrapper).length) {
                    closeDropdown();
                }
            });
            
            // Handle option click
            optionsContainer.on('click', '.apl-order-option', function(e) {
                e.stopPropagation();
                var $option = $(this);
                var value = $option.data('value');
                
                // Update selected state
                options.removeClass('selected');
                $option.addClass('selected');
                
                // Update select
                selectElement.val(value).trigger('change');
                
                // Update display
                updateDisplay();
                
                // Close dropdown
                closeDropdown();
            });
            
            // Search input
            searchInput.on('input', function() {
                var term = $(this).val();
                filterOptions(term);
            });
            
            // Keyboard navigation
            searchInput.on('keydown', function(e) {
                var visibleOptions = options.not('.hidden');
                
                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    var selectedOption = optionsContainer.find('.selected');
                    var currentIndex = visibleOptions.index(selectedOption);
                    
                    if (currentIndex < visibleOptions.length - 1) {
                        selectedOption.removeClass('selected');
                        visibleOptions.eq(currentIndex + 1).addClass('selected');
                        scrollToOption(visibleOptions.eq(currentIndex + 1));
                    } else if (visibleOptions.length > 0) {
                        selectedOption.removeClass('selected');
                        visibleOptions.eq(0).addClass('selected');
                        scrollToOption(visibleOptions.eq(0));
                    }
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    var selectedOption = optionsContainer.find('.selected');
                    var currentIndex = visibleOptions.index(selectedOption);
                    
                    if (currentIndex > 0) {
                        selectedOption.removeClass('selected');
                        visibleOptions.eq(currentIndex - 1).addClass('selected');
                        scrollToOption(visibleOptions.eq(currentIndex - 1));
                    } else if (visibleOptions.length > 0) {
                        selectedOption.removeClass('selected');
                        visibleOptions.eq(visibleOptions.length - 1).addClass('selected');
                        scrollToOption(visibleOptions.eq(visibleOptions.length - 1));
                    }
                } else if (e.key === 'Enter') {
                    e.preventDefault();
                    var selectedOption = optionsContainer.find('.selected:not(.hidden)');
                    if (selectedOption.length) {
                        selectedOption.trigger('click');
                    }
                } else if (e.key === 'Escape') {
                    e.preventDefault();
                    closeDropdown();
                }
            });
            
            function scrollToOption($option) {
                var container = $('#apl_order_options');
                var optionTop = $option.position().top + container.scrollTop();
                var optionBottom = optionTop + $option.outerHeight();
                var containerTop = container.scrollTop();
                var containerBottom = containerTop + container.height();
                
                if (optionTop < containerTop) {
                    container.scrollTop(optionTop);
                } else if (optionBottom > containerBottom) {
                    container.scrollTop(optionBottom - container.height());
                }
            }
            
            // Initialize display
            updateDisplay();
        });
        </script>
        <?php
    }
    
    /**
     * Render files meta box
     */
    public function render_files_meta_box($post) {
        $uploaded_file = get_post_meta($post->ID, '_apl_test_result_file', true);
        
        ?>
        <div class="apl-test-result-files-field">
            <style>
                .apl-upload-area {
                    position: relative;
                    border: 2px dashed #c3c4c7;
                    border-radius: 8px;
                    padding: 40px 20px;
                    text-align: center;
                    background: #f9f9f9;
                    transition: all 0.3s ease;
                    cursor: pointer;
                    min-height: 200px;
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                    justify-content: center;
                }
                .apl-upload-area:hover {
                    border-color: #2271b1;
                    background: #f0f6fc;
                }
                .apl-upload-area.drag-over {
                    border-color: #2271b1;
                    background: #e8f4fd;
                    transform: scale(1.02);
                }
                .apl-upload-area.has-file {
                    border-color: #00a32a;
                    background: #f0f6fc;
                    border-style: solid;
                }
                .apl-upload-icon {
                    font-size: 48px;
                    color: #c3c4c7;
                    margin-bottom: 15px;
                    transition: color 0.3s ease;
                }
                .apl-upload-area:hover .apl-upload-icon,
                .apl-upload-area.drag-over .apl-upload-icon {
                    color: #2271b1;
                }
                .apl-upload-area.has-file .apl-upload-icon {
                    color: #00a32a;
                }
                .apl-upload-text {
                    font-size: 16px;
                    color: #50575e;
                    margin-bottom: 8px;
                    font-weight: 500;
                }
                .apl-upload-hint {
                    font-size: 13px;
                    color: #787c82;
                    margin-top: 5px;
                }
                .apl-file-input-hidden {
                    position: absolute;
                    width: 0;
                    height: 0;
                    opacity: 0;
                    overflow: hidden;
                }
                .apl-file-preview {
                    margin-top: 20px;
                    padding: 20px;
                    background: #fff;
                    border: 1px solid #c3c4c7;
                    border-radius: 8px;
                    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
                    display: none;
                }
                .apl-file-preview.show {
                    display: block;
                    animation: fadeIn 0.3s ease;
                }
                @keyframes fadeIn {
                    from { opacity: 0; transform: translateY(-10px); }
                    to { opacity: 1; transform: translateY(0); }
                }
                .apl-file-info {
                    display: flex;
                    align-items: center;
                    gap: 15px;
                    padding-bottom: 15px;
                    border-bottom: 1px solid #e1e1e1;
                    margin-bottom: 15px;
                }
                .apl-file-icon {
                    width: 48px;
                    height: 48px;
                    border-radius: 6px;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    color: #fff;
                    font-size: 24px;
                    font-weight: bold;
                    flex-shrink: 0;
                }
                .apl-file-icon.pdf {
                    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
                }
                .apl-file-icon.image {
                    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
                }
                .apl-file-details {
                    flex: 1;
                    min-width: 0;
                }
                .apl-file-name {
                    font-size: 15px;
                    font-weight: 600;
                    color: #1d2327;
                    margin-bottom: 5px;
                    word-break: break-all;
                }
                .apl-file-meta {
                    font-size: 13px;
                    color: #787c82;
                    display: flex;
                    align-items: center;
                    gap: 12px;
                }
                .apl-file-size {
                    display: inline-flex;
                    align-items: center;
                    gap: 4px;
                }
                .apl-file-actions {
                    display: flex;
                    gap: 10px;
                    margin-top: 15px;
                }
                .apl-btn {
                    padding: 10px 20px;
                    border-radius: 6px;
                    border: none;
                    cursor: pointer;
                    font-size: 14px;
                    font-weight: 500;
                    transition: all 0.2s ease;
                    text-decoration: none;
                    display: inline-flex;
                    align-items: center;
                    gap: 6px;
                }
                .apl-btn-primary {
                    background: #2271b1;
                    color: #fff;
                }
                .apl-btn-primary:hover {
                    background: #135e96;
                    transform: translateY(-1px);
                    box-shadow: 0 4px 8px rgba(34, 113, 177, 0.3);
                }
                .apl-btn-danger {
                    background: #d63638;
                    color: #fff;
                }
                .apl-btn-danger:hover {
                    background: #b32d2e;
                    transform: translateY(-1px);
                    box-shadow: 0 4px 8px rgba(214, 54, 56, 0.3);
                }
                .apl-btn-secondary {
                    background: #f0f0f1;
                    color: #2c3338;
                }
                .apl-btn-secondary:hover {
                    background: #dcdcde;
                }
                .apl-upload-progress {
                    margin-top: 15px;
                    display: none;
                }
                .apl-upload-progress.show {
                    display: block;
                }
                .apl-progress-bar {
                    width: 100%;
                    height: 8px;
                    background: #e1e1e1;
                    border-radius: 4px;
                    overflow: hidden;
                    margin-top: 10px;
                }
                .apl-progress-fill {
                    height: 100%;
                    background: linear-gradient(90deg, #2271b1 0%, #135e96 100%);
                    width: 0%;
                    transition: width 0.3s ease;
                    border-radius: 4px;
                }
                .apl-progress-text {
                    text-align: center;
                    margin-top: 8px;
                    font-size: 13px;
                    color: #50575e;
                }
            </style>
            
            <div id="apl_upload_area" class="apl-upload-area <?php echo !empty($uploaded_file) ? 'has-file' : ''; ?>">
                <input type="file" id="apl_file_upload_input" class="apl-file-input-hidden" />
                
                <div class="apl-upload-icon">📄</div>
                <div class="apl-upload-text" id="apl_upload_text">
                    <?php _e('برای بارگذاری فایل اینجا کلیک کنید یا فایل را بکشید و رها کنید', 'arta-poyeshlab'); ?>
                </div>
                <div class="apl-upload-hint">
                    <?php _e('پشتیبانی از همه فرمت‌های فایل', 'arta-poyeshlab'); ?>
                </div>
            </div>
            
            <div class="apl-upload-progress" id="apl_upload_progress">
                <div class="apl-progress-bar">
                    <div class="apl-progress-fill" id="apl_progress_fill"></div>
                </div>
                <div class="apl-progress-text" id="apl_progress_text"></div>
            </div>
            
            <div class="apl-file-preview <?php echo !empty($uploaded_file) ? 'show' : ''; ?>" id="apl_file_preview">
                <?php if (!empty($uploaded_file) && isset($uploaded_file['url']) && isset($uploaded_file['name'])): ?>
                    <?php
                    $file_ext = strtolower(pathinfo($uploaded_file['name'], PATHINFO_EXTENSION));
                    $icon_class = '';
                    $icon_text = '📄';
                    if (in_array($file_ext, ['pdf'])) {
                        $icon_class = 'pdf';
                        $icon_text = 'PDF';
                    } elseif (in_array($file_ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'])) {
                        $icon_class = 'image';
                        $icon_text = 'IMG';
                    } else {
                        $icon_text = strtoupper($file_ext);
                    }
                    ?>
                    <div class="apl-file-info">
                        <div class="apl-file-icon <?php echo esc_attr($icon_class); ?>">
                            <?php echo esc_html($icon_text); ?>
                        </div>
                        <div class="apl-file-details">
                            <div class="apl-file-name"><?php echo esc_html($uploaded_file['name']); ?></div>
                            <div class="apl-file-meta">
                                <?php if (isset($uploaded_file['size'])): ?>
                                    <span class="apl-file-size">📦 <?php echo esc_html($uploaded_file['size']); ?></span>
                                <?php endif; ?>
                                <?php if (isset($uploaded_file['date'])): ?>
                                    <span>📅 <?php echo esc_html($uploaded_file['date']); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="apl-file-actions">
                        <a href="<?php echo esc_url($uploaded_file['url']); ?>" target="_blank" class="apl-btn apl-btn-primary">
                            <span>👁️</span>
                            <?php _e('مشاهده فایل', 'arta-poyeshlab'); ?>
                        </a>
                        <a href="<?php echo esc_url($uploaded_file['url']); ?>" download class="apl-btn apl-btn-secondary">
                            <span>⬇️</span>
                            <?php _e('دانلود', 'arta-poyeshlab'); ?>
                        </a>
                        <button type="button" class="apl-btn apl-btn-danger apl-delete-file-btn">
                            <span>🗑️</span>
                            <?php _e('حذف فایل', 'arta-poyeshlab'); ?>
                        </button>
                    </div>
                <?php endif; ?>
            </div>
            
            <input type="hidden" name="apl_test_result_file_data" id="apl_test_result_file_data" value="<?php echo esc_attr(!empty($uploaded_file) ? json_encode($uploaded_file) : ''); ?>">
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            var postId = <?php echo $post->ID; ?>;
            var nonce = '<?php echo wp_create_nonce('apl_test_result_files'); ?>';
            var ajaxUrl = typeof ajaxurl !== 'undefined' ? ajaxurl : '<?php echo admin_url('admin-ajax.php'); ?>';
            var uploadArea = $('#apl_upload_area');
            var fileInput = $('#apl_file_upload_input');
            var uploadProgress = $('#apl_upload_progress');
            var progressFill = $('#apl_progress_fill');
            var progressText = $('#apl_progress_text');
            var filePreview = $('#apl_file_preview');
            var hasFile = <?php echo !empty($uploaded_file) ? 'true' : 'false'; ?>;
            var isTriggeringInput = false;
            
            // Click to select file
            uploadArea.on('click', function(e) {
                // Don't trigger if clicking on file preview or delete button
                if ($(e.target).closest('.apl-file-preview').length || 
                    $(e.target).closest('.apl-delete-file-btn').length ||
                    $(e.target).is('a') ||
                    $(e.target).is('button')) {
                    return;
                }
                
                // Don't trigger if clicking directly on file input
                if ($(e.target).is(fileInput) || $(e.target).closest('input[type="file"]').length) {
                    return;
                }
                
                // Prevent infinite loop
                if (isTriggeringInput) {
                    return;
                }
                
                // Trigger file input click
                isTriggeringInput = true;
                e.preventDefault();
                e.stopPropagation();
                
                setTimeout(function() {
                    fileInput[0].click();
                    isTriggeringInput = false;
                }, 10);
            });
            
            // Prevent event bubbling from file input
            fileInput.on('click', function(e) {
                e.stopPropagation();
            });
            
            // File selected
            fileInput.on('change', function(e) {
                e.stopPropagation();
                var file = this.files[0];
                if (file) {
                    uploadFile(file);
                }
            });
            
            // Drag and drop
            uploadArea.on('dragover', function(e) {
                e.preventDefault();
                e.stopPropagation();
                uploadArea.addClass('drag-over');
            });
            
            uploadArea.on('dragleave', function(e) {
                e.preventDefault();
                e.stopPropagation();
                uploadArea.removeClass('drag-over');
            });
            
            uploadArea.on('drop', function(e) {
                e.preventDefault();
                e.stopPropagation();
                uploadArea.removeClass('drag-over');
                
                var files = e.originalEvent.dataTransfer.files;
                if (files.length > 0) {
                    uploadFile(files[0]);
                }
            });
            
            function uploadFile(file) {
                var formData = new FormData();
                formData.append('action', 'apl_upload_test_result_file');
                formData.append('post_id', postId);
                formData.append('nonce', nonce);
                formData.append('file', file);
                
                uploadProgress.addClass('show');
                progressFill.css('width', '0%');
                progressText.text('<?php _e('در حال بارگذاری...', 'arta-poyeshlab'); ?>');
                uploadArea.addClass('has-file');
                fileInput.prop('disabled', true);
                
                $.ajax({
                    url: ajaxUrl,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    xhr: function() {
                        var xhr = new window.XMLHttpRequest();
                        xhr.upload.addEventListener('progress', function(e) {
                            if (e.lengthComputable) {
                                var percentComplete = (e.loaded / e.total) * 100;
                                progressFill.css('width', percentComplete + '%');
                                progressText.text(Math.round(percentComplete) + '%');
                            }
                        }, false);
                        return xhr;
                    },
                    success: function(response) {
                        fileInput.prop('disabled', false);
                        uploadProgress.removeClass('show');
                        
                        if (response.success && response.data.file) {
                            updateFilePreview(response.data.file);
                            hasFile = true;
                        } else {
                            alert(response.data.message || '<?php _e('خطا در بارگذاری فایل', 'arta-poyeshlab'); ?>');
                            uploadArea.removeClass('has-file');
                        }
                    },
                    error: function() {
                        fileInput.prop('disabled', false);
                        uploadProgress.removeClass('show');
                        uploadArea.removeClass('has-file');
                        alert('<?php _e('خطا در ارتباط با سرور', 'arta-poyeshlab'); ?>');
                    }
                });
            }
            
            function updateFilePreview(file) {
                var fileExt = file.name.split('.').pop().toLowerCase();
                var iconClass = '';
                var iconText = '📄';
                
                if (fileExt === 'pdf') {
                    iconClass = 'pdf';
                    iconText = 'PDF';
                } else if (['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'].indexOf(fileExt) !== -1) {
                    iconClass = 'image';
                    iconText = 'IMG';
                } else {
                    iconText = fileExt.toUpperCase();
                }
                
                var html = '<div class="apl-file-info">';
                html += '<div class="apl-file-icon ' + iconClass + '">' + escapeHtml(iconText) + '</div>';
                html += '<div class="apl-file-details">';
                html += '<div class="apl-file-name">' + escapeHtml(file.name) + '</div>';
                html += '<div class="apl-file-meta">';
                if (file.size) {
                    html += '<span class="apl-file-size">📦 ' + escapeHtml(file.size) + '</span>';
                }
                html += '</div></div></div>';
                html += '<div class="apl-file-actions">';
                html += '<a href="' + escapeHtml(file.url) + '" target="_blank" class="apl-btn apl-btn-primary">';
                html += '<span>👁️</span> <?php _e('مشاهده فایل', 'arta-poyeshlab'); ?>';
                html += '</a>';
                html += '<a href="' + escapeHtml(file.url) + '" download class="apl-btn apl-btn-secondary">';
                html += '<span>⬇️</span> <?php _e('دانلود', 'arta-poyeshlab'); ?>';
                html += '</a>';
                html += '<button type="button" class="apl-btn apl-btn-danger apl-delete-file-btn">';
                html += '<span>🗑️</span> <?php _e('حذف فایل', 'arta-poyeshlab'); ?>';
                html += '</button></div>';
                
                filePreview.html(html).addClass('show');
                $('#apl_test_result_file_data').val(JSON.stringify(file));
            }
            
            // Handle file deletion
            $(document).on('click', '.apl-delete-file-btn', function(e) {
                e.stopPropagation();
                if (!confirm('<?php _e('آیا از حذف این فایل مطمئن هستید؟', 'arta-poyeshlab'); ?>')) {
                    return;
                }
                
                $.ajax({
                    url: ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'apl_delete_test_result_file',
                        post_id: postId,
                        nonce: nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            filePreview.removeClass('show').html('');
                            uploadArea.removeClass('has-file');
                            fileInput.val('');
                            $('#apl_test_result_file_data').val('');
                            hasFile = false;
                        } else {
                            alert(response.data.message || '<?php _e('خطا در حذف فایل', 'arta-poyeshlab'); ?>');
                        }
                    },
                    error: function() {
                        alert('<?php _e('خطا در ارتباط با سرور', 'arta-poyeshlab'); ?>');
                    }
                });
            });
            
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
        
        // Save file data (from hidden input set by JavaScript)
        if (isset($_POST['apl_test_result_file_data'])) {
            $file_data = json_decode(stripslashes($_POST['apl_test_result_file_data']), true);
            if (is_array($file_data) && !empty($file_data)) {
                update_post_meta($post_id, '_apl_test_result_file', $file_data);
            } else {
                delete_post_meta($post_id, '_apl_test_result_file');
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
        
        if (!isset($_FILES['file']) || empty($_FILES['file']['name'])) {
            wp_send_json_error(array('message' => __('فایلی انتخاب نشده است', 'arta-poyeshlab')));
        }
        
        // Get existing file and delete it if exists
        $existing_file = get_post_meta($post_id, '_apl_test_result_file', true);
        if (!empty($existing_file) && isset($existing_file['path']) && file_exists($existing_file['path'])) {
            @unlink($existing_file['path']);
        }
        
        $upload_dir = $this->get_upload_directory();
        if (!file_exists($upload_dir)) {
            wp_mkdir_p($upload_dir);
        }
        
        $file = $_FILES['file'];
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            wp_send_json_error(array('message' => __('خطا در بارگذاری فایل', 'arta-poyeshlab')));
        }
        
        // Generate unique filename
        $file_name = sanitize_file_name($file['name']);
        $file_type = wp_check_filetype($file_name);
        $unique_name = time() . '_' . uniqid() . '_' . $file_name;
        $destination = $upload_dir . '/' . $unique_name;
        
        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $destination)) {
            $file_url = $this->get_upload_url() . '/' . $unique_name;
            $file_size = filesize($destination);
            
            $uploaded_file = array(
                'name' => $file_name,
                'url' => $file_url,
                'path' => $destination,
                'size' => size_format($file_size),
                'size_bytes' => $file_size,
                'date' => current_time('Y/m/d H:i')
            );
            
            // Save file data
            update_post_meta($post_id, '_apl_test_result_file', $uploaded_file);
            
            wp_send_json_success(array(
                'message' => __('فایل با موفقیت بارگذاری شد', 'arta-poyeshlab'),
                'file' => $uploaded_file
            ));
        } else {
            wp_send_json_error(array('message' => __('خطا در ذخیره فایل', 'arta-poyeshlab')));
        }
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
        
        if ($post_id <= 0) {
            wp_send_json_error(array('message' => __('اطلاعات نامعتبر', 'arta-poyeshlab')));
        }
        
        // Get existing file
        $existing_file = get_post_meta($post_id, '_apl_test_result_file', true);
        
        if (!empty($existing_file) && isset($existing_file['path']) && file_exists($existing_file['path'])) {
            @unlink($existing_file['path']);
        }
        
        // Delete meta
        delete_post_meta($post_id, '_apl_test_result_file');
        
        wp_send_json_success(array(
            'message' => __('فایل با موفقیت حذف شد', 'arta-poyeshlab')
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
        $new_columns['has_file'] = __('فایل', 'arta-poyeshlab');
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
                
            case 'has_file':
                $file = get_post_meta($post_id, '_apl_test_result_file', true);
                if (!empty($file) && isset($file['url'])) {
                    echo '<span style="color: #00a32a;">✓ ' . __('دارد', 'arta-poyeshlab') . '</span>';
                } else {
                    echo '<span style="color: #d63638;">✗ ' . __('ندارد', 'arta-poyeshlab') . '</span>';
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

