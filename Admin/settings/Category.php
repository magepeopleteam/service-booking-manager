<?php
/**
 * Class MPWPB_Category
 * 
 * A class that represents a category and sub category for service booking.
 * This class provides functionality to manage category and sub category data.
 * 
 * @author Shahadat Hossain <raselsha@gmail.com>
 * @version 1.0.0
 */

if(!defined('ABSPATH'))die;
if(!class_exists('MPWPB_Category')){
    class MPWPB_Service_Category{
        public function __construct() {
            add_action('add_mpwpb_settings_tab_content', [$this, 'category_settings_section'], 10, 1);
           // add_action('add_mpwpb_settings_tab_content', [$this, 'set_category_service']);

            // save category service
            add_action('wp_ajax_mpwpb_save_category_service', [ $this,'save_category_service']);
            add_action('wp_ajax_nopriv_mpwpb_save_category_service', [ $this,'save_category_service']);
            
            // mpwpb update category service
            add_action('wp_ajax_mpwpb_update_category_service', [$this, 'update_category_service']);
            add_action('wp_ajax_nopriv_mpwpb_update_category_service', [$this, 'update_category_service']);
            
            // mpwpb delete category service
            add_action('wp_ajax_mpwpb_category_service_delete_item', [$this, 'delete_category_service']);
            add_action('wp_ajax_nopriv_mpwpb_category_service_delete_item', [$this, 'delete_category_service']);
            
        }

        public function category_settings_section($post_id){
            ?>
            <div class="tabsItem mpwpb_category_settings" data-tabs="#mpwpb_category_settings">
                <header>
                        <h2><?php esc_html_e('Category Settings', 'service-booking-manager'); ?></h2>
                        <span><?php esc_html_e('Category Settings', 'service-booking-manager'); ?></span>
                </header>
                <section class="section">
                    <h2><?php esc_html_e('Category Settings', 'service-booking-manager'); ?></h2>
                    <span><?php esc_html_e('Category Settings', 'service-booking-manager'); ?></span>
                </section>
                <section>
                    <table class="table category-service-table mB">
                        <thead>
                            <tr>
                                <th style="width:66px">Image</th>
                                <th>Name</th>
                                <th>Sub Category</th>
                                <th style="width:92px">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $this->show_category_items($post_id); ?>
                        </tbody>
                    </table>
                    <button class="button mpwpb-category-service-new" type="button"><?php _e('Add Category','service-booking-manager'); ?></button>
                </section>
                <!-- sidebar collapse open -->
                <div class="mpwpb-sidebar-container">
                    <div class="mpwpb-sidebar-content">
                        <h3><?php _e('Add Category Service','service-booking-manager'); ?></h3>
                        <span class="mpwpb-sidebar-close"><i class="fas fa-times"></i></span>
                        <div class="mpwpb-category-service-form">
                            <div id="mpwpb-category-service-msg"></div>
                            <input type="hidden" name="mpwpb_category_post_id" value="<?php echo $post_id; ?>"> 
                            <input type="hidden" name="mpwpb_category_item_id" value="">
                            <label>
                                <?php _e('Category Name','service-booking-manager'); ?>
                                <input type="text"   name="mpwpb_category_service_name"> 
                            </label>
                            <label>
                                <?php _e('Category Image/Icon','service-booking-manager'); ?> 
                            </label>
                            <div class="mp_add_icon_image_area">
                                <input type="hidden" name="mpwpb_category_image_icon" value="">
                                <div class="mp_icon_item dNone">
                                    <span class="" data-add-icon=""></span>
                                    <span class="fas fa-times mp_remove_icon mp_icon_remove"></span>
                                </div>
                                <div class="mp_image_item dNone">
                                    <img class="" src="" alt="">
                                    <span class="fas fa-times mp_remove_icon mp_image_remove"></span>
                                </div>
                                <div class="mp_add_icon_image_button_area ">
                                    <button class="mp_image_add" type="button">
                                        <span class="fas fa-images"></span>Image</button>
                                    <button class="mp_icon_add" type="button" data-target-popup="#mp_add_icon_popup">
                                        <span class="fas fa-plus"></span>Icon</button>
                                </div>
                            </div>

                            <div class="mpwpb_category_service_save_button">
                                <p><button id="mpwpb_category_service_save" class="button button-primary button-large"><?php _e('Save','service-booking-manager'); ?></button> <button id="mpwpb_category_service_save_close" class="button button-primary button-large">save close</button><p>
                            </div>
                            <div class="mpwpb_category_service_update_button" style="display: none;">
                                <p><button id="mpwpb_category_service_update" class="button button-primary button-large"><?php _e('Update and Close','service-booking-manager'); ?></button><p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?php
        }

        public function set_category_service($post_id){
            $service_category = get_post_meta($post_id,'mpwpb_service_category',true);
            if(empty($service_category)){
                $category_info = get_post_meta($post_id,'mpwpb_category_infos',true);
                $categories = [];
                $sub_categories = [];
                $service_items = [];
                foreach ($category_info as $cat_index => $category) {
                    if (isset($category['category'])) {
                        $categories[] = [
                            'name' => $category['category'],
                            'icon' => $category['icon'],
                            'image' => $category['image']
                        ];
                    }
                    if (isset($category['sub_category'])){
                        foreach ($category['sub_category'] as $sub_cat_index => $sub_category){
                            $sub_categories[]=[
                                'name' => $sub_category['name'],
                                'icon' => $sub_category['icon'],
                                'image' => $sub_category['image'],
                                'cat_id'=> $cat_index,
                            ];
                            if(isset($sub_category['service'])){
                                foreach ($sub_category['service'] as $service_index => $service){
                                    $service_items[]=[
                                        'name' => $service['name'],
                                        'icon' => $service['icon'],
                                        'image' => $service['image'],
                                        'details' => $service['details'],
                                        'price' => $service['price'],
                                        'duration' => $service['duration'],
                                        'cat_id'=> $sub_cat_index,
                                    ];
                                }
                            }
                        }
                    }
                }
                update_post_meta($post_id,'mpwpb_category_service',$categories);
                update_post_meta($post_id,'mpwpb_sub_category_service',$sub_categories);
                update_post_meta($post_id,'mpwpb_service',$service_items);
            }
        }

        public function show_category_items($post_id){
            $categories = $this->get_categories($post_id);
            $sub_categories = $this->get_sub_categories($post_id);
            foreach ($categories as $key => $value){
            ?>
            <tr data-id="<?php echo $key; ?>">
                <td>
                    <?php  if(!empty($value['image'])): ?>
                        <img src="<?php echo esc_attr(wp_get_attachment_url($value['image'])); ?>" alt="" data-imageId="<?php echo $value['image']; ?>">
                    <?php  endif; ?>
                    <?php  if(!empty($value['icon'])): ?>
                        <i class="<?php echo $value['icon'] ? $value['icon'] : ''; ?>"></i>
                    <?php  endif; ?>
                </td>
                <td><?php echo $value['name']; ?></td>
                <td>
                    <?php 
                        foreach($sub_categories as $value){
                            if($value['cat_id']==$key){
                                $names[] =$value['name'];
                            }
                        }
                        echo implode(', ',$names);
                    ?>
                </td>
                <td>
                    <span class="mpwpb-category-service-edit"><i class="fas fa-edit"></i></span>
                    <span class="mpwpb-category-service-delete"><i class="fas fa-trash"></i></span>
                </td>
            </tr>
        <?php
            }
        }

        public function get_categories($post_id){
            $service_category = get_post_meta($post_id,'mpwpb_category_service',true);
            return $service_category;
        }

        public function get_sub_categories($post_id){
            $sub_category = get_post_meta($post_id,'mpwpb_sub_category_service',true);
            return $sub_category;
        }

        public function save_category_service() {
            $post_id = $_POST['category_postID'];
            $categories = $this->get_categories($post_id);
            $iconClass = '';
            $imageID = '';
            if(isset($_POST['category_image_icon'])){
                if(is_numeric($_POST['category_image_icon'])){
                    $imageID = sanitize_text_field($_POST['category_image_icon']);
                    $iconClass ='';
                }
                else{
                    $iconClass = sanitize_text_field($_POST['category_image_icon']);
                    $imageID = '';
                }
            }

            $new_data = [ 
                'name'=> sanitize_text_field($_POST['category_name']), 
                'icon'=> $iconClass,
                'image'=> $imageID,
            ];
            array_push($categories,$new_data);
            update_post_meta($post_id, 'mpwpb_category_service', $categories);
            ob_start();
            $resultMessage = __('Data Updated Successfully', 'service-booking-manager');
            $this->show_category_items($post_id);
            $html_output = ob_get_clean();
            wp_send_json_success([
                'message' => $resultMessage,
                'html' => $html_output,
            ]);
            die;
        }

        public function update_category_service() {
            $post_id = $_POST['category_postID'];
            $categories = $this->get_categories($post_id);
            $iconClass = '';
            $imageID = '';
            if(isset($_POST['category_image_icon'])){
                if(is_numeric($_POST['category_image_icon'])){
                    $imageID = sanitize_text_field($_POST['category_image_icon']);
                    $iconClass ='';
                }
                else{
                    $iconClass = sanitize_text_field($_POST['category_image_icon']);
                    $imageID = '';
                }
            }

            $new_data = [ 
                'name'=> sanitize_text_field($_POST['category_name']), 
                'icon'=> $iconClass,
                'image'=> $imageID,
            ];

            if( ! empty($categories)){
                if(isset($_POST['category_itemId'])){
                    $categories[$_POST['category_itemId']]=$new_data;
                }
            }
            update_post_meta($post_id, 'mpwpb_category_service', $categories);
            ob_start();
            $resultMessage = __('Data Updated Successfully', 'mptbm_plugin_pro');
            $this->show_category_items($post_id);
            $html_output = ob_get_clean();
            wp_send_json_success([
                'message' => $resultMessage,
                'html' => $html_output,
            ]);
            die;
        }

        public function delete_category_service(){
            $post_id = $_POST['category_postID'];
            $categories = $this->get_categories($post_id);

            if( ! empty($categories)){
                if(isset($_POST['itemId'])){
                    unset($categories[$_POST['itemId']]);
                    $categories = array_values($categories);
                }
            }
            $result = update_post_meta($post_id, 'mpwpb_category_service', $categories);
            if($result){
                ob_start();
                $resultMessage = __('Data Deleted Successfully', 'service-booking-manager');
                $this->show_category_items($post_id);
                $html_output = ob_get_clean();
                wp_send_json_success([
                    'message' => $resultMessage,
                    'html' => $html_output,
                ]);
            }
            else{
                wp_send_json_success([
                    'message' => 'Data not deleted',
                    'html' => '',
                ]);
            }
            die;
        }
    }

    $MPWPB_Category = new MPWPB_Service_Category();
}
