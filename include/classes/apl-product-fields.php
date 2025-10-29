<?php

namespace APL\Classes;

class APL_Product_Fields {
    
    public function __construct() {
        $this->init();
    }
    
    public function init() {
        // Add custom fields to product edit page
        add_action('woocommerce_product_options_general_product_data', array($this, 'add_custom_product_fields'));
        
        // Save custom fields
        add_action('woocommerce_process_product_meta', array($this, 'save_custom_product_fields'));
        
        // Enqueue admin scripts
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }
    
    /**
     * Add custom fields to product general tab
     */
    public function add_custom_product_fields() {
        global $post;
        
        echo '<div class="options_group">';
        
        // Field 1: Product Type (نوع محصول)
        woocommerce_wp_select(array(
            'id' => '_apl_product_type',
            'label' => __('نوع محصول', 'arta-poyeshlab'),
            'options' => array(
                '' => __('انتخاب کنید...', 'arta-poyeshlab'),
                'lab_test' => __('آزمایش', 'arta-poyeshlab'),
                'lab_package' => __('بسته آزمایش', 'arta-poyeshlab')
            ),
            'value' => get_post_meta($post->ID, '_apl_product_type', true),
            'wrapper_class' => 'form-field-wide'
        ));
        
        // Field 2: Service Delivery Method (نوع ارائه خدمت)
        woocommerce_wp_select(array(
            'id' => '_apl_service_delivery_method',
            'label' => __('نوع ارائه خدمت', 'arta-poyeshlab'),
            'options' => array(
                '' => __('انتخاب کنید...', 'arta-poyeshlab'),
                'home_sampling' => __('نمونه‌گیری در منزل', 'arta-poyeshlab'),
                'lab_visit' => __('مراجعه به آزمایشگاه', 'arta-poyeshlab'),
                'sample_shipping' => __('ارسال نمونه', 'arta-poyeshlab')
            ),
            'value' => get_post_meta($post->ID, '_apl_service_delivery_method', true),
            'wrapper_class' => 'form-field-wide'
        ));
        
        // Field 3: Test Package Items (محصولات بسته) - Only for lab packages
        $product_type = get_post_meta($post->ID, '_apl_product_type', true);
        $wrapper_class = 'form-field-wide';
        
        woocommerce_wp_select(array(
            'id' => '_apl_package_tests',
            'name' => '_apl_package_tests[]',
            'label' => __('محصولات بسته آزمایش', 'arta-poyeshlab'),
            'options' => $this->get_all_products_options(),
            'value' => get_post_meta($post->ID, '_apl_package_tests', true),
            'wrapper_class' => $wrapper_class,
            'custom_attributes' => array(
                'data-placeholder' => __('جستجو و انتخاب محصولات...', 'arta-poyeshlab'),
                'class' => 'apl-package-tests-select-main',
                'multiple' => 'multiple'
            )
        ));
        
        // Display selected tests list
       $this->display_selected_tests_list($post->ID);
        
        echo '</div>';
    }
    
    /**
     * Get all products for package selection
     */
    private function get_all_products_options() {
        $products = get_posts(array(
            'post_type' => 'product',
            'post_status' => 'publish',
            'numberposts' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        ));
        
        $options = array();
        foreach ($products as $product) {
            $service_delivery_method = get_post_meta($product->ID, '_apl_service_delivery_method', true);
            $service_text = '';
            
            switch($service_delivery_method) {
                case 'home_sampling':
                    $service_text = 'نمونه‌گیری در منزل';
                    break;
                case 'lab_visit':
                    $service_text = 'مراجعه به آزمایشگاه';
                    break;
                case 'sample_shipping':
                    $service_text = 'ارسال نمونه';
                    break;
                default:
                    $service_text = 'نامشخص';
            }
            
            $options[$product->ID] = $product->post_title . ' (نوع ارائه خدمت: ' . $service_text . ' - ID: ' . $product->ID . ')';
        }
        
        return $options;
    }
    
    /**
     * Display selected tests list
     */
    private function display_selected_tests_list($post_id) {
        $selected_tests = get_post_meta($post_id, '_apl_package_tests', true);
        if (empty($selected_tests)) {
            $selected_tests = array();
        }
        
        if (!is_array($selected_tests)) {
            $selected_tests = array($selected_tests);
        }
        
        echo '<div id="apl-selected-tests-list" style="margin-top: 15px; clear: both; width: 100%;display:none">';
        echo '<p style="font-weight: bold; margin: 0 0 8px 0; font-size: 14px; color: #1d2327;">' . __('محصولات انتخاب شده:', 'arta-poyeshlab') . '</p>';
        echo '<div id="apl-tests-list" style="max-height: 200px;overflow-y: auto;border: 1px solid #ccd0d4;border-radius: 4px;background: #fff;width: 56%;margin-right: 3%;margin-bottom: 5%;">';
        
        if (empty($selected_tests) || (count($selected_tests) == 1 && empty($selected_tests[0]))) {
            echo '<div style="padding: 15px; text-align: center; color: #666; font-style: italic;">هیچ محصولی انتخاب نشده است</div>';
        } else {
        foreach ($selected_tests as $test_id) {
            if ($test_id) {
                $test = get_post($test_id);
                if ($test) {
                        $service_delivery_method = get_post_meta($test_id, '_apl_service_delivery_method', true);
                        $service_text = '';
                        
                        switch($service_delivery_method) {
                            case 'home_sampling':
                                $service_text = 'نمونه‌گیری در منزل';
                                break;
                            case 'lab_visit':
                                $service_text = 'مراجعه به آزمایشگاه';
                                break;
                            case 'sample_shipping':
                                $service_text = 'ارسال نمونه';
                                break;
                            default:
                                $service_text = 'نامشخص';
                        }
                        
                        echo '<div class="apl-test-item" data-test-id="' . esc_attr($test_id) . '" style="padding: 10px 15px; border-bottom: 1px solid #f0f0f1; display: flex; justify-content: space-between; align-items: center;">';
                        echo '<div style="flex: 1;">';
                        echo '<div style="font-weight: 500; color: #1d2327;">' . esc_html($test->post_title) . '</div>';
                        echo '<div style="font-size: 12px; color: #646970; margin-top: 2px;">' . $service_text . ' - ID: ' . $test_id . '</div>';
                        echo '</div>';
                        echo '<button type="button" class="button apl-remove-test" style="background: #d63638; color: white; border: none; padding: 4px 8px; border-radius: 3px; font-size: 12px; cursor: pointer;">حذف</button>';
                    echo '</div>';
                    }
                }
            }
        }
        
        echo '</div>';
        echo '</div>';

        ?>
        <script>
        jQuery(document).ready(function($) {
            // Initialize Select2 for package tests
            if ($('#_apl_package_tests').length) {
                $('#_apl_package_tests').select2({
                    placeholder: 'جستجو و انتخاب محصولات...',
                    allowClear: true
                });
                
                // Update selected tests list when selection changes
                $('#_apl_package_tests').on('change', function() {
                    updateSelectedTestsList();
                });
            }
            
            // Function to update selected tests list
            function updateSelectedTestsList() {
                var selectedValues = $('#_apl_package_tests').val() || [];
                var listContainer = $('#apl-tests-list');
                listContainer.empty();
                
                if (selectedValues.length === 0) {
                    listContainer.html('<div style="padding: 15px; text-align: center; color: #666; font-style: italic;">هیچ محصولی انتخاب نشده است</div>');
                    return;
                }
                
                selectedValues.forEach(function(testId) {
                    if (testId) {
                        var option = $('#_apl_package_tests option[value="' + testId + '"]');
                        var optionText = option.text();
                        
                        // Extract product name and service info from option text
                        var parts = optionText.split(' (نوع ارائه خدمت: ');
                        var productName = parts[0];
                        var serviceInfo = parts[1] ? parts[1].replace(')', '') : '';
                        
                        var itemDiv = $('<div class="apl-test-item" data-test-id="' + testId + '" style="padding: 10px 15px; border-bottom: 1px solid #f0f0f1; display: flex; justify-content: space-between; align-items: center;">' +
                            '<div style="flex: 1;">' +
                            '<div style="font-weight: 500; color: #1d2327;">' + productName + '</div>' +
                            '<div style="font-size: 12px; color: #646970; margin-top: 2px;">' + serviceInfo + '</div>' +
                            '</div>' +
                            '<button type="button" class="button apl-remove-test" style="background: #d63638; color: white; border: none; padding: 4px 8px; border-radius: 3px; font-size: 12px; cursor: pointer;">حذف</button>' +
                            '</div>');
                        
                        listContainer.append(itemDiv);
                    }
                });
            }
            
            // Handle remove button clicks
            $(document).on('click', '.apl-remove-test', function() {
                var testId = $(this).closest('.apl-test-item').data('test-id');
                $('#_apl_package_tests option[value="' + testId + '"]').prop('selected', false);
                $('#_apl_package_tests').trigger('change');
            });
            
            // Function to toggle package tests field visibility
            function togglePackageTestsField() {
                var productType = $('#_apl_product_type').val();
                var packageField = $('._apl_package_tests_field');
                var selectedTestsList = $('#apl-selected-tests-list');
                
                if (productType === 'lab_package') {
                    packageField.show();
                    selectedTestsList.show();
                } else {
                    packageField.hide();
                    selectedTestsList.hide();
                }
            }
            
            // Initial check
            togglePackageTestsField();
            
            // Initial update of selected tests list
            updateSelectedTestsList();
            
            // Listen for product type changes
            $('#_apl_product_type').on('change', function() {
                togglePackageTestsField();
            });
        });
        </script>
        <?php
    }
    
    /**
     * Save custom product fields
     */
    public function save_custom_product_fields($post_id) {
        // Save product type
        if (isset($_POST['_apl_product_type'])) {
            update_post_meta($post_id, '_apl_product_type', sanitize_text_field($_POST['_apl_product_type']));
        }
        
        // Save service delivery method
        if (isset($_POST['_apl_service_delivery_method'])) {
            update_post_meta($post_id, '_apl_service_delivery_method', sanitize_text_field($_POST['_apl_service_delivery_method']));
        }
        
        // Save package tests
        if (isset($_POST['_apl_package_tests'])) {
            $package_tests = $_POST['_apl_package_tests'];
            if (is_array($package_tests)) {
                $package_tests = array_map('intval', $package_tests);
                $package_tests = array_filter($package_tests); // Remove empty values
                update_post_meta($post_id, '_apl_package_tests', $package_tests);
            } else {
                update_post_meta($post_id, '_apl_package_tests', array());
            }
        } else {
            update_post_meta($post_id, '_apl_package_tests', array());
        }
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        global $post_type;
        
        if ($hook == 'post.php' || $hook == 'post-new.php') {
            if ($post_type == 'product') {
                wp_enqueue_script('select2');
                wp_enqueue_style('select2');
                

               
            }
        }
    }
    
    /**
     * Get admin JavaScript
     */
   
}

// Initialize the class
new APL_Product_Fields();
