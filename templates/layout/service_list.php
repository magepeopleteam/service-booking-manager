<?php

$statuses = ['publish', 'draft'];
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
$trash_link = add_query_arg([
    'post_status' => 'trash',
    'post_type'   => 'mpwpb_item',
], admin_url('edit.php'));
$add_new_link = admin_url('post-new.php?post_type=mpwpb_item');


function mpwpb_display_service_list( $query ){ ?>


            <?php
            $mpwpb_tag_palette = array( 'blue', 'green', 'purple', 'orange', 'pink' );
            $mpwpb_row_index = 0;

            if ($query->have_posts()) {
            while ($query->have_posts()) {
            $query->the_post();
            $post_id = get_the_ID();
            $mpwpb_tag_color = $mpwpb_tag_palette[ $mpwpb_row_index % count( $mpwpb_tag_palette ) ];
            $mpwpb_row_index++;
            $status = get_post_status($post_id);
            $service_name = get_the_title( $post_id );

            $all_services     = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_service', array());
            $title            = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_shortcode_title');
            $sub_title        = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_shortcode_sub_title');

            $view_link   = get_permalink($post_id);
            $edit_link   = get_edit_post_link($post_id);
            $delete_link = get_delete_post_link($post_id);

            if( $status === 'publish' ) {
                $status_class = 'published';
                $status_text = 'Published';
            }else if( $status === 'draft' ){
                $status_class = 'draft';
                $status_text = 'Draft';
            }else{
                $status_class = 'mpwpb_trash';
                $status_text = 'Trash';
            }

            $shortcode = '[service-booking post_id="'.$post_id.'"]';

            ?>
            <tr class="mpwpv_service_list_table-container" data-service-status="<?php echo esc_attr( $status );?>" data-service-title="<?php echo esc_attr( $service_name )?>">
                <td>
                    <div class="mpwpb_service_list_service-name">
                        <?php if ( has_post_thumbnail( $post_id ) ) : ?>
                            <div class="mpwpb_service_list_service-thumb"><?php echo get_the_post_thumbnail( $post_id, 'thumbnail' ); ?></div>
                        <?php else : ?>
                            <div class="mpwpb_service_list_service-thumb mpwpb_service_list_service-thumb-placeholder"><i class="fas fa-briefcase"></i></div>
                        <?php endif; ?>
                        <div class="mpwpb_service_list_service-info">
                            <div class="mpwpb_service_list_service-title"><?php echo esc_html( $service_name ); ?></div>
                            <?php if ( $sub_title ) : ?>
                                <div class="mpwpb_service_list_service-subtitle"><?php echo esc_html( $sub_title ); ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </td>
                <td>
                    <span class="mpwpb_service_list_shortcode" id="mpwpb_shortcode_copy">
                        <code><?php echo esc_html( $shortcode );?></code>
                        <i class="fas fa-copy mpwpb_service_list_shortcode-icon"></i>
                    </span>
                </td>
                <td>
                    <span class="mpwpb_service_list_status-badge <?php echo esc_attr( $status_class );?>"><span class="mpwpb_service_list_status-dot"></span><?php echo esc_html( $status_text )?></span>
                </td>
                <td>
                    <div class="mpwpb_service_list_tags mpwpb_service_list_tags-<?php echo esc_attr( $mpwpb_tag_color );?>">
                        <?php
                        $service_count = count($all_services);
                        $service_show = 0;

                        if( is_array( $all_services ) && !empty( $all_services ) ) {
                            foreach ($all_services as $service) {
                                if( $service_show < 4 ){
                                    ?>
                                    <span class="mpwpb_service_list_tag"><?php echo esc_html( $service['name'] )?></span>
                                    <?php $service_show++;
                                }
                            }
                        } if( $service_count > 4){
                            $remainig_service_count = $service_count - 4;
                            ?>
                            <span class="mpwpb_service_list_more-link">+<?php echo esc_attr( $remainig_service_count );?> more</span>
                        <?php }?>
                    </div>
                </td>
                <td>
                    <div class="mpwpb_service_list_actions">
                        <a href="<?php echo esc_url( $view_link );?>"><button class="mpwpb_service_list_action-btn"><i class="mi mi-eye"></i></button></a>
                        <a href="<?php echo  esc_url( $edit_link );?>"><button class="mpwpb_service_list_action-btn"><i class="mi mi-edit"></i></button></a>
                        <a title="<?php echo esc_attr__('Duplicate Post ', 'service-booking-manager') . ' : ' . get_the_title($post_id); ?>" class="mpwpb_duplicate_post" href="<?php echo wp_nonce_url(
                            admin_url('admin.php?action=mpwpb_duplicate_post&post_id=' . $post_id),
                            'mpwpb_duplicate_post_' . $post_id
                        ); ?>">
                        <button class="mpwpb_service_list_action-btn">
                            <i class="mi mi-clone"></i>
                        </button>
                        </a>
                        <a class=" delete"
                           href="<?php echo esc_url($delete_link); ?>"
                           onclick="return confirm('Are you sure you want to move this to trash?');"
                           title="Trash"><button class="mpwpb_service_list_action-btn"><i class="mi mi-trash"></i></button></a>
                    </div>
                </td>
            </tr>
            <?php }
            }
        }
function get_total_customer(){
    $users = get_users([
        'role' => 'customer',
        'fields' => 'ID',
    ]);

    return count( $users );
}
function get_upcomming_service_order_count(){
    if (!MPWPB_Global_Function::is_wc_payment_mode()) {
        return 0;
    }
    $start_date = date('Y-m-d') . 'T00:00:00' . date('P');
    $end_date = date('Y-12-31') . 'T23:59:59' . date('P');
    $args = array(
        'status'   => array('processing', 'completed', 'on-hold', 'pending'),
        'limit'    => -1,
        'orderby'  => 'date',
        'order'    => 'DESC',
        'date_query' => array(
            array(
                'after'     => $start_date,
                'before'    => $end_date,
                'inclusive' => true,
            ),
        ),
    );
    $orders = wc_get_orders($args);
    $all_booking_dates = [];

    foreach ($orders as $order) {
        foreach ($order->get_items() as $item_id => $item) {
            $service_date_raw = sanitize_text_field(wc_get_order_item_meta($item_id, '_mpwpb_date', true) ?: '');
            if ( !empty($service_date_raw ) ) {
                $dates = explode(',', $service_date_raw);
                $all_booking_dates = array_merge( $all_booking_dates, $dates );
            }
        }
    }

    return count( $all_booking_dates );
}
function get_monthly_sales_totals() {
    if (!MPWPB_Global_Function::is_wc_payment_mode()) {
        // Native (non-WooCommerce) revenue reporting isn't wired up yet.
        return [];
    }
    $start_date = date('Y-m-d') . 'T00:00:00' . date('P');
    $end_date   = date('Y-12-31') . 'T23:59:59' . date('P');

    $args = array(
        'status'     => array('processing', 'completed', 'on-hold', 'pending'),
        'limit'      => -1,
        'orderby'    => 'date',
        'order'      => 'DESC',
        'date_query' => array(
            array(
                'after'     => $start_date,
                'inclusive' => true,
            ),
        ),
    );

    $orders = wc_get_orders($args);
    $monthly_totals = [];

    foreach ($orders as $order) {
        $order_date = $order->get_date_created(); // WC_DateTime object
        if ( $order_date ) {
            $month = $order_date->format('Y-m'); // e.g., 2025-06
            $total = floatval($order->get_total());

            if (isset($monthly_totals[$month])) {
                $monthly_totals[$month] += $total;
            } else {
                $monthly_totals[$month] = $total;
            }
        }
    }

    return $monthly_totals;
}
$monthly_totals = get_monthly_sales_totals();
$all_booking_dates = get_upcomming_service_order_count();
$total_user = get_total_customer();
?>

<div class="mpwpv_service_list_container">

    <div class="mpwpv_service_list_header">
        <div class="mpwpv_service_list_header-titles">
            <h1><?php esc_html_e( 'Service Management', 'service-booking-manager')?> <i class="fas fa-star mpwpv_service_list_header-sparkle"></i></h1>
            <p><?php esc_html_e( 'Manage your services and track performance', 'service-booking-manager')?></p>
        </div>
    </div>

    <!-- Analytics Section -->
    <div class="mpwpv_service_list_analytics-container">
        <div class="mpwpv_service_list_analytics-card">
            <div class="mpwpv_service_list_analytics-icon blue"><i class="fas fa-cubes"></i></div>
            <div class="mpwpv_service_list_analytics-text">
                <p class="mpwpv_service_list_analytics-label"><?php esc_html_e( 'Total Services', 'service-booking-manager')?></p>
                <p class="mpwpv_service_list_analytics-value"><?php echo esc_html( $total )?></p>
                <p class="mpwpv_service_list_analytics-caption"><?php esc_html_e( 'All services', 'service-booking-manager')?></p>
            </div>
        </div>

        <div class="mpwpv_service_list_analytics-card">
            <div class="mpwpv_service_list_analytics-icon green"><i class="fas fa-users"></i></div>
            <div class="mpwpv_service_list_analytics-text">
                <p class="mpwpv_service_list_analytics-label"><?php esc_html_e( 'Active Clients', 'service-booking-manager')?></p>
                <p class="mpwpv_service_list_analytics-value"><?php echo esc_html( $total_user )?></p>
                <p class="mpwpv_service_list_analytics-caption"><?php esc_html_e( 'This month', 'service-booking-manager')?></p>
            </div>
        </div>

        <div class="mpwpv_service_list_analytics-card">
            <div class="mpwpv_service_list_analytics-icon purple"><i class="fas fa-coins"></i></div>
            <div class="mpwpv_service_list_analytics-text">
                <p class="mpwpv_service_list_analytics-label"><?php esc_html_e( 'Monthly Revenue', 'service-booking-manager')?></p>
                <p class="mpwpv_service_list_analytics-value"><?php

                        $current_month = date('Y-m');
                        $revinue = isset( $monthly_totals[ $current_month ] ) ? $monthly_totals[ $current_month ] : 0 ;
                        echo wp_kses_post( MPWPB_Global_Function::format_price( $revinue ) );
                    ?>
                </p>
                <p class="mpwpv_service_list_analytics-caption"><?php esc_html_e( 'This month', 'service-booking-manager')?></p>
            </div>
        </div>

        <div class="mpwpv_service_list_analytics-card">
            <div class="mpwpv_service_list_analytics-icon orange"><i class="fas fa-calendar-check"></i></div>
            <div class="mpwpv_service_list_analytics-text">
                <p class="mpwpv_service_list_analytics-label"><?php esc_html_e( 'Upcoming Bookings', 'service-booking-manager')?></p>
                <p class="mpwpv_service_list_analytics-value"><?php echo esc_html( $all_booking_dates )?></p>
                <p class="mpwpv_service_list_analytics-caption"><?php esc_html_e( 'Next 7 days', 'service-booking-manager')?></p>
            </div>
        </div>
    </div>


    <!-- Main Content -->
    <div class="mpwpv_service_list_main-card">
        <div class="mpwpv_service_list_card-header">
            <div class="mpwpv_service_list_header-top">
                <div class="mpwpv_service_list_header-top-titles">
                    <h2><?php esc_html_e( 'Service Listings', 'service-booking-manager')?></h2>
                    <p><?php esc_html_e( 'Manage all your services in one place', 'service-booking-manager')?></p>
                </div>
            </div>

            <div class="mpwpv_service_list_filter-row">
                <div class="mpwpv_service_list_filter-buttons">
                    <button class="mpwpv_service_list_filter-btn ttbm_filter_btn_active_bg_color" data-filter-item="all"><?php esc_html_e( 'All Services', 'service-booking-manager')?> <span class="mpwpb_service_list_filter-count"><?php echo esc_html( $total )?></span></button>
                    <button class="mpwpv_service_list_filter-btn ttbm_filter_btn_bg_color" data-filter-item="publish"><?php esc_html_e( 'Published', 'service-booking-manager')?> <span class="mpwpb_service_list_filter-count"><?php echo esc_html( $publish )?></span></button>
                    <button class="mpwpv_service_list_filter-btn ttbm_filter_btn_bg_color" data-filter-item="draft"><?php esc_html_e( 'Draft', 'service-booking-manager')?> <span class="mpwpb_service_list_filter-count"><?php echo esc_html( $draft )?></span></button>

                    <a class="ttbm_trash_link" href="<?php echo esc_url( $trash_link )?>" target="_blank">
                        <button class="mpwpv_service_list_filter-btn ttbm_filter_btn_bg_color" data-filter-item="trash"><?php esc_html_e( 'Trash', 'service-booking-manager')?> <span class="mpwpb_service_list_filter-count"><?php echo esc_html( $trash )?></span></button>
                    </a>

                </div>
                <div class="mpwpv_service_list_actions-inline">
                    <a href="<?php echo esc_url( $add_new_link )?>"><div class="mpwpb_add_new_Service"><span class="fas fa-plus _mR_xs"></span><?php echo esc_html__('Add New Service', 'tour-booking-manager')?></div></a>
                    <button type="button" id="mpwpb-open-business-templates" class="mpwpb_add_new_Service mpwpb-bt__trigger-btn"><span class="dashicons dashicons-superhero-alt _mR_xs"></span><?php echo esc_html__('One-Click Business Templates', 'service-booking-manager')?></button>
                    <div id="mpwpb-bt-root"></div>
                    <div class="mpwpv_service_list_search-container">
                        <span class="mpwpv_service_list_search-icon"><i class="fas fa-magnifying-glass"></i></span>
                        <input type="text" class="mpwpv_service_list_search-input" id="mpwpv_service_list_search_input" placeholder="<?php esc_attr_e( 'Search services...', 'service-booking-manager')?>">
                    </div>
                </div>
            </div>
        </div>

        <!-- Services Table -->
        <div class="mpwpb_service_list_table-container">
            <table class="mpwpb_service_list_services-table">
                <thead>
                <tr>
                    <th><?php esc_attr_e( 'Service', 'service-booking-manager')?></th>
                    <th><?php esc_attr_e( 'Shortcode', 'service-booking-manager')?></th>
                    <th><?php esc_attr_e( 'Status', 'service-booking-manager')?></th>
                    <th><?php esc_attr_e( 'Popular Services', 'service-booking-manager')?></th>
                    <th><?php esc_attr_e( 'Actions', 'service-booking-manager')?></th>
                </tr>
                </thead>
                <tbody>
                <?php echo mpwpb_display_service_list( $query );?>
                </tbody>
            </table>
        </div>

        <div class="mpwpb_service_list_pagination">
            <span class="mpwpb_service_list_pagination-info">
                <?php
                printf(
                    /* translators: 1: number of services shown, 2: total number of services */
                    esc_html__( 'Showing 1 to %1$d of %2$d entries', 'service-booking-manager' ),
                    (int) $query->post_count,
                    (int) $query->post_count
                );
                ?>
            </span>
            <div class="mpwpb_service_list_pagination-controls">
                <button type="button" class="mpwpb_service_list_page-arrow" disabled aria-label="<?php esc_attr_e( 'Previous page', 'service-booking-manager' ); ?>">&lsaquo;</button>
                <span class="mpwpb_service_list_page-num active">1</span>
                <button type="button" class="mpwpb_service_list_page-arrow" disabled aria-label="<?php esc_attr_e( 'Next page', 'service-booking-manager' ); ?>">&rsaquo;</button>
            </div>
        </div>
    </div>
</div>
