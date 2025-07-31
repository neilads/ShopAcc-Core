<?php
/*
Plugin Name: Neil-X Shop Acc
Description: Plugin làm shop acc của Neil!
Version: 1.1
Author: Neil
*/

// Ngăn Tạo Ảnh Con
function tcwp_tat_crop( $enable, $orig_w, $orig_h, $dest_w, $dest_h, $crop ) {
    return false;
}
add_filter( 'image_resize_dimensions', 'tcwp_tat_crop', 10, 6 );

function tcwp_tat_image_sizes() {
    foreach ( get_intermediate_image_sizes() as $size ) {
        remove_image_size( $size );
    }
}
add_action( 'init', 'tcwp_tat_image_sizes' );

// Hiển thị SKU sản phẩm
add_action('woocommerce_single_product_summary', 'dev_designs_show_sku', 5);
function dev_designs_show_sku() {
    global $product;
    echo 'Mã: ' . $product->get_sku();
}

// Kiểm tra và thêm các chức năng WooCommerce
if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    add_filter('posts_clauses', 'order_by_stock_status', 2000);
}

// Sắp xếp sản phẩm theo trạng thái tồn kho
function order_by_stock_status($posts_clauses) {
    global $wpdb;
  
    if (is_woocommerce() && (is_shop() || is_product_category() || is_product_tag())) {
        $posts_clauses['join'] .= " INNER JOIN $wpdb->postmeta istockstatus ON ($wpdb->posts.ID = istockstatus.post_id) ";
        
        // Nếu đang sắp xếp theo giá
        if (isset($_GET['orderby']) && ($_GET['orderby'] === 'price' || $_GET['orderby'] === 'price-desc')) {
            $posts_clauses['join'] .= " INNER JOIN $wpdb->postmeta iprice ON ($wpdb->posts.ID = iprice.post_id) ";
            $posts_clauses['orderby'] = " istockstatus.meta_value ASC, CAST(iprice.meta_value AS DECIMAL) " . 
                ($_GET['orderby'] === 'price-desc' ? 'DESC' : 'ASC') . ", " . $posts_clauses['orderby'];
            $posts_clauses['where'] = " AND istockstatus.meta_key = '_stock_status' AND istockstatus.meta_value <> '' AND iprice.meta_key = '_price' " . $posts_clauses['where'];
        } else {
            // Nếu không sắp xếp theo giá, chỉ sắp xếp theo trạng thái tồn kho
            $posts_clauses['orderby'] = " istockstatus.meta_value ASC, " . $posts_clauses['orderby'];
            $posts_clauses['where'] = " AND istockstatus.meta_key = '_stock_status' AND istockstatus.meta_value <> '' " . $posts_clauses['where'];
        }
    }
    return $posts_clauses;
}

// Thay đổi tiêu đề sản phẩm liên quan
add_filter('woocommerce_product_related_products_heading', 'misha_change_related_products_heading');
function misha_change_related_products_heading() {
    return 'Xem Thêm Acc Khác';
}

// Hiển thị SKU trong danh sách sản phẩm
function skyverge_shop_display_skus() {
    global $product;

    if ( $product->get_sku() ) {
        echo '<div class="box-excerpt is-small">Mã: ' . $product->get_sku() . '</div>';
    }
}
add_action( 'woocommerce_after_shop_loop_item_title', 'skyverge_shop_display_skus', 15 );

// Tạo trang tùy chỉnh khi plugin được kích hoạt
function create_dang_acc_page() {
    // Kiểm tra xem trang đã tồn tại chưa
    $page_check = get_page_by_path('dang-acc');
    if (empty($page_check)) {
        // Tạo trang mới
        $page_data = array(
            'post_title'    => 'Đăng Acc',
            'post_name'     => 'dang-acc',
            'post_status'   => 'publish',
            'post_type'     => 'page',
            'post_content'  => '',
            'page_template' => 'upload.php'
        );
        $page_id = wp_insert_post($page_data);
    }
}
register_activation_hook(__FILE__, 'create_dang_acc_page');

// Thêm template vào danh sách template có sẵn
function add_custom_template($templates) {
    $templates['upload.php'] = 'Upload Template';
    return $templates;
}
add_filter('theme_page_templates', 'add_custom_template');

// Load template tùy chỉnh
function load_custom_template($template) {
    if (is_page('dang-acc')) {
        $template = plugin_dir_path(__FILE__) . 'upload.php';
    }
    return $template;
}
add_filter('template_include', 'load_custom_template');

// Xử lý upload sản phẩm hàng loạt
add_action('wp_ajax_bulk_product_upload', 'handle_bulk_product_upload');
add_action('wp_ajax_delete_all_products', 'handle_delete_all_products');
add_action('wp_ajax_delete_product_group', 'handle_delete_product_group');

function handle_bulk_product_upload() {
  if (!current_user_can('edit_products')) {
    wp_send_json_error(['message' => 'Không có quyền.']);
  }

  $prefix = sanitize_text_field($_POST['prefix']);
  $prices = $_POST['prices'];
  $descs  = $_POST['descriptions'];
  $files  = $_FILES['images'];
  $uploadType = $_POST['uploadType'];

  $count = 0;
  $first_image_id = null;
  $gallery_ids = array();

  // Tạo sản phẩm trước khi xử lý ảnh
  if ($uploadType === 'single') {
    $product_id = wp_insert_post([
      'post_title'   => strtoupper($prefix),
      'post_content' => '',
      'post_excerpt' => sanitize_text_field($descs[0]),
      'post_status'  => 'publish',
      'post_type'    => 'product',
    ]);

    // Set product type
    wp_set_object_terms($product_id, 'simple', 'product_type');

    // Set product meta
    update_post_meta($product_id, '_price', $prices[0]);
    update_post_meta($product_id, '_regular_price', $prices[0]);
    $sku = strtoupper($prefix) . strtoupper(substr(md5(uniqid()), 0, 7));
    update_post_meta($product_id, '_sku', $sku);
    
    // Set product description as SKU
    wp_update_post([
      'ID' => $product_id,
      'post_content' => $sku
    ]);

    // Set virtual and sold individually
    update_post_meta($product_id, '_virtual', 'yes');
    update_post_meta($product_id, '_sold_individually', 'yes');

    // Set additional required meta
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

    // Clear product cache
    clean_post_cache($product_id);
    wc_delete_product_transients($product_id);
  }

  foreach ($files['name'] as $i => $name) {
    $file = [
      'name'     => $files['name'][$i],
      'type'     => $files['type'][$i],
      'tmp_name' => $files['tmp_name'][$i],
      'error'    => $files['error'][$i],
      'size'     => $files['size'][$i],
    ];

    $upload = wp_handle_upload($file, ['test_form' => false]);
    if (isset($upload['error'])) continue;

    $attachment = [
      'post_mime_type' => $upload['type'],
      'post_title'     => sanitize_file_name($upload['file']),
      'post_content'   => '',
      'post_status'    => 'inherit'
    ];

    $attach_id = wp_insert_attachment($attachment, $upload['file']);
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    $attach_data = wp_generate_attachment_metadata($attach_id, $upload['file']);
    wp_update_attachment_metadata($attach_id, $attach_data);

    if ($uploadType === 'single') {
      if ($i === 0) {
        // Đặt ảnh đầu tiên làm ảnh đại diện
        set_post_thumbnail($product_id, $attach_id);
      } else {
        // Thêm các ảnh còn lại vào gallery
        $gallery_ids[] = $attach_id;
      }
    } else {
      // Xử lý upload theo lô
      $product_id = wp_insert_post([
        'post_title'   => strtoupper($prefix),
        'post_content' => '',
        'post_excerpt' => sanitize_text_field($descs[$i]),
        'post_status'  => 'publish',
        'post_type'    => 'product',
      ]);

      // Set product type
      wp_set_object_terms($product_id, 'simple', 'product_type');

      // Set product meta
      update_post_meta($product_id, '_price', $prices[$i]);
      update_post_meta($product_id, '_regular_price', $prices[$i]);
      $sku = strtoupper($prefix) . strtoupper(substr(md5(uniqid()), 0, 7));
      update_post_meta($product_id, '_sku', $sku);
      
      // Set product description as SKU
      wp_update_post([
        'ID' => $product_id,
        'post_content' => $sku
      ]);

      // Set virtual and sold individually
      update_post_meta($product_id, '_virtual', 'yes');
      update_post_meta($product_id, '_sold_individually', 'yes');

      // Set additional required meta
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

      // Clear product cache
      clean_post_cache($product_id);
      wc_delete_product_transients($product_id);

      set_post_thumbnail($product_id, $attach_id);
    }

    $count++;
  }

  // Cập nhật gallery cho sản phẩm single
  if ($uploadType === 'single' && !empty($gallery_ids)) {
    update_post_meta($product_id, '_product_image_gallery', implode(',', $gallery_ids));
  }

  wp_send_json_success(['count' => $count]);
}

function handle_delete_all_products() {
  if (!current_user_can('edit_products')) {
    wp_send_json_error(['message' => 'Không có quyền.']);
  }

  // Lấy tất cả sản phẩm
  $products = get_posts([
    'post_type' => 'product',
    'posts_per_page' => -1,
    'post_status' => 'any'
  ]);

  $deleted_count = 0;
  $deleted_images = 0;

  foreach ($products as $product) {
    // Lấy tất cả ảnh của sản phẩm
    $thumbnail_id = get_post_thumbnail_id($product->ID);
    if ($thumbnail_id) {
      wp_delete_attachment($thumbnail_id, true);
      $deleted_images++;
    }

    // Lấy gallery ảnh
    $gallery_ids = get_post_meta($product->ID, '_product_image_gallery', true);
    if ($gallery_ids) {
      $gallery_ids = explode(',', $gallery_ids);
      foreach ($gallery_ids as $image_id) {
        wp_delete_attachment($image_id, true);
        $deleted_images++;
      }
    }

    // Xóa sản phẩm
    wp_delete_post($product->ID, true);
    $deleted_count++;
  }

  wp_send_json_success([
    'message' => "Đã xóa thành công $deleted_count sản phẩm và $deleted_images ảnh.",
    'deleted_count' => $deleted_count,
    'deleted_images' => $deleted_images
  ]);
}

function handle_delete_product_group() {
  if (!current_user_can('edit_products')) {
    wp_send_json_error(['message' => 'Không có quyền.']);
  }

  $group_title = sanitize_text_field($_POST['group_title']);

  // Lấy tất cả sản phẩm có cùng tên
  $products = get_posts([
    'post_type' => 'product',
    'posts_per_page' => -1,
    'post_status' => 'any',
    'title' => $group_title
  ]);

  $deleted_count = 0;
  $deleted_images = 0;

  foreach ($products as $product) {
    // Lấy tất cả ảnh của sản phẩm
    $thumbnail_id = get_post_thumbnail_id($product->ID);
    if ($thumbnail_id) {
      wp_delete_attachment($thumbnail_id, true);
      $deleted_images++;
    }

    // Lấy gallery ảnh
    $gallery_ids = get_post_meta($product->ID, '_product_image_gallery', true);
    if ($gallery_ids) {
      $gallery_ids = explode(',', $gallery_ids);
      foreach ($gallery_ids as $image_id) {
        wp_delete_attachment($image_id, true);
        $deleted_images++;
      }
    }

    // Xóa sản phẩm
    wp_delete_post($product->ID, true);
    $deleted_count++;
  }

  wp_send_json_success([
    'message' => "Đã xóa thành công $deleted_count acc và $deleted_images ảnh của lô $group_title.",
    'deleted_count' => $deleted_count,
    'deleted_images' => $deleted_images
  ]);
}

// Kích hoạt theme Flatsome
register_activation_hook(__FILE__, 'active_all_flatsome_activate');

function active_all_flatsome_activate() {
    global $wpdb;
    $prefix = $wpdb->prefix;
    $table_name = $prefix . 'options';
    $option_name = 'flatsome_wup_purchase_code';
    $option_value = '8f93cd51-5246-4505-9228-9a4137e6ec00';
    $autoload = 'yes';

    $wpdb->insert(
        $table_name,
        array(
            'option_name' => $option_name,
            'option_value' => $option_value,
            'autoload' => $autoload
        ),
        array(
            '%s',
            '%s',
            '%s'
        )
    );
}

// Ẩn thông báo Flatsome
add_action('admin_head', 'pvlan_hide_key_active');
function pvlan_hide_key_active() {
    echo '<style> div#flatsome-notice {display: none;}</style>';
}

// Enqueue custom CSS
function neil_enqueue_styles() {
    wp_enqueue_style('neil-custom-styles', plugins_url('neil.css', __FILE__));
}
add_action('wp_enqueue_scripts', 'neil_enqueue_styles');

// Hiển thị giá sản phẩm và phí trả góp
add_action( 'woocommerce_after_add_to_cart_button', 'display_product_price_and_installment_fee', 10 );

function display_product_price_and_installment_fee() {
    global $product;

    $product_price = $product->get_price();
    $formatted_price = intval( $product_price );
    
    echo '<input type="text" id="product-price" data-value="' . $formatted_price . '" value="' . $formatted_price . '" onfocus="removeCurrencySymbol(\'product-price-page\')" onblur="addCurrencySymbol(\'product-price-page\')" oninput="formatCurrencyInput(\'product-price-page\'); updateDownPaymentAndInstallmentPeriod();"><br>';
}

add_action('rest_api_init', function () {
  register_rest_route('neil-shop/v1', '/delete-all-acc', array(
      'methods' => 'POST',
      'callback' => 'neil_delete_all_acc_api_secure',
      'permission_callback' => '__return_true',
  ));
});

function neil_delete_all_acc_api_secure($request) {
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
      $thumbnail_id = get_post_thumbnail_id($product->ID);
      if ($thumbnail_id) {
          wp_delete_attachment($thumbnail_id, true);
          $deleted_images++;
      }

      $gallery_ids = get_post_meta($product->ID, '_product_image_gallery', true);
      if ($gallery_ids) {
          $gallery_ids = explode(',', $gallery_ids);
          foreach ($gallery_ids as $image_id) {
              wp_delete_attachment($image_id, true);
              $deleted_images++;
          }
      }

      wp_delete_post($product->ID, true);
      $deleted_count++;
  }

  return new WP_REST_Response([
      'message' => "Đã xoá thành công $deleted_count acc và $deleted_images ảnh.",
      'deleted_count' => $deleted_count,
      'deleted_images' => $deleted_images
  ]);
}

?>
