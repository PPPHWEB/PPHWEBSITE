<?php
/**
 * PPH  ->  Cart, Checkout and Order-received   (plugin 5.6)
 *
 * WooCommerce ships very plain cart and checkout pages. This file brings them
 * into the PPH design, and - most important - makes sure a book cover is
 * always shown complete, never cropped.
 *
 * No WooCommerce template file is ever replaced here. Everything is added
 * through hooks and styled with CSS, so a WooCommerce update cannot break it.
 *
 * 5.6 changes:
 *   - License selector removed completely.
 *   - All customer facing text is English only.
 *   - Checkout line items: cover and title aligned on one clean row, with no
 *     stray gaps inside the book title.
 *   - A Back link is printed at the top left of the cart and checkout pages.
 */
if (!defined('ABSPATH')) { exit; }


/* -------------------------------------------------------------------------
   1. ASSETS
   ------------------------------------------------------------------------- */

function pph_is_shop_page() {
    if (!function_exists('is_cart')) { return false; }
    /* 5.6.7: category and tag listings are styled by this sheet too, so that a
       reader who clicks the Books label lands on a designed page. */
    return is_cart() || is_checkout() || is_account_page()
        || is_shop() || is_product_taxonomy();
}


/* -------------------------------------------------------------------------
   1b. CATEGORY TRAIL AND CATEGORY LISTINGS                          (5.6.7)

   The Books label above a book title is a real link now. Clicking it opens the
   Books listing, and that listing opens with its sub categories on top.
   ------------------------------------------------------------------------- */

/**
 * Build a linked category trail for a product, deepest branch first.
 * Example output: Books > Coloring Devotionals
 *
 * @param int $post_id Product ID.
 * @return string HTML, or an empty string when the product has no category.
 */
/* 5.6.9: where the Books label and the plain Shop archive should lead. */
if (!defined('PPH_BOOKS_URL')) {
    define('PPH_BOOKS_URL', 'https://store.praisepublishinghouse.com/');
}

function pph_category_trail($post_id) {

    $terms = get_the_terms($post_id, 'product_cat');
    if (empty($terms) || is_wp_error($terms)) { return ''; }

    /* Pick the term that sits deepest in the tree, so a sub category wins. */
    $deepest = null;
    $depth   = -1;

    foreach ($terms as $term) {
        $level = count(get_ancestors($term->term_id, 'product_cat'));
        if ($level > $depth) {
            $depth   = $level;
            $deepest = $term;
        }
    }

    if (!$deepest) { return ''; }

    $chain   = array_reverse(get_ancestors($deepest->term_id, 'product_cat'));
    $chain[] = $deepest->term_id;

    $links = array();
    $first = true;

    foreach ($chain as $term_id) {

        $term = get_term($term_id, 'product_cat');
        if (!$term || is_wp_error($term)) { continue; }

        /* 5.6.9: the top level of the trail goes to the store home page.
           Deeper levels keep their own listing.
           5.11.1: the top level always reads Books, whatever the first
           category of the product happens to be called. */
        $label = $term->name;

        if ($first) {
            $url   = PPH_BOOKS_URL;
            $label = 'Books';
            $first = false;
        } else {
            $url = pph_category_url($term);
            if (!$url) { continue; }
        }

        $links[] = '<a href="' . esc_url($url) . '">' . esc_html($label) . '</a>';
    }

    if (empty($links)) { return ''; }

    return implode(' <span class="pph-cat-sep">&rsaquo;</span> ', $links);
}

/* A category listing opens with its sub categories, then the books. */
add_filter('option_woocommerce_category_archive_display', function () {
    return 'both';
});

/* Sub categories are shown first, before individual books. */
add_filter('woocommerce_product_subcategories_args', function ($args) {
    $args['hide_empty'] = 0;
    return $args;
});

/**
 * Work out which categories to offer as a browse row.
 *
 * If the current category has children, those children are returned. When the
 * categories are flat, which is the case on this store today, the other
 * categories at the same level are returned instead. That way a reader always
 * has somewhere to go next.
 *
 * @return array array(current term id, array of terms)
 */
function pph_browse_terms() {

    $current = 0;

    if (is_tax('product_cat')) {
        $object = get_queried_object();
        if ($object && isset($object->term_id)) {
            $current = (int) $object->term_id;
        }
    }

    if ($current) {
        $children = get_terms(array(
            'taxonomy'   => 'product_cat',
            'parent'     => $current,
            'hide_empty' => false,
        ));
        if (!is_wp_error($children) && !empty($children)) {
            return array($current, $children);
        }
    }

    /* No children, so show everything sitting beside this category. */
    $parent = 0;

    if ($current) {
        $term = get_term($current, 'product_cat');
        if ($term && !is_wp_error($term)) {
            $parent = (int) $term->parent;
        }
    }

    $terms = get_terms(array(
        'taxonomy'   => 'product_cat',
        'parent'     => $parent,
        'hide_empty' => false,
    ));

    if (is_wp_error($terms)) { return array($current, array()); }

    return array($current, $terms);
}

/**
 * The plain WooCommerce Shop archive is never shown to a reader. It is sent to
 * the store home page instead, which is where the designed book sections live.
 */
add_action('template_redirect', function () {

    if (!function_exists('is_shop') || !is_shop()) { return; }
    if (is_front_page() || is_home() || is_search()) { return; }
    if (is_admin() || wp_doing_ajax()) { return; }

    $target  = trailingslashit(PPH_BOOKS_URL);
    $current = trailingslashit(strtok(home_url(add_query_arg(array())), '?'));

    /* Never redirect a page onto itself. */
    if ($current === $target) { return; }

    wp_redirect($target, 301);
    exit;
});

/**
 * Print the browse row at the top of every listing.
 */
add_action('woocommerce_before_shop_loop', function () {

    if (!function_exists('is_shop')) { return; }
    if (!is_shop() && !is_product_taxonomy()) { return; }

    $data    = pph_browse_terms();
    $current = $data[0];
    $terms   = $data[1];

    if (empty($terms)) { return; }

    $tiles = '';

    foreach ($terms as $term) {

        if ($term->slug === 'uncategorized') { continue; }

        $url = pph_category_url($term);
        if (!$url) { continue; }

        /* Category image if one was set, otherwise the first book cover. */
        $image        = '';
        $thumbnail_id = get_term_meta($term->term_id, 'thumbnail_id', true);

        if ($thumbnail_id) {
            $image = wp_get_attachment_image($thumbnail_id, 'medium', false, array('class' => 'pph-cat-img'));
        }

        if ($image === '') {
            $lookup = new WP_Query(array(
                'post_type'      => 'product',
                'posts_per_page' => 1,
                'fields'         => 'ids',
                'no_found_rows'  => true,
                'tax_query'      => array(array(
                    'taxonomy' => 'product_cat',
                    'field'    => 'term_id',
                    'terms'    => $term->term_id,
                )),
            ));
            if (!empty($lookup->posts)) {
                $image = get_the_post_thumbnail($lookup->posts[0], 'medium', array('class' => 'pph-cat-img'));
            }
            wp_reset_postdata();
        }

        $active = ((int) $term->term_id === $current) ? ' is-on' : '';
        $count  = (int) $term->count;
        $label  = ($count === 1) ? '1 title' : $count . ' titles';

        $tiles .= '<a class="pph-cat-tile' . $active . '" href="' . esc_url($url) . '">'
                . '<span class="pph-cat-thumb">' . $image . '</span>'
                . '<span class="pph-cat-name">' . esc_html($term->name) . '</span>'
                . '<span class="pph-cat-count">' . esc_html($label) . '</span>'
                . '</a>';
    }

    if ($tiles === '') { return; }

    echo '<div class="pph-cats">'
       . '<div class="pph-cats-t">Browse by category</div>'
       . '<div class="pph-cats-row">' . $tiles . '</div>'
       . '</div>';

}, 5);

add_action('wp_enqueue_scripts', function () {
    if (!pph_is_shop_page()) { return; }

    /* 5.6.1 FIX: the old line resolved to shop/assets/pph-shop.css, which does
       not exist, so the whole cart and checkout design silently never loaded.
       The plugin root is one level above this file. */
    $root = dirname(dirname(__FILE__)) . '/pph-product-theme.php';

    wp_enqueue_style(
        'pph-shop',
        plugins_url('assets/pph-shop.css', $root),
        array(),
        PPH_VER
    );
}, 100);

/**
 * Critical inline CSS for the cart and checkout line items.
 *
 * This is printed in the page head, so the cover and the title always sit on
 * one tidy row even if the stylesheet itself is blocked by a cache, a CDN or a
 * bad file path. It is small on purpose and only covers the layout that must
 * never look broken.
 */
add_action('wp_head', function () {
    if (!pph_is_shop_page()) { return; }
    ?>
<style id="pph-shop-critical">
/* Two columns: customer details on the left, Your order on the right. The
   right column has a hard minimum width, which is what stops a long book title
   from breaking into one word per line. */
.woocommerce-checkout .entry-content,
.woocommerce-cart .entry-content{max-width:1180px;width:100%;margin-left:auto;margin-right:auto}
.woocommerce-checkout form.checkout{display:grid!important;grid-template-columns:minmax(0,1.4fr) minmax(340px,.9fr);gap:0 28px;align-items:start}
.woocommerce-checkout #customer_details{grid-column:1;grid-row:1 / span 2;width:auto!important;float:none!important;min-width:0}
/* Heading and summary are one single card, joined with no seam. */
.woocommerce-checkout #order_review_heading{grid-column:2;grid-row:1;width:auto!important;float:none!important;margin:0!important;padding:20px 24px 0;background:#fff;border:1px solid #e7e1d6;border-bottom:0;border-radius:14px 14px 0 0}
.woocommerce-checkout #order_review{grid-column:2;grid-row:2;width:auto!important;float:none!important;min-width:0;background:#fff;border:1px solid #e7e1d6;border-top:0;border-radius:0 0 14px 14px;padding:6px 24px 22px}
.woocommerce-checkout #customer_details .col-1,
.woocommerce-checkout #customer_details .col-2{width:100%!important;float:none!important}
@media (max-width:980px){
.woocommerce-checkout form.checkout{grid-template-columns:1fr!important;gap:0}
.woocommerce-checkout #customer_details,
.woocommerce-checkout #order_review_heading,
.woocommerce-checkout #order_review{grid-column:1!important;grid-row:auto!important}
.woocommerce-checkout #order_review_heading{margin-top:26px!important}
}
/* Cart and totals: money never breaks across two lines. */
.woocommerce .cart_item td.product-price,
.woocommerce .cart_item td.product-subtotal,
.woocommerce table.shop_table thead th,
.woocommerce .cart_totals th,
.woocommerce .cart_totals .amount,
.woocommerce-checkout-review-order-table .amount,
.woocommerce .woocommerce-Price-amount,
.woocommerce bdi{white-space:nowrap!important;word-break:keep-all!important;overflow-wrap:normal!important;hyphens:none!important}
/* Only the amount is nowrap. A nowrap cell pushed the shipping text out of the card. */
.woocommerce .cart_totals td{white-space:normal!important;overflow-wrap:break-word}
body.woocommerce-cart,body.woocommerce-checkout{overflow-x:hidden}
.woocommerce .cart_totals table,.woocommerce table.shop_table{width:100%;max-width:100%}
.woocommerce table.cart td.product-quantity{white-space:nowrap!important}
/* Buttons keep their text on one line, never one letter per line. */
.woocommerce a.button,.woocommerce button.button,.woocommerce input.button,.woocommerce .actions .button,.woocommerce .coupon .button,.woocommerce #place_order{width:auto!important;min-width:fit-content;white-space:nowrap!important;word-break:keep-all!important;overflow-wrap:normal!important;hyphens:none!important;line-height:1.2}
.woocommerce .cart .actions .coupon{display:flex;flex-wrap:nowrap;align-items:center;gap:10px;width:100%;float:none;margin:0 0 14px}
.woocommerce .cart .actions .coupon #coupon_code{flex:1 1 auto;width:auto!important;min-width:0;height:46px;margin:0}
.woocommerce .cart .actions .coupon .button{flex:0 0 auto;height:46px;padding:0 18px;margin:0}
.woocommerce .cart .actions{display:flex;flex-direction:column;align-items:stretch;padding:16px 0 0}
.woocommerce .cart .actions button[name=update_cart]{align-self:flex-start;height:46px;padding:0 20px;margin:0}
/* Small screens: the column label sits beside the value, never on top of it. */
@media (max-width:782px){
.woocommerce table.shop_table_responsive tr{display:block;border-bottom:1px solid #e7e1d6;padding:6px 0}
.woocommerce table.shop_table_responsive tr td{display:flex;align-items:center;justify-content:space-between;gap:14px;width:100%;text-align:right;padding:10px 2px;border:0}
.woocommerce table.shop_table_responsive tr td::before{position:static!important;float:none!important;display:block;width:auto!important;flex:0 0 auto;text-align:left;font-size:11px;font-weight:700;letter-spacing:.5px;text-transform:uppercase;color:#6f665e;white-space:nowrap}
.woocommerce table.shop_table_responsive tr td.product-remove::before,
.woocommerce table.shop_table_responsive tr td.product-thumbnail::before{content:none;display:none}
.woocommerce .cart_totals table tr{display:flex;align-items:baseline;justify-content:space-between;flex-wrap:wrap;gap:6px 14px;padding:12px 0;border-bottom:1px solid #e7e1d6}
.woocommerce .cart_totals table th,.woocommerce .cart_totals table td{display:block;width:auto;padding:0;border:0;text-align:right}
.woocommerce .cart_totals table th{flex:0 0 auto;text-align:left;font-size:11px;font-weight:700;letter-spacing:.5px;text-transform:uppercase;color:#6f665e;white-space:nowrap}
.woocommerce .cart_totals table tr.woocommerce-shipping-totals{display:block;padding:12px 0}
.woocommerce .cart_totals table tr.woocommerce-shipping-totals th{display:block;width:100%;text-align:left;margin:0 0 6px}
.woocommerce .cart_totals table tr.woocommerce-shipping-totals td{display:block;width:100%;max-width:100%;text-align:left;margin:0;white-space:normal!important}
.woocommerce .cart_totals ul#shipping_method{margin:0;padding:0;list-style:none}
.woocommerce .cart_totals ul#shipping_method li{margin:0 0 6px;white-space:normal}
.woocommerce .cart_totals .woocommerce-shipping-destination,
.woocommerce .cart_totals .shipping-calculator-button{display:block;margin-top:6px;white-space:normal}
.woocommerce .cart .actions button[name=update_cart]{width:100%;align-self:stretch}
.pph-back-wrap{margin:22px 0 16px}
}
.pph-back-wrap{margin:18px 0 18px}
/* 5.7.0: fields that stay in the form but are never shown. */
.woocommerce form .form-row.pph-field-hidden,.pph-field-hidden{display:none!important}
/* 5.6.8: the listing grid is forced, because the theme styles ul.products with
   stronger selectors and the plain WooCommerce look kept coming back. */
.woocommerce ul.products,.woocommerce-page ul.products{display:grid!important;grid-template-columns:repeat(4,minmax(0,1fr))!important;gap:22px!important;margin:18px 0 0!important;padding:0!important;list-style:none!important}
.woocommerce ul.products::before,.woocommerce ul.products::after{content:none!important}
.woocommerce ul.products li.product,.woocommerce-page ul.products li.product,.woocommerce ul.products li.product-category{width:100%!important;max-width:100%!important;float:none!important;clear:none!important;margin:0!important;padding:14px 14px 18px!important;background:#fff;border:1px solid #e7e1d6;border-radius:14px;display:flex!important;flex-direction:column;text-align:center}
.woocommerce ul.products li.product img,.woocommerce-page ul.products li.product img{width:100%!important;height:240px!important;object-fit:contain!important;margin:0 0 12px!important;border-radius:8px;background:#fefcf6}
.woocommerce ul.products li.product .button{margin:auto 0 0!important;width:auto!important;white-space:nowrap!important;background:#e8622c;color:#fff;border-radius:999px;padding:11px 20px;font-weight:700}
.pph-cats{margin:6px 0 22px}
.pph-cats-t{font-size:12px;font-weight:700;letter-spacing:.6px;text-transform:uppercase;color:#6f665e;margin:0 0 12px}
.pph-cats-row{display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:14px}
.pph-cat-tile{display:flex;flex-direction:column;align-items:center;text-align:center;gap:4px;padding:14px 12px 16px;background:#fff;border:1px solid #e7e1d6;border-radius:14px;text-decoration:none;color:#141414}
.pph-cat-tile.is-on{border-color:#e8622c;background:#fff6f1}
.pph-cat-thumb,.pph-cat-thumb img{display:block;width:100%;height:108px;object-fit:contain;border-radius:8px;background:#fefcf6}
.pph-cat-name{font-family:Playfair Display,Georgia,serif;font-size:15px;line-height:1.3;color:#3f1717}
.pph-cat-count{font-size:12px;color:#6f665e}
@media (max-width:980px){.woocommerce ul.products,.woocommerce-page ul.products{grid-template-columns:repeat(3,minmax(0,1fr))!important}}
@media (max-width:782px){
.woocommerce ul.products,.woocommerce-page ul.products{grid-template-columns:repeat(2,minmax(0,1fr))!important;gap:14px!important}
.woocommerce ul.products li.product img,.woocommerce-page ul.products li.product img{height:180px!important}
.pph-cats-row{grid-template-columns:repeat(2,minmax(0,1fr))}
.pph-cat-thumb,.pph-cat-thumb img{height:90px}
}
.woocommerce-checkout-review-order-table{width:100%!important;table-layout:auto!important;border-collapse:collapse}
.woocommerce-checkout-review-order-table th,
.woocommerce-checkout-review-order-table td{vertical-align:middle;word-break:normal!important;overflow-wrap:normal!important;hyphens:none!important}
.woocommerce-checkout-review-order-table th.product-name,
.woocommerce-checkout-review-order-table td.product-name{width:auto!important;text-align:left!important}
.woocommerce-checkout-review-order-table th.product-total,
.woocommerce-checkout-review-order-table td.product-total,
.woocommerce-checkout-review-order-table tfoot th,
.woocommerce-checkout-review-order-table tfoot td{width:1%!important;text-align:right!important;white-space:nowrap!important}
.pph-co-name{display:block;font-size:15px;font-weight:600;line-height:1.4;letter-spacing:0;text-transform:none;word-break:normal;overflow-wrap:normal;hyphens:none}
.pph-co-name br{display:none}
</style>
    <?php
}, 99);


/* -------------------------------------------------------------------------
   2. BACK LINK  -  top left of cart and checkout

   The site header is hidden, so without this a customer standing on the cart
   or checkout page has no way back to the store. The link points at the page
   the customer actually came from when that is known, and falls back to the
   shop page.
   ------------------------------------------------------------------------- */

/**
 * Where the Back link goes when the previous page is not usable.
 *
 * The WooCommerce default Shop page is deliberately NOT used here. It is
 * unstyled and is not the books listing customers should land on. Change the
 * address below, or define PPH_BACK_FALLBACK in wp-config.php, to point the
 * Back link anywhere else.
 */
if (!defined('PPH_BACK_FALLBACK')) {
    define('PPH_BACK_FALLBACK', 'https://praisepublishinghouse.com/books.html');
}

function pph_back_url() {
    $ref = wp_get_referer();

    if ($ref) {
        $ref_path = untrailingslashit(strtok($ref, '?'));
        $skip     = array();

        /* Cart, checkout and the bare Shop page are never useful as a Back
           target, so they are skipped. */
        foreach (array('cart', 'checkout', 'shop') as $slug) {
            $page = wc_get_page_permalink($slug);
            if ($page) { $skip[] = untrailingslashit(strtok($page, '?')); }
        }

        $is_shopish = (bool) preg_match('#/(shop|cart|checkout)(/|$)#i', $ref_path);

        if (!in_array($ref_path, $skip, true) && !$is_shopish) { return $ref; }
    }

    return PPH_BACK_FALLBACK ? PPH_BACK_FALLBACK : home_url('/');
}

/* 5.11.11: the section a book was taken from.

   The cart Back link should return to the listing the book came from, which
   is its deepest product category, not the store front and not the browser
   referrer. The cart is read from the last line item, because that is the
   book the shopper just added.

   A term with a parent is preferred. Only when a product sits in one plain
   top level category is that one used, and the store front is the last
   resort. */
function pph_cart_section_url() {

    if (!function_exists('WC') || !WC()->cart || WC()->cart->is_empty()) {
        return '';
    }

    $items = WC()->cart->get_cart();
    if (empty($items)) { return ''; }

    $last = end($items);
    if (empty($last['product_id'])) { return ''; }

    $terms = get_the_terms($last['product_id'], 'product_cat');
    if (!$terms || is_wp_error($terms)) { return ''; }

    $deepest = null;

    foreach ($terms as $term) {
        if (strtolower($term->slug) === 'uncategorized') { continue; }

        /* A child term wins over a plain top level one. */
        if ($term->parent) {
            if (!$deepest || !$deepest->parent) { $deepest = $term; }
            continue;
        }

        if (!$deepest) { $deepest = $term; }
    }

    if (!$deepest) { return ''; }

    /* 5.11.12: a designed landing page wins over the plain archive. */
    return pph_category_url($deepest);
}

/* The cart link, with every fallback in order. */
function pph_cart_back_url() {

    $url = pph_cart_section_url();
    if ($url) { return $url; }

    $ref = pph_back_url();
    if ($ref) { return $ref; }

    return PPH_BOOKS_URL;
}

function pph_back_link($label, $url = '') {
    if (!$url) { $url = pph_back_url(); }
    ?>
    <div class="pph-back-wrap">
      <a class="pph-back" href="<?php echo esc_url($url); ?>">
        <span class="pph-back-ic" aria-hidden="true">&larr;</span>
        <span class="pph-back-t"><?php echo esc_html($label); ?></span>
      </a>
    </div>
    <?php
}

add_action('woocommerce_before_cart', function () {
    pph_back_link('Continue shopping', pph_cart_back_url());
}, 5);

add_action('woocommerce_before_checkout_form', function () {
    pph_back_link('Back to cart', wc_get_cart_url());
}, 5);

/* Empty cart screen has its own hook, so the link is there too. */
add_action('woocommerce_cart_is_empty', function () {
    pph_back_link('Continue shopping', PPH_BOOKS_URL);
}, 5);


/* -------------------------------------------------------------------------
   3. COVER IMAGES  -  always complete, never cropped

   The checkout order-review table does not print a thumbnail at all, so the
   cover is attached to the product name instead.

   Size medium is deliberate. WordPress soft-crops it, which keeps the whole
   book cover. The woocommerce_thumbnail size is a hard crop and shaves the
   edges off a cover.
   ------------------------------------------------------------------------- */

/* 5.6.2: no cover image is added in the checkout order summary any more.
   The summary column is narrow, and a cover inside it pushed the title into a
   one-word-per-line stack. Only a clean title is printed here. The cart page
   still shows a full, uncropped cover, where there is real room for it. */
add_filter('woocommerce_cart_item_name', function ($name, $cart_item, $cart_item_key) {
    if (!is_checkout() || is_wc_endpoint_url('order-received')) { return $name; }

    /* The incoming name carries line breaks and indentation from the Woo
       template. Collapsing that whitespace removes the odd gaps that showed up
       inside book titles. */
    $name = trim(preg_replace('/\s+/u', ' ', $name));

    return '<span class="pph-co-name">' . $name . '</span>';
}, 20, 3);

/* Cart page thumbnail stays uncropped as well. */
add_filter('woocommerce_cart_item_thumbnail', function ($thumbnail, $cart_item, $cart_item_key) {
    $product = isset($cart_item['data']) ? $cart_item['data'] : null;
    if (!$product || !is_a($product, 'WC_Product')) { return $thumbnail; }

    return $product->get_image('large', array('class' => 'pph-cart-img'));
}, 20, 3);


/* -------------------------------------------------------------------------
   3b. CHECKOUT  -  no postal address for a fully digital order
   ------------------------------------------------------------------------- */

/**
 * True when every line in the cart is a virtual product, so nothing will ever
 * be posted to the buyer and a street address serves no purpose.
 *
 * @return bool
 */
function pph_cart_is_digital_only() {

    if (!function_exists('WC') || !WC()->cart) { return false; }

    $items = WC()->cart->get_cart();
    if (empty($items)) { return false; }

    foreach ($items as $item) {
        $p = isset($item['data']) ? $item['data'] : null;
        if (!$p || !is_a($p, 'WC_Product')) { return false; }
        if (!$p->is_virtual()) { return false; }
    }
    return true;
}

/* A digital order only needs a name, an email address and a phone number. */
add_filter('woocommerce_checkout_fields', function ($fields) {

    if (!pph_cart_is_digital_only()) { return $fields; }

    $drop = array(
        'billing_company',
        'billing_address_1',
        'billing_address_2',
        'billing_city',
        'billing_postcode',
        'billing_state',
    );

    foreach ($drop as $key) {
        if (isset($fields['billing'][$key])) { unset($fields['billing'][$key]); }
    }

    /* Nothing is shipped, so the whole shipping block goes away. */
    if (isset($fields['shipping'])) { unset($fields['shipping']); }

    /* The country stays in the form but is never shown. Payment gateways and
       tax rules still read it, and removing it outright can break them. */
    if (isset($fields['billing']['billing_country'])) {
        $fields['billing']['billing_country']['class']    = array('pph-field-hidden');
        $fields['billing']['billing_country']['required'] = false;
    }

    /* The remaining fields take the full width of the column. */
    foreach (array('billing_first_name', 'billing_last_name', 'billing_email', 'billing_phone') as $key) {
        if (isset($fields['billing'][$key])) {
            $fields['billing'][$key]['class'] = array('form-row-wide');
        }
    }

    return $fields;
}, 20);

/* Keep a sensible country value behind the scenes. */
add_filter('default_checkout_billing_country', function ($country) {
    if (pph_cart_is_digital_only() && empty($country)) { return 'IN'; }
    return $country;
});

/* Do not print an address block on the thank you page or in the emails. */
add_filter('woocommerce_order_needs_shipping_address', function ($needs, $types, $order) {

    if (!$order || !is_a($order, 'WC_Order')) { return $needs; }

    foreach ($order->get_items() as $item) {
        $p = $item->get_product();
        if (!$p || !$p->is_virtual()) { return $needs; }
    }
    return false;
}, 20, 3);


/* -------------------------------------------------------------------------
   4. CART  -  small trust details
   ------------------------------------------------------------------------- */

/* A fully digital order should never mention shipping. */
add_filter('woocommerce_cart_needs_shipping', function ($needs) {
    if (!function_exists('WC') || !WC()->cart) { return $needs; }

    foreach (WC()->cart->get_cart() as $item) {
        $p = isset($item['data']) ? $item['data'] : null;
        if ($p && is_a($p, 'WC_Product') && !$p->is_virtual()) {
            return $needs;   /* something physical is in the cart, leave Woo alone */
        }
    }
    return false;
});

add_action('woocommerce_after_cart_totals', function () {
    ?>
    <div class="pph-trust">
      <div class="pph-trust-row">
        <span class="pph-trust-i"><?php echo pph_svg('ext'); ?> Secure payment</span>
        <span class="pph-trust-i"><?php echo pph_svg('pdf'); ?> Instant email delivery</span>
        <span class="pph-trust-i"><?php echo pph_svg('ebook'); ?> Read on any device</span>
      </div>
      <p class="pph-trust-note">
        No shipping charge on digital files. Your download link is emailed to
        you as soon as the payment is complete.
      </p>
    </div>
    <?php
}, 20);


/* -------------------------------------------------------------------------
   5. ORDER RECEIVED  -  Your resources are ready
   ------------------------------------------------------------------------- */

add_action('woocommerce_before_thankyou', function ($order_id) {
    $order = wc_get_order($order_id);
    if (!$order) { return; }

    $downloads = $order->get_downloadable_items();
    $ready     = !empty($downloads);
    ?>
    <div class="pph-ty<?php echo $ready ? ' is-ready' : ''; ?>">
      <div class="pph-ty-ic"><?php echo $ready ? pph_svg('pdf') : pph_svg('cart'); ?></div>
      <h2 class="pph-ty-title">
        <?php echo $ready ? 'Your resources are ready' : 'Thank you for your order'; ?>
      </h2>
      <p class="pph-ty-sub">
        <?php if ($ready) : ?>
          Your download links are below, and a copy has also been sent to
          <strong><?php echo esc_html($order->get_billing_email()); ?></strong>.
        <?php else : ?>
          Payment received. Your download link will reach
          <strong><?php echo esc_html($order->get_billing_email()); ?></strong>
          as soon as it is ready.
        <?php endif; ?>
      </p>
    </div>
    <?php
}, 5);


/* =========================================================================
   5.11.4  ->  LATE OVERRIDES FOR THE CART AND CHECKOUT HEAD AREA

   Two corrections kept losing to the theme and to the Additional CSS in the
   customizer, both of which use important. They are printed here in the
   footer, after every other stylesheet, with a selector that starts at html
   so that nothing on the page outranks them. The small script adds the same
   two values inline as a last resort, because an inline important value wins
   over every stylesheet.
   ========================================================================= */

add_action('wp_footer', 'pph_late_cart_fixes', 9999);

function pph_late_cart_fixes() {
    if (!function_exists('is_cart')) { return; }
    if (!is_cart() && !is_checkout()) { return; }
    ?>
    <style id="pph-late-fixes">
      html body .woocommerce-message::before,
      html body .woocommerce-info::before,
      html body .woocommerce-error::before,
      html body .woocommerce-notices-wrapper .woocommerce-message::before,
      html body div.woocommerce-message::before,
      html body ul.woocommerce-error::before {
        content: none !important;
        display: none !important;
        background: none !important;
        background-image: none !important;
        width: 0 !important;
        height: 0 !important;
        margin: 0 !important;
        padding: 0 !important;
      }

      html body .woocommerce-message,
      html body .woocommerce-info,
      html body .woocommerce-error {
        padding: 16px 22px !important;
        background-image: none !important;
        text-indent: 0 !important;
        list-style: none !important;
      }

      html body .pph-back-wrap {
        margin-top: 48px !important;
        margin-bottom: 20px !important;
        padding-top: 0 !important;
      }

      @media (max-width: 782px) {
        html body .pph-back-wrap {
          margin-top: 32px !important;
          margin-bottom: 16px !important;
        }
      }

      /* pph-late-tick: the tick leads, the sentence follows. */
      html body .woocommerce-message,
      html body .woocommerce-info {
        display: flex !important;
        align-items: center !important;
        gap: 12px !important;
        flex-wrap: wrap !important;
      }

      html body .woocommerce-message::after {
        content: "\2713" !important;
        display: inline-flex !important;
        order: -1 !important;
        flex: 0 0 auto !important;
        position: static !important;
        align-items: center !important;
        justify-content: center !important;
        width: 22px !important;
        height: 22px !important;
        border-radius: 50% !important;
        background: #1f7ae0 !important;
        color: #fff !important;
        font-size: 13px !important;
        font-weight: 700 !important;
        line-height: 1 !important;
        margin: 0 !important;
        padding: 0 !important;
      }

      html body .woocommerce-message .button,
      html body .woocommerce-info .button {
        order: 9 !important;
        float: none !important;
        margin: 0 0 0 auto !important;
      }
    </style>
    <script id="pph-late-fixes-js">
      (function () {
        function fix() {
          var wrap = document.querySelector('.pph-back-wrap');
          if (wrap) {
            var top = window.innerWidth < 783 ? '32px' : '48px';
            wrap.style.setProperty('margin-top', top, 'important');
            wrap.style.setProperty('margin-bottom', '20px', 'important');
          }

          var notes = document.querySelectorAll('.woocommerce-message, .woocommerce-info, .woocommerce-error');
          for (var i = 0; i < notes.length; i++) {
            notes[i].style.setProperty('padding', '16px 22px', 'important');
            notes[i].style.setProperty('background-image', 'none', 'important');
            notes[i].classList.add('pph-note-plain');
          }
        }

        if (document.readyState === 'loading') {
          document.addEventListener('DOMContentLoaded', fix);
        } else {
          fix();
        }

        window.addEventListener('resize', fix);
        document.addEventListener('updated_wc_div', fix);
      })();
    </script>
    <?php
}


/* -------------------------------------------------------------------------
   9. DESIGNED SECTION PAGES INSTEAD OF PLAIN CATEGORY ARCHIVES

   Some sections already have a hand built page on the store, and that page,
   not the bare WooCommerce category archive, is what a reader should ever
   see. Download PDF is the first of them.

   Everything is driven by one map of category slug to page address. Add a
   line to it, or hook the filter from a child plugin, and that section is
   covered everywhere at once:

     - the archive address redirects to the designed page,
     - the Back link from the cart lands on the designed page,
     - the breadcrumb trail and the browse tiles point at it.
   ------------------------------------------------------------------------- */

function pph_category_landing_pages() {

    $map = array(
        'download-pdf' => 'https://store.praisepublishinghouse.com/pdf-download/',
        /* 5.16.0: the default WooCommerce category (slug "uncategorized") is
           never shown as an archive. WooCommerce does not allow the default
           category to be deleted, so its address is 301-redirected to the
           store home page instead. */
        'uncategorized' => PPH_BOOKS_URL,
    );

    return apply_filters('pph_category_landing_pages', $map);
}

/**
 * The address a category should open at: its designed page when one is
 * listed, and its own archive otherwise.
 *
 * @param WP_Term|int $term Term object or term id.
 * @return string Empty string when the term cannot be resolved.
 */
function pph_category_url($term) {

    if (is_numeric($term)) { $term = get_term((int) $term, 'product_cat'); }
    if (!$term || is_wp_error($term)) { return ''; }

    $map = pph_category_landing_pages();

    if (!empty($map[$term->slug])) { return $map[$term->slug]; }

    $url = get_term_link($term);

    return is_wp_error($url) ? '' : $url;
}

/**
 * Send the plain archive to the designed page.
 */
add_action('template_redirect', function () {

    if (!function_exists('is_product_category') || !is_product_category()) { return; }
    if (is_admin() || wp_doing_ajax()) { return; }

    $term = get_queried_object();
    if (!$term || empty($term->slug)) { return; }

    $map = pph_category_landing_pages();
    if (empty($map[$term->slug])) { return; }

    $target  = trailingslashit($map[$term->slug]);
    $current = trailingslashit(strtok(home_url(add_query_arg(array())), '?'));

    /* Never redirect a page onto itself. */
    if ($current === $target) { return; }

    wp_redirect($target, 301);
    exit;
});
