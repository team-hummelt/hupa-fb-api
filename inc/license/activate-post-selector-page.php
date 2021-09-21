<?php
defined('ABSPATH') or die();
/**
 * ADMIN HOME SITE
 * @package Hummelt & Partner WordPress Theme
 * Copyright 2021, Jens Wiecker
 * License: Commercial - goto https://www.hummelt-werbeagentur.de/
 * https://www.hummelt-werbeagentur.de/
 */

$code = filter_input(INPUT_GET, 'code', FILTER_SANITIZE_STRING);
$errMsg = '';
$aktivShow = 'd-none';
$registerShow = '';
$file = '';
if ($code) {
    $response = apply_filters('get_fb_api_resource_authorization_code', $code);
    if ($response->status) {
        if ($response->if_file) {
            $file = HUPA_PLUGIN_DIR . DIRECTORY_SEPARATOR . $response->aktivierung_path;
            file_put_contents($file, $response->install_datei);
        }
        update_option('fb_api_product_install_authorize', true);
        delete_option('fb_api_message');
    } else {
        $errMsg = 'Plugin konnte nicht aktiviert werden!';
    }
    $aktivShow = '';
    $registerShow = 'd-none';
}
$reloadUrl=admin_url();
echo get_option('fb_api_product_install_authorize');
?>

<div id="wp-activate-license-wrapper">

    <div class="container">
        <div class="card card-license">
            <div class="card-body shadow-license-box">
                <h5 class="card-title"><i class="wp-blue bi bi-exclude"></i>&nbsp;Plugin aktivieren </h5>
                <?php if(get_option('fb_api_message')): ?>
                <p style="padding: 0 1rem; color: red;text-align: center"><i class="bi bi-exclamation-triangle-fill"></i>&nbsp;
                    <b><?=get_option('fb_api_message')?></b></p>
                <?php endif; ?>
                <hr>
                <div id="licence_display_data">
                    <!------------------------>
                    <div class="final-activate <?= $aktivShow ?>">
                        <h5><i class="bi bi-info-circle"></i>&nbsp;erfolgreich aktiviert</h5>
                        <a href="<?= $reloadUrl ?>" class="btn btn-primary">
                            <i class="bi bi-exclude"></i>&nbsp;Aktivierung abschließen</a>
                        <hr>
                    </div>
                    <!------------------------>
                    <!--<?=$registerShow?>-->
                    <div class="<?=$registerShow?>">
                        <div class="card-title">
                            <i class="bi bi-share-fill"></i>&nbsp; Zugangsdaten eingeben
                        </div>
                        <hr>
                        <div class="form-container">
                            <form id="sendAjaxLicenseForm" action="#" method="post">
                                <input type="hidden" name="method" value="save_license_data">
                                <div class="form-input-wrapper">
                                    <div class="col">
                                        <label for="ClientIDInput" class="form-label">
                                            <?= __('Client ID', 'hupa-fb-api') ?> <span
                                                    class="text-danger">*</span></label>
                                        <input type="text" name="client_id" class="form-control"
                                               value="<?= get_option('fb_api_client_id') ?>"
                                               id="ClientIDInput" autocomplete="cc-number" required>
                                    </div>
                                    <div class="col">
                                        <label for="clientSecretInput" class="form-label">
                                            <?= __('Client secret', 'hupa-fb-api') ?> <span
                                                    class="text-danger">*</span></label>
                                        <input type="text" name="client_secret" class="form-control"
                                               value="<?= get_option('fb_api_client_secret') ?>"
                                               id="clientSecretInput" autocomplete="cc-number" required>
                                    </div>
                                </div>
                                <button id="saveBtn" type="submit" class="btn btn-primary"><i class="bi bi-save"></i>&nbsp;
                                    Speichern
                                </button>
                                <span id="activateBtn"></span>
                            </form>
                        </div>
                        <div id="licenseAlert" class="alert alert-danger <?= $errMsg ?: 'd-none' ?>" role="alert">
                            <i class="fa fa-exclamation-triangle"></i>&nbsp; <b>FEHLER!</b> <span
                                    id="licenseErrMsg"><?= $errMsg ?></span>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>
