<?php

function apl_get_lab_package_products() {
        // Check if WooCommerce is active
        if (!function_exists('wc_get_products')) {
            return array();
        }
        
        // Get products using WordPress get_posts with meta_query
        $product_posts = get_posts(array(
            'post_type' => 'product',
            'post_status' => 'publish',
            'numberposts' => -1,
            'meta_query' => array(
                array(
                    'key' => '_apl_product_type',
                    'value' => 'lab_package',
                    'compare' => '=',
                ),
            ),
        ));
        
        // Convert to WooCommerce product objects
        $products = array();
        foreach ($product_posts as $post) {
            $product = wc_get_product($post->ID);
            if ($product) {
                // Double check the meta value to be sure
                $product_type = get_post_meta($post->ID, '_apl_product_type', true);
                if ($product_type === 'lab_package') {
                    $products[] = $product;
                }
            }
        }

        $currency_symbol = function_exists('get_woocommerce_currency_symbol') ? get_woocommerce_currency_symbol() : '';
        $service_label = function ($value) {
            switch ($value) {
                case 'home_sampling':
                    return 'نمونه‌گیری در منزل';
                case 'lab_visit':
                    return 'مراجعه به آزمایشگاه';
                case 'sample_shipping':
                    return 'ارسال نمونه';
                default:
                    return 'نامشخص';
            }
        };

        $result = array();
        foreach ($products as $product) {
            $product_id = $product->get_id();
            $title = $product->get_name();
            $short_desc = $product->get_short_description();
            $thumb_id = get_post_thumbnail_id($product_id);
            $image_url = $thumb_id ? wp_get_attachment_image_url($thumb_id, 'full') : '';
            $service_method_value = get_post_meta($product_id, '_apl_service_delivery_method', true);
            $service_method = array(
                'value' => $service_method_value ? $service_method_value : '',
                'label' => $service_label($service_method_value),
            );

            $package_tests = get_post_meta($product_id, '_apl_package_tests', true);
            if (!is_array($package_tests)) {
                $package_tests = empty($package_tests) ? array() : array((int) $package_tests);
            }
            $package_items = array();
            foreach ($package_tests as $test_id) {
                $test_post = get_post($test_id);
                if ($test_post && $test_post->post_type === 'product') {
                    $package_items[] = array(
                        'id' => (int) $test_id,
                        'title' => get_the_title($test_id),
                    );
                }
            }

            $regular_price = $product->get_regular_price();
            $sale_price = $product->get_sale_price();
            $is_on_sale = $product->is_on_sale();

            $prices = array(
                'has_discount' => (bool) $is_on_sale,
                'currency_symbol' => $currency_symbol,
                'regular' => array(
                    'raw' => $regular_price !== '' ? (float) $regular_price : null,
                    'formatted' => function_exists('wc_price') && $regular_price !== '' ? wc_price($regular_price) : ($regular_price !== '' ? $regular_price . ' ' . $currency_symbol : ''),
                ),
                'sale' => array(
                    'raw' => $sale_price !== '' ? (float) $sale_price : null,
                    'formatted' => function_exists('wc_price') && $sale_price !== '' ? wc_price($sale_price) : ($sale_price !== '' ? $sale_price . ' ' . $currency_symbol : ''),
                ),
            );

            $result[] = (object) array(
                'id' => (int) $product_id,
                'title' => $title,
                'short_description' => $short_desc,
                'thumbnail_url' => $image_url,
                'service_delivery' => $service_method,
                'package_items' => $package_items,
                'prices' => $prices,
            );
        }

        return $result;
}

