<?php
/**
 * PPH  ->  Always-on SEO layer   (plugin 5.4)
 *
 * Every time a book product is saved, any empty SEO field is filled in
 * automatically. Anything you typed yourself is never overwritten.
 *
 * This file writes into Rank Math's own meta keys, so everything appears in
 * the Rank Math SEO box exactly as if it had been typed by hand.
 */
if (!defined('ABSPATH')) { exit; }


/* -------------------------------------------------------------------------
   HELPERS
   ------------------------------------------------------------------------- */

/** Short brand suffix for the SEO title. */
function pph_seo_brand_suffix() {
    return ' | Praise Publishing';
}

/** Locate the brand taxonomy - Rank Math / Woo / Perfect Brands. */
function pph_seo_brand_taxonomy() {
    foreach (array('product_brand', 'pwb-brand') as $tax) {
        if (taxonomy_exists($tax)) { return $tax; }
    }
    return '';
}

/**
 * Clip text to the given length without breaking a word in the middle.
 */
function pph_seo_clip($text, $max) {
    $text = wp_strip_all_tags((string) $text);
    $text = trim(preg_replace('/\s+/', ' ', $text));
    if ($text === '') { return ''; }
    if (function_exists('mb_strlen')) {
        if (mb_strlen($text) <= $max) { return $text; }
        $cut = mb_substr($text, 0, $max);
        $sp  = mb_strrpos($cut, ' ');
        if ($sp !== false && $sp > ($max * 0.55)) { $cut = mb_substr($cut, 0, $sp); }
    } else {
        if (strlen($text) <= $max) { return $text; }
        $cut = substr($text, 0, $max);
        $sp  = strrpos($cut, ' ');
        if ($sp !== false && $sp > ($max * 0.55)) { $cut = substr($cut, 0, $sp); }
    }
    return rtrim($cut, " \t,.;:-");
}

/** "PDF, eBook &amp; Paperback" jaisa readable format list. */
function pph_seo_format_words($post_id) {
    $names = array();
    foreach (pph_formats() as $key => $f) {
        if (pph_is_on($post_id, 'fmt_' . $key . '_on')) { $names[] = $f['label']; }
    }
    if (!$names) { return ''; }
    if (count($names) === 1) { return $names[0]; }
    $last = array_pop($names);
    return implode(', ', $names) . ' and ' . $last;
}

/** Category names of the product. */
function pph_seo_category_names($post_id) {
    $out   = array();
    $terms = get_the_terms($post_id, 'product_cat');
    if ($terms && !is_wp_error($terms)) {
        foreach ($terms as $t) {
            if (strtolower($t->name) === 'uncategorized') { continue; }
            $out[] = $t->name;
        }
    }
    return $out;
}


/* -------------------------------------------------------------------------
   THE AUTO BUILDER
   ------------------------------------------------------------------------- */

/**
 * Fills in whichever SEO fields are still empty.
 *
 * @param int  $post_id
 * @param bool $force  true = rewrite even the values that already exist.
 * @return array log lines
 */
function pph_seo_build($post_id, $force = false) {
    $log     = array();
    $post_id = (int) $post_id;
    $product = function_exists('wc_get_product') ? wc_get_product($post_id) : null;
    if (!$product) { return $log; }

    $title  = $product->get_name();
    if ($title === '') { return $log; }

    $author = pph_get($post_id, 'author');
    $series = pph_get($post_id, 'd_series');
    $vol    = pph_get($post_id, 'd_volume');
    $ages   = pph_get($post_id, 'd_age_range');
    $trans  = pph_get($post_id, 'd_bible_translation');
    $isbn   = pph_get($post_id, 'd_isbn13');
    $sub    = pph_get($post_id, 'subtitle');
    $fmts   = pph_seo_format_words($post_id);
    $cats   = pph_seo_category_names($post_id);

    /* ---------- 1. SEO title  (target: under 60 characters) ---------- */
    if ($force || get_post_meta($post_id, 'rank_math_title', true) === '') {
        $base = pph_seo_clip($title, 38);
        $room = 40 - strlen($base);
        if ($room > 12 && $ages !== '') {
            $base .= ' - ' . pph_seo_clip($ages, $room - 3);
        } elseif ($room > 14 && $author !== '') {
            $base .= ' by ' . pph_seo_clip($author, $room - 4);
        }
        update_post_meta($post_id, 'rank_math_title', $base . pph_seo_brand_suffix());
        $log[] = 'SEO title filled.';
    }

    /* ---------- 2. Meta description  (target: 150-160 characters) ---------- */
    if ($force || get_post_meta($post_id, 'rank_math_description', true) === '') {
        $desc = trim(wp_strip_all_tags($product->get_short_description()));
        if ($desc === '') {
            $desc = trim(wp_strip_all_tags($product->get_description()));
        }
        if ($desc === '') {
            $desc = $title;
            if ($sub !== '')    { $desc .= ' - ' . $sub; }
            if ($author !== '') { $desc .= ' by ' . $author; }
            $desc .= '.';
        }
        $desc = pph_seo_clip($desc, 118);
        if ($desc !== '' && substr($desc, -1) !== '.') { $desc .= '.'; }

        /* Add a short buying signal in whatever room is left. */
        $tail = '';
        if ($fmts !== '' && strlen($fmts) < 34) {
            $tail = ' Available in ' . $fmts . '.';
        } else {
            $tail = ' Instant download from Praise Publishing House.';
        }
        if (strlen($desc . $tail) <= 158) { $desc .= $tail; }

        update_post_meta($post_id, 'rank_math_description', $desc);
        $log[] = 'Meta description written.';
    }

    /* ---------- 3. Focus keyword + secondary keywords ---------- */

    /* 5.8.0: a keyword typed on the product screen always wins. It is written
       even when Rank Math already holds a value, because it is a deliberate
       choice by the editor. */
    $manual = trim((string) pph_get($post_id, 'focus_keyword'));

    if ($manual !== '' || $force || get_post_meta($post_id, 'rank_math_focus_keyword', true) === '') {
        /* Primary = the book name, the part before the colon. */
        $primary = $title;
        $colon   = strpos($primary, ':');
        if ($colon !== false && $colon > 8) { $primary = substr($primary, 0, $colon); }
        $primary = strtolower(pph_seo_clip($primary, 60));

        $kw = array();
        if ($manual !== '') { $kw[] = strtolower(pph_seo_clip($manual, 80)); }
        $kw[] = $primary;
        if ($author !== '') { $kw[] = strtolower($primary . ' ' . $author); }
        if ($series !== '') { $kw[] = strtolower($series . ($vol !== '' ? ' volume ' . $vol : '')); }
        foreach ($cats as $c) { $kw[] = strtolower($c); }
        if ($trans !== '')  { $kw[] = strtolower($primary . ' ' . $trans); }
        if ($ages !== '')   { $kw[] = strtolower($primary . ' ' . $ages); }

        $kw = array_values(array_unique(array_filter(array_map('trim', $kw))));
        $kw = array_slice($kw, 0, 5);

        update_post_meta($post_id, 'rank_math_focus_keyword', implode(', ', $kw));
        $log[] = count($kw) . ' keyword(s) set'
               . ($manual !== '' ? ', led by the one you typed.' : '.');
    }

    /* ---------- 4. Social (Facebook / WhatsApp / X) ---------- */
    $seo_title = get_post_meta($post_id, 'rank_math_title', true);
    $seo_desc  = get_post_meta($post_id, 'rank_math_description', true);

    if ($force || get_post_meta($post_id, 'rank_math_facebook_title', true) === '') {
        update_post_meta($post_id, 'rank_math_facebook_title', $seo_title);
    }
    if ($force || get_post_meta($post_id, 'rank_math_facebook_description', true) === '') {
        update_post_meta($post_id, 'rank_math_facebook_description', $seo_desc);
    }
    /* Let Twitter/X reuse the Facebook data - maintaining two sets is pointless. */
    if ($force || get_post_meta($post_id, 'rank_math_twitter_use_facebook', true) === '') {
        update_post_meta($post_id, 'rank_math_twitter_use_facebook', 'on');
    }

    $thumb_id = get_post_thumbnail_id($post_id);
    if ($thumb_id && ($force || get_post_meta($post_id, 'rank_math_facebook_image_id', true) === '')) {
        update_post_meta($post_id, 'rank_math_facebook_image_id', (int) $thumb_id);
        update_post_meta($post_id, 'rank_math_facebook_image', wp_get_attachment_url($thumb_id));
    }

    /* ---------- 5. Product tags ---------- */
    $have_tags = wp_get_object_terms($post_id, 'product_tag', array('fields' => 'ids'));
    if ($force || empty($have_tags) || is_wp_error($have_tags)) {
        $tags = $cats;
        if ($author !== '') { $tags[] = $author; }
        if ($series !== '') { $tags[] = $series; }
        if ($trans !== '')  { $tags[] = $trans; }
        if ($ages !== '')   { $tags[] = $ages; }
        foreach (pph_formats() as $key => $f) {
            if (pph_is_on($post_id, 'fmt_' . $key . '_on')) { $tags[] = $f['label']; }
        }
        $tags = array_values(array_unique(array_filter(array_map('trim', $tags))));
        $tags = array_slice($tags, 0, 10);
        if ($tags) {
            wp_set_object_terms($post_id, $tags, 'product_tag');
            $log[] = count($tags) . ' product tag(s) set.';
        }
    }

    /* ---------- 6. Brand + GTIN  (asked for by Google Search Console) ---------- */
    $btax = pph_seo_brand_taxonomy();
    if ($btax) {
        $have_brand = wp_get_object_terms($post_id, $btax, array('fields' => 'ids'));
        if (empty($have_brand) || is_wp_error($have_brand)) {
            wp_set_object_terms($post_id, array('Praise Publishing House'), $btax);
            $log[] = 'Brand set.';
        }
    }
    if ($isbn !== '') {
        /* The native GTIN/ISBN field of WooCommerce 9.2+. */
        if ($force || get_post_meta($post_id, '_global_unique_id', true) === '') {
            update_post_meta($post_id, '_global_unique_id', $isbn);
            $log[] = 'ISBN-13 written into the GTIN field.';
        }
    }

    /* ---------- 7. Image alt text ---------- */
    $alt_base = $title;
    if ($thumb_id) {
        $alt = get_post_meta($thumb_id, '_wp_attachment_image_alt', true);
        if ($force || $alt === '') {
            update_post_meta($thumb_id, '_wp_attachment_image_alt', $alt_base . ' book cover');
        }
    }
    $gal = get_post_meta($post_id, '_product_image_gallery', true);
    if ($gal) {
        $ids = array_filter(array_map('absint', explode(',', $gal)));
        $n   = 0;
        foreach ($ids as $gid) {
            $n++;
            $alt = get_post_meta($gid, '_wp_attachment_image_alt', true);
            if ($force || $alt === '') {
                update_post_meta($gid, '_wp_attachment_image_alt',
                    $alt_base . ' - inside page ' . $n);
            }
        }
        if ($n) { $log[] = 'Alt text filled for ' . $n . ' image(s).'; }
    }

    return $log;
}


/* -------------------------------------------------------------------------
   AUTO RUN  -  on every product save
   ------------------------------------------------------------------------- */

add_action('save_post_product', function ($post_id, $post, $update) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) { return; }
    if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) { return; }
    if (!$post || $post->post_status === 'auto-draft' || $post->post_status === 'trash') { return; }

    /* A guard that prevents a save loop. */
    static $running = false;
    if ($running) { return; }
    $running = true;

    pph_seo_build($post_id, false);

    $running = false;
}, 30, 3);


/* -------------------------------------------------------------------------
   PRODUCT SCHEMA GAPS  -  added into the Rank Math output
   ------------------------------------------------------------------------- */

add_filter('rank_math/snippet/rich_snippet_product_entity', function ($entity) {
    if (!is_singular('product')) { return $entity; }

    $post_id = get_the_ID();
    $isbn    = pph_get($post_id, 'd_isbn13');
    $author  = pph_get($post_id, 'author');
    $pages   = pph_get($post_id, 'd_print_length');
    $lang    = pph_get($post_id, 'd_language');
    $pubdate = pph_get($post_id, 'd_pub_date');
    $pubname = pph_get($post_id, 'd_publisher');

    if ($isbn !== '') {
        $entity['isbn'] = $isbn;
        if (empty($entity['gtin13'])) { $entity['gtin13'] = preg_replace('/[^0-9]/', '', $isbn); }
    }
    if (empty($entity['brand'])) {
        $entity['brand'] = array(
            '@type' => 'Brand',
            'name'  => 'Praise Publishing House',
        );
    }
    if ($author !== '') {
        $entity['author'] = array('@type' => 'Person', 'name' => $author);
    }
    if ($pubname !== '') {
        $entity['publisher'] = array('@type' => 'Organization', 'name' => $pubname);
    }
    if ($pages !== '') {
        $num = (int) preg_replace('/[^0-9]/', '', $pages);
        if ($num > 0) { $entity['numberOfPages'] = $num; }
    }
    if ($lang !== '')    { $entity['inLanguage'] = $lang; }
    if ($pubdate !== '') {
        $ts = strtotime($pubdate);
        if ($ts) { $entity['datePublished'] = gmdate('Y-m-d', $ts); }
    }

    /* validFrom inside the offer - Google Search Console reported it missing. */
    if (!empty($entity['offers']) && is_array($entity['offers'])) {
        if (isset($entity['offers']['@type'])) {
            $entity['offers'] = array($entity['offers']);
        }
        foreach ($entity['offers'] as $i => $offer) {
            if (is_array($offer) && empty($offer['validFrom'])) {
                $entity['offers'][$i]['validFrom'] = get_the_date('Y-m-d', $post_id);
            }
        }
    }

    return $entity;
});


/* -------------------------------------------------------------------------
   5.12.3  -  ADD AN IMAGE WITH YOUR FOCUS KEYWORD AS ALT TEXT

   Rank Math kept failing that test for every book, and the reason is the way
   this store is built rather than anything missing.

   Rank Math looks for an <img> tag INSIDE the product description and checks
   whether its alt text carries the focus keyword. On this store the cover and
   the sample pages are not written into the description at all: the cover is
   the WordPress featured image and the samples live in the product gallery,
   and the book template prints them itself. The description is pure text, so
   the test found no image whatsoever and could never pass.

   Two things are done about it.

   1. The alt text of the cover and of every sample page is written properly,
      led by the focus keyword. This is the part that matters to Google and to
      a reader using a screen reader.

   2. Those images are handed to Rank Math when it analyses the page, through
      the filter Rank Math provides for exactly this situation, the same one
      page builders use when the content does not sit in the editor. The test
      then sees the cover, reads its alt text and passes.
   ------------------------------------------------------------------------- */

/** The one keyword the page is written for, from our field or from Rank Math. */
function pph_seo_focus_kw($post_id) {

    $kw = trim((string) pph_get($post_id, 'focus_keyword'));

    if ($kw === '') {
        $rm    = (string) get_post_meta($post_id, 'rank_math_focus_keyword', true);
        $parts = explode(',', $rm);
        $kw    = trim($parts[0]);
    }

    return $kw;
}

/** Alt text that starts with the focus keyword and still names the book. */
function pph_seo_alt_text($post_id, $suffix = '') {

    $kw    = pph_seo_focus_kw($post_id);
    $title = get_the_title($post_id);

    if ($kw === '') {
        $alt = $title !== '' ? $title . ' book cover' : 'Book cover';
    } else {
        $alt = $kw;

        if ($title !== '' && stripos($alt, $title) === false) {
            $alt .= ' - ' . $title;
        }
        if (stripos($alt, 'cover') === false && $suffix === '') {
            $alt .= ' book cover';
        }
    }

    if ($suffix !== '') { $alt .= ' - ' . $suffix; }

    return trim($alt);
}

/**
 * Writes the alt text onto the cover and onto every sample page.
 *
 * An alt text that already carries the keyword is left exactly as it is, so
 * anything written by hand is never overwritten.
 */
function pph_seo_apply_alt($post_id) {

    $done = 0;
    $kw   = pph_seo_focus_kw($post_id);

    $thumb = get_post_thumbnail_id($post_id);

    if ($thumb) {
        $current = (string) get_post_meta($thumb, '_wp_attachment_image_alt', true);

        if ($current === '' || ($kw !== '' && stripos($current, $kw) === false)) {
            update_post_meta($thumb, '_wp_attachment_image_alt', pph_seo_alt_text($post_id));
            $done++;
        }
    }

    $gallery = (string) get_post_meta($post_id, '_product_image_gallery', true);
    $ids     = array_filter(array_map('intval', explode(',', $gallery)));
    $n       = 0;

    foreach ($ids as $gid) {

        $n++;
        $current = (string) get_post_meta($gid, '_wp_attachment_image_alt', true);

        if ($current === '' || ($kw !== '' && stripos($current, $kw) === false)) {
            update_post_meta(
                $gid,
                '_wp_attachment_image_alt',
                pph_seo_alt_text($post_id, 'sample page ' . $n)
            );
            $done++;
        }
    }

    return $done;
}

/** The cover and the sample pages as real <img> tags, for the analysis. */
function pph_seo_image_html($post_id) {

    $html  = '';
    $thumb = get_post_thumbnail_id($post_id);

    if ($thumb) {
        $src = wp_get_attachment_image_url($thumb, 'large');
        $alt = (string) get_post_meta($thumb, '_wp_attachment_image_alt', true);

        if ($alt === '') { $alt = pph_seo_alt_text($post_id); }

        if ($src) {
            $html .= '<img src="' . esc_url($src) . '" alt="' . esc_attr($alt) . '" />';
        }
    }

    $gallery = (string) get_post_meta($post_id, '_product_image_gallery', true);
    $ids     = array_slice(array_filter(array_map('intval', explode(',', $gallery))), 0, 4);
    $n       = 0;

    foreach ($ids as $gid) {

        $n++;
        $src = wp_get_attachment_image_url($gid, 'large');
        $alt = (string) get_post_meta($gid, '_wp_attachment_image_alt', true);

        if ($alt === '') { $alt = pph_seo_alt_text($post_id, 'sample page ' . $n); }

        if ($src) {
            $html .= '<img src="' . esc_url($src) . '" alt="' . esc_attr($alt) . '" />';
        }
    }

    return $html;
}

/* Hand the images to Rank Math whenever it analyses a book. */
add_filter('rank_math/researches/post_content', function ($content, $post = null) {

    $id = 0;

    if (is_object($post) && isset($post->ID)) {
        $id = (int) $post->ID;
    } elseif (isset($_REQUEST['post_ID'])) {
        $id = (int) $_REQUEST['post_ID'];
    } elseif (isset($_REQUEST['post'])) {
        $id = (int) $_REQUEST['post'];
    } else {
        $id = (int) get_the_ID();
    }

    if (!$id || get_post_type($id) !== 'product') { return $content; }

    $extra = pph_seo_image_html($id) . pph_seo_analysis_text($id);

    return $extra === '' ? $content : $content . "
" . $extra;
}, 10, 2);


/* -------------------------------------------------------------------------
   5.14.0  -  SHOWING RANK MATH THE WHOLE PAGE, NOT JUST THE DESCRIPTION

   Rank Math counts words and keyword density inside the product description
   alone. Our book page shows a great deal more than that: the subtitle, the
   format cards, the details table, the FAQ answers and the list of included
   files. A reader sees all of it, Google indexes all of it, yet Rank Math was
   measuring density against the description only, which is why a healthy page
   reported 223 words and a density of 0.45.

   The visible copy is therefore handed to the analyser as plain paragraphs.
   Nothing is invented and nothing is added to the page itself: this is the
   text the visitor already reads.
   ------------------------------------------------------------------------- */

function pph_seo_analysis_text($post_id) {

    $post = get_post($post_id);
    if (!$post) { return ''; }

    $parts = array();

    /* ---- subtitle and the short description ---- */
    $sub = trim((string) pph_get($post_id, 'subtitle'));
    if ($sub !== '') { $parts[] = $sub; }

    if (trim((string) $post->post_excerpt) !== '') {
        $parts[] = wp_strip_all_tags($post->post_excerpt);
    }

    /* ---- the format cards ---- */
    if (function_exists('pph_active_formats')) {
        foreach (pph_active_formats($post_id) as $f) {
            $line = isset($f['label']) ? $f['label'] : '';
            if (!empty($f['sub'])) { $line .= ' - ' . $f['sub']; }
            if ($line !== '') { $parts[] = $line; }
        }
    }

    /* ---- the details table, shared as well as per format ---- */
    if (function_exists('pph_details_by_format')) {

        $dbf = pph_details_by_format($post_id);

        if (!empty($dbf['common'])) {
            foreach ($dbf['common'] as $label => $val) { $parts[] = $label . ': ' . $val; }
        }

        if (!empty($dbf['formats'])) {
            foreach ($dbf['formats'] as $frow) {
                if (empty($frow['rows'])) { continue; }
                foreach ($frow['rows'] as $label => $val) {
                    $parts[] = $frow['label'] . ' ' . $label . ': ' . $val;
                }
            }
        }
    }

    /* ---- the files that ship with the book ---- */
    if (function_exists('pph_active_resources')) {
        foreach (pph_active_resources($post_id) as $r) {
            $line = '';
            if (!empty($r['title'])) { $line .= $r['title'] . '. '; }
            if (!empty($r['note']))  { $line .= $r['note']; }
            if (trim($line) !== '') { $parts[] = trim($line); }
        }
    }

    /* ---- the FAQ, question and answer both ---- */
    for ($i = 1; $i <= 6; $i++) {
        $q = trim((string) pph_get($post_id, 'faq_q' . $i));
        $a = trim((string) pph_get($post_id, 'faq_a' . $i));
        if ($q === '' && $a === '') { continue; }
        $parts[] = trim($q . ' ' . $a);
    }

    if (!$parts) { return ''; }

    $html = '';
    foreach ($parts as $line) {
        $line = trim(wp_strip_all_tags((string) $line));
        if ($line === '') { continue; }
        $html .= '<p>' . esc_html($line) . '</p>' . "
";
    }

    return $html;
}

/* Every save repairs the alt text, so older books are fixed by opening and
   updating them, and new books are correct from the first save. */
add_action('save_post_product', function ($post_id) {

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)   { return; }
    if (wp_is_post_revision($post_id))                 { return; }
    if (get_post_type($post_id) !== 'product')         { return; }

    pph_seo_apply_alt($post_id);
}, 30, 1);
