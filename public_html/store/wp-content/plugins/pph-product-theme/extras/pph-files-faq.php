<?php
/**
 * PPH  ->  What's Included (files + filter) and FAQ   (plugin 5.5)
 *
 * A book can ship with many kinds of files - PDF, PowerPoint, audio, video,
 * printables. This file handles that list, its filter chips and the FAQ
 * accordion, and it also outputs the FAQ schema for Google.
 *
 * Everything is per-product meta. Any field left empty simply does not appear
 * on the page, so the code never needs to be touched.
 */
if (!defined('ABSPATH')) { exit; }


/* -------------------------------------------------------------------------
   1. CONFIG
   To add a new file type, add one line here. The filter chips, the admin
   fields, the Quick Add JSON and the AI prompt all update themselves.
   ------------------------------------------------------------------------- */

function pph_resource_types() {
    return array(
        'pdf'        => array('label' => 'PDF',          'group' => 'Read'),
        'ppt'        => array('label' => 'PowerPoint',   'group' => 'Teach'),
        'video'      => array('label' => 'Video',        'group' => 'Watch'),
        'audio'      => array('label' => 'Audio',        'group' => 'Listen'),
        'printable'  => array('label' => 'Printable',    'group' => 'Print'),
        'worksheet'  => array('label' => 'Worksheet',    'group' => 'Print'),
        'answer_key' => array('label' => 'Answer Key',   'group' => 'Teach'),
        'doc'        => array('label' => 'Editable Doc', 'group' => 'Teach'),
        'epub'       => array('label' => 'ePub',         'group' => 'Read'),
        'bonus'      => array('label' => 'Bonus',        'group' => 'Extra'),
    );
}

function pph_resource_field_keys() {
    return array('on', 'title', 'count', 'size', 'note');
}

function pph_faq_slots() { return 6; }


/* -------------------------------------------------------------------------
   2. ICONS
   ------------------------------------------------------------------------- */

function pph_res_icon($key) {
    $open  = '<svg class="pph-res-ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" ';
    $open .= 'stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">';

    switch ($key) {
        case 'pdf':
            $d = '<path d="M6 2h8l4 4v16H6z"/><path d="M14 2v4h4"/><path d="M9 13h6"/><path d="M9 17h4"/>';
            break;
        case 'ppt':
            $d = '<rect x="3" y="4" width="18" height="12" rx="1.5"/><path d="M12 16v4"/>'
               . '<path d="M8 20h8"/><path d="M8 8h5a2 2 0 0 1 0 4H8z"/>';
            break;
        case 'video':
            $d = '<rect x="2.5" y="5" width="14" height="14" rx="2"/><path d="M16.5 10l5-3v10l-5-3z"/>';
            break;
        case 'audio':
            $d = '<path d="M4 14v-4h3l4-3v14l-4-3H4z"/><path d="M16 9a4 4 0 0 1 0 6"/>'
               . '<path d="M19 6a8 8 0 0 1 0 12"/>';
            break;
        case 'printable':
            $d = '<path d="M7 9V3h10v6"/><rect x="3" y="9" width="18" height="7" rx="1.5"/>'
               . '<path d="M7 16h10v5H7z"/>';
            break;
        case 'worksheet':
            $d = '<rect x="4" y="3" width="16" height="18" rx="1.5"/><path d="M8 8h8"/>'
               . '<path d="M8 12h8"/><path d="M8 16h5"/>';
            break;
        case 'answer_key':
            $d = '<circle cx="8" cy="12" r="3.5"/><path d="M11.5 12h9"/><path d="M17 12v3.5"/>'
               . '<path d="M20 12v2.5"/>';
            break;
        case 'doc':
            $d = '<path d="M6 2h8l4 4v16H6z"/><path d="M14 2v4h4"/><path d="M9 12h6"/>'
               . '<path d="M9 16h6"/><path d="M9 8h3"/>';
            break;
        case 'epub':
            $d = '<path d="M4 5a2 2 0 0 1 2-2h5v18H6a2 2 0 0 1-2-2z"/>'
               . '<path d="M20 5a2 2 0 0 0-2-2h-5v18h5a2 2 0 0 0 2-2z"/>';
            break;
        case 'bonus':
            $d = '<path d="M12 3l2.6 5.6 6.1.7-4.5 4.2 1.2 6-5.4-3-5.4 3 1.2-6L3.3 9.3l6.1-.7z"/>';
            break;
        default:
            $d = '<rect x="4" y="3" width="16" height="18" rx="1.5"/><path d="M8 9h8"/><path d="M8 13h8"/>';
    }

    return $open . $d . '</svg>';
}


/* -------------------------------------------------------------------------
   3. FRONT-END DATA BUILDERS
   ------------------------------------------------------------------------- */

function pph_active_resources($post_id) {
    $out = array();
    foreach (pph_resource_types() as $key => $t) {
        if (!pph_is_on($post_id, 'res_' . $key . '_on')) { continue; }

        $out[] = array(
            'key'   => $key,
            'label' => $t['label'],
            'group' => $t['group'],
            'title' => pph_get($post_id, 'res_' . $key . '_title', $t['label']),
            'count' => pph_get($post_id, 'res_' . $key . '_count'),
            'size'  => pph_get($post_id, 'res_' . $key . '_size'),
            'note'  => pph_get($post_id, 'res_' . $key . '_note'),
        );
    }
    return $out;
}

function pph_faqs($post_id) {
    $out = array();
    for ($i = 1; $i <= pph_faq_slots(); $i++) {
        $q = pph_get($post_id, 'faq_q' . $i);
        $a = pph_get($post_id, 'faq_a' . $i);
        if ($q !== '' && $a !== '') {
            $out[] = array('q' => $q, 'a' => $a);
        }
    }
    return $out;
}

function pph_last_updated($post_id) {
    $manual = pph_get($post_id, 'last_updated');
    if ($manual !== '') { return $manual; }

    $mod = get_post_modified_time('U', true, $post_id);
    if (!$mod) { return ''; }
    return date_i18n('F Y', $mod);
}


/* -------------------------------------------------------------------------
   4. ADMIN  -  a second meta box
   The main box is left untouched. This is a separate box, but it reuses the
   nonce that the main box already prints on the page.
   ------------------------------------------------------------------------- */

add_action('add_meta_boxes', function () {
    add_meta_box(
        'pph_files_box',
        'PPH - Files Included, FAQ & Freshness',
        'pph_render_files_box',
        'product',
        'normal',
        'high'
    );
});

/** Small textarea helper, used for the FAQ answers. */
function pph_field_area($post_id, $key, $label, $placeholder = '', $rows = 3) {
    $val = pph_get($post_id, $key);
    echo '<p class="pph-adm-f pph-adm-wide">';
    echo '<label for="pph_' . esc_attr($key) . '"><strong>' . esc_html($label) . '</strong></label>';
    echo '<textarea id="pph_' . esc_attr($key) . '" name="pph[' . esc_attr($key) . ']"';
    echo ' rows="' . (int) $rows . '" placeholder="' . esc_attr($placeholder) . '"';
    echo ' style="width:100%">' . esc_textarea($val) . '</textarea>';
    echo '</p>';
}

function pph_render_files_box($post) {
    $id = $post->ID;
    ?>
    <div class="pph-adm">

      <div class="pph-adm-note">
        <strong>Files Included</strong> = everything the customer actually receives.
        Tick a type and only that card appears on the page, and its filter chip is
        created automatically. When you add PowerPoint, audio or video later, just
        tick that checkbox &mdash; no code changes are ever needed.
      </div>

      <h3>Files Included</h3>

      <?php foreach (pph_resource_types() as $key => $t) : ?>
        <div class="pph-adm-fmt">
          <span class="pph-adm-tag d"><?php echo esc_html($t['group']); ?></span>
          <?php pph_field_check($id, 'res_' . $key . '_on', $t['label'] . ' included'); ?>
          <div class="pph-adm-grid">
            <?php
            pph_field_text($id, 'res_' . $key . '_title', 'Card title',
                'e.g. Teaching Slides');
            pph_field_text($id, 'res_' . $key . '_count', 'How many files / pages',
                'e.g. 12 slides');
            pph_field_text($id, 'res_' . $key . '_size', 'Size or length',
                'e.g. 18.7 MB  or  24 min');
            pph_field_text($id, 'res_' . $key . '_note', 'One line note',
                'e.g. Editable in PowerPoint and Keynote', true);
            ?>
          </div>
        </div>
      <?php endforeach; ?>

      <h3>FAQ  (produces a rich result in Google)</h3>
      <div class="pph-adm-note">
        An FAQ only appears when <strong>both</strong> the question and the answer are
        filled in. These FAQs are also sent to Google as schema, so write the real
        questions customers ask &mdash; stuffing keywords here will not help.
      </div>

      <?php for ($i = 1; $i <= pph_faq_slots(); $i++) : ?>
        <div class="pph-adm-fmt">
          <?php
          pph_field_text($id, 'faq_q' . $i, 'Question ' . $i,
              'e.g. Is this a printable PDF or a physical book?', true);
          pph_field_area($id, 'faq_a' . $i, 'Answer ' . $i,
              'A direct answer, 1-3 lines.');
          ?>
        </div>
      <?php endfor; ?>

      <h3>Freshness</h3>
      <div class="pph-adm-grid">
        <?php
        pph_field_text($id, 'last_updated', 'Last updated (shown on the page)',
            'leave blank to use the edit date automatically');
        pph_field_text($id, 'edition', 'Edition',
            'e.g. 2nd Edition, Revised 2026');
        pph_field_text($id, 'access_note', 'Access note',
            'e.g. Lifetime access - free updates');
        ?>
      </div>

    </div>
    <?php
}


/* -------------------------------------------------------------------------
   5. SAVE  -  its own keys, its own handler
   ------------------------------------------------------------------------- */

add_action('save_post_product', function ($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) { return; }
    if (!isset($_POST['pph_nonce'])) { return; }
    if (!wp_verify_nonce($_POST['pph_nonce'], 'pph_save_' . $post_id)) { return; }
    if (!current_user_can('edit_post', $post_id)) { return; }

    $in = isset($_POST['pph']) && is_array($_POST['pph']) ? $_POST['pph'] : array();

    $keys = array('last_updated', 'edition', 'access_note');

    foreach (pph_resource_types() as $key => $t) {
        foreach (pph_resource_field_keys() as $f) {
            $keys[] = 'res_' . $key . '_' . $f;
        }
    }
    for ($i = 1; $i <= pph_faq_slots(); $i++) {
        $keys[] = 'faq_q' . $i;
        $keys[] = 'faq_a' . $i;
    }

    foreach ($keys as $key) {
        $raw = isset($in[$key]) ? $in[$key] : '';

        /* FAQ answers keep their line breaks; everything else is single line. */
        if (strpos($key, 'faq_a') === 0) {
            $val = sanitize_textarea_field($raw);
        } else {
            $val = sanitize_text_field($raw);
        }

        if ($val === '') {
            delete_post_meta($post_id, '_pph_' . $key);
        } else {
            update_post_meta($post_id, '_pph_' . $key, $val);
        }
    }
}, 15);


/* -------------------------------------------------------------------------
   6. FAQ SCHEMA  -  for Google
   ------------------------------------------------------------------------- */

add_action('wp_footer', function () {
    if (!is_singular('product')) { return; }

    $faqs = pph_faqs(get_the_ID());
    if (!$faqs) { return; }

    $items = array();
    foreach ($faqs as $f) {
        $items[] = array(
            '@type'          => 'Question',
            'name'           => wp_strip_all_tags($f['q']),
            'acceptedAnswer' => array(
                '@type' => 'Answer',
                'text'  => wp_strip_all_tags($f['a']),
            ),
        );
    }

    $schema = array(
        '@context'   => 'https://schema.org',
        '@type'      => 'FAQPage',
        'mainEntity' => $items,
    );

    echo '<script type="application/ld+json">';
    echo wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    echo '</script>' . "\n";
}, 30);
