<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * WooCommerce Product Types Manager
 * 
 * @class APL_WC_Product_Types
 */
class APL_WC_Product_Types {
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Register product types
        add_filter('product_type_selector', array($this, 'add_product_types'));
        add_filter('woocommerce_product_class', array($this, 'woocommerce_product_class'), 10, 2);
        
        // Add custom fields to product edit page
        add_action('woocommerce_product_options_general_product_data', array($this, 'add_custom_fields'));
        
        // Add price fields for custom product types
        add_action('woocommerce_product_options_pricing', array($this, 'add_price_fields'));
        
        // Add JavaScript to show/hide fields based on product type
        add_action('admin_footer', array($this, 'add_product_type_script'));
        
        // Save custom fields
        add_action('woocommerce_process_product_meta', array($this, 'save_custom_fields'));
        
        // Hide shipping tab for custom product types
        add_filter('woocommerce_product_data_tabs', array($this, 'hide_shipping_tab'));
        
        // Hide shipping tab options for custom product types
        add_filter('product_type_options', array($this, 'hide_shipping_options'));
        
        // Enqueue admin scripts for product search
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }
    
    /**
     * Add custom product types to selector
     * 
     * @param array $types
     * @return array
     */
    public function add_product_types($types) {
        $types['lab_test'] = __('آزمایش', 'arta-poyeshlab');
        $types['lab_package'] = __('بسته آزمایش', 'arta-poyeshlab');
        
        return $types;
    }
    
    /**
     * Map product types to classes
     * 
     * @param string $classname
     * @param string $product_type
     * @return string
     */
    public function woocommerce_product_class($classname, $product_type) {
        switch ($product_type) {
            case 'lab_test':
                return 'WC_Product_Lab_Test';
            case 'lab_package':
                return 'WC_Product_Lab_Package';
            default:
                return $classname;
        }
    }
    
    /**
     * Add custom fields to product edit page
     */
    public function add_custom_fields() {
        global $post, $thepostid;
        
        echo '<div class="options_group" id="apl_custom_fields" style="display: none;">';
        
        // Sample method field for both product types
        $sample_method_options = array(
            '' => __('انتخاب کنید...', 'arta-poyeshlab'),
            'home_sampling' => __('نمونه‌گیری در منزل', 'arta-poyeshlab'),
            'lab_visit' => __('مراجعه به آزمایشگاه', 'arta-poyeshlab'),
            'sample_sending' => __('ارسال نمونه', 'arta-poyeshlab')
        );
        
        $current_sample_method = '';
        if ($post && $post->ID) {
            $product = wc_get_product($post->ID);
            if ($product) {
                $current_sample_method = $product->get_meta('sample_method');
            }
        }
        
        woocommerce_wp_select(array(
            'id' => 'sample_method',
            'name' => 'sample_method',
            'label' => __('روش نمونه‌گیری', 'arta-poyeshlab'),
            'options' => $sample_method_options,
            'value' => $current_sample_method,
            'desc_tip' => true,
            'description' => __('روش نمونه‌گیری برای این محصول را انتخاب کنید', 'arta-poyeshlab')
        ));
        
        // Included tests field for lab_package
        $included_tests = array();
        if ($post && $post->ID) {
            $product = wc_get_product($post->ID);
            if ($product) {
                $included_tests = $product->get_meta('included_tests');
                $included_tests = is_array($included_tests) ? $included_tests : array();
            }
        }
        
        echo '<p class="form-field included_tests_field" id="included_tests_field" style="display: none;">';
        echo '<label for="included_tests">' . __('آزمایش‌های شامل', 'arta-poyeshlab') . '</label>';
        echo '<select id="included_tests" name="included_tests[]" class="wc-product-search" multiple="multiple" data-placeholder="' . __('آزمایش‌ها را جستجو و انتخاب کنید...', 'arta-poyeshlab') . '" data-action="woocommerce_json_search_products" data-exclude="' . ($post ? $post->ID : '') . '">';
        
        foreach ($included_tests as $test_id) {
            $test = get_post($test_id);
            if ($test) {
                echo '<option value="' . esc_attr($test_id) . '" selected="selected">' . esc_html($test->post_title) . '</option>';
            }
        }
        
        echo '</select>';
        echo '<span class="description">' . __('آزمایش‌هایی که در این بسته گنجانده شده‌اند را انتخاب کنید', 'arta-poyeshlab') . '</span>';
        echo '</p>';
        
        echo '</div>';
    }
    
    /**
     * Add price fields for custom product types
     */
    public function add_price_fields() {
        global $post;
        
        // Get product type from POST data or existing product
        $product_type = '';
        if (isset($_POST['product-type'])) {
            $product_type = sanitize_text_field($_POST['product-type']);
        } elseif ($post && $post->ID) {
            $product = wc_get_product($post->ID);
            if ($product) {
                $product_type = $product->get_type();
            }
        }
        
        // Only show price fields for our custom product types
        if (!in_array($product_type, array('lab_test', 'lab_package'))) {
            return;
        }
        
        // Get current prices
        $regular_price = '';
        $sale_price = '';
        if ($post && $post->ID) {
            $product = wc_get_product($post->ID);
            if ($product) {
                $regular_price = $product->get_regular_price();
                $sale_price = $product->get_sale_price();
            }
        }
        
        echo '<div class="options_group pricing show_if_lab_test show_if_lab_package">';
        
        // Regular price field
        woocommerce_wp_text_input(array(
            'id' => '_regular_price',
            'name' => '_regular_price',
            'label' => __('قیمت عادی', 'woocommerce') . ' (' . get_woocommerce_currency_symbol() . ')',
            'value' => $regular_price,
            'type' => 'number',
            'custom_attributes' => array(
                'step' => '0.01',
                'min' => '0'
            )
        ));
        
        // Sale price field
        woocommerce_wp_text_input(array(
            'id' => '_sale_price',
            'name' => '_sale_price',
            'label' => __('قیمت فروش ویژه', 'woocommerce') . ' (' . get_woocommerce_currency_symbol() . ')',
            'value' => $sale_price,
            'type' => 'number',
            'custom_attributes' => array(
                'step' => '0.01',
                'min' => '0'
            )
        ));
        
        echo '</div>';
    }
    
    /**
     * Save custom fields
     * 
     * @param int $post_id
     */
    public function save_custom_fields($post_id) {
        $product = wc_get_product($post_id);
        if (!$product) {
            return;
        }
        
        // Only save for our custom product types
        if (!in_array($product->get_type(), array('lab_test', 'lab_package'))) {
            return;
        }
        
        // Save sample method
        if (isset($_POST['sample_method'])) {
            $sample_method = sanitize_text_field($_POST['sample_method']);
            $product->update_meta_data('sample_method', $sample_method);
        }
        
        // Save prices
        if (isset($_POST['_regular_price'])) {
            $regular_price = sanitize_text_field($_POST['_regular_price']);
            $product->set_regular_price($regular_price);
        }
        
        if (isset($_POST['_sale_price'])) {
            $sale_price = sanitize_text_field($_POST['_sale_price']);
            $product->set_sale_price($sale_price);
        }
        
        // Save included tests (only for lab_package)
        if ($product->get_type() === 'lab_package' && isset($_POST['included_tests'])) {
            $included_tests = array_map('intval', $_POST['included_tests']);
            $included_tests = array_filter($included_tests); // Remove empty values
            $product->update_meta_data('included_tests', $included_tests);
        } elseif ($product->get_type() === 'lab_package') {
            // If no tests selected, save empty array
            $product->update_meta_data('included_tests', array());
        }
        
        $product->save();
    }
    
    /**
     * Hide shipping tab for custom product types
     * 
     * @param array $tabs
     * @return array
     */
    public function hide_shipping_tab($tabs) {
        global $post;
        
        // Check if we're on product edit page
        if (isset($_GET['post']) || isset($_POST['post_ID'])) {
            $post_id = isset($_GET['post']) ? intval($_GET['post']) : intval($_POST['post_ID']);
            $product = wc_get_product($post_id);
            
            if ($product && in_array($product->get_type(), array('lab_test', 'lab_package'))) {
                unset($tabs['shipping']);
            }
        }
        
        // Also check for new products based on product type selector
        if (isset($_POST['product-type']) && in_array($_POST['product-type'], array('lab_test', 'lab_package'))) {
            unset($tabs['shipping']);
        }
        
        return $tabs;
    }
    
    /**
     * Hide shipping options for custom product types
     * 
     * @param array $options
     * @return array
     */
    public function hide_shipping_options($options) {
        global $post;
        
        // Check if we're on product edit page
        if (isset($_GET['post']) || isset($_POST['post_ID'])) {
            $post_id = isset($_GET['post']) ? intval($_GET['post']) : intval($_POST['post_ID']);
            $product = wc_get_product($post_id);
            
            if ($product && in_array($product->get_type(), array('lab_test', 'lab_package'))) {
                unset($options['virtual']);
                unset($options['downloadable']);
            }
        }
        
        // Also check for new products based on product type selector
        if (isset($_POST['product-type']) && in_array($_POST['product-type'], array('lab_test', 'lab_package'))) {
            unset($options['virtual']);
            unset($options['downloadable']);
        }
        
        return $options;
    }
    
    /**
     * Add JavaScript to show/hide fields based on product type
     */
    public function add_product_type_script() {
        global $post;
        
        // Only add script on product edit pages
        if (!$post || $post->post_type !== 'product') {
            return;
        }
        
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Function to show/hide custom fields
            function toggleCustomFields() {
                var productType = $('#product-type').val();
                
                if (productType === 'lab_test' || productType === 'lab_package') {
                    $('#apl_custom_fields').show();
                    $('.show_if_lab_test, .show_if_lab_package').show();
                    
                    if (productType === 'lab_package') {
                        $('#included_tests_field').show();
                    } else {
                        $('#included_tests_field').hide();
                    }
                } else {
                    $('#apl_custom_fields').hide();
                    $('.show_if_lab_test, .show_if_lab_package').hide();
                }
            }
            
            // Initial toggle
            toggleCustomFields();
            
            // Toggle on product type change
            $('#product-type').on('change', function() {
                toggleCustomFields();
            });
        });
        </script>
        <?php
    }
    
    /**
     * Enqueue admin scripts
     * 
     * @param string $hook
     */
    public function enqueue_admin_scripts($hook) {
        global $post;
        
        // Only load on product edit page
        if ($hook !== 'post.php' && $hook !== 'post-new.php') {
            return;
        }
        
        if (!$post || $post->post_type !== 'product') {
            return;
        }
        
        $product = wc_get_product($post->ID);
        if (!$product || !in_array($product->get_type(), array('lab_test', 'lab_package'))) {
            return;
        }
        
        // Enqueue WooCommerce admin scripts for product search
        wp_enqueue_script('wc-admin-product-meta-boxes');
        wp_enqueue_script('wc-admin-variation-meta-boxes');
    }
}
