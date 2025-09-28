<?php

if (!defined('ABSPATH')) {
    exit;
}

class ShopAcc_Flatsome_Activator {
    
    public function __construct() {
        add_action('admin_head', array($this, 'hide_flatsome_notice'));
    }
    
    public function hide_flatsome_notice() {
        echo '<style> div#flatsome-notice {display: none;}</style>';
    }
}
