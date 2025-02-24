<?php
	/**
	 * @author Sahahdat Hossain <raselsha@gmail.com>
	 * @license mage-people.com
	 * @var 1.0.0
	 */
	if (!defined('ABSPATH'))
		die;
	if (!class_exists('MPWPB_Faq_Settings')) {
		class MPWPB_Faq_Settings {
			public function __construct() {
				add_action('add_mpwpb_settings_tab_content', [$this, 'faq_settings']);
				add_action('admin_enqueue_scripts', [$this, 'my_custom_editor_enqueue']);
				// save faq data
				add_action('wp_ajax_mpwpb_faq_data_save', [$this, 'save_faq_data_settings']);
				add_action('wp_ajax_nopriv_mpwpb_faq_data_save', [$this, 'save_faq_data_settings']);
				// update faq data
				add_action('wp_ajax_mpwpb_faq_data_update', [$this, 'faq_data_update']);
				add_action('wp_ajax_nopriv_mpwpb_faq_data_update', [$this, 'faq_data_update']);
				// mpwpb_delete_faq_data
				add_action('wp_ajax_mpwpb_faq_delete_item', [$this, 'faq_delete_item']);
				add_action('wp_ajax_nopriv_mpwpb_faq_delete_item', [$this, 'faq_delete_item']);
				// FAQ sort_faq
				add_action('wp_ajax_mpwpb_sort_faq', [$this, 'sort_faq']);
			}

			public function sort_faq() {
				if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'mpwpb_admin_nonce')) {
					wp_send_json_error('Invalid nonce!'); // Prevent unauthorized access
				}
				$post_id = isset($_POST['postID']) ? sanitize_text_field(wp_unslash($_POST['postID'])) : '';
				$sorted_ids = isset($_POST['sortedIDs']) ? array_map('intval', $_POST['sortedIDs']) : [];
				$mpwpb_faq = get_post_meta($post_id, 'mpwpb_faq', true);;
				$new_ordered = [];
				foreach ($sorted_ids as $id) {
					if (isset($mpwpb_faq[$id])) {
						$new_ordered[$id] = $mpwpb_faq[$id];
					}
				}
				update_post_meta($post_id, 'mpwpb_faq', $new_ordered);
				ob_start();
				$resultMessage = esc_html__('Data Updated Successfully', 'service-booking-manager');
				$this->show_faq_data($post_id);
				$html_output = ob_get_clean();
				wp_send_json_success([
					'message' => $resultMessage,
					'html' => $html_output,
				]);
				die;
			}

			public function my_custom_editor_enqueue() {
				// Enqueue necessary scripts
				wp_enqueue_script('jquery');
				wp_enqueue_script('editor');
				wp_enqueue_script('media-upload');
				wp_enqueue_script('thickbox');
				wp_enqueue_style('thickbox');
			}
			public function faq_settings($post_id) {
				$mpwpb_faq_active = MP_Global_Function::get_post_info($post_id, 'mpwpb_faq_active', 'off');
				$active_class = $mpwpb_faq_active == 'on' ? 'mActive' : '';
				$mpwpb_faq_active_checked = $mpwpb_faq_active == 'on' ? 'checked' : '';
				?>
                <div class="tabsItem" data-tabs="#mpwpb_faq_settings">
                    <header>
                        <h2><?php esc_html_e('FAQ Settings', 'service-booking-manager'); ?></h2>
                        <span><?php esc_html_e('FAQ Settings will be here.', 'service-booking-manager'); ?></span>
                    </header>
                    <section class="section">
                        <h2><?php esc_html_e('FAQ Settings', 'service-booking-manager'); ?></h2>
                        <span><?php esc_html_e('FAQ Settings', 'service-booking-manager'); ?></span>
                    </section>
                    <section>
                        <label class="label">
                            <div>
                                <p><?php esc_html_e('Enable FAQ Section', 'service-booking-manager'); ?></p>
                                <span><?php esc_html_e('Enable FAQ Section', 'service-booking-manager'); ?></span>
                            </div>
                            <div>
								<?php MP_Custom_Layout::switch_button('mpwpb_faq_active', $mpwpb_faq_active_checked); ?>
                            </div>
                        </label>
                    </section>
                    <section class="mpwpb-faq-section <?php echo esc_attr($active_class); ?>" data-collapse="#mpwpb_faq_active">
                        <div class="mpwpb-faq-items mB">
							<?php $this->show_faq_data($post_id); ?>
                        </div>
                        <button class="button mpwpb-faq-item-new" data-modal="mpwpb-faq-item-new" type="button"><?php esc_html_e('Add FAQ', 'service-booking-manager'); ?></button>
                    </section>
                    <!-- sidebar collapse open -->
                    <div class="mpwpb-modal-container" data-modal-target="mpwpb-faq-item-new">
                        <div class="mpwpb-modal-content">
                            <span class="mpwpb-modal-close"><i class="fas fa-times"></i></span>
                            <div class="title">
                                <h3><?php esc_html_e('Add F.A.Q.', 'service-booking-manager'); ?></h3>
                                <div id="mpwpb-service-msg"></div>
                            </div>
                            <div class="content">
                                <label>
									<?php esc_html_e('Add Title', 'service-booking-manager'); ?>
                                    <input type="hidden" name="mpwpb_post_id" value="<?php echo esc_attr($post_id); ?>">
                                    <input type="text" name="mpwpb_faq_title">
                                    <input type="hidden" name="mpwpb_faq_item_id">
                                </label>
                                <label>
									<?php esc_html_e('Add Content', 'service-booking-manager'); ?>
                                </label>
								<?php
									$content = '';
									$editor_id = 'mpwpb_faq_content';
									$settings = array(
										'textarea_name' => 'mpwpb_faq_content',
										'media_buttons' => true,
										'textarea_rows' => 10,
									);
									wp_editor($content, $editor_id, $settings);
								?>
                                <div class="mT"></div>
                                <div class="mpwpb_faq_save_buttons">
                                    <p>
                                        <button id="mpwpb_faq_save" class="button button-primary button-large"><?php esc_html_e('Save', 'service-booking-manager'); ?></button>
                                        <button id="mpwpb_faq_save_close" class="button button-primary button-large">save close</button>
                                    <p>
                                </div>
                                <div class="mpwpb_faq_update_buttons" style="display: none;">
                                    <p>
                                        <button id="mpwpb_faq_update" class="button button-primary button-large"><?php esc_html_e('Update and Close', 'service-booking-manager'); ?></button>
                                    <p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
				<?php
			}
			public function show_faq_data($post_id) {
				$mpwpb_faq = get_post_meta($post_id, 'mpwpb_faq', true);
				if (!empty($mpwpb_faq)):
					foreach ($mpwpb_faq as $key => $value) :
						?>
                        <div class="mpwpb-faq-item" data-id="<?php echo esc_attr($key); ?>">
                            <section class="faq-header" data-collapse-target="#faq-content-<?php echo esc_attr($key); ?>">
                                <label class="label">
                                    <p><?php echo esc_html($value['title']); ?></p>
                                    <div class="faq-action">
                                        <span class=""><i class="fas fa-eye"></i></span>
                                        <span class="mpwpb-faq-item-edit" data-modal="mpwpb-faq-item-new"><i class="fas fa-edit"></i></span>
                                        <span class="mpwpb-faq-item-delete"><i class="fas fa-trash"></i></span>
                                    </div>
                                </label>
                            </section>
                            <section class="faq-content mB" data-collapse="#faq-content-<?php echo esc_attr($key); ?>">
								<?php echo wp_kses_post($value['content']); ?>
                            </section>
                        </div>
					<?php
					endforeach;
				endif;
			}
			public function faq_data_update() {
				if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'mpwpb_admin_nonce')) {
					wp_send_json_error('Invalid nonce!'); // Prevent unauthorized access
				}
				$post_id = isset($_POST['mpwpb_faq_postID']) ? sanitize_text_field(wp_unslash($_POST['mpwpb_faq_postID'])) : '';
				$mpwpb_faq_title = isset($_POST['mpwpb_faq_title']) ? sanitize_text_field(wp_unslash($_POST['mpwpb_faq_title'])) : '';
				$mpwpb_faq_content = isset($_POST['mpwpb_faq_content']) ? wp_kses_post(wp_unslash($_POST['mpwpb_faq_content'])) : '';
				$mpwpb_faq = get_post_meta($post_id, 'mpwpb_faq', true);
				$mpwpb_faq = !empty($mpwpb_faq) ? $mpwpb_faq : [];
				$new_data = ['title' => $mpwpb_faq_title, 'content' => $mpwpb_faq_content];
				if (!empty($mpwpb_faq)) {
					if (isset($_POST['mpwpb_faq_itemID'])) {
						$mpwpb_faq[sanitize_text_field(wp_unslash($_POST['mpwpb_faq_itemID']))] = $new_data;
					}
				}
				update_post_meta($post_id, 'mpwpb_faq', $mpwpb_faq);
				ob_start();
				$resultMessage = esc_html__('Data Updated Successfully', 'service-booking-manager');
				$this->show_faq_data($post_id);
				$html_output = ob_get_clean();
				wp_send_json_success([
					'message' => $resultMessage,
					'html' => $html_output,
				]);
				die;
			}
			public function save_faq_data_settings() {
				if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'mpwpb_admin_nonce')) {
					wp_send_json_error('Invalid nonce!'); // Prevent unauthorized access
				}
				$post_id = isset($_POST['mpwpb_faq_postID']) ? sanitize_text_field(wp_unslash($_POST['mpwpb_faq_postID'])) : '';
				update_post_meta($post_id, 'mpwpb_faq_active', 'on');
				$mpwpb_faq_title = isset($_POST['mpwpb_faq_title']) ? sanitize_text_field(wp_unslash($_POST['mpwpb_faq_title'])) : '';
				$mpwpb_faq_content = isset($_POST['mpwpb_faq_content']) ? wp_kses_post(wp_unslash($_POST['mpwpb_faq_content'])) : '';
				$mpwpb_faq = get_post_meta($post_id, 'mpwpb_faq', true);
				$mpwpb_faq = !empty($mpwpb_faq) ? $mpwpb_faq : [];
				$new_data = ['title' => $mpwpb_faq_title, 'content' => $mpwpb_faq_content];
				if (isset($post_id)) {
					array_push($mpwpb_faq, $new_data);
				}
				$result = update_post_meta($post_id, 'mpwpb_faq', $mpwpb_faq);
				if ($result) {
					ob_start();
					$resultMessage = esc_html__('Data Added Successfully', 'service-booking-manager');
					$this->show_faq_data($post_id);
					$html_output = ob_get_clean();
					wp_send_json_success([
						'message' => $resultMessage,
						'html' => $html_output,
					]);
				} else {
					wp_send_json_success([
						'message' => 'Data not inserted',
						'html' => 'error',
					]);
				}
				die;
			}
			public function faq_delete_item() {
				if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'mpwpb_admin_nonce')) {
					wp_send_json_error('Invalid nonce!'); // Prevent unauthorized access
				}
				$post_id = isset($_POST['mpwpb_faq_postID']) ? sanitize_text_field(wp_unslash($_POST['mpwpb_faq_postID'])) : '';
				$mpwpb_faq = get_post_meta($post_id, 'mpwpb_faq', true);
				$mpwpb_faq = !empty($mpwpb_faq) ? $mpwpb_faq : [];
				if (!empty($mpwpb_faq)) {
					if (isset($_POST['itemId'])) {
						unset($mpwpb_faq[sanitize_text_field(wp_unslash($_POST['itemId']))]);
						$mpwpb_faq = array_values($mpwpb_faq);
					}
				}
				$result = update_post_meta($post_id, 'mpwpb_faq', $mpwpb_faq);
				if ($result) {
					ob_start();
					$resultMessage = esc_html__('Data Deleted Successfully', 'service-booking-manager');
					$this->show_faq_data($post_id);
					$html_output = ob_get_clean();
					wp_send_json_success([
						'message' => $resultMessage,
						'html' => $html_output,
					]);
				} else {
					wp_send_json_success([
						'message' => 'Data not inserted',
						'html' => '',
					]);
				}
				die;
			}
		}
		new MPWPB_Faq_Settings();
	}