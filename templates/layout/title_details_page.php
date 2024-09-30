<?php
	/*
   * @Author 		engr.sumonazma@gmail.com
   * Copyright: 	mage-people.com
   */
	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}
	$post_id   = $post_id ?? get_the_id();
	$title     = MP_Global_Function::get_post_info( $post_id, 'mpwpb_shortcode_title' );
	$sub_title = MP_Global_Function::get_post_info( $post_id, 'mpwpb_shortcode_sub_title' );
	if ( $title ) {
		?>
		<div class="mp_title _mTB">
			<h2><?php echo esc_html( $title ); ?></h2>
			<?php if ( $sub_title ) { ?>
				<p><?php echo esc_html( $sub_title ); ?></p>
				<?php MPWPB_Static_Template::get_ratings(); ?>
			<?php } ?>
		</div>
		<?php
	}