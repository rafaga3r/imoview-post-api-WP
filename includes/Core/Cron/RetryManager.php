<?php
namespace ImoviewImporter\Core\Cron;

/**
 * Manages retry attempts for failed imports
 */
class RetryManager {
    const RETRY_OPTION = 'imoview_import_retries';
    const MAX_RETRIES = 3;
    const RETRY_DELAY = 300; // 5 minutes

    /**
     * Record a failed attempt
     */
    public function record_failure(): void {
        $retries = get_option(self::RETRY_OPTION, []);
        
        if (empty($retries)) {
            $retries = [
                'count' => 1,
                'last_attempt' => time(),
                'first_failure' => time()
            ];
        } else {
            $retries['count']++;
            $retries['last_attempt'] = time();
        }

        update_option(self::RETRY_OPTION, $retries);
    }

    /**
     * Clear retry history after successful import
     */
    public function clear(): void {
        delete_option(self::RETRY_OPTION);
    }

    /**
     * Check if we should retry
     */
    public function should_retry(): bool {
        $retries = get_option(self::RETRY_OPTION, []);
        
        if (empty($retries)) {
            return true;
        }

        // Check if we've exceeded max retries
        if ($retries['count'] >= self::MAX_RETRIES) {
            return false;
        }

        // Check if enough time has passed since last attempt
        return (time() - $retries['last_attempt']) >= self::RETRY_DELAY;
    }

    /**
     * Get retry status information
     */
    public function get_status(): array {
        $retries = get_option(self::RETRY_OPTION, []);
        
        if (empty($retries)) {
            return [
                'has_retries' => false,
                'count' => 0,
                'next_attempt' => 0
            ];
        }

        return [
            'has_retries' => true,
            'count' => $retries['count'],
            'next_attempt' => $retries['last_attempt'] + self::RETRY_DELAY,
            'first_failure' => $retries['first_failure']
        ];
    }
}