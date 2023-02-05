<?php
	// Template Name: Default Theme
	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}
	$post_id = $post_id ?? get_the_id();
?>
	<div class="mpStyle mptbm_default_theme">
		<div class="mpContainer">
			<div class="_infoLayout_mT">
				<?php include( MPWPB_Function::template_path( 'layout/title_details_page.php' ) ); ?>
			</div>
			<div class="dLayout_xs">
				<?php do_action( 'mpwpb_super_slider', $post_id, 'mpwpb_slider_images' ); ?>
			</div>
			<div class="mpRow mpwpb_registration">
				<div class="leftSidebar">
					<?php do_action( 'mpwpb_category_list',$post_id ); ?>
				</div>
				<div class="mainSection">
					<div class="mpwpb_registration_section">
						<?php include( MPWPB_Function::template_path( 'registration/car_wash_registration.php' ) ); ?>
					</div>
				</div>
			</div>
		</div>
	</div>
<?php do_action( 'mpwpb_after_details_page' ); ?>