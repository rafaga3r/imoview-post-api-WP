<?php
namespace ImoviewImporter\Core;

class PostType {
    public function __construct() {
        add_action('init', [$this, 'register']);
    }

    public function register() {
        register_post_type('apartamentos', [
            'labels' => [
                'name' => __('Apartamentos'),
                'singular_name' => __('Apartamento')
            ],
            'public' => true,
            'has_archive' => true,
            'supports' => ['title', 'custom-fields']
        ]);
    }
}