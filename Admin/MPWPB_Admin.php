<?php
	if ( ! defined( 'ABSPATH' ) ) {
		die;
	} // Cannot access pages directly.
	if ( ! class_exists( 'MPWPB_Admin' ) ) {
		class MPWPB_Admin {
			public function __construct() {
				$this->load_file();
				//add_action( 'init', [ $this, 'add_taxonomy' ] );
				add_action( 'upgrader_process_complete', [ $this, 'flush_rewrite' ] );
			}
			private function load_file(): void {
				require_once MPWPB_PLUGIN_DIR . '/Admin/MAGE_Setting_API.php';
				require_once MPWPB_PLUGIN_DIR . '/Admin/MPWPB_Taxonomy.php';
				//require_once MPWPB_PLUGIN_DIR . '/Admin/MPTBM_Dummy_Import.php';
				require_once MPWPB_PLUGIN_DIR . '/Admin/MPWPB_Settings_Global.php';
				require_once MPWPB_PLUGIN_DIR . '/Admin/MPWPB_Hidden_Product.php';
				require_once MPWPB_PLUGIN_DIR . '/Admin/MP_Select_Icon_image.php';
				require_once MPWPB_PLUGIN_DIR . '/Admin/MPWPB_CPT.php';
				require_once MPWPB_PLUGIN_DIR . '/Admin/MPWPB_Quick_Setup.php';
				require_once MPWPB_PLUGIN_DIR . '/Admin/MPWPB_Status.php';
				require_once MPWPB_PLUGIN_DIR . '/Admin/MPWPB_Save.php';
				require_once MPWPB_PLUGIN_DIR . '/Admin/MPWPB_Settings.php';
				require_once MPWPB_PLUGIN_DIR . '/Admin/settings/MPWPB_General_Settings.php';
				require_once MPWPB_PLUGIN_DIR . '/Admin/settings/MPWPB_Price_Settings.php';
				require_once MPWPB_PLUGIN_DIR . '/Admin/settings/MPWPB_Date_Time_Settings.php';
				//require_once MPWPB_PLUGIN_DIR . '/Admin/settings/MPWPB_Gallery_Settings.php';
				//require_once MPWPB_PLUGIN_DIR . '/Admin/settings/MPWPB_FAQ_Settings.php';
			}
			public function add_taxonomy(){
				//new MPTBM_Dummy_Import();
			}
			public function flush_rewrite() {
				flush_rewrite_rules();
			}
		}
		new MPWPB_Admin();
	}