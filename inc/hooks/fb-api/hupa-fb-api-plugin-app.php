<?php

namespace Hupa\FBApiPluginAPP;
defined('ABSPATH') or die();
/**
 *  Jens Wiecker PHP Class
 * @package Jens Wiecker WordPress Plugin
 *  Copyright 2021, Jens Wiecker
 *  License: Commercial - goto https://www.hummelt-werbeagentur.de/
 *  https://www.hummelt-werbeagentur.de/
 *
 */

use Facebook\Exceptions\FacebookResponseException;
use Facebook\Exceptions\FacebookSDKException;
use Facebook\Facebook;
use stdClass;


if (!class_exists('HupaFBApiPluginApp')) {
    add_action('plugin_loaded', array('Hupa\\FBApiPluginAPP\\HupaFBApiPluginApp', 'init_App'), 0);

    final class HupaFBApiPluginApp
    {
        //CLASS INSTANCE
        private static $instance;
        //Settings Object
        private object $settings;
        //FB-SDK
        private $fbApp;

        /**
         * @return static
         */
        public static function init_App(): self
        {
            if (is_null(self::$instance)) {
                self::$instance = new self;
            }
            return self::$instance;
        }


        /**
         * HupaFBApiPluginApp constructor.
         */
        public function __construct()
        {
            //FILTER
            add_filter('get_fb_api_get_fb_posts', array($this, 'hupa_fb_api_get_fb_posts'));
            add_filter('get_fp_api_get_events', array($this, 'hupa_fp_api_get_events'));
            add_filter('check_fb_access_token', array($this, 'hupa_check_fb_access_token'));
        }

        /**
         * @param $args
         * @return object
         * @throws FacebookSDKException
         */
        public function hupa_fb_api_get_fb_posts($args): object
        {
            $this->facebookApp();
            $record = new stdClass();
            if (!isset($this->settings->access_token)) {
                $record->status = false;
                $record->msg = __('no access token stored!', 'hupa-fb-api');
                return $record;
            }

            try {
                $response = $this->fbApp->get('/'.$args->apiId.'/feed?fields=is_published, is_expired, is_hidden, media_type, status_type, created_time, updated_time, message, attachments{type, title, description, unshimmed_url, media{source}, target{id} }, id, permalink_url, full_picture, from'.'&limit='.$args->limit.$args->since.$args->until.'', $this->settings->access_token);
                //$response = $this->fbApp->get('/'.$args->apiId.'/feed?fields=is_published, is_expired, is_hidden, media_type, status_type, created_time, updated_time, message, attachments{type, title, description, unshimmed_url, media{source}, target{id} }, id, permalink_url, full_picture, from&since='.$args->since.'&until='.$args->until.'', $this->settings->access_token);
                $getGraphEdge = $response->getGraphEdge()->asArray();

            } catch (FacebookResponseException $e) {
                $record->status = false;
                $record->msg = __('Facebook Graph error: ', 'hupa-fb-api') . $e->getMessage();
                return $record;

            } catch (FacebookSDKException $e) {
                $record->status = false;
                $record->msg = __('Facebook SDK error: ', 'hupa-fb-api') . $e->getMessage();
                return $record;
            }

            if (!$getGraphEdge) {
                $record->status = false;
                $record->msg = __('no new data found!', 'hupa-fb-api');
                return $record;
            }

            $record->status = true;
            $record->record = $getGraphEdge;
            return $record;
        }


	    /**
	     * @param $args
	     *
	     * @return object
	     * @throws FacebookSDKException
	     */
        public function hupa_fp_api_get_events($args): object
        {
            $this->facebookApp();
            $record = new stdClass();
            if (!isset($this->settings->access_token)) {
                $record->status = false;
                $record->msg = __('no access token stored!', 'hupa-fb-api');
                return $record;
            }

            try {
                $response = $this->fbApp->get('/'.$args->apiId.'/events/?fields=description,end_time,event_times,is_canceled,is_draft,name,place,start_time,type,updated_time&limit='.$args->limit.'', $this->settings->access_token);
               // $response = $this->fbApp->get('/'.$args->apiId.'/events?limit='.$args->max.'', $this->settings->access_token);
                $getGraphEdge = $response->getGraphEdge()->asArray();

            } catch (FacebookResponseException $e) {
                $record->status = false;
                $record->msg = __('Facebook Graph error: ', 'hupa-fb-api') . $e->getMessage();
                return $record;

            } catch (FacebookSDKException $e) {
                $record->status = false;
                $record->msg = __('Facebook SDK error: ', 'hupa-fb-api') . $e->getMessage();
                return $record;
            }

            if (!$getGraphEdge) {
                $record->status = false;
                $record->msg = __('no data found!', 'hupa-fb-api');
                return $record;
            }

            $record->status = true;
            $record->record = $getGraphEdge;
            return $record;

        }

        /**
         * @param $args
         * @return object
         * @throws FacebookSDKException
         */
        public function hupa_check_fb_access_token($args): object
        {
            $this->facebookApp();
            $record = new stdClass();
            if (!isset($this->settings->access_token)) {
                $record->status = false;
                $record->msg = __('no access token stored!', 'hupa-fb-api');
                return $record;
            }

            try {
                $response = $this->fbApp->get('/me', $this->settings->access_token);
            } catch (FacebookResponseException $e) {
                $record->status = false;
                $record->msg = __('Facebook Graph error: ', 'hupa-fb-api') . $e->getMessage();
                return $record;
            } catch (FacebookSDKException $e) {
                $record->status = false;
                $record->msg = __('Facebook SDK error: ', 'hupa-fb-api') . $e->getMessage();
                return $record;
            }

            try {
                $me = $response->getGraphUser();
            } catch (FacebookSDKException $e) {
                $record->status = false;
                $record->msg = __('Facebook SDK error: ', 'hupa-fb-api') . $e->getMessage();
                return $record;
            }

            $record->status = true;
            $record->msg = __('Logged in as ', 'hupa-fb-api') . $me->getName();
            return $record;
        }

        /**
         * @throws FacebookSDKException
         */
        private function facebookApp(): void
        {
            $args = sprintf('WHERE id=%d', HUPA_PLUGIN_SETTINGS_ID);
            $this->settings = apply_filters('get_fb_api_plugin_settings', $args)->record;
            //SET FB-SDK
            $this->fbApp = new Facebook([
                'app_id' => $this->settings->app_id,
                'app_secret' => $this->settings->app_secret,
                'default_graph_version' => 'v11.0'
            ]);
        }
    }
}//endClass
