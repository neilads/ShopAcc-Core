<?php

if (!defined('ABSPATH')) {
    exit;
}

class ShopAcc_GitHub_Config {
    
    public static function get_github_username() {
        return apply_filters('shopacc_github_username', 'your-github-username');
    }
    
    public static function get_github_repo() {
        return apply_filters('shopacc_github_repo', 'ShopAcc-Core');
    }
    
    public static function get_github_token() {
        return apply_filters('shopacc_github_token', '');
    }
    
    public static function is_private_repo() {
        return apply_filters('shopacc_github_private', false);
    }
}
