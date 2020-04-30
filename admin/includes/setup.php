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
                            
                            if(isset($quads_settings['pos1'])){ 
                            if($quads_settings['pos1']['BegnAds'] == 1){
                                if(isset($quads_settings['pos1']['BegnRnd']) && $quads_settings['pos1']['BegnRnd']== $i){ 
                                    $value['position']                      = 'beginning_of_post';  
                                }
                            }
                        } 

                        if(isset($quads_settings['pos2'])){ 
                            if($quads_settings['pos2']['MiddAds'] == 1){
                                if(isset($quads_settings['pos2']['MiddRnd']) && $quads_settings['pos2']['MiddRnd']== $i){ 
                                    $value['position']                      = 'middle_of_post';  
                                }
                            }
                        }
                         if(isset($quads_settings['pos3'])){ 
                            if($quads_settings['pos3']['EndiRnd'] == 1){
                                if(isset($quads_settings['pos3']['EndiRnd']) && $quads_settings['pos3']['EndiRnd']== $i){ 
                                    $value['position']                      = 'end_of_post';  
                                }
                            }
                        }
                         if(isset($quads_settings['pos4'])){ 
                            if($quads_settings['pos4']['MoreRnd'] == 1){
                                if(isset($quads_settings['pos4']['MoreRnd']) && $quads_settings['pos4']['MoreRnd']== $i){ 
                                    $value['position']                      = 'after_more_tag';  
                                }
                            }
                        }
                         if(isset($quads_settings['pos5'])){ 
                            if($quads_settings['pos5']['LapaRnd'] == 1){
                                if(isset($quads_settings['pos5']['LapaRnd']) && $quads_settings['pos5']['LapaRnd']== $i){ 
                                    $value['position']                      = 'middle_of_post';  
                                }
                            }
                        }
                         if(isset($quads_settings['pos6'])){ 
                            if($quads_settings['pos6']['Par1Ads'] == 1){
                                if(isset($quads_settings['pos6']['Par1Rnd']) && $quads_settings['pos6']['Par1Rnd']== $i){ 
                                    $value['position']                      = 'after_paragraph';  
                                    $value['paragraph_number']              = $quads_settings['pos6']['Par1Nup'];
                                    $value['enable_on_end_of_post']              = $quads_settings['pos6']['Par1Con'];
                                }
                            }
                        }
                        if(isset($quads_settings['pos7'])){ 
                            if($quads_settings['pos7']['Par2Ads'] == 1){
                                if(isset($quads_settings['pos7']['Par2Rnd']) && $quads_settings['pos7']['Par2Rnd']== $i){ 
                                    $value['position']                      = 'after_paragraph';  
                                    $value['paragraph_number']              = $quads_settings['pos7']['Par2Nup'];
                                    $value['enable_on_end_of_post']              = $quads_settings['pos7']['Par2Con'];
                                }
                            }
                        }
                        if(isset($quads_settings['pos8'])){ 
                            if($quads_settings['pos8']['Par3Rnd'] == 1){
                                if(isset($quads_settings['pos8']['Par3Rnd']) && $quads_settings['pos8']['Par3Rnd']== $i){ 
                                    $value['position']                      = 'after_paragraph';  
                                    $value['paragraph_number']              = $quads_settings['pos8']['Par3Nup'];
                                    $value['enable_on_end_of_post']              = $quads_settings['pos8']['Par3Con'];
                                }
                            }
                        }
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

}
if(class_exists('QUADS_Ad_Setup')){
    $quadsAdSetup = QUADS_Ad_Setup::getInstance();
    $quadsAdSetup->quadsAdSetupHooks();
}