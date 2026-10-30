<?php
/*
Plugin Name: PPH Product Page Theme
Description: Custom WooCommerce single product page, cart and checkout styling for Praise Publishing House. Adds per-book format cards (PDF / eBook / Paperback / Hardcover), retailer buttons (Amazon, IngramSpark, Kindle, Apple Books, Google Play, TPT), and a Book Details panel.
Version: 5.15.3
Author: Praise Publishing House
*/

if (!defined('ABSPATH')) { exit; }

/* =========================================================================
   PPH 5.0 CHANGELOG
   - Keeps everything from 3.8 (template override, cart badge, thumbnails)
   - Rebuilds the 3.9 "Book Details" fields (nothing lost)
   - NEW: per-book format cards. Digital = sold here, Print = retailer links
   - NEW: retailer buttons per format, blank field = button hidden
   - NEW: tabbed panel (Sample Pages / About / Details / Related)
   - NEW: Astra header + footer hide moved from Additional CSS into plugin

   5.1
   - No price is shown anywhere for the print formats (Paperback / Hardcover).
     Amazon and IngramSpark show their own price and it keeps changing, so
     holding an amount here was both wrong and risky.

   5.2
   - Real brand logos on the retailer buttons (Amazon smile, IngramSpark
     spark, Kindle, Apple Books, Google Play, Flipkart, B&N, Kobo, TPT).
   - The retailer strip is now ALWAYS visible, right under the format cards,
     so both are seen together. Choosing a format highlights its retailers.
   - Amazon and IngramSpark links live in completely separate fields.

   5.3
   - NEW: Products -> Quick Add Book. Paste JSON and a complete book product
     is created: title, description, categories, cover + gallery images,
     formats, retailer links and every Book Details field.
   - The screen also carries a ready-made AI prompt, generated from the
     plugin's own fields, so adding a field updates the prompt automatically
     and it can never go stale.

   5.4
   - NEW: Always-on SEO. Every time a book product is saved, the SEO title,
     meta description, keywords, product tags, brand, GTIN from ISBN and the
     image alt text are filled in, straight into the Rank Math fields.
     Anything you typed yourself is never overwritten.
   - NEW: Media Library picker on the Quick Add screen. You pick the cover and
     gallery images yourself, as many as you like. No images are asked from AI.
   - The Rank Math product schema gained ISBN, author, publisher, page count,
     language, datePublished and the offer validFrom, which clears the Google
     Search Console warnings.

   5.5
   - NEW: Cart and Checkout fully redesigned in the PPH theme. Checkout is a
     two column layout, the order summary is sticky, and the payment methods
     look like cards.
   - FIX: A book cover is never cropped - cart, checkout, related book
     suggestions, the thumbnail rail and sample pages all show it complete.
   - NEW: "What's Included" section with filter chips and sort. PDF,
     PowerPoint, video, audio, printable, worksheet, answer key, editable doc,
     ePub and bonus - ten types are ready. A new type later is one line.
   - NEW: Per-book FAQ accordion, plus its FAQPage schema for Google.
   - NEW: Edition / Updated / Access freshness pills.
   - NEW: Sticky buy bar on mobile.
   - A digital-only cart never mentions shipping.

   5.6
   - REMOVED: the checkout license selector and all of its text.
   - All plugin text, on the store and inside the admin screens, is now
     English only.
   - FIX: Checkout line items show the cover and the book title on one clean
     row, with no stray gaps or broken words inside the title.
   - NEW: A Back link at the top left of the cart and checkout pages, which
     returns the customer to the page they came from.

   5.6.1
   - CRITICAL FIX: the cart / checkout stylesheet was being requested from the
     wrong folder, so none of the shop design was ever loading. Fixed, and the
     line item layout is now also printed as small inline critical CSS, so the
     cover and title cannot look broken even behind a cache or CDN.
   - FIX: the order review table is a fixed layout table now. The Subtotal
     column can no longer collapse into a narrow strip, amounts never wrap, and
     the cover sits on one row with the title, which is never hyphenated.

   5.6.2
   - FIX: the checkout is a single stacked full width column now. The old narrow
     side column squeezed long book titles into one word per line.
   - CHANGE: the checkout order summary shows the book title only, with no cover
     image. The cart page still shows the full, uncropped cover.

   5.6.3
   - The checkout is back to two columns: customer details on the left, Your
     order on the right, with no cover image in the summary. The right column
     now has a hard minimum width, so a long book title can never collapse into
     one word per line. Below 980px the two columns stack.
   - The Back link no longer points at the plain WooCommerce Shop page. It falls
     back to the books listing set in PPH_BACK_FALLBACK, and any Shop, cart or
     checkout address is skipped as a Back target.

   5.6.4
   - FIX: the Your order heading and the summary table are one single joined
     card now, pinned to the top of the right column. Earlier they looked like
     two separate broken boxes.
   - FIX: every price, subtotal and total is nowrap on the cart, checkout and
     totals table, so no digit can ever drop to the next line. Table headings
     such as Subtotal do not break either.

   5.6.5
   - FIX (mobile): Apply coupon and Update cart were squeezed into a narrow box
     and printed one letter per line. Every button is auto width and nowrap now,
     and the coupon field sits on one row with its button.
   - FIX (mobile): the responsive cart table printed its column label as a
     floated overlay, so labels such as SUBTOTAL and SHIPMENT sat on top of the
     values. Each cell is a flex row now: label left, value right.
   - FIX (mobile): the Back link was glued to the top of the screen under the
     phone notch. It has proper spacing above it now.

   5.6.6
   - FIX (mobile): the shipping row of Cart totals was dragged outside the card
     and the page could be scrolled sideways. Cause: nowrap had been applied to
     the whole totals cell, and that cell holds three lines of text. Only the
     amount is nowrap now, and the shipping row is a full width block.

   5.6.7
   - NEW: the category label above a book title is a clickable trail, for
     example Books > Coloring Devotionals. Every level opens its own listing.
   - NEW: a category listing now opens with its sub categories on top, then
     the books below them.
   - NEW: product, category and tag listings are fully styled in the PPH design
     with a card grid, and every cover is shown whole and never cropped.

   5.6.8
   - FIX: the store has no parent categories, everything is flat, so WooCommerce
     had no sub categories to show. A Browse by category row is printed at the
     top of every listing now. It shows the children when they exist, and the
     neighbouring categories when the list is flat, so a click on Books always
     leads somewhere useful.
   - FIX: the listing kept its plain look because the theme styles ul.products
     with stronger selectors. The card grid is forced now, and the same rules
     are also printed inline in the page head so a cache cannot undo them.

   5.6.9
   - CHANGE: the Books label above a book title now opens the store home page,
     set in PPH_BOOKS_URL. A deeper sub category, when one exists, still opens
     its own listing.
   - CHANGE: the plain WooCommerce Shop archive is no longer shown to a reader.
     It is redirected to the store home page with a permanent 301.

   5.7.0
   - FIX: an order that holds nothing but ebooks no longer asks for a postal
     address. Company, street, city, state and postcode are removed, and the
     whole shipping block is gone. Only the name, the email address and the
     phone number remain, because the file is delivered by email.
   - NOTE: the country field is kept in the form but hidden, since payment
     gateways and tax rules still read it. A cart that holds even one printed
     book keeps the full address form as before.
   - NOTE: the thank you page and the order emails no longer print an address
     block for a fully digital order.

   5.7.1
   - FIX: six labels on the Quick Add Book screen were still written in Hindi.
     Every word in the plugin is English now.

   5.8.0
   - NEW: a Focus keyword field sits on the product screen. Whatever is typed
     there always wins and is written to Rank Math as the first keyword. Left
     blank, the plugin still writes one on its own as before.
   - NEW: the image viewer is a real viewer. A click on any side image, or on
     the cover, opens it large with a left and a right arrow, a counter, arrow
     key support, and swipe with dots on a phone.
   - CHANGE: the back cover is kept out of Sample Pages. Any image whose name
     or alt text says back cover is skipped there.
   - CHANGE: the title and the subtitle are one heading now, in the same face,
     the same weight and the same colour.
   - CHANGE: the ebook is sold here and only here. Its card carries the price
     and the buy button, and no outside store is offered against it. Paperback
     and hardcover carry no price at all and lead to their retailer links.

   5.15.1
   - NEW: Free Sample is a simple format-style card beside eBook / Paperback /
     Hardcover. Choosing it reveals one orange Download Free Sample button.
   - NEW: Quick Add JSON accepts free_sample {on,title,url,pages,size}.
   - The sample is downloaded through a same-site endpoint with attachment
     headers, so the PDF downloads instead of opening inline.
   ========================================================================= */

define('PPH_VER', '5.15.1');

/* Set to true if you ever want the Astra header/footer back on the store. */
if (!defined('PPH_SHOW_ASTRA_CHROME')) { define('PPH_SHOW_ASTRA_CHROME', false); }

/* Always-on SEO layer (5.4). This always loads, because products created by
   CSV import or REST also need their SEO filled in. */
$pph_seo_file = plugin_dir_path(__FILE__) . 'seo/pph-seo.php';
if (file_exists($pph_seo_file)) { require_once $pph_seo_file; }

/* Files Included + FAQ + freshness (5.5). Loaded in admin and on the front end. */
$pph_ff_file = plugin_dir_path(__FILE__) . 'extras/pph-files-faq.php';
if (file_exists($pph_ff_file)) { require_once $pph_ff_file; }

/* Cart / Checkout / Order-received design and hooks (5.5, updated in 5.6). */
$pph_shop_file = plugin_dir_path(__FILE__) . 'shop/pph-shop.php';
if (file_exists($pph_shop_file)) { require_once $pph_shop_file; }

/* Country based pricing (5.10.0): rupees in India, dollars outside India. */
$pph_pricing_file = plugin_dir_path(__FILE__) . 'pricing/pph-pricing.php';
if (file_exists($pph_pricing_file)) { require_once $pph_pricing_file; }

/* Admin-only: Quick Add Book importer (5.3). Never loaded on the front end. */
if (is_admin()) {
    $pph_qa_file = plugin_dir_path(__FILE__) . 'admin/quick-add.php';
    if (file_exists($pph_qa_file)) { require_once $pph_qa_file; }
}


/* -------------------------------------------------------------------------
   1. CONFIG
   ------------------------------------------------------------------------- */

function pph_formats() {
    return array(
        'pdf' => array(
            'label' => 'PDF',
            'sub'   => 'Digital Download',
            'type'  => 'digital',
        ),
        'ebook' => array(
            'label' => 'eBook',
            'sub'   => 'Read on any device',
            'type'  => 'digital',
        ),
        'paperback' => array(
            'label' => 'Paperback',
            'sub'   => 'Premium Quality',
            'type'  => 'print',
        ),
        'hardcover' => array(
            'label' => 'Hardcover',
            'sub'   => 'Premium Quality',
            'type'  => 'print',
        ),
    );
}

/* Retailers shown for PRINT formats (paperback / hardcover) */
function pph_print_retailers() {
    return array(
        'amazon_in' => 'Amazon India',
        'amazon_us' => 'Amazon US',
        'ingram'    => 'IngramSpark',
        'flipkart'  => 'Flipkart',
        'bn'        => 'Barnes & Noble',
    );
}

/* Retailers shown for DIGITAL formats (pdf / ebook) */
function pph_digital_retailers() {
    return array(
        'kindle' => 'Amazon Kindle',
        'apple'  => 'Apple Books',
        'google' => 'Google Play Books',
        'kobo'   => 'Kobo',
        'tpt'    => 'Teachers Pay Teachers',
    );
}

/* Book Details fields. The first 13 are the ones from plugin v3.9. */
function pph_detail_fields() {
    /* 5.8.1: Publisher leads the list, because that is the line a reader looks
       for first. The order here is the order shown on the product page. */
    return array(
        /* ---- shown for every edition ---- */
        'publisher'            => 'Publisher',
        'pub_date'             => 'Publication Date',
        'language'             => 'Language',
        'print_length'         => 'Print Length',
        'series'               => 'Series',
        'volume'               => 'Volume',
        'bible_translation'    => 'Bible Translation',
        'age_range'            => 'Age Range',
        'reading_age'          => 'Reading Age',
        'accessibility'        => 'Accessibility',
        /* ---- the ebook we sell on this website ---- */
        'file_size'            => 'File Size',
        'asin'                 => 'ASIN',
        'screen_reader'        => 'Screen Reader',
        'enhanced_typesetting' => 'Enhanced Typesetting',
        'xray'                 => 'X-Ray',
        'word_wise'            => 'Word Wise',
        'page_flip'            => 'Page Flip',
        /* ---- the printed editions bought at a retailer ---- */
        'isbn13'               => 'ISBN-13',
        'isbn10'               => 'ISBN-10',
        'dimensions'           => 'Dimensions',
    );
}

/**
 * Which block of the Details tab each field belongs to.
 * common  = true for every edition
 * digital = the ebook sold on this website
 * print   = the paperback and the hardcover bought at a retailer
 */
function pph_detail_groups() {
    return array(
        'publisher'            => 'common',
        'pub_date'             => 'common',
        'language'             => 'common',
        'print_length'         => 'common',
        'series'               => 'common',
        'volume'               => 'common',
        'bible_translation'    => 'common',
        'age_range'            => 'common',
        'reading_age'          => 'common',
        'accessibility'        => 'common',

        'file_size'            => 'digital',
        'asin'                 => 'digital',
        'screen_reader'        => 'digital',
        'enhanced_typesetting' => 'digital',
        'xray'                 => 'digital',
        'word_wise'            => 'digital',
        'page_flip'            => 'digital',

        'isbn13'               => 'print',
        'isbn10'               => 'print',
        'dimensions'           => 'print',
    );
}


/* -------------------------------------------------------------------------
   2. META READ HELPER
   Reads _pph_<key> first, then falls back to older key spellings so that
   data entered under plugin 3.9 is still picked up.
   ------------------------------------------------------------------------- */

function pph_get($post_id, $key, $default = '') {
    $candidates = array('_pph_' . $key, 'pph_' . $key, '_' . $key, $key);
    foreach ($candidates as $k) {
        $v = get_post_meta($post_id, $k, true);
        if ($v !== '' && $v !== null && $v !== false) {
            return $v;
        }
    }
    return $default;
}

function pph_is_on($post_id, $key) {
    $v = pph_get($post_id, $key);

    if (is_bool($v))  { return $v; }
    if (is_array($v)) { return false; }

    $v = strtolower(trim((string) $v));

    /* 5.9.1: the meta box saves "yes", while Quick Add Book used to save "1".
       Both mean the same thing, so both are accepted now. Without this, a book
       imported from JSON showed no format cards at all. */
    return in_array($v, array('yes', '1', 'true', 'on'), true);
}


/* -------------------------------------------------------------------------
   3. ADMIN META BOX
   ------------------------------------------------------------------------- */

add_action('add_meta_boxes', function () {
    add_meta_box(
        'pph_book_box',
        'PPH — Book Formats, Retailers & Details',
        'pph_render_meta_box',
        'product',
        'normal',
        'high'
    );
});

function pph_field_text($post_id, $key, $label, $placeholder = '', $wide = false) {
    $val = pph_get($post_id, $key);
    $cls = $wide ? 'pph-adm-f pph-adm-wide' : 'pph-adm-f';
    echo '<p class="' . esc_attr($cls) . '">';
    echo '<label for="pph_' . esc_attr($key) . '"><strong>' . esc_html($label) . '</strong></label>';
    echo '<input type="text" id="pph_' . esc_attr($key) . '" name="pph[' . esc_attr($key) . ']"';
    echo ' value="' . esc_attr($val) . '" placeholder="' . esc_attr($placeholder) . '" />';
    echo '</p>';
}

function pph_field_check($post_id, $key, $label) {
    $on = pph_is_on($post_id, $key);
    echo '<label class="pph-adm-chk"><input type="checkbox" name="pph[' . esc_attr($key) . ']" value="yes" ';
    checked($on, true);
    echo ' /> <strong>' . esc_html($label) . '</strong></label>';
}

function pph_render_meta_box($post) {
    wp_nonce_field('pph_save_' . $post->ID, 'pph_nonce');
    $id = $post->ID;
    ?>
    <style>
      .pph-adm{font-size:13px}
      .pph-adm h3{margin:22px 0 6px;padding-bottom:6px;border-bottom:2px solid #e8622c;font-size:14px;text-transform:uppercase;letter-spacing:.5px}
      .pph-adm .pph-adm-note{background:#fff8f4;border-left:3px solid #e8622c;padding:9px 12px;margin:0 0 14px;color:#5c534c}
      .pph-adm-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:0 22px}
      .pph-adm-f{display:flex;flex-direction:column;margin:0 0 12px}
      .pph-adm-f label{margin-bottom:3px;color:#23282d}
      .pph-adm-f input{width:100%}
      .pph-adm-wide{grid-column:1/-1}
      .pph-adm-fmt{border:1px solid #dcd7ce;border-radius:6px;padding:12px 16px;margin:0 0 12px;background:#fcfbf9}
      .pph-adm-fmt.pph-print{background:#f6f9fc}
      .pph-adm-chk{display:inline-block;margin:0 0 10px}
      .pph-adm-tag{float:right;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;padding:2px 8px;border-radius:10px}
      .pph-adm-tag.d{background:#e8f5e9;color:#256029}
      .pph-adm-tag.p{background:#e3f2fd;color:#0d47a1}
    </style>
    <div class="pph-adm">

      <div class="pph-adm-note">
        <strong>Rule:</strong> leave any field blank and that button simply does not appear on the page.
        Untick a format checkbox and the whole card disappears.
        So for each book fill in only what is actually available — the code never needs to be touched.
      </div>

      <div class="pph-adm-note">
        <strong>Paperback / Hardcover deliberately have no price field.</strong>
        No amount for those formats is shown anywhere on the page — the customer goes to
        Amazon or IngramSpark and sees the live price there. That removes any risk of
        showing an outdated price.
      </div>

      <h3>Header Badge</h3>
      <div class="pph-adm-grid">
        <?php pph_field_text($id, 'badge', 'Badge (beside the title)', 'e.g. Ages 4–12'); ?>
        <?php pph_field_text($id, 'subtitle', 'Subtitle (below the title)', 'e.g. From Creation to Our Eternal Destiny'); ?>
        <?php pph_field_text($id, 'author', 'Author', 'e.g. J. D. Harrison'); ?>
      </div>

      <h3>SEO Focus Keyword</h3>
      <div class="pph-adm-note">
        The one phrase a real buyer would type into Google to find this book.
        Leave it blank and the plugin writes one for you from the title. Type
        something here and it always wins, and it is placed first in Rank Math.
      </div>
      <div class="pph-adm-grid">
        <div class="pph-adm-wide">
          <?php pph_field_text($id, 'focus_keyword', 'Focus keyword', 'e.g. psalms word search puzzle book for adults'); ?>
        </div>
      </div>

      <h3>Free Sample Download</h3>
      <div class="pph-adm-note">
        <strong>Optional free sample:</strong> enable this only when this book has a sample PDF.
        Upload the sample PDF to the WordPress Media Library, paste its full URL below, and the
        product page will show a <strong>Download Free Sample</strong> button. The button uses a
        protected same-site download endpoint that sends the PDF as an attachment instead of
        opening it in the browser.
      </div>
      <div class="pph-adm-grid">
        <?php pph_field_check($id, 'free_sample_on', 'Free sample available'); ?>
        <?php pph_field_text($id, 'free_sample_title', 'Sample title', 'Free Sample PDF'); ?>
        <?php pph_field_text($id, 'free_sample_url', 'Sample PDF URL', 'https://store.praisepublishinghouse.com/wp-content/uploads/...pdf', true); ?>
        <?php pph_field_text($id, 'free_sample_pages', 'Sample PDF pages', 'e.g. 8 pages'); ?>
        <?php pph_field_text($id, 'free_sample_size', 'Sample PDF size', 'e.g. 2.4 MB'); ?>
      </div>

      <h3>Format Cards</h3>

      <?php foreach (pph_formats() as $key => $f) :
          $is_digital = ($f['type'] === 'digital'); ?>
        <div class="pph-adm-fmt <?php echo $is_digital ? 'pph-digital' : 'pph-print'; ?>">
          <span class="pph-adm-tag <?php echo $is_digital ? 'd' : 'p'; ?>">
            <?php echo $is_digital ? 'Sold here — download' : 'At retailer — no price shown'; ?>
          </span>
          <?php pph_field_check($id, 'fmt_' . $key . '_on', $f['label'] . ' available'); ?>

          <div class="pph-adm-grid">
            <?php
            pph_field_text($id, 'fmt_' . $key . '_sub', 'Card subtitle', $f['sub']);

            if ($is_digital) {
                pph_field_text(
                    $id,
                    'fmt_' . $key . '_attr',
                    'WooCommerce variation name',
                    'exact variation value, e.g. ' . $f['label']
                );
                pph_field_text($id, 'fmt_' . $key . '_price', 'Price (blank = taken from Woo)', 'auto');
            } else {
                /* Deliberately no price field here. A print amount is only ever
                   shown on the Amazon / IngramSpark page. */
                foreach (pph_print_retailers() as $rkey => $rlabel) {
                    pph_field_text($id, 'fmt_' . $key . '_' . $rkey, $rlabel . ' link', 'https://');
                }
                pph_field_text($id, 'fmt_' . $key . '_other_label', 'Extra button name', 'e.g. Lulu');
                pph_field_text($id, 'fmt_' . $key . '_other_url', 'Extra button link', 'https://');
            }
            ?>
          </div>

          <div class="pph-adm-note" style="margin:12px 0 10px">
            <strong>Details for the <?php echo esc_html($f['label']); ?> edition only.</strong>
            Each edition has its own ISBN, so fill them in here. Leave a box blank
            and the shared value from <em>Book Details</em> further down is used instead.
          </div>
          <div class="pph-adm-grid">
            <?php
            $pph_dl = pph_detail_fields();
            foreach (pph_format_detail_keys($f['type']) as $dk) {
                if (!isset($pph_dl[$dk])) { continue; }
                pph_field_text($id, 'fmt_' . $key . '_d_' . $dk, $pph_dl[$dk]);
            }
            ?>
          </div>
        </div>
      <?php endforeach; ?>

      <h3>Digital Retailer Buttons (“Buy now from your favorite retailer”)</h3>
      <div class="pph-adm-note">
        This row appears with PDF / eBook — for customers who prefer reading on Kindle or Apple Books.
      </div>
      <div class="pph-adm-grid">
        <?php foreach (pph_digital_retailers() as $rkey => $rlabel) {
            pph_field_text($id, 'rt_' . $rkey, $rlabel, 'https://');
        } ?>
      </div>

      <h3>Book Details (shared by every edition)</h3>
      <div class="pph-adm-note">
        These are the facts that are true for the whole book, such as the publisher
        and the language. Anything that differs between editions, such as an ISBN,
        belongs in that format card above.
      </div>
      <div class="pph-adm-grid">
        <?php foreach (pph_detail_fields() as $key => $label) {
            pph_field_text($id, 'd_' . $key, $label);
        } ?>
      </div>

      <h3>Sample Pages</h3>
      <div class="pph-adm-note">
        There is no field for sample pages — the <strong>Product gallery</strong> is used instead.
        Add images to <em>“Product gallery”</em> in the right sidebar and they appear
        automatically in the thumbnail rail and in the “Sample Pages” tab.
      </div>

    </div>
    <?php
}


/* -------------------------------------------------------------------------
   4. SAVE
   ------------------------------------------------------------------------- */

add_action('save_post_product', function ($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) { return; }
    if (!isset($_POST['pph_nonce'])) { return; }
    if (!wp_verify_nonce($_POST['pph_nonce'], 'pph_save_' . $post_id)) { return; }
    if (!current_user_can('edit_post', $post_id)) { return; }

    $in = isset($_POST['pph']) && is_array($_POST['pph']) ? $_POST['pph'] : array();

    /* Build the full list of keys we own, so unchecking really clears it. */
    $keys = array('badge', 'subtitle', 'author', 'focus_keyword', 'free_sample_on', 'free_sample_title', 'free_sample_url', 'free_sample_pages', 'free_sample_size');

    foreach (pph_formats() as $key => $f) {
        $keys[] = 'fmt_' . $key . '_on';
        $keys[] = 'fmt_' . $key . '_sub';
        foreach (pph_format_detail_keys($f['type']) as $dk) {
            $keys[] = 'fmt_' . $key . '_d_' . $dk;
        }
        if ($f['type'] === 'digital') {
            $keys[] = 'fmt_' . $key . '_price';
            $keys[] = 'fmt_' . $key . '_attr';
        } else {
            foreach (pph_print_retailers() as $rkey => $rlabel) {
                $keys[] = 'fmt_' . $key . '_' . $rkey;
            }
            $keys[] = 'fmt_' . $key . '_other_label';
            $keys[] = 'fmt_' . $key . '_other_url';
        }
    }
    foreach (pph_digital_retailers() as $rkey => $rlabel) { $keys[] = 'rt_' . $rkey; }
    foreach (pph_detail_fields() as $key => $label)       { $keys[] = 'd_' . $key; }

    foreach ($keys as $key) {
        $raw = isset($in[$key]) ? $in[$key] : '';

        /* URL-ish fields get URL sanitising, everything else plain text. */
        $is_url = (strpos($key, '_url') !== false) || (strpos($key, 'rt_') === 0) || ($key === 'free_sample_url');
        if (!$is_url) {
            foreach (pph_print_retailers() as $rkey => $rlabel) {
                if (substr($key, -strlen('_' . $rkey)) === '_' . $rkey) { $is_url = true; break; }
            }
        }

        if ($is_url) {
            $val = $raw === '' ? '' : esc_url_raw(trim($raw));
        } else {
            $val = sanitize_text_field($raw);
        }

        if ($val === '') {
            delete_post_meta($post_id, '_pph_' . $key);
        } else {
            update_post_meta($post_id, '_pph_' . $key, $val);
        }
    }
});


/* -------------------------------------------------------------------------
   5. FRONT-END DATA BUILDERS (used by the template)
   ------------------------------------------------------------------------- */

/**
 * Returns only the formats that are switched on and actually usable.
 */
function pph_active_formats($post_id) {
    $out = array();
    foreach (pph_formats() as $key => $f) {
        if (!pph_is_on($post_id, 'fmt_' . $key . '_on')) { continue; }

        $row = array(
            'key'   => $key,
            'type'  => $f['type'],
            'label' => $f['label'],
            'sub'   => pph_get($post_id, 'fmt_' . $key . '_sub', $f['sub']),
            /* Never an amount for print formats. Digital price comes from Woo. */
            'price' => ($f['type'] === 'digital')
                ? pph_get($post_id, 'fmt_' . $key . '_price')
                : '',
            'attr'  => pph_get($post_id, 'fmt_' . $key . '_attr'),
            'links' => array(),
        );

        if ($f['type'] === 'print') {
            foreach (pph_print_retailers() as $rkey => $rlabel) {
                $url = pph_clean_retailer_url(pph_get($post_id, 'fmt_' . $key . '_' . $rkey));
                if ($url !== '') {
                    $row['links'][] = array('key' => $rkey, 'label' => $rlabel, 'url' => $url);
                }
            }
            $ol = pph_get($post_id, 'fmt_' . $key . '_other_label');
            $ou = pph_get($post_id, 'fmt_' . $key . '_other_url');
            if ($ol !== '' && $ou !== '') {
                $row['links'][] = array('key' => 'other', 'label' => $ol, 'url' => $ou);
            }

            /* A print format with no links anywhere is dead weight - skip it. */
            if (empty($row['links'])) { continue; }
        }

        $out[$key] = $row;
    }

    /* 5.9.0: a book that is a virtual or downloadable WooCommerce product is
       sold here as an ebook, even if nobody remembered to tick the eBook box.
       Without this the page opened with no format selected at all. */
    $has_digital = false;
    foreach ($out as $row) {
        if (isset($row['type']) && $row['type'] === 'digital') { $has_digital = true; break; }
    }
    if (!$has_digital && function_exists('wc_get_product')) {
        $p = wc_get_product($post_id);
        if ($p && is_a($p, 'WC_Product') && ($p->is_virtual() || $p->is_downloadable())) {
            $all = pph_formats();
            $out = array('ebook' => array(
                'key'   => 'ebook',
                'type'  => 'digital',
                'label' => $all['ebook']['label'],
                'sub'   => pph_get($post_id, 'fmt_ebook_sub', $all['ebook']['sub']),
                'price' => pph_get($post_id, 'fmt_ebook_price'),
                'attr'  => pph_get($post_id, 'fmt_ebook_attr'),
                'links' => array(),
            )) + $out;
        }
    }

    return $out;
}

/**
 * 5.9.0: which detail fields belong to one edition rather than to the book.
 * These are asked for once per format, so the paperback can carry its own
 * ISBN while the hardcover carries a different one.
 */
function pph_format_detail_keys($type) {
    if ($type === 'digital') {
        return array(
            'file_size', 'isbn13', 'asin', 'print_length', 'pub_date',
            'screen_reader', 'enhanced_typesetting', 'xray', 'word_wise', 'page_flip',
        );
    }
    return array('isbn13', 'isbn10', 'dimensions', 'print_length', 'pub_date');
}

/**
 * 5.9.0: the Details tab, arranged the way the page is used.
 *
 * common  = facts that are true for the whole book
 * formats = one block per active format, shown when that format card is chosen
 *
 * A per-format value is read from fmt_<format>_d_<field> and falls back to the
 * shared d_<field>, so an old book with only shared values still shows them.
 *
 * @return array array('common' => array(label => value), 'formats' => array(key => array('label','type','rows')))
 */
function pph_details_by_format($post_id, $product = null) {
    $labels  = pph_detail_fields();
    $formats = pph_active_formats($post_id);
    $rows    = pph_active_details($post_id, $product);

    /* Any field that is asked per format is not repeated in the shared block. */
    $owned = array();
    foreach ($formats as $f) {
        foreach (pph_format_detail_keys($f['type']) as $k) { $owned[$k] = true; }
    }

    $common = array();
    foreach ($labels as $key => $label) {
        if (!isset($rows[$label])) { continue; }
        if (!isset($owned[$key])) { $common[$label] = $rows[$label]; }
        unset($rows[$label]);
    }
    /* Whatever is left came from a WooCommerce attribute. */
    foreach ($rows as $label => $val) { $common[$label] = $val; }

    $out = array('common' => $common, 'formats' => array());

    foreach ($formats as $fkey => $f) {
        $frows = array();
        foreach (pph_format_detail_keys($f['type']) as $k) {
            if (!isset($labels[$k])) { continue; }
            $v = pph_get($post_id, 'fmt_' . $fkey . '_d_' . $k);
            if ($v === '') { $v = pph_get($post_id, 'd_' . $k); }
            if ($v === '') { continue; }
            $frows[$labels[$k]] = $v;
        }

        if ($f['type'] === 'digital') {
            $auto = array(
                'Sold by'  => 'Praise Publishing House, on this website',
                'Delivery' => 'Instant download, and a copy stays in your account',
                'Read on'  => 'Phone, tablet, laptop or desktop. No app is needed.',
            );
        } else {
            $auto = array(
                'Where to buy' => 'At the retailer buttons shown above',
                'Shipping'     => 'Printed and posted by the retailer you choose',
            );
        }
        foreach ($auto as $k => $v) {
            if (!isset($frows[$k])) { $frows[$k] = $v; }
        }

        $out['formats'][$fkey] = array(
            'label' => $f['label'],
            'type'  => $f['type'],
            'rows'  => $frows,
        );
    }

    return $out;
}

function pph_active_digital_retailers($post_id) {
    $out = array();
    foreach (pph_digital_retailers() as $rkey => $rlabel) {
        $url = pph_get($post_id, 'rt_' . $rkey);
        if ($url !== '') {
            $out[] = array('key' => $rkey, 'label' => $rlabel, 'url' => $url);
        }
    }
    return $out;
}

/**
 * Book details: plugin fields first, then WooCommerce attributes as a
 * fallback so older books that used attributes still show something.
 */
function pph_active_details($post_id, $product = null) {
    $rows = array();
    foreach (pph_detail_fields() as $key => $label) {
        $v = pph_get($post_id, 'd_' . $key);
        if ($v !== '') { $rows[$label] = $v; }
    }

    if ($product && is_a($product, 'WC_Product')) {
        foreach ($product->get_attributes() as $attr) {
            if (!is_a($attr, 'WC_Product_Attribute')) { continue; }
            if ($attr->get_variation()) { continue; }
            $label = wc_attribute_label($attr->get_name());
            if (isset($rows[$label])) { continue; }
            if ($attr->is_taxonomy()) {
                $vals = wc_get_product_terms($post_id, $attr->get_name(), array('fields' => 'names'));
            } else {
                $vals = $attr->get_options();
            }
            $val = is_array($vals) ? implode(', ', $vals) : (string) $vals;
            if ($val === '') { continue; }
            if (stripos($label, 'author') !== false) { continue; }
            $rows[$label] = $val;
        }
    }
    return $rows;
}

/**
 * 5.8.1: the Details tab, split into blocks.
 *
 * The ebook block always appears for a book that has a digital format or that
 * is a virtual WooCommerce product, because that edition is sold right here.
 * Three plain facts are added when they were not typed in, so the ebook never
 * looks emptier than the printed edition.
 *
 * @return array List of array('title' => string, 'rows' => array(label => value))
 */
function pph_active_details_grouped($post_id, $product = null) {
    $rows   = pph_active_details($post_id, $product);
    $groups = pph_detail_groups();

    $common  = array();
    $digital = array();
    $print   = array();

    foreach (pph_detail_fields() as $key => $label) {
        if (!isset($rows[$label])) { continue; }
        $g = isset($groups[$key]) ? $groups[$key] : 'common';
        if ($g === 'digital') {
            $digital[$label] = $rows[$label];
        } elseif ($g === 'print') {
            $print[$label] = $rows[$label];
        } else {
            $common[$label] = $rows[$label];
        }
        unset($rows[$label]);
    }

    /* Whatever is left came from a WooCommerce attribute. */
    foreach ($rows as $label => $val) {
        $common[$label] = $val;
    }

    $has_digital = false;
    $formats = function_exists('pph_active_formats') ? pph_active_formats($post_id) : array();
    foreach ($formats as $f) {
        if (isset($f['type']) && $f['type'] === 'digital') { $has_digital = true; }
    }
    if (!$has_digital && $product && is_a($product, 'WC_Product') && $product->is_virtual()) {
        $has_digital = true;
    }

    if ($has_digital) {
        $auto = array(
            'Sold by'  => 'Praise Publishing House, on this website',
            'Delivery' => 'Instant download, and a copy stays in your account',
            'Read on'  => 'Phone, tablet, laptop or desktop. No app is needed.',
        );
        foreach ($auto as $k => $v) {
            if (!isset($digital[$k])) { $digital[$k] = $v; }
        }
    }

    $out = array();
    if (!empty($common)) {
        $out[] = array('title' => 'Book information', 'rows' => $common);
    }
    if ($has_digital && !empty($digital)) {
        $out[] = array('title' => 'Ebook, sold on this website', 'rows' => $digital);
    }
    if (!empty($print)) {
        $out[] = array('title' => 'Paperback and hardcover', 'rows' => $print);
    }
    return $out;
}

function pph_svg($name) {
    $s = array(
        'pdf'       => '<path d="M7 3h7l5 5v13a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1z"/><path d="M14 3v5h5"/><text x="12" y="18" font-size="6" text-anchor="middle" fill="currentColor" stroke="none">PDF</text>',
        'ebook'     => '<rect x="5" y="3" width="14" height="18" rx="2"/><line x1="8" y1="7" x2="16" y2="7"/><line x1="8" y1="11" x2="16" y2="11"/><line x1="8" y1="15" x2="13" y2="15"/>',
        'paperback' => '<path d="M4 5a2 2 0 0 1 2-2h12v18H6a2 2 0 0 1-2-2z"/><line x1="8" y1="3" x2="8" y2="21"/>',
        'hardcover' => '<path d="M4 4a2 2 0 0 1 2-2h13v20H6a2 2 0 0 1-2-2z"/><line x1="9" y1="2" x2="9" y2="22"/><line x1="12" y1="7" x2="16" y2="7"/>',
        'cart'      => '<circle cx="9" cy="20" r="1.6"/><circle cx="18" cy="20" r="1.6"/><path d="M3 4h2l2.6 11h11L21 8H6"/>',
        'ext'       => '<path d="M14 4h6v6"/><path d="M20 4l-9 9"/><path d="M18 14v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h5"/>',
    );
    $p = isset($s[$name]) ? $s[$name] : $s['ebook'];
    return '<svg class="pph-ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" '
         . 'stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
         . $p . '</svg>';
}


/* -------------------------------------------------------------------------
   5b. RETAILER STRIP + BRAND LOGOS  (new in 5.2)
   ------------------------------------------------------------------------- */

/**
 * Every retailer link on the book, merged into ONE list for the strip that
 * sits under the format cards. Each row remembers which formats it belongs
 * to, so selecting a format can highlight the right buttons.
 *
 * Same URL used for both Paperback and Hardcover is shown only once.
 */
function pph_all_retailers($post_id) {
    $out = array();

    /* ---- print retailers, read per format ---- */
    foreach (pph_formats() as $key => $f) {
        if ($f['type'] !== 'print') { continue; }
        if (!pph_is_on($post_id, 'fmt_' . $key . '_on')) { continue; }

        foreach (pph_print_retailers() as $rkey => $rlabel) {
            $url = pph_clean_retailer_url(pph_get($post_id, 'fmt_' . $key . '_' . $rkey));
            if ($url === '') { continue; }
            $id = $rkey . '|' . $url;
            if (isset($out[$id])) {
                $out[$id]['fmts'][] = $key;
            } else {
                $out[$id] = array(
                    'key'   => $rkey,
                    'label' => $rlabel,
                    'url'   => $url,
                    'fmts'  => array($key),
                );
            }
        }

        $ol = pph_get($post_id, 'fmt_' . $key . '_other_label');
        $ou = pph_clean_retailer_url(pph_get($post_id, 'fmt_' . $key . '_other_url'));
        if ($ol !== '' && $ou !== '') {
            $id = 'other|' . $ou;
            if (isset($out[$id])) {
                $out[$id]['fmts'][] = $key;
            } else {
                $out[$id] = array(
                    'key'   => 'other',
                    'label' => $ol,
                    'url'   => $ou,
                    'fmts'  => array($key),
                );
            }
        }
    }

    /* ---- digital retailers belong to every active digital format ---- */
    $dig = array();
    foreach (pph_formats() as $key => $f) {
        if ($f['type'] === 'digital' && pph_is_on($post_id, 'fmt_' . $key . '_on')) {
            $dig[] = $key;
        }
    }
    if ($dig) {
        foreach (pph_digital_retailers() as $rkey => $rlabel) {
            $url = pph_clean_retailer_url(pph_get($post_id, 'rt_' . $rkey));
            if ($url === '') { continue; }
            $out[$rkey . '|' . $url] = array(
                'key'   => $rkey,
                'label' => $rlabel,
                'url'   => $url,
                'fmts'  => $dig,
            );
        }
    }

    return array_values($out);
}

/**
 * Inline brand logos. Kept as SVG so there is nothing to upload, nothing
 * to break, and they stay sharp on every screen.
 */
function pph_logo($key) {
    $amz_arc = '<path d="M6 31 Q44 40.5 82 31" fill="none" stroke="#FF9900" '
             . 'stroke-width="3.6" stroke-linecap="round"/>'
             . '<path d="M77 29 q7.5 1.5 5 8.5" fill="none" stroke="#FF9900" '
             . 'stroke-width="3.6" stroke-linecap="round"/>';
    $ff = 'Arial,Helvetica,sans-serif';

    switch ($key) {

        case 'amazon_in':
            return '<svg class="pph-logo" viewBox="0 0 138 40" aria-hidden="true">'
                 . '<text x="2" y="26" font-family="' . $ff . '" font-size="24" '
                 . 'font-weight="700" fill="#141414">amazon</text>'
                 . '<text x="100" y="26" font-family="' . $ff . '" font-size="17" '
                 . 'fill="#141414">.in</text>' . $amz_arc . '</svg>';

        case 'amazon_us':
            return '<svg class="pph-logo" viewBox="0 0 152 40" aria-hidden="true">'
                 . '<text x="2" y="26" font-family="' . $ff . '" font-size="24" '
                 . 'font-weight="700" fill="#141414">amazon</text>'
                 . '<text x="100" y="26" font-family="' . $ff . '" font-size="17" '
                 . 'fill="#141414">.com</text>' . $amz_arc . '</svg>';

        case 'kindle':
            return '<svg class="pph-logo" viewBox="0 0 96 40" aria-hidden="true">'
                 . '<text x="2" y="26" font-family="' . $ff . '" font-size="24" '
                 . 'font-weight="700" fill="#141414">kindle</text>'
                 . '<path d="M6 31 Q40 40.5 74 31" fill="none" stroke="#FF9900" '
                 . 'stroke-width="3.6" stroke-linecap="round"/>'
                 . '<path d="M69 29 q7.5 1.5 5 8.5" fill="none" stroke="#FF9900" '
                 . 'stroke-width="3.6" stroke-linecap="round"/></svg>';

        case 'ingram':
            return '<svg class="pph-logo" viewBox="0 0 162 40" aria-hidden="true">'
                 . '<path d="M16 5l3.1 9.4L28.5 18l-9.4 3.6L16 31l-3.1-9.4L3.5 18l9.4-3.6z" '
                 . 'fill="#E8622C"/>'
                 . '<text x="34" y="26" font-family="' . $ff . '" font-size="20" '
                 . 'font-weight="700" fill="#16223F">Ingram</text>'
                 . '<text x="104" y="26" font-family="' . $ff . '" font-size="20" '
                 . 'font-weight="700" fill="#E8622C">Spark</text></svg>';

        case 'apple':
            return '<svg class="pph-logo" viewBox="0 0 40 40" aria-hidden="true">'
                 . '<rect x="2" y="2" width="36" height="36" rx="9" fill="#F5623C"/>'
                 . '<path d="M20 13c-2.1-1.7-5.2-2.2-7.8-1.7v16.6c2.6-.5 5.7 0 7.8 1.7 '
                 . '2.1-1.7 5.2-2.2 7.8-1.7V11.3c-2.6-.5-5.7 0-7.8 1.7z" fill="#ffffff"/>'
                 . '<path d="M20 13v16.6" stroke="#F5623C" stroke-width="1.5"/></svg>';

        case 'google':
            return '<svg class="pph-logo" viewBox="0 0 40 40" aria-hidden="true">'
                 . '<path d="M8 5L21 20 8 35z" fill="#00A0FF"/>'
                 . '<path d="M8 5l17.5 10L21 20z" fill="#00C853"/>'
                 . '<path d="M8 35l17.5-10L21 20z" fill="#FF3A44"/>'
                 . '<path d="M25.5 15L32 20l-6.5 5L21 20z" fill="#FFCE00"/></svg>';

        case 'kobo':
            return '<svg class="pph-logo" viewBox="0 0 84 40" aria-hidden="true">'
                 . '<text x="2" y="27" font-family="' . $ff . '" font-size="24" '
                 . 'font-weight="700" fill="#141414">kob</text>'
                 . '<circle cx="60" cy="19" r="9" fill="#D2202F"/>'
                 . '<circle cx="60" cy="19" r="3.4" fill="#ffffff"/></svg>';

        case 'tpt':
            return '<svg class="pph-logo" viewBox="0 0 40 40" aria-hidden="true">'
                 . '<rect x="2" y="2" width="36" height="36" rx="9" fill="#0E8A6A"/>'
                 . '<text x="20" y="26" font-family="' . $ff . '" font-size="15" '
                 . 'font-weight="700" fill="#ffffff" text-anchor="middle">TPT</text></svg>';

        case 'flipkart':
            return '<svg class="pph-logo" viewBox="0 0 40 40" aria-hidden="true">'
                 . '<rect x="2" y="2" width="36" height="36" rx="9" fill="#2874F0"/>'
                 . '<text x="20" y="27" font-family="' . $ff . '" font-size="19" '
                 . 'font-weight="700" fill="#FFE11B" text-anchor="middle">F</text></svg>';

        case 'bn':
            return '<svg class="pph-logo" viewBox="0 0 74 40" aria-hidden="true">'
                 . '<text x="37" y="26" font-family="' . $ff . '" font-size="22" '
                 . 'font-weight="700" fill="#00703C" text-anchor="middle">B&amp;N</text></svg>';
    }

    /* Unknown / custom retailer: neutral storefront mark. */
    return '<svg class="pph-logo" viewBox="0 0 40 40" aria-hidden="true">'
         . '<path d="M7 16l3-7h20l3 7z" fill="none" stroke="#3F1717" stroke-width="2.2" '
         . 'stroke-linejoin="round"/>'
         . '<path d="M9 16v14h22V16" fill="none" stroke="#3F1717" stroke-width="2.2" '
         . 'stroke-linejoin="round"/></svg>';
}


/* -------------------------------------------------------------------------
   5.15.0 - FREE SAMPLE PDF DOWNLOAD

   A free sample is independent of the paid PDF/eBook format. It is public,
   costs nothing, does not enter the cart, and is streamed with attachment
   headers so the browser downloads the PDF instead of opening it inline.
   ------------------------------------------------------------------------- */

function pph_free_sample_enabled($post_id) {
    return pph_is_on($post_id, 'free_sample_on') && pph_get($post_id, 'free_sample_url') !== '';
}

function pph_free_sample_download_url($post_id) {
    return add_query_arg(
        array(
            'pph_sample_download' => '1',
            'product_id'          => (int) $post_id,
        ),
        home_url('/')
    );
}

function pph_free_sample_local_path($url) {
    $url = esc_url_raw(trim($url));
    if ($url === '') { return ''; }

    $parts = wp_parse_url($url);
    $home  = wp_parse_url(home_url('/'));
    if (!$parts || !$home || empty($parts['host']) || empty($home['host'])) { return ''; }
    if (strtolower($parts['host']) !== strtolower($home['host'])) { return ''; }

    $attachment_id = attachment_url_to_postid($url);
    if ($attachment_id) {
        $path = get_attached_file($attachment_id);
        if ($path && is_readable($path)) { return $path; }
    }

    $uploads = wp_upload_dir();
    $baseurl = isset($uploads['baseurl']) ? trailingslashit($uploads['baseurl']) : '';
    $basedir = isset($uploads['basedir']) ? trailingslashit($uploads['basedir']) : '';
    if ($baseurl && $basedir && strpos($url, $baseurl) === 0) {
        $relative = ltrim(substr($url, strlen($baseurl)), '/');
        $path = wp_normalize_path($basedir . $relative);
        $root = wp_normalize_path($basedir);
        if (strpos($path, $root) === 0 && is_readable($path) && is_file($path)) {
            return $path;
        }
    }

    return '';
}

add_action('init', function () {
    if (!isset($_GET['pph_sample_download']) || (string) $_GET['pph_sample_download'] !== '1') { return; }

    $post_id = isset($_GET['product_id']) ? absint($_GET['product_id']) : 0;
    if (!$post_id || get_post_type($post_id) !== 'product' || !pph_free_sample_enabled($post_id)) {
        status_header(404);
        exit;
    }

    $url  = pph_get($post_id, 'free_sample_url');
    $path = pph_free_sample_local_path($url);

    if ($path === '' || strtolower(pathinfo($path, PATHINFO_EXTENSION)) !== 'pdf') {
        status_header(404);
        exit;
    }

    $filename = sanitize_file_name(pph_get($post_id, 'free_sample_title', get_the_title($post_id) . ' Free Sample'));
    $filename = preg_replace('/\.pdf$/i', '', $filename) . '.pdf';
    $size = filesize($path);

    while (ob_get_level()) { ob_end_clean(); }
    nocache_headers();
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    if ($size !== false) { header('Content-Length: ' . (string) $size); }
    header('X-Content-Type-Options: nosniff');
    readfile($path);
    exit;
});


/* -------------------------------------------------------------------------
   6. TEMPLATE OVERRIDE  (unchanged from 3.8)
   ------------------------------------------------------------------------- */

add_filter('template_include', function ($template) {
    if (function_exists('is_product') && is_product()) {
        $custom = plugin_dir_path(__FILE__) . 'templates/single-product.php';
        if (file_exists($custom)) {
            return $custom;
        }
    }
    return $template;
}, 99);


/* -------------------------------------------------------------------------
   7. ASSETS
   ------------------------------------------------------------------------- */

/* Astra header/footer hide - loaded on EVERY page, as requested. */
add_action('wp_enqueue_scripts', function () {
    if (PPH_SHOW_ASTRA_CHROME) { return; }

    $dir = plugin_dir_path(__FILE__);
    $url = plugin_dir_url(__FILE__);
    $f   = 'assets/pph-chrome.css';
    if (!file_exists($dir . $f)) { return; }

    wp_enqueue_style('pph-chrome', $url . $f, array(), PPH_VER . '.' . filemtime($dir . $f));
}, 5);

/* Product / cart / checkout styles + scripts */
add_action('wp_enqueue_scripts', function () {
    $is_product  = function_exists('is_product') && is_product();
    $is_cart     = function_exists('is_cart') && is_cart();
    $is_checkout = function_exists('is_checkout') && is_checkout();
    if (!$is_product && !$is_cart && !$is_checkout) { return; }

    $dir = plugin_dir_path(__FILE__);
    $url = plugin_dir_url(__FILE__);

    $cver = PPH_VER . '.' . (file_exists($dir . 'assets/pph-product.css') ? filemtime($dir . 'assets/pph-product.css') : '0');
    wp_enqueue_style('pph-product', $url . 'assets/pph-product.css', array(), $cver);

    if ($is_product) {
        $jver = PPH_VER . '.' . (file_exists($dir . 'assets/pph-product.js') ? filemtime($dir . 'assets/pph-product.js') : '0');
        wp_enqueue_script('pph-product', $url . 'assets/pph-product.js', array(), $jver, true);

        wp_enqueue_script('wc-add-to-cart');
        $pid  = get_queried_object_id();
        $prod = $pid ? wc_get_product($pid) : null;
        if ($prod && $prod->is_type('variable')) {
            wp_enqueue_script('wc-add-to-cart-variation');
        }
    }
}, 100);

/* Cart count badge fragment  (unchanged from 3.8) */
add_action('wp_enqueue_scripts', function () {
    if (class_exists('WooCommerce')) {
        wp_enqueue_script('wc-cart-fragments');
    }
}, 20);

add_filter('woocommerce_add_to_cart_fragments', function ($fragments) {
    $count = (function_exists('WC') && WC()->cart) ? WC()->cart->get_cart_contents_count() : 0;
    $style = $count > 0 ? '' : 'display:none;';
    $fragments['span.pph-cart-count'] = '<span class="pph-cart-count" style="' . $style . '">' . esc_html($count) . '</span>';
    return $fragments;
});

/* Return to shop -> Books page  (unchanged from 3.8) */
add_filter('woocommerce_return_to_shop_redirect', function ($url) {
    return 'https://store.praisepublishinghouse.com/books/';
});

/* Uncropped cart & checkout thumbnails  (unchanged from 3.8) */
add_filter('woocommerce_cart_item_thumbnail', function ($thumbnail, $cart_item, $cart_item_key) {
    $product = isset($cart_item['data']) ? $cart_item['data'] : null;
    if ($product && is_a($product, 'WC_Product')) {
        $img = $product->get_image('large', array('style' => 'width:100%;height:auto;object-fit:contain;'));
        if ($img) { return $img; }
    }
    return $thumbnail;
}, 20, 3);


/* -------------------------------------------------------------------------
   8. DIGITAL DELIVERY SAFETY NET
   Reminds you on the product screen if a downloadable file is missing,
   which is exactly what broke order #234.
   ------------------------------------------------------------------------- */

add_action('admin_notices', function () {
    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    if (!$screen || $screen->id !== 'product') { return; }

    global $post;
    if (!$post) { return; }
    $product = function_exists('wc_get_product') ? wc_get_product($post->ID) : null;
    if (!$product) { return; }

    $digital_on = pph_is_on($post->ID, 'fmt_pdf_on') || pph_is_on($post->ID, 'fmt_ebook_on');
    if (!$digital_on) { return; }

    $has_file = false;
    $kids = $product->is_type('variable') ? $product->get_children() : array($product->get_id());
    foreach ($kids as $kid) {
        $p = wc_get_product($kid);
        if ($p && $p->is_downloadable() && !empty($p->get_downloads())) { $has_file = true; break; }
    }

    if (!$has_file) {
        echo '<div class="notice notice-error"><p><strong>PPH:</strong> This book has a PDF/eBook format switched ON, '
           . 'but no <em>downloadable file</em> is attached, so the customer will not receive a download link. '
           . 'In Product data, tick <strong>Virtual</strong> + <strong>Downloadable</strong> and add the file.</p></div>';
    }
});


/* =========================================================================
   5.9.2  ->  DIAGNOSTICS BOX

   Shows exactly what is stored in the database for one book, so a missing
   format card can be traced without guessing. It appears in the sidebar of
   the product edit screen and is visible only to a user who can edit the
   product. It changes nothing on the storefront.
   ========================================================================= */

add_action('add_meta_boxes', function () {
    add_meta_box(
        'pph_diag_box',
        'PPH - Diagnostics',
        'pph_render_diag_box',
        'product',
        'side',
        'low'
    );
});

function pph_render_diag_box($post) {
    $pid = $post->ID;

    echo '<div style="font:12px/1.6 Menlo,Consolas,monospace">';
    echo '<p><strong>Plugin version:</strong> ' . esc_html(PPH_VER) . '</p>';

    $product = function_exists('wc_get_product') ? wc_get_product($pid) : null;
    if ($product && is_a($product, 'WC_Product')) {
        echo '<p><strong>Virtual:</strong> ' . ($product->is_virtual() ? 'yes' : 'NO') . '<br>';
        echo '<strong>Downloadable:</strong> ' . ($product->is_downloadable() ? 'yes' : 'NO') . '</p>';
    }

    echo '<p><strong>Format switches</strong></p><ul style="margin:0 0 10px 14px;list-style:disc">';
    foreach (pph_formats() as $key => $f) {
        $raw = get_post_meta($pid, '_pph_fmt_' . $key . '_on', true);
        $shown = ($raw === '' || $raw === null) ? '(empty)' : $raw;
        $read  = pph_is_on($pid, 'fmt_' . $key . '_on') ? 'ON' : 'off';
        echo '<li>' . esc_html($f['label']) . ': stored ' . esc_html($shown)
           . ' -> read as <strong>' . esc_html($read) . '</strong></li>';
    }
    echo '</ul>';

    $active = pph_active_formats($pid);
    echo '<p><strong>Cards on the page:</strong> '
       . ($active ? esc_html(implode(', ', array_keys($active))) : 'none')
       . '</p>';

    $filled = array();
    foreach (pph_detail_fields() as $key => $label) {
        if (pph_get($pid, 'd_' . $key) !== '') { $filled[] = $key; }
    }
    echo '<p><strong>Book Details filled:</strong> ' . count($filled)
       . ' of ' . count(pph_detail_fields()) . '</p>';
    if ($filled) {
        echo '<p style="word-break:break-all">' . esc_html(implode(', ', $filled)) . '</p>';
    }

    if (function_exists('pph_details_by_format')) {
        $dbf = pph_details_by_format($pid, $product);
        echo '<p><strong>Details tab:</strong> '
           . count($dbf['common']) . ' shared row(s), '
           . count($dbf['formats']) . ' format block(s)</p>';
    }

    echo '<p style="color:#6f665e">Send a screenshot of this box if a format '
       . 'card or a detail row is missing.</p>';
    echo '</div>';
}


/* -------------------------------------------------------------------------
   5.12.1  -  BOOKS THAT HAVE NO EBOOK

   Some titles exist on paper only. For those the page must not offer a price
   or an Add to cart button at all, it must simply send the reader to Amazon
   or IngramSpark.

   A book counts as print only when at least one format is switched on and
   none of the switched on formats is a digital one. A book with nothing
   configured yet is left alone, so plain WooCommerce still works.
   ------------------------------------------------------------------------- */

/** The product id to read format settings from: a variation asks its parent. */
function pph_root_id($product) {
    if (is_numeric($product)) { return (int) $product; }
    if (!is_object($product)) { return 0; }
    $parent = $product->get_parent_id();
    return $parent ? (int) $parent : (int) $product->get_id();
}

/** True when any digital format, ebook or PDF, is switched on. */
function pph_has_digital($post_id) {
    foreach (pph_formats() as $key => $f) {
        if ($f['type'] === 'digital' && pph_is_on($post_id, 'fmt_' . $key . '_on')) {
            return true;
        }
    }
    return false;
}

/** True when formats are configured and every one of them is a printed one. */
function pph_is_print_only($post_id) {

    $post_id = (int) $post_id;
    if (!$post_id) { return false; }

    $any = false;
    foreach (pph_formats() as $key => $f) {
        if (pph_is_on($post_id, 'fmt_' . $key . '_on')) { $any = true; break; }
    }

    if (!$any) { return false; }

    return !pph_has_digital($post_id);
}

/* Nothing on a print only book can be added to the cart. */
add_filter('woocommerce_is_purchasable', function ($purchasable, $product) {
    return pph_is_print_only(pph_root_id($product)) ? false : $purchasable;
}, 20, 2);

add_filter('woocommerce_variation_is_purchasable', function ($purchasable, $product) {
    return pph_is_print_only(pph_root_id($product)) ? false : $purchasable;
}, 20, 2);

/* No price is shown either, on the book page or in any listing. */
add_filter('woocommerce_get_price_html', function ($html, $product) {
    return pph_is_print_only(pph_root_id($product)) ? '' : $html;
}, 20, 2);

/* The listing button reads Buy from retailer and opens the book page. */
add_filter('woocommerce_loop_add_to_cart_link', function ($html, $product) {

    if (!pph_is_print_only(pph_root_id($product))) { return $html; }

    return sprintf(
        '<a href="%s" class="button pph-loop-print">%s</a>',
        esc_url(get_permalink($product->get_id())),
        esc_html__('View book', 'pph')
    );
}, 20, 2);


/* -------------------------------------------------------------------------
   5.13.0  -  REPAIRING RETAILER ADDRESSES SO AMAZON STOPS BLOCKING THEM

   An Amazon address copied out of a browser looks like this:

     https://www.amazon.com/Unlocking-Proverbs-Adventures-Ella-Part/dp/9334327081/ref=tmm_pap_swatch_0

   Everything after the product number is a tracking token that belongs to the
   session it was copied from. Followed later from another website, Amazon
   often answers with a robot check or a page not found, which is exactly the
   blocking that was happening here. Typing the address in by hand worked
   because the short form was used.

   The address is therefore reduced to the short permanent form,

     https://www.amazon.com/dp/9334327081

   which never expires and is never bounced. An affiliate tag is kept if one
   is present. Repair happens when the page is built as well as when a link is
   saved, so links already stored are corrected without re-importing anything.
   ------------------------------------------------------------------------- */

function pph_clean_retailer_url($url) {

    $url = trim((string) $url);
    if ($url === '') { return ''; }

    $parts = wp_parse_url($url);

    if (empty($parts['host']) || empty($parts['path'])) { return $url; }

    $host = strtolower($parts['host']);
    $path = $parts['path'];

    /* ---- Amazon: keep the product number, drop everything else ---- */
    if (strpos($host, 'amazon.') !== false || strpos($host, 'amzn.') !== false) {

        if (preg_match('#/(?:dp|gp/product|gp/aw/d|product|ASIN)/([A-Za-z0-9]{10})#', $path, $m)) {

            $clean = 'https://' . $host . '/dp/' . strtoupper($m[1]);

            /* An affiliate tag still earns its commission, so it is kept. */
            if (!empty($parts['query'])) {
                $q = array();
                wp_parse_str($parts['query'], $q);
                if (!empty($q['tag'])) {
                    $clean = add_query_arg('tag', sanitize_text_field($q['tag']), $clean);
                }
            }

            return $clean;
        }

        /* No product number in the path: at least drop the session token. */
        $path = preg_replace('#/ref=[^/?]*#', '', $path);

        return 'https://' . $host . $path;
    }

    /* ---- everything else: only the campaign parameters are removed ---- */
    $strip = array('utm_source', 'utm_medium', 'utm_campaign', 'utm_term',
                   'utm_content', 'ref', 'ref_', 'psc', 'th');

    return remove_query_arg($strip, $url);
}
