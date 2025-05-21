<?php
	/*
   * @Author 		engr.sumonazma@gmail.com
   * Copyright: 	mage-people.com
   */
	if ( ! defined( 'ABSPATH' ) ) {
		die;
	} // Cannot access pages directly.
	if ( ! class_exists('MPWPB_Global_Style') ) {
		class MPWPB_Global_Style {
			public function __construct() {
				add_action( 'wp_head', array( $this, 'add_global_style' ), 100 );
				add_action( 'admin_head', array( $this, 'add_global_style' ), 100 );
			}
			public function add_global_style() {
				$default_color   = MPWPB_Global_Function::get_style_settings( 'default_text_color', '#303030' );
				$theme_color     = MPWPB_Global_Function::get_style_settings( 'theme_color', '#f12971' );
				$alternate_color = MPWPB_Global_Function::get_style_settings( 'theme_alternate_color', '#fff' );
				$warning_color   = MPWPB_Global_Function::get_style_settings( 'warning_color', '#E67C30' );
				$default_fs      = MPWPB_Global_Function::get_style_settings( 'default_font_size', '14' ) . 'px';
				$fs_h1           = MPWPB_Global_Function::get_style_settings( 'font_size_h1', '35' ) . 'px';
				$fs_h2           = MPWPB_Global_Function::get_style_settings( 'font_size_h2', '30' ) . 'px';
				$fs_h3           = MPWPB_Global_Function::get_style_settings( 'font_size_h3', '25' ) . 'px';
				$fs_h4           = MPWPB_Global_Function::get_style_settings( 'font_size_h4', '22' ) . 'px';
				$fs_h5           = MPWPB_Global_Function::get_style_settings( 'font_size_h5', '18' ) . 'px';
				$fs_h6           = MPWPB_Global_Function::get_style_settings( 'font_size_h6', '16' ) . 'px';
				$fs_label        = MPWPB_Global_Function::get_style_settings( 'font_size_label', '16' ) . 'px';
				$button_fs       = MPWPB_Global_Function::get_style_settings( 'button_font_size', '16' ) . 'px';
				$button_color    = MPWPB_Global_Function::get_style_settings( 'button_color', $alternate_color );
				$button_bg       = MPWPB_Global_Function::get_style_settings( 'button_bg', '#ea8125' );
				$section_bg      = MPWPB_Global_Function::get_style_settings( 'section_bg', '#FAFCFE' );
				?>
				<style>
					:root {
						--mpwpb_container_Width: 1320px;
						--mpwpb_sidebarLeft: 280px;
						--mpwpb_sidebar_right: 300px;
						--mpwpb_main_section: calc(100% - 300px);
						--mpwpb_dmpl: 40px;
						--mpwpb_dmp: 20px;
						--mpwpb_dmp_negetive: -20px;
						--mpwpb_dmp_xs: 10px;
						--mpwpb_dmp_xs_negative: -10px;
						--mpwpb_dbrl: 10px;
						--mpwpb_dbr: 5px;
						--mpwpb_shadow: 0 0 2px #665F5F7A;
					}
					/*****Font size********/
					:root {
						--mpwpb_fs: <?php echo esc_attr($default_fs); ?>;
						--mpwpb_fw: normal;
						--mpwpb_fs_small: 10px;
						--mpwpb_fs_label: <?php echo esc_attr($fs_label); ?>;
						--mpwpb_fs_h6: <?php echo esc_attr($fs_h6); ?>;
						--mpwpb_fs_h5: <?php echo esc_attr($fs_h5); ?>;
						--mpwpb_fs_h4: <?php echo esc_attr($fs_h4); ?>;
						--mpwpb_fs_h3: <?php echo esc_attr($fs_h3); ?>;
						--mpwpb_fs_h2: <?php echo esc_attr($fs_h2); ?>;
						--mpwpb_fs_h1: <?php echo esc_attr($fs_h1); ?>;
						--mpwpb_fw-thin: 300; /*font weight medium*/
						--mpwpb_fw-normal: 500; /*font weight medium*/
						--mpwpb_fw-medium: 600; /*font weight medium*/
						--mpwpb_fw-bold: bold; /*font weight bold*/
					}
					/*****Button********/
					:root {
						--mpwpb_button_bg: <?php echo esc_attr($button_bg); ?>;
						--mpwpb_button_color: <?php echo esc_attr($button_color); ?>;
						--mpwpb_button_fs: <?php echo esc_attr($button_fs); ?>;
						--mpwpb_button_height: 40px;
						--mpwpb_button_height_xs: 30px;
						--mpwpb_button_width: 120px;
						--mpwpb_button_shadows: 0 8px 12px rgb(51 65 80 / 6%), 0 14px 44px rgb(51 65 80 / 11%);
					}
					/*******Color***********/
					:root {
						--d_color: <?php echo esc_attr($default_color); ?>;
						--mpwpb_color_border: #DDD;
						--mpwpb_color_active: #0E6BB7;
						--mpwpb_color_section: <?php echo esc_attr($section_bg); ?>;
						--mpwpb_color_theme: <?php echo esc_attr($theme_color); ?>;
						--mpwpb_color_theme_ee: <?php echo esc_attr($theme_color).'ee'; ?>;
						--mpwpb_color_theme_cc: <?php echo esc_attr($theme_color).'cc'; ?>;
						--mpwpb_color_theme_aa: <?php echo esc_attr($theme_color).'aa'; ?>;
						--mpwpb_color_theme_88: <?php echo esc_attr($theme_color).'88'; ?>;
						--mpwpb_color_theme_77: <?php echo esc_attr($theme_color).'77'; ?>;
						--mpwpb_color_theme_alter: <?php echo esc_attr($alternate_color); ?>;
						--mpwpb_color_warning: <?php echo esc_attr($warning_color); ?>;
						--mpwpb_color_black: #000;
						--mpwpb_color_success: #03A9F4;
						--mpwpb_color_danger: #C00;
						--mpwpb_color_required: #C00;
						--mpwpb_color_white: #FFFFFF;
						--mpwpb_color_light: #F2F2F2;
						--mpwpb_color_light_1: #BBB;
						--mpwpb_color_light_2: #EAECEE;
						--mpwpb_color_light_3: #878787;
						--mpwpb_color_light_4: #f9f9f9;
						--mpwpb_color_info: #666;
						--mpwpb_color_yellow: #FEBB02;
						--mpwpb_color_blue: #815DF2;
						--mpwpb_color_navy_blue: #007CBA;
						--mpwpb_color_1: #0C5460;
						--mpwpb_color_2: #0CB32612;
						--mpwpb_color_3: #FAFCFE;
						--mpwpb_color_4: #6148BA;
						--mpwpb_color_5: #BCB;
					}
					@media only screen and (max-width: 1100px) {
						:root {
							--mpwpb_fs: 14px;
							--mpwpb_fs_small: 12px;
							--mpwpb_fs_label: 15px;
							--mpwpb_fs_h4: 20px;
							--mpwpb_fs_h3: 22px;
							--mpwpb_fs_h2: 25px;
							--mpwpb_fs_h1: 30px;
							--mpwpb_dmpl: 32px;
							--mpwpb_dmp: 16px;
							--mpwpb_dmp_negetive: -16px;
							--mpwpb_dmp_xs: 8px;
							--mpwpb_dmp_xs_negative: -8px;
						}
					}
					@media only screen and (max-width: 700px) {
						:root {
							--mpwpb_fs: 12px;
							--mpwpb_fs_small: 10px;
							--mpwpb_fs_label: 13px;
							--mpwpb_fs_h6: 15px;
							--mpwpb_fs_h5: 16px;
							--mpwpb_fs_h4: 18px;
							--mpwpb_fs_h3: 20px;
							--mpwpb_fs_h2: 22px;
							--mpwpb_fs_h1: 24px;
							--mpwpb_dmp: 10px;
							--mpwpb_dmp_xs: 5px;
							--mpwpb_dmp_xs_negative: -5px;
							--mpwpb_button_fs: 14px;
						}
					}
				</style>
				<?php
			}
		}
		new MPWPB_Global_Style();
	}