<?php
$file = HUPA_LOG_DIR . 'fb-api-sync.log';
$logMsg = '';
file_put_contents($file, $logMsg);
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
