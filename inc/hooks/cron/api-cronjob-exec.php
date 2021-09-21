<?php
/**
 *
 *   Jens Wiecker PHP Class
 *   @package Jens Wiecker WordPress Plugin
 *   Copyright 2021, Jens Wiecker
 *   License: Commercial - goto https://www.hummelt-werbeagentur.de/
 *   https://www.hummelt-werbeagentur.de/
 *
 */


add_action('fb_api_plugin_sync', 'HupaFBApiPlugin_synchronisation_exec',0);
function HupaFBApiPlugin_synchronisation_exec(): void
{
    $file = HUPA_LOG_DIR . 'fb-api-sync.log';
    $logMsg = '';
    //file_put_contents($file, $logMsg);
    $time = date('\a\m d.m.Y \u\m H:i:s', current_time('timestamp'));
    $import = apply_filters('get_fb_api_imports', false);
    if($import->status){
        foreach ($import->record as $log){
            if(!$log->aktiv){
                continue;
            }
            $msg = __('Synchronisation successful', 'hupa-fb-api');
            $logMsg = $time . ' Uhr | <<< Import: '.$log->bezeichnung.' >>> | Message: '.$msg.' ' . "\r\n";
            file_put_contents($file, $logMsg, FILE_APPEND | LOCK_EX);
        }
        foreach ($import->record as $tmp){
            if(!$tmp->aktiv){
                continue;
            }
            apply_filters('fb_api_plugin_posts_sync', $tmp->id);
            apply_filters('fb_api_plugin_events_sync', $tmp->id);
        }
    }
}


