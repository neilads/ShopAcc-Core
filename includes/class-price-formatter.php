<?php

if (!defined('ABSPATH')) {
    exit;
}

class ShopAcc_Price_Formatter {
    
    public function __construct() {
        add_filter('wc_price', array($this, 'format_price'), 999, 5);
    }
    
    public function format_price($return, $price, $args, $unformatted_price, $original_price) {
        if (is_admin() || (defined('REST_REQUEST') && REST_REQUEST)) {
            return $return;
        }

        $n = (float)$unformatted_price;
        $neg = $n < 0;
        if ($neg) $n = -$n;

        if ($n >= 1000000) {
            $m = floor($n / 1000000);
            $d = floor(($n % 1000000) / 100000);
            $abbr = $m . 'm' . ($d > 0 ? $d : '');
        } elseif ($n >= 1000) {
            $k = floor($n / 1000);
            $d = floor(($n % 1000) / 100);
            $abbr = $k . 'k' . ($d > 0 ? $d : '');
        } else {
            $abbr = (string)intval($n);
        }

        if ($neg) $abbr = '-' . $abbr;

        if (!empty($args['in_span'])) {
            $aria = !empty($args['aria-hidden']) ? ' aria-hidden="true"' : '';
            return '<span class="woocommerce-Price-amount amount"' . $aria . '><bdi>' . $abbr . '</bdi></span>';
        }
        return $abbr;
    }
}
