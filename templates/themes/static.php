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
?>
    <div class="mpwpb_style mpwpb-static-template mpwpb_registration">
        <header style="background-image: url('<?php echo esc_url(get_the_post_thumbnail_url()); ?>');">
            <div class="template-header">
                <div class="header-content">
                    <h2><?php the_title(); ?></h2>
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
            <div class="sidebar">
	            <?php include(MPWPB_Function::template_path('registration/static_registration.php')); ?>
            </div>
        </main>
    </div>
<?php
