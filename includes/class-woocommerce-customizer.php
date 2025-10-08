<?php

if (!defined('ABSPATH')) {
    exit;
}

class ShopAcc_WooCommerce_Customizer {
    
    public function __construct() {
        add_action('woocommerce_single_product_summary', array($this, 'show_sku'), 5);
        add_filter('posts_clauses', array($this, 'order_by_stock_status'), 2000);
        add_filter('woocommerce_product_related_products_heading', array($this, 'change_related_products_heading'));
        add_action('woocommerce_after_shop_loop_item_title', array($this, 'display_sku_in_shop'), 15);
    }
    
    public function show_sku() {
        global $product;
        echo 'Mã: ' . $product->get_sku();
    }
    
    public function order_by_stock_status($posts_clauses) {
        global $wpdb;
        if (is_woocommerce() && (is_shop() || is_product_category() || is_product_tag())) {
            $posts_clauses['join'] .= " INNER JOIN $wpdb->postmeta istockstatus ON ($wpdb->posts.ID = istockstatus.post_id) ";
            if (isset($_GET['orderby']) && ($_GET['orderby'] === 'price' || $_GET['orderby'] === 'price-desc')) {
                $posts_clauses['join'] .= " INNER JOIN $wpdb->postmeta iprice ON ($wpdb->posts.ID = iprice.post_id) ";
                $posts_clauses['orderby'] = " istockstatus.meta_value ASC, CAST(iprice.meta_value AS DECIMAL) " . 
                    ($_GET['orderby'] === 'price-desc' ? 'DESC' : 'ASC') . ", " . $posts_clauses['orderby'];
                $posts_clauses['where'] = " AND istockstatus.meta_key = '_stock_status' AND istockstatus.meta_value <> '' AND iprice.meta_key = '_price' " . $posts_clauses['where'];
            } else {
                $posts_clauses['orderby'] = " istockstatus.meta_value ASC, " . $posts_clauses['orderby'];
                $posts_clauses['where'] = " AND istockstatus.meta_key = '_stock_status' AND istockstatus.meta_value <> '' " . $posts_clauses['where'];
            }
        }
        return $posts_clauses;
    }
    
    public function change_related_products_heading() {
        return 'Xem Thêm Acc Khác';
    }
    
    public function display_sku_in_shop() {
        global $product;
        if ($product->get_sku()) {
            echo '<div class="box-excerpt is-small">Mã: ' . $product->get_sku() . '</div>';
        }
    }
    
}
