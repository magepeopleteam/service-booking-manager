<?php
	if ( ! defined( 'ABSPATH' ) ) {
		die;
	}
	$post_id           = $post_id ?? get_the_id();
	$extra_services           = $extra_services ?? MPWPB_Function::get_post_info( $post_id, 'mpwpb_extra_service', array() );
	if ( sizeof( $extra_services ) > 0 ) {
		?>
		<table class="layoutFixed">
			<tbody>
			<?php
				foreach ( $extra_services as $group_service ) {
					$group_service_name = array_key_exists( 'group_service', $group_service ) ? $group_service['group_service'] : '';
					$service_infos      = array_key_exists( 'group_service_info', $group_service ) ? $group_service['group_service_info'] : [];
					$count              = count( $service_infos );
					if ( $group_service_name && $count > 0 ) {
						?>
						<tr>
							<td rowspan="<?php echo esc_attr( $count ); ?>" colspan="2" class="verticalTop"><strong><?php echo esc_html( $group_service_name ); ?></strong></td>
							<?php MPWPB_Details_Layout::extra_service_layout( $post_id, $service_infos[0], $group_service_name ); ?>
						</tr>
						<?php
						if ( $count > 1 ) {
							for ( $i = 1; $i < $count; $i ++ ) {
								MPWPB_Details_Layout::extra_service_layout( $post_id, $service_infos[ $i ], $group_service_name );
							}
						}
					}
				}
			?>
			</tbody>
		</table>
		<?php
	}