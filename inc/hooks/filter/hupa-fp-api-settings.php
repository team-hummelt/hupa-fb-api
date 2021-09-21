<?php

namespace Hupa\FBApiPluginSettings;
defined('ABSPATH') or die();

use stdClass;

/**
 * Jens Wiecker PHP Class
 * @package Jens Wiecker WordPress Plugin
 * Copyright 2021, Jens Wiecker
 * License: Commercial - goto https://www.hummelt-werbeagentur.de/
 * https://www.hummelt-werbeagentur.de/
 *
 */

if (!class_exists('HupaFBApiPluginSettings')) {
    add_action('plugin_loaded', array('Hupa\\FBApiPluginSettings\\HupaFBApiPluginSettings', 'init'), 0);

    final class HupaFBApiPluginSettings
    {
        //INSTANCE
        private static $instance;

        //DB TABLES
        private string $table_settings = 'fb_api_settings';

        /**
         * @return static
         */
        public static function init(): self
        {
            if (is_null(self::$instance)) {
                self::$instance = new self;
            }
            return self::$instance;
        }

        /**
         * HupaFBApiPluginSettings constructor.
         */
        public function __construct()
        {
            //GET SETTINGS
            add_filter('get_fb_api_plugin_settings', array($this, 'hupa_get_plugin_api_settings'));
            //SET SETTINGS
            add_filter('set_plugin_default_settings', array($this, 'hupa_set_plugin_default_settings'));
            //UPDATE SETTINGS
            add_filter('fb_api_plugin_update_settings', array($this, 'hupa_fb_api_plugin_update_settings'));
            //GET TERMS
            add_filter('get_custom_terms', array($this, 'hupa_get_custom_terms'));
            //GET TERMS BY term_id
            add_filter('fb_api_get_term_by_term_id', array($this, 'hupa_fb_api_get_term_by_term_id'));
            //SELECT MAX POSTS OBJECT
            add_filter('select_max_post_for_sync', array($this, 'hupa_max_post_for_sync'));
            //AJAX FORM LANGUAGE
            add_filter('fb_api_language', array($this, 'hupa_fb_api_language'));
        }

        /**
         * @param $args
         * @return object
         */
        public function hupa_get_plugin_api_settings($args): object
        {
            $record = new stdClass();
            global $wpdb;
            $table = $wpdb->prefix . $this->table_settings;
            $results = $wpdb->get_row("SELECT * FROM {$table} {$args} ");
            if (!$results) {
                $record->status = false;
                return $record;
            }
            $record->record = $results;
            $record->status = true;
            return $record;
        }


        /**
         * @param $args
         * @return bool
         */
        public function hupa_set_plugin_default_settings($args): bool
        {
            $settings = $this->hupa_get_plugin_api_settings(false);
            if (!$settings->status) {
                global $wpdb;
                $table = $wpdb->prefix . $this->table_settings;
                $wpdb->insert(
                    $table,
                    array(
                        'sync_interval' => 2
                    ),
                    array('%d')
                );
                return (bool)$wpdb->insert_id;
            }
            return false;
        }

        /**
         * @param $record
         */
        public function hupa_fb_api_plugin_update_settings($record): void
        {
            $id = HUPA_PLUGIN_SETTINGS_ID;
            global $wpdb;
            $table = $wpdb->prefix . $this->table_settings;
            $wpdb->update(
                $table,
                array(
                    $record->column => $record->content,
                ),
                array('id' => $id),
                $record->type,
                array('%d')
            );
        }

        /**
         * @param string $taxonomy
         * @return object
         */
        public function hupa_get_custom_terms(string $taxonomy): object
        {
            $return = new  stdClass();
            $return->status = false;
            $terms = get_terms(array(
                'taxonomy' => $taxonomy,
                'parent' => 0,
                'hide_empty' => false,
            ));

            if (!$terms) {
                return $return;
            }
            $return->status = true;
            $return->terms = $terms;
            return $return;
        }


        /**
         * @param int $term_id
         * @return object
         */
        public function hupa_fb_api_get_term_by_term_id(int $term_id): object
        {
            $return = new  stdClass();
            $return->status = false;
            $terms = get_terms(array(
                'taxonomy' => 'facebook_category',
                'parent' => 0,
                'hide_empty' => false,
            ));

            foreach ($terms as $tmp) {
                if ($tmp->term_id == $term_id) {
                    $return->term = $tmp;
                    $return->status = true;
                    return $return;
                }
            }
            return $return;
        }

        public function hupa_max_post_for_sync(): object
        {
            $select = [
                "0" => [
                    "id" => 1,
                    'value' => 10
                ],
                "1" => [
                    "id" => 2,
                    'value' => 20
                ],
                "2" => [
                    "id" => 3,
                    'value' => 30
                ],
                "3" => [
                    "id" => 4,
                    'value' => 40
                ],
                "4" => [
                    "id" => 4,
                    'value' => 40
                ],
                "5" => [
                    "id" => 5,
                    'value' => 50
                ],
            ];

            return $this->arrayToObject($select);
        }

        /**
         * @return object
         */
        public function hupa_fb_api_language(): object
        {
            $lang = new stdClass();
            $lang->formulare = [
                'import_name' => __('Name or location for this import:', 'hupa-fb-api'),
                'import_name_help' => __('This name is displayed on the website..', 'hupa-fb-api'),
                'import_description' => __('Description for this import:', 'hupa-fb-api'),
                'import_description_help' => __('The description is optional.', 'hupa-fb-api'),
                'max_number_events' => __('Max. Number of posts and events Import:', 'hupa-fb-api'),
                'max_number_events_help' => __('The standard value is 100.', 'hupa-fb-api'),
                'header_api_options' => __('Facebook Api and WordPress Options:', 'hupa-fb-api'),
                'page_id_id_help' => __('If you do not enter a Page ID or User ID, your Facebook User ID will be used.', 'hupa-fb-api'),
                'fb_user_id' => __('Facebook User-ID:', 'hupa-fb-api'),
                'fb_user_help' => __('Only posts and events of the user are imported.', 'hupa-fb-api'),
                'user_id_aktiv' => __('User ID active', 'hupa-fb-api'),
                'header_select_cat' => __('Select category for Facebook posts and events:', 'hupa-fb-api'),
                'header_select_cat_sm' => __('If you do not select a category, the default categories for posts or events will be used.', 'hupa-fb-api'),
                'select' => __('select', 'hupa-fb-api'),
                'category_select' => __('Category for posts:', 'hupa-fb-api'),
                'event_select' => __('Category for events:', 'hupa-fb-api'),
                'header_new_cat' => __('Create a new category for Facebook posts or events:', 'hupa-fb-api'),
                'btn_new_cat' => __('Create new category', 'hupa-fb-api'),
                'btn_create_import' => __('Create a new Facebook import', 'hupa-fb-api'),
                'btn_update_import' => __('Save changes', 'hupa-fb-api'),
                'btn_back' => __('back to the overview', 'hupa-fb-api'),
                'btn_sync' => __('Synchronise now', 'hupa-fb-api'),
                'btn_del_import' => __('Delete import', 'hupa-fb-api'),
                'btn_reset_import' => __('Reset Import', 'hupa-fb-api'),
                'btn_del_posts' => __('Delete all posts', 'hupa-fb-api'),
            ];
            $lang->modal = [
                'del_header_import' => __('Delete import?', 'hupa-fb-api'),
                'del_import' => __('Delete import really?', 'hupa-fb-api'),
                'del_header_post' => __('Delete posts?', 'hupa-fb-api'),
                'del_post' => __('Delete all events and posts?', 'hupa-fb-api'),
                'delete_note' => __('The deletion can <b class="text-danger"> not</b> be undone!', 'hupa-fb-api'),
            ];
            return $lang;
        }

        private function arrayToObject($array): object
        {
            foreach ($array as $key => $value) {
                if (is_array($value)) $array[$key] = self::arrayToObject($value);
            }
            return (object)$array;
        }
    }


}//endClass

