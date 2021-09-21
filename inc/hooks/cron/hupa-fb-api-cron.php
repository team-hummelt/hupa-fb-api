<?php

namespace Hupa\FBApiPluginCron;

use stdClass;

defined('ABSPATH') or die();

/**
 *
 *  Jens Wiecker PHP Class
 * @package Jens Wiecker WordPress Plugin
 *  Copyright 2021, Jens Wiecker
 *  License: Commercial - goto https://www.hummelt-werbeagentur.de/
 *  https://www.hummelt-werbeagentur.de/
 *
 */


if (!class_exists('HupaFBApiPluginCronJob')) {
    add_action('init', array('Hupa\\FBApiPluginCron\\HupaFBApiPluginCronJob', 'init_cron'), 0);

    final class HupaFBApiPluginCronJob
    {
        //INSTANCE
        private static $instance;

        /**
         * @return static
         */
        public static function init_cron(): self
        {
            if (is_null(self::$instance)) {
                self::$instance = new self;
            }
            return self::$instance;
        }

        /**
         * HupaFBApiPluginCronJob constructor.
         */
        public function __construct()
        {
            //TODO INTERVAL REGISTRIEREN
            if ($this->hupa_check_wp_cron_aktiv()) {
                //TODO WARNING JOB INTERVALL ERSTELLEN
                add_filter('cron_schedules', array($this, 'hupa_wp_api_plugin_cron_schedules'));

                //TODO WARNING JOB CRONJOB ERSTELLEN
                add_filter('wp_api_run_schedule_task', array($this, 'hupa_wp_api_run_schedule_task'));
            }

            //TODO WARNING JOB CRONJOB EVENT ABBRECHEN
            add_filter('fb_plugin_wp_unschedule_event', array($this, 'hupa_fb_plugin_wp_unschedule_event'));

            //TODO WARNING JOB CRONJOB EVENT LÖSCHEN
            add_filter('fb_plugin_wp_delete_event', array($this, 'hupa_fb_plugin_wp_delete_event'));

            //TODO FILTER CHECK OB WP-CRON AKTIV IST
            add_filter('check_wp_cron_aktiv', array($this, 'hupa_check_wp_cron_aktiv'));

            //TODO Interval and SELECT FOR SITE
            //@args select
            //@args taskInterval
            add_filter('select_api_sync_interval', array($this, 'hupa_select_api_sync_interval'));
        }

        /**
         * @param $schedules
         * @return array
         */
        public function hupa_wp_api_plugin_cron_schedules($schedules): array
        {
            $prefix = 'fb_api_cron_';
            $schedule_options = [
                '30_min' => [
                    'display' => sprintf(__('every %d minutes', 'hupa-fb-api'), 30),
                    'interval' => '1800'
                ],
                '1_hours' => [
                    'display' => __('Hourly', 'hupa-fb-api'),
                    'interval' => '3600'
                ],
                '6_hours' => [
                    'display' => sprintf(__('every %d hours', 'hupa-fb-api'), 6),
                    'interval' => '21600'
                ],
                '12_hours' => [
                    'display' => sprintf(__('every %d hours', 'hupa-fb-api'), 12),
                    'interval' => '43200'
                ],
                '24_hours' => [
                    'display' => sprintf(__('every %d hours', 'hupa-fb-api'), 24),
                    'interval' => '86400'
                ],
                '1_weakly' => [
                    'display' => sprintf(__('every %d days', 'hupa-fb-api'), 7),
                    'interval' => '604800'
                ]
            ];

           // unset( $schedules['fb_api_cron_1_min'] );
            foreach ($schedule_options as $schedule_key => $schedule) {
               $schedules[$prefix . $schedule_key] = array(
                    'interval' => $schedule['interval'],
                    'display' => $schedule['display']
                );
            }
            return $schedules;
        }

        /**
         * @return bool
         */
        public function hupa_check_wp_cron_aktiv(): bool
        {
            if (defined('DISABLE_WP_CRON') && DISABLE_WP_CRON) {
                return false;
            } else {
                return true;
            }
        }

        public function hupa_fb_plugin_wp_unschedule_event($args): void
        {
            $timestamp = wp_next_scheduled('fb_api_plugin_sync');
            wp_unschedule_event($timestamp, 'fb_api_plugin_sync');
        }

        public function hupa_fb_plugin_wp_delete_event($args): void
        {
            wp_clear_scheduled_hook('fb_api_plugin_sync');
        }

        public function hupa_wp_api_run_schedule_task($args): void
        {
            $schedule = $this->hupa_select_api_sync_interval('taskInterval');
            $time = get_gmt_from_date(gmdate('Y-m-d H:i:s', current_time('timestamp')), 'U');
            $args = [
                'timestamp' => $time,
                'recurrence' => $schedule->recurrence,
                'hook' => 'fb_api_plugin_sync'
            ];

            $this->schedule_task($args);
        }

        /**
         * @param $task
         * @return void
         */
        private function schedule_task($task): void
        {

            /* Must have task information. */
            if (!$task) {
                return;
            }

            /* Set list of required task keys. */
            $required_keys = array(
                'timestamp',
                'recurrence',
                'hook'
            );

            /* Verify the necessary task information exists. */
            $missing_keys = [];
            foreach ($required_keys as $key) {
                if (!array_key_exists($key, $task)) {
                    $missing_keys[] = $key;
                }
            }

            /* Check for missing keys. */
            if (!empty($missing_keys)) {
                return;
            }
            /* Task darf nicht bereits geplant sein. */
            if (wp_next_scheduled($task['hook'])) {
                wp_clear_scheduled_hook($task['hook']);
            }

            /* Schedule the task to run. */
            wp_schedule_event($task['timestamp'], $task['recurrence'], $task['hook']);
        }

        /**
         * @param $args
         * @return object
         */
        public function hupa_select_api_sync_interval($args): object
        {
            $return = new stdClass();
            switch ($args) {
                case 'select':
                    $select = [
                        "0" => [
                            "id" => 1,
                            "bezeichnung" => sprintf(__('every %d minutes', 'hupa-fb-api'), 30)
                        ],
                        "1" => [
                            'id' => 2,
                            "bezeichnung" => __('hourly', 'hupa-fb-api')
                        ],
                        "2" => [
                            'id' => 3,
                            "bezeichnung" => sprintf(__('every %d hours', 'hupa-fb-api'), 6)
                        ],
                        "3" => [
                            'id' => 4,
                            "bezeichnung" => sprintf(__('every %d hours', 'hupa-fb-api'), 12)
                        ],
                        "4" => [
                            'id' => 5,
                            "bezeichnung" => sprintf(__('every %d hours', 'hupa-fb-api'), 24)
                        ],
                        "5" => [
                            'id' => 6,
                            "bezeichnung" => sprintf(__('every %d days', 'hupa-fb-api'), 7)
                        ]
                    ];

                    $return->select = $this->arrayToObject($select);

                    break;
                case'taskInterval':
                    $settings = $this->settings();
                    switch ($settings->sync_interval) {
                        case '1':
                            $return->recurrence = 'fb_api_cron_30_min';
                            break;
                        case '2':
                            $return->recurrence = 'fb_api_cron_1_hours';
                            break;
                        case '3':
                            $return->recurrence = 'fb_api_cron_6_hours';
                            break;
                        case '4':
                            $return->recurrence = 'fb_api_cron_12_hours';
                            break;
                        case '5':
                            $return->recurrence = 'fb_api_cron_24_hours';
                            break;
                        case '6':
                            $return->recurrence = 'fb_api_cron_1_weakly';
                            break;
                        default:
                            $return->recurrence = 'fb_api_cron_6_hours';
                    }
                    break;
            }
            return $return;
        }

        /**
         * @return object
         */
        private function settings(): object
        {
            return apply_filters('get_fb_api_plugin_settings', sprintf('WHERE id=%d', HUPA_PLUGIN_SETTINGS_ID))->record;
        }

        /**
         * @param $array
         * @return object
         */
        private function arrayToObject($array): object
        {
            foreach ($array as $key => $value) {
                if (is_array($value)) $array[$key] = self::arrayToObject($value);
            }
            return (object)$array;
        }
    }
}