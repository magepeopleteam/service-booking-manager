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
            //add_action('add_mpwpb_settings_tab_content', [$this, 'category_settings_section']);

            // save category service
            add_action('wp_ajax_mpwpb_save_category_service', [ $this,'save_category_service']);
            add_action('wp_ajax_nopriv_mpwpb_save_category_service', [ $this,'save_category_service']);

            // mpwpb update category service
            add_action('wp_ajax_mpwpb_update_category_service', [$this, 'update_category_service']);
            add_action('wp_ajax_nopriv_mpwpb_update_category_service', [$this, 'update_category_service']);
            
            // mpwpb update sub category service
            add_action('wp_ajax_mpwpb_update_sub_category_service', [$this, 'update_sub_category_service']);
            add_action('wp_ajax_nopriv_mpwpb_update_sub_category_service', [$this, 'update_sub_category_service']);
            
            // mpwpb delete category service
            add_action('wp_ajax_mpwpb_category_service_delete_item', [$this, 'delete_category_service']);
            add_action('wp_ajax_nopriv_mpwpb_category_service_delete_item', [$this, 'delete_category_service']);
            
            // Load sub category by ajax
            add_action('wp_ajax_mpwpb_load_parent_category', [ $this,'load_parent_category']);
            add_action('wp_ajax_nopriv_mpwpb_load_parent_category', [ $this,'load_parent_category']);
            
            add_action('mpwpb_show_category',[$this,'category_settings_section']);
        }

        public function category_settings_section($post_id){
            $use_sub_category = MP_Global_Function::get_post_info($post_id, 'mpwpb_use_sub_category', 'off');
            $active_class = $use_sub_category == 'on' ? 'mActive' : '';
            $sub_category_checked = $use_sub_category == 'on' ? 'checked' : '';
            $categories = $this->get_categories($post_id);
            $sub_categories = $this->get_sub_categories($post_id);
            ?>
            <div class="mpwpb-category-lists">
                <?php foreach ($categories as $parent_key => $category): ?>
                <div class="mpwpb-category-items" data-id="<?php echo $parent_key; ?>">
                    <div class="image-block">
                        <?php  if(!empty($category['image'])): ?>
                                <img src="<?php echo esc_attr(wp_get_attachment_url($category['image'])); ?>" alt="" data-imageId="<?php echo $category['image']; ?>">
                            <?php  endif; ?>
                            <?php  if(!empty($category['icon'])): ?>
                                <i class="<?php echo $category['icon'] ? $category['icon'] : ''; ?>"></i>
                            <?php  endif; ?>
                        <div class="title"><?php echo $category['name']; ?></div>
                    </div>
                    
                    <div class="action">
                        <span class="mpwpb-category-service-edit"><i class="fas fa-edit"></i></span>
                        <span class="mpwpb-category-service-delete"><i class="fas fa-trash"></i></span>
                    </div>
                </div>
                <div class="mpwpb-sub-category-lists">
                    <?php foreach($sub_categories as $child_key => $sub_category): ?>
                        <?php if($sub_category['cat_id']==$parent_key): ?>
                            <div class="mpwpb-sub-category-items" data-parent-id="<?php echo $parent_key; ?>" data-id="<?php echo $child_key; ?>">
                                <div class="image-block">
                                    <?php if(!empty($sub_category['image'])): ?>
                                        <img src="<?php echo esc_attr(wp_get_attachment_url($sub_category['image'])); ?>" alt="" data-imageId="<?php echo $sub_category['image']; ?>">
                                        <?php  endif; ?>
                                        <?php  if(!empty($sub_category['icon'])): ?>
                                            <i class="<?php echo $sub_category['icon'] ? $sub_category['icon'] : ''; ?>"></i>
                                        <?php  endif; ?>
                                    <div class="title"><?php echo $sub_category['name']; ?></div>
                                </div>
                                <div class="action">
                                    <span class="mpwpb-sub-category-service-edit"><i class="fas fa-edit"></i></span>
                                    <span class="mpwpb-sub-category-service-delete"><i class="fas fa-trash"></i></span>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <button class="button mpwpb-category-service-new" type="button"><?php _e('Add Category','service-booking-manager'); ?></button>
            <!-- sidebar collapse open -->
            <div class="mpwpb-sidebar-container">
                <div class="mpwpb-sidebar-content">
                    
                    
                    <span class="mpwpb-sidebar-close"><i class="fas fa-times"></i></span>
                    <div class="title">
                        <h3><?php _e('Add Category Service','service-booking-manager'); ?></h3>
                        <div id="mpwpb-category-service-msg"></div>
                    </div>
                    <div class="content">
                        <input type="hidden" name="mpwpb_category_post_id" value="<?php echo $post_id; ?>"> 
                        <input type="hidden" name="mpwpb_category_item_id" value="">
                        <input type="hidden" name="mpwpb_parent_item_id" value="">
                        <label>
                            <?php _e('Category Name','service-booking-manager'); ?>
                            <input type="text"   name="mpwpb_category_service_name"> 
                        </label>
                        <div class="mpwpb-sub-category-enable" style="display: none;">
                            <label><?php _e('Use As Sub Category','service-booking-manager'); ?></label>
                            <?php MP_Custom_Layout::switch_button('mpwpb_use_sub_category', $sub_category_checked); ?>
                            <div class="<?php echo $active_class; ?>" data-collapse="#mpwpb_use_sub_category">
                                <label><?php _e('Select Parent Category','service-booking-manager'); ?> </label>
                                <div class="mpwpb-parent-category">
                                    <?php $this->show_parent_category_lists($post_id); ?>
                                </div>
                            </div>
                        </div>
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
        <?php

        }

        public function category_view($post_id){
        ?>
            
            
        <?php
        }

        public function load_parent_category(){
            $post_id = $_POST['postID'];
            ob_start();
            $resultMessage = __('Data Updated Successfully', 'service-booking-manager');
            $this->show_parent_category_lists($post_id);
            $html_output = ob_get_clean();
            wp_send_json_success([
                'message' => $resultMessage,
                'html' => $html_output,
            ]);
            die;
        }

        public function show_parent_category_lists($post_id){
            $categories = $this->get_categories($post_id);
            ?>
            <select name="mpwpb_parent_cat">
                <?php foreach($categories as $key => $category): 
                    ?>
                    <option value="<?php echo $key; ?>"><?php echo $category['name']; ?></option>
                <?php endforeach; ?>
            </select>
            <?php
        }


        public function show_sub_category_lists($post_id){
            $sub_categories = $this->get_sub_categories($post_id);
            ?>
            <select name="mpwpb_sub_category">
                <?php foreach($sub_categories as $key => $category): 
                    ?>
                    <option value="<?php echo $key; ?>"><?php echo $category['name']; ?></option>
                <?php endforeach; ?>
            </select>
            <?php
        }
        

        public function set_category_service($post_id){
            $service_category = get_post_meta($post_id, 'mpwpb_category_service', true);
            if(empty($service_category) or $service_category==''){
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
                                        'show_cat_status'=> 'on',
                                        'parent_cat'=> $cat_index,
                                        'sub_cat'=> $sub_cat_index,
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
            
        }

        public function get_categories($post_id){
            $this->set_category_service($post_id);
            $service_category = get_post_meta($post_id,'mpwpb_category_service',true);
            $service_category = !empty($service_category) ? $service_category : [];
            return $service_category;
        }

        public function get_sub_categories($post_id){
            $sub_category = get_post_meta($post_id,'mpwpb_sub_category_service',true);
            return $sub_category;
        }

        public function save_category_service() {
            $post_id = $_POST['category_postID'];
            $categories = $this->get_categories($post_id);
            $sub_categories = $this->get_sub_categories($post_id);
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

            if($_POST['use_sub_category']=='on'){
                $new_sub_data = [ 
                    'name'=> sanitize_text_field($_POST['category_name']), 
                    'icon'=> $iconClass,
                    'image'=> $imageID,
                    'cat_id'=> $_POST['parent_category'],
                ];
                array_push($sub_categories,$new_sub_data);
                update_post_meta($post_id, 'mpwpb_sub_category_service', $sub_categories);
            }
            else{
                $new_data = [ 
                    'name'=> sanitize_text_field($_POST['category_name']), 
                    'icon'=> $iconClass,
                    'image'=> $imageID,
                ];
                array_push($categories,$new_data);
                update_post_meta($post_id, 'mpwpb_category_service', $categories);
            }

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

        public function update_sub_category_service() {
            $post_id = $_POST['category_postID'];
            $sub_categories = $this->get_sub_categories($post_id);
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
                'cat_id'=> $_POST['category_parentId'],
            ];

            if( ! empty($sub_categories)){
                if(isset($_POST['category_itemId'])){
                    $sub_categories[$_POST['category_itemId']]=$new_data;
                }
            }
            update_post_meta($post_id, 'mpwpb_sub_category_service', $sub_categories);
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
