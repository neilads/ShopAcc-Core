<?php

if (!defined('ABSPATH')) {
    exit;
}

class ShopAcc_Template_Loader {
    
    public function __construct() {
        add_filter('theme_page_templates', array($this, 'add_custom_template'));
        add_filter('template_include', array($this, 'load_custom_template'));
    }
    
    public function add_custom_template($templates) {
        $templates['upload.php'] = 'Upload Template';
        return $templates;
    }
    
    public function load_custom_template($template) {
        if (is_page('dang-acc')) {
            $template = SHOPACC_CORE_PLUGIN_DIR . 'templates/upload.php';
        }
        return $template;
    }
}
