<?php

if (!defined('ABSPATH')) {
    exit;
}

class ShopAcc_Image_Optimizer {
    
    public function __construct() {
        add_filter('image_resize_dimensions', array($this, 'disable_image_crop'), 10, 6);
        add_action('init', array($this, 'remove_image_sizes'));
    }
    
    public function disable_image_crop($enable, $orig_w, $orig_h, $dest_w, $dest_h, $crop) {
        return false;
    }
    
    public function remove_image_sizes() {
        foreach (get_intermediate_image_sizes() as $size) {
            remove_image_size($size);
        }
    }
}
