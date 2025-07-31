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
function parsePrice(priceStr) {
  const matchM = priceStr.match(/^(\d+)m(\d*)$/i);
  const matchK = priceStr.match(/^(\d+)k$/i);
  const matchDot = priceStr.match(/^(\d+)\.(\d*)$/);

  if (matchM) {
    const millions = parseInt(matchM[1]) * 1000000;
    const hundredThousands = parseInt(matchM[2] || 0) * 100000;
    return millions + hundredThousands;
  } else if (matchK) {
    return parseInt(matchK[1]) * 1000;
  } else if (matchDot) {
    const millions = parseInt(matchDot[1]) * 1000000;
    const hundredThousands = parseInt(matchDot[2] || 0) * 100000;
    return millions + hundredThousands;
  } else if (/^\d+$/.test(priceStr)) {
    const price = parseInt(priceStr);
    return price < 100000 ? null : price;
  }
  return null;
}

function processLinks(text) {
  
  if (!text || !text.trim()) {
    return 'Liên Hệ';
  }

  const descriptions = {
    'gct': 'Game Center',
    'fb': 'Facebook',
    'rip': 'RIP',
    'gm': 'Gmail',
    'du': 'Dư',
    'icl': 'iCloud',
    'tw': 'Twitter'
  };

  const parts = text.trim().split(/\s+/);

  const processedLinks = [];
  for (const part of parts) {
    const cleanPart = part.toLowerCase().trim();
    
    if (descriptions[cleanPart]) {
      processedLinks.push(descriptions[cleanPart]);
    }
  }

  if (processedLinks.length === 2) {
    return processedLinks.join(' + ');
  } else if (processedLinks.length === 1) {
    return `${processedLinks[0]} + Dư`;
  }
  return 'Liên Hệ';
}

document.getElementById('imageUpload').addEventListener('change', function(e) {
  const files = e.target.files;
  const productList = document.getElementById('productList');
  const uploadType = document.querySelector('input[name="uploadType"]:checked').value;
  productList.innerHTML = '';

  if (uploadType === 'single') {
    const imageGrid = document.createElement('div');
    imageGrid.className = 'image-grid';
    const input = document.createElement('input');
    input.type = 'text';
    input.placeholder = 'Nhập giá và liên kết (ví dụ: 500k gct fb)';
    input.style.width = '100%';
    input.style.padding = '8px';
    input.style.marginBottom = '20px';

    for (let i = 0; i < files.length; i++) {
      const file = files[i];
      const reader = new FileReader();

      reader.onload = function(event) {
        const imgContainer = document.createElement('div');
        imgContainer.style.position = 'relative';
        imgContainer.style.display = 'inline-block';
        
        const img = document.createElement('img');
        img.src = event.target.result;
        
        const deleteBtn = document.createElement('button');
        deleteBtn.innerHTML = '×';
        deleteBtn.style.position = 'absolute';
        deleteBtn.style.top = '5px';
        deleteBtn.style.right = '5px';
        deleteBtn.style.background = 'rgba(255, 0, 0, 0.7)';
        deleteBtn.style.color = 'white';
        deleteBtn.style.border = 'none';
        deleteBtn.style.borderRadius = '50%';
        deleteBtn.style.width = '24px';
        deleteBtn.style.height = '24px';
        deleteBtn.style.cursor = 'pointer';
        deleteBtn.style.display = 'flex';
        deleteBtn.style.alignItems = 'center';
        deleteBtn.style.justifyContent = 'center';
        deleteBtn.style.fontSize = '16px';
        
        deleteBtn.onclick = function() {
          imgContainer.remove();
          const dt = new DataTransfer();
          const input = document.getElementById('imageUpload');
          const { files } = input;
          
          for (let i = 0; i < files.length; i++) {
            if (i !== Array.from(imageGrid.children).indexOf(imgContainer)) {
              dt.items.add(files[i]);
            }
          }
          
          input.files = dt.files;
        };
        
        imgContainer.appendChild(img);
        imgContainer.appendChild(deleteBtn);
        imageGrid.appendChild(imgContainer);
      };

      reader.readAsDataURL(file);
    }

    productList.appendChild(imageGrid);
    productList.appendChild(input);
  } else {
    for (let i = 0; i < files.length; i++) {
      const file = files[i];
      const reader = new FileReader();

      reader.onload = function(event) {
        const div = document.createElement('div');
        div.style.border = '1px solid #ccc';
        div.style.marginBottom = '10px';
        div.style.padding = '10px';
        div.style.position = 'relative';

        div.innerHTML = `
          <button class="delete-btn" style="position: absolute; top: 5px; right: 5px; background: rgba(255, 0, 0, 0.7); color: white; border: none; border-radius: 50%; width: 24px; height: 24px; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 16px;">×</button>
          <img src="${event.target.result}" style="max-width: 150px; display:block; margin-bottom: 10px;">
          <input type="text" placeholder="Nhập giá và liên kết (ví dụ: 500k gct fb)" style="width:100%; padding: 8px;">
        `;

        const deleteBtn = div.querySelector('.delete-btn');
        deleteBtn.onclick = function() {
          div.remove();
          const dt = new DataTransfer();
          const input = document.getElementById('imageUpload');
          const { files } = input;
          
          for (let i = 0; i < files.length; i++) {
            if (i !== Array.from(productList.children).indexOf(div)) {
              dt.items.add(files[i]);
            }
          }
          
          input.files = dt.files;
        };

        productList.appendChild(div);
      };

      reader.readAsDataURL(file);
    }
  }
});

document.querySelectorAll('input[name="uploadType"]').forEach(radio => {
  radio.addEventListener('change', function() {
    const imageUpload = document.getElementById('imageUpload');
    imageUpload.value = '';
    document.getElementById('productList').innerHTML = '';
  });
});

function showMessage(message, type = 'error') {
  const messageBox = document.getElementById('messageBox');
  messageBox.style.display = 'block';
  messageBox.style.padding = '10px';
  messageBox.style.margin = '10px 0';
  messageBox.style.borderRadius = '3px';
  messageBox.style.backgroundColor = type === 'error' ? '#f8d7da' : '#d4edda';
  messageBox.style.color = type === 'error' ? '#721c24' : '#155724';
  messageBox.style.border = `1px solid ${type === 'error' ? '#f5c6cb' : '#c3e6cb'}`;
  messageBox.textContent = message;

  if (type === 'success') {
    setTimeout(() => {
      messageBox.style.display = 'none';
    }, 3000);
  }
}

document.getElementById('uploadBtn').addEventListener('click', async function () {
  const button = this;
  const buttonText = button.querySelector('.button-text');
  const prefix = document.getElementById('productPrefix').value.trim();
  const images = document.getElementById('imageUpload').files;
  const uploadType = document.querySelector('input[name="uploadType"]:checked').value;

  if (!prefix || images.length === 0) {
    showMessage('Nhập tên và chọn ảnh trước đã bạn ơi!');
    return;
  }

  button.classList.add('loading');
  buttonText.style.visibility = 'hidden';

  const formData = new FormData();
  formData.append('action', 'bulk_product_upload');
  formData.append('prefix', prefix);
  formData.append('uploadType', uploadType);

  if (uploadType === 'single') {
    const input = document.querySelector('#productList input');
    const info = input.value.trim();
    
    const parts = info.split(/\s+/);
    
    if (parts.length === 0) {
      showMessage('Vui lòng nhập giá và liên kết');
      button.classList.remove('loading');
      buttonText.style.visibility = 'visible';
      return;
    }

    const price = parsePrice(parts[0]);
    if (price === null) {
      showMessage('Giá không hợp lệ hoặc dưới 100k');
      button.classList.remove('loading');
      buttonText.style.visibility = 'visible';
      return;
    }

    const remainingText = parts.slice(1).join(' ');
    
    const description = remainingText ? processLinks(remainingText) : 'Liên Hệ';
    
    for (let i = 0; i < images.length; i++) {
      formData.append('images[]', images[i]);
      formData.append('prices[]', price);
      formData.append('descriptions[]', description);
    }
  } else {
    const inputs = document.querySelectorAll('#productList input');
    for (let i = 0; i < images.length; i++) {
      const info = inputs[i].value.trim();
      
      const parts = info.split(/\s+/);
      
      if (parts.length === 0) {
        showMessage(`Vui lòng nhập giá và liên kết cho ảnh ${i + 1}`);
        button.classList.remove('loading');
        buttonText.style.visibility = 'visible';
        return;
      }

      const price = parsePrice(parts[0]);
      if (price === null) {
        showMessage(`Giá không hợp lệ hoặc dưới 100k ở ảnh ${i + 1}`);
        button.classList.remove('loading');
        buttonText.style.visibility = 'visible';
        return;
      }

      const remainingText = parts.slice(1).join(' ');
      
      const description = remainingText ? processLinks(remainingText) : 'Liên Hệ';
      
      formData.append('images[]', images[i]);
      formData.append('prices[]', price);
      formData.append('descriptions[]', description);
    }
  }

  try {
    const res = await fetch('<?php echo admin_url("admin-ajax.php"); ?>', {
      method: 'POST',
      body: formData
    });

    const json = await res.json();
    
    button.classList.remove('loading');
    buttonText.style.visibility = 'visible';
    
    if (json.success) {
      showMessage('Đăng thành công!', 'success');
      
      setTimeout(() => {
        location.reload();
      }, 3000);
    } else {
      showMessage('Có lỗi: ' + json.message);
    }
  } catch (error) {
    button.classList.remove('loading');
    buttonText.style.visibility = 'visible';
    showMessage('Có lỗi xảy ra: ' + error.message);
  }
});

document.getElementById('deleteAllBtn').addEventListener('click', async function() {
  if (!confirm('Bạn có chắc chắn muốn xóa tất cả sản phẩm và ảnh không? Hành động này không thể hoàn tác!')) {
    return;
  }

  const button = this;
  const buttonText = button.querySelector('.button-text');
  
  button.classList.add('loading');
  buttonText.style.visibility = 'hidden';

  try {
    const formData = new FormData();
    formData.append('action', 'delete_all_products');

    const res = await fetch('<?php echo admin_url("admin-ajax.php"); ?>', {
      method: 'POST',
      body: formData
    });

    const json = await res.json();
    
    button.classList.remove('loading');
    buttonText.style.visibility = 'visible';
    
    if (json.success) {
      showMessage(json.data.message, 'success');
      
      setTimeout(() => {
        location.reload();
      }, 3000);
    } else {
      showMessage('Có lỗi: ' + json.message);
    }
  } catch (error) {
    button.classList.remove('loading');
    buttonText.style.visibility = 'visible';
    showMessage('Có lỗi xảy ra: ' + error.message);
  }
});

document.querySelectorAll('.delete-group-btn').forEach(button => {
  button.addEventListener('click', async function() {
    const title = this.dataset.title;
    if (!confirm(`Bạn có chắc chắn muốn xóa tất cả acc và ảnh của lô ${title} không? Hành động này không thể hoàn tác!`)) {
      return;
    }

    const button = this;
    const originalContent = button.innerHTML;
    button.disabled = true;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

    try {
      const formData = new FormData();
      formData.append('action', 'delete_product_group');
      formData.append('group_title', title);

      const res = await fetch('<?php echo admin_url("admin-ajax.php"); ?>', {
        method: 'POST',
        body: formData
      });

      const json = await res.json();
      
      if (json.success) {
        showMessage(json.data.message, 'success');
        
        setTimeout(() => {
          location.reload();
        }, 3000);
      } else {
        showMessage('Có lỗi: ' + json.message);
        button.disabled = false;
        button.innerHTML = originalContent;
      }
    } catch (error) {
      showMessage('Có lỗi xảy ra: ' + error.message);
      button.disabled = false;
      button.innerHTML = originalContent;
    }
  });
});
</script>

<?php get_footer(); ?>