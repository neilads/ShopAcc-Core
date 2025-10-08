<?php

if (!defined('ABSPATH')) {
    exit;
}

class ShopAcc_Admin_Updates {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_shopacc_clear_cache', array($this, 'ajax_clear_cache'));
        add_action('wp_dashboard_setup', array($this, 'add_dashboard_widget'));
    }
    
    public function add_admin_menu() {
        add_options_page(
            'ShopAcc Updates',
            'ShopAcc Updates',
            'manage_options',
            'shopacc-updates',
            array($this, 'admin_page')
        );
    }
    
    public function enqueue_scripts($hook) {
        if ($hook !== 'settings_page_shopacc-updates') {
            return;
        }
        
        wp_enqueue_script('jquery');
    }
    
    public function admin_page() {
        $github_username = ShopAcc_GitHub_Config::get_github_username();
        $github_repo = ShopAcc_GitHub_Config::get_github_repo();
        $github_token = ShopAcc_GitHub_Config::get_github_token();
        
        $plugin_data = get_plugin_data(SHOPACC_CORE_PLUGIN_DIR . 'shopacc-core.php');
        $current_version = $plugin_data['Version'];
        
        echo '<div class="wrap">';
        echo '<h1>ShopAcc Core - Quản lý cập nhật</h1>';
        
        echo '<div class="card" style="max-width: 800px;">';
        echo '<h2>Thông tin hiện tại</h2>';
        echo '<table class="form-table">';
        echo '<tr><th>Phiên bản hiện tại:</th><td><strong>' . $current_version . '</strong></td></tr>';
        echo '<tr><th>GitHub Username:</th><td>' . $github_username . '</td></tr>';
        echo '<tr><th>GitHub Repository:</th><td>' . $github_repo . '</td></tr>';
        echo '<tr><th>GitHub Token:</th><td>' . (empty($github_token) ? 'Không có' : 'Đã cấu hình') . '</td></tr>';
        echo '</table>';
        echo '</div>';
        
        echo '<div class="card" style="max-width: 800px;">';
        echo '<h2>Kiểm tra cập nhật</h2>';
        echo '<p>Nhấn nút bên dưới để kiểm tra cập nhật mới nhất từ GitHub:</p>';
        echo '<button id="check-updates" class="button button-primary">Kiểm tra cập nhật</button>';
        echo '<div id="update-result" style="margin-top: 15px;"></div>';
        echo '</div>';
        
        echo '<div class="card" style="max-width: 800px;">';
        echo '<h2>Hướng dẫn cập nhật</h2>';
        echo '<ol>';
        echo '<li>Nhấn "Kiểm tra cập nhật" để xem có phiên bản mới không</li>';
        echo '<li>Nếu có cập nhật, bạn sẽ thấy thông báo trong admin hoặc trang Plugins</li>';
        echo '<li>Vào <a href="' . admin_url('plugins.php') . '">Plugins</a> để cập nhật</li>';
        echo '<li>Hoặc vào <a href="' . admin_url('update-core.php') . '">Updates</a> để cập nhật tất cả</li>';
        echo '</ol>';
        echo '</div>';
        
        echo '<div class="card" style="max-width: 800px;">';
        echo '<h2>Khắc phục sự cố</h2>';
        echo '<p>Nếu không thấy cập nhật:</p>';
        echo '<ul>';
        echo '<li>Kiểm tra kết nối internet</li>';
        echo '<li>Kiểm tra GitHub token có đúng không</li>';
        echo '<li>Xóa cache: <button id="clear-cache" class="button">Xóa cache cập nhật</button></li>';
        echo '<li>Kiểm tra repository có public không</li>';
        echo '</ul>';
        echo '</div>';
        
        echo '</div>';
        
        $this->render_scripts();
    }
    
    private function render_scripts() {
        ?>
        <script>
        jQuery(document).ready(function($) {
            $('#check-updates').click(function() {
                var button = $(this);
                var result = $('#update-result');
                
                button.prop('disabled', true).text('Đang kiểm tra...');
                result.html('<p>Đang kiểm tra cập nhật...</p>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'shopacc_check_updates',
                        nonce: '<?php echo wp_create_nonce('shopacc_check_updates'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            var data = response.data;
                            var html = '<div class="notice notice-' + (data.has_update ? 'warning' : 'success') + '">';
                            html += '<p><strong>' + data.message + '</strong></p>';
                            if (data.has_update) {
                                html += '<p>Phiên bản hiện tại: ' + data.current_version + '</p>';
                                html += '<p>Phiên bản mới: ' + data.latest_version + '</p>';
                                html += '<p><a href="<?php echo admin_url('plugins.php'); ?>" class="button button-primary">Cập nhật ngay</a></p>';
                            }
                            html += '</div>';
                            result.html(html);
                        } else {
                            result.html('<div class="notice notice-error"><p>Lỗi: ' + response.data + '</p></div>');
                        }
                    },
                    error: function() {
                        result.html('<div class="notice notice-error"><p>Lỗi kết nối</p></div>');
                    },
                    complete: function() {
                        button.prop('disabled', false).text('Kiểm tra cập nhật');
                    }
                });
            });
            
            $('#clear-cache').click(function() {
                var button = $(this);
                button.prop('disabled', true).text('Đang xóa...');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'shopacc_clear_cache',
                        nonce: '<?php echo wp_create_nonce('shopacc_clear_cache'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('Đã xóa cache thành công!');
                        } else {
                            alert('Lỗi: ' + response.data);
                        }
                    },
                    error: function() {
                        alert('Lỗi kết nối');
                    },
                    complete: function() {
                        button.prop('disabled', false).text('Xóa cache cập nhật');
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    public function ajax_clear_cache() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        if (!wp_verify_nonce($_POST['nonce'], 'shopacc_clear_cache')) {
            wp_die('Invalid nonce');
        }
        
        delete_site_transient('update_plugins');
        wp_update_plugins();
        
        wp_send_json_success('Cache đã được xóa và kiểm tra cập nhật lại');
    }
    
    public function add_dashboard_widget() {
        wp_add_dashboard_widget(
            'shopacc_updates_widget',
            'ShopAcc Core - Cập nhật',
            array($this, 'dashboard_widget_content')
        );
    }
    
    public function dashboard_widget_content() {
        $plugin_data = get_plugin_data(SHOPACC_CORE_PLUGIN_DIR . 'shopacc-core.php');
        $current_version = $plugin_data['Version'];
        
        echo '<p><strong>Phiên bản hiện tại:</strong> ' . $current_version . '</p>';
        echo '<p><a href="' . admin_url('options-general.php?page=shopacc-updates') . '" class="button button-primary">Quản lý cập nhật</a></p>';
        
        $github_data = $this->get_latest_release();
        if ($github_data) {
            $out_of_date = version_compare($current_version, $github_data['tag_name'], '<');
            if ($out_of_date) {
                echo '<div class="notice notice-warning inline">';
                echo '<p><strong>Có phiên bản mới ' . $github_data['tag_name'] . '!</strong></p>';
                echo '<p><a href="' . admin_url('plugins.php') . '" class="button">Cập nhật ngay</a></p>';
                echo '</div>';
            } else {
                echo '<p style="color: green;"><strong>✓ Đã cập nhật mới nhất</strong></p>';
            }
        } else {
            echo '<p style="color: orange;">Không thể kiểm tra cập nhật</p>';
        }
    }
    
    private function get_latest_release() {
        $github_username = ShopAcc_GitHub_Config::get_github_username();
        $github_repo = ShopAcc_GitHub_Config::get_github_repo();
        $github_token = ShopAcc_GitHub_Config::get_github_token();
        
        $request_uri = sprintf('https://api.github.com/repos/%s/%s/releases', $github_username, $github_repo);
        
        $args = array(
            'timeout' => 10,
            'headers' => array(
                'User-Agent' => 'WordPress-Plugin-Update-Checker'
            )
        );
        
        if (!empty($github_token)) {
            $args['headers']['Authorization'] = 'token ' . $github_token;
        }
        
        $response = wp_remote_get($request_uri, $args);
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code !== 200) {
            return false;
        }
        
        $releases = json_decode(wp_remote_retrieve_body($response), true);
        
        if (empty($releases) || !is_array($releases)) {
            return false;
        }
        
        return $releases[0];
    }
}

new ShopAcc_Admin_Updates();
