<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Lab Test Product Class
 * 
 * @class WC_Product_Lab_Test
 * @extends WC_Product
 */
class WC_Product_Lab_Test extends WC_Product {
    
    /**
     * Product type
     * 
     * @var string
     */
    protected $product_type = 'lab_test';
    
    /**
     * Get product type
     * 
     * @return string
     */
    public function get_type() {
        return 'lab_test';
    }
    
    /**
     * Check if product is virtual
     * Lab tests are virtual products (no shipping required)
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
