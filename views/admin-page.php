<?php
if (!defined('ABSPATH')) {
    exit;
}

use ImoviewImporter\Core\Cron\Handler;
use ImoviewImporter\Core\Cron\Scheduler;
use ImoviewImporter\Core\Cron\Lock;
use ImoviewImporter\Core\Cron\RetryManager;

$cron_scheduler = new Scheduler();
$lock = new Lock();
$retry_manager = new RetryManager();

$is_cron_running = $cron_scheduler->is_cron_running();
$is_locked = $lock->is_locked();
$last_success = get_option('imoview_last_cron_success', 0);
$retry_status = $retry_manager->get_status();
?>

<div class="wrap">
    <h1><?php _e('Imoview Importer'); ?></h1>

    <!-- Cron Status -->
    <div class="card">
        <h2><?php _e('Status do Sistema'); ?></h2>
        
        <!-- Cron Schedule Status -->
        <p>
            <strong><?php _e('Agendamento:'); ?></strong>
            <?php if ($is_cron_running): ?>
                <span class="status-ok"><?php _e('Ativo'); ?></span>
            <?php else: ?>
                <span class="status-error"><?php _e('Inativo'); ?></span>
            <?php endif; ?>
        </p>

        <!-- Lock Status -->
        <p>
            <strong><?php _e('Estado:'); ?></strong>
            <?php if ($is_locked): ?>
                <span class="status-warning"><?php _e('Em execução'); ?></span>
            <?php else: ?>
                <span class="status-ok"><?php _e('Disponível'); ?></span>
            <?php endif; ?>
        </p>

        <!-- Last Success -->
        <p>
            <strong><?php _e('Última execução bem-sucedida:'); ?></strong>
            <?php echo $last_success ? date('d/m/Y H:i:s', $last_success) : __('Nunca'); ?>
        </p>

        <!-- Retry Status -->
        <?php if ($retry_status['has_retries']): ?>
            <div class="notice notice-warning">
                <p>
                    <?php printf(
                        __('Tentativas de recuperação: %d de %d'),
                        $retry_status['count'],
                        RetryManager::MAX_RETRIES
                    ); ?>
                </p>
                <p>
                    <?php printf(
                        __('Próxima tentativa: %s'),
                        date('d/m/Y H:i:s', $retry_status['next_attempt'])
                    ); ?>
                </p>
            </div>
        <?php endif; ?>

        <?php if ($last_success && (time() - $last_success) > (HOUR_IN_SECONDS * 2)): ?>
            <div class="notice notice-warning">
                <p><?php _e('Aviso: A última execução bem-sucedida foi há mais de 2 horas.'); ?></p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Import Log -->
    <div class="card">
        <h2><?php _e('Log de Importação'); ?></h2>
        <?php
        $log = get_option('imoview_importer_log', []);
        if (!empty($log)): ?>
            <pre><?php echo esc_html(implode("\n", array_slice($log, -50))); ?></pre>
        <?php else: ?>
            <p><?php _e('Nenhum log disponível.'); ?></p>
        <?php endif; ?>
    </div>

    <!-- Manual Import Form -->
    <div class="card">
        <h2><?php _e('Importação Manual'); ?></h2>
        <form method="post">
            <?php
            wp_nonce_field('imoview_manual_import', 'imoview_nonce');
            submit_button(
                $is_locked ? __('Importação em Andamento...') : __('Forçar Atualização'),
                'primary',
                'force_update',
                false,
                ['disabled' => $is_locked]
            );

            if (isset($_POST['force_update']) && check_admin_referer('imoview_manual_import', 'imoview_nonce')) {
                try {
                    $cron_handler = new Handler();
                    $cron_handler->force_run();
                    echo '<div class="notice notice-success"><p>' . 
                        __('Atualização forçada concluída com sucesso.') . 
                        '</p></div>';
                } catch (\Exception $e) {
                    echo '<div class="notice notice-error"><p>' . 
                        esc_html(__('Erro: ') . $e->getMessage()) . 
                        '</p></div>';
                }
            }
            ?>
        </form>
    </div>
</div>

<style>
.card {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    margin-bottom: 20px;
    padding: 15px;
}
.status-ok {
    color: #46b450;
    font-weight: bold;
}
.status-error {
    color: #dc3232;
    font-weight: bold;
}
.status-warning {
    color: #ffb900;
    font-weight: bold;
}
</style>