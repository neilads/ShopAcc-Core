<?php

if (!defined('ABSPATH')) {
    exit;
}

class ShopAcc_GitHub_Updater {
    
    private $plugin_slug;
    private $version;
    private $plugin_data;
    private $username;
    private $repo;
    private $plugin_file;
    private $github_data;
    
    public function __construct($plugin_file, $github_username, $github_repo) {
        add_filter('pre_set_site_transient_update_plugins', array($this, 'modify_transient'), 10, 1);
        add_filter('plugins_api', array($this, 'plugin_popup'), 10, 3);
        add_filter('upgrader_post_install', array($this, 'after_install'), 10, 3);
        add_action('admin_notices', array($this, 'update_notice'));
        add_action('wp_ajax_shopacc_check_updates', array($this, 'ajax_check_updates'));
        
        $this->plugin_file = $plugin_file;
        $this->username = $github_username;
        $this->repo = $github_repo;
        $this->plugin_slug = plugin_basename($plugin_file);
        
        $this->plugin_data = get_plugin_data($plugin_file);
        $this->version = $this->plugin_data['Version'];
    }
    
    private function get_repository_data() {
        if (is_null($this->github_data)) {
            $request_uri = sprintf('https://api.github.com/repos/%s/%s/releases', $this->username, $this->repo);
            
            $args = array(
                'timeout' => 30,
                'headers' => array(
                    'User-Agent' => 'WordPress-Plugin-Update-Checker'
                )
            );
            
            $github_token = ShopAcc_GitHub_Config::get_github_token();
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
            
            $this->github_data = $releases[0];
        }
        
        return $this->github_data;
    }
    
    public function modify_transient($transient) {
        if (empty($transient->checked)) {
            return $transient;
        }
        
        $github_data = $this->get_repository_data();
        
        if (!$github_data) {
            return $transient;
        }
        
        $plugin_folder = plugin_basename(dirname($this->plugin_file));
        $plugin_file = basename($this->plugin_file);
        
        $out_of_date = version_compare($this->version, $github_data['tag_name'], '<');
        
        if ($out_of_date) {
            $new_files = $github_data['zipball_url'];
            $slug = current(explode('/', $this->plugin_slug));
            
            $transient->response[$this->plugin_slug] = (object) array(
                'url' => $this->plugin_data['PluginURI'],
                'slug' => $slug,
                'package' => $new_files,
                'new_version' => $github_data['tag_name'],
                'tested' => $github_data['tag_name'],
                'icons' => array(),
                'banners' => array(),
            );
        }
        
        return $transient;
    }
    
    public function plugin_popup($result, $action, $args) {
        if (!empty($args->slug) && $args->slug == current(explode('/', $this->plugin_slug))) {
            $github_data = $this->get_repository_data();
            
            if ($github_data) {
                $args->slug = $this->plugin_slug;
                $args->plugin_name = $this->plugin_data['Name'];
                $args->version = $github_data['tag_name'];
                $args->author = $this->plugin_data['AuthorName'];
                $args->homepage = $this->plugin_data['PluginURI'];
                $args->requires = '5.0';
                $args->tested = '6.4';
                $args->downloaded = 0;
                $args->last_updated = $github_data['published_at'];
                $args->sections = array(
                    'description' => $this->plugin_data['Description'],
                    'changelog' => $github_data['body']
                );
                $args->download_link = $github_data['zipball_url'];
                
                return $args;
            }
        }
        
        return $result;
    }
    
    public function after_install($response, $hook_extra, $result) {
        global $wp_filesystem;
        
        $install_directory = plugin_dir_path($this->plugin_file);
        $plugin_directory = WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . dirname($this->plugin_slug);
        
        $wp_filesystem->move($result['destination'], $plugin_directory);
        $result['destination'] = $plugin_directory;
        
        if ($wp_filesystem->is_dir($result['destination'])) {
            $result['destination'] = trailingslashit($result['destination']);
        }
        
        return $result;
    }
    
    public function update_notice() {
        $github_data = $this->get_repository_data();
        
        if (!$github_data) {
            return;
        }
        
        $out_of_date = version_compare($this->version, $github_data['tag_name'], '<');
        
        if ($out_of_date && current_user_can('update_plugins')) {
            $update_url = wp_nonce_url(
                self_admin_url('update.php?action=upgrade-plugin&plugin=' . $this->plugin_slug),
                'upgrade-plugin_' . $this->plugin_slug
            );
            
            echo '<div class="notice notice-warning is-dismissible">';
            echo '<p><strong>ShopAcc Core:</strong> Có phiên bản mới ' . $github_data['tag_name'] . ' có sẵn. ';
            echo '<a href="' . $update_url . '">Cập nhật ngay</a> hoặc ';
            echo '<a href="' . admin_url('plugins.php') . '">Xem chi tiết</a></p>';
            echo '</div>';
        }
    }
    
    public function ajax_check_updates() {
        if (!current_user_can('update_plugins')) {
            wp_die('Unauthorized');
        }
        
        delete_site_transient('update_plugins');
        wp_update_plugins();
        
        $github_data = $this->get_repository_data();
        $out_of_date = version_compare($this->version, $github_data['tag_name'], '<');
        
        wp_send_json_success(array(
            'has_update' => $out_of_date,
            'current_version' => $this->version,
            'latest_version' => $github_data['tag_name'],
            'message' => $out_of_date ? 'Có phiên bản mới ' . $github_data['tag_name'] : 'Đã cập nhật mới nhất'
        ));
    }
}
