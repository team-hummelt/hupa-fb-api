<?php

namespace Hupa\FBApiImports;
defined('ABSPATH') or die();

use DateTime;
use DateTimeZone;
use Exception;
use stdClass;

/**
 *
 *  Jens Wiecker PHP Class
 * @package Jens Wiecker WordPress Plugin
 *  Copyright 2021, Jens Wiecker
 *  License: Commercial - goto https://www.hummelt-werbeagentur.de/
 *  https://www.hummelt-werbeagentur.de/
 *
 */

if (!class_exists('HupaFBApiImports')) {
    add_action('plugin_loaded', array('Hupa\\FBApiImports\\HupaFBApiImports', 'import_init'), 0);

    final class HupaFBApiImports
    {
        //INSTANCE
        private static $instance;
        //TABLES
        private string $tbImports = 'fb_api_imports';
        // Variables
        private bool $force_delete = true;
        //Max. Anzahl von Posts
        private $max_count;
        //
        private $fb_img_url;
        //
        private $fbID;
        //
        private $wp_post_id;

        /**
         * @return static
         */
        public static function import_init(): self
        {
            if (is_null(self::$instance)) {
                self::$instance = new self;
            }
            return self::$instance;
        }

        /**
         * HupaFBApiImports constructor.
         */
        public function __construct()
        {
            //getImports
            add_filter('get_fb_api_imports', array($this, 'hupa_get_fb_api_imports'));
            //setImports
            add_filter('set_fb_abi_imports', array($this, 'hupa_set_fb_abi_imports'));
            //updateSingleInputs
            add_filter('update_fb_abi_inputs', array($this, 'hupa_update_fb_api_inputs'));
            //Delete Import
            add_filter('delete_fb_api_inputs', array($this, 'hupa_delete_fb_api_inputs'));
            //UPDATE IMPORT
            add_filter('update_fb_api_import', array($this, 'hupa_update_fb_api_import'));
            //SYNC FACEBOOK POST IMPORT
            add_filter('fb_api_plugin_posts_sync', array($this, 'hupa_fb_api_plugin_posts_sync'));
            //SYNC FACEBOOK EVENTS IMPORT
            add_filter('fb_api_plugin_events_sync', array($this, 'hupa_fb_api_plugin_events_sync'));
            //RESET SYNC TIMESTAMP
            add_filter('reset_fb_api_import_sync', array($this, 'update_fb_api_import_sync'), 10, 2);
            //DELETE FACEBOOK EVENTS AND POSTS BY IMPORT ID
            add_filter('delete_fb_api_facebook_posts', array($this, 'hupa_delete_facebook_posts'));
        }


        /**
         * @param int $id
         */
        public function hupa_delete_facebook_posts(int $id):void
        {
            $posts = get_posts(array(
                'post_type' => 'facebook_posts',
                'numberposts' => -1,
                'orderby' => 'date',
                'order' => 'DESC',
                'meta_query' => array(
                    array(
                        'key' => '_import_id',
                        'value' => $id,
                        'compare' => '==',
                    )
                )
            ));

            foreach ($posts as $post) {
                $import_type = get_post_meta($post->ID, '_import_type', true);
                if($import_type == 'post'){
                    if ($this->force_delete ) {
                        $thumbnail_id = get_post_thumbnail_id($post->ID);
                       // $file =  get_attached_file( $thumbnail_id );
                       // wp_delete_file( $file );
                        wp_delete_attachment($thumbnail_id, true);
                    }
                }
                wp_delete_post($post->ID, true);
            }

            $this->update_fb_api_import_sync('', $id);
        }

        /**
         * @param $args
         * @return object
         */
        public function hupa_get_fb_api_imports($args): object
        {
            $record = new stdClass();
            global $wpdb;
            $table = $wpdb->prefix . $this->tbImports;
            $results = $wpdb->get_results("SELECT * FROM {$table} {$args} ");
            if (!$results) {
                $record->status = false;
                return $record;
            }
            $record->record = $results;
            $record->status = true;
            $record->count = count($results);
            return $record;
        }

        /**
         * @param $record
         * @return object
         */
        public function hupa_set_fb_abi_imports($record): object
        {
            global $wpdb;
            $table = $wpdb->prefix . $this->tbImports;
            $wpdb->insert(
                $table,
                array(
                    'aktiv' => (int)$record->aktiv,
                    'bezeichnung' => (string)$record->import_name,
                    'description' => (string)$record->post_description,
                    'max_count' => (int)$record->import_count,
                    'user_id' => (string)$record->user_id,
                    'user_aktiv' => (int)$record->check_user_id,
                    'page_id' => (string)$record->page_id,
                    'post_term' => (int)$record->post_term_id,
                    'event_term' => (int)$record->event_term_id
                ),
                array('%d', '%s', '%s', '%d', '%s', '%d', '%s', '%d', '%d')
            );

            $return = new stdClass();
            if ($wpdb->insert_id) {
                $return->status = true;
                $return->id = $wpdb->insert_id;
                $return->msg = sprintf(__('New import "%s" with the ID: %d created!', 'hupa-fb-api'), $record->import_name, $wpdb->insert_id);
                return $return;
            }
            $return->status = false;
            $return->msg = sprintf(__('"%s" could not be saved!', 'hupa-fb-api'), $record->import_named);
            return $return;
        }

        /**
         * @param $record
         */
        public function hupa_update_fb_api_inputs($record): void
        {
            global $wpdb;
            $table = $wpdb->prefix . $this->tbImports;
            $wpdb->update(
                $table,
                array(
                    $record->column => $record->content,
                ),
                array('id' => (int)$record->id),
                array(
                    $record->type
                ),
                array('%d')
            );
        }

        /**
         * @param $record
         */
        public function hupa_update_fb_api_import($record): void
        {
            global $wpdb;
            $table = $wpdb->prefix . $this->tbImports;
            $wpdb->update(
                $table,
                array(
                    'bezeichnung' => (string)$record->import_name,
                    'description' => (string)$record->post_description,
                    'max_count' => (int)$record->import_count,
                    'user_id' => (string)$record->user_id,
                    'user_aktiv' => (int)$record->check_user_id,
                    'page_id' => (string)$record->page_id,
                    'post_term' => (int)$record->post_term_id,
                    'event_term' => (int)$record->event_term_id
                ),
                array('id' => (int)$record->id),
                array(
                    '%s', '%s', '%d', '%s', '%d', '%s', '%d', '%d'
                ),
                array('%d')
            );
        }

        /**
         * @param int $id
         */
        public function hupa_delete_fb_api_inputs(int $id): void
        {
            global $wpdb;
            $table = $wpdb->prefix . $this->tbImports;
            $wpdb->delete(
                $table,
                array(
                    'id' => $id),
                array('%d')
            );
        }

        public function update_fb_api_import_sync($time, $id)
        {
            global $wpdb;
            $table = $wpdb->prefix . $this->tbImports;
            $wpdb->update(
                $table,
                array(
                    'last_sync' => strtotime($time)
                ),
                array('id' => (int)$id),
                array(
                    '%s'
                ),
                array('%d')
            );
        }

        /**
         * @param $id
         * @return object
         * @throws Exception
         */
        public function hupa_fb_api_plugin_posts_sync($id): object
        {
            $return = new stdClass();
            $apiData = new stdClass();
            $maxSync = 20;
            $postIDS = [];
            $retMsg = [];
            //GET Import Settings
            $settings = apply_filters('get_fb_api_plugin_settings', false)->record;
            //DATEN VOM IMPORT
            $args = sprintf('WHERE id=%d', $id);
            $import = $this->hupa_get_fb_api_imports($args);

            if (!$import->status) {
                $return->status = false;
                $retMsg[] = __('Synchronisation error!', 'hupa-fb-api');
                $return->msg = $retMsg;
                return $return;
            }

            $import = $import->record[0];
            $import->user_aktiv ? $apiId = $import->user_id : $apiId = $import->page_id;
            $this->max_count = $import->max_count;
            if (!$apiId) {
                $apiId = 'me';
            }

            $apiData->apiId = $apiId;
            $select = apply_filters('select_max_post_for_sync', false);
            foreach ($select as $tmp) {
                if ($tmp->id == $settings->max_sync) {
                    $maxSync = $tmp->value;
                    break;
                }
            }
            $apiData->limit = $maxSync;
            if (!$import->last_sync) {
                $apiData->limit = $this->max_count;
                $apiData->until = '';
                $apiData->since = '';
            } else {
                $apiData->until = '&until=' . current_time('timestamp');
                $apiData->since = '&since=' . $import->last_sync;
            }

            //TODO JOB WARNING DELETE MAX COUNT
            $this->delete_max_limit_wp_posts($import->max_count, $id);

            if (!$this->max_count) {
                $return->status = false;
                $retMsg[] = __('Number for imports is 0!', 'hupa-fb-api');
                $return->msg = $retMsg;
                return $return;
            }

            $posts = get_posts(array(
                'post_type' => 'facebook_posts',
                'numberposts' => -1,
                'orderby' => 'date',
                'order' => 'DESC'
            ));

            foreach ($posts as $post) {
                $postIDS[] = $post->ID;
            }
            // FACEBOOK API AUFRUF POSTS
            $fbPosts = apply_filters('get_fb_api_get_fb_posts', $apiData);
            //print_r($fbPosts);
            if (!$fbPosts->status) {
                $return->status = false;
                $retMsg[] = $fbPosts->msg;
                $return->msg = $retMsg;
                return $return;
            }
            $i = 1;
            foreach ($fbPosts->record as $tmp) {
                $setPost = $this->hupa_fb_api_plugin_sync($tmp, $import, $i, $postIDS);
                $retMsg[] = $setPost->msg;
                $i++;
            }

            $return->status = true;
            $return->msg = $retMsg;
            return $return;
        }

        /**
         * @param array $apiData
         * @param object $import
         * @param int $count
         * @param array $postIDS
         * @return object
         * @throws Exception
         */
        private function hupa_fb_api_plugin_sync(array $apiData, object $import, int $count, array $postIDS): object
        {
            $return = new stdClass();
            $record = new stdClass();

            filter_var($apiData['full_picture'], FILTER_VALIDATE_URL) ? $this->fb_img_url = $apiData['full_picture'] : $this->fb_img_url = '';
            filter_var($apiData['permalink_url'], FILTER_VALIDATE_URL) ? $record->fb_permalink = $apiData['permalink_url'] : $record->fb_permalink = '';
            isset($apiData['attachments'][0]['unshimmed_url']) ? $record->fb_link = $apiData['attachments'][0]['unshimmed_url'] : $record->fb_link = '';
            isset($apiData['attachments'][0]['target']['id']) ? $record->fb_event_id = $apiData['attachments'][0]['target']['id'] : $record->fb_event_id = '';
            $this->fbID = filter_var($apiData['id'], FILTER_SANITIZE_STRING);

            $record->fb_created = $this->convert_datetime($apiData['created_time']);
            $record->fb_update = $this->convert_datetime($apiData['updated_time']);
            $record->fb_post_id = (int)substr($apiData['id'], strpos($apiData['id'], '_') + 1);
            $record->fb_user_id = $apiData['from']['id'];
            $record->fb_user_name = $apiData['from']['name'];
            $record->fb_post_type = $apiData['attachments'][0]['type'];
            $record->titel = $this->set_wp_post_title(strtotime($record->fb_created), $import->bezeichnung);
            if ($apiData['message']) {
                $content = esc_html($apiData['message']);
                $content = preg_replace("/\s+/", " ", $content);
                $record->excerpt = substr($content, 0, 90) . '...';
            } else {
                $content = '';
            }
            if (!$content) {
                if ($apiData['description']) {
                    $content = esc_html($apiData['description']);
                    $content = preg_replace("/\s+/", " ", $content);
                    $record->titel = substr($content, 0, 24);
                    $record->excerpt = substr($content, 0, 90) . '...';
                } else {
                    $content = '';
                    $record->excerpt = '';
                    $record->titel = 'kein Content';
                }
            }

            $record->content = $content;
            if (!$record->content && !$this->fb_img_url) {
                $return->status = false;
                $return->msg = 'kein Image und Content.  ' . $record->fb_created;
                return $return;
            }

            if (in_array($this->fbID, $postIDS)) {
                $return->status = false;
                $return->msg = 'Post schon vorhanden! ' . $record->fb_created;
                return $return;
            }

            $checkTitle = $this->check_double_post_title($record->titel);
            if($checkTitle){
                $return->status = false;
                $return->msg = 'Post Title schon vorhanden! ' . $record->titel;
                return $return;
            }

            $term = apply_filters('fb_api_get_term_by_term_id', $import->post_term)->term;
            $args = array(
                'import_id' => $this->fbID,
                'post_title' => $record->titel,
                'post_type' => 'facebook_posts',
                'post_content' => $record->content,
                'post_status' => 'publish',
                'comment_status' => 'closed',
                'post_excerpt' => $record->excerpt,
                'post_date' => $record->fb_created,
                'post_author' => get_current_user_id(),
                //'menu_order' => $i,
                'post_category' => array((int)$import->post_term),
                'meta_input' => array(
                    '_fb_from' => $import->bezeichnung,
                    '_import_type' => 'post',
                    '_fb_page_id' => $import->page_id,
                    '_fb_id' => $record->fb_id,
                    '_import_id' => $import->id,
                    '_fb_post_id' => $record->fb_post_id,
                    '_fb_user_id' => $record->fb_user_id,
                    '_fb_img_url' => $this->fb_img_url,
                    '_fb_permalink' => $record->fb_permalink,
                    '_fb_link' => $record->fb_link,
                    '_fb_type' => $record->fb_post_type,
                    '_has_content' => (bool)$record->content,
                    '_has_image' => (bool)$this->fb_img_url,
                )
            );

           $insert = $this->insert_wp_custom_post($args);
            if (!$insert->status) {
                $return->status = false;
                $return->msg = $insert->msg;
                return $return;
            }

            if ($count === 1) {
            	//TODO JOB UPDATE DATUM DB
              //  $this->update_fb_api_import_sync($record->fb_created, $import->id);
            }

            //TODO Kategorie für neuen Beitrag setzen
            wp_set_object_terms($this->wp_post_id, array($term->term_id), $term->taxonomy);


            $return->status = true;
            $return->msg = __('Synchronisation successful', 'hupa-fb-api');
            return $return;
        }

        /**
         * @param array $args
         * @return object
         */
        private function insert_wp_custom_post(array $args): object
        {
            $return = new stdClass();
            $this->wp_post_id = wp_insert_post($args, true);
            if (is_wp_error($return->wp_post_id)) {
                $return->status = false;
                $return->msg = $this->wp_post_id->get_error_message();
                return $return;
            }

            //TODO IMAGE SPEICHERN UND BEITRAGSBILD ERSTELLEN
            if ($this->fb_img_url) {
                $this->set_fb_image_to_post();
            }

            $return->status = true;
            return $return;
        }

	    /**
	     * @param $maxPosts
	     * @param $id
	     */
        private function delete_max_limit_wp_posts($maxPosts, $id): void
        {
            $posts = get_posts(array(
                'post_type' => 'facebook_posts',
                'numberposts' => -1,
                'orderby' => 'date',
                'order' => 'DESC',
                'meta_query' => array(
                    array(
                        'key' => '_import_id',
                        'value' => $id,
                        'compare' => '==',
                    )
                )
            ));
            $i = 0;
            foreach ($posts as $post) {
                if ($i >= $maxPosts) {
                    if ($this->force_delete && $thumbnail_id = get_post_thumbnail_id($post->ID)) {
                        wp_delete_attachment($thumbnail_id, true);
                    }
                    wp_delete_post($post->ID, true);
                }
                $i++;
            }
        }

        private function check_double_post_title($title){

            $args = array("post_type" => "facebook_posts", "s" => $title);
            $query = get_posts( $args );
            foreach ($query as $tmp){
                if($tmp->post_title == $title){
                    return true;
                }
            }
        }

        private function set_fb_image_to_post(): void
        {
            $url_parts = parse_url($this->fb_img_url);
            $extension = pathinfo($url_parts['path'], PATHINFO_EXTENSION);
            $extension = $extension ?: 'jpg';
            $wp_upload_dir = wp_upload_dir();
            $filename = $wp_upload_dir['path'] . '/' . $this->wp_post_id . '.' . $extension;
            if (copy($this->fb_img_url, $filename)) {
                $wp_filetype = wp_check_filetype(basename($filename), null);
                $attachment = array(
                    'guid' => $wp_upload_dir['url'] . '/' . basename($filename),
                    'post_mime_type' => $wp_filetype['type'],
                    'post_title' => preg_replace('/\.[^.]+$/', '', basename($filename)),
                    'post_content' => '',
                    'post_status' => 'inherit'
                );
                $attach_id = wp_insert_attachment($attachment, $filename, $this->wp_post_id);
                require_once(ABSPATH . 'wp-admin/includes/image.php');
                $attach_data = wp_generate_attachment_metadata($attach_id, $filename);
                wp_update_attachment_metadata($attach_id, $attach_data);
                set_post_thumbnail($this->wp_post_id, $attach_id);
            }
        }

        /**
         * ================================ EVENTS ===================================
         */

        /**
         * @param $id
         * @return object
         * @throws Exception
         */
        public function hupa_fb_api_plugin_events_sync($id): object
        {
            $return = new stdClass();
            $apiData = new stdClass();
            $postIDS = [];
            $retMsg = [];

            //DATEN VOM IMPORT
            $args = sprintf('WHERE id=%d', $id);
            $import = $this->hupa_get_fb_api_imports($args);

            if (!$import->status) {
                $return->status = false;
                $retMsg[] = __('Synchronisation error!', 'hupa-fb-api');
                $return->msg = $retMsg;
                return $return;
            }

            $import = $import->record[0];

            $apiId = $import->page_id;
            if (!$apiId) {
                $apiId = 'me';
            }

            $apiData->limit = 10;
            $apiData->apiId = $apiId;
            $this->delete_max_limit_wp_events($import->max_count, $id);

            if (!$import->max_count) {
                $return->status = false;
                $retMsg[] = __('Number for imports is 0!', 'hupa-fb-api');
                $return->msg = $retMsg;
                return $return;
            }

            $posts = get_posts(array(
                'post_type' => 'facebook_posts',
                'numberposts' => -1,
                'orderby' => 'date',
                'order' => 'DESC',
                'meta_query' => array(
                    array(
                        'key' => '_import_type',
                        'value' => 'event',
                        'compare' => '==',
                    )
                )
            ));
            foreach ($posts as $post) {
                $postIDS[] = $post->ID;
            }
            // FACEBOOK API AUFRUF EVENTS
            $fbPosts = apply_filters('get_fp_api_get_events', $apiData);
            if (!$fbPosts->status) {
                $return->status = false;
                $retMsg[] = $fbPosts->msg;
                $return->msg = $retMsg;
                return $return;
            }
            foreach ($fbPosts->record as $tmp) {
                $setPost = $this->hupa_fb_api_plugin_event_sync($tmp, $import, $id, $postIDS);
                $retMsg[] = $setPost->msg;
            }
            $return->status = true;
            $return->msg = $retMsg;
            //print_r($return);
            return $return;
        }

        /**
         * @param $apiData
         * @param $import
         * @param $id
         * @param $postIDS
         * @return stdClass
         * @throws Exception
         */
        private function hupa_fb_api_plugin_event_sync($apiData, $import, $id, $postIDS): stdClass
        {
            $return = new stdClass();
            $record = new stdClass();
            $record->eventId = $apiData['id'];
            $record->type = $apiData['type'];
            $record->is_canceled = $apiData['is_canceled'];
            $record->is_draft = $apiData['is_canceled'];
            $record->name = $apiData['name'];
            if ($apiData['place']) {
                $record->place = serialize($apiData['place']);
            } else {
                $record->place = false;
            }

            $record->start_time = $this->convert_event_time($apiData['start_time']);
            $record->end_time = $this->convert_event_time($apiData['end_time']);
            $record->post_modified = $this->convert_datetime($apiData['updated_time']);
            $record->description = $apiData['description'];
            $endString = strtotime($record->end_time);
            $now = current_time('timestamp');
            if ($endString - $now < 0) {
                wp_delete_post($record->eventId, true);
                $return->status = false;
                $return->msg = 'Veranstaltung abgelaufen!';
                return $return;
            }

            if (in_array($record->eventId, $postIDS)) {
                $this->update_wp_custom_event_post($record);
                $return->status = false;
                $return->msg = 'Veranstaltung schon vorhanden!';
                return $return;
            }

            $term = apply_filters('fb_api_get_term_by_term_id', $import->event_term)->term;
            $args = array(
                'import_id' => $record->eventId,
                'post_title' => $this->set_wp_event_title(strtotime($record->start_time), $import->bezeichnung),
                'post_type' => 'facebook_posts',
                'post_content' => $record->description,
                'post_status' => 'publish',
                'comment_status' => 'closed',
                'post_excerpt' => $record->name,
                'post_date' => current_time('mysql'),
                'post_author' => get_current_user_id(),
                'post_category' => array((int)$import->post_term),
                'meta_input' => array(
                    '_import_id' => $id,
                    '_import_type' => 'event',
                    '_fb_page_id' => $import->page_id,
                    '_fb_type' => $record->type,
                    '_event_order' => strtotime($record->start_time),
                    '_fb_event_id' => $record->eventId,
                    '_fb_event_link' => 'https://www.facebook.com/events/' . $record->eventId . '/',
                    '_fb_event_name' => $record->name,
                    '_fb_start_time' => $record->start_time,
                    '_fb_end_time' => $record->end_time,
                    '_fb_place' => $record->place,
                    '_fb_has_place' => (bool)$record->place,
                )
            );

            $insert = $this->insert_wp_custom_event_post($args);
            if (!$insert->status) {
                $return->status = false;
                $return->msg = $insert->msg;
                return $return;
            }

            //TODO Kategorie für neuen Beitrag setzen
            wp_set_object_terms($this->wp_post_id, array($term->term_id), $term->taxonomy);

            $return->status = true;
            $return->msg = __('Synchronisation successful', 'hupa-fb-api');
            return $return;
        }

        /**
         * @param $args
         * @return object
         */
        private function insert_wp_custom_event_post($args): object
        {
            $return = new stdClass();
            (int)$this->wp_post_id = wp_insert_post($args, true);
            if (is_wp_error($this->wp_post_id)) {
                $return->status = false;
                $return->msg = $this->wp_post_id->get_error_message();
                return $return;
            }
            $return->status = true;
            return $return;
        }

        /**
         * @param int $maxPosts
         * @param $id
         */
        private function delete_max_limit_wp_events(int $maxPosts, $id): void
        {
            $posts = get_posts(array(
                'post_type' => 'facebook_posts',
                'numberposts' => -1,
                'orderby' => 'date',
                'order' => 'DESC',
                'meta_query' => array(
                    array(
                        'key' => '_import_id',
                        'value' => $id,
                        'compare' => '==',
                    )
                )
            ));

            $i = 0;
            foreach ($posts as $post) {
                if ($i >= $maxPosts) {
                    wp_delete_post($post->ID, true);
                }
                $i++;
            }
        }

        /**
         * @param $record
         */
        private function update_wp_custom_post_type($record): void
        {
            $args = array(
                'ID' => $this->fbID,
                'post_content' => $record->content,
                'post_excerpt' => $record->excerpt,
            );
            wp_update_post($args, false);
        }


        /**
         * @param $record
         */
        private function update_wp_custom_event_post($record): void
        {
            $args = array(
                'ID' => $record->eventId,
                'post_type' => 'facebook_posts',
                'post_content' => $record->description,
                'post_status' => 'publish',
                'comment_status' => 'closed',
                'post_excerpt' => $record->name,
                'post_date' => $record->post_modified,
                'meta_input' => array(
                    '_fb_type' => $record->type,
                    '_fb_event_id' => $record->eventId,
                    '_fb_event_link' => 'https://www.facebook.com/events/' . $record->eventId . '/',
                    '_fb_event_name' => $record->name,
                    '_event_order' => strtotime($record->start_time),
                    '_fb_start_time' => $record->start_time,
                    '_fb_end_time' => $record->end_time,
                    '_fb_place' => $record->place,
                    '_fb_has_place' => (bool)$record->place,
                )
            );
            wp_update_post($args, false);
        }


        /**
         * @param DateTime $datetime
         * @return string
         * @throws Exception
         */
        private function convert_datetime(DateTime $datetime): string
        {
            get_option('timezone_string') ? $timezone = get_option('timezone_string') : $timezone = 'Europe/Berlin';
            $date = new DateTime($datetime->format('Y-m-d H:i:s'));
            $date->setTimeZone(new DateTimeZone($timezone));
            return $date->format('Y-m-d H:i:s');
        }

	    /**
	     * @throws Exception
	     */
	    private function convert_event_time(DateTime $datetime): string
	    {
		    $date = new DateTime($datetime->format('Y-m-d H:i:s'));
		    return $date->format('Y-m-d H:i:s');
	    }

        /**
         * @param int $timestamp
         * @param string $bezeichnung
         * @return string
         */
        private function set_wp_post_title(int $timestamp, string $bezeichnung): string
        {
            return $bezeichnung . ' - ' . date('d.m.Y', $timestamp) . ' um ' . date('H:i', $timestamp) . ' Uhr';
        }


        /**
         * @param int $timestamp
         * @param string $bezeichnung
         * @return string
         */
        private function set_wp_event_title(int $timestamp, string $bezeichnung): string
        {
            return $bezeichnung . ' ' . __('Event', 'hupa-fb-api') . ' - ' . date('d.m.Y', $timestamp) . ' um ' . date('H:i', $timestamp) . ' Uhr';
        }


    }

}//endClass
