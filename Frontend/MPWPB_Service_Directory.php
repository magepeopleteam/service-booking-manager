<?php
	/*
	 * [mpwpb-service-list] -- a browsable list of every published service,
	 * as cards (grid) or rows (list), with search, sort and a Grid/List
	 * switch the visitor's choice is remembered for.
	 *
	 * Attributes:
	 *   view     grid|list   starting view (the visitor's last choice wins)   default grid
	 *   columns  2|3|4       grid columns on wide screens                     default 3
	 *   limit    number      how many services (-1 = all)                     default -1
	 *   search   yes|no      show the search box and sort menu                default yes
	 *   title    text        heading above the list ("" hides it)
	 */
	if (!defined('ABSPATH')) {
		die;
	}
	if (!class_exists('MPWPB_Service_Directory')) {
		class MPWPB_Service_Directory {
			public function __construct() {
				add_shortcode('mpwpb-service-list', array($this, 'render'));
				add_action('wp_enqueue_scripts', array($this, 'register_assets'), 95);
			}

			public function register_assets() {
				$css = '/assets/frontend/mpwpb-service-list.css';
				$js = '/assets/frontend/mpwpb-service-list.js';
				wp_register_style('mpwpb-service-list', MPWPB_PLUGIN_URL . $css, array(), file_exists(MPWPB_PLUGIN_DIR . $css) ? (string) filemtime(MPWPB_PLUGIN_DIR . $css) : MPWPB_VERSION);
				wp_register_script('mpwpb-service-list', MPWPB_PLUGIN_URL . $js, array(), file_exists(MPWPB_PLUGIN_DIR . $js) ? (string) filemtime(MPWPB_PLUGIN_DIR . $js) : MPWPB_VERSION, true);
				// Enqueue in the <head> when the page is known to use the
				// shortcode, so the cards never render unstyled first.
				if (MPWPB_Global_Function::current_page_has_shortcode(array('mpwpb-service-list'))) {
					wp_enqueue_style('mpwpb-service-list');
					wp_enqueue_script('mpwpb-service-list');
				}
			}

			/** Card data for one service post. */
			private function service_data($post_id) {
				$services = MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_service', array());
				$services = is_array($services) ? $services : array();
				$prices = array();
				foreach ($services as $svc) {
					if (isset($svc['price']) && $svc['price'] !== '' && is_numeric($svc['price'])) {
						$prices[] = (float) $svc['price'];
					}
				}
				$overview = (string) MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_service_overview_content', '');
				$summary = wp_trim_words(wp_strip_all_tags($overview !== '' ? $overview : get_post_field('post_content', $post_id)), 26, '…');
				$rating = (float) MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_service_review_ratings', 0);
				return array(
					'id' => $post_id,
					'title' => get_the_title($post_id),
					'link' => get_permalink($post_id),
					'tagline' => (string) MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_shortcode_sub_title', ''),
					'summary' => $summary,
					'image' => get_the_post_thumbnail_url($post_id, 'large'),
					'rating' => $rating > 0 ? min(5, $rating) : 0,
					'rating_text' => (string) MPWPB_Global_Function::get_post_info($post_id, 'mpwpb_service_rating_text', ''),
					'count' => count($services),
					'min_price' => $prices ? min($prices) : null,
				);
			}

			public function render($atts) {
				$atts = shortcode_atts(array(
					'view' => 'grid',
					'columns' => 3,
					'limit' => -1,
					'search' => 'yes',
					'title' => __('Our services', 'service-booking-manager'),
				), $atts, 'mpwpb-service-list');
				$view = $atts['view'] === 'list' ? 'list' : 'grid';
				$columns = max(2, min(4, (int) $atts['columns']));
				$posts = get_posts(array(
					'post_type' => MPWPB_Function::get_cpt(),
					'post_status' => 'publish',
					'numberposts' => (int) $atts['limit'],
					'orderby' => array('menu_order' => 'ASC', 'date' => 'DESC'),
					'fields' => 'ids',
				));
				wp_enqueue_style('mpwpb-service-list');
				wp_enqueue_script('mpwpb-service-list');
				$theme = MPWPB_Global_Function::get_style_settings('theme_color', '#00685f');
				$items = array_map(array($this, 'service_data'), $posts);
				$show_search = $atts['search'] !== 'no' && count($items) > 1;

				ob_start();
				?>
				<section class="mpwpb-sl" data-mpwpb-sl data-view="<?php echo esc_attr($view); ?>" data-one="<?php /* translators: %d: 1 */ esc_attr_e('%d service', 'service-booking-manager'); ?>" data-many="<?php /* translators: %d: number of services */ esc_attr_e('%d services', 'service-booking-manager'); ?>" style="--sl-theme:<?php echo esc_attr($theme); ?>;--sl-cols:<?php echo esc_attr($columns); ?>">
					<div class="mpwpb-sl__bar">
						<div class="mpwpb-sl__heading">
							<?php if ($atts['title'] !== '') : ?>
								<h2 class="mpwpb-sl__title"><?php echo esc_html($atts['title']); ?></h2>
							<?php endif; ?>
							<p class="mpwpb-sl__count" data-mpwpb-sl-count aria-live="polite">
								<?php echo esc_html(sprintf(_n('%d service', '%d services', count($items), 'service-booking-manager'), count($items))); ?>
							</p>
						</div>
						<div class="mpwpb-sl__tools">
							<?php if ($show_search) : ?>
								<label class="mpwpb-sl__search">
									<span class="screen-reader-text"><?php esc_html_e('Search services', 'service-booking-manager'); ?></span>
									<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/></svg>
									<input type="search" data-mpwpb-sl-search placeholder="<?php esc_attr_e('Search services', 'service-booking-manager'); ?>">
								</label>
								<label class="mpwpb-sl__sort">
									<span class="screen-reader-text"><?php esc_html_e('Sort by', 'service-booking-manager'); ?></span>
									<select data-mpwpb-sl-sort>
										<option value="default"><?php esc_html_e('Featured', 'service-booking-manager'); ?></option>
										<option value="price-asc"><?php esc_html_e('Price: low to high', 'service-booking-manager'); ?></option>
										<option value="price-desc"><?php esc_html_e('Price: high to low', 'service-booking-manager'); ?></option>
										<option value="rating"><?php esc_html_e('Top rated', 'service-booking-manager'); ?></option>
										<option value="name"><?php esc_html_e('Name A–Z', 'service-booking-manager'); ?></option>
									</select>
								</label>
							<?php endif; ?>
							<div class="mpwpb-sl__views" role="group" aria-label="<?php esc_attr_e('Layout', 'service-booking-manager'); ?>">
								<button type="button" data-mpwpb-sl-view="grid" aria-pressed="<?php echo $view === 'grid' ? 'true' : 'false'; ?>" aria-label="<?php esc_attr_e('Grid view', 'service-booking-manager'); ?>" title="<?php esc_attr_e('Grid view', 'service-booking-manager'); ?>">
									<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3.5" y="3.5" width="7" height="7" rx="1.5"/><rect x="13.5" y="3.5" width="7" height="7" rx="1.5"/><rect x="3.5" y="13.5" width="7" height="7" rx="1.5"/><rect x="13.5" y="13.5" width="7" height="7" rx="1.5"/></svg>
								</button>
								<button type="button" data-mpwpb-sl-view="list" aria-pressed="<?php echo $view === 'list' ? 'true' : 'false'; ?>" aria-label="<?php esc_attr_e('List view', 'service-booking-manager'); ?>" title="<?php esc_attr_e('List view', 'service-booking-manager'); ?>">
									<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M9 6h12M9 12h12M9 18h12"/><circle cx="4.5" cy="6" r="1.2"/><circle cx="4.5" cy="12" r="1.2"/><circle cx="4.5" cy="18" r="1.2"/></svg>
								</button>
							</div>
						</div>
					</div>

					<?php if (!$items) : ?>
						<p class="mpwpb-sl__empty"><?php esc_html_e('No services are available yet.', 'service-booking-manager'); ?></p>
					<?php else : ?>
						<ul class="mpwpb-sl__items" data-mpwpb-sl-items>
							<?php foreach ($items as $i => $it) : ?>
								<li class="mpwpb-sl__item" data-order="<?php echo esc_attr($i); ?>" data-price="<?php echo esc_attr($it['min_price'] === null ? '' : $it['min_price']); ?>" data-rating="<?php echo esc_attr($it['rating']); ?>" data-name="<?php echo esc_attr(strtolower($it['title'])); ?>" data-search="<?php echo esc_attr(strtolower($it['title'] . ' ' . $it['tagline'] . ' ' . $it['summary'])); ?>">
									<article class="mpwpb-sl__card">
										<a class="mpwpb-sl__media" href="<?php echo esc_url($it['link']); ?>" tabindex="-1" aria-hidden="true">
											<?php if ($it['image']) : ?>
												<img src="<?php echo esc_url($it['image']); ?>" alt="" loading="lazy">
											<?php else : ?>
												<span class="mpwpb-sl__media-ph"><?php echo esc_html(function_exists('mb_substr') ? mb_substr($it['title'], 0, 1) : substr($it['title'], 0, 1)); ?></span>
											<?php endif; ?>
											<?php if ($it['rating']) : ?>
												<span class="mpwpb-sl__rating-badge"><svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2.5l2.9 6 6.6.9-4.8 4.6 1.2 6.5L12 17.4l-5.9 3.1 1.2-6.5L2.5 9.4l6.6-.9z"/></svg><?php echo esc_html(number_format_i18n($it['rating'], 1)); ?></span>
											<?php endif; ?>
										</a>
										<div class="mpwpb-sl__body">
											<h3 class="mpwpb-sl__name"><a href="<?php echo esc_url($it['link']); ?>"><?php echo esc_html($it['title']); ?></a></h3>
											<?php if ($it['tagline'] !== '') : ?>
												<p class="mpwpb-sl__tagline"><?php echo esc_html($it['tagline']); ?></p>
											<?php endif; ?>
											<?php if ($it['summary'] !== '') : ?>
												<p class="mpwpb-sl__summary"><?php echo esc_html($it['summary']); ?></p>
											<?php endif; ?>
											<ul class="mpwpb-sl__meta">
												<?php if ($it['count']) : ?>
													<li><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><rect x="3.5" y="3.5" width="7" height="7" rx="1.5"/><rect x="13.5" y="3.5" width="7" height="7" rx="1.5"/><rect x="3.5" y="13.5" width="7" height="7" rx="1.5"/><rect x="13.5" y="13.5" width="7" height="7" rx="1.5"/></svg><?php echo esc_html(sprintf(_n('%d option', '%d options', $it['count'], 'service-booking-manager'), $it['count'])); ?></li>
												<?php endif; ?>
												<?php if ($it['rating'] && $it['rating_text'] !== '') : ?>
													<li><?php echo esc_html($it['rating_text']); ?></li>
												<?php endif; ?>
											</ul>
										</div>
										<div class="mpwpb-sl__foot">
											<?php if ($it['min_price'] !== null) : ?>
												<p class="mpwpb-sl__price"><span><?php esc_html_e('From', 'service-booking-manager'); ?></span> <strong><?php echo wp_kses_post(MPWPB_Global_Function::wc_price($it['id'], $it['min_price'])); ?></strong></p>
											<?php endif; ?>
											<a class="mpwpb-sl__cta" href="<?php echo esc_url($it['link']); ?>"><?php esc_html_e('Book now', 'service-booking-manager'); ?><span class="screen-reader-text"> <?php echo esc_html($it['title']); ?></span>
												<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
											</a>
										</div>
									</article>
								</li>
							<?php endforeach; ?>
						</ul>
						<p class="mpwpb-sl__empty" data-mpwpb-sl-none hidden><?php esc_html_e('No services match your search.', 'service-booking-manager'); ?></p>
					<?php endif; ?>
				</section>
				<?php
				return ob_get_clean();
			}
		}
		new MPWPB_Service_Directory();
	}
