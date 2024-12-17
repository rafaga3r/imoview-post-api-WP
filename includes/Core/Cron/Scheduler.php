<?php
namespace ImoviewImporter\Core\Cron;

class Scheduler {
    const CRON_HOOK = 'imoview_import_cron';
    const CRON_INTERVAL = 'imoview_hourly';

    public function __construct() {
        add_filter('cron_schedules', [$this, 'add_cron_interval']);
    }

    /**
     * Adds custom cron interval
     */
    public function add_cron_interval($schedules) {
        $schedules[self::CRON_INTERVAL] = [
            'interval' => HOUR_IN_SECONDS,
            'display' => __('Every Hour')
        ];
        return $schedules;
    }

    /**
     * Schedule the cron job
     */
    public function schedule() {
        if (!wp_next_scheduled(self::CRON_HOOK)) {
            wp_schedule_event(time(), self::CRON_INTERVAL, self::CRON_HOOK);
        }
    }

    /**
     * Unschedule the cron job
     */
    public function unschedule() {
        $timestamp = wp_next_scheduled(self::CRON_HOOK);
        if ($timestamp) {
            wp_unschedule_event($timestamp, self::CRON_HOOK);
        }
    }

    /**
     * Check if cron is running properly
     */
    public function is_cron_running() {
        return (bool) wp_next_scheduled(self::CRON_HOOK);
    }
}