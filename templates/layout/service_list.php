<?php

$statuses = ['publish', 'draft'];
$args = [
    'post_type'      => 'mpwpb_item',
    'post_status'    => $statuses,
    'posts_per_page' => -1,
];

$query = new WP_Query($args);

$data = mpwpb_get_service_posts_by_status( $query );
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

function mpwpb_get_service_posts_by_status( $query ) {
    ob_start();
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $post_id = get_the_ID();
            $status = get_post_status($post_id);
            $service_name = get_the_title( $post_id );

            $all_services     = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_service', array());
            $title            = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_shortcode_title');
            $sub_title        = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_shortcode_sub_title');
            $is_multiselect   = get_post_meta($post_id, 'mpwpb_service_multiple_category_check', true);
            $enable_recurring = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_enable_recurring', 'no');

            $view_link   = get_permalink($post_id);
            $edit_link   = get_edit_post_link($post_id);
            $delete_link = get_delete_post_link($post_id);

            if( $status === 'publish' ) {
                $status_dot_class = 'mpwpb_available_dot';
                $status_class = 'mpwpb_available';
                $status_text = 'Available';
                $left_border = 'available';
                $display = '';
            }else if( $status === 'draft' ){
                $status_dot_class = 'mpwpb_draft_dot';
                $status_class = 'mpwpb_draft';
                $status_text = 'Draft';
                $left_border = '';
                $display = '';
            }else{
                $status_dot_class = 'mpwpb_trash_dot';
                $status_class = 'mpwpb_trash';
                $status_text = 'Trash';
                $left_border = '';
                $display = 'none';
            }

            ?>
            <div class="mpwpv_service_list_table-container" data-service-status="<?php echo esc_attr( $status );?>" data-service-title="<?php echo esc_attr( $service_name )?>" >
                <div class="mpwpv_service_list_wrapper">
                    <div class="mpwpv_service_list_item <?php echo esc_attr( $left_border );?>">
                        <div class="mpwpv_service_list_header" >
                            <div class="mpwpv_service_list_service">
                                <div class="mpwpv_service_list_status_text_holder" style="display:flex;">
                                    <span class="mpwpv_service_list_status <?php echo esc_attr( $status_dot_class );?>"></span>
                                    <span class="mpwpv_service_list_availability <?php echo esc_attr( $status_class );?>"><?php echo ucfirst($status_text); ?></span>
                                </div>
                                <div class="mpwpv_service_list_title"><a href="<?php echo esc_url( $edit_link )?>" style="color: unset"><?php echo esc_html( $service_name ); ?></a></div>
                            </div>
                            <div class="mpwpv_service_list_location"><?php echo esc_attr( $sub_title );?></div>
                            <div class="mpwpv_service_list_services">
                                <?php
                                $service_count = count($all_services);
                                $service_show = 0;

                                if( is_array( $all_services ) && !empty( $all_services ) ) {
                                    foreach ($all_services as $service) {
                                        if( $service_show < 2 ){
                                            if( $service_show === 0 ){
                                                $color_class = 'blue';
                                            }else{
                                                $color_class = 'green';
                                            }
                                            ?>
                                            <div class="mpwpv_service_list_holder <?php echo esc_attr( $color_class );?>">
                                                <span class="mpwpv_service_list_service-tag <?php echo esc_attr( $color_class );?>"></span>
                                                <span class="mpwpv_service_list_badge"><?php echo esc_html( $service['name'] )?></span>
                                            </div>
                                            <?php $service_show++;
                                        }
                                    }
                                } if( $service_count > 2){
                                    $remainig_service_count = $service_count - 2;
                                    ?>
                                    <span class="mpwpv_service_list_more">+<?php echo esc_attr( $remainig_service_count );?> more</span>
                                <?php }?>
                            </div>
                            <div class="mpwpv_service_list_rating">
                                <span class="mpwpv_service_list_rating_number"><?php MPWPB_Static_Template::get_ratings();?></span>
                            </div>

                            <div class="mpwpv_service_list_actions">
                                <a class="mpwpv_icon view" href="<?php echo esc_url($view_link); ?>" title="View">👁️</a>

                                <?php if (current_user_can('edit_post', $post_id)) : ?>
                                    <a class="mpwpv_icon edit" href="<?php echo esc_url($edit_link); ?>" title="Edit">✏️</a>
                                <?php endif; ?>

                                <a title="<?php echo esc_attr__('Duplicate Post ', 'service-booking-manager') . ' : ' . get_the_title($post_id); ?>" class="mpwpb_duplicate_post" href="<?php echo wp_nonce_url(
                                    admin_url('admin.php?action=mpwpb_duplicate_post&post_id=' . $post_id),
                                    'mpwpb_duplicate_post_' . $post_id
                                ); ?>">
                                    <i class="fa fa-clone"></i>
                                </a>

                                <?php if (current_user_can('delete_post', $post_id)) : ?>
                                    <a class="mpwpv_icon delete"
                                       href="<?php echo esc_url($delete_link); ?>"
                                       onclick="return confirm('Are you sure you want to move this to trash?');"
                                       title="Trash">🗑️</a>
                                <?php endif; ?>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
            <?php
        }
        wp_reset_postdata();
    }
//    }

    $results = ob_get_clean(); // End buffering and store the HTML

    return $results;
}
function get_total_customer(){
    $users = get_users([
        'role' => 'customer',
        'fields' => 'ID',
    ]);

    return count( $users );
}
function get_upcomming_service_order_count(){
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
        <h1><?php esc_attr_e( 'Service Management Dashboard', 'service-booking-manager')?></h1>
        <p><?php esc_attr_e( 'Manage your services and track performance', 'service-booking-manager')?></p>
    </div>

    <!-- Analytics Section -->
    <div class="mpwpv_service_list_analytics-container">
        <div class="mpwpv_service_list_analytics-card">
            <div class="mpwpv_service_list_analytics-icon blue">📊</div>
            <div class="mpwpv_service_list_analytics-text">
                <p><?php esc_attr_e( 'Total Services', 'service-booking-manager')?></p>
                <p><?php echo esc_attr( $total )?></p>
            </div>
        </div>

        <div class="mpwpv_service_list_analytics-card">
            <div class="mpwpv_service_list_analytics-icon green">👥</div>
            <div class="mpwpv_service_list_analytics-text">
                <p><?php esc_attr_e( 'Active Clients', 'service-booking-manager')?></p>
                <p><?php echo esc_attr( $total_user )?></p>
            </div>
        </div>

        <div class="mpwpv_service_list_analytics-card">
            <div class="mpwpv_service_list_analytics-icon purple">💲</div>
            <div class="mpwpv_service_list_analytics-text">
                <p><?php esc_attr_e( 'Monthly Revenue', 'service-booking-manager')?></p>
                <p><?php
                        $current_month = date('Y-m');
                        $revinue = isset( $monthly_totals[ $current_month ] ) ? $monthly_totals[ $current_month ] : 0 ;
                        echo wp_kses_post( wc_price( $revinue ) );
                    ?>
                </p>
            </div>
        </div>

        <div class="mpwpv_service_list_analytics-card">
            <div class="mpwpv_service_list_analytics-icon orange">📅</div>
            <div class="mpwpv_service_list_analytics-text">
                <p><?php esc_attr_e( 'Upcoming Bookings', 'service-booking-manager')?></p>
                <p><?php echo esc_attr( $all_booking_dates )?></p>
            </div>
        </div>
    </div>


    <!-- Main Content -->
    <div class="mpwpv_service_list_main-card">
        <div class="mpwpv_service_list_card-header">
            <div class="mpwpv_service_list_header-top">
                <h2><?php esc_attr_e( 'Service Listings', 'service-booking-manager')?></h2>
                <div class="mpwpb_add_new_search_holder">
                    <a href="<?php echo esc_url( $add_new_link )?>"><div class="mpwpb_add_new_Service"><span class="fas fa-plus _mR_xs"></span><?php echo esc_html__('Add New Service', 'tour-booking-manager')?></div></a>
                    <div class="mpwpv_service_list_search-container">
                        <input type="text" class="mpwpv_service_list_search-input" id="mpwpv_service_list_search_input" placeholder="<?php esc_attr_e( 'Search services...', 'service-booking-manager')?>">
                        <span class="mpwpv_service_list_search-icon">🔍</span>
                    </div>
                </div>
            </div>

            <div class="mpwpv_service_list_filter-buttons">
                <button class="mpwpv_service_list_filter-btn" data-filter-item="all"><?php esc_attr_e( 'All Items', 'service-booking-manager')?> (<?php echo esc_attr( $total )?>)</button>
                <button class="mpwpv_service_list_filter-btn" data-filter-item="publish"><?php esc_attr_e( 'Published', 'service-booking-manager')?> (<?php echo esc_attr( $publish )?>)</button>
                <button class="mpwpv_service_list_filter-btn" data-filter-item="draft"><?php esc_attr_e( 'Draft', 'service-booking-manager')?> (<?php echo esc_attr( $draft )?>)</button>

                <a class="ttbm_trash_link" href="<?php echo esc_url( $trash_link )?>" target="_blank">
                    <button class="mpwpv_service_list_filter-btn" data-filter-item="trash"><?php esc_attr_e( 'Trash', 'service-booking-manager')?> (<?php echo esc_attr( $trash )?>)</button>
                </a>

            </div>
        </div>

        <!-- Services Table -->
        <div class="mpwpv_service_list_table_header">
            <div class="mpwpv_service_list_table_th" style="width: 25%"><?php esc_attr_e( 'Service', 'service-booking-manager')?></div>
            <div class="mpwpv_service_list_table_th" style="width: 20%"><?php esc_attr_e( 'Sub Title', 'service-booking-manager')?></div>
            <div class="mpwpv_service_list_table_th" style="width: 30%"><?php esc_attr_e( 'Popular Services', 'service-booking-manager')?></div>
            <div class="mpwpv_service_list_table_th" style="width: 15%"><?php esc_attr_e( 'Rating', 'service-booking-manager')?></div>
            <div class="mpwpv_service_list_table_th" style="width: 15%"><?php esc_attr_e( 'Actions', 'service-booking-manager')?></div>
        </div>
        <?php echo wp_kses_post( $data ) ;?>
    </div>
</div>
