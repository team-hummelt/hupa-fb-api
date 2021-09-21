<?php

namespace Hupa\FBApiPlugin;

defined('ABSPATH') or die();

/**
 * Jens Wiecker PHP Class
 * @package Jens Wiecker WordPress Plugin
 * Copyright 2021, Jens Wiecker
 * goto https://www.hummelt-werbeagentur.de/
 * https://www.hummelt-werbeagentur.de/
 *
 */


final class Hupa_Register_FB_Plugin
{
    private static $fb_api_instance;
    private $FBApiDependencies;

    /**
     * Hupa_Register_FB_Plugin constructor.
     */
    public function __construct()
    {
        $this->FBApiDependencies = $this->check_fb_api_plugin_dependencies();
    }

    /**
     * @return static
     */
    public static function fb_api_instance(): self
    {
        if (is_null((self::$fb_api_instance))) {
            self::$fb_api_instance = new self();
        }
        return self::$fb_api_instance;
    }

    public function init_fb_api_plugin(): void
    {
        if (!$this->FBApiDependencies) {
            return;
        }

        register_activation_hook(__FILE__, array($this, 'fp_api_activate'));

        add_action('init', array($this, 'load_plugin_fb_api_textdomain'));
        add_action('admin_menu', array($this, 'register_hupa_fb_api_plugin_admin_menu'));

        //FB CUSTOM POST-TYPE
        add_action('init', array($this, 'hupa_register_facebook_post'));
        add_action('init', array($this, 'hupa_register_facebook_taxonomies'));

        //PUBLIC SITES TRIGGER
        add_action('template_redirect', array($this, 'hupa_fb_api_public_one_trigger_check'));

        //CUSTOM SITES
        add_action('init', array($this, 'hupa_fb_api_public_site_trigger_check'));

        //AJAX HANDLE
        add_action('wp_ajax_HupaApiHandle', array($this, 'prefix_ajax_HupaApiHandle'));
        add_action('wp_ajax_nopriv_HupaApiNoAdmin', array($this, 'prefix_ajax_HupaApiNoAdmin'));
        add_action('wp_ajax_HupaApiNoAdmin', array($this, 'prefix_ajax_HupaApiNoAdmin'));

        //CREATE / UPDATE DATABASE
        add_action('init', array($this, 'hupa_fb_api_update_db'));
        add_action('plugins_loaded', array($this, 'hupa_fb_api_update_database_columns'));
    }


    public function fp_api_activate(): void
    {
        $this->hupa_fb_api_create_db();
        $this->hupa_register_facebook_post();
        $this->hupa_register_facebook_taxonomies();
        flush_rewrite_rules();
    }

    public function load_plugin_fb_api_textdomain(): void
    {
        load_plugin_textdomain('hupa-fb-api', false, dirname(HUPA_API_SLUG_PATH) . '/language/');
    }

    public function register_hupa_fb_api_plugin_admin_menu(): void
    {
        //startseite
        $hook_suffix = add_menu_page(
            __('FB-Importer', 'hupa-fb-api'),
            __('FB-Importer', 'hupa-fb-api'),
            'manage_options',
            'fb-api-home',
            array($this, 'hupa_fb_api_plugin_home'),
            'dashicons-facebook', 2
        );
        add_action('load-' . $hook_suffix, array($this, 'fb_api_load_ajax_plugin_admin_optioned_script'));

        $hook_suffix = add_submenu_page(
            'fb-api-home',
            __('FB pages', 'hupa-fb-api'),
            __('FB pages', 'hupa-fb-api'),
            'manage_options',
            'fb-api-sites',
            array($this, 'hupa_fb_api_plugin_sites'));

        add_action('load-' . $hook_suffix, array($this, 'fb_api_load_ajax_plugin_admin_optioned_script'));
    }


    /*==================================
     TODO: ADMIN PAGES
    ====================================
    */
    public function hupa_fb_api_plugin_home(): void
    {
        require HUPA_ADMIN_PAGE_DIR . 'hupa-api-home.php';
    }

    public function hupa_fb_api_plugin_sites(): void
    {
        require HUPA_ADMIN_PAGE_DIR . 'hupa-api-sites.php';
    }

    /*======================================
    TODO LOAD PUBLIC SITES
    ========================================
    */
    public function hupa_fb_api_public_one_trigger_check()
    {
        //AJAX HANDLE
        wp_enqueue_script(
            'ajax-script',
            HUPA_PLUGIN_URL . '/assets/js/ajax-public.js',
            array('jquery')
        );
        $title_nonce = wp_create_nonce('hupa_public_handle');
        wp_localize_script('ajax-script', 'hupa_ajax_obj', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => $title_nonce,
        ));
    }

    /*======================================
    TODO LOAD JAVASCRIPT
    ========================================
    */

    public function fb_api_load_ajax_plugin_admin_optioned_script(): void
    {
        wp_enqueue_script(
            'ajax-script',
            HUPA_PLUGIN_URL . '/assets/admin/js/admin-backend.js',
            array('jquery')
        );

        add_action('admin_enqueue_scripts', array($this, 'load_hupa_fb_api_admin_style'));
        $title_nonce = wp_create_nonce('hupa_plugin_handle');
        wp_localize_script('ajax-script', 'hupa_ajax_obj', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => $title_nonce));
    }

    /*===============================================
    TODO AJAX PLUGIN ADMIN HANDLE
    =================================================
    */
    public function prefix_ajax_HupaApiHandle(): void
    {
        $responseJson = null;
        check_ajax_referer('hupa_plugin_handle');
        require HUPA_AJAX_DIR . 'backend-ajax.php';
        wp_send_json($responseJson);
    }

    /*===============================================
    TODO AJAX PLUGIN PUBLIC HANDLE
    =================================================
    */
    public function prefix_ajax_HupaApiNoAdmin(): void
    {
        $responseJson = null;
        check_ajax_referer('hupa_public_handle');
        require_once HUPA_AJAX_DIR . 'public-ajax.php';
        wp_send_json($responseJson);
    }

    /*===============================================
    TODO GENERATE CUSTOM SITES
    =================================================
    */
    public function hupa_fb_api_public_site_trigger_check(): void
    {
        global $wp;
        $wp->add_query_var(HUPA_FB_PLUGIN_CRONJOB_SLUG);
        add_action('template_redirect', 'Hupa\\FBApiPlugin\\hupa_fb_api_template_fb_callback_trigger_check');
        function hupa_fb_api_template_fb_callback_trigger_check(): void
        {
            if (get_query_var(HUPA_FB_PLUGIN_CRONJOB_SLUG) === strtoupper(md5(HUPA_FB_PLUGIN_CRONJOB_URL))) {
                require HUPA_PLUGIN_DIR . '/pages-public/hupa-fb-api-sync.php';
                //add_action('fb_api_plugin_sync', 'HupaFBApiPlugin_synchronisation_exec');
                exit;
            }
        }
    }

    /*===============================================
    TODO FACEBOOK CUSTOM POST TYPE
    =================================================
    */
    public function hupa_register_facebook_post(): void
    {
        register_post_type(
            'facebook_posts',
            array(
                'labels' => array(
                    'name' => __('Facebook Posts', 'hupa-fb-api'),
                    'singular_name' => __('Facebook Posts', 'hupa-fb-api'),
                    'edit_item' => __('Edit Facebook Post', 'hupa-fb-api'),
                    'items_list_navigation' => __('Facebook Posts navigation', 'hupa-fb-api'),
                    'add_new_item' => __('Add new post', 'hupa-fb-api'),
                    'archives' => __('Facebook Posts Archives', 'hupa-fb-api'),
                ),
                'public' => true,
                'publicly_queryable' => true,
                'show_in_rest' => true,
                'show_ui' => true,
                'show_in_menu' => true,
                'has_archive' => true,
                'show_in_nav_menus' => true,
                'exclude_from_search' => false,
                'hierarchical' => true,
                'menu_icon' => 'dashicons-facebook',
                'menu_position' => 3,
                'supports' => array(
                    'title', 'excerpt', 'page-attributes', 'author', 'editor', 'thumbnail', 'comments'
                ),
                'taxonomies' => array('facebook_category'),
            )
        );
    }

    /*===============================================
    TODO FACEBOOK CUSTOM POST TAXONOMIES
    =================================================
    */
    public function hupa_register_facebook_taxonomies(): void
    {
        $labels = array(
            'name' => __('Facebook Categories', 'hupa-fb-api'),
            'singular_name' => __('Facebook Category', 'hupa-fb-api'),
            'search_items' => __('Search Facebook Categories', 'hupa-fb-api'),
            'all_items' => __('All Facebook Categories', 'hupa-fb-api'),
            'parent_item' => __('Parent Facebook Category', 'hupa-fb-api'),
            'parent_item_colon' => __('Parent Facebook Category:', 'hupa-fb-api'),
            'edit_item' => __('Edit Facebook Category', 'hupa-fb-api'),
            'update_item' => __('Update Facebook Category', 'hupa-fb-api'),
            'add_new_item' => __('Add New Facebook Category', 'hupa-fb-api'),
            'new_item_name' => __('New Facebook Category', 'hupa-fb-api'),
            'menu_name' => __('Facebook Categories', 'hupa-fb-api'),
        );

        $args = array(
            'labels' => $labels,
            'hierarchical' => true,
            'show_ui' => true,
            'sort' => true,
            'show_in_rest' => true,
            'query_var' => true,
            'args' => array('orderby' => 'term_order'),
            'rewrite' => array('slug' => 'facebook_category'),
            'show_admin_column' => true
        );
        register_taxonomy('facebook_category', array('facebook_posts'), $args);

        //Kategorie erstellen (STANDARD CATEGORY)
        if (!term_exists('Facebook Allgemein', 'facebook_category')) {
            wp_insert_term(
                'Facebook Allgemein',
                'facebook_category',
                array(
                    'description' => __('Standard category for posts', 'hupa-fb-api'),
                    'slug' => 'facebook-posts'
                )
            );
        }

        //Kategorie erstellen (STANDARD CATEGORY EVENTS)
        if (!term_exists('Facebook Veranstaltungen', 'facebook_category')) {
            wp_insert_term(
                'Facebook Veranstaltungen',
                'facebook_category',
                array(
                    'description' => __('Standard category for events', 'hupa-fb-api'),
                    'slug' => 'facebook-veranstaltungen'
                )
            );
        }
    }

    /**
     * @return bool
     */
    public function check_fb_api_plugin_dependencies(): bool
    {
        global $wp_version;
        if (version_compare(PHP_VERSION, HUPA_FB_MIN_PHP_VERSION, '<') || $wp_version < HUPA_FB_MIN_WP_VERSION) {
            $this->maybe_self_fb_api_deactivate();
            return false;
        }
        return true;
    }

    /*=======================================
    TODO SELF-DEACTIVATE
    =========================================
    */
    public function maybe_self_fb_api_deactivate(): void
    {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
        deactivate_plugins(HUPA_API_SLUG_PATH);
        add_action('admin_notices', array($this, 'self_deactivate_fb_api_notice'));
    }

    /*==============================================
    TODO DEACTIVATE-ADMIN-NOTIZ
    ================================================
    */
    public function self_deactivate_fb_api_notice(): void
    {
        echo sprintf('<div class="error"><p>' . __('This plugin has been disabled because it requires a PHP version greater than %s and a WordPress version greater than %s. Your PHP version can be updated by your hosting provider.', 'hupa-fb-api') . '</p></div>', HUPA_FB_MIN_PHP_VERSION, HUPA_FB_MIN_WP_VERSION);
    }

    /*==============================================
    TODO CREATE DATABASE
    ===============================================
    */
    public function hupa_fb_api_create_db(): void
    {
        require 'database/fb-api-plugin-database.php';
        do_action('create_fb_api_plugin_database');
    }

    function hupa_fb_api_update_db(): void
    {
        require 'database/fb-api-plugin-database.php';
        do_action('check_fb_api_plugin_database');
    }

    /*==============================================
    TODO ADMIN DASHBOARD PLUGIN STYLES
    ===============================================
    */
    function load_hupa_fb_api_admin_style(): void
    {
        $pageSlug = filter_input(INPUT_GET,'page', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH);
        $plugin_data = get_file_data(plugin_dir_path(__DIR__) . '/hupa-fb-api.php', array('Version' => 'Version'), false);
        global $plugin_version;
        $plugin_version = $plugin_data['Version'];

        //CSS STYLES
        //TODO FontAwesome / Bootstrap
        wp_enqueue_style('hupa-fb-api-bs-style', HUPA_PLUGIN_URL . '/assets/admin/css/bs/bootstrap.min.css', array(), $plugin_version, false);
        wp_enqueue_style('hupa-fb-api-icons-style', HUPA_PLUGIN_URL . '/assets/css/font-awesome.css', array(), $plugin_version, false);

        //TODO DATA TABLE
        if($pageSlug == 'fb-api-sites'){
            wp_enqueue_style('hupa-fb-api-dt-bs5-style', HUPA_PLUGIN_URL . '/assets/admin/css/data-table/datatables-bs5.min..css', array(), $plugin_version, false);

        }

        //TODO PLUGIN BACKEND STYLE
        wp_enqueue_style('hupa-fb-api-plugin-style', HUPA_PLUGIN_URL . '/assets/admin/css/backend-style.css', array(), $plugin_version, false);
        wp_enqueue_style('hupa-fb-api-tools-style', HUPA_PLUGIN_URL . '/assets/css/tools.css', array(), $plugin_version, false);

        //JS FILES
        wp_enqueue_script('jquery');
        // TODO Bootstrap JS
        wp_enqueue_script('hupa-fb-api-bs-js', HUPA_PLUGIN_URL . '/assets/admin/js/bs/bootstrap.bundle.min.js', array(), $plugin_version, true);
        //TODO DATA TABLE
        if($pageSlug == 'fb-api-sites'){
            wp_enqueue_script('hupa-fb-api-dt-js', HUPA_PLUGIN_URL . '/assets/admin/js/data-table/jquery.dataTables.min.js', array(), $plugin_version, true);
            wp_enqueue_script('hupa-fb-api-dt-bs5-js', HUPA_PLUGIN_URL . '/assets/admin/js/data-table/dataTables.bootstrap5.min.js', array(), $plugin_version, true);

            //TODO IMPORTS DATA TABLE
            wp_enqueue_script('hupa-fb-api-dt-imports-js', HUPA_PLUGIN_URL . '/assets/admin/js/imports-data-table.js', array(), $plugin_version, true);
        }

        // TODO localize Script
        wp_register_script('hupa-fb-api-js-localize', '', [], '', true);
        wp_enqueue_script('hupa-fb-api-js-localize');
        wp_localize_script('hupa-fb-api-js-localize', 'hupa_fb_api', array(
                'plugin_url' => HUPA_PLUGIN_URL,
                'data_table' => HUPA_PLUGIN_URL . '/assets/admin/json/DataTablesGerman.json',
                'admin_url' => admin_url(),
                'site_url' => get_bloginfo('url'),
                'lang_modal' => apply_filters('fb_api_language', false)->modal
            )
        );
    }


    /*===============================================
    TODO UPDATE DATABASE BY CHANGE DB-VERSION
    =================================================
    */
    public function hupa_fb_api_update_database_columns()
    {
        global $wpdb;
        switch (HUPA_FB_PLUGIN_DB_VERSION) {
            case'10.0.4':
                //ADD
                //$table = $wpdb->prefix . 'lva_settings';
                //$wpdb->query("ALTER TABLE {$table} ADD form_plugin_smtp int(1) NOT NULL DEFAULT 1");
                // $wpdb->query("ALTER TABLE {$table} ADD form_email_typ int(1) NOT NULL DEFAULT 0");
                // $wpdb->query("ALTER TABLE {$table} ADD form_smtp_settings text NULL");
                break;
            case'5.0.1':
                //DELETE
                //$table = $wpdb->prefix . 'lva_templates';
                //$wpdb->query( "ALTER TABLE {$table} DROP COLUMN form_test" );
                break;
        }
    }
}

$hupa_fb_api_register_plugin = Hupa_Register_FB_Plugin::fb_api_instance();
$hupa_fb_api_register_plugin->init_fb_api_plugin();


//Settings CLASS
require 'hooks/filter/hupa-fp-api-settings.php';
//Imports CLASS
require 'hooks/filter/hupa-fb-api-imports.php';
//FB SDK
require 'fb-sdk/vendor/autoload.php';
//FB APP ClASS
require 'hooks/fb-api/hupa-fb-api-plugin-app.php';
//CRONJOB CLASS
require 'hooks/cron/hupa-fb-api-cron.php';
//CRONJOB EXEC (AUSFÜHREN)
require 'hooks/cron/api-cronjob-exec.php';
