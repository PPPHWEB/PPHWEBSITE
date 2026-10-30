<?php
/**
 * PPH  ->  Quick Add Book   (plugin 5.4)
 *
 * A single screen where you paste JSON and a complete book product is
 * created: title, description, categories, formats, retailer links, every
 * Book Details field, and the full SEO set.
 *
 * You pick the images yourself from the Media Library, so images that do not
 * exist on Amazon can be uploaded here together.
 */
if (!defined('ABSPATH')) { exit; }


/* -------------------------------------------------------------------------
   MENU  +  SCRIPTS FOR THE MEDIA PICKER
   ------------------------------------------------------------------------- */

add_action('admin_menu', function () {
    add_submenu_page(
        'edit.php?post_type=product',
        'Quick Add Book',
        'Quick Add Book',
        'manage_woocommerce',
        'pph-quick-add',
        'pph_qa_page'
    );
});

add_action('admin_enqueue_scripts', function ($hook) {
    if ($hook === 'product_page_pph-quick-add') {
        wp_enqueue_media();
    }
});


/* -------------------------------------------------------------------------
   SCHEMA  -  generated from the plugin's own fields, so it can never go
   stale. Add a new field and the prompt updates itself.
   ------------------------------------------------------------------------- */

function pph_qa_schema() {
    $fmt = array();
    foreach (pph_formats() as $key => $f) {
        /* 5.13.0: every format carries its own details block, so an ISBN or a
           file size lands on that edition instead of in the shared list. */
        $fdet = array();
        foreach (pph_format_detail_keys($f['type']) as $dk) { $fdet[$dk] = ''; }

        if ($f['type'] === 'digital') {
            $fmt[$key] = array(
                'on'      => true,
                'sub'     => '',
                'price'   => '',
                'attr'    => '',
                'details' => $fdet,
            );
        } else {
            $row = array('on' => true, 'sub' => '');
            foreach (pph_print_retailers() as $rk => $rl) { $row[$rk] = ''; }
            $row['other_label'] = '';
            $row['other_url']   = '';
            $row['details']     = $fdet;
            $fmt[$key] = $row;
        }
    }

    $dig = array();
    foreach (pph_digital_retailers() as $rk => $rl) { $dig[$rk] = ''; }

    $det = array();
    foreach (pph_detail_fields() as $k => $l) { $det[$k] = ''; }

    /* 5.5: which file types the buyer receives */
    $res = array();
    if (function_exists('pph_resource_types')) {
        foreach (pph_resource_types() as $k => $t) {
            $res[$k] = array('on' => false, 'title' => '', 'count' => '', 'size' => '', 'note' => '');
        }
    }

    return array(
        'title'             => '',
        'subtitle'          => '',
        'author'            => '',
        'badge'             => '',
        'sku'               => '',
        'price'             => '',
        'sale_price'        => '',
        'price_usd'         => '',
        'sale_price_usd'    => '',
        'short_description' => '',
        'description'       => '',
        'categories'        => array('Books'),
        'formats'           => $fmt,
        'digital_retailers' => $dig,
        'details'           => $det,
        'edition'           => '',
        'access_note'       => '',
        'free_sample'       => array(
            'on'    => false,
            'title' => '',
            'url'   => '',
            'pages' => '',
            'size'  => '',
        ),
        'resources'         => $res,
        'faqs'              => array(
            array('q' => '', 'a' => ''),
        ),
        'seo'               => array(
            'title'         => '',
            'description'   => '',
            'focus_keyword' => '',
            'keywords'      => array(),
            'slug'          => '',
            'tags'          => array(),
            'image_alt'     => '',
        ),
    );
}

function pph_qa_schema_json() {
    return wp_json_encode(
        pph_qa_schema(),
        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
    );
}

/**
 * Ready-to-copy AI prompt. The SEO rules are already written into it, so SEO
 * never has to be requested separately - it arrives in the same JSON.
 */
function pph_qa_prompt() {

    $schema = wp_json_encode(
        pph_qa_schema(),
        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
    );

    $p  = "Fill a book product for Praise Publishing House, a Christian ";
    $p .= "publisher. Reply with ONE JSON object only, using exactly the keys ";
    $p .= "in the schema below. No markdown, no code fence, no commentary, no ";
    $p .= "review or summary of this message.\n\n";

    $p .= "RULES\n";
    $p .= "1 Unknown value = empty string. Never invent an ISBN, ASIN, date, ";
    $p .= "page count or file size.\n";
    $p .= "2 on:true only for formats this book really has. No ebook means ";
    $p .= "ebook and pdf on:false with price and price_usd empty.\n";
    $p .= "3 Every format keeps its own details block. Ebook takes file_size, ";
    $p .= "asin, screen_reader, enhanced_typesetting, xray, word_wise, ";
    $p .= "page_flip. Paperback and hardcover each take their own isbn13, ";
    $p .= "isbn10, dimensions. Repeat print_length and pub_date inside a format ";
    $p .= "when editions differ. The outer details block is only for facts true ";
    $p .= "of the whole book.\n";
    $p .= "4 Retailer links in short form, https://www.amazon.com/dp/ID, with ";
    $p .= "no /ref= token.\n";
    $p .= "5 price = ebook price in rupees, price_usd = the same in dollars, ";
    $p .= "numbers only. Never price paperback or hardcover.\n";
    $p .= "6 description = 250 to 350 words of <p> HTML with one <h3>. The ";
    $p .= "focus keyword must appear in the first sentence, in the <h3> and ";
    $p .= "twice more, so four or five times in all, and still read naturally.\n";
    $p .= "7 short_description = 1 to 2 sentences containing the focus keyword ";
    $p .= "once.\n";
    $p .= "8 seo.focus_keyword = 2 to 4 lowercase words a buyer would type, ";
    $p .= "describing the need rather than the title.\n";
    $p .= "9 seo.slug = the focus keyword with hyphens, 3 or 4 words, nothing ";
    $p .= "else.\n";
    $p .= "10 seo.title = 60 characters at most, focus keyword first, and it ";
    $p .= "must contain a number such as the page count, the year or 40 Days.\n";
    $p .= "11 seo.description = 150 to 158 characters, contains the focus ";
    $p .= "keyword, one concrete benefit and one soft call to action.\n";
    $p .= "12 seo.keywords = 4 to 6 phrases, one of them 5 words or longer and ";
    $p .= "one naming the audience. seo.tags = 5 to 8 short tags. ";
    $p .= "seo.image_alt = focus keyword followed by the book title.\n";
    $p .= "13 faqs = 5 or 6 honest buyer questions with 1 to 3 sentence ";
    $p .= "answers, and the focus keyword used once in the first answer.\n";
    $p .= "14 free_sample: use on:true only when a public free sample PDF exists. ";
    $p .= "The URL must be the full same-site PDF upload URL; pages and size are optional facts and must never be guessed. ";
    $p .= "This sample is free and separate from paid PDF/eBook formats.\n";
    $p .= "15 resources: on:true only for files that really ship with the book. No image fields anywhere.\n\n";

    $p .= "SCHEMA\n" . $schema . "\n\n";
    $p .= "BOOK INFORMATION\n";
    $p .= "<<< paste the book text, the Amazon details, ISBNs and prices here. ";
    $p .= "Label them ebook details, paperback details and hardcover details ";
    $p .= "when you have more than one edition. >>>";

    return $p;
}


/* -------------------------------------------------------------------------
   IMAGE HELPERS
   ------------------------------------------------------------------------- */

/** Downloads an image from a URL into the Media Library. */
function pph_qa_sideload($url, $post_id) {
    $url = esc_url_raw(trim($url));
    if ($url === '') { return new WP_Error('pph_url', 'empty image URL'); }

    if (!function_exists('media_sideload_image')) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';
    }

    return media_sideload_image($url, $post_id, null, 'id');
}

/**
 * Turns an image value into an attachment ID.
 * A number is treated as a Media Library ID; a URL is downloaded first.
 */
function pph_qa_media_id($value, $post_id) {
    if (is_numeric($value)) {
        $id = absint($value);
        if ($id && get_post_type($id) === 'attachment') { return $id; }
        return new WP_Error('pph_att', 'Attachment ID ' . $id . ' was not found.');
    }
    return pph_qa_sideload($value, $post_id);
}


/* -------------------------------------------------------------------------
   THE IMPORT ITSELF
   ------------------------------------------------------------------------- */

function pph_qa_apply($data, $existing_id, $publish) {
    $log = array();

    if (!is_array($data)) {
        return new WP_Error('pph_json', 'The JSON must be an object.');
    }

    $title  = isset($data['title']) ? sanitize_text_field($data['title']) : '';
    $is_new = false;

    if ($existing_id) {
        $product = wc_get_product($existing_id);
        if (!$product) {
            return new WP_Error('pph_id', 'Product ID ' . $existing_id . ' was not found.');
        }
        $log[] = 'Updated existing product #' . $existing_id . '.';
    } else {
        if ($title === '') {
            return new WP_Error('pph_title', 'A "title" is required for a new product.');
        }
        $product = new WC_Product_Simple();
        $is_new  = true;
    }

    if ($title !== '') { $product->set_name($title); }

    if (isset($data['description'])) {
        $product->set_description(wp_kses_post($data['description']));
    }
    if (isset($data['short_description'])) {
        $product->set_short_description(wp_kses_post($data['short_description']));
    }

    if (!empty($data['sku'])) {
        try {
            $product->set_sku(sanitize_text_field($data['sku']));
        } catch (Exception $e) {
            $log[] = 'SKU was not set (another product already uses it).';
        }
    }

    if (isset($data['price']) && $data['price'] !== '') {
        $product->set_regular_price(wc_format_decimal($data['price']));
    }

    /* 5.12.0: an optional rupee sale price, cleared when the field is empty. */
    if (isset($data['sale_price'])) {
        $product->set_sale_price(
            $data['sale_price'] === '' ? '' : wc_format_decimal($data['sale_price'])
        );
    }

    /* SEO slug - only applied to a new product. Changing the URL of an older
       book would put its Google ranking at risk. */
    $seo = (isset($data['seo']) && is_array($data['seo'])) ? $data['seo'] : array();
    if ($is_new && !empty($seo['slug'])) {
        $product->set_slug(sanitize_title($seo['slug']));
    }

    /* A new digital book is marked Virtual + Downloadable. The same setting on
       an existing product is left alone, because that is your choice. */
    if ($is_new) {
        $product->set_virtual(true);
        $product->set_downloadable(true);
        $product->set_status($publish ? 'publish' : 'draft');
    } elseif ($publish) {
        $product->set_status('publish');
    }

    $pid = $product->save();
    if (!$pid) {
        return new WP_Error('pph_save', 'The product could not be saved.');
    }
    if ($is_new) {
        $log[] = 'Created new product #' . $pid . ' (' .
                 ($publish ? 'published' : 'draft') . ', Virtual + Downloadable).';
    }

    /* 5.12.0: the WooCommerce side of the ebook, from the same JSON. */
    $log = array_merge($log, pph_qa_setup_ebook($pid, $data));

    /* ---- categories: matched by name, created when missing ---- */
    if (!empty($data['categories']) && is_array($data['categories'])) {
        $ids = array();
        foreach ($data['categories'] as $cname) {
            $cname = sanitize_text_field($cname);
            if ($cname === '') { continue; }
            $term = get_term_by('name', $cname, 'product_cat');
            if ($term && !is_wp_error($term)) {
                $ids[] = (int) $term->term_id;
            } else {
                $new = wp_insert_term($cname, 'product_cat');
                if (!is_wp_error($new)) {
                    $ids[] = (int) $new['term_id'];
                    $log[] = 'Created new category: ' . $cname;
                }
            }
        }
        if ($ids) {
            wp_set_object_terms($pid, $ids, 'product_cat');
            $log[] = 'Set ' . count($ids) . ' categories.';
        }
    }

    /* ---- PPH meta ---- */
    $meta = array();

    foreach (array('badge', 'subtitle', 'author') as $k) {
        if (isset($data[$k])) { $meta[$k] = sanitize_text_field($data[$k]); }
    }

    /* 5.12.3: the focus keyword is also kept in our own field, so the meta box
       shows it and the alt text helper can read it later. */
    if (isset($data['seo']['focus_keyword'])) {
        $meta['focus_keyword'] = sanitize_text_field($data['seo']['focus_keyword']);
    }

    /* 5.12.2: retailer links are counted and reported, so a link that the
       JSON spelled in an unexpected way can no longer disappear in silence. */
    $rt_count   = 0;
    $rt_unknown = array();
    $rt_missing = array();

    if (!empty($data['formats']) && is_array($data['formats'])) {
        foreach (pph_formats() as $key => $f) {
            if (!isset($data['formats'][$key]) || !is_array($data['formats'][$key])) {
                continue;
            }
            $row = $data['formats'][$key];

            /* 5.9.1: must be the exact word "yes", the same value the meta box
               saves. The old "1" was read as off, so no format card appeared. */
            $on_raw = isset($row['on']) ? $row['on'] : false;
            if (is_string($on_raw)) {
                $on_raw = !in_array(strtolower(trim($on_raw)), array('', '0', 'false', 'no'), true);
            }
            $meta['fmt_' . $key . '_on'] = $on_raw ? 'yes' : '';

            if (isset($row['sub'])) {
                $meta['fmt_' . $key . '_sub'] = sanitize_text_field($row['sub']);
            }

            /* 5.13.0: details written inside the format itself always win. */
            $frow = array();
            if (isset($row['details']) && is_array($row['details'])) { $frow = $row['details']; }

            foreach (pph_format_detail_keys($f['type']) as $dk) {

                $val = '';
                if (isset($frow[$dk]))     { $val = $frow[$dk]; }
                elseif (isset($row[$dk]))  { $val = $row[$dk]; }

                if (!is_string($val) && !is_numeric($val)) { continue; }

                $val = trim((string) $val);
                if ($val === '') { continue; }

                $meta['fmt_' . $key . '_d_' . $dk] = sanitize_text_field($val);
            }

            if ($f['type'] === 'digital') {
                if (isset($row['price'])) {
                    $meta['fmt_' . $key . '_price'] = sanitize_text_field($row['price']);
                }
                if (isset($row['attr'])) {
                    $meta['fmt_' . $key . '_attr'] = sanitize_text_field($row['attr']);
                }
            } else {
                $found = pph_qa_pick_links($row, pph_print_retailers(), $rt_unknown);

                foreach ($found as $rk => $url) {
                    $meta['fmt_' . $key . '_' . $rk] = $url;
                    $rt_count++;
                }

                $has_other = isset($row['other_url']) && trim((string) $row['other_url']) !== '';

                if (!$found && !$has_other && !empty($meta['fmt_' . $key . '_on'])) {
                    $rt_missing[] = $f['label'];
                }
                if (isset($row['other_label'])) {
                    $meta['fmt_' . $key . '_other_label'] = sanitize_text_field($row['other_label']);
                }
                if (isset($row['other_url'])) {
                    $meta['fmt_' . $key . '_other_url'] = esc_url_raw($row['other_url']);
                }
            }
        }
    }

    if (!empty($data['digital_retailers']) && is_array($data['digital_retailers'])) {
        $found = pph_qa_pick_links($data['digital_retailers'], pph_digital_retailers(), $rt_unknown);
        foreach ($found as $rk => $url) {
            $meta['rt_' . $rk] = $url;
            $rt_count++;
        }
    }

    /* ---- 5.12.2: say out loud what happened to the retailer links ---- */
    if ($rt_count) {
        $log[] = $rt_count . ' retailer link(s) saved from the JSON (Amazon, IngramSpark and the rest).';
    } else {
        $log[] = 'No retailer link was found in the JSON.';
    }

    if ($rt_missing) {
        $log[] = 'Warning: ' . implode(' and ', array_unique($rt_missing))
               . ' is switched on but carries no buy link, so that card will not appear on the page.';
    }

    if ($rt_unknown) {
        $log[] = 'Ignored unrecognised link field(s): ' . implode(', ', array_unique($rt_unknown))
               . '. Use amazon_us, amazon_in, ingram, flipkart or bn.';
    }

    /* ---- 5.15: free sample PDF ---- */
    if (isset($data['free_sample']) && is_array($data['free_sample'])) {
        $fs = $data['free_sample'];
        $on = !empty($fs['on']) && $fs['on'] !== 'false';
        $meta['free_sample_on'] = $on ? 'yes' : '';

        if ($on) {
            if (isset($fs['title']) && trim((string) $fs['title']) !== '') {
                $meta['free_sample_title'] = sanitize_text_field($fs['title']);
            }
            if (isset($fs['url']) && trim((string) $fs['url']) !== '') {
                $meta['free_sample_url'] = esc_url_raw(trim((string) $fs['url']));
            }
            if (isset($fs['pages']) && trim((string) $fs['pages']) !== '') {
                $meta['free_sample_pages'] = sanitize_text_field($fs['pages']);
            }
            if (isset($fs['size']) && trim((string) $fs['size']) !== '') {
                $meta['free_sample_size'] = sanitize_text_field($fs['size']);
            }
            $log[] = 'Free sample PDF settings saved.';
        }
    }

    /* ---- 5.5: files included ---- */
    if (!empty($data['resources']) && is_array($data['resources'])
        && function_exists('pph_resource_types')) {

        $res_on = 0;
        foreach (pph_resource_types() as $rk => $rt) {
            if (!isset($data['resources'][$rk]) || !is_array($data['resources'][$rk])) { continue; }
            $row = $data['resources'][$rk];

            $on = !empty($row['on']) && $row['on'] !== 'false';
            if (!$on) { continue; }

            $meta['res_' . $rk . '_on'] = 'yes';
            foreach (array('title', 'count', 'size', 'note') as $f) {
                if (isset($row[$f]) && $row[$f] !== '') {
                    $meta['res_' . $rk . '_' . $f] = sanitize_text_field($row[$f]);
                }
            }
            $res_on++;
        }
        if ($res_on) {
            $log[] = $res_on . ' file type(s) set (What&#39;s Included + filter chips).';
        }
    }

    /* ---- 5.5: FAQ ---- */
    if (!empty($data['faqs']) && is_array($data['faqs'])) {
        $slots = function_exists('pph_faq_slots') ? pph_faq_slots() : 6;
        $n = 0;
        foreach ($data['faqs'] as $faq) {
            if ($n >= $slots) { break; }
            if (!is_array($faq)) { continue; }

            $q = isset($faq['q']) ? trim($faq['q']) : '';
            $a = isset($faq['a']) ? trim($faq['a']) : '';
            if ($q === '' || $a === '') { continue; }

            $n++;
            $meta['faq_q' . $n] = sanitize_text_field($q);
            $meta['faq_a' . $n] = sanitize_textarea_field($a);
        }
        if ($n) { $log[] = $n . ' FAQ set (on the page + in the Google FAQ schema).'; }
    }

    /* ---- 5.5: freshness ---- */
    foreach (array('edition', 'access_note') as $fk) {
        if (!empty($data[$fk])) {
            $meta[$fk] = sanitize_text_field($data[$fk]);
        }
    }

    if (!empty($data['details']) && is_array($data['details'])) {

        foreach (pph_detail_fields() as $k => $l) {
            if (isset($data['details'][$k])) {
                $meta['d_' . $k] = sanitize_text_field($data['details'][$k]);
            }
        }

        /* -----------------------------------------------------------------
           5.13.0: a detail that belongs to one kind of edition is copied to
           the editions it describes.

           A file size, an ASIN or an X-Ray setting describes the ebook. An
           ISBN or a set of dimensions describes a printed edition. Written
           once in the shared block they used to sit in the shared list only,
           which is why every format card looked half empty while the shared
           list carried facts that were not true of the whole book.

           A value written inside a format above is never replaced here.
           ----------------------------------------------------------------- */

        $groups = pph_detail_groups();
        $active = array();

        foreach (pph_formats() as $fkey => $f) {

            $on = false;

            if (isset($data['formats'][$fkey]['on'])) {
                $raw = $data['formats'][$fkey]['on'];
                if (is_string($raw)) {
                    $raw = !in_array(strtolower(trim($raw)), array('', '0', 'false', 'no'), true);
                }
                $on = (bool) $raw;
            } else {
                $on = pph_is_on($pid, 'fmt_' . $fkey . '_on');
            }

            if ($on) { $active[$fkey] = $f['type']; }
        }

        $routed = 0;

        foreach ($data['details'] as $dk => $dv) {

            if (!is_string($dv) && !is_numeric($dv)) { continue; }

            $dv = trim((string) $dv);
            if ($dv === '') { continue; }

            $group = isset($groups[$dk]) ? $groups[$dk] : 'common';
            if ($group === 'common') { continue; }

            foreach ($active as $fkey => $ftype) {

                if ($group === 'digital' && $ftype !== 'digital') { continue; }
                if ($group === 'print'   && $ftype !== 'print')   { continue; }

                if (!in_array($dk, pph_format_detail_keys($ftype), true)) { continue; }

                $mk = 'fmt_' . $fkey . '_d_' . $dk;
                if (isset($meta[$mk]) && $meta[$mk] !== '') { continue; }

                $meta[$mk] = sanitize_text_field($dv);
                $routed++;
            }
        }

        if ($routed) {
            $log[] = $routed . ' shared detail(s) were also copied onto the format '
                   . 'card(s) they describe, so each edition shows its own facts.';
        }
    }

    $written = 0;
    foreach ($meta as $k => $v) {
        if ($v === '') {
            delete_post_meta($pid, '_pph_' . $k);
        } else {
            update_post_meta($pid, '_pph_' . $k, $v);
            $written++;
        }
    }
    $log[] = $written . ' PPH field(s) filled.';

    /* ---- images: Media Library IDs, or URLs ---- */
    /* 5.12.3: the focus keyword leads the alt text, because that is what the
       Rank Math image test reads. Anything the writer put in seo.image_alt is
       used when no keyword was supplied. */
    $focus    = isset($seo['focus_keyword']) ? trim(sanitize_text_field($seo['focus_keyword'])) : '';
    $alt_text = '';

    if ($focus !== '') {

        $alt_text = $focus;

        if ($title !== '' && stripos($alt_text, $title) === false) {
            $alt_text .= ' - ' . $title;
        }
        if (stripos($alt_text, 'cover') === false) {
            $alt_text .= ' book cover';
        }

    } elseif (!empty($seo['image_alt'])) {
        $alt_text = sanitize_text_field($seo['image_alt']);
    } elseif ($title !== '') {
        $alt_text = $title . ' book cover';
    }

    if (!empty($data['cover'])) {
        $att = pph_qa_media_id($data['cover'], $pid);
        if (is_wp_error($att)) {
            $log[] = 'Cover image fail: ' . $att->get_error_message();
        } else {
            set_post_thumbnail($pid, $att);
            if ($alt_text !== '') {
                update_post_meta($att, '_wp_attachment_image_alt', $alt_text);
                $log[] = 'Cover alt text set to: ' . $alt_text;
            }
            $log[] = 'Cover image set.';
        }
    }

    if (!empty($data['gallery']) && is_array($data['gallery'])) {
        $gids = array();
        $n    = 0;
        foreach ($data['gallery'] as $g) {
            $att = pph_qa_media_id($g, $pid);
            if (is_wp_error($att)) {
                $log[] = 'Gallery image fail: ' . $att->get_error_message();
            } else {
                $n++;
                $gids[] = (int) $att;
                if (get_post_meta($att, '_wp_attachment_image_alt', true) === '' && $alt_text !== '') {
                    update_post_meta($att, '_wp_attachment_image_alt',
                        $alt_text . ' - sample page ' . $n);
                }
            }
        }
        if ($gids) {
            update_post_meta($pid, '_product_image_gallery', implode(',', $gids));
            $log[] = 'Set ' . count($gids) . ' gallery image(s) (rail + Sample Pages).';
        }
    }

    /* ---- SEO: JSON data first, then anything left over is filled in ---- */
    $seo_written = 0;
    $map = array(
        'title'         => 'rank_math_title',
        'description'   => 'rank_math_description',
    );
    foreach ($map as $src => $mk) {
        if (!empty($seo[$src])) {
            update_post_meta($pid, $mk, sanitize_text_field($seo[$src]));
            $seo_written++;
        }
    }

    $kw = array();
    if (!empty($seo['focus_keyword'])) { $kw[] = sanitize_text_field($seo['focus_keyword']); }
    if (!empty($seo['keywords']) && is_array($seo['keywords'])) {
        foreach ($seo['keywords'] as $k) { $kw[] = sanitize_text_field($k); }
    }
    $kw = array_values(array_unique(array_filter(array_map('trim', $kw))));
    if ($kw) {
        /* Rank Math treats the first keyword as the focus keyword. */
        update_post_meta($pid, 'rank_math_focus_keyword', implode(', ', array_slice($kw, 0, 5)));
        $seo_written++;
    }

    if (!empty($seo['tags']) && is_array($seo['tags'])) {
        $tags = array_values(array_unique(array_filter(array_map('sanitize_text_field', $seo['tags']))));
        if ($tags) {
            wp_set_object_terms($pid, array_slice($tags, 0, 10), 'product_tag');
            $seo_written++;
        }
    }

    if ($seo_written) {
        $log[] = 'SEO fields filled from the JSON (visible in Rank Math).';
    }

    /* Whatever SEO is still empty - title, description, keywords, social,
       brand, GTIN, alt text - is generated by the plugin itself. */
    if (function_exists('pph_seo_build')) {
        $auto = pph_seo_build($pid, false);
        foreach ($auto as $line) { $log[] = 'Auto SEO: ' . $line; }
    }

    return array('id' => $pid, 'log' => $log);
}


/* -------------------------------------------------------------------------
   THE SCREEN
   ------------------------------------------------------------------------- */

function pph_qa_page() {
    if (!current_user_can('manage_woocommerce')) {
        wp_die('You do not have permission to open this screen.');
    }

    $result   = null;
    $error    = '';
    $json     = '';
    $cover_id = 0;
    $gal_ids  = '';
    $exist    = 0;

    if (isset($_POST['pph_qa_submit'])) {
        check_admin_referer('pph_qa');

        $json     = isset($_POST['pph_qa_json']) ? wp_unslash($_POST['pph_qa_json']) : '';
        $exist    = isset($_POST['pph_qa_id']) ? absint($_POST['pph_qa_id']) : 0;
        $publish  = !empty($_POST['pph_qa_publish']);
        $cover_id = isset($_POST['pph_qa_cover_id']) ? absint($_POST['pph_qa_cover_id']) : 0;
        $gal_ids  = isset($_POST['pph_qa_gallery_ids']) ? sanitize_text_field($_POST['pph_qa_gallery_ids']) : '';

        $data = json_decode(trim($json), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $error = 'The JSON is not valid: ' . json_last_error_msg() .
                     '  -  ask the AI again for JSON only, with no code fence.';
        } else {
            /* Images are chosen from the Media Library, not from the JSON. */
            if ($cover_id) { $data['cover'] = $cover_id; }
            if ($gal_ids !== '') {
                $data['gallery'] = array_values(array_filter(array_map('absint', explode(',', $gal_ids))));
            }

            $out = pph_qa_apply($data, $exist, $publish);
            if (is_wp_error($out)) {
                $error = $out->get_error_message();
            } else {
                $result   = $out;
                $json     = '';
                $cover_id = 0;
                $gal_ids  = '';
            }
        }
    }
    ?>
    <div class="wrap pph-qa">
      <h1>Quick Add Book</h1>
      <p class="description" style="max-width:860px;font-size:14px;">
        There is no need to fill in every field by hand. Copy the <strong>step 3</strong>
        prompt below, give the AI the details of the book, paste the JSON you get back
        into the <strong>step 1</strong> box, choose the images in <strong>step 2</strong>,
        then press <strong>Import</strong>. SEO arrives inside the same JSON &mdash;
        nothing extra has to be requested.
      </p>

      <?php if ($error) : ?>
        <div class="notice notice-error"><p><?php echo esc_html($error); ?></p></div>
      <?php endif; ?>

      <?php if ($result) : ?>
        <div class="notice notice-success">
          <p><strong>Done.</strong>
            <a href="<?php echo esc_url(get_edit_post_link($result['id'])); ?>">Open product #<?php echo (int) $result['id']; ?></a>
            &nbsp;|&nbsp;
            <a href="<?php echo esc_url(get_permalink($result['id'])); ?>" target="_blank">View the page</a>
          </p>
          <ul style="margin:0 0 10px 18px;list-style:disc;">
            <?php foreach ($result['log'] as $line) : ?>
              <li><?php echo esc_html($line); ?></li>
            <?php endforeach; ?>
          </ul>
          <p style="margin-bottom:10px;">
            <strong>One task is left:</strong> open the product and attach the PDF/eBook file under
            <em>Product data &rarr; General &rarr; Downloadable files</em>.
            Uploading the file itself cannot be automated.
          </p>
        </div>
      <?php endif; ?>

      <div style="display:flex;gap:26px;flex-wrap:wrap;align-items:flex-start;">

        <form method="post" style="flex:1 1 540px;min-width:440px;">
          <?php wp_nonce_field('pph_qa'); ?>

          <h2 style="margin-top:6px;">1. Paste the JSON here</h2>
          <p class="description" style="margin-bottom:6px;">
            No file needs to be uploaded. The JSON from the AI is pasted straight into this box.
          </p>
          <textarea name="pph_qa_json" rows="20" class="large-text code"
                    placeholder="{ ... }" spellcheck="false"><?php echo esc_textarea($json); ?></textarea>

          <h2>2. Choose the images</h2>
          <p class="description" style="margin-bottom:10px;">
            You supply these yourself, not the AI. The Media Library opens and you can
            <strong>drag and drop as many images at once</strong> as you like.
            The cover goes in its own field, and the inside pages go in the gallery.
          </p>

          <table class="form-table">
            <tr>
              <th scope="row">Cover image</th>
              <td>
                <input type="hidden" id="pph_qa_cover_id" name="pph_qa_cover_id"
                       value="<?php echo (int) $cover_id; ?>">
                <button type="button" class="button" id="pph-pick-cover">Choose cover</button>
                <button type="button" class="button-link" id="pph-clear-cover"
                        style="margin-left:8px;color:#b32d2e;">remove</button>
                <div id="pph-cover-prev" style="margin-top:10px;"></div>
              </td>
            </tr>
            <tr>
              <th scope="row">Gallery images</th>
              <td>
                <input type="hidden" id="pph_qa_gallery_ids" name="pph_qa_gallery_ids"
                       value="<?php echo esc_attr($gal_ids); ?>">
                <button type="button" class="button" id="pph-pick-gal">Choose gallery images</button>
                <button type="button" class="button-link" id="pph-clear-gal"
                        style="margin-left:8px;color:#b32d2e;">remove all</button>
                <div id="pph-gal-prev" style="margin-top:10px;display:flex;gap:8px;flex-wrap:wrap;"></div>
                <p class="description">
                  These images appear in the left rail of the book page and in the <em>Sample Pages</em> tab,
                  in exactly the order you choose them.
                </p>
              </td>
            </tr>
          </table>

          <h2>3. Settings</h2>
          <table class="form-table">
            <tr>
              <th scope="row"><label for="pph_qa_id">Existing product ID</label></th>
              <td>
                <input type="number" id="pph_qa_id" name="pph_qa_id" min="0" class="small-text"
                       value="<?php echo (int) $exist; ?>">
                <p class="description">
                  0 = a new product is created. To update an existing book, enter its ID.
                  The URL slug of an existing book is never changed, to protect its Google ranking.
                </p>
              </td>
            </tr>
            <tr>
              <th scope="row">Publish</th>
              <td>
                <label>
                  <input type="checkbox" name="pph_qa_publish" value="1">
                  Publish immediately
                </label>
                <p class="description">
                  Leave it unticked to create a <strong>draft</strong> &mdash; checking it first is safer.
                </p>
              </td>
            </tr>
          </table>

          <p>
            <button type="submit" name="pph_qa_submit" value="1" class="button button-primary button-hero">
              Import
            </button>
          </p>
        </form>

        <div style="flex:1 1 430px;min-width:390px;">
          <h2 style="margin-top:6px;">AI prompt (copy this)</h2>
          <p class="description">
            This prompt is generated from the plugin's own fields, so it can never go stale.
            The SEO rules are already written into it.
          </p>
          <textarea rows="24" class="large-text code" readonly spellcheck="false"
                    onclick="this.select();"><?php echo esc_textarea(pph_qa_prompt()); ?></textarea>
          <p class="description">Click once and the whole prompt is selected.</p>

          <h2>Where does the SEO go?</h2>
          <p class="description" style="font-size:13px;">
            Straight into the <strong>Rank Math</strong> fields. Open the product and scroll down to
            <em>Rank Math SEO</em> box, where you will
            find the title, description, focus keyword and social preview already filled in.
            You can edit them there; anything you write is never overwritten.
          </p>
          <p class="description" style="font-size:13px;">
            If the SEO is left empty in the JSON, the plugin builds it itself &mdash;
            title, description, keywords, product tags, brand, GTIN from ISBN, and
            including the alt text of every image. This runs on <strong>every product save</strong>,
            not only in Quick Add.
          </p>

          <h2>Many books at once?</h2>
          <p class="description" style="font-size:13px;">
            Use the native WooCommerce importer:
            <em>Products &rarr; All Products &rarr; Import</em>.
            In the CSV, name the column for a PPH field like this &mdash;
            <code>Meta: _pph_d_isbn13</code>,
            <code>Meta: _pph_fmt_paperback_amazon_in</code>,
            <code>Meta: _pph_fmt_paperback_on</code>.
            No extra plugin is needed for this.
          </p>
        </div>

      </div>
    </div>

    <script>
    jQuery(function ($) {
      var frame;

      function pick(multiple, onDone) {
        frame = wp.media({
          title: multiple ? 'Choose gallery images' : 'Choose cover image',
          library: { type: 'image' },
          multiple: multiple ? 'add' : false,
          button: { text: 'Use these images' }
        });
        frame.on('select', function () {
          var out = [];
          frame.state().get('selection').each(function (a) {
            var at = a.attributes;
            var u  = (at.sizes && at.sizes.thumbnail) ? at.sizes.thumbnail.url : at.url;
            out.push({ id: a.id, url: u });
          });
          onDone(out);
        });
        frame.open();
      }

      function tile(url) {
        return '<img src="' + url + '" style="width:70px;height:70px;object-fit:cover;' +
               'border:1px solid #dcdcde;border-radius:4px;">';
      }

      $('#pph-pick-cover').on('click', function () {
        pick(false, function (list) {
          if (!list.length) { return; }
          $('#pph_qa_cover_id').val(list[0].id);
          $('#pph-cover-prev').html(tile(list[0].url));
        });
      });

      $('#pph-clear-cover').on('click', function () {
        $('#pph_qa_cover_id').val(0);
        $('#pph-cover-prev').empty();
      });

      $('#pph-pick-gal').on('click', function () {
        pick(true, function (list) {
          var have = $('#pph_qa_gallery_ids').val();
          var ids  = have ? have.split(',') : [];
          list.forEach(function (it) {
            if (ids.indexOf(String(it.id)) === -1) {
              ids.push(String(it.id));
              $('#pph-gal-prev').append(tile(it.url));
            }
          });
          $('#pph_qa_gallery_ids').val(ids.join(','));
        });
      });

      $('#pph-clear-gal').on('click', function () {
        $('#pph_qa_gallery_ids').val('');
        $('#pph-gal-prev').empty();
      });
    });
    </script>
    <?php
}


/* -------------------------------------------------------------------------
   5.12.0  -  THE EBOOK SIDE OF WOOCOMMERCE, FROM THE SAME JSON

   Everything a shopper actually buys is set here, so nothing has to be typed
   into the WooCommerce panels by hand:

     price           the rupee price shown in India
     sale_price      an optional rupee offer price
     price_usd       the dollar price shown to buyers outside India
     sale_price_usd  an optional dollar offer price
     download_url    an optional file address, with download_name as its label

   Virtual and Downloadable are ticked every time, on a new book and on an
   older one, because an ebook is never shipped.

   A book that was built as a variable product, with an Ebook variation, is
   handled too: each variation is ticked and priced, and the dollar price is
   written on the parent as well as on the variation, which is where the price
   filter looks for it.

   Print formats are deliberately untouched. Paperback and hardcover are
   bought from Amazon and IngramSpark, so they carry links in the meta box and
   never a price here.
   ------------------------------------------------------------------------- */

function pph_qa_setup_ebook($pid, $data) {

    $log     = array();
    $product = wc_get_product($pid);

    if (!$product) { return $log; }

    /* 5.12.1: a printed only book must not be priced or sold here. When the
       JSON carries a formats block and no digital format inside it is on, the
       WooCommerce side is emptied instead of filled. */
    if (isset($data['formats']) && is_array($data['formats'])) {

        $digital_on = false;

        foreach (pph_formats() as $fkey => $f) {

            if ($f['type'] !== 'digital')            { continue; }
            if (!isset($data['formats'][$fkey]['on'])) { continue; }

            $raw = $data['formats'][$fkey]['on'];
            if (is_string($raw)) {
                $raw = !in_array(strtolower(trim($raw)), array('', '0', 'false', 'no'), true);
            }
            if ($raw) { $digital_on = true; break; }
        }

        if (!$digital_on) {
            return pph_qa_make_print_only($product);
        }
    }

    $inr      = (isset($data['price'])          && $data['price']          !== '') ? wc_format_decimal($data['price'])          : null;
    $inr_sale = (isset($data['sale_price'])     && $data['sale_price']     !== '') ? wc_format_decimal($data['sale_price'])     : null;
    $usd      = (isset($data['price_usd'])      && $data['price_usd']      !== '') ? wc_format_decimal($data['price_usd'])      : null;
    $usd_sale = (isset($data['sale_price_usd']) && $data['sale_price_usd'] !== '') ? wc_format_decimal($data['sale_price_usd']) : null;

    /* ---- the dollar price, read later by the pricing module ---- */
    if ($usd !== null) {
        update_post_meta($pid, '_pph_price_usd', $usd);
        $log[] = 'International price set to ' . $usd . ' USD.';
    }

    if ($usd_sale !== null) {
        update_post_meta($pid, '_pph_sale_usd', $usd_sale);
        $log[] = 'International sale price set to ' . $usd_sale . ' USD.';
    } elseif (isset($data['sale_price_usd'])) {
        delete_post_meta($pid, '_pph_sale_usd');
    }

    /* ---- an optional downloadable file ---- */
    $file_url = isset($data['download_url']) ? esc_url_raw(trim($data['download_url'])) : '';

    /* ---- a simple product: the normal case ---- */
    if ($product->is_type('simple')) {

        $product->set_virtual(true);
        $product->set_downloadable(true);

        if ($inr !== null) { $product->set_regular_price($inr); }

        if ($inr_sale !== null) {
            $product->set_sale_price($inr_sale);
        } elseif (isset($data['sale_price'])) {
            $product->set_sale_price('');
        }

        if ($file_url && class_exists('WC_Product_Download')) {
            $name = isset($data['download_name']) && $data['download_name'] !== ''
                ? sanitize_text_field($data['download_name'])
                : $product->get_name();

            $file = new WC_Product_Download();
            $file->set_id(md5($file_url));
            $file->set_name($name);
            $file->set_file($file_url);

            $product->set_downloads(array($file));
            $product->set_download_limit(-1);
            $product->set_download_expiry(-1);
            $log[] = 'Attached the download file.';
        }

        $product->save();
        $log[] = 'Ebook ready: Virtual and Downloadable are ticked'
               . ($inr !== null ? ', rupee price ' . $inr : '') . '.';

        return $log;
    }

    /* ---- a variable product: every variation gets the same treatment ---- */
    if ($product->is_type('variable')) {

        $children = $product->get_children();

        foreach ($children as $vid) {

            $variation = wc_get_product($vid);
            if (!$variation) { continue; }

            $variation->set_virtual(true);
            $variation->set_downloadable(true);

            if ($inr !== null) { $variation->set_regular_price($inr); }

            if ($inr_sale !== null) {
                $variation->set_sale_price($inr_sale);
            } elseif (isset($data['sale_price'])) {
                $variation->set_sale_price('');
            }

            $variation->save();

            /* The price filter checks the variation first, then the parent. */
            if ($usd !== null)      { update_post_meta($vid, '_pph_price_usd', $usd); }
            if ($usd_sale !== null) { update_post_meta($vid, '_pph_sale_usd', $usd_sale); }
        }

        if (function_exists('wc_delete_product_transients')) {
            wc_delete_product_transients($pid);
        }

        $log[] = 'Ebook ready on ' . count($children)
               . ' variation(s): Virtual and Downloadable are ticked'
               . ($inr !== null ? ', rupee price ' . $inr : '') . '.';

        return $log;
    }

    $log[] = 'The product type is ' . $product->get_type()
           . ', so the ebook settings were left alone.';

    return $log;
}


/* -------------------------------------------------------------------------
   5.12.1  -  TURNING A BOOK INTO A PRINTED ONLY TITLE

   Called from the Quick Add routine, from the bulk action on the Products
   screen, and available to anything else that needs it.

   The price is emptied, which is what makes WooCommerce stop offering the
   book for sale, the download file is detached, and the ebook and PDF cards
   are switched off so the page shows paperback and hardcover only.

   Virtual stays ticked, because a book bought on Amazon is never posted from
   here and no shipping should ever be calculated for it.
   ------------------------------------------------------------------------- */

function pph_qa_make_print_only($product) {

    $log = array();

    if (is_numeric($product)) { $product = wc_get_product((int) $product); }
    if (!$product)            { return $log; }

    $pid = $product->get_id();

    /* ---- switch every digital format card off ---- */
    foreach (pph_formats() as $key => $f) {
        if ($f['type'] === 'digital') {
            update_post_meta($pid, '_pph_fmt_' . $key . '_on', '');
        }
    }

    /* ---- clear both currencies ---- */
    delete_post_meta($pid, '_pph_price_usd');
    delete_post_meta($pid, '_pph_sale_usd');

    $strip = function ($item) {
        $item->set_regular_price('');
        $item->set_sale_price('');
        $item->set_price('');
        $item->set_downloadable(false);
        $item->set_downloads(array());
        $item->set_virtual(true);
        $item->save();
    };

    if ($product->is_type('variable')) {

        foreach ($product->get_children() as $vid) {
            $variation = wc_get_product($vid);
            if (!$variation) { continue; }
            $strip($variation);
            delete_post_meta($vid, '_pph_price_usd');
            delete_post_meta($vid, '_pph_sale_usd');
        }

        $product->save();

    } else {
        $strip($product);
    }

    if (function_exists('wc_delete_product_transients')) {
        wc_delete_product_transients($pid);
    }

    $log[] = 'Print only: the ebook card, both prices and the download file were removed. '
           . 'The page now shows the retailer buttons only.';

    return $log;
}


/* -------------------------------------------------------------------------
   5.12.1  -  A BULK ACTION, FOR CLEANING UP MANY OLD BOOKS AT ONCE

   Products -> All Products, tick the titles that have no ebook, then choose
   Make print only from the Bulk actions list.
   ------------------------------------------------------------------------- */

add_filter('bulk_actions-edit-product', function ($actions) {
    $actions['pph_print_only'] = 'Make print only (remove ebook)';
    return $actions;
});

add_filter('handle_bulk_actions-edit-product', function ($redirect, $action, $ids) {

    if ($action !== 'pph_print_only') { return $redirect; }

    $done = 0;

    foreach ((array) $ids as $id) {
        if (!current_user_can('edit_post', $id)) { continue; }
        pph_qa_make_print_only((int) $id);
        $done++;
    }

    return add_query_arg('pph_print_only', $done, $redirect);
}, 10, 3);

add_action('admin_notices', function () {

    if (!isset($_GET['pph_print_only'])) { return; }

    $n = (int) $_GET['pph_print_only'];

    printf(
        '<div class="notice notice-success is-dismissible"><p>%d book(s) are now print only. '
        . 'The ebook card, the price and the Add to cart button were removed from them.</p></div>',
        $n
    );
});


/* -------------------------------------------------------------------------
   5.12.2  -  READING RETAILER LINKS OUT OF THE JSON, WHATEVER THEY ARE CALLED

   The old reader looked for the exact field names amazon_us, amazon_in,
   ingram, flipkart and bn and quietly ignored everything else. An Amazon
   address written as "amazon", "amazon_com", "amazon_link" or placed inside a
   "retailers" block was therefore dropped without a word, which is why links
   that were clearly present in the JSON never reached the book page.

   The reader below accepts all the common spellings, looks inside a nested
   block as well as at the top of the format, repairs an address that was
   written without https, and reports anything it still could not place.
   ------------------------------------------------------------------------- */

function pph_qa_retailer_aliases() {
    return array(
        'amazon_in' => array('amazon in', 'amazonin', 'amazon india', 'amazon.in', 'amazon_india_url'),
        'amazon_us' => array('amazon', 'amazon us', 'amazon com', 'amazon.com', 'amazoncom',
                             'amazon usa', 'amazon url', 'amazon link', 'amzn'),
        'ingram'    => array('ingram spark', 'ingramspark', 'ingram sparks', 'ingramsparks',
                             'ingram url', 'ingram link', 'ingram content'),
        'flipkart'  => array('flip kart'),
        'bn'        => array('barnes', 'barnes noble', 'barnes and noble', 'barnesandnoble',
                             'b and n', 'bnn'),
        'kindle'    => array('amazon kindle', 'kindle store'),
        'apple'     => array('apple books', 'ibooks', 'itunes', 'apple store'),
        'google'    => array('google play', 'google books', 'play books', 'googleplay'),
        'kobo'      => array('rakuten kobo', 'rakuten'),
        'tpt'       => array('teachers pay teachers', 'teacherspayteachers', 'teachers_pay_teachers'),
    );
}

/** Lower case, letters and digits only, so amazon_US and Amazon.com both match. */
function pph_qa_norm_key($key) {
    return preg_replace('/[^a-z0-9]/', '', strtolower(trim((string) $key)));
}

/** Every accepted spelling, mapped back to the one key the plugin stores. */
function pph_qa_link_map($retailers) {

    $alias = pph_qa_retailer_aliases();
    $map   = array();

    foreach ($retailers as $rk => $rl) {

        $map[pph_qa_norm_key($rk)] = $rk;
        $map[pph_qa_norm_key($rl)] = $rk;

        if (isset($alias[$rk])) {
            foreach ($alias[$rk] as $a) { $map[pph_qa_norm_key($a)] = $rk; }
        }
    }

    return $map;
}

/** An address with no scheme still becomes a working https address. */
function pph_qa_clean_url($url) {

    $url = trim((string) $url);
    if ($url === '') { return ''; }

    if (strpos($url, '//') === 0) {
        $url = 'https:' . $url;
    } elseif (!preg_match('#^https?://#i', $url)) {
        $url = 'https://' . ltrim($url, '/');
    }

    /* 5.13.0: an Amazon session token is dropped here as well as at render. */
    if (function_exists('pph_clean_retailer_url')) {
        $url = pph_clean_retailer_url($url);
    }

    return esc_url_raw($url);
}

/**
 * Pulls the buy links out of one format row, or out of a retailer block.
 *
 * @param array $row        the JSON fragment to read
 * @param array $retailers  key => label, from pph_print_retailers() or the digital list
 * @param array $unknown    collects field names that looked like a link but matched nothing
 * @return array            key => cleaned address
 */
function pph_qa_pick_links($row, $retailers, &$unknown) {

    $out = array();
    if (!is_array($row)) { return $out; }

    /* A nested block is treated exactly like a field written at the top. */
    $flat = $row;
    foreach (array('retailers', 'links', 'buy', 'urls', 'stores') as $nest) {
        if (isset($row[$nest]) && is_array($row[$nest])) {
            foreach ($row[$nest] as $k => $v) { $flat[$k] = $v; }
        }
    }

    $map  = pph_qa_link_map($retailers);
    $skip = array('on', 'sub', 'price', 'attr', 'otherlabel', 'otherurl',
                  'retailers', 'links', 'buy', 'urls', 'stores', 'details');

    foreach ($flat as $k => $v) {

        if (!is_string($v) && !is_numeric($v)) { continue; }

        $v = trim((string) $v);
        if ($v === '') { continue; }

        $nk = pph_qa_norm_key($k);
        if (in_array($nk, $skip, true)) { continue; }

        if (isset($map[$nk])) {
            $out[$map[$nk]] = pph_qa_clean_url($v);
            continue;
        }

        /* Not a field we know, but it clearly holds a web address. */
        if (preg_match('#^https?://#i', $v) || preg_match('#^www\.#i', $v)) {
            $unknown[] = (string) $k;
        }
    }

    return $out;
}


/* -------------------------------------------------------------------------
   5.12.3  -  A BULK ACTION FOR THE ALT TEXT OF OLDER BOOKS

   Products -> All Products, tick the books, then choose Fix image alt text
   from the Bulk actions list. The cover and every sample page are given alt
   text led by that book's own focus keyword.
   ------------------------------------------------------------------------- */

add_filter('bulk_actions-edit-product', function ($actions) {
    $actions['pph_fix_alt'] = 'Fix image alt text (SEO)';
    return $actions;
});

add_filter('handle_bulk_actions-edit-product', function ($redirect, $action, $ids) {

    if ($action !== 'pph_fix_alt') { return $redirect; }
    if (!function_exists('pph_seo_apply_alt')) { return $redirect; }

    $images = 0;
    $books  = 0;

    foreach ((array) $ids as $id) {
        if (!current_user_can('edit_post', $id)) { continue; }
        $images += pph_seo_apply_alt((int) $id);
        $books++;
    }

    return add_query_arg(
        array('pph_alt_books' => $books, 'pph_alt_images' => $images),
        $redirect
    );
}, 10, 3);

add_action('admin_notices', function () {

    if (!isset($_GET['pph_alt_books'])) { return; }

    printf(
        '<div class="notice notice-success is-dismissible"><p>Alt text written on %d image(s) '
        . 'across %d book(s), each one led by that book&#39;s focus keyword.</p></div>',
        (int) $_GET['pph_alt_images'],
        (int) $_GET['pph_alt_books']
    );
});


/* -------------------------------------------------------------------------
   5.14.0  -  BRINGING AN OLDER BOOK URL IN LINE WITH ITS KEYWORD

   Rank Math fails a page when the focus keyword is missing from the address,
   and a long address of its own is marked down as well. New books already get
   the short address from the JSON. Older books kept the address they were
   created with, so a bulk action is offered for them.

   WordPress remembers the old address by itself and sends visitors and search
   engines on to the new one, so no ranking and no printed link is lost.
   ------------------------------------------------------------------------- */

add_filter('bulk_actions-edit-product', function ($actions) {
    $actions['pph_seo_slug'] = 'Shorten URL to the SEO keyword';
    return $actions;
});

add_filter('handle_bulk_actions-edit-product', function ($redirect, $action, $ids) {

    if ($action !== 'pph_seo_slug') { return $redirect; }

    $done    = 0;
    $skipped = 0;

    foreach ((array) $ids as $id) {

        $id = (int) $id;
        if (!$id || get_post_type($id) !== 'product') { continue; }

        $kw = function_exists('pph_seo_focus_kw') ? pph_seo_focus_kw($id) : '';
        $kw = sanitize_title((string) $kw);

        if ($kw === '') { $skipped++; continue; }

        $post = get_post($id);
        if (!$post || $post->post_name === $kw) { $skipped++; continue; }

        wp_update_post(array('ID' => $id, 'post_name' => $kw));
        $done++;
    }

    $redirect = add_query_arg(
        array('pph_slug_done' => $done, 'pph_slug_skip' => $skipped),
        $redirect
    );

    return $redirect;
}, 10, 3);

add_action('admin_notices', function () {

    if (!isset($_GET['pph_slug_done'])) { return; }

    $done = (int) $_GET['pph_slug_done'];
    $skip = isset($_GET['pph_slug_skip']) ? (int) $_GET['pph_slug_skip'] : 0;

    echo '<div class="notice notice-success is-dismissible"><p>';
    echo esc_html($done . ' URL(s) shortened to the focus keyword. WordPress '
        . 'keeps sending the old address to the new one.');
    if ($skip) {
        echo ' ' . esc_html($skip . ' book(s) were left alone, either because '
            . 'no focus keyword is set or the URL already matched.');
    }
    echo '</p></div>';
});
