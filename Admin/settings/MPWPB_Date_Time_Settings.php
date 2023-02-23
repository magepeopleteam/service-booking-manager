<?php
	if ( ! defined( 'ABSPATH' ) ) {
		die;
	} // Cannot access pages directly.
	if ( ! class_exists( 'MPWPB_Date_Time_Settings' ) ) {
		class MPWPB_Date_Time_Settings {
			public function __construct() {
				add_action( 'add_mpwpb_settings_tab_content', [ $this, 'date_time_settings' ], 10, 1 );
				/************************/
				add_action( 'wp_ajax_get_mpwpb_end_time_slot', array( $this, 'get_mpwpb_end_time_slot' ) );
				add_action( 'wp_ajax_nopriv_get_mpwpb_end_time_slot', array( $this, 'get_mpwpb_end_time_slot' ) );
				/***/
				add_action( 'wp_ajax_get_mpwpb_start_break_time', array( $this, 'get_mpwpb_start_break_time' ) );
				add_action( 'wp_ajax_nopriv_get_mpwpb_start_break_time', array( $this, 'get_mpwpb_start_break_time' ) );
				/***/
				add_action( 'wp_ajax_get_mpwpb_end_break_time', array( $this, 'get_mpwpb_end_break_time' ) );
				add_action( 'wp_ajax_nopriv_get_mpwpb_end_break_time', array( $this, 'get_mpwpb_end_break_time' ) );
				/************************/
				add_action( 'mpwpb_settings_save', [ $this, 'save_date_time_settings' ], 10, 1 );
			}
			public function date_time_settings( $post_id ) {
				$date_format = MPWPB_Function::date_picker_format();
				$now         = date_i18n( $date_format, strtotime( current_time( 'Y-m-d' ) ) );
				?>
				<div class="tabsItem mpwpb_settings_date_time" data-tabs="#mpwpb_settings_date_time">
					<h5><?php echo get_the_title( $post_id ) . ' ' . esc_html__( 'Date & Time Settings', 'mpwpb_plugin' ); ?></h5>
					<div class="divider"></div>
					<div class="mpTabs tabBorder">
						<ul class="tabLists">
							<li data-tabs-target="#mpwpb_date_time_general">
								<span class="fas fa-home"></span><?php esc_html_e( 'General', 'mpwpb_plugin' ); ?>
							</li>
							<li data-tabs-target="#mpwpb_date_time_schedule">
								<span class="far fa-clock"></span><?php esc_html_e( 'schedule', 'mpwpb_plugin' ); ?>
							</li>
							<li data-tabs-target="#mpwpb_date_time_off_day">
								<span class="fas fa-calendar-alt"></span><?php esc_html_e( 'Off Days & Dates', 'mpwpb_plugin' ); ?>
							</li>
						</ul>
						<div class="tabsContent tab-content">
							<div class="tabsItem" data-tabs="#mpwpb_date_time_general">
								<?php
									$start_date         = MPWPB_Function::get_post_info( $post_id, 'mpwpb_service_start_date' );
									$hidden_start_date  = $start_date ? date( 'Y-m-d', strtotime( $start_date ) ) : '';
									$visible_start_date = $start_date ? date_i18n( $date_format, strtotime( $start_date ) ) : '';
									/**************/
									$end_date         = MPWPB_Function::get_post_info( $post_id, 'mpwpb_service_end_date' );
									$hidden_end_date  = $end_date ? date( 'Y-m-d', strtotime( $end_date ) ) : '';
									$visible_end_date = $end_date ? date_i18n( $date_format, strtotime( $end_date ) ) : '';
									/********************/
									$time_slot = MPWPB_Function::get_post_info( $post_id, 'mpwpb_time_slot_length' );
									$capacity  = MPWPB_Function::get_post_info( $post_id, 'mpwpb_capacity_per_session' );
								?>
								<label>
									<span class="max_200"><?php esc_html_e( 'Service Start Date', 'mpwpb_plugin' ); ?><span class="textRequired">&nbsp;*</span></span>
									<input type="hidden" name="mpwpb_service_start_date" value="<?php echo esc_attr( $hidden_start_date ); ?>" required/>
									<input type="text" readonly required name="" class="formControl date_type" value="<?php echo esc_attr( $visible_start_date ); ?>" placeholder="<?php echo esc_attr( $now ); ?>"/>
								</label>
								<div class="divider"></div>
								<label>
									<span class="max_200"><?php esc_html_e( 'Service end Date', 'mpwpb_plugin' ); ?><span class="textRequired">&nbsp;*</span></span>
									<input type="hidden" name="mpwpb_service_end_date" value="<?php echo esc_attr( $hidden_end_date ); ?>" required/>
									<input type="text" readonly required name="" class="formControl date_type" value="<?php echo esc_attr( $visible_end_date ); ?>" placeholder="<?php echo esc_attr( $now ); ?>"/>
								</label>
								<div class="divider"></div>
								<label>
									<span class="max_200"><?php esc_html_e( 'Time Slot Length', 'mpwpb_plugin' ); ?></span>
									<select class="formControl" name="mpwpb_time_slot_length">
										<option selected disabled><?php esc_html_e( 'Select time slot Length', 'mpwpb_plugin' ); ?></option>
										<option value="10" <?php echo esc_attr( $time_slot == 10 ? 'selected' : '' ); ?>><?php esc_html_e( '10 min', 'mpwpb_plugin' ); ?></option>
										<option value="15" <?php echo esc_attr( $time_slot == 15 ? 'selected' : '' ); ?>><?php esc_html_e( '15 min', 'mpwpb_plugin' ); ?></option>
										<option value="30" <?php echo esc_attr( $time_slot == 30 ? 'selected' : '' ); ?>><?php esc_html_e( '30 min', 'mpwpb_plugin' ); ?></option>
										<option value="60" <?php echo esc_attr( $time_slot == 60 ? 'selected' : '' ); ?>><?php esc_html_e( '1 Hour', 'mpwpb_plugin' ); ?></option>
										<option value="120" <?php echo esc_attr( $time_slot == 120 ? 'selected' : '' ); ?>><?php esc_html_e( '2 Hour', 'mpwpb_plugin' ); ?></option>
										<option value="180" <?php echo esc_attr( $time_slot == 180 ? 'selected' : '' ); ?>><?php esc_html_e( '3 Hour', 'mpwpb_plugin' ); ?></option>
									</select>
								</label>
								<div class="divider"></div>
								<label>
									<span class="max_200"><?php esc_html_e( 'Capacity per Session', 'mpwpb_plugin' ); ?></span>
									<input class="formControl" name="mpwpb_capacity_per_session" type="number" value="<?php echo esc_attr( $capacity ); ?>" placeholder="Ex. 25"/>
								</label>
							</div>
							<div class="tabsItem" data-tabs="#mpwpb_date_time_schedule">
								<table>
									<thead>
									<tr>
										<th><?php esc_html_e( 'Day', 'mpwpb_plugin' ); ?></th>
										<th><?php esc_html_e( 'Start Time', 'mpwpb_plugin' ); ?></th>
										<th><?php esc_html_e( 'To', 'mpwpb_plugin' ); ?></th>
										<th><?php esc_html_e( 'End Time', 'mpwpb_plugin' ); ?></th>
										<th colspan="3" style="background-color: #e3d3d3;"><?php esc_html_e( 'Break Time', 'mpwpb_plugin' ); ?></th>
									</tr>
									</thead>
									<tbody>
									<?php
										$this->time_slot_tr( $post_id, 'default' );
										$days = MPWPB_Function::week_day();
										foreach ( $days as $key => $day ) {
											$this->time_slot_tr( $post_id, $key );
										}
									?>
									</tbody>
								</table>
							</div>
							<div class="tabsItem" data-tabs="#mpwpb_date_time_off_day">
								<table>
									<tr>
										<th><?php esc_html_e( 'Off Day', 'mpwpb_plugin' ); ?></th>
										<td colspan="2">
											<?php
												$off_days      = MPWPB_Function::get_post_info( $post_id, 'mpwpb_off_days' );
												$days          = MPWPB_Function::week_day();
												$off_day_array = explode( ',', $off_days );
											?>
											<div class="groupCheckBox">
												<input type="hidden" name="mpwpb_off_days" value="<?php echo esc_attr( $off_days ); ?>"/>
												<?php foreach ( $days as $key => $day ) { ?>
													<label class="customCheckboxLabel">
														<input type="checkbox" <?php echo in_array( $key, $off_day_array ) ? 'checked' : ''; ?> data-checked="<?php echo esc_attr( $key ); ?>"/>
														<span class="customCheckbox"><?php echo esc_html( $day ); ?></span>
													</label>
												<?php } ?>
											</div>
										</td>
									</tr>
									<tr>
										<th><?php esc_html_e( 'Off Dates', 'mpwpb_plugin' ); ?></th>
										<td colspan="2">
											<div class="mp_settings_area">
												<div class="mp_item_insert">
													<?php
														$off_day_lists = MPWPB_Function::get_post_info( $post_id, 'mpwpb_off_dates', array() );
														if ( sizeof( $off_day_lists ) ) {
															foreach ( $off_day_lists as $off_day ) {
																if ( $off_day ) {
																	$hidden_off_day  = date( 'Y-m-d', strtotime( $off_day ) );
																	$visible_off_day = date_i18n( $date_format, strtotime( $off_day ) );
																	?>
																	<label>
																		<input type="hidden" name="mpwpb_off_dates[]" value="<?php echo esc_attr( $hidden_off_day ); ?>"/>
																		<input value="<?php echo esc_attr( $visible_off_day ); ?>" class="formControl date_type" placeholder="<?php echo esc_attr( $now ); ?>"/>
																	</label>
																	<div class="divider"></div>
																<?php }
															}
														} ?>
												</div>
												<?php MPWPB_Layout::add_new_button( esc_html__( 'Add New Off date', 'mpwpb_plugin' ) ); ?>
												<div class="mp_hidden_content">
													<div class="mp_hidden_item">
														<label>
															<input type="hidden" name="mpwpb_off_dates[]" value=""/>
															<input value="" class="formControl date_type" placeholder="<?php echo esc_attr( $now ); ?>"/>
														</label>
														<div class="divider"></div>
													</div>
												</div>
											</div>
										</td>
									</tr>
								</table>
							</div>
						</div>
					</div>
				</div>
				<?php
			}
			public function time_slot_tr( $post_id, $day ) {
				$start_name       = 'mpwpb_' . $day . '_start_time';
				$default_start_time=$day=='default'?10:'';
				$start_time       = MPWPB_Function::get_post_info( $post_id, $start_name ,$default_start_time);
				$end_name         = 'mpwpb_' . $day . '_end_time';
				$default_end_time=$day=='default'?18:'';
				$end_time         = MPWPB_Function::get_post_info( $post_id, $end_name ,$default_end_time);
				$start_name_break = 'mpwpb_' . $day . '_start_break_time';
				$start_time_break = MPWPB_Function::get_post_info( $post_id, $start_name_break );
				?>
				<tr>
					<th style="text-transform: capitalize;"><?php echo esc_html( $day ); ?></th>
					<td class="mpwpb_start_time" data-day-name="<?php echo esc_attr( $day ); ?>">
						<?php //echo '<pre>'; print_r( $start_time );echo '</pre>'; ?>
						<label>
							<select class="formControl" name="<?php echo esc_attr( $start_name ); ?>">
								<option value="" <?php echo $start_time == '' ? 'selected' : ''; ?>>
									<?php $this->default_text( $day ); ?>
								</option>
								<?php $this->time_slot( $start_time ); ?>
							</select>
						</label>
					</td>
					<td class="textCenter"><strong><?php esc_html_e( 'To', 'mpwpb_plugin' ); ?></strong></td>
					<td class="mpwpb_end_time">
						<?php $this->end_time_slot( $post_id, $day, $start_time ); ?>
					</td>
					<td style="background-color: #e3d3d3;" class="mpwpb_start_break_time">
						<?php $this->start_break_time_slot( $post_id, $day, $start_time, $end_time ) ?>
					</td>
					<td class="textCenter" style="background-color: #e3d3d3;"><strong><?php esc_html_e( 'To', 'mpwpb_plugin' ); ?></strong></td>
					<td style="background-color: #e3d3d3;" class="mpwpb_end_break_time">
						<?php $this->end_break_time_slot( $post_id, $day, $start_time_break, $end_time ) ?>
					</td>
				</tr>
				<?php
			}
			public function end_time_slot( $post_id, $day, $start_time ) {
				$end_name = 'mpwpb_' . $day . '_end_time';
				$default_end_time=$day=='default'?18:'';
				$end_time = MPWPB_Function::get_post_info( $post_id, $end_name,$default_end_time );
				?>
				<label>
					<select class="formControl " name="<?php echo $end_name; ?>">
						<?php if ( $start_time == '' ) { ?>
							<option value="" selected><?php $this->default_text( $day ); ?></option>
						<?php } ?>
						<?php $this->time_slot( $end_time, $start_time ); ?>
					</select>
				</label>
				<?php
			}
			public function start_break_time_slot( $post_id, $day, $start_time, $end_time = '' ) {
				$start_name_break = 'mpwpb_' . $day . '_start_break_time';
				$start_time_break = MPWPB_Function::get_post_info( $post_id, $start_name_break );
				?>
				<label>
					<select class="formControl" name="<?php echo $start_name_break; ?>">
						<option value="" <?php echo ! $start_time_break ? 'selected' : ''; ?>><?php esc_html_e( 'No Break', 'mpwpb_plugin' ); ?></option>
						<?php $this->time_slot( $start_time_break, $start_time, $end_time ); ?>
					</select>
				</label>
				<?php
			}
			public function end_break_time_slot( $post_id, $day, $start_time_break, $end_time ) {
				$end_name_break = 'mpwpb_' . $day . '_end_break_time';
				$end_time_break = MPWPB_Function::get_post_info( $post_id, $end_name_break );
				?>
				<label>
					<select class="formControl" name="<?php echo $end_name_break; ?>">
						<?php if ( $start_time_break == '' ) { ?>
							<option value="" selected><?php esc_html_e( 'No Break', 'mpwpb_plugin' ); ?></option>
						<?php } ?>
						<?php $this->time_slot( $end_time_break, $start_time_break, $end_time ); ?>
					</select>
				</label>
				<?php
			}
			public function time_slot( $time, $stat_time = '', $end_time = '' ) {
				if ( $stat_time >= 0 || $stat_time == '' ) {
					$time_count = $stat_time == '' ? 0 : $stat_time;
					$end_time   = $end_time != '' ? $end_time : 23.5;
					for ( $i = $time_count; $i <= $end_time; $i = $i + 0.5 ) {
						if ( $stat_time == 'yes' || $i > $time_count ) {
							?>
							<option value="<?php echo esc_attr( $i ); ?>" <?php echo esc_attr( $time != '' && $time == $i ? 'selected' : '' ); ?>><?php echo date_i18n( 'h:i A', $i * 3600 ); ?></option>
							<?php
						}
					}
				}
			}
			public function default_text( $day ) {
				if ( $day == 'default' ) {
					esc_html_e( 'Please select', 'mpwpb_plugin' );
				} else {
					esc_html_e( 'Default', 'mpwpb_plugin' );
				}
			}
			/*************************************/
			public function get_mpwpb_end_time_slot() {
				$post_id    = $_REQUEST['post_id'];
				$day        = $_REQUEST['day_name'];
				$start_time = $_REQUEST['start_time'];
				$this->end_time_slot( $post_id, $day, $start_time );
				die();
			}
			public function get_mpwpb_start_break_time() {
				$post_id    = $_REQUEST['post_id'];
				$day        = $_REQUEST['day_name'];
				$start_time = $_REQUEST['start_time'];
				$end_time   = $_REQUEST['end_time'];
				$this->start_break_time_slot( $post_id, $day, $start_time, $end_time );
				die();
			}
			public function get_mpwpb_end_break_time() {
				$post_id    = $_REQUEST['post_id'];
				$day        = $_REQUEST['day_name'];
				$start_time = $_REQUEST['start_time'];
				$end_time   = $_REQUEST['end_time'];
				$this->end_break_time_slot( $post_id, $day, $start_time, $end_time );
				die();
			}
			/*************************************/
			public function save_date_time_settings( $post_id ) {
				if ( get_post_type( $post_id ) == MPWPB_Function::get_cpt_name() ) {
					//************************************//
					$service_start_date = MPWPB_Function::get_submit_info( 'mpwpb_service_start_date' );
					$service_start_date = $service_start_date?date( 'Y-m-d', strtotime( $service_start_date ) ):'';
					update_post_meta( $post_id, 'mpwpb_service_start_date', $service_start_date );
					$service_end_date = MPWPB_Function::get_submit_info( 'mpwpb_service_end_date' );
					$service_end_date = $service_end_date?date( 'Y-m-d', strtotime( $service_end_date ) ):'';
					update_post_meta( $post_id, 'mpwpb_service_end_date', $service_end_date );
					$time_slot_length = MPWPB_Function::get_submit_info( 'mpwpb_time_slot_length' );
					update_post_meta( $post_id, 'mpwpb_time_slot_length', $time_slot_length );
					$capacity_per_session = MPWPB_Function::get_submit_info( 'mpwpb_capacity_per_session' );
					update_post_meta( $post_id, 'mpwpb_capacity_per_session', $capacity_per_session );
					//**********************//
					$this->save_schedule( $post_id, 'default' );
					$days = MPWPB_Function::week_day();
					foreach ( $days as $key => $day ) {
						$this->save_schedule( $post_id, $key );
					}
					//**********************//
					$off_days = MPWPB_Function::get_submit_info( 'mpwpb_off_days', array() );
					update_post_meta( $post_id, 'mpwpb_off_days', $off_days );
					//**********************//
					$off_dates = MPWPB_Function::get_submit_info( 'mpwpb_off_dates', array() );
					$_off_dates     = array();
					if ( sizeof( $off_dates ) > 0 ) {
						foreach ( $off_dates as $off_date ) {
							if ( $off_date ) {
								$_off_dates[] = date( 'Y-m-d', strtotime( $off_date ) );
							}
						}
					}
					update_post_meta( $post_id, 'mpwpb_off_dates', $_off_dates );
				}
			}
			public function save_schedule( $post_id, $day ) {
				$start_name = 'mpwpb_' . $day . '_start_time';
				$start_time = MPWPB_Function::get_submit_info( $start_name );
				update_post_meta( $post_id, $start_name, $start_time );
				$end_name = 'mpwpb_' . $day . '_end_time';
				$end_time = MPWPB_Function::get_submit_info( $end_name );
				update_post_meta( $post_id, $end_name, $end_time );
				$start_name_break = 'mpwpb_' . $day . '_start_break_time';
				$start_time_break = MPWPB_Function::get_submit_info( $start_name_break );
				update_post_meta( $post_id, $start_name_break, $start_time_break );
				$end_name_break = 'mpwpb_' . $day . '_end_break_time';
				$end_time_break = MPWPB_Function::get_submit_info( $end_name_break );
				update_post_meta( $post_id, $end_name_break, $end_time_break );
			}
		}
		new MPWPB_Date_Time_Settings();
	}