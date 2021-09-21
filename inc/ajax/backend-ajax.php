<?php
/** @noinspection PhpUnreachableStatementInspection */
defined('ABSPATH') or die();

/**
 * Jens Wiecker PHP Class
 * @package Jens Wiecker WordPress Plugin
 * Copyright 2021, Jens Wiecker
 * License: Commercial - goto https://www.hummelt-werbeagentur.de/
 * https://www.hummelt-werbeagentur.de/
 *
 */

$responseJson = new stdClass();
$data = $_POST['daten'];

isset($data['method']) && is_string($data['method']) ? $method = esc_html($data['method']) : $method = '';
if (empty($method)) {
    $method = $_POST['method'];
}

$status = false;
$msg = '';
$errMsg = [];
$successMsg = [];
global $wpdb;

switch ($method) {
    case'set_plugin_settings':
        isset($data['type']) && is_string($data['type']) ? $type = sanitize_text_field($data['type']) : $type = '';

        if (!$type) {
            $responseJson->status = false;
            $responseJson->msg = 'Settings können nicht gespeichert werden!';
            return $responseJson;
        }

        $record = new stdClass();

        switch ($type) {
            case 'api-settings':
                isset($data['app_id']) && is_string($data['app_id']) ? $data['app_id'] = sanitize_text_field($data['app_id']) : $data['app_id'] = '';
                isset($data['app_secret']) && is_string($data['app_secret']) ? $data['app_secret'] = sanitize_text_field($data['app_secret']) : $data['app_secret'] = '';
                isset($data['token']) && is_string($data['token']) ? $data['token'] = sanitize_textarea_field($data['token']) : $data['token'] = '';
                isset($data['sync_interval']) && is_numeric($data['sync_interval']) ? $sync_interval = (int)$data['sync_interval'] : $sync_interval = '';
                isset($data['sync_max']) && is_numeric($data['sync_max']) ? $data['sync_max'] = (int)sanitize_text_field($data['sync_max']) : $data['sync_max'] = '';
                isset($data['cron_aktiv']) && is_string($data['cron_aktiv']) ? $data['cron_aktiv'] = 1 : $data['cron_aktiv'] = 0;
                $settings = apply_filters('get_fb_api_plugin_settings', false)->record;
                foreach ($data as $key => $val) {
                    switch ($key) {
                        case'app_id':
                            if ($data['app_id']) {
                                $record->content = $data['app_id'];
                                $record->type = '%s';
                                $record->column = 'app_id';
                                apply_filters('fb_api_plugin_update_settings', $record);
                                $successMsg[] = 'app_id';
                            } else {
                                $errMsg[] = 'app_id';
                            }
                            break;
                        case'app_secret':
                            if ($data['app_secret']) {
                                $record->content = $data['app_secret'];
                                $record->type = '%s';
                                $record->column = 'app_secret';
                                apply_filters('fb_api_plugin_update_settings', $record);
                                $successMsg[] = 'app_secret';
                            } else {
                                $errMsg[] = 'app_secret';
                            }
                            break;
                        case'token':
                            $where = sprintf('WHERE id=%d', HUPA_PLUGIN_SETTINGS_ID);
                            $settings = apply_filters('get_fb_api_plugin_settings', $where);
                            if (!$data['token'] && $settings->status && $settings->record->access_token) {
                                $successMsg[] = 'token';
                            } elseif ($data['token']) {
                                $record->content = $data['token'];
                                $record->type = '%s';
                                $record->column = 'access_token';
                                apply_filters('fb_api_plugin_update_settings', $record);
                                $successMsg[] = 'token';
                            } else {
                                $errMsg[] = 'token';
                            }
                            break;
                        case'sync_interval':
                            if ($settings->sync_interval !== $sync_interval) {
                                $record->content = $sync_interval;
                                $record->type = '%d';
                                $record->column = 'sync_interval';
                                apply_filters('fb_api_plugin_update_settings', $record);
                                wp_clear_scheduled_hook('fb_api_plugin_sync');
                                apply_filters('wp_api_run_schedule_task', false);
                            }
                            break;
                        case'sync_max':
                            $record->content = $data['sync_max'];
                            $record->type = '%d';
                            $record->column = 'max_sync';
                            apply_filters('fb_api_plugin_update_settings', $record);
                            break;
                        case 'cron_aktiv':
                            if ($data['cron_aktiv'] && $settings->cron_aktiv !== $data['cron_aktiv']) {
                                apply_filters('wp_api_run_schedule_task', false);
                            }
                            if (!$data['cron_aktiv'] && $settings->cron_aktiv !== $data['cron_aktiv']) {
                                wp_clear_scheduled_hook('fb_api_plugin_sync');
                            }
                            $record->content = $data['cron_aktiv'];
                            $record->type = '%d';
                            $record->column = 'cron_aktiv';
                            apply_filters('fb_api_plugin_update_settings', $record);

                            break;
                    }
                }
                break;
        }

        $responseJson->status = true;
        $responseJson->err_arr = $errMsg ? (object)$errMsg : false;
        $responseJson->success_arr = $successMsg ? (object)$successMsg : false;
        $responseJson->msg = current_time('H:i:s');
        break;

    case'show_fb_access_token':
        $where = sprintf('WHERE id=%d', HUPA_PLUGIN_SETTINGS_ID);
        $settings = apply_filters('get_fb_api_plugin_settings', $where);

        if (!$settings->status || !$settings->record->access_token) {
            $responseJson->msg = __('No access token found!', 'hupa-fb-api');
            return $responseJson;
        }
        $responseJson->msg = $settings->record->access_token;
        break;

    case'check_fp_access_token':
        $check = apply_filters('check_fb_access_token', false);
        if ($check->status) {
            $body = '<h5 class="text-center"><i class="text-success fa fa-check"></i>&nbsp;' . $check->msg . '.</h5>';
        } else {
            $body = '<p class="text-center fs-6"><i class="text-danger fa fa-times"></i>&nbsp; ' . $check->msg . '</p>';
        }

        $responseJson->head = '<i class="fa fa-facebook-square"></i> Facebook API Status';
        $responseJson->status = $check->status;
        $responseJson->msg = $body;
        break;

    case 'set_facebook_category':
        isset($data['cat_name']) && is_string($data['cat_name']) ? $cat_name = sanitize_text_field($data['cat_name']) : $cat_name = '';
        isset($data['cat_slug']) && is_string($data['cat_slug']) ? $cat_slug = sanitize_text_field($data['cat_slug']) : $cat_slug = '';
        isset($data['description']) && is_string($data['description']) ? $description = sanitize_textarea_field($data['description']) : $description = '';

        if (!$cat_name) {
            $responseJson->status = false;
            $responseJson->name = 'cat_name';
            $responseJson->msg = __('Category name is a required field!', 'hupa-fb-api');
            return $responseJson;
        }
        if (!$cat_slug) {
            $responseJson->status = false;
            $responseJson->name = 'cat_slug';
            $responseJson->msg = __('The title form is required!', 'hupa-fb-api');
            return $responseJson;
        }

        $cat_slug = strtolower(preg_replace('/\s+/', '', $cat_slug));
        if (strlen($cat_slug) < 5) {
            $responseJson->status = false;
            $responseJson->name = 'cat_slug';
            $responseJson->msg = __('Title form must have at least 5 characters!', 'hupa-fb-api');
            return $responseJson;
        }

        if (!preg_match("~^[0-9a-z\-_]+$~i", $cat_slug)) {
            $responseJson->status = false;
            $responseJson->name = 'cat_slug';
            $responseJson->msg = __('Title form wrong format! Only letters, numbers, hyphens or underscores.', 'hupa-fb-api');
            return $responseJson;
        }

        $fb_terms = apply_filters('get_custom_terms', 'facebook_category');
        if ($fb_terms->status) {
            foreach ($fb_terms->terms as $tmp) {
                if ($tmp->name === $cat_name || $tmp->slug === $cat_slug) {
                    $responseJson->status = false;
                    $responseJson->msg = __('Name or title form already exists!', 'hupa-fb-api');
                    return $responseJson;
                }
            }
        }
        wp_insert_term(
            $cat_name,
            'facebook_category',
            array(
                'description' => $description,
                'slug' => $cat_slug
            )
        );

        $responseJson->select = apply_filters('get_custom_terms', 'facebook_category')->terms;
        $responseJson->status = true;
        $responseJson->catName = $cat_name;
        $responseJson->selLang = __('select', 'hupa-fb-api');
        $responseJson->msg = __('saved', 'hupa-fb-api');

        break;

    case'fb_api_import_form_handle':
        $record = new stdClass();
        isset($data['import_name']) && is_string($data['import_name']) ? $record->import_name = sanitize_text_field($data['import_name']) : $record->import_name = '';
        isset($data['post_description']) && is_string($data['post_description']) ? $record->post_description = sanitize_textarea_field($data['post_description']) : $record->post_description = '';
        isset($data['import_count']) && is_numeric($data['import_count']) ? $import_count = (int)sanitize_text_field($data['import_count']) : $import_count = '';
        isset($data['user_id']) && is_string($data['user_id']) ? $user_id = sanitize_text_field($data['user_id']) : $user_id = '';
        isset($data['page_id']) && is_string($data['page_id']) ? $page_id = sanitize_text_field($data['page_id']) : $page_id = '';
        isset($data['check_user_id']) && is_string($data['check_user_id']) ? $check_user_id = 1 : $check_user_id = 0;
        isset($data['post_cat']) && is_numeric($data['post_cat']) ? $post_cat = (int)sanitize_text_field($data['post_cat']) : $post_cat = '';
        isset($data['event_cat']) && is_numeric($data['event_cat']) ? $event_cat = (int)sanitize_text_field($data['event_cat']) : $event_cat = '';
        isset($data['type']) && is_string($data['type']) ? $type = sanitize_text_field($data['type']) : $type = '';

        if (!$type) {
            $responseJson->status = false;
            $responseJson->msg = 'ERROR!';
            return $responseJson;
        }

        if (!$record->import_name && $type === 'insert') {
            $responseJson->status = false;
            $responseJson->msg = __('The input field Name/Location is a mandatory field!', 'hupa-fb-api');
            $responseJson->feeld = 'import_name';
            return $responseJson;
        }

        $import_count ? $record->import_count = $import_count : $record->import_count = 0;
        if ($check_user_id && !$user_id || !$check_user_id && !$page_id) {
            $record->user_id = 'me';
        } else {
            $record->user_id = $user_id;
        }

        $check_user_id && !$user_id ? $record->check_user_id = 0 : $record->check_user_id = $check_user_id;
        $record->page_id = $page_id;

        $term_post_cat = '';
        $term_event_cat = '';
        if (!$post_cat || !$event_cat) {
            $terms = apply_filters('get_custom_terms', 'facebook_category');
            foreach ($terms->terms as $tmp) {
                if ($tmp->name === 'Facebook Allgemein') {
                    $term_post_cat = $tmp->term_id;
                }
                if ($tmp->name == 'Facebook Veranstaltungen') {
                    $term_event_cat = $tmp->term_id;
                }
            }
        }

        $post_cat ? $record->post_term_id = $post_cat : $record->post_term_id = $term_post_cat;
        $event_cat ? $record->event_term_id = $event_cat : $record->event_term_id = $term_event_cat;


        $args = sprintf('WHERE bezeichnung ="%s"', $record->import_name);
        $dbImports = apply_filters('get_fb_api_imports', $args);
        if($type === 'insert') {
            if ($dbImports->status) {
                $responseJson->status = false;
                $responseJson->msg = sprintf(__('Name or location "%s" already exists!', 'hupa-fb-api'), $record->import_name);
                $responseJson->feeld = 'import_name';
                return $responseJson;
            }
        }

        if (!$record->user_id) {
            $record->user_id = 'me';
        }
        $record->aktiv = 1;

        switch ($type){
            case 'insert':
                $insert = apply_filters('set_fb_abi_imports', $record);
                $status = $insert->status;
                $msg = $insert->msg;
                $responseJson->reset = true;
                break;
            case 'update':
                isset($data['id']) && is_numeric($data['id']) ? $record->id = (int)$data['id'] : $record->id = '';
                if (!$record->id) {
                    $responseJson->status = false;
                    $responseJson->msg = 'ERROR!';
                    return $responseJson;
                }
                $args = sprintf('WHERE id =%d', $record->id);
                $import = apply_filters('get_fb_api_imports', $args);
                if($import->record[0]->bezeichnung !== $record->import_name){
                    $args = sprintf('WHERE bezeichnung ="%s"', $record->import_name);
                    $dbImports = apply_filters('get_fb_api_imports', $args);
                    if ($dbImports->status) {
                        $responseJson->status = false;
                        $responseJson->msg = sprintf(__('Name or location "%s" already exists!', 'hupa-fb-api'), $record->import_name);
                        $responseJson->feeld = 'import_name';
                        return $responseJson;
                    }
                }

                apply_filters('update_fb_api_import', $record);
                $status = true;
                $msg = __('Changes saved', 'hupa-fb-api');
                break;
        }

        $responseJson->status = $status;
        $responseJson->msg = $msg;
        break;

    case'change_import_settings':

        $record = new stdClass();
        isset($_POST['type']) && is_string($_POST['type']) ? $record->column = sanitize_text_field($_POST['type']) : $record->column = '';
        isset($_POST['id']) && is_numeric($_POST['id']) ? $record->id = (int)$_POST['id'] : $record->id = '';

        if (!$record->column || !$record->id) {
            $responseJson->status = false;
            $responseJson->msg = 'ERROR!';
            return $responseJson;
        }

        $args = sprintf('WHERE id =%d', $record->id);
        $import = apply_filters('get_fb_api_imports', $args);
        if (!$import->status) {
            $responseJson->status = false;
            $responseJson->msg = 'ERROR!';
            return $responseJson;
        }

        switch ($record->column) {
            case'aktiv':
                $import->record[0]->aktiv ? $record->content = 0 : $record->content = 1;
                $record->type = "'%d'";
                break;
            case 'user_aktiv':
                $import->record[0]->user_aktiv ? $record->content = 0 : $record->content = 1;
                $record->type = "'%d'";
                break;
        }

        $update = apply_filters('update_fb_abi_inputs', $record);
        $responseJson->status = true;
        break;

    case'get_import_by_id':
        isset($_POST['id']) && is_numeric($_POST['id']) ? $id = (int)$_POST['id'] : $id = '';
        if (!$id) {
            $responseJson->status = false;
            $responseJson->msg = 'ERROR!';
            return $responseJson;
        }

        $args = sprintf('WHERE id =%d', $id);
        $import = apply_filters('get_fb_api_imports', $args);
        if (!$import->status) {
            $responseJson->status = false;
            $responseJson->msg = 'ERROR!';
            return $responseJson;
        }

        $import = $import->record[0];
        $responseJson->status = true;
        $responseJson->id = $import->id;
        $responseJson->lang = apply_filters('fb_api_language', false)->formulare;
        $responseJson->aktiv = $import->aktiv ? 'checked' : '';
        $responseJson->bezeichnung = $import->bezeichnung;
        $responseJson->description = $import->description;
        $responseJson->max_count = $import->max_count;
        $responseJson->user_id = $import->user_id;
        $responseJson->user_aktiv = $import->user_aktiv ? 'checked' : '';
        $responseJson->page_id = $import->page_id;
        $responseJson->post_term_id = $import->post_term;
        $responseJson->event_term_id = $import->event_term;
        $responseJson->post_term_name = apply_filters('fb_api_get_term_by_term_id', $import->post_term)->term->name;
        $responseJson->event_term_name = apply_filters('fb_api_get_term_by_term_id', $import->event_term)->term->name;
        $responseJson->select = apply_filters('get_custom_terms', 'facebook_category')->terms;

        break;
    case'api_delete_handle':
        isset($_POST['id']) && is_numeric($_POST['id']) ? $id = (int)$_POST['id'] : $id = '';
        isset($_POST['type']) && is_string($_POST['type']) ? $type = sanitize_text_field($_POST['type']) : $type = '';
        if (!$type) {
            $responseJson->status = false;
            $responseJson->msg = 'ERROR!';
            return $responseJson;
        }
        switch ($type){
            case 'import':
                if (!$id) {
                    $responseJson->status = false;
                    $responseJson->msg = 'ERROR!';
                    return $responseJson;
                }

                apply_filters('delete_fb_api_inputs', $id);
                apply_filters('delete_fb_api_facebook_posts',$id);
                $responseJson->status = true;
                $responseJson->reload = true;
                break;
            case'delete-posts':
                if (!$id) {
                    $responseJson->status = false;
                    $responseJson->msg = 'ERROR!';
                    return $responseJson;
                }
                apply_filters('delete_fb_api_facebook_posts',$id);
                $responseJson->status = true;
                $responseJson->msg = __('All posts and events deleted!', 'hupa-fb-api');
                break;

        }
        break;

    case 'syn_fb_posts':
        isset($_POST['id']) && is_numeric($_POST['id']) ? $id = (int)$_POST['id'] : $id = '';
        if (!$id) {
            $responseJson->status = false;
            $responseJson->msg = 'ERROR!';
            return $responseJson;
        }

        $syncPosts = apply_filters('fb_api_plugin_posts_sync', $id);
        $syncEvents = apply_filters('fb_api_plugin_events_sync', $id);
        $responseJson->status = $syncPosts->status;
        if($syncPosts->status){
            $msg = __('Synchronisation successful', 'hupa-fb-api');
        } else {
            $msg = $syncPosts->msg;
        }

        $responseJson->msg = $msg;
        break;

    case'reset_import_date':
        isset($_POST['id']) && is_numeric($_POST['id']) ? $id = (int)$_POST['id'] : $id = '';
        if (!$id) {
            $responseJson->status = false;
            $responseJson->msg = 'ERROR!';
            return $responseJson;
        }

        apply_filters('reset_fb_api_import_sync','', $id);
        $responseJson->status = true;
        $responseJson->msg = __('Reset successful', 'hupa-fb-api');
        break;


    case 'imports_data_table':
        $tableData = new stdClass();
        $query = '';
        $columns = array(
            "bezeichnung",
            "aktiv",
            "max_count",
            "user_id",
            "page_id",
            "user_aktiv",
            "post_term",
            "event_term",
            ""
        );

        if (isset($_POST['search']['value'])) {
            $query = ' WHERE bezeichnung LIKE "%' . $_POST['search']['value'] . '%"
             OR max_count LIKE "%' . $_POST['search']['value'] . '%"
             OR user_id LIKE "%' . $_POST['search']['value'] . '%"
             OR page_id LIKE "%' . $_POST['search']['value'] . '%"
             ';
        }

        if (isset($_POST['order'])) {
            $query .= ' ORDER BY ' . $columns[$_POST['order']['0']['column']] . ' ' . $_POST['order']['0']['dir'] . ' ';
        } else {
            $query .= ' ORDER BY created_at ASC';
        }

        $limit = '';
        if ($_POST["length"] != -1) {
            $limit = ' LIMIT ' . $_POST['start'] . ', ' . $_POST['length'];
        }

        $dbImports = apply_filters('get_fb_api_imports', $query . $limit);
        $data_arr = array();
        if (!$dbImports->status) {
            return $responseJson = array(
                "draw" => $_POST['draw'],
                "recordsTotal" => 0,
                "recordsFiltered" => 0,
                "data" => $data_arr
            );
        }

        foreach ($dbImports->record as $tmp) {
            $post_term = apply_filters('fb_api_get_term_by_term_id', $tmp->post_term);
            $event_term = apply_filters('fb_api_get_term_by_term_id', $tmp->event_term);

            $tmp->aktiv ? $a = 'checked' : $a = '';
            $tmp->user_aktiv ? $ua = 'checked' : $ua = '';
            $check1 = '<div class="form-check form-switch">
                            <input data-id="' . $tmp->id . '" data-type="aktiv" class="form-check-input" type="checkbox"
                            id="CheckUserIdActive' . $tmp->id . '" ' . $a . '> </div>';

            $check2 = '<div class="form-check form-switch">
                            <input data-id="' . $tmp->id . '" data-type="user_aktiv" class="form-check-input" type="checkbox"
                            id="CheckUserIdActive' . $tmp->id . '" ' . $ua . '> </div>';
            $data_item = array();
            $data_item[] = '<span>' . $tmp->bezeichnung . '</span>';
            $data_item[] = $check1;
            $data_item[] = '<span>' . $tmp->max_count . '</span>';
            $data_item[] = '<span>' . $tmp->user_id . '</span>';
            $data_item[] = '<span>' . $tmp->page_id . '</span>';
            $data_item[] = $check2;
            $data_item[] = $post_term->term->name;
            $data_item[] = $event_term->term->name;
            $data_item[] = '<button data-id="' . $tmp->id . '" class="btn-import-edit btn btn-blue btn-sm"><i class="fa fa-tasks"></i>&nbsp; ' . __('Edit', 'hupa-fb-api') . ' </button>';
            $data_arr[] = $data_item;
        }
        $importCount = apply_filters('get_fb_api_imports', false);
        $responseJson = array(
            "draw" => $_POST['draw'],
            "recordsTotal" => $importCount->count,
            "recordsFiltered" => $importCount->count,
            "data" => $data_arr,
        );

        break;
    default:
        $responseJson->status = $status;
        $responseJson->msg = 'Error:';
}

