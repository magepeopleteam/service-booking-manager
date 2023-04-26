<?phpif (!defined('ABSPATH')) {    die;} // Cannot access pages directly.if (!class_exists('MPWPB_Status')) {    class MPWPB_Status {        public function __construct() {            add_action('admin_menu', array($this, 'status_menu'));        }        public function status_menu() {            $cpt = MPWPB_Function::mp_cpt();            add_submenu_page('edit.php?post_type=' . $cpt, __('Status', 'service-booking-manager'), __('<span style="color:yellow">Status</span>', 'service-booking-manager'), 'manage_options', 'mpwpb_status_page', array($this, 'status_page'));        }        public function status_page() {            $label = MPWPB_Function::get_name();            $wc_i = MP_Global_Function::check_woocommerce();            $wp_v = get_bloginfo('version');            $wc_v = WC()->version;            $from_name = get_option('woocommerce_email_from_name');            $from_email = get_option('woocommerce_email_from_address');            ?>            <div class="wrap">            </div>            <div class="mpStyle">                <?php do_action('mp_status_notice_sec'); ?>                <div class="_dLayout_mT_max_800_mAuto">                    <h2><?php echo esc_html($label) . '  ' . esc_html__('For Woocommerce Environment Status', 'service-booking-manager') ?></h2>                    <div class="divider"></div>                    <table>                        <tbody>                        <tr>                            <th data-export-label="WC Version">WordPress Version:</th>                            <th>                                <?php if ($wp_v > 5.5) {                                    echo '<span class="textSuccess"> <span class="far fa-check-circle mR_xs"></span>' . $wp_v . '</span>';                                } else {                                    echo '<span class="textWarning"> <span class="fas fa-exclamation-triangle mR_xs"></span>' . $wp_v . '</span>';                                } ?>                            </th>                        </tr>                        <tr>                            <th data-export-label="WC Version">Woocommerce Installed:</th>                            <th>                                <?php if ($wc_i == 1) {                                    echo '<span class="textSuccess"> <span class="far fa-check-circle mR_xs"></span>Yes</span>';                                } else {                                    echo '<span class="textWarning"> <span class="fas fa-exclamation-triangle mR_xs"></span>No</span>';                                } ?>                            </th>                        </tr>                        <?php if ($wc_i == 1) { ?>                            <tr>                                <th data-export-label="WC Version">Woocommerce Version:</th>                                <th><?php if ($wc_v > 4.8) {                                        echo '<span class="textSuccess"> <span class="far fa-check-circle mR_xs"></span>' . $wc_v . '</span>';                                    } else {                                        echo '<span class="textWarning"> <span class="fas fa-exclamation-triangle mR_xs"></span>' . $wc_v . '</span>';                                    } ?>                                </th>                            </tr>                            <tr>                                <th data-export-label="WC Version">Email From Name:</th>                                <th>                                    <?php if ($from_name) {                                        echo '<span class="textSuccess"> <span class="far fa-check-circle mR_xs"></span>' . $from_name . '</span>';                                    } else {                                        echo '<span class="textWarning"> <span class="fas fa-exclamation-triangle"></span></span>';                                    } ?>                                </th>                            </tr>                            <tr>                                <th data-export-label="WC Version">From Email Address:</th>                                <th>                                    <?php if ($from_email) {                                        echo '<span class="textSuccess"> <span class="far fa-check-circle mR_xs"></span>' . $from_email . '</span>';                                    } else {                                        echo '<span class="textWarning"> <span class="fas fa-exclamation-triangle"></span></span>';                                    } ?>                                </th>                            </tr>                        <?php }                        do_action('mp_status_table_item_sec'); ?>                        </tbody>                    </table>                </div>            </div>            <?php        }    }    new MPWPB_Status();}