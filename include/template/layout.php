<?php
$logo_id = get_option('apl_login_logo');
$logo_url = $logo_id ? wp_get_attachment_url($logo_id) : '';
$login_title = get_option('apl_login_title', 'آزمایشگاه پویش');
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?=$login_title; ?> - پنل کاربری</title>
        <script src="https://cdn.tailwindcss.com"></script>
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://unpkg.com/persian-datepicker@latest/dist/css/persian-datepicker.min.css">
        <link rel="icon" href="<?=$logo_url; ?>" type="image/x-icon">
        <?php
        // Enqueue styles properly
        wp_enqueue_style('apl-style', ARTA_POYESHLAB_PLUGIN_URL . 'assets/css/style.css');
        
        // Print the styles
        wp_print_styles('apl-style');
        ?>
    </head>
    <body class="bg-gray-50 font-sans">

        <?php include_once ARTA_POYESHLAB_PLUGIN_DIR . 'include/template-helper.php'; ?>
        <?php if(is_user_logged_in()) {
            include_once ARTA_POYESHLAB_PLUGIN_DIR . 'include/template/dashbord.php';
        } else {
            include_once ARTA_POYESHLAB_PLUGIN_DIR . 'include/template/auth.php';
        }
        ?>
    
        
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="https://unpkg.com/persian-date@latest/dist/persian-date.min.js"></script>
        <script src="https://unpkg.com/persian-datepicker@latest/dist/js/persian-datepicker.min.js"></script>
        
        <?php
        // Enqueue scripts properly to get wp_localize_script
        wp_enqueue_script('apl-script', ARTA_POYESHLAB_PLUGIN_URL . 'assets/js/script.js', array('jquery'), '1.0.0', true);
        
        // Localize script for AJAX
        wp_localize_script('apl-script', 'apl_ajax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'login_nonce' => wp_create_nonce('apl_login_nonce'),
            'register_nonce' => wp_create_nonce('apl_register_nonce'),
            'dashboard_nonce' => wp_create_nonce('apl_dashboard_nonce')
        ));
        
        // Print the scripts
        wp_print_scripts('apl-script');
        ?>
    </body>
</html>
