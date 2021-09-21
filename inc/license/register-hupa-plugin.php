<?php

namespace Hupa\FbApiLicense;

defined('ABSPATH') or die();

/**
 * REGISTER HUPA CUSTOM THEME
 * @package Hummelt & Partner WordPress Theme
 * Copyright 2021, Jens Wiecker
 * License: Commercial - goto https://www.hummelt-werbeagentur.de/
 * https://www.hummelt-werbeagentur.de/
 */
final class RegisterHupaFbApi
{
    private static $hupa_fb_api_instance;
    private string $plugin_dir;

    /**
     * @return static
     */
    public static function hupa_fb_api_instance(): self
    {
        if (is_null(self::$hupa_fb_api_instance)) {
            self::$hupa_fb_api_instance = new self();
        }
        return self::$hupa_fb_api_instance;
    }

    public function __construct()
    {
        $file_path_from_plugin_root = str_replace(WP_PLUGIN_DIR . '/', '', __DIR__);
        $path_array = explode('/', $file_path_from_plugin_root);
        $plugin_folder_name = reset($path_array);
        $this->plugin_dir = $plugin_folder_name;
    }

    public function init_fb_api(): void
    {

        // TODO REGISTER LICENSE MENU
        if (!get_option('fb_api_product_install_authorize')) {
            add_action('admin_menu', array($this, 'register_license_fb_api_plugin'));
        }
        add_action('wp_ajax_FbApiLicenceHandle', array($this, 'prefix_ajax_FbApiLicenceHandle'));
        add_action('init', array($this, 'fb_api_license_site_trigger_check'));
        add_action('template_redirect', array($this, 'fb_api_license_callback_trigger_check'));

        add_action( 'wp_print_scripts',array($this, 'fb_api_list_scripts' ));
        add_action( 'wp_print_styles',array($this, 'fb_api_list_styles' ));
    }

    /**
     * =================================================
     * =========== REGISTER THEME ADMIN MENU ===========
     * =================================================
     */

    public function register_license_fb_api_plugin(): void
    {
        $hook_suffix = add_menu_page(
            __('FB-Api Lizenz', 'wp-post-selector'),
            __('FB-Api Lizenz', 'wp-post-selector'),
            'manage_options',
            'fb-api-license',
            array($this, 'hupa_fb_api_license'),
            'dashicons-lock', 2
        );
        add_action('load-' . $hook_suffix, array($this, 'fb_api_load_ajax_admin_options_script'));
    }


    public function hupa_fb_api_license(): void
    {
        require 'activate-post-selector-page.php';
    }


    /**
     * =========================================
     * =========== ADMIN AJAX HANDLE ===========
     * =========================================
     */

    public function fb_api_load_ajax_admin_options_script(): void
    {
        add_action('admin_enqueue_scripts', array($this, 'load_fb_api_admin_style'));
        $title_nonce = wp_create_nonce('fb_api_license_handle');
        wp_register_script('fb-api-ajax-script', '', [], '', true);
        wp_enqueue_script('fb-api-ajax-script');
        wp_localize_script('fb-api-ajax-script', 'fb_api_license_obj', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => $title_nonce
        ));
    }

    /**
     * ==================================================
     * =========== THEME AJAX RESPONSE HANDLE ===========
     * ==================================================
     */

    public function prefix_ajax_FbApiLicenceHandle(): void
    {
        $responseJson = null;
        check_ajax_referer('fb_api_license_handle');
        require 'post-selector-license-ajax.php';
        wp_send_json($responseJson);
    }

    /*===============================================
       TODO GENERATE CUSTOM SITES
    =================================================
    */
    public function fb_api_license_site_trigger_check(): void
    {
        global $wp;
        $wp->add_query_var($this->plugin_dir);
    }

    function fb_api_license_callback_trigger_check(): void
    {
        $file_path_from_plugin_root = str_replace(WP_PLUGIN_DIR . '/', '', __DIR__);
        $path_array = explode('/', $file_path_from_plugin_root);
        $plugin_folder_name = reset($path_array);
        //$requestUri = base64_encode($plugin_folder_name);
        if (get_query_var($this->plugin_dir) === $this->plugin_dir) {
            require 'api-request-page.php';
            exit;
        }
    }

    public function fb_api_list_scripts() {
        global $wp_scripts;
        $enqueued_scripts = array();
        foreach( $wp_scripts->queue as $handle ) {
            $enqueued_scripts[] = $wp_scripts->registered[$handle]->src;
        }
    }

    function fb_api_list_styles() {
        global $wp_styles;
        $enqueued_styles = array();
        foreach( $wp_styles->queue as $handle ) {
            $enqueued_styles[] = $wp_styles->registered[$handle]->src;
        }
    }

    /**
     * ====================================================
     * =========== THEME ADMIN DASHBOARD STYLES ===========
     * ====================================================
     */

    public function load_fb_api_admin_style(): void
    {
        wp_enqueue_style('post-selector-license-style', plugins_url('hupa-fb-api') . '/inc/license/assets/license-backend.css', array(), '');
        wp_enqueue_script('js-post-selector-license', plugins_url('hupa-fb-api') . '/inc/license/license-script.js', array(), '', true);
    }
}

$hupa_register_post_selector = RegisterHupaFbApi::hupa_fb_api_instance();
if (!empty($hupa_register_post_selector)) {
    $hupa_register_post_selector->init_fb_api();
}
require 'hupa_client_api_wp_remote.php';