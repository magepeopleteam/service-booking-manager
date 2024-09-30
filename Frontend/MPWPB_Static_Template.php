<?php
/**
 * @author Shahadat Hossain <raselsha@gmail.com>
 * @version 1.0.0
 */

if( ! defined('ABSPATH') )die;

if(! class_exists('MPWPB_Static_Template') ){
    class MPWPB_Static_Template{
        public function __construct() {
            add_action('mpwpb_service_header',[$this, 'show_service_header']);
            add_action('mpwpb_service_nav',[$this, 'show_service_nav']);
            add_action('mpwpb_service_overview',[$this, 'show_service_overview']);
            add_action('mpwpb_service_faq',[$this, 'show_service_faq']);
            add_action('mpwpb_service_details',[$this, 'show_service_details']);
            add_action('mpwpb_service_reviews',[$this, 'show_service_reviews']);
        }

        public function show_service_header() {
            ?>  
            <header style="background-image: url('<?php echo get_the_post_thumbnail_url(); ?>');">
                <div class="template-header" >
                    <div class="header-content">
                        <h2><?php the_title(); ?></h2>
                        <p class="ratings"><i class="fas fa-star"></i> 4.67 <span> out of 5</span></p>
                        <p>(8868 ratings on 3 services)</p>
                        <ul class="features">
                            <li><i class="fas fa-check-circle"></i> On Time Work Completion</li>
                            <li><i class="fas fa-check-circle"></i> Trusted and Experienced Plumbers</li>
                        </ul>
                    </div>
                </div>
            </header>
            <?php
        }

        public function show_service_nav() {
            ?>
            <nav>
                <ul>
                    <li>
                        <a href="#service-overview"><?php _e('Overview','service-booking-manager') ?></a>
                    </li>
                    <li>
                        <a href="#service-faq"><?php _e('FAQ','service-booking-manager') ?></a>
                    </li>
                    <li>
                        <a href="#service-details"><?php _e('Details','service-booking-manager') ?></a>
                    </li>
                    <li>
                        <a href="#service-reviews"><?php _e('Reviews','service-booking-manager') ?></a>
                    </li>
                </ul>
            </nav>
            <?php
        }

        public function show_service_overview() {
            $service_overview_status = get_post_meta(get_the_ID(),'mpwpb_service_overview_status',true);
            $service_overview_content = get_post_meta(get_the_ID(),'mpwpb_service_overview_content',true);
            ?>
                <section id="service-overview">
                    <h2><?php _e('Servie Overview','service-booking-manager'); ?></h2>
                    <?php echo $service_overview_status === 'on' ? wp_kses_post($service_overview_content) : '';?>

                </section>
            <?php
        }

        public function show_service_faq() {
            
            ?>
            <section id="service-faq">
                <h2><?php _e('Servie FAQ','service-booking-manager'); ?></h2>
                <?php 
                        $mpwpb_faq = get_post_meta(get_the_ID(),'mpwpb_faq',true);
                        if( ! empty($mpwpb_faq)){
                            foreach ($mpwpb_faq as $key => $value) {
                                $this->show_faq_data($value['title'],$value['content']);
                            }
                        }
                    ?>
            </section>
            <?php
        }

        public function show_faq_data($title,$content){
            ?>
            <div class="mpwpb-serivice-faq">
                <div class="faq-header">
                    <i class="fas fa-plus"></i> <?php echo esc_html($title); ?>
                </div>
                <div class="faq-content">
                    <?php echo esc_html($content); ?>
                </div>
            </div>
            <?php
        }

        public function show_service_details() {
            $service_details_status = get_post_meta(get_the_ID(),'mpwpb_service_details_status',true);
            $service_details_content = get_post_meta(get_the_ID(),'mpwpb_service_details_content',true);
            ?>
            <section id="service-details">
                <h2><?php _e('Servie Details','service-booking-manager'); ?></h2>
                <?php 
                    echo $service_details_status === 'on' ? wp_kses_post($service_details_content) : '';
                ?>
            </section>
            <?php
        }

        public function show_service_reviews() {
            ?>
            <section id="service-reviews">
                <h2><?php _e('Servie Reviews','service-booking-manager'); ?></h2>
                Lorem ipsum dolor sit amet consectetur adipisicing elit. Nemo alias distinctio fugiat corrupti. In tempore voluptatibus consequuntur! Magnam asperiores tempora accusamus, quasi doloremque cumque, ipsa dolores tenetur possimus, voluptas quo dolorem? Quo vero rerum id, ullam, sapiente harum temporibus corrupti aliquam perferendis, error quisquam ipsum hic blanditiis aperiam ea nihil non saepe sint exercitationem! Consectetur, error quidem consequuntur labore explicabo quisquam sapiente vel architecto? Consequatur tenetur eum vero error repellat neque optio, atque dignissimos sed, suscipit perferendis. Quis ipsum asperiores dolor laudantium officiis tempore tempora, doloribus quae quo ea veniam, odit nobis ullam! Beatae facere iusto corrupti! Voluptas, vel corporis?
            </section>
            <?php
        }

    }
    new MPWPB_Static_Template();
}