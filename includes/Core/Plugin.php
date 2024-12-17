<?php
namespace ImoviewImporter\Core;

use ImoviewImporter\Core\Cron\Handler;
use ImoviewImporter\Core\Cron\Scheduler;

class Plugin {
    private $post_type;
    private $admin;
    private $cron_handler;
    private $cron_scheduler;

    public function init() {
        // Initialize components
        $this->post_type = new PostType();
        $this->admin = new Admin();
        
        // Initialize cron components
        $this->cron_scheduler = new Scheduler();
        $this->cron_handler = new Handler();

        // Register activation/deactivation hooks
        register_activation_hook(IMOVIEW_PLUGIN_DIR . 'imoview-importer.php', [$this, 'activate']);
        register_deactivation_hook(IMOVIEW_PLUGIN_DIR . 'imoview-importer.php', [$this, 'deactivate']);
    }

    public function activate() {
        // Schedule cron job
        $this->cron_scheduler->schedule();
        
        // Log activation
        $logger = new Logger();
        $logger->log(__('Plugin ativado e cron agendado'));
    }

    public function deactivate() {
        // Unschedule cron job
        $this->cron_scheduler->unschedule();
        
        // Log deactivation
        $logger = new Logger();
        $logger->log(__('Plugin desativado e cron removido'));
    }
}