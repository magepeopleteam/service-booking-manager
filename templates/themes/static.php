<?php
	/**
	 * Tempalte Name: Static Template
	 *
	 * @author Shahadat Hossain <raselsha@gmail.com>
	 * @copyright 2024 mage-people.com
	 */
	if (!defined('ABSPATH')) {
		exit;
	}
	$post_id = $post_id ?? get_the_id();
    $shortcode = 'no';
    $mpwpb_hero_badge = MPWPB_Static_Template::hero_badge_text($post_id);
    $mpwpb_hero_subtitle = MPWPB_Static_Template::hero_subtitle($post_id);
    // Per-service overlay overrides (Pro). Empty by default, in which case the
    // header keeps using the global values already declared on :root.
    $mpwpb_hero_overlay_style = apply_filters('mpwpb_hero_overlay_style', '', $post_id);
?>
    <div class="mpwpb_style mpwpb-static-template mpwpb_registration">
        <header style="background-image: url('<?php echo esc_url(get_the_post_thumbnail_url()); ?>');<?php echo esc_attr($mpwpb_hero_overlay_style); ?>">
            <div class="template-header">
                <div class="header-content">
					<?php if ($mpwpb_hero_badge !== ''): ?>
                    <span class="mpwpb-hero-eyebrow"><i class="fas fa-store"></i> <?php echo esc_html($mpwpb_hero_badge); ?></span>
					<?php endif; ?>
                    <h2><?php the_title(); ?></h2>
					<?php if ($mpwpb_hero_subtitle !== ''): ?>
                    <p class="mpwpb-hero-subtext"><?php echo esc_html($mpwpb_hero_subtitle); ?></p>
					<?php endif; ?>
                    <!-- dispaly service static page reatings using this hook -->
					<?php do_action('mpwpb_service_show_ratings'); ?>
                    <!-- dispaly service static page feature heighlight using this hook -->
					<?php do_action('mpwpb_service_feature_heighlight'); ?>
                </div>
            </div>
        </header>
        <main>
            <div class="main">
                <!-- dispaly service static page nav using this hook -->
				<?php do_action('mpwpb_service_nav'); ?>
                <!-- dispaly service overview section using this hook -->
				<?php do_action('mpwpb_service_overview'); ?>
                <!-- dispaly service FAQ section using this hook -->
				<?php do_action('mpwpb_service_faq'); ?>
                <!-- dispaly service Details section using this hook -->
				<?php do_action('mpwpb_service_details'); ?>
                <!-- dispaly service Reviews section using this hook -->
				<?php do_action('mpwpb_service_reviews'); ?>

				<?php do_action('mpwpb_added_staff_details'); ?>
            </div>
            <div class="sidebar" id="mpwpb_static_sidbar">
	            <?php include(MPWPB_Function::template_path('registration/static_registration.php')); ?>
            </div>
        </main>
        <!-- dispaly service past work gallery section using this hook -->
		<?php do_action('mpwpb_service_gallery'); ?>
    </div>
<?php
