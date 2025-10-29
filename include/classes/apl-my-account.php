<?php
namespace APL\Classes;
class APL_My_Account {
    public function __construct() {
        add_filter('pre_handle_404', array($this, 'prevent_404'), 10, 2);
        add_filter('template_include', array($this, 'template_include'));
    }

    public function prevent_404($preempt, $wp_query) {
        $request_uri = trim($_SERVER['REQUEST_URI'], '/');
        $home_path = trim(parse_url(home_url(), PHP_URL_PATH), '/');
        
        if ($home_path) {
            $request_uri = str_replace($home_path, '', $request_uri);
            $request_uri = trim($request_uri, '/');
        }
        
        if ($request_uri === 'lab-portal') {
            return true;
        }
        
        return $preempt;
    }

    public function template_include($template) {
        $request_uri = trim($_SERVER['REQUEST_URI'], '/');
        $home_path = trim(parse_url(home_url(), PHP_URL_PATH), '/');
        
        if ($home_path) {
            $request_uri = str_replace($home_path, '', $request_uri);
            $request_uri = trim($request_uri, '/');
        }
        
        if ($request_uri === 'lab-portal') {
            status_header(200);
            
            return ARTA_POYESHLAB_PLUGIN_DIR . 'include/template/layout.php';
        }
        
        return $template;
    }
}
new APL_My_Account();