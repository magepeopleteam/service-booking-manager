<?php
	// Template Name: Default Theme
	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}
	$post_id = $post_id ?? get_the_id();
?>
	<div class="mpStyle mptbm_default_theme">
		<div class="mpContainer">
			<?php include( MPWPB_Function::template_path( 'layout/title_details_page.php' ) ); ?>
			<?php include( MPWPB_Function::template_path( 'registration/registration.php' ) ); ?>
		</div>
	</div>
<?php do_action( 'mpwpb_after_details_page' ); ?>