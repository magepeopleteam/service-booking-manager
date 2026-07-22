<?php
if (!defined('ABSPATH')) {
	exit;
}

if (!class_exists('MPWPB_Help_Guide')) {
	class MPWPB_Help_Guide {
		private $page_hook = '';

		public function __construct() {
			add_action('admin_menu', array($this, 'register_menu'), 99);
			add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
		}

		public function register_menu() {
			$capability = apply_filters('mpwpb_help_guide_capability', 'manage_options');
			$this->page_hook = add_submenu_page(
				'edit.php?post_type=mpwpb_item',
				esc_html__('WPBookingly Help Center', 'service-booking-manager'),
				esc_html__('Help & Guide', 'service-booking-manager'),
				$capability,
				'mpwpb_help_guide',
				array($this, 'render_page'),
				99
			);
		}

		public function enqueue_assets($hook) {
			if (!$this->page_hook || $hook !== $this->page_hook) {
				return;
			}

			wp_enqueue_style(
				'mpwpb-help-guide',
				MPWPB_PLUGIN_URL . '/assets/admin/mpwpb-help-guide.css',
				array('dashicons'),
				'1.0.0'
			);
			wp_enqueue_script(
				'mpwpb-help-guide',
				MPWPB_PLUGIN_URL . '/assets/admin/mpwpb-help-guide.js',
				array(),
				'1.0.0',
				true
			);
			wp_localize_script('mpwpb-help-guide', 'mpwpbHelpGuide', array(
				'copied'      => esc_html__('Topic link copied.', 'service-booking-manager'),
				'copyFailed'  => esc_html__('Copy the address from your browser.', 'service-booking-manager'),
				'proRequired' => esc_html__('This guide topic requires Service Booking Manager Pro to be active.', 'service-booking-manager'),
			));
		}

		private function admin_urls() {
			return array(
				'services'       => admin_url('edit.php?post_type=mpwpb_item&page=mpwpb_service_list'),
				'add_service'    => admin_url('post-new.php?post_type=mpwpb_item'),
				'coupons'        => admin_url('edit.php?post_type=mpwpb_item&page=mpwpb_coupon_list'),
				'staff'          => admin_url('edit.php?post_type=mpwpb_item&page=mpwpb_staffs'),
				'settings'       => admin_url('edit.php?post_type=mpwpb_item&page=mpwpb_settings_page'),
				'status'         => admin_url('edit.php?post_type=mpwpb_item&page=mpwpb_status_page'),
				'reviews'        => admin_url('edit.php?post_type=mpwpb_item&page=mpwpb-reviews'),
				'analytics'      => admin_url('edit.php?post_type=mpwpb_item&page=mpwpb_analytics_dashboard'),
				'orders'         => admin_url('edit.php?post_type=mpwpb_item&page=mpwpb_order_list'),
				'queue'          => admin_url('edit.php?post_type=mpwpb_item&page=mpwpb_service_queue'),
				'calendar'       => admin_url('edit.php?post_type=mpwpb_item&page=mp-custom-calendar'),
				'backend_order'  => admin_url('edit.php?post_type=mpwpb_item&page=mpwpb_create_order'),
				'gdpr_tools'     => admin_url('edit.php?post_type=mpwpb_item&page=mpwpb_gdpr_tools'),
				'audit_logs'     => admin_url('edit.php?post_type=mpwpb_item&page=mpwpb_audit_logs'),
				'profile'        => admin_url('profile.php'),
			);
		}

		private function sections() {
			require_once MPWPB_PLUGIN_DIR . '/Admin/MPWPB_Help_Guide_Content.php';
			$sections = MPWPB_Help_Guide_Content::get_sections();
			$sections = apply_filters('mpwpb_help_guide_sections', $sections, array(
				'pro_active' => class_exists('MPWPB_Admin_Pro'),
				'urls'       => $this->admin_urls(),
			));

			return is_array($sections) ? $sections : array();
		}

		private function has_pro_content($sections) {
			foreach ($sections as $section) {
				if (isset($section['tier']) && 'pro' === $section['tier']) {
					return true;
				}
				foreach (isset($section['topics']) && is_array($section['topics']) ? $section['topics'] : array() as $topic) {
					if (isset($topic['tier']) && 'pro' === $topic['tier']) {
						return true;
					}
				}
			}
			return false;
		}

		private function icon($name) {
			$icons = array(
				'start' => 'dashicons-lightbulb', 'map' => 'dashicons-location-alt',
				'service' => 'dashicons-admin-tools', 'calendar' => 'dashicons-calendar-alt',
				'pricing' => 'dashicons-money-alt', 'staff' => 'dashicons-groups',
				'booking' => 'dashicons-tickets-alt', 'payment' => 'dashicons-cart',
				'coupon' => 'dashicons-tag', 'review' => 'dashicons-star-filled',
				'analytics' => 'dashicons-chart-bar', 'privacy' => 'dashicons-shield',
				'style' => 'dashicons-art', 'tools' => 'dashicons-sos',
				'orders' => 'dashicons-clipboard', 'document' => 'dashicons-media-document',
				'integration' => 'dashicons-admin-links', 'security' => 'dashicons-lock',
				'license' => 'dashicons-admin-network',
			);
			return isset($icons[$name]) ? $icons[$name] : 'dashicons-book-alt';
		}

		public function render_page() {
			$capability = apply_filters('mpwpb_help_guide_capability', 'manage_options');
			if (!current_user_can($capability)) {
				wp_die(esc_html__('You do not have permission to view this guide.', 'service-booking-manager'));
			}

			$sections = $this->sections();
			$has_pro = $this->has_pro_content($sections);
			$urls = $this->admin_urls();
			?>
			<div class="wrap mpwpb-guide" data-has-pro="<?php echo $has_pro ? '1' : '0'; ?>">
				<div class="mpwpb-guide__hero">
					<div class="mpwpb-guide__hero-copy">
						<span class="mpwpb-guide__eyebrow"><?php esc_html_e('Booking management, explained clearly', 'service-booking-manager'); ?></span>
						<h1><?php esc_html_e('WPBookingly Help Center', 'service-booking-manager'); ?></h1>
						<p><?php esc_html_e('Find any feature, understand every setting, and follow practical steps to configure your booking system with confidence.', 'service-booking-manager'); ?></p>
					</div>
					<div class="mpwpb-guide__edition">
						<span class="dashicons dashicons-yes-alt" aria-hidden="true"></span>
						<span><?php echo $has_pro ? esc_html__('Free + Pro guide', 'service-booking-manager') : esc_html__('Free guide', 'service-booking-manager'); ?></span>
					</div>
					<div class="mpwpb-guide__search-wrap">
						<label for="mpwpb-guide-search"><?php esc_html_e('Search the guide', 'service-booking-manager'); ?></label>
						<span class="dashicons dashicons-search" aria-hidden="true"></span>
						<input id="mpwpb-guide-search" type="search" placeholder="<?php esc_attr_e('Try “buffer time”, “coupon”, “Stripe”…', 'service-booking-manager'); ?>" autocomplete="off">
						<kbd>/</kbd>
					</div>
				</div>

				<div id="mpwpb-guide-pro-notice" class="mpwpb-guide__notice" hidden></div>

				<div class="mpwpb-guide__quick" aria-label="<?php esc_attr_e('Quick start', 'service-booking-manager'); ?>">
					<a href="<?php echo esc_url($urls['add_service']); ?>"><strong>1</strong><span><?php esc_html_e('Create a service', 'service-booking-manager'); ?></span></a>
					<a href="#free-availability"><strong>2</strong><span><?php esc_html_e('Set availability', 'service-booking-manager'); ?></span></a>
					<a href="#free-payments"><strong>3</strong><span><?php esc_html_e('Choose payments', 'service-booking-manager'); ?></span></a>
					<a href="#free-staff"><strong>4</strong><span><?php esc_html_e('Assign staff', 'service-booking-manager'); ?></span></a>
					<a href="#free-first-test"><strong>5</strong><span><?php esc_html_e('Test a booking', 'service-booking-manager'); ?></span></a>
				</div>

				<div class="mpwpb-guide__toolbar">
					<div class="mpwpb-guide__mobile-nav">
						<label for="mpwpb-guide-section-select"><?php esc_html_e('Jump to a section', 'service-booking-manager'); ?></label>
						<select id="mpwpb-guide-section-select">
							<option value=""><?php esc_html_e('Choose a section…', 'service-booking-manager'); ?></option>
							<?php foreach ($sections as $section) : ?>
								<option value="#<?php echo esc_attr($section['id']); ?>"><?php echo esc_html($section['title']); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
					<div class="mpwpb-guide__filters" aria-label="<?php esc_attr_e('Filter guide', 'service-booking-manager'); ?>">
						<button type="button" class="is-active" data-tier="all" aria-pressed="true"><?php esc_html_e('All', 'service-booking-manager'); ?></button>
						<button type="button" data-tier="free" aria-pressed="false"><?php esc_html_e('Free', 'service-booking-manager'); ?></button>
						<?php if ($has_pro) : ?><button type="button" data-tier="pro" aria-pressed="false"><?php esc_html_e('Pro', 'service-booking-manager'); ?></button><?php endif; ?>
					</div>
					<button type="button" class="mpwpb-guide__expand" data-expanded="false"><?php esc_html_e('Expand all', 'service-booking-manager'); ?></button>
					<span id="mpwpb-guide-results" class="mpwpb-guide__results" aria-live="polite"></span>
				</div>

				<div class="mpwpb-guide__layout">
					<nav class="mpwpb-guide__nav" aria-label="<?php esc_attr_e('Guide sections', 'service-booking-manager'); ?>">
						<p><?php esc_html_e('Guide contents', 'service-booking-manager'); ?></p>
						<?php foreach ($sections as $section) : ?>
							<a href="#<?php echo esc_attr($section['id']); ?>" data-tier="<?php echo esc_attr($section['tier']); ?>">
								<span class="dashicons <?php echo esc_attr($this->icon($section['icon'])); ?>" aria-hidden="true"></span>
								<span><?php echo esc_html($section['title']); ?></span>
								<small><?php echo 'pro' === $section['tier'] ? esc_html__('PRO', 'service-booking-manager') : esc_html__('FREE', 'service-booking-manager'); ?></small>
							</a>
						<?php endforeach; ?>
					</nav>

					<main class="mpwpb-guide__content">
						<?php foreach ($sections as $section) : $this->render_section($section, $urls); endforeach; ?>
						<div class="mpwpb-guide__empty" hidden>
							<span class="dashicons dashicons-search" aria-hidden="true"></span>
							<h2><?php esc_html_e('No guide topics found', 'service-booking-manager'); ?></h2>
							<p><?php esc_html_e('Try a simpler term such as service, schedule, payment, staff, coupon, or email.', 'service-booking-manager'); ?></p>
							<button type="button"><?php esc_html_e('Clear search', 'service-booking-manager'); ?></button>
						</div>
					</main>
				</div>
				<div id="mpwpb-guide-live" class="screen-reader-text" aria-live="polite"></div>
			</div>
			<?php
		}

		private function render_section($section, $urls) {
			$topics = isset($section['topics']) && is_array($section['topics']) ? $section['topics'] : array();
			?>
			<section id="<?php echo esc_attr($section['id']); ?>" class="mpwpb-guide__section" data-tier="<?php echo esc_attr($section['tier']); ?>">
				<header class="mpwpb-guide__section-head">
					<div class="mpwpb-guide__section-icon"><span class="dashicons <?php echo esc_attr($this->icon($section['icon'])); ?>" aria-hidden="true"></span></div>
					<div><span class="mpwpb-guide__tier mpwpb-guide__tier--<?php echo esc_attr($section['tier']); ?>"><?php echo 'pro' === $section['tier'] ? esc_html__('Pro', 'service-booking-manager') : esc_html__('Free', 'service-booking-manager'); ?></span><h2><?php echo esc_html($section['title']); ?></h2><p><?php echo esc_html($section['description']); ?></p></div>
				</header>
				<div class="mpwpb-guide__topics">
					<?php foreach ($topics as $topic) : $this->render_topic($topic, $urls); endforeach; ?>
				</div>
			</section>
			<?php
		}

		private function render_topic($topic, $urls) {
			$fields = isset($topic['fields']) && is_array($topic['fields']) ? $topic['fields'] : array();
			$steps = isset($topic['steps']) && is_array($topic['steps']) ? $topic['steps'] : array();
			$tier = isset($topic['tier']) ? $topic['tier'] : 'free';
			$search = implode(' ', array($topic['title'], $topic['summary'], $topic['where'], isset($topic['keywords']) ? $topic['keywords'] : ''));
			foreach ($fields as $field) {
				$search .= ' ' . $field['label'] . ' ' . $field['description'] . ' ' . (isset($field['tip']) ? $field['tip'] : '');
			}
			?>
			<article id="<?php echo esc_attr($topic['id']); ?>" class="mpwpb-guide__topic" data-tier="<?php echo esc_attr($tier); ?>" data-search="<?php echo esc_attr(wp_strip_all_tags($search)); ?>">
				<button type="button" class="mpwpb-guide__topic-toggle" aria-expanded="false" aria-controls="<?php echo esc_attr($topic['id']); ?>-body">
					<span><span class="mpwpb-guide__tier mpwpb-guide__tier--<?php echo esc_attr($tier); ?>"><?php echo 'pro' === $tier ? esc_html__('Pro', 'service-booking-manager') : esc_html__('Free', 'service-booking-manager'); ?></span><strong><?php echo esc_html($topic['title']); ?></strong><small><?php echo esc_html($topic['summary']); ?></small></span>
					<span class="dashicons dashicons-arrow-down-alt2" aria-hidden="true"></span>
				</button>
				<div id="<?php echo esc_attr($topic['id']); ?>-body" class="mpwpb-guide__topic-body">
					<div class="mpwpb-guide__where"><span class="dashicons dashicons-location" aria-hidden="true"></span><div><strong><?php esc_html_e('Where to find it', 'service-booking-manager'); ?></strong><span><?php echo esc_html($topic['where']); ?></span></div></div>
					<?php if (!empty($topic['note'])) : ?><div class="mpwpb-guide__callout"><span class="dashicons dashicons-info-outline" aria-hidden="true"></span><p><?php echo esc_html($topic['note']); ?></p></div><?php endif; ?>
					<?php if ($steps) : ?><ol class="mpwpb-guide__steps"><?php foreach ($steps as $step) : ?><li><?php echo esc_html($step); ?></li><?php endforeach; ?></ol><?php endif; ?>
					<?php if ($fields) : ?>
						<div class="mpwpb-guide__fields">
							<h3><?php esc_html_e('Settings explained', 'service-booking-manager'); ?></h3>
							<dl><?php foreach ($fields as $field) : ?><div><dt><?php echo esc_html($field['label']); ?></dt><dd><?php echo esc_html($field['description']); ?><?php if (!empty($field['tip'])) : ?><small><?php echo esc_html($field['tip']); ?></small><?php endif; ?></dd></div><?php endforeach; ?></dl>
						</div>
					<?php endif; ?>
					<div class="mpwpb-guide__actions">
						<?php if (!empty($topic['url_key']) && isset($urls[$topic['url_key']])) : ?><a class="mpwpb-guide__open" href="<?php echo esc_url($urls[$topic['url_key']]); ?>"><?php esc_html_e('Open this area', 'service-booking-manager'); ?><span class="dashicons dashicons-arrow-right-alt" aria-hidden="true"></span></a><?php endif; ?>
						<button type="button" class="mpwpb-guide__copy" data-topic="<?php echo esc_attr($topic['id']); ?>"><span class="dashicons dashicons-admin-links" aria-hidden="true"></span><?php esc_html_e('Copy topic link', 'service-booking-manager'); ?></button>
					</div>
				</div>
			</article>
			<?php
		}
	}
	new MPWPB_Help_Guide();
}
