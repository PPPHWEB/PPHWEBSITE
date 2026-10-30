<?php
/**
 * PPH 5.16.0 - FREE SAMPLE EMAIL POP-UP (optional email, saved leads)
 *
 * Flow on the book page:
 *   Download Free Sample  ->  pop-up asks for an email (optional)
 *     - Email entered     ->  saved, then the pop-up shows the Download button
 *     - Skip / X / Cancel ->  nothing saved, the pop-up shows the Download button
 *   Download button       ->  the existing same-site attachment endpoint
 *
 * Saved emails:
 *   1. always in the table {prefix}pph_sample_leads
 *      (Products > Sample Leads, with a CSV export)
 *   2. also sent to the Apps Script web app (Brevo list + Google Sheet)
 *   3. the action  pph_sample_lead_saved  fires for any other mail tool
 */

if (!defined('ABSPATH')) { exit; }

define('PPH_LEAD_DB_VER', '1');

function pph_lead_table() {
    global $wpdb;
    return $wpdb->prefix . 'pph_sample_leads';
}

/* ------------------------------------------------------------------ table */

function pph_lead_install() {
    global $wpdb;
    $table   = pph_lead_table();
    $charset = $wpdb->get_charset_collate();

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta("CREATE TABLE {$table} (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        email VARCHAR(190) NOT NULL,
        product_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
        product_title VARCHAR(255) NOT NULL DEFAULT '',
        source_url VARCHAR(255) NOT NULL DEFAULT '',
        created_at DATETIME NOT NULL,
        PRIMARY KEY  (id),
        UNIQUE KEY email_product (email, product_id),
        KEY created_at (created_at)
    ) {$charset};");

    update_option('pph_lead_db_ver', PPH_LEAD_DB_VER);
}

/* The plugin is uploaded as a zip, so there may be no activation hook run:
   create the table the first time the code loads, and again on a new version. */
add_action('init', function () {
    if (get_option('pph_lead_db_ver') !== PPH_LEAD_DB_VER) { pph_lead_install(); }
});

/* ------------------------------------------------------------------- ajax */

add_action('wp_ajax_nopriv_pph_sample_lead', 'pph_sample_lead_ajax');
add_action('wp_ajax_pph_sample_lead',        'pph_sample_lead_ajax');

function pph_sample_lead_ajax() {

    /* Honeypot: real visitors never see or fill this field. */
    if (!empty($_POST['pph_website'])) { wp_send_json_success(); }

    $email      = isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '';
    $product_id = isset($_POST['product_id']) ? absint($_POST['product_id']) : 0;

    if ($email === '' || !is_email($email)) {
        wp_send_json_error(array('message' => 'Please enter a valid email address, or choose Skip.'));
    }
    if (!$product_id || get_post_type($product_id) !== 'product') {
        wp_send_json_error(array('message' => 'Something went wrong. Please try again.'));
    }

    /* Light rate limit per visitor: 20 saves per hour. */
    $ip  = isset($_SERVER['REMOTE_ADDR']) ? (string) $_SERVER['REMOTE_ADDR'] : '';
    $key = 'pph_lead_rl_' . md5($ip);
    $n   = (int) get_transient($key);
    if ($n >= 20) {
        wp_send_json_error(array('message' => 'Too many tries. Please use Skip to download.'));
    }
    set_transient($key, $n + 1, HOUR_IN_SECONDS);

    global $wpdb;
    $title = get_the_title($product_id);
    $src   = isset($_POST['source_url']) ? esc_url_raw(wp_unslash($_POST['source_url'])) : '';

    /* The same email for the same book is stored once. */
    $wpdb->query($wpdb->prepare(
        'INSERT IGNORE INTO ' . pph_lead_table() .
        ' (email, product_id, product_title, source_url, created_at) VALUES (%s, %d, %s, %s, %s)',
        strtolower($email), $product_id, $title, substr($src, 0, 255), current_time('mysql')
    ));

    pph_lead_send_to_crm($email, $product_id, $title);

    do_action('pph_sample_lead_saved', strtolower($email), $product_id, $title);

    wp_send_json_success();
}

/* ----------------------------------------------- optional mail-tool bridges */

/* Brevo + Google Sheet, through the existing Apps Script web app (type=sample).
   The Apps Script adds the contact to the Brevo list and writes the Subscribers
   tab row, exactly like the newsletter and free PDF forms on the main site.
   No API key lives in WordPress. */
if (!defined('PPH_LEAD_WEBAPP_URL')) {
    define('PPH_LEAD_WEBAPP_URL', 'https://script.google.com/macros/s/AKfycby-ctua8nl14HyUy-SyIkhIsapNT6bR17j-ry2wbBRTzZULj7rCqtqyUsXXzB3Kl9JI/exec');
}

function pph_lead_send_to_crm($email, $product_id, $title) {
    $url = apply_filters('pph_lead_webapp_url', PPH_LEAD_WEBAPP_URL);
    if (!$url) { return; }

    /* Non-blocking: a slow or failing Google call never delays the visitor. */
    wp_remote_post($url, array(
        'timeout'     => 5,
        'blocking'    => false,
        'redirection' => 5,
        'body'        => array(
            'type'    => 'sample',
            'email'   => $email,
            'message' => $title,
        ),
    ));
}

/* ------------------------------------------------------- front-end assets */

add_action('wp_enqueue_scripts', function () {
    if (!function_exists('is_product') || !is_product()) { return; }

    $dir = plugin_dir_path(dirname(__FILE__));
    $url = plugin_dir_url(dirname(__FILE__));
    $f   = 'assets/pph-sample-lead.js';
    if (!file_exists($dir . $f)) { return; }

    wp_enqueue_script('pph-sample-lead', $url . $f, array(), PPH_VER . '.' . filemtime($dir . $f), true);
    wp_localize_script('pph-sample-lead', 'PPH_LEAD', array(
        'ajax' => admin_url('admin-ajax.php'),
    ));
}, 110);

/* ----------------------------------------------------------- admin screen */

add_action('admin_menu', function () {
    add_submenu_page(
        'edit.php?post_type=product',
        'Sample Leads',
        'Sample Leads',
        'manage_woocommerce',
        'pph-sample-leads',
        'pph_lead_admin_page'
    );
});

add_action('admin_init', function () {
    if (!isset($_GET['pph_lead_csv'])) { return; }
    if (!current_user_can('manage_woocommerce')) { return; }
    if (!wp_verify_nonce(isset($_GET['_wpnonce']) ? $_GET['_wpnonce'] : '', 'pph_lead_csv')) { return; }

    global $wpdb;
    $rows = $wpdb->get_results('SELECT email, product_title, product_id, created_at FROM ' . pph_lead_table() . ' ORDER BY id DESC', ARRAY_A);

    nocache_headers();
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="pph-sample-leads-' . gmdate('Y-m-d') . '.csv"');

    $out = fopen('php://output', 'w');
    fputcsv($out, array('Email', 'Book', 'Product ID', 'Date'));
    foreach ($rows as $r) {
        /* Guard against spreadsheet formula injection. */
        $email = preg_match('/^[=+\-@]/', $r['email']) ? "'" . $r['email'] : $r['email'];
        fputcsv($out, array($email, $r['product_title'], $r['product_id'], $r['created_at']));
    }
    fclose($out);
    exit;
});

function pph_lead_admin_page() {
    if (!current_user_can('manage_woocommerce')) { return; }
    global $wpdb;
    $table = pph_lead_table();

    if (isset($_POST['pph_lead_delete'], $_POST['lead_id']) && check_admin_referer('pph_lead_del')) {
        $wpdb->delete($table, array('id' => absint($_POST['lead_id'])), array('%d'));
        echo '<div class="notice notice-success"><p>Deleted.</p></div>';
    }

    $total = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
    $rows  = $wpdb->get_results("SELECT * FROM {$table} ORDER BY id DESC LIMIT 500");
    $csv   = wp_nonce_url(admin_url('edit.php?post_type=product&page=pph-sample-leads&pph_lead_csv=1'), 'pph_lead_csv');
    ?>
    <div class="wrap">
      <h1>Sample Leads</h1>
      <p>Emails given by visitors who chose to share one before downloading a free sample.
         Visitors who clicked Skip are not listed. Total: <strong><?php echo (int) $total; ?></strong></p>
      <p><a class="button button-primary" href="<?php echo esc_url($csv); ?>">Export CSV</a></p>

      <table class="widefat striped" style="max-width:900px">
        <thead><tr><th>Email</th><th>Book</th><th>Date</th><th></th></tr></thead>
        <tbody>
        <?php if (!$rows) : ?>
          <tr><td colspan="4">No emails yet.</td></tr>
        <?php else : foreach ($rows as $r) : ?>
          <tr>
            <td><?php echo esc_html($r->email); ?></td>
            <td><?php echo esc_html($r->product_title); ?></td>
            <td><?php echo esc_html($r->created_at); ?></td>
            <td>
              <form method="post" onsubmit="return confirm('Delete this email?');">
                <?php wp_nonce_field('pph_lead_del'); ?>
                <input type="hidden" name="lead_id" value="<?php echo (int) $r->id; ?>">
                <button class="button-link-delete" name="pph_lead_delete" value="1">Delete</button>
              </form>
            </td>
          </tr>
        <?php endforeach; endif; ?>
        </tbody>
      </table>
      <?php if ($total > 500) : ?><p>Showing the latest 500. Use Export CSV for the full list.</p><?php endif; ?>
    </div>
    <?php
}
