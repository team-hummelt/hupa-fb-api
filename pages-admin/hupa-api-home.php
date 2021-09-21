<?php
/**
 * Jens Wiecker PHP Class
 * @package Jens Wiecker WordPress Plugin
 * Copyright 2021, Jens Wiecker
 * License: Commercial - goto https://www.hummelt-werbeagentur.de/
 * https://www.hummelt-werbeagentur.de/
 *
 */
$settings = apply_filters('get_fb_api_plugin_settings', false)->record;
?>
<div class="wp-bs-custom-wrapper">
    <div class="container">
        <?php

        ?>
        <div class="card shadow-sm">
            <h5 class="card-header bg-custom-blue py-4"><i class="fa fa-facebook-square"></i>&nbsp; Facebook Import
                Posts</h5>
            <div class="card-body pb-4">
                <div class="d-flex align-items-center">
                <h5 class="card-title"><i class="font-blue fa fa-cog"></i> FB-API Settings</h5>
                <span class="ms-auto d-inline-block mb-2 pe-2 statusMsg"></span>
                </div>
                <form class="send-ajax-plugin-settings" action="#" method="post">
                    <input type="hidden" name="method" value="set_plugin_settings">
                    <input type="hidden" name="type" value="api-settings">
                    <div class="border rounded shadow-sm p-3 bg-custom-gray">
                        <hr>
                        <h6 class="card-title"><?=__('FB API credentials', 'hupa-fb-api')?> </h6>
                        <hr>
                        <div class="row g-3 ">
                            <div class="col-lg-4">
                                <label for="inputAppId" class="form-label">App ID:</label>
                                <input type="text" name="app_id" value="<?=$settings->app_id?>" autocomplete="cc-number" class="form-control"
                                       id="inputAppId">
                            </div>
                            <div class="col-lg-4">
                                <label for="inputSecret" class="form-label">APP Secret:</label>
                                <input type="text" name="app_secret" value="<?=$settings->app_secret?>" autocomplete="cc-number" class="form-control"
                                       id="inputSecret">
                            </div>
                            <div class="col-lg-12">
                                <label for="inputToken" class="form-label">Access-Token:</label>
                                  <textarea class="form-control" name="token" id="inputToken" placeholder="<?=$settings->access_token ? __('Access token saved!', 'hupa-fb-api') : 'xxxxxxxxxxxx'?>"></textarea>
                            </div>
                        </div>
                        <div class="py-1">
                        <button type="button" class="show-access-token show btn btn-outline-secondary btn-sm mx-0 mt-2 mb-0" <?php if($settings->access_token ? '' : 'disabled')?>><span class="show-btn-text"><i class="font-blue fa fa-eye"></i>&nbsp;Token anzeigen</span></button>
                        <button type="button" data-bs-method="check_fp_access_token" data-bs-toggle="modal" data-bs-target="#AjaxResponseModal" class="btn btn-outline-secondary btn-sm mx-0 mt-2 mb-0" <?php if($settings->access_token ? '' : 'disabled')?>><span class="check-btn-token"><i class="text-success fa fa-check"></i>&nbsp;Check Access-Token</span></button>
                        </div>
                    </div>
                      <div class="border rounded mt-4 shadow-sm p-3 bg-custom-gray">
                        <hr>
                        <h6 class="card-title"> <?= __('Synchronisation settings', 'hupa-fb-api') ?></h6>
                        <hr>
                        <div class="row g-3 pb-3">
                            <div class="col-lg-12 pt-2">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" name="cron_aktiv" type="checkbox"
                                           id="CheckCronActive" <?=$settings->cron_aktiv ? 'checked' : ''?>>
                                    <label class="form-check-label" for="CheckCronActive">
                                        <?= __('Cronjob active', 'hupa-fb-api') ?></label>
                                </div>
                            </div>
                            <hr>
                            <div class="col-lg-4">
                                <label for="inputSyncInterval" class="form-label"><?= __('Synchronisation interval:', 'hupa-fb-api') ?></label>
                                <select class="form-select" name="sync_interval" id="inputSyncInterval" <?=$settings->cron_aktiv ?: 'disabled'?>>
                                <?php $interval = apply_filters('select_api_sync_interval','select');
                                    foreach ($interval->select as $tmp):
                                        if($settings->sync_interval == $tmp->id){
                                            $sel = 'selected';
                                        } else {
                                            $sel = '';
                                        } ?>
                                        <option value="<?=$tmp->id?>" <?=$sel?>><?= $tmp->bezeichnung?></option>
                                    <?php endforeach; ?>
                                </select>
                                <div id="inputSyncIntervalHelp" class="form-text">
                                    <?= __('Update interval for synchronisation.', 'hupa-fb-api') ?>
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <label for="inputMaxPosts" class="form-label"><?= __('Import posts per update:', 'hupa-fb-api') ?></label>
                                <select class="form-select" name="sync_max" id="inputMaxPosts" <?=$settings->cron_aktiv ?: 'disabled';?>>
                                    <?php $select = apply_filters('select_max_post_for_sync',false);
                                    foreach ($select as $tmp):
                                        if($settings->max_sync == $tmp->id){
                                            $sel = 'selected';
                                        } else {
                                            $sel = '';
                                        } ?>
                                        <option value="<?=$tmp->id?>" <?=$sel?>><?= $tmp->value?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                          <hr>
                          <small><?= __('URL for external cronjob:', 'hupa-fb-api') ?> <span class="font-blue"> <?=site_url()?>/?<?=HUPA_FB_PLUGIN_CRONJOB_SLUG?>=<?=strtoupper(md5(HUPA_FB_PLUGIN_CRONJOB_URL))?></span></small>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="AjaxResponseModal" tabindex="-1" aria-labelledby="AjaxResponseModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="AjaxResponseModalTitle">Response</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body"></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal"><i class="text-danger fa fa-times"></i> <?=__('Close', 'hupa-fb-api')?></button>
                </div>
            </div>
        </div>
    </div>
</div><!--BS-Wrapper-->
<div id="snackbar-success"></div>
<div id="snackbar-warning"></div>