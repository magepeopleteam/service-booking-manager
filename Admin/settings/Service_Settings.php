<?php
/**
 * @author Rubel Mia rubelcuet10@gmail.com>
 * @license mage-people.com
 * @var 1.0.0
 */
if (!defined('ABSPATH'))
    die;
if (!class_exists('Service_Settings')) {
    class Service_Settings{

        public function __construct() {
            add_action('add_mpwpb_settings_tab_content', [$this, 'service_settings']);
            add_action('save_post', [$this, 'add_custom_meta_on_post_create'], 10, 3);
        }

        function add_custom_meta_on_post_create( $post_id, $post, $update ) {
            if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
            if ($update) return;
            if ( $post->post_type === 'mpwpb_item') {
                add_post_meta($post_id, 'mpwpb_service_multiple_category_check', 'on', true);
                add_post_meta($post_id, 'mpwpb_multiple_service_select', 'on', true);
            }
        }
        public function service_settings( $post_id ){
            $multiple_category_check = get_post_meta( $post_id, 'mpwpb_service_multiple_category_check', true );
            $multiple_service_check = get_post_meta( $post_id, 'mpwpb_multiple_service_select', true );
            $checked = '';
            $mp_service_checked = '';
            if( $multiple_category_check === 'on' ){
                $checked = 'checked';
            }
            if( $multiple_service_check === 'on' ){
                $mp_service_checked = 'checked';
            }
            ?>

            <div class="tabsItem" data-tabs="#mpwpb_service_settings">
                <header>
                    <h2><?php esc_html_e('Service Settings', 'service-booking-manager'); ?></h2>
                    <span><?php esc_html_e('Service Settings will be here.', 'service-booking-manager'); ?></span>
                </header>
                <section>
                    <label class="label">

                        <p><?php esc_html_e('Enable Multiple Category Check', 'service-booking-manager'); ?></p>

                        <label class="roundSwitchLabel">
                            <input type="checkbox" name="mpwpb_service_multiple_category_check" <?php echo esc_attr( $checked )?>>
                            <span class="roundSwitch" data-collapse-target="#mpwpb_service_multiple_category_check"></span>
                        </label>
                    </label>
                </section>
                <section>
                    <label class="label">
                        <p><?php esc_html_e('Enable Multiple Service Select', 'service-booking-manager'); ?></p>

                        <label class="roundSwitchLabel">
                            <input type="checkbox" name="mpwpb_multiple_service_select" <?php echo esc_attr( $mp_service_checked )?>>
                            <span class="roundSwitch" data-collapse-target="#mpwpb_multiple_service_select"></span>
                        </label>
                    </label>
                </section>

                <?php $this->page_header_fields( $post_id ); ?>
            </div>
        <?php }

        /**
         * "Page Header (Featured Box)" fields, rendered inside the Service
         * Settings tab in the classic editor and as its own card in the modern
         * one (see page_header_settings() below) -- both call this single copy.
         *
         * These exist because the header used to print the site name and an
         * auto-excerpt with no setting anywhere to change either, which is what
         * made unexpected text appear there with nothing in Service Settings to
         * account for it.
         */
        public function page_header_fields( $post_id ) {
            // Both switches default to 'on' while the meta is still unset, so
            // existing services keep rendering exactly the header they render
            // today -- only an explicit save can turn one off.
            $badge_status = MPWPB_Global_Function::get_post_info( $post_id, 'mpwpb_hero_badge_status', 'on' );
            $badge_text = MPWPB_Global_Function::get_post_info( $post_id, 'mpwpb_hero_badge_text', '' );
            $subtitle_status = MPWPB_Global_Function::get_post_info( $post_id, 'mpwpb_hero_subtitle_status', 'on' );
            $subtitle_text = MPWPB_Global_Function::get_post_info( $post_id, 'mpwpb_hero_subtitle', '' );
            ?>
            <section class="section">
                <h2><?php esc_html_e('Page Header (Featured Box)', 'service-booking-manager'); ?></h2>
                <span><?php esc_html_e('Controls the badge and the short description printed over the featured image at the top of the service page.', 'service-booking-manager'); ?></span>
            </section>
            <?php // Marks these fields as present in the submitted form. Without it a
                  // save from a screen that does not render them would read the two
                  // unchecked-by-absence switches as "off" and silently hide the header. ?>
            <input type="hidden" name="mpwpb_hero_fields_present" value="1"/>
            <section>
                <label class="label">
                    <p><?php esc_html_e('Show Header Badge', 'service-booking-manager'); ?></p>
                    <label class="roundSwitchLabel">
                        <input type="checkbox" name="mpwpb_hero_badge_status" <?php checked( $badge_status, 'on' ); ?>>
                        <span class="roundSwitch" data-collapse-target="#mpwpb_hero_badge_status"></span>
                    </label>
                </label>
            </section>
            <section>
                <label class="label">
                    <p><?php esc_html_e('Header Badge Text', 'service-booking-manager'); ?></p>
                    <input type="text" name="mpwpb_hero_badge_text" class="formControl" value="<?php echo esc_attr( $badge_text ); ?>" placeholder="<?php echo esc_attr( get_bloginfo('name') ); ?>"/>
                </label>
                <p class="description"><?php esc_html_e('Small pill shown above the service title. Leave empty to use the site name (the default).', 'service-booking-manager'); ?></p>
            </section>
            <section>
                <label class="label">
                    <p><?php esc_html_e('Show Header Description', 'service-booking-manager'); ?></p>
                    <label class="roundSwitchLabel">
                        <input type="checkbox" name="mpwpb_hero_subtitle_status" <?php checked( $subtitle_status, 'on' ); ?>>
                        <span class="roundSwitch" data-collapse-target="#mpwpb_hero_subtitle_status"></span>
                    </label>
                </label>
            </section>
            <section>
                <label class="label">
                    <p><?php esc_html_e('Header Description', 'service-booking-manager'); ?></p>
                    <textarea name="mpwpb_hero_subtitle" class="formControl" rows="3"><?php echo esc_textarea( $subtitle_text ); ?></textarea>
                </label>
                <p class="description"><?php esc_html_e('Leave empty to fall back to the post excerpt, then the Service Sub Title, then the post content, then the Service Overview content.', 'service-booking-manager'); ?></p>
            </section>
            <?php
        }

        /** Modern-editor card: the same fields in their own tab container. */
        public function page_header_settings( $post_id ) {
            ?>
            <div class="tabsItem" data-tabs="#mpwpb_page_header_settings">
                <header>
                    <h2><?php esc_html_e('Page Header', 'service-booking-manager'); ?></h2>
                    <span><?php esc_html_e('Badge and description over the featured image.', 'service-booking-manager'); ?></span>
                </header>
                <?php $this->page_header_fields( $post_id ); ?>
            </div>
            <?php
        }

    }

    new Service_Settings();
}