<?php

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;

$page = get_page_by_path('dang-acc');
if ($page) {
    wp_delete_post($page->ID, true);
}

$products = get_posts([
    'post_type' => 'product',
    'posts_per_page' => -1,
    'post_status' => 'any'
]);

foreach ($products as $product) {
    $thumbnail_id = get_post_thumbnail_id($product->ID);
    if ($thumbnail_id) {
        wp_delete_attachment($thumbnail_id, true);
    }

    $gallery_ids = get_post_meta($product->ID, '_product_image_gallery', true);
    if ($gallery_ids) {
        $gallery_ids = explode(',', $gallery_ids);
        foreach ($gallery_ids as $image_id) {
            wp_delete_attachment($image_id, true);
        }
    }

    wp_delete_post($product->ID, true);
}

delete_option('flatsome_wup_purchase_code');
