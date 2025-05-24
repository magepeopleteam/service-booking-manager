<?php

if ( ! defined( 'ABSPATH' ) ) {
    die;
}

if ( ! class_exists( 'MPWPB_Service_List' ) ) {
    class MPWPB_Service_List{
        public function __construct(){
            add_action( 'admin_menu', array( $this, 'service_list_menu' ) );
        }
        public function service_list_menu() {
            add_submenu_page( 'edit.php?post_type=mpwpb_item', esc_html__( 'Service List', 'service-booking-manager' ), esc_html__( 'Service List', 'service-booking-manager' ), 'manage_options', 'mpwpb_service_list', array( $this, 'service_list' ),10 );
        }
        public function service_list() {?>
            <div class="wrap">
                <div class="mpwpb_style mpwpb_order_filter_area">
                    <div id="mpwpb_order_list_result">
                        <?php $this->service_list_result(); ?>
                    </div>
                </div>
            </div>
            <style>
                #update-nag, .update-nag {display: none;}
            </style>
            <?php
        }

        public function service_list_result() {
            include(MPWPB_Function::template_path('layout/service_list.php'));
        }
    }

    new MPWPB_Service_List();

}