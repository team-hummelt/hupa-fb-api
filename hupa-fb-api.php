<?php
/**
 * WP Custom Facebook Importer
 *
 *
 * @link              https://www.hummelt-werbeagentur.de/
 * @since             1.0.0
 *
 * @wordpress-plugin
 * Plugin Name:       Custom Facebook Importer
 * Plugin URI:        https://www.hummelt-werbeagentur.de/
 * Description:       Facebook API Posts importieren und synchronisieren.
 * Version:           1.0.0
 * Author:            Jens Wiecker
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Requires PHP:      8.0
 * Requires at least: 5.8
 * Tested up to:      5.8
 * Stable tag:        1.0.0
 */

//ignore_user_abort(true);
//DEFINE CONSTANT
const HUPA_FB_PLUGIN_DB_VERSION = '1.0.1';
const HUPA_FB_MIN_PHP_VERSION = '7.4';
const HUPA_FB_MIN_WP_VERSION = '5.6';

//CronJob SLUG (Custom Sites URL)
const HUPA_FB_PLUGIN_CRONJOB_URL = '1206Ob2f6Mu=DG';
const HUPA_FB_PLUGIN_CRONJOB_SLUG = 'cron';
//Settings ID
const HUPA_PLUGIN_SETTINGS_ID = 1;

define('HUPA_API_SLUG_PATH', plugin_basename(__FILE__));
define('HUPA_PLUGIN_URL', plugins_url('hupa-fb-api'));
define('HUPA_PLUGIN_DIR', dirname(__FILE__));
define('HUPA_ADMIN_PAGE_DIR', dirname(__FILE__) . DIRECTORY_SEPARATOR . 'pages-admin' . DIRECTORY_SEPARATOR);
define('HUPA_AJAX_DIR', dirname(__FILE__) . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'ajax' . DIRECTORY_SEPARATOR);
define('HUPA_LOG_DIR', dirname(__FILE__) . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'log' . DIRECTORY_SEPARATOR);

$upload = wp_upload_dir();
define('HUPA_UPLOAD_BASE_URL', $upload['baseurl'] . '/');
define('HUPA_UPLOAD_BASE_DIR', $upload['basedir'] . DIRECTORY_SEPARATOR);

/**
 * REGISTER PLUGIN
 */

require 'inc/license/register-hupa-plugin.php';
if(get_option('fb_api_product_install_authorize')) {
    require 'inc/register-hupa-fb-plugin.php';
    require 'inc/update-checker/autoload.php';
    $fb_api_UpdateChecker = Puc_v4_Factory::buildUpdateChecker(
        'https://github.com/team-hummelt/hupa-fb-api/',
        __FILE__,
        'hupa-fb-api'
    );
    $fb_api_UpdateChecker->getVcsApi()->enableReleaseAssets();
} else {
    $file = HUPA_PLUGIN_DIR . DIRECTORY_SEPARATOR . 'inc' .DIRECTORY_SEPARATOR . 'register-hupa-fb-plugin.php';
    unlink($file);
}


