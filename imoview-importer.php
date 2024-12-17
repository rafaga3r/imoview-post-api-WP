<?php
/**
 * Plugin Name: Imoview Importer
 * Description: Importa dados da API Imoview e cria posts personalizados no WordPress.
 * Version: 1.10
 * Author: Rafaga Studio
 */

if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('IMOVIEW_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('IMOVIEW_PLUGIN_URL', plugin_dir_url(__FILE__));

// Autoload classes
spl_autoload_register(function ($class) {
    $prefix = 'ImoviewImporter\\';
    $base_dir = IMOVIEW_PLUGIN_DIR . 'includes/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

// Initialize the plugin
function imoview_importer_init() {
    $plugin = new ImoviewImporter\Core\Plugin();
    $plugin->init();
}

add_action('plugins_loaded', 'imoview_importer_init');