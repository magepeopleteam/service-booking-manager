<?php
	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}
	$post_id      = $post_id ?? get_the_id();
	$all_services = $all_services ?? MPWPB_Function::get_post_info( $post_id, 'mpwpb_category_infos', array() );
	//echo '<pre>'; print_r( $all_services ); echo '</pre>';
	if ( sizeof( $all_services ) > 0 ) {
		?>
		<form action="" method="post" class="mpwpb_registration">
			<div class="mpTabs leftTabs">
				<div class="leftSidebar">
					<div class="dLayout_xs">
						<ul class="tabLists fdColumn">
							<li class="mptbm_service_tab" data-tabs-target="#mpwpb_service" data-open-icon="fab fa-servicestack" data-close-icon="fas fa-check-square">
								<span class="fab fa-servicestack" data-icon></span><?php esc_html_e( 'Service', 'mpwpb_plugin' ); ?>
							</li>
							<li class="mptbm_extra_service mpDisabled" data-tabs-target="#mpwpb_extra_service" data-open-icon="fas fa-clipboard-list" data-close-icon="fas fa-check-square">
								<span class="fas fa-clipboard-list" data-icon></span><?php esc_html_e( 'Extra Service', 'mpwpb_plugin' ); ?>
							</li>
							<li class="mptbm_extra_date_time mpDisabled" data-tabs-target="#mpwpb_date_time" data-open-icon="fas fa-calendar-alt" data-close-icon="fas fa-check-square">
								<span class="fas fa-calendar-alt" data-icon></span><?php esc_html_e( 'Date & Time', 'mpwpb_plugin' ); ?>
							</li>
						</ul>
					</div>
				</div>
				<div class="mainSection">
					<div class="tabsContent dLayout">
						<div class="tabsItem" data-tabs="#mpwpb_service">
							<?php
								include( MPWPB_Function::template_path( 'registration/category_selection.php' ) );
								include( MPWPB_Function::template_path( 'registration/service_selection.php' ) );
							?>
							<button class="_themeButton_xs_min_100_dNone mpwpb_next_extra_service" type="button">
								<?php esc_html_e( 'Next', 'mpwpb_plugin' ); ?>
								<span class="fas fa-chevron-right mL_xs"></span>
							</button>
						</div>
						<div class="tabsItem" data-tabs="#mpwpb_extra_service">
							<?php include( MPWPB_Function::template_path( 'registration/extra_services.php' ) ); ?>
							<div class="justifyBetween">
								<button class="_themeButton_xs_min_100 mpwpb_prev_service" type="button">
									<span class="fas fa-chevron-left mR_xs"></span>
									<?php esc_html_e( 'Prev', 'mpwpb_plugin' ); ?>
								</button>
								<button class="_themeButton_xs_min_100 mpwpb_next_date_time" type="button">
									<?php esc_html_e( 'Next', 'mpwpb_plugin' ); ?>
									<span class="fas fa-chevron-right mL_xs"></span>
								</button>
							</div>
						</div>
						<div class="tabsItem" data-tabs="#mpwpb_date_time">
							<?php include( MPWPB_Function::template_path( 'registration/date_time_select.php' ) ); ?>
							<button class="_themeButton_xs_min_100_mT_xs mpwpb_prev_ex_service" type="button">
								<span class="fas fa-chevron-left mR_xs"></span>
								<?php esc_html_e( 'Prev', 'mpwpb_plugin' ); ?>
							</button>
						</div>
					</div>
					<div class="divider"></div>
					<?php include( MPWPB_Function::template_path( 'registration/book_now.php' ) ); ?>
				</div>
			</div>
		</form>
	<?php } ?>