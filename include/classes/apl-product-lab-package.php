<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Lab Package Product Class
 * 
 * @class WC_Product_Lab_Package
 * @extends WC_Product
 */
class WC_Product_Lab_Package extends WC_Product {
    
    /**
     * Product type
     * 
     * @var string
     */
    protected $product_type = 'lab_package';
    
    /**
     * Get product type
     * 
     * @return string
     */
    public function get_type() {
        return 'lab_package';
    }
    
    /**
     * Check if product is virtual
     * Lab packages are virtual products (no shipping required)
     * 
     * @return bool
     */
    public function is_virtual() {
        return true;
    }
    
    /**
     * Check if product is downloadable
     * 
     * @return bool
     */
    public function is_downloadable() {
        return false;
    }
    
    /**
     * Check if product is sold individually
     * 
     * @return bool
     */
    public function is_sold_individually() {
        return true;
    }
    
    /**
     * Check if product needs shipping
     * 
     * @return bool
     */
    public function needs_shipping() {
        return false;
    }
    
    /**
     * Check if product is taxable
     * 
     * @return bool
     */
    public function is_taxable() {
        return true;
    }
    
    /**
     * Get sample method
     * 
     * @return string
     */
    public function get_sample_method() {
        return $this->get_meta('sample_method');
    }
    
    /**
     * Set sample method
     * 
     * @param string $method
     */
    public function set_sample_method($method) {
        $this->update_meta_data('sample_method', $method);
    }
    
    /**
     * Get included tests
     * 
     * @return array
     */
    public function get_included_tests() {
        $tests = $this->get_meta('included_tests');
        return is_array($tests) ? $tests : array();
    }
    
    /**
     * Set included tests
     * 
     * @param array $tests
     */
    public function set_included_tests($tests) {
        $this->update_meta_data('included_tests', is_array($tests) ? $tests : array());
    }
    
    /**
     * Get included test products
     * 
     * @return array
     */
    public function get_included_test_products() {
        $test_ids = $this->get_included_tests();
        $products = array();
        
        foreach ($test_ids as $test_id) {
            $product = wc_get_product($test_id);
            if ($product && $product->get_type() === 'lab_test') {
                $products[] = $product;
            }
        }
        
        return $products;
    }
    
    /**
     * Get sample method options
     * 
     * @return array
     */
    public static function get_sample_method_options() {
        return array(
            'home_sampling' => __('نمونه‌گیری در منزل', 'arta-poyeshlab'),
            'lab_visit' => __('مراجعه به آزمایشگاه', 'arta-poyeshlab'),
            'sample_sending' => __('ارسال نمونه', 'arta-poyeshlab')
        );
    }
    
    /**
     * Get sample method label
     * 
     * @return string
     */
    public function get_sample_method_label() {
        $method = $this->get_sample_method();
        $options = self::get_sample_method_options();
        return isset($options[$method]) ? $options[$method] : '';
    }
    
    /**
     * Get included tests labels
     * 
     * @return array
     */
    public function get_included_tests_labels() {
        $tests = $this->get_included_test_products();
        $labels = array();
        
        foreach ($tests as $test) {
            $labels[] = $test->get_name();
        }
        
        return $labels;
    }
    
    /**
     * Get regular price
     * 
     * @param string $context
     * @return string
     */
    public function get_regular_price($context = 'view') {
        return $this->get_prop('regular_price', $context);
    }
    
    /**
     * Set regular price
     * 
     * @param string $price
     */
    public function set_regular_price($price) {
        $this->set_prop('regular_price', $price);
    }
    
    /**
     * Get sale price
     * 
     * @param string $context
     * @return string
     */
    public function get_sale_price($context = 'view') {
        return $this->get_prop('sale_price', $context);
    }
    
    /**
     * Set sale price
     * 
     * @param string $price
     */
    public function set_sale_price($price) {
        $this->set_prop('sale_price', $price);
    }
    
    /**
     * Get price
     * 
     * @param string $context
     * @return string
     */
    public function get_price($context = 'view') {
        $price = $this->get_sale_price() ? $this->get_sale_price() : $this->get_regular_price();
        return $price;
    }
    
    /**
     * Check if product is on sale
     * 
     * @param string $context
     * @return bool
     */
    public function is_on_sale($context = 'view') {
        return $this->get_sale_price($context) && $this->get_sale_price($context) < $this->get_regular_price($context);
    }
}
