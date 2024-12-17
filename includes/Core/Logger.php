<?php
namespace ImoviewImporter\Core;

class Logger {
    public function log($message) {
        $timestamp = date('Y-m-d H:i:s');
        $entry = "[$timestamp] $message";

        $stored_log = get_option('imoview_importer_log', []);
        $stored_log[] = $entry;

        update_option('imoview_importer_log', $stored_log);
    }
}