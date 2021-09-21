<?php
defined('ABSPATH') or die();

/**
 * Jens Wiecker PHP Class
 * @package Jens Wiecker WordPress Plugin
 * Copyright 2021, Jens Wiecker
 * License: Commercial - goto https://www.hummelt-werbeagentur.de/
 * https://www.hummelt-werbeagentur.de/
 *
 */

function hupa_fb_api_plugin_create_db(): void
{
    global $wpdb;
    $hupa_fb_api_installed_ver = get_option("jal_hupa_fb_api_db_version");
    if ($hupa_fb_api_installed_ver != HUPA_FB_PLUGIN_DB_VERSION) {
        $table_name = $wpdb->prefix . 'fb_api_settings';
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table_name (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `app_id` varchar(255) NOT NULL DEFAULT '123456789ABCDEFGHI',
            `app_secret` varchar (255) NOT NULL DEFAULT '123456789ABCDEFGHI',
            `access_token` TINYTEXT NULL,
            `cron_aktiv` tinyint(1) NOT NULL DEFAULT 0,
            `max_sync` tinyint(1) NOT NULL DEFAULT 2,
            `sync_interval` tinyint(1) NOT NULL DEFAULT 2,
            `last_api_sync` TIMESTAMP NULL DEFAULT NOW(),
       PRIMARY KEY (id)
     ) $charset_collate;";
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);

            $table_name = $wpdb->prefix . 'fb_api_imports';
            $charset_collate = $wpdb->get_charset_collate();
            $sql = "CREATE TABLE $table_name (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `aktiv` tinyint(1) NOT NULL,
            `bezeichnung` varchar(255) NOT NULL UNIQUE,
            `description` tinytext NULL,
            `max_count` int(11) NOT NULL DEFAULT 100,
            `user_id` varchar(255) NOT NULL DEFAULT 'me',
            `user_aktiv` tinyint(1) NOT NULL DEFAULT 0,
            `page_id` varchar(255) NULL,
            `post_term` tinyint(6) NOT NULL,
            `event_term` tinyint(6) NOT NULL,
            `last_sync` varchar (255) NULL,
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
       PRIMARY KEY (id)
     ) $charset_collate;";
            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
            dbDelta($sql);

        apply_filters('set_plugin_default_settings', false);
        update_option("jal_hupa_fb_api_db_version", HUPA_FB_PLUGIN_DB_VERSION);
    }
}

function fb_api_plugin_update_db(): void
{
    if (get_option('jal_hupa_fb_api_db_version') != HUPA_FB_PLUGIN_DB_VERSION) {
        hupa_fb_api_plugin_create_db();
    }
}

add_action('check_fb_api_plugin_database', 'fb_api_plugin_update_db');
add_action('create_fb_api_plugin_database', 'hupa_fb_api_plugin_create_db');

