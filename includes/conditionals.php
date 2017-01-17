<?php

/**
 * Conditionals
 *
 * @package     QUADS
 * @subpackage  Functions/conditionals
 * @copyright   Copyright (c) 2016, René Hermenau
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.2.8
 */



/**
 * Determine if ads are visible
 * 
 * @global arr $quads_options
 * @param string $content 
 * @since 0.9.4
 * @return boolean true when ads are shown
 */
function quads_ad_is_allowed( $content = null ) {
    global $quads_options;

    // Only show ads in main query
    if( !is_main_query() ) {
        return false;
    }
    
    $hide_ads = apply_filters('quads_hide_ads', false);

    if(
            (is_feed()) ||
            (is_search()) ||
            (is_404() ) ||
            (strpos( $content, '<!--NoAds-->' ) !== false) ||
            (strpos( $content, '<!--OffAds-->' ) !== false) ||
            (is_front_page() && !( isset( $quads_options['visibility']['AppHome'] ) ) ) ||
            //(is_single() && !( isset( $quads_options['visibility']['AppPost'] ) ) ) ||
            //(is_page() && !( isset( $quads_options['visibility']['AppPage'] ) ) ) ||
            (is_category() && !(isset( $quads_options['visibility']['AppCate'] ) ) ) ||
            (is_archive() && !( isset( $quads_options['visibility']['AppArch'] ) ) ) ||
            (is_tag() && !( isset( $quads_options['visibility']['AppTags'] ) ) ) ||
            (!quads_user_roles_permission()) ||
            (!quads_post_type_allowed()) ||
            (is_user_logged_in() && ( isset( $quads_options['visibility']['AppLogg'] ) ) ) ||
            true === $hide_ads
    ) {
        return false;
    }
    // else
    return true;
}



/**
 * Get the total number of active ads
 * 
 * @global int $visibleShortcodeAds
 * @global int $visibleContentAdsGlobal
 * @global int $ad_count_custom
 * @global int $ad_count_widget
 * @return int number of active ads
 */
function quads_get_total_ad_count(){
    global $visibleShortcodeAds, $visibleContentAdsGlobal, $ad_count_custom, $ad_count_widget;
    
    $shortcode = isset($visibleShortcodeAds) ? (int)$visibleShortcodeAds : 0;
    $content = isset($visibleContentAdsGlobal) ? (int)$visibleContentAdsGlobal : 0;
    $custom = isset($ad_count_custom) ? (int)$ad_count_custom : 0;
    $widget = isset($ad_count_widget) ? (int)$ad_count_widget : 0;
 
    return $shortcode + $content + $custom + $widget;
}

/**
 * Check if the maximum amount of ads are reached
 * 
 * @global arr $quads_options settings
 * @var int amount of ads to activate 

 * @return bool true if max is reached
 */

function quads_ad_reach_max_count(){
    global $quads_options;
    
    $maxads = isset($quads_options['maxads']) ? $quads_options['maxads'] : 10;
    if ( quads_get_total_ad_count() >= $maxads ){
        return true;
    }
}

/**
 * Increment count of active ads generated in the_content
 * 
 * @global int $ad_count
 * @param type $ad_count
 * @return int amount of active ads in the_content
 */
function quads_set_ad_count_content(){
    global $visibleContentAdsGlobal;
       
    $visibleContentAdsGlobal++;
    return $visibleContentAdsGlobal;
}

/**
 * Increment count of active ads generated with shortcodes
 * 
 * @return int amount of active shortcode ads in the_content
 */
function quads_set_ad_count_shortcode(){
    global $visibleShortcodeAds;
       
    $visibleShortcodeAds++;
    return $visibleShortcodeAds;
}

/**
 * Increment count of custom active ads 
 * 
 * @return int amount of active custom ads
 */
function quads_set_ad_count_custom(){
    global $ad_count_custom;
       
    $ad_count_custom++;
    return $ad_count_custom;
}

/**
 * Increment count of active ads generated on widgets
 * 
 * @return int amount of active widget ads 
 */
function quads_set_ad_count_widget(){
    global $ad_count_widget;

    $ad_count_widget++;
    return $ad_count_widget;
}
