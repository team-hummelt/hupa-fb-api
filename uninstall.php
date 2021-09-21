<?php
/**
 *  * Jens Wiecker PHP Class
 *  * @package Jens Wiecker WordPress Plugin
 *  * Copyright 2021, Jens Wiecker
 *  * License: Commercial - goto https://www.hummelt-werbeagentur.de/
 *  * https://www.hummelt-werbeagentur.de/
 *
 */

defined('WP_UNINSTALL_PLUGIN') or die();

global $wpdb;
$table_name = $wpdb->prefix . 'fb_api_settings';
$sql = "DROP TABLE IF EXISTS $table_name";
$wpdb->query($sql);

$table_name = $wpdb->prefix . 'fb_api_imports';
$sql = "DROP TABLE IF EXISTS $table_name";
$wpdb->query($sql);


delete_option("jal_hupa_fb_api_db_version");

//DELETE FP-CUSTOM POST-TYPE
if( !function_exists( 'plugin_prefix_unregister_post_type' ) ) {
    function plugin_prefix_unregister_post_type(){
        unregister_post_type( 'facebook_posts' );
    }
}

add_action('init','plugin_prefix_unregister_post_type');


//WP-CRONJOB ENTFERNEN
//$timestamp = wp_next_scheduled( 'fb_api_plugin_sync' );
//wp_unschedule_event( $timestamp, 'fb_api_plugin_sync' );
wp_clear_scheduled_hook('fb_api_plugin_sync');
