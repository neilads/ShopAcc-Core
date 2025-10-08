<?php

if (!defined('ABSPATH')) {
    exit;
}

class ShopAcc_GitHub_Config {
    
    public static function get_github_username() {
        return apply_filters('shopacc_github_username', 'neilads');
    }
    
    public static function get_github_repo() {
        return apply_filters('shopacc_github_repo', 'ShopAcc-Core');
    }
    
    public static function get_github_token() {
        return apply_filters('shopacc_github_token', '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');
    }
    
    public static function is_private_repo() {
        return apply_filters('shopacc_github_private', true);
    }
}
