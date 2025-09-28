<?php

if (!defined('ABSPATH')) {
    exit;
}

class ShopAcc_Product_Manager {
    
    public function __construct() {
        add_action('wp_ajax_bulk_product_upload', array($this, 'handle_bulk_product_upload'));
        add_action('wp_ajax_delete_all_products', array($this, 'handle_delete_all_products'));
        add_action('wp_ajax_delete_product_group', array($this, 'handle_delete_product_group'));
        add_action('rest_api_init', array($this, 'register_rest_routes'));
    }
    
    public function handle_bulk_product_upload() {
        if (!current_user_can('edit_products')) {
            wp_send_json_error(['message' => 'Không có quyền.']);
        }
        
        $prefix = sanitize_text_field($_POST['prefix']);
        $prices = $_POST['prices'];
        $descs = $_POST['descriptions'];
        $files = $_FILES['images'];
        $uploadType = $_POST['uploadType'];
        $count = 0;
        $first_image_id = null;
        $gallery_ids = array();

        if ($uploadType === 'single') {
            $product_id = $this->create_single_product($prefix, $prices[0], $descs[0]);
        }

        foreach ($files['name'] as $i => $name) {
            $file = [
                'name' => $files['name'][$i],
                'type' => $files['type'][$i],
                'tmp_name' => $files['tmp_name'][$i],
                'error' => $files['error'][$i],
                'size' => $files['size'][$i],
            ];

            $upload = wp_handle_upload($file, ['test_form' => false]);
            if (isset($upload['error'])) continue;

            $attach_id = $this->create_attachment($upload);

            if ($uploadType === 'single') {
                if ($i === 0) {
                    set_post_thumbnail($product_id, $attach_id);
                } else {
                    $gallery_ids[] = $attach_id;
                }
            } else {
                $product_id = $this->create_single_product($prefix, $prices[$i], $descs[$i]);
                set_post_thumbnail($product_id, $attach_id);
            }

            $count++;
        }

        if ($uploadType === 'single' && !empty($gallery_ids)) {
            update_post_meta($product_id, '_product_image_gallery', implode(',', $gallery_ids));
        }

        wp_send_json_success(['count' => $count]);
    }
    
    private function create_single_product($prefix, $price, $description) {
        $product_id = wp_insert_post([
            'post_title' => strtoupper($prefix),
            'post_content' => '',
            'post_excerpt' => sanitize_text_field($description),
            'post_status' => 'publish',
            'post_type' => 'product',
        ]);

        wp_set_object_terms($product_id, 'simple', 'product_type');
        update_post_meta($product_id, '_price', $price);
        update_post_meta($product_id, '_regular_price', $price);
        $sku = strtoupper($prefix) . strtoupper(substr(md5(uniqid()), 0, 7));
        update_post_meta($product_id, '_sku', $sku);
        
        wp_update_post([
            'ID' => $product_id,
            'post_content' => $sku
        ]);

        $this->set_product_meta($product_id);
        clean_post_cache($product_id);
        wc_delete_product_transients($product_id);
        
        return $product_id;
    }
    
    private function set_product_meta($product_id) {
        update_post_meta($product_id, '_virtual', 'yes');
        update_post_meta($product_id, '_sold_individually', 'yes');
        update_post_meta($product_id, '_visibility', 'visible');
        update_post_meta($product_id, '_stock_status', 'instock');
        update_post_meta($product_id, '_manage_stock', 'no');
        update_post_meta($product_id, '_backorders', 'no');
        update_post_meta($product_id, '_tax_status', 'taxable');
        update_post_meta($product_id, '_tax_class', '');
        update_post_meta($product_id, '_featured', 'no');
        update_post_meta($product_id, '_weight', '');
        update_post_meta($product_id, '_length', '');
        update_post_meta($product_id, '_width', '');
        update_post_meta($product_id, '_height', '');
    }
    
    private function create_attachment($upload) {
        $attachment = [
            'post_mime_type' => $upload['type'],
            'post_title' => sanitize_file_name($upload['file']),
            'post_content' => '',
            'post_status' => 'inherit'
        ];

        $attach_id = wp_insert_attachment($attachment, $upload['file']);
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        $attach_data = wp_generate_attachment_metadata($attach_id, $upload['file']);
        wp_update_attachment_metadata($attach_id, $attach_data);
        
        return $attach_id;
    }
    
    public function handle_delete_all_products() {
        if (!current_user_can('edit_products')) {
            wp_send_json_error(['message' => 'Không có quyền.']);
        }

        $products = get_posts([
            'post_type' => 'product',
            'posts_per_page' => -1,
            'post_status' => 'any'
        ]);

        $deleted_count = 0;
        $deleted_images = 0;

        foreach ($products as $product) {
            $result = $this->delete_product_with_images($product->ID);
            $deleted_count++;
            $deleted_images += $result['images'];
        }

        wp_send_json_success([
            'message' => "Đã xóa thành công $deleted_count sản phẩm và $deleted_images ảnh.",
            'deleted_count' => $deleted_count,
            'deleted_images' => $deleted_images
        ]);
    }
    
    public function handle_delete_product_group() {
        if (!current_user_can('edit_products')) {
            wp_send_json_error(['message' => 'Không có quyền.']);
        }

        $group_title = sanitize_text_field($_POST['group_title']);

        $products = get_posts([
            'post_type' => 'product',
            'posts_per_page' => -1,
            'post_status' => 'any',
            'title' => $group_title
        ]);

        $deleted_count = 0;
        $deleted_images = 0;

        foreach ($products as $product) {
            $result = $this->delete_product_with_images($product->ID);
            $deleted_count++;
            $deleted_images += $result['images'];
        }

        wp_send_json_success([
            'message' => "Đã xóa thành công $deleted_count acc và $deleted_images ảnh của lô $group_title.",
            'deleted_count' => $deleted_count,
            'deleted_images' => $deleted_images
        ]);
    }
    
    private function delete_product_with_images($product_id) {
        $deleted_images = 0;
        
        $thumbnail_id = get_post_thumbnail_id($product_id);
        if ($thumbnail_id) {
            wp_delete_attachment($thumbnail_id, true);
            $deleted_images++;
        }

        $gallery_ids = get_post_meta($product_id, '_product_image_gallery', true);
        if ($gallery_ids) {
            $gallery_ids = explode(',', $gallery_ids);
            foreach ($gallery_ids as $image_id) {
                wp_delete_attachment($image_id, true);
                $deleted_images++;
            }
        }

        wp_delete_post($product_id, true);
        
        return ['images' => $deleted_images];
    }
    
    public function register_rest_routes() {
        register_rest_route('neil-shop/v1', '/delete-all-acc', array(
            'methods' => 'POST',
            'callback' => array($this, 'delete_all_acc_api_secure'),
            'permission_callback' => '__return_true',
        ));
    }
    
    public function delete_all_acc_api_secure($request) {
        $username = sanitize_text_field($request->get_param('username'));
        $password = $request->get_param('password');

        if (empty($username) || empty($password)) {
            return new WP_REST_Response(['message' => 'Thiếu username hoặc password.'], 400);
        }

        $user = wp_authenticate($username, $password);

        if (is_wp_error($user)) {
            return new WP_REST_Response(['message' => 'Tài khoản hoặc mật khẩu sai.'], 403);
        }

        if (!user_can($user, 'administrator')) {
            return new WP_REST_Response(['message' => 'Không phải admin, không được phép xoá.'], 403);
        }

        $products = get_posts([
            'post_type' => 'product',
            'posts_per_page' => -1,
            'post_status' => 'any'
        ]);

        $deleted_count = 0;
        $deleted_images = 0;

        foreach ($products as $product) {
            $result = $this->delete_product_with_images($product->ID);
            $deleted_count++;
            $deleted_images += $result['images'];
        }

        return new WP_REST_Response([
            'message' => "Đã xoá thành công $deleted_count acc và $deleted_images ảnh.",
            'deleted_count' => $deleted_count,
            'deleted_images' => $deleted_images
        ]);
    }
}
