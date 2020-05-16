<?php

/**
 * Render Ad Functions
 *
 * @package     QUADS
 * @subpackage  Functions/Render Ad Functions
 * @copyright   Copyright (c) 2016, René Hermenau
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       0.9.0
 */
// Exit if accessed directly
if( !defined( 'ABSPATH' ) )
    exit;

/**
 * Render the adsense code
 * 
 * @param1 string the ad id  => ad1, ad2, ad3 etc
 * @param2 string $string The adsense code
 * @param3 bool True when function is called from widget
 * 
 * @todo create support for widgets
 * @return string HTML js adsense code
 */
function quads_render_ad( $id, $string, $widget = false,$ampsupport='' ) {
    
    // Return empty string
    if( empty( $id ) ) {
        return '';
    }
    
    
    if (quads_is_amp_endpoint()){
        return quads_render_amp($id,$ampsupport);
    }
    

    // Return the original ad code if it's no adsense code
    if( false === quads_is_adsense( $id, $string ) && !empty( $string ) ) {
        // allow use of shortcodes in ad plain text content
        $string = quadsCleanShortcode('quads', $string);
        //wp_die('t1');
        return apply_filters( 'quads_render_ad', $string );
    }

    // Return the adsense ad code
    if( true === quads_is_adsense( $id, $string ) ) {
        return apply_filters( 'quads_render_ad', quads_render_google_async( $id ) );
    }
    if( true === quads_is_double_click( $id, $string ) ) {
        return apply_filters( 'quads_render_ad', quads_render_double_click_async( $id ) );
    }

    // Return empty string
    return '';
}
function quads_doubleclick_head_code(){

    $data_slot  = '';    
    require_once QUADS_PLUGIN_DIR . '/admin/includes/rest-api-service.php';
    $api_service = new QUADS_Ad_Setup_Api_Service();
    $quads_ads = $api_service->getAdDataByParam('quads-ads');               

    if(isset($quads_ads['posts_data'])){  

        foreach($quads_ads['posts_data'] as $key => $value){
            if($value['post']['post_status']== 'draft'){
                continue;
            }
            $ads =$value['post_meta'];
            if(isset($ads['random_ads_list']))
                $ads['random_ads_list'] = unserialize($ads['random_ads_list']);
            if(isset($ads['visibility_include']))
                $ads['visibility_include'] = unserialize($ads['visibility_include']);
            if(isset($ads['visibility_exclude']))
                $ads['visibility_exclude'] = unserialize($ads['visibility_exclude']);

            if(isset($ads['targeting_include']))
                $ads['targeting_include'] = unserialize($ads['targeting_include']);

            if(isset($ads['targeting_exclude']))
                $ads['targeting_exclude'] = unserialize($ads['targeting_exclude']);
            $is_on =quads_is_visibility_on($ads);
           if(!$is_on){
             continue;
           }
            if($ads['ad_type']== 'double_click'){
                $network_code  = $ads['network_code'];                          
                $ad_unit_name  = $ads['ad_unit_name'];

                $width        = (isset($ads['g_data_ad_width']) && !empty($ads['g_data_ad_width'])) ? $ads['g_data_ad_width'] : '300';  
                 $height        = (isset($ads['g_data_ad_height']) && !empty($ads['g_data_ad_height'])) ? $ads['g_data_ad_height'] : '250';                                                                                                            
                $data_slot .="googletag.defineSlot('/".esc_attr($network_code)."/".esc_attr($ad_unit_name)."/', [".esc_attr($width).", ".esc_attr($height)."], 'wp_quads_dfp_".esc_attr($ads['ad_id'])."')
             .addService(googletag.pubads());";
            }   

        }
        if( $data_slot !=''){

            echo "<script async src='https://securepubads.g.doubleclick.net/tag/js/gpt.js'></script>
                    <script>
                 window.googletag = window.googletag || {cmd: []};
  googletag.cmd.push(function() {
  ".$data_slot." 
    googletag.pubads().enableSingleRequest();
    googletag.enableServices();
  });
                </script>";   

        }                            

    }                                                    

}  
/**
 * Render Google async ad
 * 
 * @global array $quads_options
 * @param int $id
 * @return html
 */
function quads_render_double_click_async( $id ) {
    global $quads_options;
      $width        = (isset($quads_options['ads'][$id]['g_data_ad_width']) && !empty($quads_options['ads'][$id]['g_data_ad_width'])) ? $quads_options['ads'][$id]['g_data_ad_width'] : '300';  
        $height        = (isset($quads_options['ads'][$id]['g_data_ad_height']) && !empty($quads_options['ads'][$id]['g_data_ad_height'])) ? $quads_options['ads'][$id]['g_data_ad_height'] : '250';  

    $html = "\n <!-- " . QUADS_NAME . " v." . QUADS_VERSION . " Content Doubleclick async --> \n\n";
    $html .= '<div id="wp_quads_dfp_'.esc_attr($quads_options['ads'][$id]['ad_id']). '" style="height:'.esc_attr($height). 'px; width:'.esc_attr($width). 'px;">
                        <script>
                        googletag.cmd.push(function() { googletag.display("wp_quads_dfp_'.esc_attr($quads_options['ads'][$id]['ad_id']).'"); });
                        </script>
                        </div>';
    $html .= "\n <!-- end WP QUADS --> \n\n";
    return apply_filters( 'quads_render_double_click_async', $html );
}


/**
 * Render Google async ad
 * 
 * @global array $quads_options
 * @param int $id
 * @return html
 */
function quads_render_google_async( $id ) {
    global $quads_options;


    // Default ad sizes - Option: Auto
    $default_ad_sizes[$id] = array(
        'desktop_width' => '300',
        'desktop_height' => '250',
        'tbl_landscape_width' => '300',
        'tbl_landscape_height' => '250',
        'tbl_portrait_width' => '300',
        'tbl_portrait_height' => '250',
        'phone_width' => '300',
        'phone_height' => '250'
    );

    // Overwrite default values if there are ones
    // Desktop big ad
    if( !empty( $quads_options['ads'][$id]['desktop_size'] ) && $quads_options['ads'][$id]['desktop_size'] !== 'Auto' ) {
        $ad_size_parts = explode( ' x ', $quads_options['ads'][$id]['desktop_size'] );
        $default_ad_sizes[$id]['desktop_width'] = $ad_size_parts[0];
        $default_ad_sizes[$id]['desktop_height'] = $ad_size_parts[1];
    }


    //tablet landscape
    if( !empty( $quads_options['ads'][$id]['tbl_lands_size'] ) && $quads_options['ads'][$id]['tbl_lands_size'] !== 'Auto' ) {
        $ad_size_parts = explode( ' x ', $quads_options['ads'][$id]['tbl_lands_size'] );
        $default_ad_sizes[$id]['tbl_landscape_width'] = $ad_size_parts[0];
        $default_ad_sizes[$id]['tbl_landscape_height'] = $ad_size_parts[1];
    }


    //tablet portrait
    if( !empty( $quads_options['ads'][$id]['tbl_portr_size'] ) && $quads_options['ads'][$id]['tbl_portr_size'] !== 'Auto' ) {
        $ad_size_parts = explode( ' x ', $quads_options['ads'][$id]['tbl_portr_size'] );
        $default_ad_sizes[$id]['tbl_portrait_width'] = $ad_size_parts[0];
        $default_ad_sizes[$id]['tbl_portrait_height'] = $ad_size_parts[1];
    }


    //phone
    if( !empty( $quads_options['ads'][$id]['phone_size'] ) && $quads_options['ads'][$id]['phone_size'] !== 'Auto' ) {
        $ad_size_parts = explode( ' x ', $quads_options['ads'][$id]['phone_size'] );
        $default_ad_sizes[$id]['phone_width'] = $ad_size_parts[0];
        $default_ad_sizes[$id]['phone_height'] = $ad_size_parts[1];
    }


    $html = "\n <!-- " . QUADS_NAME . " v." . QUADS_VERSION . " Content AdSense async --> \n\n";

    //google async script
    $html .= '<script async data-cfasync="false" src="//pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>';

    $html .= '<script type="text/javascript" data-cfasync="false">' . "\n";
    $html .= 'var quads_screen_width = document.body.clientWidth;' . "\n";
    

    $html .= quads_render_desktop_js( $id, $default_ad_sizes );
    $html .= quads_render_tablet_landscape_js( $id, $default_ad_sizes );
    $html .= quads_render_tablet_portrait_js( $id, $default_ad_sizes );
    $html .= quads_render_phone_js( $id, $default_ad_sizes );

    $html .= '</script>' . "\n";

    $html .= "\n <!-- end WP QUADS --> \n\n";

    return apply_filters( 'quads_render_adsense_async', $html );
}

/**
 * Render Google Ad Code Java Script for desktop devices
 * 
 * @global array $quads_options
 * @param string $id
 * @param array $default_ad_sizes
 * @return string
 */
function quads_render_desktop_js( $id, $default_ad_sizes ) {
    global $quads_options;
    
    $adtype = 'desktop';

    $backgroundcolor = '';

    $responsive_style = 'display:block;' . $backgroundcolor;
    
    if( quads_is_extra() && isset( $quads_options['ads'][$id]['adsense_type'] ) && $quads_options['ads'][$id]['adsense_type'] === 'responsive' ) {
        $width = $default_ad_sizes[$id][$adtype.'_width'];

        $height = $default_ad_sizes[$id][$adtype.'_height'];

        $normal_style = 'display:inline-block;width:' . $width . 'px;height:' . $height . 'px;' . $backgroundcolor;

        $style = isset( $quads_options['ads'][$id]['adsense_type'] ) && $quads_options['ads'][$id]['adsense_type'] === 'responsive' && (isset( $quads_options['ads'][$id][$adtype.'_size'] ) && $quads_options['ads'][$id][$adtype.'_size'] === 'Auto') ? $responsive_style : $normal_style;
    } else {
        $width = empty( $quads_options['ads'][$id]['g_data_ad_width'] ) ? $default_ad_sizes[$id][$adtype.'_width'] : $quads_options['ads'][$id]['g_data_ad_width'];

        $height = empty( $quads_options['ads'][$id]['g_data_ad_height'] ) ? $default_ad_sizes[$id][$adtype.'_height'] : $quads_options['ads'][$id]['g_data_ad_height'];

        $normal_style = 'display:inline-block;width:' . $width . 'px;height:' . $height . 'px;' . $backgroundcolor;

        $style = isset( $quads_options['ads'][$id]['adsense_type'] ) && $quads_options['ads'][$id]['adsense_type'] === 'responsive' ? $responsive_style : $normal_style;
    }

    $ad_format = (isset( $quads_options['ads'][$id]['adsense_type'] ) && $quads_options['ads'][$id]['adsense_type'] === 'responsive') && (isset( $quads_options['ads'][$id][$adtype.'_size'] ) && $quads_options['ads'][$id][$adtype.'_size'] === 'Auto') ? 'data-ad-format="auto"' : '';

    $html = '<ins class="adsbygoogle" style="' . $style . '"';
    $html .= ' data-ad-client="' . $quads_options['ads'][$id]['g_data_ad_client'] . '"';
    $html .= ' data-ad-slot="' . $quads_options['ads'][$id]['g_data_ad_slot'] . '" ' . $ad_format . '></ins>';
    
    if (!quads_is_extra() && !empty( $default_ad_sizes[$id][$adtype.'_width'] ) and ! empty( $default_ad_sizes[$id][$adtype.'_height'])){
$js = 'if ( quads_screen_width >= 1140 ) {
/* desktop monitors */
document.write(\'' . $html . '\');
(adsbygoogle = window.adsbygoogle || []).push({});
}';
        return $js;   
    }
    
    if( !isset( $quads_options['ads'][$id][$adtype] ) and !empty( $default_ad_sizes[$id][$adtype.'_width'] ) and ! empty( $default_ad_sizes[$id][$adtype.'_height'] ) ) {
$js = 'if ( quads_screen_width >= 1140 ) {
/* desktop monitors */
document.write(\'' . $html . '\');
(adsbygoogle = window.adsbygoogle || []).push({});
}';
        return $js;
    }
}

/**
 * Render Google Ad Code Java Script for tablet landscape devices
 * 
 * @global array $quads_options
 * @param string $id
 * @param array $default_ad_sizes
 * @return string
 */
function quads_render_tablet_landscape_js( $id, $default_ad_sizes ) {
    global $quads_options;
    
    $adtype = 'tbl_landscape';
    $adtype_short = 'tbl_lands';

    //$backgroundcolor = 'background-color:white;'; // Pro Version
    $backgroundcolor = '';

    $responsive_style = 'display:block;' . $backgroundcolor;

    if( quads_is_extra() && isset( $quads_options['ads'][$id]['adsense_type'] ) && $quads_options['ads'][$id]['adsense_type'] === 'responsive' ) {
        $width = $default_ad_sizes[$id][$adtype.'_width'];

        $height = $default_ad_sizes[$id][$adtype.'_height'];

        $normal_style = 'display:inline-block;width:' . $width . 'px;height:' . $height . 'px;' . $backgroundcolor;

        $style = isset( $quads_options['ads'][$id]['adsense_type'] ) && $quads_options['ads'][$id]['adsense_type'] === 'responsive' && (isset( $quads_options['ads'][$id][$adtype_short.'_size'] ) && $quads_options['ads'][$id][$adtype_short.'_size'] === 'Auto') ? $responsive_style : $normal_style;
    } else {
        $width = empty( $quads_options['ads'][$id]['g_data_ad_width'] ) ? $default_ad_sizes[$id][$adtype.'_width'] : $quads_options['ads'][$id]['g_data_ad_width'];

        $height = empty( $quads_options['ads'][$id]['g_data_ad_height'] ) ? $default_ad_sizes[$id][$adtype.'_height'] : $quads_options['ads'][$id]['g_data_ad_height'];

        $normal_style = 'display:inline-block;width:' . $width . 'px;height:' . $height . 'px;' . $backgroundcolor;

        $style = isset( $quads_options['ads'][$id]['adsense_type'] ) && $quads_options['ads'][$id]['adsense_type'] === 'responsive' ? $responsive_style : $normal_style;
    }

    $ad_format = (isset( $quads_options['ads'][$id]['adsense_type'] ) && $quads_options['ads'][$id]['adsense_type'] === 'responsive') && (isset( $quads_options['ads'][$id][$adtype_short.'_size'] ) && $quads_options['ads'][$id][$adtype_short.'_size'] === 'Auto') ? 'data-ad-format="auto"' : '';


    $html = '<ins class="adsbygoogle" style="' . $style . '"';
    $html .= ' data-ad-client="' . $quads_options['ads'][$id]['g_data_ad_client'] . '"';
    $html .= ' data-ad-slot="' . $quads_options['ads'][$id]['g_data_ad_slot'] . '" ' . $ad_format . '></ins>';

        if( !quads_is_extra() && ! empty( $default_ad_sizes[$id][$adtype.'_width'] ) and ! empty( $default_ad_sizes[$id][$adtype.'_height'] ) ) {
$js = 'if ( quads_screen_width >= 1024  && quads_screen_width < 1140 ) {
/* tablet landscape */
document.write(\'' . $html . '\');
(adsbygoogle = window.adsbygoogle || []).push({});
}';
        return $js;
    }
    
    if( !isset( $quads_options['ads'][$id]['tablet_landscape'] ) and ! empty( $default_ad_sizes[$id][$adtype.'_width'] ) and ! empty( $default_ad_sizes[$id][$adtype.'_height'] ) ) {
        $js = 'if ( quads_screen_width >= 1024  && quads_screen_width < 1140 ) {
/* tablet landscape */
document.write(\'' . $html . '\');
(adsbygoogle = window.adsbygoogle || []).push({});
}';
        return $js;
    }
}

/**
 * Render Google Ad Code Java Script for tablet portrait devices
 * 
 * @global array $quads_options
 * @param string $id
 * @param array $default_ad_sizes
 * @return string
 */
function quads_render_tablet_portrait_js( $id, $default_ad_sizes ) {
    global $quads_options;
  
    $adtype = 'tbl_portrait';
    
    $adtype_short = 'tbl_portr';

    $backgroundcolor = '';

    $responsive_style = 'display:block;' . $backgroundcolor;

    if( quads_is_extra() && isset( $quads_options['ads'][$id]['adsense_type'] ) && $quads_options['ads'][$id]['adsense_type'] === 'responsive' ) {
        $width = $default_ad_sizes[$id][$adtype.'_width'];

        $height = $default_ad_sizes[$id][$adtype.'_height'];

        $normal_style = 'display:inline-block;width:' . $width . 'px;height:' . $height . 'px;' . $backgroundcolor;

        $style = isset( $quads_options['ads'][$id]['adsense_type'] ) && $quads_options['ads'][$id]['adsense_type'] === 'responsive' && (isset( $quads_options['ads'][$id][$adtype_short.'_size'] ) && $quads_options['ads'][$id][$adtype_short.'_size'] === 'Auto') ? $responsive_style : $normal_style;
    } else {
        $width = empty( $quads_options['ads'][$id]['g_data_ad_width'] ) ? $default_ad_sizes[$id][$adtype.'_width'] : $quads_options['ads'][$id]['g_data_ad_width'];

        $height = empty( $quads_options['ads'][$id]['g_data_ad_height'] ) ? $default_ad_sizes[$id][$adtype.'_height'] : $quads_options['ads'][$id]['g_data_ad_height'];

        $normal_style = 'display:inline-block;width:' . $width . 'px;height:' . $height . 'px;' . $backgroundcolor;

        $style = isset( $quads_options['ads'][$id]['adsense_type'] ) && $quads_options['ads'][$id]['adsense_type'] === 'responsive' ? $responsive_style : $normal_style;
    }

    $ad_format = (isset( $quads_options['ads'][$id]['adsense_type'] ) && $quads_options['ads'][$id]['adsense_type'] === 'responsive') && (isset( $quads_options['ads'][$id][$adtype_short.'_size'] ) && $quads_options['ads'][$id][$adtype_short.'_size'] === 'Auto') ? 'data-ad-format="auto"' : '';

    $html = '<ins class="adsbygoogle" style="' . $style . '"';
    $html .= ' data-ad-client="' . $quads_options['ads'][$id]['g_data_ad_client'] . '"';
    $html .= ' data-ad-slot="' . $quads_options['ads'][$id]['g_data_ad_slot'] . '" ' . $ad_format . '></ins>';

        if( !quads_is_extra() and !empty( $default_ad_sizes[$id]['tbl_portrait_width'] ) and !empty( $default_ad_sizes[$id][$adtype.'_height'] ) ) {
$js = 'if ( quads_screen_width >= 768  && quads_screen_width < 1024 ) {
/* tablet portrait */
document.write(\'' . $html . '\');
(adsbygoogle = window.adsbygoogle || []).push({});
}';
return $js;
        }
    
    if( !isset( $quads_options['ads'][$id]['tablet_portrait'] ) and !empty( $default_ad_sizes[$id]['tbl_portrait_width'] ) and !empty( $default_ad_sizes[$id][$adtype.'_height'] ) ) {
$js = 'if ( quads_screen_width >= 768  && quads_screen_width < 1024 ) {
/* tablet portrait */
document.write(\'' . $html . '\');
(adsbygoogle = window.adsbygoogle || []).push({});
}';
return $js;
    }
}

/**
 * Render Google Ad Code Java Script for phone devices
 * 
 * @global array $quads_options
 * @param string $id
 * @param array $default_ad_sizes
 * @return string
 */
function quads_render_phone_js( $id, $default_ad_sizes ) {
    global $quads_options;
    
    $adtype = 'phone';

    $backgroundcolor = '';

    $responsive_style = 'display:block;' . $backgroundcolor;

    if( quads_is_extra() && isset( $quads_options['ads'][$id]['adsense_type'] ) && $quads_options['ads'][$id]['adsense_type'] === 'responsive' ) {
        $width = $default_ad_sizes[$id][$adtype.'_width'];

        $height = $default_ad_sizes[$id][$adtype.'_height'];

        $normal_style = 'display:inline-block;width:' . $width . 'px;height:' . $height . 'px;' . $backgroundcolor;

        $style = isset( $quads_options['ads'][$id]['adsense_type'] ) && $quads_options['ads'][$id]['adsense_type'] === 'responsive' && (isset( $quads_options['ads'][$id][$adtype.'_size'] ) && $quads_options['ads'][$id][$adtype.'_size'] === 'Auto') ? $responsive_style : $normal_style;
    } else {
        $width = empty( $quads_options['ads'][$id]['g_data_ad_width'] ) ? $default_ad_sizes[$id][$adtype.'_width'] : $quads_options['ads'][$id]['g_data_ad_width'];

        $height = empty( $quads_options['ads'][$id]['g_data_ad_height'] ) ? $default_ad_sizes[$id][$adtype.'_height'] : $quads_options['ads'][$id]['g_data_ad_height'];

        $normal_style = 'display:inline-block;width:' . $width . 'px;height:' . $height . 'px;' . $backgroundcolor;

        $style = isset( $quads_options['ads'][$id]['adsense_type'] ) && $quads_options['ads'][$id]['adsense_type'] === 'responsive' ? $responsive_style : $normal_style;
    }

    $ad_format = (isset( $quads_options['ads'][$id]['adsense_type'] ) && $quads_options['ads'][$id]['adsense_type'] === 'responsive') && (isset( $quads_options['ads'][$id][$adtype.'_size'] ) && $quads_options['ads'][$id][$adtype.'_size'] === 'Auto') ? 'data-ad-format="auto"' : '';

    $html = '<ins class="adsbygoogle" style="' . $style . '"';
    $html .= ' data-ad-client="' . $quads_options['ads'][$id]['g_data_ad_client'] . '"';
    $html .= ' data-ad-slot="' . $quads_options['ads'][$id]['g_data_ad_slot'] . '" ' . $ad_format . '></ins>';

        if( !quads_is_extra() and ! empty( $default_ad_sizes[$id][$adtype.'_width'] ) and ! empty( $default_ad_sizes[$id][$adtype.'_height'] ) ) {
        $js = 'if ( quads_screen_width < 768 ) {
/* phone */
document.write(\'' . $html . '\');
(adsbygoogle = window.adsbygoogle || []).push({});
}';
        return $js;
    }
    
    
    if( !isset( $quads_options['ads'][$id][$adtype] ) and ! empty( $default_ad_sizes[$id][$adtype.'_width'] ) and ! empty( $default_ad_sizes[$id][$adtype.'_height'] ) ) {
        $js = 'if ( quads_screen_width < 768 ) {
/* phone */
document.write(\'' . $html . '\');
(adsbygoogle = window.adsbygoogle || []).push({});
}';
        return $js;
    }
}


/**
 * Check if ad code is adsense or other ad code
 * 
 * @param1 id int id of the ad
 * @param string $string ad code
 * @return boolean
 */
function quads_is_adsense( $id, $string ) {
    global $quads_options;

    if( isset($quads_options['ads'][$id]['ad_type']) && $quads_options['ads'][$id]['ad_type'] === 'adsense') {
        return true;
    }
    return false;
}
/**
 * Check if ad code is double click or other ad code
 * 
 * @param1 id int id of the ad
 * @param string $string ad code
 * @return boolean
 */
function quads_is_double_click( $id, $string ) {
    global $quads_options;

    if( isset($quads_options['ads'][$id]['ad_type']) && $quads_options['ads'][$id]['ad_type'] === 'double_click') {
        return true;
    }
    return false;
}


/**
 * Render advert on amp pages
 * 
 * @global array $quads_options
 * @param int $id
 * @return string
 */
function quads_render_amp($id,$ampsupport=''){
    global $quads_options,$quads_mode;

    if($quads_mode == 'old'){
        // quads pro not installed and activated
        if ( !quads_is_extra() ){
           return '';
        }
        if(isset($quads_options['ads'][$id]['amp']) && isset($quads_options['ads'][$id]['code']) && !empty($quads_options['ads'][$id]['code'])){
                return $quads_options['ads'][$id]['code'];
            }
        // if amp is not activated return empty
        if (!isset($quads_options['ads'][$id]['amp']) || quads_is_disabled_post_amp() ){
            return '';
        }
    }else{

         if((isset($quads_options['ads'][$id]['enabled_on_amp']) && isset($quads_options['ads'][$id]['code']) && !empty($quads_options['ads'][$id]['code']))|| (!empty($ampsupport) && $ampsupport)){
                if((isset($quads_options['ads'][$id]['enabled_on_amp']) && $quads_options['ads'][$id]['enabled_on_amp']) || (!empty($ampsupport) && $ampsupport)){
                    if(isset($quads_options['ads'][$id]['code'])){
                      return  $quads_options['ads'][$id]['code'];
                    }else if(isset($quads_options['ads'][$id]['post_meta'])){
                      return $quads_options['ads'][$id]['post_meta']['code'];
                    }else{
                       return '';
                    }
                }else{
                    return '';
                }
            }
        // if amp is not activated return empty
        if (!isset($quads_options['ads'][$id]['enabled_on_amp']) || quads_is_disabled_post_amp() ){
            return '';
        }
    }
    if (!empty($quads_options['ads'][$id]['amp_code'])){
        $html = $quads_options['ads'][$id]['amp_code'];
    } else {
            if($quads_options['ads'][$id]['ad_type'] == 'double_click'){
                 $width        = isset($quads_options['ads'][$id]['g_data_ad_width'])? $quads_options['ads'][$id]['g_data_ad_width'] : '300';            
                $height       =isset($quads_options['ads'][$id]['g_data_ad_height'])? $quads_options['ads'][$id]['g_data_ad_height'] : '250';   
                $network_code  = $quads_options['ads'][$id]['network_code'];                          
                $ad_unit_name  = $quads_options['ads'][$id]['ad_unit_name']; 
               // Return default Double click code
        $html = '<amp-ad width='.esc_attr($width).' height='.esc_attr($width).' type="doubleclick" data-ad-slot="/'.esc_attr($network_code)."/".esc_attr($ad_unit_name). '/" data-multi-size="468x60,300x250"></amp-ad>';
            }else{
                   // Return default adsense code
             $html = '<amp-ad layout="responsive" width=300 height=250 type="adsense" data-ad-client="'. $quads_options['ads'][$id]['g_data_ad_client'] . '" data-ad-slot="'.$quads_options['ads'][$id]['g_data_ad_slot'].'"></amp-ad>';
            }
     
    }

    return $html;
}

/**
 * Check if page is AMP one
 * 
 * @return boolean
 */
function quads_is_amp_endpoint(){
   
   // General AMP query
   if (false !== get_query_var( 'amp', false )){
      return true;
   }
   
    // Automattic AMP plugin
    if (  function_exists( 'is_amp_endpoint' )){
        if ( is_amp_endpoint()){
            return true;
        }
    }
    return false;
}