<?php
/**
 * PPH - Country based pricing (5.10.0, extended in 5.14.1)
 *
 * India sees the rupee price that is already set on the product.
 * A visitor outside India sees a separate dollar price that you set per book.
 *
 * How the country is decided, in order:
 *   1. the pph_country cookie, if the visitor used the currency switch
 *   2. WooCommerce geolocation of the IP address
 *   3. the store base country
 *
 * If a book has no dollar price, nothing changes for it and the rupee price
 * is shown to everyone. So this file is safe to activate before every book
 * has been filled in.
 */

if (!defined('ABSPATH')) { exit; }

/* The dollar currency used for buyers outside India. */
if (!defined('PPH_INTL_CURRENCY')) { define('PPH_INTL_CURRENCY', 'USD'); }

/* The home country, which keeps the rupee price. */
if (!defined('PPH_HOME_COUNTRY')) { define('PPH_HOME_COUNTRY', 'IN'); }

/* A fallback rate, in rupees to one dollar, used ONLY for a book that has no
   dollar price of its own. A price typed into the book always wins over this.
   It exists so a rupee number is never printed behind a dollar sign: without
   it a book with a missing dollar price showed 300 rupees as $300.00.
   Set it to 0 only if you would rather such a book showed no converted price. */
if (!defined('PPH_USD_RATE')) { define('PPH_USD_RATE', 88); }


/* -------------------------------------------------------------------------
   1. WHICH COUNTRY IS THIS VISITOR IN
   ------------------------------------------------------------------------- */

function pph_visitor_country() {
    static $cc = null;
    if ($cc !== null) { return $cc; }

    /* A manual choice always wins. */
    if (!empty($_COOKIE['pph_country'])) {
        $c = strtoupper(substr(sanitize_text_field(wp_unslash($_COOKIE['pph_country'])), 0, 2));
        if (strlen($c) === 2) { $cc = $c; return $cc; }
    }

    /* Cloudflare and some hosts already know the country. */
    if (!empty($_SERVER['HTTP_CF_IPCOUNTRY'])) {
        $c = strtoupper(substr(sanitize_text_field(wp_unslash($_SERVER['HTTP_CF_IPCOUNTRY'])), 0, 2));
        if (strlen($c) === 2 && $c !== 'XX') { $cc = $c; return $cc; }
    }

    if (class_exists('WC_Geolocation')) {
        $geo = WC_Geolocation::geolocate_ip('', true, true);
        if (!empty($geo['country'])) { $cc = strtoupper($geo['country']); return $cc; }
    }

    $cc = PPH_HOME_COUNTRY;
    return $cc;
}

/**
 * True when this visitor should be charged in dollars.
 */
function pph_is_intl() {
    if (is_admin() && !wp_doing_ajax()) { return false; }
    return (pph_visitor_country() !== PPH_HOME_COUNTRY);
}


/* -------------------------------------------------------------------------
   2. THE DOLLAR PRICE OF ONE PRODUCT
   ------------------------------------------------------------------------- */

function pph_usd_price($product) {
    if (!is_a($product, 'WC_Product')) { return ''; }

    $id  = $product->get_id();
    $val = get_post_meta($id, '_pph_price_usd', true);

    /* A variation falls back to the price set on its parent book. */
    if ($val === '' && $product->get_parent_id()) {
        $val = get_post_meta($product->get_parent_id(), '_pph_price_usd', true);
    }

    $val = trim((string) $val);
    if ($val !== '' && is_numeric($val)) { return $val; }

    /* No dollar price of its own, so use the rate if one was set. */
    if (PPH_USD_RATE > 0) {
        $inr = get_post_meta($id, '_regular_price', true);
        if ($inr !== '' && is_numeric($inr)) {
            return (string) round(((float) $inr) / (float) PPH_USD_RATE, 2);
        }
    }

    return '';
}


/* -------------------------------------------------------------------------
   3. SWAP THE CURRENCY AND THE PRICE
   ------------------------------------------------------------------------- */

add_filter('woocommerce_currency', function ($currency) {
    if (pph_is_intl()) { return PPH_INTL_CURRENCY; }
    return $currency;
}, 20);

function pph_filter_price($price, $product) {
    if (!pph_is_intl()) { return $price; }
    $usd = pph_usd_price($product);
    return ($usd === '') ? $price : $usd;
}

foreach (array(
    'woocommerce_product_get_price',
    'woocommerce_product_get_regular_price',
    'woocommerce_product_variation_get_price',
    'woocommerce_product_variation_get_regular_price',
) as $pph_price_hook) {
    add_filter($pph_price_hook, 'pph_filter_price', 20, 2);
}

/* A sale price is only kept when the book itself carries a dollar sale price. */
add_filter('woocommerce_product_get_sale_price', function ($price, $product) {
    if (!pph_is_intl()) { return $price; }
    $sale = get_post_meta($product->get_id(), '_pph_sale_usd', true);
    return (trim((string) $sale) === '') ? '' : $sale;
}, 20, 2);

/* WooCommerce caches variation prices, so the cache key must know the country. */
add_filter('woocommerce_get_variation_prices_hash', function ($hash) {
    $hash['pph_country'] = pph_visitor_country();
    return $hash;
}, 20);


/* -------------------------------------------------------------------------
   4. PAGE CACHING SAFETY

   A cached page must not serve a rupee price to a dollar visitor. The cookie
   below gives LiteSpeed something to vary on, and the cart, checkout and
   account pages are never cached.
   ------------------------------------------------------------------------- */

add_action('init', function () {
    if (is_admin()) { return; }
    $want = pph_is_intl() ? 'USD' : 'INR';
    if (!isset($_COOKIE['pph_cur']) || $_COOKIE['pph_cur'] !== $want) {
        if (!headers_sent()) {
            setcookie('pph_cur', $want, time() + DAY_IN_SECONDS, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN);
        }
    }
}, 5);

add_filter('litespeed_vary_cookies', function ($cookies) {
    $cookies[] = 'pph_cur';
    $cookies[] = 'pph_country';
    return $cookies;
});

add_action('template_redirect', function () {
    if (function_exists('is_cart') && (is_cart() || is_checkout() || is_account_page())) {
        if (!defined('DONOTCACHEPAGE')) { define('DONOTCACHEPAGE', true); }
    }
});


/* -------------------------------------------------------------------------
   5. THE CURRENCY SWITCH SHOWN TO THE READER

   A short line under the price, so a reader who is travelling can pick the
   currency that suits them. It writes the pph_country cookie and reloads.
   ------------------------------------------------------------------------- */

add_action('wp_footer', function () {
    if (!function_exists('is_product')) { return; }
    if (!is_product() && !is_cart() && !is_checkout()) { return; }
    ?>
    <script>
    (function () {
        function set(cc) {
            document.cookie = 'pph_country=' + cc + ';path=/;max-age=' + (60 * 60 * 24 * 30);
            location.reload();
        }
        document.addEventListener('click', function (e) {
            var a = e.target.closest ? e.target.closest('[data-pph-cur]') : null;
            if (!a) { return; }
            e.preventDefault();
            set(a.getAttribute('data-pph-cur') === 'USD' ? 'US' : 'IN');
        });
    })();
    </script>
    <?php
});

function pph_currency_switch_html() {
    $intl = pph_is_intl();
    $out  = '<p class="pph-cur-switch">';
    if ($intl) {
        $out .= 'Prices shown in US dollars. '
              . '<a href="#" data-pph-cur="INR">Show Indian rupees</a>';
    } else {
        $out .= 'Prices shown in Indian rupees. '
              . '<a href="#" data-pph-cur="USD">Show US dollars</a>';
    }
    $out .= '</p>';
    return $out;
}

add_action('woocommerce_single_product_summary', function () {
    echo pph_currency_switch_html();
}, 25);


/* -------------------------------------------------------------------------
   6. ADMIN: THE DOLLAR PRICE FIELD
   ------------------------------------------------------------------------- */

add_action('add_meta_boxes', function () {
    add_meta_box(
        'pph_intl_price_box',
        'PPH - International Price',
        'pph_render_intl_price_box',
        'product',
        'side',
        'default'
    );
});

function pph_render_intl_price_box($post) {
    $usd  = get_post_meta($post->ID, '_pph_price_usd', true);
    $sale = get_post_meta($post->ID, '_pph_sale_usd', true);
    wp_nonce_field('pph_intl_' . $post->ID, 'pph_intl_nonce');
    ?>
    <p style="margin-top:0">
        <label for="pph_price_usd"><strong>Price in US dollars</strong></label><br>
        <input type="text" id="pph_price_usd" name="pph_price_usd" class="widefat"
               value="<?php echo esc_attr($usd); ?>" placeholder="11.99">
    </p>
    <p>
        <label for="pph_sale_usd">Sale price in US dollars</label><br>
        <input type="text" id="pph_sale_usd" name="pph_sale_usd" class="widefat"
               value="<?php echo esc_attr($sale); ?>" placeholder="">
    </p>
    <p style="color:#6f665e;font-size:12px">
        A visitor in India always sees the rupee price above.
        A visitor outside India sees this dollar price.
        Leave it empty to show the rupee price to everyone.
    </p>
    <?php
}

add_action('save_post_product', function ($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) { return; }
    if (!isset($_POST['pph_intl_nonce'])) { return; }
    if (!wp_verify_nonce($_POST['pph_intl_nonce'], 'pph_intl_' . $post_id)) { return; }
    if (!current_user_can('edit_post', $post_id)) { return; }

    foreach (array('pph_price_usd' => '_pph_price_usd', 'pph_sale_usd' => '_pph_sale_usd') as $field => $meta) {
        $raw = isset($_POST[$field]) ? trim(sanitize_text_field(wp_unslash($_POST[$field]))) : '';
        if ($raw === '' || !is_numeric($raw)) {
            delete_post_meta($post_id, $meta);
        } else {
            update_post_meta($post_id, $meta, wc_format_decimal($raw));
        }
    }
}, 20);


/* -------------------------------------------------------------------------
   7. QUICK ADD SUPPORT

   A JSON file may carry "price_usd", so a new book arrives with both prices.
   ------------------------------------------------------------------------- */

function pph_apply_price_usd($post_id, $data) {
    if (!is_array($data)) { return; }
    if (isset($data['price_usd']) && $data['price_usd'] !== '' && is_numeric($data['price_usd'])) {
        update_post_meta($post_id, '_pph_price_usd', wc_format_decimal($data['price_usd']));
    }
}


/* -------------------------------------------------------------------------
   5.14.1  -  THE BOOK PAGE PRICE OF A BOOK THAT HAS VARIATIONS

   A book sold as a variable product, with an Ebook variation and perhaps a
   PDF variation, does not ask each variation for its price while the page is
   built. WooCommerce reads all of the variation prices straight out of the
   database in one bulk read and caches the result. The filters in section 3
   are never called during that read, so the page printed the rupee number
   while the currency symbol had already been switched to the dollar. That is
   exactly why a book page showed $300.00 while the cart, which does ask the
   variation object, correctly showed $11.99.

   The three filters below run inside that bulk read, so the dollar price is
   used there too. The cache key already carries the country, so a rupee
   visitor and a dollar visitor never share a cached price range.
   ------------------------------------------------------------------------- */

function pph_variation_prices_price($price, $variation, $product) {
    if (!pph_is_intl()) { return $price; }
    $usd = pph_usd_price($variation);
    return ($usd === '') ? $price : $usd;
}

add_filter('woocommerce_variation_prices_price', 'pph_variation_prices_price', 20, 3);
add_filter('woocommerce_variation_prices_regular_price', 'pph_variation_prices_price', 20, 3);

/* A dollar sale price is only kept when one was actually typed in. */
add_filter('woocommerce_variation_prices_sale_price', function ($price, $variation, $product) {
    if (!pph_is_intl()) { return $price; }
    $sale = get_post_meta($variation->get_id(), '_pph_sale_usd', true);
    if (trim((string) $sale) === '' && $variation->get_parent_id()) {
        $sale = get_post_meta($variation->get_parent_id(), '_pph_sale_usd', true);
    }
    return (trim((string) $sale) === '') ? '' : $sale;
}, 20, 3);


/* -------------------------------------------------------------------------
   5.14.1  -  A DOLLAR PRICE FIELD ON EVERY VARIATION

   The box in section 6 sets one dollar price for the whole book, which is
   enough when only the ebook is sold here. When a book has more than one
   digital variation, each one can now carry its own dollar price. A variation
   left empty falls back to the price set on the book itself.
   ------------------------------------------------------------------------- */

add_action('woocommerce_variation_options_pricing', function ($loop, $variation_data, $variation) {

    woocommerce_wp_text_input(array(
        'id'            => 'pph_variation_price_usd_' . $loop,
        'name'          => 'pph_variation_price_usd[' . $loop . ']',
        'value'         => get_post_meta($variation->ID, '_pph_price_usd', true),
        'label'         => 'Price in US dollars',
        'placeholder'   => '11.99',
        'wrapper_class' => 'form-row form-row-first',
        'data_type'     => 'price',
        'desc_tip'      => true,
        'description'   => 'Shown to a visitor outside India. Leave it empty to use the dollar price set on the book itself.',
    ));

    woocommerce_wp_text_input(array(
        'id'            => 'pph_variation_sale_usd_' . $loop,
        'name'          => 'pph_variation_sale_usd[' . $loop . ']',
        'value'         => get_post_meta($variation->ID, '_pph_sale_usd', true),
        'label'         => 'Sale price in US dollars',
        'placeholder'   => '',
        'wrapper_class' => 'form-row form-row-last',
        'data_type'     => 'price',
        'desc_tip'      => true,
        'description'   => 'Optional. Leave it empty when this edition is not on offer.',
    ));

}, 20, 3);

add_action('woocommerce_save_product_variation', function ($variation_id, $loop) {

    $fields = array(
        'pph_variation_price_usd' => '_pph_price_usd',
        'pph_variation_sale_usd'  => '_pph_sale_usd',
    );

    foreach ($fields as $field => $meta) {
        $raw = isset($_POST[$field][$loop])
            ? trim(sanitize_text_field(wp_unslash($_POST[$field][$loop])))
            : '';

        if ($raw === '' || !is_numeric($raw)) {
            delete_post_meta($variation_id, $meta);
        } else {
            update_post_meta($variation_id, $meta, wc_format_decimal($raw));
        }
    }

    $parent = wp_get_post_parent_id($variation_id);
    if ($parent) { wc_delete_product_transients($parent); }

}, 20, 2);


/* -------------------------------------------------------------------------
   5.14.1  -  CLEARING THE CACHED PRICE RANGE WHEN A DOLLAR PRICE CHANGES

   WooCommerce keeps the price range of a variable book in a transient. A new
   dollar price must throw that away, otherwise the old number keeps showing.
   ------------------------------------------------------------------------- */

add_action('updated_post_meta', function ($meta_id, $post_id, $meta_key) {
    if ($meta_key !== '_pph_price_usd' && $meta_key !== '_pph_sale_usd') { return; }
    if (!function_exists('wc_delete_product_transients')) { return; }
    $parent = wp_get_post_parent_id($post_id);
    wc_delete_product_transients($parent ? $parent : $post_id);
}, 20, 3);

add_action('added_post_meta', function ($meta_id, $post_id, $meta_key) {
    if ($meta_key !== '_pph_price_usd' && $meta_key !== '_pph_sale_usd') { return; }
    if (!function_exists('wc_delete_product_transients')) { return; }
    $parent = wp_get_post_parent_id($post_id);
    wc_delete_product_transients($parent ? $parent : $post_id);
}, 20, 3);
