<?php

namespace Hupa\PluginLicense;

use Exception;
use stdClass;

defined('ABSPATH') or die();

/**
 * @package Hummelt & Partner WordPress Theme
 * Copyright 2021, Jens Wiecker
 * License: Commercial - goto https://www.hummelt-werbeagentur.de/
 * https://www.hummelt-werbeagentur.de/
 */

if (!class_exists('HupaApiFbApiPluginServerHandle')) {
    add_action('plugin_loaded', array('Hupa\\PluginLicense\\HupaApiFbApiPluginServerHandle', 'init'), 0);

    class HupaApiFbApiPluginServerHandle
    {
        private static $api_filter_instance;
        private string $hupa_server_url;

        /**
         * @return static
         */
        public static function init(): self
        {
            if (is_null(self::$api_filter_instance)) {
                self::$api_filter_instance = new self;
            }
            return self::$api_filter_instance;
        }

        public function __construct()
        {

            $this->hupa_server_url = get_option('hupa_server_url');

            //TODO Endpoints URL's
            add_filter('get_fb_api_urls', array($this, 'FbApiGetApiUrl'));
            //TODO JOB POST Resources Endpoints
            add_filter('fb_api_scope_resource', array($this, 'hupaFbApiPOSTApiResource'), 10, 2);
            //TODO JOB GET Resources Endpoints
            add_filter('get_fb_api_scope_resource', array($this, 'FbApiGETApiResource'), 10, 2);

            //TODO JOB VALIDATE SOURCE BY Authorization Code
            add_filter('get_fb_api_resource_authorization_code', array($this, 'FbApiInstallByAuthorizationCode'));


            //TODO JOB SERVER URL ÄNDERN FALLS NÖTIG
            add_filter('fb_api_update_server_url', array($this, 'FbApiUpdateServerUrl'));
        }

        public function FbApiUpdateServerUrl($url)
        {
            update_option('hupa_server_url', $url);
        }

        public function FbApiGetApiUrl($scope): string
        {
            $client_id =  get_option('fb_api_client_id');
                return get_option('hupa_server_url') . 'authorize?response_type=code&client_id=' . $client_id;

        }

        public function FbApiInstallByAuthorizationCode($authorization_code): object
        {
            $error = new stdClass();
            $error->status = false;
            $client_id =  get_option('fb_api_client_id');
            $client_secret = get_option('fb_api_client_secret');
            $token_url =$this->hupa_server_url . 'token';
            $authorization = base64_encode("$client_id:$client_secret");

            $args = array(
                'headers' => array(
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'Authorization' => "Basic {$authorization}"
                ),
                'body' => [
                    'grant_type' => "authorization_code",
                    'code' => $authorization_code
                ]
            );

            $response = wp_remote_post($token_url, $args);
            if (is_wp_error($response)) {
                $error->message = $response->get_error_message();
                return $error;
            }

            $apiData = json_decode($response['body']);
            if ($apiData->error) {
                $apiData->status = false;
                return $apiData;
            }

            update_option('fb_api_access_token', $apiData->access_token);
            return $this->hupaFbApiPOSTApiResource('install');
        }

        public function hupaFbApiPOSTApiResource($scope, $body=false)
        {
            $error = new stdClass();
            $error->status = false;
            $response = wp_remote_post($this->hupa_server_url . $scope, $this->FbApiApiPostArgs($body));
            if (is_wp_error($response)) {

                $error->message = $response->get_error_message();
                return $error;
            }

            $apiData = json_decode($response['body']);
            if($apiData->error){
                $errType = $this->get_error_message($apiData);
                if($errType) {
                   $this->FbApiGetApiClientCredentials();
                }
            }

            $response = wp_remote_post($this->hupa_server_url . $scope, $this->FbApiApiPostArgs($body));
            if (is_wp_error($response)) {
                $error->message = $response->get_error_message();
                $error->apicode = $response['code'];
                $error->apimessage = $response['message'];
                return $error;
            }
            $apiData = json_decode($response['body']);
            if(!$apiData->error){
                $apiData->status = true;
                return $apiData;
            }

            $error->error = $apiData->error;
            $error->error_description = $apiData->error_description;
            return $error;
        }

        public function FbApiGETApiResource($scope, $get = []) {

            $error = new stdClass();
            $error->status = false;

            $getUrl = '';
            if($get){
                $getUrl = implode('&', $get);
                $getUrl = '?' . $getUrl;
            }

            $url = $this->hupa_server_url . $scope . $getUrl;
            $args = $this->FbApiGETApiArgs();

            $response = wp_remote_get( $url, $args );
            if (is_wp_error($response)) {
                $error->message = $response->get_error_message();
                return $error;
            }

            $apiData = json_decode($response['body']);
            if($apiData->error){
                $errType = $this->get_error_message($apiData);
                if($errType) {
                    $this->FbApiGetApiClientCredentials();
                }
            }

            $response = wp_remote_get( $this->hupa_server_url, $this->FbApiGETApiArgs() );
            if (is_wp_error($response)) {
                $error->message = $response->get_error_message();
                return $error;
            }
            $apiData = json_decode($response['body']);
            if(!$apiData->error){
                $apiData->status = true;
                return $apiData;
            }

            $error->error = $apiData->error;
            $error->error_description = $apiData->error_description;
            return $error;
        }

        public function FbApiApiPostArgs($body = []):array
        {

            $bearerToken = get_option('fb_api_access_token');
            return [
                'method'        => 'POST',
                'timeout'       => 45,
                'redirection'   => 5,
                'httpversion'   => '1.0',
                'blocking'      => true,
                'sslverify'     => true,
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'Authorization' => "Bearer $bearerToken"
                ],
                'body'          => $body

            ];
        }

        private function FbApiGETApiArgs():array
        {
            $bearerToken = get_option('fb_api_access_token');
            return  [
                'method' => 'GET',
                'timeout' => 45,
                'redirection' => 5,
                'httpversion' => '1.0',
                'sslverify' => true,
                'blocking' => true,
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'Authorization' => "Bearer $bearerToken"
                ],
                'body'          => []
            ];
        }

        private function FbApiGetApiClientCredentials():void
        {
            $token_url = $this->hupa_server_url . 'token';
            $client_id =  get_option('fb_api_client_id');
            $client_secret = get_option('fb_api_client_secret');
            $authorization = base64_encode("$client_id:$client_secret");
            $error = new stdClass();
            $error->status = false;
            $args = [
                'method' => 'POST',
                'timeout' => 45,
                'redirection' => 5,
                'httpversion' => '1.0',
                'sslverify' => true,
                'blocking' => true,
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'Authorization' => "Basic $authorization"
                ],
                'body' => [
                    'grant_type' => 'client_credentials'
                ]
            ];

            $response = wp_remote_post($token_url, $args);
            if (!is_wp_error($response)) {
                $apiData = json_decode($response['body']);
                update_option('fb_api_access_token', $apiData->access_token);
            }
        }

        private function get_error_message($error): bool
        {
            $return = false;
            switch ($error->error) {
                case 'invalid_grant':
                case 'insufficient_scope':
                case 'invalid_request':
                    $return = false;
                    break;
                case'invalid_token':
                    $return = true;
                    break;
            }

            return $return;
        }

    }
}

