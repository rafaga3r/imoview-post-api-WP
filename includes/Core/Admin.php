<?php
namespace ImoviewImporter\Core;

class Admin {
    public function __construct() {
        add_action('admin_menu', [$this, 'add_menu']);
    }

    public function add_menu() {
        add_menu_page(
            __('Imoview Importer'),
            __('Imoview Importer'),
            'manage_options',
            'imoview-importer',
            [$this, 'render_page']
        );
    }

    public function render_page() {
        require_once IMOVIEW_PLUGIN_DIR . 'views/admin-page.php';
    }
}