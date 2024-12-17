<?php
namespace ImoviewImporter\Core\Cron;

/**
 * Handles cron job locking to prevent concurrent runs
 */
class Lock {
    const LOCK_OPTION = 'imoview_cron_lock';
    const LOCK_TIMEOUT = 3600; // 1 hour

    /**
     * Acquire a lock for the cron job
     */
    public function acquire(): bool {
        $lock = get_option(self::LOCK_OPTION);
        
        if ($lock) {
            // Check if the existing lock has expired
            if (time() - $lock['timestamp'] > self::LOCK_TIMEOUT) {
                $this->release();
            } else {
                return false;
            }
        }

        return update_option(self::LOCK_OPTION, [
            'timestamp' => time(),
            'pid' => getmypid()
        ]);
    }

    /**
     * Release the cron job lock
     */
    public function release(): bool {
        return delete_option(self::LOCK_OPTION);
    }

    /**
     * Check if the cron job is locked
     */
    public function is_locked(): bool {
        $lock = get_option(self::LOCK_OPTION);
        return $lock && (time() - $lock['timestamp'] <= self::LOCK_TIMEOUT);
    }
}