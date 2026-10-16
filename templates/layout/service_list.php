<?php

$statuses = ['publish', 'draft', 'trash'];
$args = [
    'post_type'      => 'mpwpb_item',
    'post_status'    => $statuses,
    'posts_per_page' => -1,
];

$query = new WP_Query($args);

$count_service = wp_count_posts('mpwpb_item');
$publish = isset($count_service->publish) ? $count_service->publish : 0;
$draft   = isset($count_service->draft) ? $count_service->draft : 0;
$trash   = isset($count_service->trash) ? $count_service->trash : 0;
$total   = $publish + $draft + $trash;
// "All Services" excludes trashed items (matches native WP list-table semantics).
$active_total = $publish + $draft;
$add_new_link = admin_url('post-new.php?post_type=mpwpb_item');


// ---- Stat cards (top of the Services page) ----
if (!function_exists('mpwpb_sl_admin_page_url')) {
    /** First registered admin page among $slugs (Pro and free register different order screens). */
    function mpwpb_sl_admin_page_url($slugs) {
        global $submenu;
        $registered = array();
        foreach ((array) $submenu as $items) {
            foreach ((array) $items as $item) {
                if (isset($item[2])) { $registered[$item[2]] = true; }
            }
        }
        foreach ($slugs as $slug) {
            if (isset($registered[$slug])) {
                return admin_url('edit.php?post_type=mpwpb_item&page=' . $slug);
            }
        }
        return '';
    }
}
if (!function_exists('mpwpb_sl_stats')) {
    /**
     * Figures for the stat cards, each matching its label:
     * revenue = orders created since the 1st of this month; upcoming = booking
     * dates in the next 7 days; customers = distinct people who have booked.
     */
    function mpwpb_sl_stats() {
        global $wpdb;
        $skip = array('cancelled', 'refunded', 'failed', 'trash');
        $tz = wp_timezone();
        $now = new DateTimeImmutable('now', $tz);
        $month_start = $now->modify('first day of this month')->setTime(0, 0);
        $stats = array('revenue' => 0.0, 'orders' => 0, 'upcoming' => 0, 'next' => null, 'customers' => 0, 'month_start' => $month_start, 'now' => $now);

        if (MPWPB_Global_Function::is_wc_payment_mode() && function_exists('wc_get_orders')) {
            $orders = wc_get_orders(array(
                'status'       => array('processing', 'completed', 'on-hold', 'pending'),
                'limit'        => -1,
                'date_created' => $month_start->getTimestamp() . '...' . $now->getTimestamp(),
            ));
            foreach ($orders as $order) {
                $stats['revenue'] += (float) $order->get_total();
            }
            $stats['orders'] = count($orders);
        } else {
            // Native checkout: sum each order once from its bookings' totals.
            $rows = $wpdb->get_results($wpdb->prepare(
                "SELECT p.ID, MAX(CASE WHEN m.meta_key='mpwpb_order_id' THEN m.meta_value END) AS oid,
                        MAX(CASE WHEN m.meta_key='mpwpb_tp' THEN m.meta_value END) AS tp,
                        MAX(CASE WHEN m.meta_key='mpwpb_order_status' THEN m.meta_value END) AS st
                 FROM {$wpdb->posts} p JOIN {$wpdb->postmeta} m ON m.post_id = p.ID
                 WHERE p.post_type = 'mpwpb_booking' AND p.post_status = 'publish' AND p.post_date >= %s
                 GROUP BY p.ID", $month_start->format('Y-m-d H:i:s')));
            $seen = array();
            foreach ($rows as $r) {
                if (in_array((string) $r->st, $skip, true)) { continue; }
                $key = $r->oid ? 'o' . $r->oid : 'b' . $r->ID;
                if (isset($seen[$key])) { continue; }
                $seen[$key] = true;
                $stats['revenue'] += (float) $r->tp;
            }
            $stats['orders'] = count($seen);
        }

        // Upcoming bookings (mpwpb_date is stored in site time, "Y-m-d H:i").
        $until = $now->modify('+7 days');
        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT d.meta_value AS dt, s.meta_value AS st
             FROM {$wpdb->postmeta} d
             JOIN {$wpdb->posts} p ON p.ID = d.post_id AND p.post_type = 'mpwpb_booking' AND p.post_status = 'publish'
             LEFT JOIN {$wpdb->postmeta} s ON s.post_id = d.post_id AND s.meta_key = 'mpwpb_order_status'
             WHERE d.meta_key = 'mpwpb_date' AND d.meta_value >= %s AND d.meta_value <= %s
             ORDER BY d.meta_value ASC", $now->format('Y-m-d H:i'), $until->format('Y-m-d H:i')));
        foreach ($rows as $r) {
            if (in_array((string) $r->st, $skip, true)) { continue; }
            $stats['upcoming']++;
            if (!$stats['next']) {
                $stats['next'] = date_create($r->dt, $tz) ?: null;
            }
        }

        // Customers: distinct billing emails (or account ids) across all bookings.
        $stats['customers'] = (int) $wpdb->get_var(
            "SELECT COUNT(DISTINCT LOWER(TRIM(m.meta_value)))
             FROM {$wpdb->postmeta} m JOIN {$wpdb->posts} p ON p.ID = m.post_id
             WHERE p.post_type = 'mpwpb_booking' AND p.post_status = 'publish'
               AND m.meta_key = 'mpwpb_billing_email' AND m.meta_value <> ''");
        return $stats;
    }
}
$mpwpb_stats = mpwpb_sl_stats();
$mpwpb_orders_url = mpwpb_sl_admin_page_url(array('mpwpb_order_list', 'mpwpb_orders'));
$mpwpb_bookings_url = mpwpb_sl_admin_page_url(array('mpwpb_service_queue', 'mpwpb_order_list', 'mpwpb_orders'));
?>

<div class="mpwpv_service_list_container">

    <div class="mpwpv_service_list_header">
        <div class="mpwpv_service_list_header-titles">
            <h1><?php esc_html_e( 'Service Management', 'service-booking-manager')?> <i class="fas fa-star mpwpv_service_list_header-sparkle"></i></h1>
            <p><?php esc_html_e( 'Manage your services and track performance', 'service-booking-manager')?></p>
        </div>
    </div>

    <div class="mpwpb-st" aria-label="<?php esc_attr_e('At a glance', 'service-booking-manager'); ?>">
        <article class="mpwpb-st__card">
            <div class="mpwpb-st__head"><span class="mpwpb-st__icon is-blue" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l8 4.5v9L12 21l-8-4.5v-9z"/><path d="M12 12l8-4.5M12 12v9M12 12L4 7.5"/></svg></span><span class="mpwpb-st__label"><?php esc_html_e('Services', 'service-booking-manager'); ?></span></div>
            <div class="mpwpb-st__value"><?php echo esc_html($active_total); ?></div>
            <div class="mpwpb-st__foot"><span><?php
                /* translators: 1: published count, 2: draft count */
                echo esc_html(sprintf(__('%1$d published · %2$d drafts', 'service-booking-manager'), $publish, $draft)); ?></span><a href="<?php echo esc_url($add_new_link); ?>" data-sv-add><?php esc_html_e('Add', 'service-booking-manager'); ?></a></div>
        </article>
        <article class="mpwpb-st__card">
            <div class="mpwpb-st__head"><span class="mpwpb-st__icon is-purple" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 17l6-6 4 4 8-8"/><path d="M14 7h7v7"/></svg></span><span class="mpwpb-st__label"><?php esc_html_e('Revenue this month', 'service-booking-manager'); ?></span></div>
            <div class="mpwpb-st__value"><?php echo wp_kses_post(MPWPB_Global_Function::format_price($mpwpb_stats['revenue'])); ?></div>
            <div class="mpwpb-st__foot"><span><?php
                $mpwpb_range = wp_date('M j', $mpwpb_stats['month_start']->getTimestamp()) . '–' . wp_date('j', $mpwpb_stats['now']->getTimestamp());
                /* translators: 1: number of orders, 2: date range such as "Oct 1–15" */
                echo esc_html(sprintf(_n('%1$d order · %2$s', '%1$d orders · %2$s', $mpwpb_stats['orders'], 'service-booking-manager'), $mpwpb_stats['orders'], $mpwpb_range)); ?></span><?php if ($mpwpb_orders_url) { ?><a href="<?php echo esc_url($mpwpb_orders_url); ?>"><?php esc_html_e('Orders', 'service-booking-manager'); ?></a><?php } ?></div>
        </article>
        <article class="mpwpb-st__card">
            <div class="mpwpb-st__head"><span class="mpwpb-st__icon is-orange" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18"/><path d="M9 15l2 2 4-4"/></svg></span><span class="mpwpb-st__label"><?php esc_html_e('Bookings next 7 days', 'service-booking-manager'); ?></span></div>
            <div class="mpwpb-st__value"><?php echo esc_html($mpwpb_stats['upcoming']); ?></div>
            <div class="mpwpb-st__foot"><span><?php
                if ($mpwpb_stats['next']) {
                    /* translators: %s: date and time of the next booking */
                    echo esc_html(sprintf(__('Next: %s', 'service-booking-manager'), wp_date('M j, ' . get_option('time_format'), $mpwpb_stats['next']->getTimestamp())));
                } else {
                    esc_html_e('Nothing booked this week', 'service-booking-manager');
                } ?></span><?php if ($mpwpb_bookings_url) { ?><a href="<?php echo esc_url($mpwpb_bookings_url); ?>"><?php esc_html_e('Bookings', 'service-booking-manager'); ?></a><?php } ?></div>
        </article>
        <article class="mpwpb-st__card">
            <div class="mpwpb-st__head"><span class="mpwpb-st__icon is-green" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="8" r="3.5"/><path d="M2.5 20a6.5 6.5 0 0 1 13 0"/><path d="M16 4.5a3.5 3.5 0 0 1 0 7M18 14a5.5 5.5 0 0 1 3.5 6"/></svg></span><span class="mpwpb-st__label"><?php esc_html_e('Customers', 'service-booking-manager'); ?></span></div>
            <div class="mpwpb-st__value"><?php echo esc_html($mpwpb_stats['customers']); ?></div>
            <div class="mpwpb-st__foot"><span><?php esc_html_e('Booked at least once', 'service-booking-manager'); ?></span><?php if ($mpwpb_orders_url) { ?><a href="<?php echo esc_url($mpwpb_orders_url); ?>"><?php esc_html_e('Orders', 'service-booking-manager'); ?></a><?php } ?></div>
        </article>
    </div>


    <!-- Services: list + detail panel (assets/admin/mpwpb-service-manage.js) -->
    <?php
    $mpwpb_sm_items = array();
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $mpwpb_sm_items[] = get_the_ID();
        }
        wp_reset_postdata();
    }
    $mpwpb_sm_tiles = array('#1d4ed8', '#15803d', '#7c3aed', '#c2410c', '#be185d', '#0e7490');
    $mpwpb_sm_status = array(
        'publish' => __('Published', 'service-booking-manager'),
        'draft'   => __('Draft', 'service-booking-manager'),
        'trash'   => __('Trash', 'service-booking-manager'),
    );
    $mpwpb_sm_initials = function ($name) {
        $words = preg_split('/\s+/', trim(str_replace('&', ' ', wp_strip_all_tags($name))));
        $out = '';
        foreach (array_slice(array_filter($words), 0, 2) as $w) {
            $out .= function_exists('mb_substr') ? mb_strtoupper(mb_substr($w, 0, 1)) : strtoupper(substr($w, 0, 1));
        }
        return $out;
    };
    ?>
    <div class="mpwpb-sv" data-mpwpb-sv>
        <div class="mpwpb-sv__bar">
            <div class="mpwpb-sv__bar-text">
                <h2><?php esc_html_e('Services', 'service-booking-manager'); ?></h2>
                <p><?php esc_html_e('Pick a service to see its shortcode, options and actions.', 'service-booking-manager'); ?></p>
            </div>
            <button type="button" id="mpwpb-open-business-templates" class="mpwpb-sv__btn mpwpb-bt__trigger-btn"><?php esc_html_e('Start from a template', 'service-booking-manager'); ?></button>
            <a class="mpwpb-sv__btn mpwpb-sv__btn--primary" href="<?php echo esc_url($add_new_link); ?>" data-sv-add>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                <?php esc_html_e('Add service', 'service-booking-manager'); ?>
            </a>
            <div id="mpwpb-bt-root"></div>
        </div>

        <div class="mpwpb-sv__layout">
            <section class="mpwpb-sv__list" aria-label="<?php esc_attr_e('Service list', 'service-booking-manager'); ?>">
                <div class="mpwpb-sv__list-tools">
                    <input type="search" class="mpwpb-sv__search" data-sv-search placeholder="<?php esc_attr_e('Search services', 'service-booking-manager'); ?>" aria-label="<?php esc_attr_e('Search services', 'service-booking-manager'); ?>">
                    <div class="mpwpb-sv__tabs" role="group" aria-label="<?php esc_attr_e('Status', 'service-booking-manager'); ?>">
                        <button type="button" data-sv-tab="all" aria-pressed="true"><?php esc_html_e('All', 'service-booking-manager'); ?> <span><?php echo esc_html($active_total); ?></span></button>
                        <button type="button" data-sv-tab="publish" aria-pressed="false"><?php esc_html_e('Published', 'service-booking-manager'); ?> <span><?php echo esc_html($publish); ?></span></button>
                        <button type="button" data-sv-tab="draft" aria-pressed="false"><?php esc_html_e('Drafts', 'service-booking-manager'); ?> <span><?php echo esc_html($draft); ?></span></button>
                        <button type="button" data-sv-tab="trash" aria-pressed="false"><?php esc_html_e('Trash', 'service-booking-manager'); ?> <span><?php echo esc_html($trash); ?></span></button>
                    </div>
                </div>
                <ul class="mpwpb-sv__items">
                    <?php foreach ($mpwpb_sm_items as $i => $sid) {
                        $st = get_post_status($sid);
                        $name = get_the_title($sid);
                        $opts = MPWPB_Global_Function::get_post_info($sid, 'mpwpb_service', array());
                        $n_opts = is_array($opts) ? count($opts) : 0;
                        ?>
                        <li data-sv-item="<?php echo esc_attr($sid); ?>" data-status="<?php echo esc_attr($st); ?>" data-title="<?php echo esc_attr(strtolower($name)); ?>"<?php echo $st === 'trash' ? ' hidden' : ''; ?>>
                            <button type="button" class="mpwpb-sv__pick" data-sv-pick="<?php echo esc_attr($sid); ?>" aria-controls="mpwpb-sv-detail-<?php echo esc_attr($sid); ?>">
                                <span class="mpwpb-sv__thumb" style="background:<?php echo esc_attr($mpwpb_sm_tiles[$i % count($mpwpb_sm_tiles)]); ?>">
                                    <?php if (has_post_thumbnail($sid)) { echo get_the_post_thumbnail($sid, 'thumbnail', array('alt' => '')); } else { echo esc_html($mpwpb_sm_initials($name)); } ?>
                                </span>
                                <span class="mpwpb-sv__pick-text">
                                    <span class="mpwpb-sv__pick-title"><?php echo esc_html($name); ?></span>
                                    <span class="mpwpb-sv__pick-sub"><?php
                                        /* translators: %d: number of bookable options */
                                        echo esc_html(sprintf(_n('%d popular option', '%d popular options', $n_opts, 'service-booking-manager'), $n_opts)); ?></span>
                                </span>
                                <span class="mpwpb-sv__dot is-<?php echo esc_attr($st); ?>" title="<?php echo esc_attr(isset($mpwpb_sm_status[$st]) ? $mpwpb_sm_status[$st] : $st); ?>"></span>
                            </button>
                        </li>
                    <?php } ?>
                </ul>
                <p class="mpwpb-sv__empty" data-sv-empty hidden><?php esc_html_e('No services here.', 'service-booking-manager'); ?></p>
            </section>

            <div class="mpwpb-sv__details">
                <?php foreach ($mpwpb_sm_items as $i => $sid) {
                    $st = get_post_status($sid);
                    $name = get_the_title($sid);
                    $sub = MPWPB_Global_Function::get_post_info($sid, 'mpwpb_shortcode_sub_title');
                    $opts = MPWPB_Global_Function::get_post_info($sid, 'mpwpb_service', array());
                    $opts = is_array($opts) ? array_filter($opts, function ($o) { return is_array($o) && !empty($o['name']); }) : array();
                    $cats = MPWPB_Global_Function::get_post_info($sid, 'mpwpb_category_service', array());
                    $cats = is_array($cats) ? $cats : array();
                    $subcats = MPWPB_Global_Function::get_post_info($sid, 'mpwpb_sub_category_service', array());
                    $subcats = is_array($subcats) ? $subcats : array();
                    $extras = MPWPB_Global_Function::get_post_info($sid, 'mpwpb_extra_service', array());
                    $extras = is_array($extras) ? array_filter($extras, function ($e) { return is_array($e) && !empty($e['name']); }) : array();
                    $cat_label = MPWPB_Global_Function::get_post_info($sid, 'mpwpb_category_text') ?: __('Categories', 'service-booking-manager');
                    $shortcode = '[service-booking post_id="' . $sid . '"]';
                    $edit_link = get_edit_post_link($sid);

                    // Price list grouped by category (and sub-category: sub_cat / cat_id are the
                    // stable array keys, see Admin/settings/Category.php).
                    $groups = array();
                    $prices = array();
                    $used_cats = array();
                    foreach ($opts as $opt) {
                        $pc = isset($opt['parent_cat']) ? (string) $opt['parent_cat'] : '';
                        $sc = isset($opt['sub_cat']) ? (string) $opt['sub_cat'] : '';
                        $label = '';
                        if ($pc !== '' && isset($cats[$pc]['name'])) {
                            $label = $cats[$pc]['name'];
                            $used_cats[$pc] = true;
                            if ($sc !== '' && isset($subcats[$sc]['name']) && (string) ($subcats[$sc]['cat_id'] ?? '') === $pc) {
                                $label .= ' · ' . $subcats[$sc]['name'];
                            }
                        }
                        $groups[$label][] = $opt;
                        if (isset($opt['price']) && $opt['price'] !== '') {
                            $prices[] = (float) $opt['price'];
                        }
                    }
                    if ($prices) {
                        $min = min($prices);
                        $max = max($prices);
                        $range = $min === $max ? MPWPB_Global_Function::format_price($min) : MPWPB_Global_Function::format_price($min) . ' – ' . MPWPB_Global_Function::format_price($max);
                    } else {
                        $range = '';
                    }
                    ?>
                    <section class="mpwpb-sv__detail" id="mpwpb-sv-detail-<?php echo esc_attr($sid); ?>" data-sv-detail="<?php echo esc_attr($sid); ?>" aria-labelledby="mpwpb-sv-title-<?php echo esc_attr($sid); ?>" hidden>
                        <div class="mpwpb-sv__top">
                            <div class="mpwpb-sv__cover" style="background:<?php echo esc_attr($mpwpb_sm_tiles[$i % count($mpwpb_sm_tiles)]); ?>">
                                <?php if (has_post_thumbnail($sid)) { echo get_the_post_thumbnail($sid, 'medium', array('alt' => '')); } else { echo '<span aria-hidden="true">' . esc_html($mpwpb_sm_initials($name)) . '</span>'; } ?>
                            </div>
                            <div class="mpwpb-sv__top-text">
                                <div class="mpwpb-sv__meta">
                                    <span class="mpwpb-sv__badge is-<?php echo esc_attr($st); ?>"><span></span><?php echo esc_html(isset($mpwpb_sm_status[$st]) ? $mpwpb_sm_status[$st] : $st); ?></span>
                                    <span class="mpwpb-sv__updated"><?php
                                        /* translators: %s: date the service was last changed */
                                        echo esc_html(sprintf(__('Updated %s', 'service-booking-manager'), get_the_modified_date('', $sid))); ?></span>
                                </div>
                                <h3 id="mpwpb-sv-title-<?php echo esc_attr($sid); ?>"><?php echo esc_html($name); ?></h3>
                                <?php if ($sub) { ?><p><?php echo esc_html($sub); ?></p><?php } ?>
                                <?php if ($st !== 'trash') { ?>
                                    <div class="mpwpb-sv__head-actions">
                                        <a class="mpwpb-sv__btn mpwpb-sv__btn--primary" href="<?php echo esc_url($edit_link); ?>"><?php esc_html_e('Edit service', 'service-booking-manager'); ?></a>
                                        <a class="mpwpb-sv__btn" href="<?php echo esc_url($st === 'publish' ? get_permalink($sid) : get_preview_post_link($sid)); ?>" target="_blank" rel="noopener"><?php echo $st === 'publish' ? esc_html__('View page', 'service-booking-manager') : esc_html__('Preview', 'service-booking-manager'); ?>
                                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7 17L17 7"/><path d="M8 7h9v9"/></svg></a>
                                    </div>
                                <?php } ?>
                            </div>
                        </div>

                        <dl class="mpwpb-sv__facts">
                            <div><dt><?php esc_html_e('Options', 'service-booking-manager'); ?></dt><dd><?php echo esc_html(count($opts)); ?></dd></div>
                            <div><dt><?php esc_html_e('Price range', 'service-booking-manager'); ?></dt><dd><?php echo $range ? wp_kses_post($range) : '—'; ?></dd></div>
                            <?php if ($used_cats) { ?>
                                <div><dt><?php echo esc_html($cat_label); ?></dt><dd><?php echo esc_html(count($used_cats)); ?></dd></div>
                            <?php } ?>
                            <div><dt><?php esc_html_e('Add-ons', 'service-booking-manager'); ?></dt><dd><?php echo esc_html(count($extras)); ?></dd></div>
                        </dl>

                        <div class="mpwpb-sv__body">
                            <?php if ($st !== 'trash') { ?>
                                <div class="mpwpb-sv__block">
                                    <div class="mpwpb-sv__code-row">
                                        <code class="mpwpb-sv__code"><?php echo esc_html($shortcode); ?></code>
                                        <button type="button" class="mpwpb-sv__btn" data-sv-copy="<?php echo esc_attr($shortcode); ?>" aria-label="<?php esc_attr_e('Copy shortcode', 'service-booking-manager'); ?>"><?php esc_html_e('Copy', 'service-booking-manager'); ?></button>
                                    </div>
                                    <p class="mpwpb-sv__hint"><?php esc_html_e('Paste into any page or post to show this booking form.', 'service-booking-manager'); ?></p>
                                </div>
                            <?php } ?>

                            <div class="mpwpb-sv__block">
                                <div class="mpwpb-sv__block-head">
                                    <h4><?php esc_html_e('Price list', 'service-booking-manager'); ?></h4>
                                    <?php if ($st !== 'trash') { ?><a href="<?php echo esc_url($edit_link); ?>"><?php esc_html_e('Edit prices', 'service-booking-manager'); ?></a><?php } ?>
                                </div>
                                <?php if ($opts) { ?>
                                    <div class="mpwpb-sv__prices">
                                        <?php foreach ($groups as $label => $rows) { ?>
                                            <?php if ($label !== '') { ?><div class="mpwpb-sv__group"><?php echo esc_html($label); ?></div><?php } ?>
                                            <?php foreach ($rows as $opt) { ?>
                                                <div class="mpwpb-sv__price-row">
                                                    <span class="mpwpb-sv__price-name"><?php echo esc_html($opt['name']); ?></span>
                                                    <span class="mpwpb-sv__price-dur"><?php echo !empty($opt['duration']) ? esc_html($opt['duration']) : '—'; ?></span>
                                                    <span class="mpwpb-sv__price-amt"><?php echo (isset($opt['price']) && $opt['price'] !== '') ? wp_kses_post(MPWPB_Global_Function::format_price((float) $opt['price'])) : '—'; ?></span>
                                                </div>
                                            <?php } ?>
                                        <?php } ?>
                                    </div>
                                <?php } else { ?>
                                    <p class="mpwpb-sv__hint"><?php esc_html_e('No options added yet.', 'service-booking-manager'); ?></p>
                                <?php } ?>
                            </div>

                            <?php if ($extras) { ?>
                                <div class="mpwpb-sv__block">
                                    <div class="mpwpb-sv__block-head"><h4><?php esc_html_e('Add-ons', 'service-booking-manager'); ?></h4></div>
                                    <div class="mpwpb-sv__tags">
                                        <?php foreach ($extras as $ex) { ?>
                                            <span><?php echo esc_html($ex['name']); ?><?php if (isset($ex['price']) && $ex['price'] !== '') { ?> <em><?php echo wp_kses_post(MPWPB_Global_Function::format_price((float) $ex['price'])); ?></em><?php } ?></span>
                                        <?php } ?>
                                    </div>
                                </div>
                            <?php } ?>

                            <div class="mpwpb-sv__foot">
                                <?php if ($st === 'trash') {
                                    $restore_link = wp_nonce_url(admin_url('post.php?action=untrash&post=' . $sid), 'untrash-post_' . $sid);
                                    ?>
                                    <a class="mpwpb-sv__btn" href="<?php echo esc_url($restore_link); ?>"><?php esc_html_e('Restore', 'service-booking-manager'); ?></a>
                                    <span class="mpwpb-sv__spacer"></span>
                                    <a class="mpwpb-sv__btn mpwpb-sv__btn--danger" href="<?php echo esc_url(get_delete_post_link($sid, '', true)); ?>" onclick="return confirm('<?php echo esc_js(__('Permanently delete this service? This cannot be undone.', 'service-booking-manager')); ?>');"><?php esc_html_e('Delete permanently', 'service-booking-manager'); ?></a>
                                <?php } else { ?>
                                    <a class="mpwpb-sv__btn" href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?action=mpwpb_duplicate_post&post_id=' . $sid), 'mpwpb_duplicate_post_' . $sid)); ?>"><?php esc_html_e('Duplicate', 'service-booking-manager'); ?></a>
                                    <span class="mpwpb-sv__spacer"></span>
                                    <a class="mpwpb-sv__btn mpwpb-sv__btn--danger" href="<?php echo esc_url(get_delete_post_link($sid)); ?>" onclick="return confirm('<?php echo esc_js(__('Move this service to the trash?', 'service-booking-manager')); ?>');"><?php esc_html_e('Move to trash', 'service-booking-manager'); ?></a>
                                <?php } ?>
                            </div>
                        </div>
                    </section>
                <?php } ?>
                <div class="mpwpb-sv__detail mpwpb-sv__detail--empty" data-sv-detail-empty hidden>
                    <p><?php esc_html_e('No service selected.', 'service-booking-manager'); ?></p>
                    <a class="mpwpb-sv__btn mpwpb-sv__btn--primary" href="<?php echo esc_url($add_new_link); ?>" data-sv-add><?php esc_html_e('Add service', 'service-booking-manager'); ?></a>
                </div>
            </div>
        </div>
    </div>
</div>
