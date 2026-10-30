<?php
/**
 * Single product template  -  PPH 5.2
 *
 * Layout:
 *   HERO   thumbnail rail | 3D cover | title, badge, author, formats,
 *          buy panel, and an ALWAYS VISIBLE retailer strip with logos
 *   PANEL  tabs (Sample Pages / About / Details / Reviews) | related sidebar
 */
if (!defined('ABSPATH')) { exit; }

get_header('shop');

while (have_posts()) :
  the_post();
  global $product;
  if (!is_a($product, 'WC_Product')) {
    $product = wc_get_product(get_the_ID());
  }
  if (!$product) { continue; }

  $pid  = get_the_ID();
  $cats = wc_get_product_category_list($pid);

  /* ---- plugin data -------------------------------------------------- */
  $badge    = pph_get($pid, 'badge');
  $subtitle = pph_get($pid, 'subtitle');
  $formats  = pph_active_formats($pid);
  $all_rt   = pph_all_retailers($pid);
  $details  = pph_active_details($pid, $product);

  /* 5.9.0: shared facts first, then one block per format. The block follows the
     format card above, so paperback shows its own ISBN and hardcover its own. */
  $dbf = function_exists('pph_details_by_format')
    ? pph_details_by_format($pid, $product)
    : array('common' => $details, 'formats' => array());
  $detail_common  = isset($dbf['common'])  ? $dbf['common']  : array();
  $detail_formats = isset($dbf['formats']) ? $dbf['formats'] : array();
  $has_details    = (!empty($detail_common) || !empty($detail_formats));

  /* ---- 5.5: files included, FAQ, freshness ------------------------- */
  $resources = function_exists('pph_active_resources') ? pph_active_resources($pid) : array();
  $faqs      = function_exists('pph_faqs')             ? pph_faqs($pid)             : array();
  $updated   = function_exists('pph_last_updated')     ? pph_last_updated($pid)     : '';
  $edition   = pph_get($pid, 'edition');
  $access    = pph_get($pid, 'access_note');

  /* Filter chip groups - only for the resources that actually exist. */
  $res_groups = array();
  foreach ($resources as $r) {
    if (!isset($res_groups[$r['group']])) { $res_groups[$r['group']] = 0; }
    $res_groups[$r['group']]++;
  }

  /* ---- author: plugin field first, then an "Author" attribute ------- */
  $author = pph_get($pid, 'author');
  if ($author === '') {
    foreach ($product->get_attributes() as $attr) {
      if (!is_a($attr, 'WC_Product_Attribute')) { continue; }
      if ($attr->get_variation()) { continue; }
      $label = wc_attribute_label($attr->get_name());
      if (stripos($label, 'author') === false) { continue; }
      if ($attr->is_taxonomy()) {
        $vals = wc_get_product_terms($pid, $attr->get_name(), array('fields' => 'names'));
      } else {
        $vals = $attr->get_options();
      }
      $author = is_array($vals) ? implode(', ', $vals) : (string) $vals;
      break;
    }
  }

  /* ---- gallery = sample pages + thumbnail rail ---------------------- */
  $gallery_ids = $product->get_gallery_image_ids();

  /* 5.8.0: the back cover is a cover, not a sample page. Any image whose file
     name, title or alt text mentions a back cover is kept out of the Sample
     Pages tab. It still appears in the side rail and in the large viewer. */
  if (!function_exists('pph_is_back_cover')) {
      function pph_is_back_cover($attachment_id) {
          $bits = array(
              (string) get_post_field('post_name',  $attachment_id),
              (string) get_post_field('post_title', $attachment_id),
              (string) get_post_meta($attachment_id, '_wp_attachment_image_alt', true),
          );
          $hay = strtolower(implode(' ', $bits));
          $hay = str_replace(array('-', '_'), ' ', $hay);

          foreach (array('back cover', 'backcover', 'back page', 'rear cover') as $needle) {
              if (strpos($hay, $needle) !== false) { return true; }
          }
          return false;
      }
  }

  $sample_ids = array();
  foreach ($gallery_ids as $pph_gid) {
      if (!pph_is_back_cover($pph_gid)) { $sample_ids[] = $pph_gid; }
  }
  $cover_full  = has_post_thumbnail($pid)
    ? wp_get_attachment_image_url(get_post_thumbnail_id($pid), 'full')
    : '';
  ?>

  <div class="pph-pp">
    <?php if (function_exists('woocommerce_output_all_notices')) {
      echo '<div class="pph-pp-notices">';
      woocommerce_output_all_notices();
      echo '</div>';
    } ?>

    <div class="pph-pp-card">

      <!-- ============================ CREAM HERO ============================ -->
      <div class="pph-pp-hero<?php echo $gallery_ids ? ' has-rail' : ''; ?>">

        <?php if ($gallery_ids) : ?>
          <div class="pph-rail">
            <?php if ($cover_full) : ?>
              <button type="button" class="pph-thumb is-on"
                      data-full="<?php echo esc_url($cover_full); ?>"
                      data-pph-zoom="<?php echo esc_url($cover_full); ?>"
                      data-pph-group="book"
                      data-pph-kind="front"
                      aria-label="Front cover">
                <?php echo get_the_post_thumbnail($pid, 'woocommerce_gallery_thumbnail'); ?>
              </button>
            <?php endif; ?>

            <?php foreach (array_slice($gallery_ids, 0, 5) as $i => $gid) : ?>
              <button type="button" class="pph-thumb"
                      data-full="<?php echo esc_url(wp_get_attachment_image_url($gid, 'full')); ?>"
                      data-pph-zoom="<?php echo esc_url(wp_get_attachment_image_url($gid, 'full')); ?>"
                      data-pph-group="book"
                      data-pph-kind="<?php echo pph_is_back_cover($gid) ? 'back' : 'spread'; ?>"
                      aria-label="<?php echo esc_attr('Sample page ' . ($i + 1)); ?>">
                <?php echo wp_get_attachment_image($gid, 'woocommerce_gallery_thumbnail'); ?>
              </button>
            <?php endforeach; ?>

            <?php if ($cover_full) : ?>
              <a href="#" class="pph-rail-more" data-pph-zoom="<?php echo esc_url($cover_full); ?>"
                 data-pph-group="book" data-pph-kind="front">
                <?php echo pph_svg('ebook'); ?>
                View larger
              </a>
            <?php endif; ?>
          </div>
        <?php endif; ?>

        <div class="pph-pp-cover">
          <div class="pph-book<?php echo $cover_full ? ' is-zoomable' : ''; ?>"
               <?php if ($cover_full) : ?>
                 data-pph-zoom="<?php echo esc_url($cover_full); ?>"
                 data-pph-group="book" data-pph-kind="front"
                 role="button" tabindex="0" aria-label="View larger"
               <?php endif; ?>>
            <?php
            if (has_post_thumbnail()) {
              echo get_the_post_thumbnail($pid, 'large', array('class' => 'pph-pp-cover-img'));
            } else {
              echo wc_placeholder_img('large');
            }
            ?>
          </div>
        </div>

        <div class="pph-pp-info">

          <?php
          /* 5.6.7: every level of the category line is a real link, so a click
             on Books opens the Books listing with its sub categories on top. */
          $pph_trail = function_exists('pph_category_trail') ? pph_category_trail($pid) : $cats;
          if ($pph_trail) : ?>
            <div class="pph-pp-cat"><?php echo wp_kses_post($pph_trail); ?></div>
          <?php endif; ?>

          <?php
          /* 5.8.0: the title and the subtitle are one heading. The subtitle is
             printed inside the H1 so it carries the same face, weight and
             colour, and a screen reader reads them as one name. */
          ?>
          <div class="pph-title-row">
            <h1 class="pph-pp-title">
              <span class="pph-pp-titlemain"><?php the_title(); ?></span>
              <?php if ($subtitle !== '') : ?>
                <span class="pph-pp-subtitle"><?php echo esc_html($subtitle); ?></span>
              <?php endif; ?>
            </h1>
            <?php if ($badge !== '') : ?>
              <span class="pph-badge"><?php echo esc_html($badge); ?></span>
            <?php endif; ?>
          </div>

          <?php if ($author !== '') : ?>
            <div class="pph-pp-author"><?php echo esc_html($author); ?></div>
          <?php endif; ?>

          <div class="pph-pp-short">
            <?php echo apply_filters('woocommerce_short_description', $product->get_short_description()); ?>
          </div>

          <?php if ($updated !== '' || $edition !== '' || $access !== '') : ?>
            <div class="pph-meta-strip">
              <?php if ($edition !== '') : ?>
                <span class="pph-meta-pill"><?php echo esc_html($edition); ?></span>
              <?php endif; ?>
              <?php if ($updated !== '') : ?>
                <span class="pph-meta-pill">Updated <?php echo esc_html($updated); ?></span>
              <?php endif; ?>
              <?php if ($access !== '') : ?>
                <span class="pph-meta-pill"><?php echo esc_html($access); ?></span>
              <?php endif; ?>
            </div>
          <?php endif; ?>

          <?php if ($formats) : ?>

            <!-- ---------------- format cards (always visible) ---------------- -->
            <div class="pph-pp-fmtlabel" id="pph-buy-anchor">Choose your format</div>

            <div class="pph-fmts" role="radiogroup" aria-label="Choose your format">
              <?php foreach ($formats as $key => $f) : ?>
                <div class="pph-fmt pph-fmt--<?php echo esc_attr($f['type']); ?>"
                     role="radio" tabindex="0" aria-checked="false"
                     data-key="<?php echo esc_attr($key); ?>"
                     data-type="<?php echo esc_attr($f['type']); ?>"
                     data-label="<?php echo esc_attr($f['label']); ?>"
                     data-attr="<?php echo esc_attr($f['attr']); ?>">
                  <span class="pph-fmt-ic"><?php echo pph_svg($key); ?></span>
                  <span class="pph-fmt-label"><?php echo esc_html($f['label']); ?></span>
                  <?php if ($f['sub'] !== '') : ?>
                    <span class="pph-fmt-sub"><?php echo esc_html($f['sub']); ?></span>
                  <?php endif; ?>
                  <span class="pph-fmt-tick" aria-hidden="true">&#10003;</span>
                </div>
              <?php endforeach; ?>

              <?php if (function_exists('pph_free_sample_enabled') && pph_free_sample_enabled($pid)) : ?>
                <div class="pph-fmt pph-free-sample-card"
                     role="radio" tabindex="0" aria-checked="false"
                     data-key="free-sample" data-type="free-sample"
                     data-label="Free Sample">
                  <span class="pph-fmt-ic"><?php echo pph_svg('pdf'); ?></span>
                  <span class="pph-fmt-label">Free Sample</span>
                  <span class="pph-fmt-sub">PDF</span>
                  <span class="pph-fmt-tick" aria-hidden="true">&#10003;</span>
                </div>
              <?php endif; ?>
            </div>

            <!-- ---------------- action panel per format ---------------- -->
            <?php foreach ($formats as $key => $f) : ?>
              <div class="pph-act" data-for="<?php echo esc_attr($key); ?>" hidden>

                <?php if ($f['type'] === 'digital') : ?>

                  <div class="pph-pp-buybox">
                    <div class="pph-pp-price"><?php echo $product->get_price_html(); ?></div>
                    <?php woocommerce_template_single_add_to_cart(); ?>
                    <div class="pph-instant">
                      <?php echo pph_svg('ext'); ?>
                      Secure payment &middot; Instant email delivery
                    </div>
                  </div>

                <?php else : ?>

                  <div class="pph-print-note">
                    <?php echo pph_svg('ext'); ?>
                    <span>
                      <strong><?php echo esc_html($f['label']); ?></strong> is printed and posted by our
                      retail partners, so it is bought on their store, not here. Pick a store
                      below and the live price, the delivery date and the taxes are all shown there.
                    </span>
                  </div>

                <?php endif; ?>
              </div>
            <?php endforeach; ?>

            <?php if (function_exists('pph_free_sample_enabled') && pph_free_sample_enabled($pid)) : ?>
              <div class="pph-act pph-free-sample-act" data-for="free-sample" hidden>
                <a class="pph-free-sample-download" href="<?php echo esc_url(pph_free_sample_download_url($pid)); ?>" download>
                  <span aria-hidden="true">&#8595;</span> Download Free Sample
                </a>
              </div>
            <?php endif; ?>

            <!-- ------- retailer strip: ALWAYS visible, with brand logos ------- -->
            <?php if ($all_rt) : ?>
              <div class="pph-rt-strip">
                <div class="pph-rt-label">Buy now from your favorite retailer</div>

                <div class="pph-rt-row">
                  <?php foreach ($all_rt as $l) : ?>
                    <a class="pph-rt pph-rt--<?php echo esc_attr($l['key']); ?>"
                       href="<?php echo esc_url($l['url']); ?>"
                       data-fmts="<?php echo esc_attr(implode(' ', $l['fmts'])); ?>"
                       target="_blank" rel="noopener nofollow sponsored"
                       title="<?php echo esc_attr($l['label']); ?>">
                      <?php echo pph_logo($l['key']); ?>
                      <span class="pph-rt-cap"><?php echo esc_html($l['label']); ?></span>
                    </a>
                  <?php endforeach; ?>
                </div>

                <div class="pph-rt-note">
                  On the retailer&#39;s site you will see their price and delivery details.
                </div>
              </div>
            <?php endif; ?>

          <?php else : ?>

            <!-- No formats configured yet: fall back to plain WooCommerce -->
            <div class="pph-pp-fmtlabel">Choose format</div>
            <div class="pph-pp-buybox">
              <div class="pph-pp-price"><?php echo $product->get_price_html(); ?></div>
              <?php woocommerce_template_single_add_to_cart(); ?>
            </div>

          <?php endif; ?>

        </div>
      </div>

      <!-- ============================ WHITE PANEL =========================== -->
      <div class="pph-pp-panel">

        <?php
        /* Related books for the sidebar */
        $more_args = array(
          'status'  => 'publish',
          'limit'   => 8,
          'exclude' => array($pid),
          'orderby' => 'date',
          'order'   => 'DESC',
        );
        $books_term = get_term_by('slug', 'books', 'product_cat');
        if (!$books_term) { $books_term = get_term_by('name', 'Books', 'product_cat'); }
        if ($books_term && !is_wp_error($books_term)) {
          $more_args['category'] = array($books_term->slug);
        }
        $more = wc_get_products($more_args);
        ?>

        <div class="pph-pp-panel-grid<?php echo empty($more) ? ' no-side' : ''; ?>">

          <div class="pph-main-col">

            <div class="pph-tabs" role="tablist">
              <?php if ($has_details) : ?>
                <button class="pph-tab" role="tab" data-tab="details">Details</button>
              <?php endif; ?>
              <button class="pph-tab" role="tab" data-tab="about">About This Book</button>
              <?php if ($sample_ids) : ?>
                <button class="pph-tab" role="tab" data-tab="sample">Sample Pages</button>
              <?php endif; ?>
              <?php if ($resources) : ?>
                <button class="pph-tab" role="tab" data-tab="included">What&#39;s Included</button>
              <?php endif; ?>
              <?php if ($faqs) : ?>
                <button class="pph-tab" role="tab" data-tab="faq">FAQ</button>
              <?php endif; ?>
            </div>

            <?php if ($sample_ids) : ?>
              <div class="pph-panel" data-panel="sample" hidden>
                <div class="pph-samples">
                  <?php foreach ($sample_ids as $gid) :
                    $full = wp_get_attachment_image_url($gid, 'full'); ?>
                    <a href="<?php echo esc_url($full); ?>" class="pph-sample"
                       data-pph-zoom="<?php echo esc_url($full); ?>"
                       data-pph-group="sample">
                      <?php echo wp_get_attachment_image($gid, 'large'); ?>
                    </a>
                  <?php endforeach; ?>
                </div>
              </div>
            <?php endif; ?>

            <?php if ($resources) : ?>
              <div class="pph-panel" data-panel="included" hidden>
                <div class="pph-res-wrap">

                  <div class="pph-res-bar">
                    <button type="button" class="pph-chip is-on" data-group="all">
                      All files <span class="pph-chip-n"><?php echo count($resources); ?></span>
                    </button>
                    <?php foreach ($res_groups as $g => $n) : ?>
                      <button type="button" class="pph-chip" data-group="<?php echo esc_attr($g); ?>">
                        <?php echo esc_html($g); ?>
                        <span class="pph-chip-n"><?php echo (int) $n; ?></span>
                      </button>
                    <?php endforeach; ?>

                    <label class="pph-res-sort">
                      Sort
                      <select>
                        <option value="default">Recommended</option>
                        <option value="name">Name (A to Z)</option>
                        <option value="type">File type</option>
                      </select>
                    </label>
                  </div>

                  <div class="pph-res-grid">
                    <?php foreach ($resources as $i => $r) : ?>
                      <div class="pph-res-card"
                           data-group="<?php echo esc_attr($r['group']); ?>"
                           data-label="<?php echo esc_attr($r['label']); ?>"
                           data-name="<?php echo esc_attr($r['title']); ?>"
                           data-order="<?php echo (int) $i; ?>">
                        <?php echo pph_res_icon($r['key']); ?>
                        <div class="pph-res-body">
                          <div class="pph-res-t"><?php echo esc_html($r['title']); ?></div>
                          <div class="pph-res-m">
                            <span class="pph-res-tag"><?php echo esc_html($r['label']); ?></span>
                            <?php if ($r['count'] !== '') : ?>
                              <span><?php echo esc_html($r['count']); ?></span>
                            <?php endif; ?>
                            <?php if ($r['size'] !== '') : ?>
                              <span><?php echo esc_html($r['size']); ?></span>
                            <?php endif; ?>
                          </div>
                          <?php if ($r['note'] !== '') : ?>
                            <div class="pph-res-note"><?php echo esc_html($r['note']); ?></div>
                          <?php endif; ?>
                        </div>
                      </div>
                    <?php endforeach; ?>
                  </div>

                  <div class="pph-res-empty" hidden>
                    No files match this filter. Please choose another chip.
                  </div>

                </div>
              </div>
            <?php endif; ?>

            <div class="pph-panel" data-panel="about" hidden>
              <div class="pph-pp-desc"><?php the_content(); ?></div>
            </div>

            <?php if ($has_details) : ?>
              <div class="pph-panel" data-panel="details" hidden>

                <?php if (!empty($detail_common)) : ?>
                  <section class="pph-dl-block">
                    <h3 class="pph-dl-h">Book information</h3>
                    <div class="pph-dl">
                      <?php foreach ($detail_common as $label => $val) : ?>
                        <div class="pph-dl-row">
                          <div class="pph-dl-k"><?php echo esc_html($label); ?></div>
                          <div class="pph-dl-v"><?php echo esc_html($val); ?></div>
                        </div>
                      <?php endforeach; ?>
                    </div>
                  </section>
                <?php endif; ?>

                <?php if (count($detail_formats) > 1) : ?>
                  <p class="pph-dl-hint">These details follow the format you pick above.</p>
                <?php endif; ?>

                <?php foreach ($detail_formats as $fkey => $blk) : ?>
                  <section class="pph-dl-block pph-dl-fmt"
                           data-for="<?php echo esc_attr($fkey); ?>" hidden>
                    <h3 class="pph-dl-h"><?php echo esc_html($blk['label']); ?> edition</h3>
                    <div class="pph-dl">
                      <?php foreach ($blk['rows'] as $label => $val) : ?>
                        <div class="pph-dl-row">
                          <div class="pph-dl-k"><?php echo esc_html($label); ?></div>
                          <div class="pph-dl-v"><?php echo esc_html($val); ?></div>
                        </div>
                      <?php endforeach; ?>
                    </div>
                  </section>
                <?php endforeach; ?>

              </div>
            <?php endif; ?>

            <?php if ($faqs) : ?>
              <div class="pph-panel" data-panel="faq" hidden>
                <div class="pph-faq">
                  <?php foreach ($faqs as $f) : ?>
                    <div class="pph-faq-item">
                      <button type="button" class="pph-faq-q" aria-expanded="false">
                        <span><?php echo esc_html($f['q']); ?></span>
                        <span class="pph-faq-sign" aria-hidden="true">+</span>
                      </button>
                      <div class="pph-faq-a" hidden>
                        <?php echo wpautop(esc_html($f['a'])); ?>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>
              </div>
            <?php endif; ?>

            <?php if (comments_open() || get_comments_number()) : ?>
              <section class="pph-reviews-sec">
                <h2 class="pph-pp-sec">Reviews</h2>
                <div class="pph-pp-reviews"><?php comments_template(); ?></div>
              </section>
            <?php endif; ?>

          </div>

          <?php if (!empty($more)) : ?>
            <aside class="pph-side">
              <h2 class="pph-pp-sec">Related books</h2>
              <?php $i = 0; foreach ($more as $rp) : if ($i++ >= 4) break; ?>
                <a class="pph-side-book" href="<?php echo esc_url(get_permalink($rp->get_id())); ?>">
                  <div class="pph-side-cover"><?php echo $rp->get_image('medium'); ?></div>
                  <div class="pph-side-meta">
                    <div class="pph-side-title"><?php echo esc_html($rp->get_name()); ?></div>
                    <div class="pph-side-price"><?php echo $rp->get_price_html(); ?></div>
                    <?php if ($rp->get_average_rating() > 0) : ?>
                      <div class="pph-side-rate">
                        <span class="pph-stars">&#9733;</span>
                        <?php echo esc_html(number_format((float) $rp->get_average_rating(), 1)); ?>
                        <span class="pph-side-count">(<?php echo esc_html($rp->get_review_count()); ?>)</span>
                      </div>
                    <?php endif; ?>
                  </div>
                </a>
              <?php endforeach; ?>

              <?php if ($books_term && !is_wp_error($books_term)) : ?>
                <a class="pph-side-all" href="<?php echo esc_url(get_term_link($books_term)); ?>">
                  View all related books
                </a>
              <?php endif; ?>
            </aside>
          <?php endif; ?>

        </div>
      </div>

    </div>
  </div>

  <!-- ---- sticky buy bar: mobile only, once the hero has scrolled away ---- -->
  <div class="pph-buybar">
    <div class="pph-buybar-cover">
      <?php
      if (has_post_thumbnail($pid)) {
        echo get_the_post_thumbnail($pid, 'thumbnail');
      }
      ?>
    </div>
    <div class="pph-buybar-info">
      <div class="pph-buybar-t"><?php the_title(); ?></div>
      <?php if (!pph_is_print_only($pid)) : ?>
        <div class="pph-buybar-p"><?php echo wp_kses_post($product->get_price_html()); ?></div>
      <?php else : ?>
        <div class="pph-buybar-p">Available from our retail partners</div>
      <?php endif; ?>
    </div>
    <a class="pph-buybar-btn" href="#pph-buy-anchor">Choose format</a>
  </div>

  <?php
endwhile;

get_footer('shop');
