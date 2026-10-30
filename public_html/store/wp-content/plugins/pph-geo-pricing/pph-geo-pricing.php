<?php
/**
 * Plugin Name: PPH Geo Pricing & Gateway Router
 * Description: INR price with Razorpay for India, fixed USD price with PayPal elsewhere. Switches automatically by visitor country.
 * Version: 1.0
 * Author: Praise Publishing House
 * Requires Plugins: woocommerce
 */

if (!defined('ABSPATH')) { exit; }

/* Fallback rate used only when no USD price is set (1 USD = N INR). */
if (!defined('PPH_USD_RATE')) { define('PPH_USD_RATE', 83); }

class PPH_Geo_Pricing {

    const USD_META = '_pph_price_usd';
    const COOKIE   = 'pph_geo_cur';

    public function __construct() {
        add_action('woocommerce_product_options_general_product_data', array($this, 'add_simple_field'));
        add_action('woocommerce_process_product_meta', array($this, 'save_simple_field'));
        add_action('woocommerce_variation_options_pricing', array($this, 'add_variation_field'), 10, 3);
        add_action('woocommerce_save_product_variation', array($this, 'save_variation_field'), 10, 2);

        add_filter('woocommerce_currency', array($this, 'filter_currency'), 99);

        $price_hooks = array(
            'woocommerce_product_get_price',
            'woocommerce_product_get_regular_price',
            'woocommerce_product_get_sale_price',
            'woocommerce_product_variation_get_price',
            'woocommerce_product_variation_get_regular_price',
            'woocommerce_product_variation_get_sale_price',
        );
        foreach ($price_hooks as $h) {
            add_filter($h, array($this, 'filter_price'), 99, 2);
        }
        add_filter('woocommerce_variation_prices_price', array($this, 'filter_price'), 99, 2);
        add_filter('woocommerce_variation_prices_regular_price', array($this, 'filter_price'), 99, 2);
        add_filter('woocommerce_variation_prices_sale_price', array($this, 'filter_price'), 99, 2);
        add_filter('woocommerce_get_variation_prices_hash', array($this, 'variation_hash'), 99, 3);

        add_filter('woocommerce_available_payment_gateways', array($this, 'filter_gateways'), 99);
    }

    public function is_india() {
        static $cache = null;
        if ($cache !== null) { return $cache; }

        /* Manual override: ?pph_cur=INR or ?pph_cur=USD */
        if (isset($_GET['pph_cur'])) {
            $v = strtoupper(sanitize_text_field(wp_unslash($_GET['pph_cur'])));
            if ($v === 'INR' || $v === 'USD') {
                $this->set_cookie($v);
                $_COOKIE[self::COOKIE] = $v;
            }
        }

        if (!empty($_COOKIE[self::COOKIE])) {
            $cache = ($_COOKIE[self::COOKIE] === 'INR');
            return $cache;
        }

        $country = '';

        if (!empty($_SERVER['HTTP_CF_IPCOUNTRY'])) {
            $country = strtoupper(substr($_SERVER['HTTP_CF_IPCOUNTRY'], 0, 2));
        }

        if (!$country && class_exists('WC_Geolocation')) {
            $geo = WC_Geolocation::geolocate_ip();
            if (!empty($geo['country'])) { $country = strtoupper($geo['country']); }
        }

        if (!$country) {
            $ip = $this->get_ip();
            if ($ip) {
                $resp = wp_remote_get('https://ipapi.co/' . $ip . '/country/', array('timeout' => 3));
                if (!is_wp_error($resp)) {
                    $c = trim(wp_remote_retrieve_body($resp));
                    if (strlen($c) === 2) { $country = strtoupper($c); }
                }
            }
        }

        if (!$country) { $country = 'IN'; }

        $india = ($country === 'IN');
        $this->set_cookie($india ? 'INR' : 'USD');
        $_COOKIE[self::COOKIE] = $india ? 'INR' : 'USD';
        $cache = $india;
        return $cache;
    }

    private function set_cookie($val) {
        if (headers_sent()) { return; }
        setcookie(self::COOKIE, $val, time() + 43200, defined('COOKIEPATH') ? COOKIEPATH : '/', defined('COOKIE_DOMAIN') ? COOKIE_DOMAIN : '');
    }

    private function get_ip() {
        $keys = array('HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR');
        foreach ($keys as $k) {
            if (!empty($_SERVER[$k])) {
                $parts = explode(',', $_SERVER[$k]);
                $ip = trim($parts[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP)) { return $ip; }
            }
        }
        return '';
    }

    private function is_backend() {
        return (is_admin() && !wp_doing_ajax());
    }

    public function filter_currency($currency) {
        if ($this->is_backend()) { return $currency; }
        return $this->is_india() ? 'INR' : 'USD';
    }

    public function filter_price($price, $product) {
        if ($this->is_backend()) { return $price; }
        if ($this->is_india()) { return $price; }
        if (!is_object($product)) { return $price; }

        $usd = get_post_meta($product->get_id(), self::USD_META, true);
        if (($usd === '' || $usd === null) && method_exists($product, 'get_parent_id') && $product->get_parent_id()) {
            $usd = get_post_meta($product->get_parent_id(), self::USD_META, true);
        }

        if ($usd !== '' && $usd !== null && is_numeric($usd)) {
            return floatval($usd);
        }

        $rate = floatval(apply_filters('pph_usd_rate', PPH_USD_RATE));
        if ($rate > 0 && is_numeric($price) && $price > 0) {
            return round(floatval($price) / $rate, 2);
        }
        return $price;
    }

    public function variation_hash($hash, $product, $for_display) {
        $hash[] = $this->is_india() ? 'INR' : 'USD';
        return $hash;
    }

    public function filter_gateways($gateways) {
        if ($this->is_backend()) { return $gateways; }
        $india = $this->is_india();
        foreach ($gateways as $id => $gw) {
            $lid = strtolower($id);
            $is_razorpay = (strpos($lid, 'razorpay') !== false);
            $is_paypal   = (strpos($lid, 'ppcp') !== false || strpos($lid, 'paypal') !== false);
            if ($india && $is_paypal) { unset($gateways[$id]); }
            if (!$india && $is_razorpay) { unset($gateways[$id]); }
        }
        return $gateways;
    }

    public function add_simple_field() {
        echo '<div class="options_group">';
        woocommerce_wp_text_input(array(
            'id'          => self::USD_META,
            'label'       => 'USD Price (International $)',
            'desc_tip'    => true,
            'description' => 'Fixed USD price shown to visitors outside India. India sees the regular INR price.',
            'data_type'   => 'decimal',
        ));
        echo '</div>';
    }

    public function save_simple_field($post_id) {
        if (isset($_POST[self::USD_META])) {
            update_post_meta($post_id, self::USD_META, wc_clean(wp_unslash($_POST[self::USD_META])));
        }
    }

    public function add_variation_field($loop, $variation_data, $variation) {
        woocommerce_wp_text_input(array(
            'id'            => self::USD_META . '[' . $loop . ']',
            'name'          => self::USD_META . '[' . $loop . ']',
            'value'         => get_post_meta($variation->ID, self::USD_META, true),
            'label'         => 'USD Price (International $)',
            'wrapper_class' => 'form-row form-row-full',
            'data_type'     => 'decimal',
        ));
    }

    public function save_variation_field($variation_id, $i) {
        if (isset($_POST[self::USD_META][$i])) {
            update_post_meta($variation_id, self::USD_META, wc_clean(wp_unslash($_POST[self::USD_META][$i])));
        }
    }
}

new PPH_Geo_Pricing();
