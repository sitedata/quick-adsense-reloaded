<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

class QUADS_Ad_Setup {
                
        private static $instance;      
        private $migration_service = null;
        private $api_service = null;

        private function __construct() {
            
            if($this->migration_service == null){
                require_once QUADS_PLUGIN_DIR . '/admin/includes/migration-service.php';
                $this->migration_service = new QUADS_Ad_Migration();
            }   
            if($this->api_service == null){
                require_once QUADS_PLUGIN_DIR . '/admin/includes/rest-api-service.php';
                $this->api_service = new QUADS_Ad_Setup_Api_Service();
            }                    
                                 
        }
        public function quadsAdSetupHooks(){
            
            add_action( 'init', array($this, 'quadsAdminInit'));  
            add_action( 'upgrader_process_complete', array($this, 'quadsUpgradeToNewDesign') ,10, 2);            
            add_action( 'wp_ajax_quads_sync_ads_in_new_design', array($this, 'quadsSyncAdsInNewDesign') );
             add_action( 'wp_ajax_quads_sync_random_ads_in_new_design', array($this, 'quadsSyncRandomAdsInNewDesign') );

        }
                
        public static function getInstance() {
        if ( null == self::$instance ) {
            self::$instance = new self;
        }
            return self::$instance;
        }
        public function quadsAdminInit(){            
            $this->migration_service->quadsSaveAllAdToNewDesign();           
        }
        
        public function quadsSyncAdsInNewDesign(){
               
            check_ajax_referer( 'quads_ajax_nonce', 'nonce' );

            if( ! current_user_can( 'manage_options' ) )
                return;

                $quads_settings = get_option('quads_settings');

                if(isset($quads_settings['ads'])){               
                    
                    $i=1;
                    foreach($quads_settings['ads'] as $key => $value){                            
    
                        if($key === 'ad'.$i){
                            
                            $post_id = quadsGetPostIdByMetaKeyValue('quads_ad_old_id', $key); 
                            
                            if($post_id){                            
                                $value['ad_id']                      = $post_id;                                                                                                                                            
                            }else{
                                $value['quads_ad_old_id']            = $key;                                  
                            }    
                            
                             $parameters['quads_post_meta']       = $value;                                                                                            
                             $this->api_service->updateAdData($parameters, 'old_mode');                            

                        } 
                        
                        $i++;                       
                                            
                    }
    
               }
                   wp_die();         
        }                        


public function quadsSyncRandomAdsInNewDesign(){

    check_ajax_referer( 'quads_ajax_nonce', 'nonce' );

    if( ! current_user_can( 'manage_options' ) )
        return;

    $quads_settings = get_option('quads_settings');

    $random_beginning_of_post = true;
    $random_middle_of_post = true;
    $random_end_of_post = true;
    $random_after_more_tag = true;
    $random_before_last_paragraph = true;
    $random_after_paragraph1 = true;
    $random_after_paragraph2 = true;
    $random_after_paragraph3 = true;
    $random_after_image = true;  
    $quads_ads = $this->api_service->getAdDataByParam('quads-ads');
   if(isset($quads_ads['posts_data'])){

        $random_ads_list =array();
        foreach($quads_ads['posts_data'] as $key => $value){                            
            if($value['post']['post_status']=='draft'){
            // break;
            continue;
            }
             if($value['post_meta']['code']  || $value['post_meta']['g_data_ad_slot']){
                $random_ads_list[] = array('value'=>$value['post_meta']['ad_id'],'label'=>$value['post_meta']['quads_ad_old_id']);
            }

            if($value['post_meta']['position'] == 'beginning_of_post' && $value['post_meta']['ad_type'] == 'random_ads'){
                $random_beginning_of_post = false;
            }
            if($value['post_meta']['position'] =='middle_of_post' && $value['post_meta']['ad_type'] == 'random_ads'){
                $random_middle_of_post = false;
            }
            if($value['post_meta']['position'] == 'end_of_post' ){
                $random_end_of_post = false;
            }
            if($value['post_meta']['position'] == 'after_more_tag' && $value['post_meta']['ad_type'] == 'random_ads'){
                $random_after_more_tag = false;
            }
             if($value['post_meta']['position'] == 'before_last_paragraph' && $value['post_meta']['ad_type'] == 'random_ads'){
                $random_before_last_paragraph = false;
            }
            if($value['post_meta']['position'] == 'after_paragraph' && $value['post_meta']['ad_type'] == 'random_ads' && $value['post_meta']['label'] =='Random ads after paragraph 1'){
                $random_after_paragraph1 = false;
            }
            if($value['post_meta']['position'] == 'after_paragraph' && $value['post_meta']['ad_type'] == 'random_ads' && $value['post_meta']['label'] =='Random ads after paragraph 2'){
                $random_after_paragraph2 = false;
            }
            if($value['post_meta']['position'] == 'after_paragraph' && $value['post_meta']['ad_type'] == 'random_ads' && $value['post_meta']['label'] =='Random ads after paragraph 3'){
                $random_after_paragraph3 = false;
            }
            if($value['post_meta']['position'] == 'after_image' && $value['post_meta']['ad_type'] == 'random_ads'){
                $random_after_image = false;
            }                    

        }

        if(isset($quads_settings['pos1'])){ 
            if(isset($quads_settings['pos1']['BegnAds']) && $quads_settings['pos1']['BegnAds'] && $random_beginning_of_post){
                if(isset($quads_settings['pos1']['BegnRnd']) && $quads_settings['pos1']['BegnRnd']== 0){ 
                    $visibility_include[0]['type']['label'] = 'Post Type';
                    $visibility_include[0]['type']['value'] = 'post_type';
                    $visibility_include[0]['value']['label'] = 'post';
                    $visibility_include[0]['type']['value'] = 'post';
                    $value['visibility_include'] = $visibility_include;
                    $value['ad_type']       = 'random_ads';
                    $value['random_ads_list']   = $random_ads_list;
                    $value['position']      = 'beginning_of_post';  
                    $value['label']         = 'Random ads beginning';
                    $parameters['quads_post_meta']  = $value;
                    $this->api_service->updateAdData($parameters);  
                }
            }
        } 
        if(isset($quads_settings['pos2'])){ 
            if(isset($quads_settings['pos2']['MiddAds']) && $quads_settings['pos2']['MiddAds']  && $random_middle_of_post){
                if(isset($quads_settings['pos2']['MiddRnd']) && $quads_settings['pos2']['MiddRnd']== 0){ 
                    $visibility_include[0]['type']['label'] = 'Post Type';
                    $visibility_include[0]['type']['value'] = 'post_type';
                    $visibility_include[0]['value']['label'] = 'post';
                    $visibility_include[0]['type']['value'] = 'post';
                    $value['visibility_include'] = $visibility_include;
                    $value['ad_type']       = 'random_ads';
                    $value['random_ads_list']   = $random_ads_list;
                    $value['position']      = 'middle_of_post';  
                    $value['label']         = 'Random ads middle'; 
                    $value['random']        = true;  
                    $parameters['quads_post_meta']  = $value;
                    $this->api_service->updateAdData($parameters); 
                }
            }
        }
        if(isset($quads_settings['pos3'])){ 

            if(isset($quads_settings['pos3']['EndiAds']) && $quads_settings['pos3']['EndiAds'] && $random_end_of_post){

                if(isset($quads_settings['pos3']['EndiRnd']) && $quads_settings['pos3']['EndiRnd']== 0){ 
                    $visibility_include[0]['type']['label'] = 'Post Type';
                    $visibility_include[0]['type']['value'] = 'post_type';
                    $visibility_include[0]['value']['label'] = 'post';
                    $visibility_include[0]['type']['value'] = 'post';
                    $value['visibility_include'] = $visibility_include;
                    $value['ad_type']       = 'random_ads';
                    $value['random_ads_list']   = $random_ads_list;
                    $value['position']      = 'end_of_post';  
                    $value['label']         = 'Random ads end';  
                    $value['random']        = true; 
                    $parameters['quads_post_meta']  = $value;
                    $this->api_service->updateAdData($parameters); 
                }
            }
        }

        if(isset($quads_settings['pos4'])){ 
            if(isset($quads_settings['pos4']['MoreAds']) && $quads_settings['pos4']['MoreAds'] && $random_after_more_tag){
                if(isset($quads_settings['pos4']['MoreRnd']) && $quads_settings['pos4']['MoreRnd']== 0){ 
                    $visibility_include[0]['type']['label'] = 'Post Type';
                    $visibility_include[0]['type']['value'] = 'post_type';
                    $visibility_include[0]['value']['label'] = 'post';
                    $visibility_include[0]['type']['value'] = 'post';
                    $value['visibility_include'] = $visibility_include;
                    $value['ad_type']       = 'random_ads';
                    $value['random_ads_list']   = $random_ads_list;
                    $value['position']      = 'after_more_tag';  
                    $value['label']         = 'Random ads after more';
                    $value['random']        = true;   
                    $parameters['quads_post_meta']  = $value;
                    $this->api_service->updateAdData($parameters); 
                }
            }
        }
        if(isset($quads_settings['pos5'])){ 
            if(isset($quads_settings['pos5']['LapaAds']) &&  $quads_settings['pos5']['LapaAds'] == 1 && $random_before_last_paragraph){
                if(isset($quads_settings['pos5']['LapaRnd']) && $quads_settings['pos5']['LapaRnd']== 0){ 
                    $visibility_include[0]['type']['label'] = 'Post Type';
                    $visibility_include[0]['type']['value'] = 'post_type';
                    $visibility_include[0]['value']['label'] = 'post';
                    $visibility_include[0]['type']['value'] = 'post';
                    $value['visibility_include'] = $visibility_include;
                    $value['ad_type']       = 'random_ads';
                    $value['random_ads_list']   = $random_ads_list;
                    $value['position']      = 'before_last_paragraph';  
                    $value['label']         = 'Random ads before last paragraph'; 
                    $value['random']        = true;  
                    $parameters['quads_post_meta']  = $value;
                    $this->api_service->updateAdData($parameters); 
                }
            }
        }
        if(isset($quads_settings['pos6'])){ 
            if(isset($quads_settings['pos6']['Par1Ads']) &&  $quads_settings['pos6']['Par1Ads'] && $random_after_paragraph1){
                if(isset($quads_settings['pos6']['Par1Rnd']) && $quads_settings['pos6']['Par1Rnd']== 0){ 
                    $visibility_include[0]['type']['label'] = 'Post Type';
                    $visibility_include[0]['type']['value'] = 'post_type';
                    $visibility_include[0]['value']['label'] = 'post';
                    $visibility_include[0]['type']['value'] = 'post';
                    $value['visibility_include'] = $visibility_include;
                    $value['ad_type']       = 'random_ads';
                    $value['random_ads_list']   = $random_ads_list;
                    $value['position']      = 'after_paragraph';
                    $value['paragraph_number']  = $quads_settings['pos6']['Par1Nup'];
                    $value['enable_on_end_of_post'] = $quads_settings['pos6']['Par1Con'];  
                    $value['label']         = 'Random ads after paragraph 1';  
                    $value['random']        = true; 
                    $parameters['quads_post_meta']  = $value;
                    $this->api_service->updateAdData($parameters); 
                }
            }
        }
        if(isset($quads_settings['pos7'])){ 
            if(isset($quads_settings['pos7']['Par2Ads']) &&  $quads_settings['pos7']['Par2Ads'] && $random_after_paragraph2){
                if(isset($quads_settings['pos7']['Par2Rnd']) && $quads_settings['pos7']['Par2Rnd']== 0){ 
                    $visibility_include[0]['type']['label'] = 'Post Type';
                    $visibility_include[0]['type']['value'] = 'post_type';
                    $visibility_include[0]['value']['label'] = 'post';
                    $visibility_include[0]['type']['value'] = 'post';
                    $value['visibility_include'] = $visibility_include;
                    $value['ad_type']       = 'random_ads';
                    $value['random_ads_list']   = $random_ads_list;
                    $value['position']      = 'after_paragraph';  
                    $value['paragraph_number']  = $quads_settings['pos7']['Par2Nup'];
                    $value['enable_on_end_of_post'] = $quads_settings['pos7']['Par2Con'];
                    $value['label']         = 'Random ads after paragraph 2'; 
                    $value['random']        = true;  
                    $parameters['quads_post_meta']  = $value;
                    $this->api_service->updateAdData($parameters); 
                }
            }
        }
        if(isset($quads_settings['pos8'])){ 
            if(isset($quads_settings['pos8']['Par3Ads']) &&  $quads_settings['pos8']['Par3Ads'] && $random_after_paragraph3){
                if(isset($quads_settings['pos8']['Par3Rnd']) && $quads_settings['pos8']['Par3Rnd']== 0){ 
                    $visibility_include[0]['type']['label'] = 'Post Type';
                    $visibility_include[0]['type']['value'] = 'post_type';
                    $visibility_include[0]['value']['label'] = 'post';
                    $visibility_include[0]['type']['value'] = 'post';
                    $value['visibility_include'] = $visibility_include;
                    $value['ad_type']       = 'random_ads';
                    $value['random_ads_list']   = $random_ads_list;
                    $value['position']      = 'after_paragraph'; 
                    $value['paragraph_number']              = $quads_settings['pos8']['Par3Nup'];
                    $value['enable_on_end_of_post']         = $quads_settings['pos8']['Par3Con']; 
                    $value['label']         = 'Random ads after paragraph 3';  
                    $value['random']        = true; 
                    $parameters['quads_post_meta']  = $value;
                    $this->api_service->updateAdData($parameters); 
                }
            }
        }
        if(isset($quads_settings['pos9'])){ 
            if(isset($quads_settings['pos9']['Img1Ads']) &&  $quads_settings['pos9']['Img1Ads'] && $random_after_image){
                if(isset($quads_settings['pos9']['Img1Rnd']) && $quads_settings['pos9']['Img1Rnd']== 0){ 
                    $visibility_include[0]['type']['label'] = 'Post Type';
                    $visibility_include[0]['type']['value'] = 'post_type';
                    $visibility_include[0]['value']['label'] = 'post';
                    $visibility_include[0]['type']['value'] = 'post';
                    $value['visibility_include'] = $visibility_include;
                    $value['ad_type']       = 'random_ads';
                    $value['random_ads_list']   = $random_ads_list;
                    $value['position']      = 'after_image'; 
                    $value['paragraph_number']  = $quads_settings['pos9']['Img1Nup'];
                    $value['image_number']   = $quads_settings['pos9']['Img1Con']; 
                    $value['label']         = 'Random ads after image';  
                    $value['random']        = true; 
                    $parameters['quads_post_meta']  = $value;
                    $this->api_service->updateAdData($parameters); 
                }
            }
        }

    }
    wp_die();         
}  
}
if(class_exists('QUADS_Ad_Setup')){
    $quadsAdSetup = QUADS_Ad_Setup::getInstance();
    $quadsAdSetup->quadsAdSetupHooks();
}