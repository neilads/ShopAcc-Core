<?php
/**
 * Plugin Name: ShopAcc Core
 * Plugin URI: https://taphoaneil.dev
 * Description: Plugin quản lý shop account với tính năng upload hàng loạt và tùy chỉnh WooCommerce
 * Version: 1.3.2
 * Author: Neil
 * Author URI: https://taphoaneil.dev
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: shopacc-core
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

define('SHOPACC_CORE_VERSION', '1.3.2');
define('SHOPACC_CORE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SHOPACC_CORE_PLUGIN_URL', plugin_dir_url(__FILE__));

class ShopAcc_Core {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    public function init() {
        $this->load_dependencies();
        $this->init_hooks();
    }
    
    private function load_dependencies() {
        require_once SHOPACC_CORE_PLUGIN_DIR . 'includes/class-image-optimizer.php';
        require_once SHOPACC_CORE_PLUGIN_DIR . 'includes/class-woocommerce-customizer.php';
        require_once SHOPACC_CORE_PLUGIN_DIR . 'includes/class-product-manager.php';
        require_once SHOPACC_CORE_PLUGIN_DIR . 'includes/class-template-loader.php';
        require_once SHOPACC_CORE_PLUGIN_DIR . 'includes/class-flatsome-activator.php';
        require_once SHOPACC_CORE_PLUGIN_DIR . 'includes/class-price-formatter.php';
    }
    
    private function init_hooks() {
        new ShopAcc_Image_Optimizer();
        new ShopAcc_WooCommerce_Customizer();
        new ShopAcc_Product_Manager();
        new ShopAcc_Template_Loader();
        new ShopAcc_Flatsome_Activator();
        new ShopAcc_Price_Formatter();
        
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_footer', array($this, 'change_out_of_stock_text'));
    }
    
    public function enqueue_scripts() {
        wp_enqueue_style('shopacc-core-style', SHOPACC_CORE_PLUGIN_URL . 'assets/css/style.css', array(), SHOPACC_CORE_VERSION);
        
        if (is_page('dang-acc')) {
            wp_enqueue_script('shopacc-core-upload', SHOPACC_CORE_PLUGIN_URL . 'assets/js/upload.js', array('jquery'), SHOPACC_CORE_VERSION, true);
        }
    }
    
    public function change_out_of_stock_text() {
        ?>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const elements = document.querySelectorAll('.out-of-stock-label');
                elements.forEach(function (element) {
                    element.textContent = 'Đã Bán';
                });
            });
        </script>
        <?php
    }
    
    public function activate() {
        $this->create_dang_acc_page();
        $this->create_flatsome_activation();
    }
    
    public function deactivate() {
        
    }
    
    
    private function create_dang_acc_page() {
        $page_check = get_page_by_path('dang-acc');
        if (empty($page_check)) {
            $page_data = array(
                'post_title'    => 'Đăng Acc',
                'post_name'     => 'dang-acc',
                'post_status'   => 'publish',
                'post_type'     => 'page',
                'post_content'  => '',
                'page_template' => 'upload.php'
            );
            wp_insert_post($page_data);
        }
    }
    
    private function create_flatsome_activation() {
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
}

new ShopAcc_Core();
