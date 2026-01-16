<?php

/*
Template Name: Bulk Product Upload
*/

if (!current_user_can('administrator')) {
    wp_redirect(wp_login_url(get_permalink()));
    exit;
}

get_header(); ?>

<div style="max-width: 800px; margin: 0 auto; padding: 20px;">
  <h2>Đăng Acc</h2>
  
  <div style="margin-bottom: 20px;">
    <label style="display: block; margin-bottom: 10px;">Loại Đăng:</label>
    <label style="margin-right: 20px;">
      <input type="radio" name="uploadType" value="bulk" checked> Đăng acc theo lô
    </label>
    <label>
      <input type="radio" name="uploadType" value="single"> Đăng acc ảnh lẻ
    </label>
  </div>

  <label>Tên Lô Acc</label>
  <input type="text" id="productPrefix" style="width: 100%; padding: 8px; margin-bottom: 20px;" placeholder="Ví dụ: ABC">

  <label>Ảnh Acc</label>
  <input type="file" id="imageUpload" accept="image/*" multiple style="margin-bottom: 20px;">

  <div id="productList"></div>

  <div id="messageBox" style="margin: 10px 0; display: none;"></div>

  <button id="uploadBtn" class="single_add_to_cart_button button alt">
    <span class="button-text">Đăng tất cả</span>
  </button>
</div>

<div style="max-width: 800px; margin: 20px auto; padding: 20px; border-top: 1px solid #ccc;">
  <h2>Xóa Tất Cả Acc</h2>
  <p style="color: #666; margin-bottom: 15px;">Lưu ý: Hành động này sẽ xóa tất cả acc đang có trên shop. Hãy cẩn thận khi sử dụng!</p>
  <button id="deleteAllBtn" class="single_add_to_cart_button button" style="background-color: #dc3545;">
    <span class="button-text">Xóa tất cả</span>
  </button>
</div>

<div style="max-width: 800px; margin: 20px auto; padding: 20px; border-top: 1px solid #ccc;">
  <h2>Các Lô Đang Trên Shop</h2>
  <div id="productGroups" style="margin-top: 20px;">
    <?php
    $products = get_posts([
      'post_type' => 'product',
      'posts_per_page' => -1,
      'post_status' => 'publish'
    ]);

    $product_groups = [];
    foreach ($products as $product) {
      $title = $product->post_title;
      if (!isset($product_groups[$title])) {
        $product_groups[$title] = [
          'count' => 0,
          'last_upload' => null
        ];
      }
      $product_groups[$title]['count']++;
      
      $post_date = strtotime($product->post_date);
      if (!$product_groups[$title]['last_upload'] || $post_date > $product_groups[$title]['last_upload']) {
        $product_groups[$title]['last_upload'] = $post_date;
      }
    }

    uasort($product_groups, function($a, $b) {
      return $a['last_upload'] - $b['last_upload'];
    });

    echo '<div class="product-groups-grid">';
    foreach ($product_groups as $title => $data) {
      $last_upload = $data['last_upload'] ? date('d/m', $data['last_upload']) : 'N/A';
      echo '<div class="product-group">';
      echo '<div class="product-group-header">';
      echo '<div class="product-group-title">' . esc_html($title) . '</div>';
      echo '<div class="product-group-count">' . $data['count'] . ' acc</div>';
      echo '</div>';
      echo '<div class="product-group-footer">';
      echo '<div class="product-group-date">' . $last_upload . '</div>';
      echo '<button class="delete-group-btn" data-title="' . esc_attr($title) . '"><i class="fas fa-trash-alt"></i></button>';
      echo '</div>';
      echo '</div>';
    }
    echo '</div>';
    ?>
  </div>
</div>

<style>
.icon-spinner {
  display: inline-block;
  width: 1em;
  height: 1em;
  border: 2px solid rgba(255,255,255,.3);
  border-radius: 50%;
  border-top-color: #fff;
  animation: spin 1s ease-in-out infinite;
}

.image-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
  gap: 10px;
  margin-bottom: 20px;
}

.image-grid img {
  width: 100%;
  height: 150px;
  object-fit: cover;
  border-radius: 4px;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.product-groups-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 20px;
  padding: 10px;
}

.product-group {
  background: linear-gradient(145deg, #ffffff, #f5f5f5);
  border-radius: 12px;
  padding: 20px;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
  transition: all 0.3s ease;
  border: 1px solid rgba(0, 0, 0, 0.05);
}

.product-group:hover {
  transform: translateY(-5px);
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
}

.product-group-header {
  margin-bottom: 15px;
}

.product-group-title {
  font-size: 18px;
  font-weight: 600;
  color: #2c3e50;
  margin-bottom: 8px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.product-group-count {
  font-size: 14px;
  color: #666;
  background: #f8f9fa;
  padding: 4px 10px;
  border-radius: 20px;
  display: inline-block;
}

.product-group-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 15px;
  padding-top: 15px;
  border-top: 1px solid rgba(0, 0, 0, 0.05);
}

.product-group-date {
  font-size: 13px;
  color: #888;
}

.delete-group-btn {
  background: #ff4757;
  color: white;
  border: none;
  width: 32px;
  height: 32px;
  border-radius: 6px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s ease;
}

.delete-group-btn i {
  font-size: 14px;
}

.delete-group-btn:hover {
  background: #ff6b81;
  transform: translateY(-2px);
}

@media screen and (max-width: 1024px) {
  .product-groups-grid {
    grid-template-columns: repeat(3, 1fr);
    gap: 15px;
  }
}

@media screen and (max-width: 768px) {
  .product-groups-grid {
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
  }
  
  .product-group {
    padding: 15px;
  }
  
  .product-group-title {
    font-size: 16px;
  }
  
  .delete-group-btn {
    width: 28px;
    height: 28px;
  }
  
  .delete-group-btn i {
    font-size: 12px;
  }
}
</style>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

<script>
var ajaxurl = '<?php echo admin_url("admin-ajax.php"); ?>';
</script>

<?php get_footer(); ?>
