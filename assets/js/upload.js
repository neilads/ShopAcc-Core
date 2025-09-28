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

document.addEventListener('DOMContentLoaded', function() {
  const imageUpload = document.getElementById('imageUpload');
  const productList = document.getElementById('productList');
  const uploadBtn = document.getElementById('uploadBtn');
  const deleteAllBtn = document.getElementById('deleteAllBtn');

  if (imageUpload) {
    imageUpload.addEventListener('change', function(e) {
      const files = e.target.files;
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
  }

  document.querySelectorAll('input[name="uploadType"]').forEach(radio => {
    radio.addEventListener('change', function() {
      const imageUpload = document.getElementById('imageUpload');
      imageUpload.value = '';
      document.getElementById('productList').innerHTML = '';
    });
  });

  if (uploadBtn) {
    uploadBtn.addEventListener('click', async function () {
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
        const res = await fetch(ajaxurl, {
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
  }

  if (deleteAllBtn) {
    deleteAllBtn.addEventListener('click', async function() {
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

        const res = await fetch(ajaxurl, {
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
  }

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

        const res = await fetch(ajaxurl, {
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
});
