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
<!--			<div class="dLayout_xs">-->
<!--				--><?php //do_action( 'mpwpb_super_slider', $post_id, 'mpwpb_slider_images' ); ?>
<!--			</div>-->
			<?php include( MPWPB_Function::template_path( 'registration/registration.php' ) ); ?>
		</div>
	</div>
<?php do_action( 'mpwpb_after_details_page' ); ?>