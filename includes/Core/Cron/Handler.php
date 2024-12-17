<?php
namespace ImoviewImporter\Core\Cron;

use ImoviewImporter\Core\Importer;
use ImoviewImporter\Core\Logger;

class Handler {
    private $importer;
    private $logger;
    private $scheduler;
    private $lock;
    private $retry_manager;

    public function __construct() {
        $this->importer = new Importer();
        $this->logger = new Logger();
        $this->scheduler = new Scheduler();
        $this->lock = new Lock();
        $this->retry_manager = new RetryManager();
        
        add_action(Scheduler::CRON_HOOK, [$this, 'handle']);
        add_action('admin_init', [$this, 'check_cron_health']);
    }

    /**
     * Handle the cron execution
     */
    public function handle() {
        // Check if we should retry
        if (!$this->retry_manager->should_retry()) {
            $this->logger->log(__('Máximo de tentativas atingido. Aguardando próximo ciclo.'));
            return;
        }

        // Try to acquire lock
        if (!$this->lock->acquire()) {
            $this->logger->log(__('Importação já em andamento. Pulando execução.'));
            return;
        }

        try {
            // Set time limit and memory limit
            set_time_limit(0);
            ini_set('memory_limit', '256M');
            
            // Log start of import
            $this->logger->log(__('Iniciando importação automática via cron'));
            
            // Run the import
            $this->importer->run_import();
            
            // Clear retry history on success
            $this->retry_manager->clear();
            
            // Log successful completion
            $this->logger->log(__('Importação automática concluída com sucesso'));
            
            // Update last successful run time
            update_option('imoview_last_cron_success', time());
        } catch (\Exception $e) {
            $this->logger->log(__('Erro na importação automática: ') . $e->getMessage());
            $this->retry_manager->record_failure();
        } finally {
            // Always release the lock
            $this->lock->release();
        }
    }

    /**
     * Check cron health and handle issues
     */
    public function check_cron_health() {
        // Check if cron is scheduled
        if (!$this->scheduler->is_cron_running()) {
            $this->logger->log(__('Cron não está agendado. Reagendando...'));
            $this->scheduler->schedule();
        }

        // Check for stuck locks
        if ($this->lock->is_locked()) {
            $lock_time = get_option(Lock::LOCK_OPTION)['timestamp'];
            if ((time() - $lock_time) > Lock::LOCK_TIMEOUT) {
                $this->logger->log(__('Detectado travamento. Liberando lock...'));
                $this->lock->release();
            }
        }

        // Check retry status
        $retry_status = $this->retry_manager->get_status();
        if ($retry_status['has_retries']) {
            $this->logger->log(sprintf(
                __('Status das tentativas: %d de %d. Próxima tentativa: %s'),
                $retry_status['count'],
                RetryManager::MAX_RETRIES,
                date('Y-m-d H:i:s', $retry_status['next_attempt'])
            ));
        }

        // Check for missed runs
        $last_success = get_option('imoview_last_cron_success', 0);
        if ($last_success && (time() - $last_success) > (HOUR_IN_SECONDS * 2)) {
            $this->logger->log(__('Detectada possível falha no cron. Última execução: ') . 
                date('Y-m-d H:i:s', $last_success));
        }
    }

    /**
     * Force run the cron job manually
     */
    public function force_run() {
        // Clear any existing locks and retry history
        $this->lock->release();
        $this->retry_manager->clear();
        
        $this->logger->log(__('Iniciando importação manual'));
        $this->handle();
    }
}