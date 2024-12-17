<?php
namespace ImoviewImporter\Core;

class PostProcessor {
    public function create_or_update($data) {
        $post_id = $this->get_post_by_codigo($data['codigo']);

        $titulo = isset($data['titulo']) ? $data['titulo'] : 'Apartamento';
        $codigo = $data['codigo'];

        $post_data = [
            'post_title' => $titulo . ' - ' . $codigo,
            'post_type' => 'apartamentos',
            'post_status' => 'publish',
            'post_name' => sanitize_title($titulo . '-' . $codigo)
        ];

        if ($post_id) {
            $post_data['ID'] = $post_id;
            wp_update_post($post_data);
        } else {
            $post_id = wp_insert_post($post_data);
        }

        $this->update_meta_data($post_id, $data);
    }

    private function update_meta_data($post_id, $data) {
        foreach ($data as $key => $value) {
            if ($key === 'fotos' && is_array($value)) {
                $this->process_fotos_meta($post_id, $value);
            } else {
                $new_meta = is_bool($value) ? (int) $value : $value;
                update_post_meta($post_id, $key, $new_meta);
            }
        }
    }

    private function process_fotos_meta($post_id, $fotos) {
        $urls_processadas = [];

        foreach ($fotos as $foto) {
            if (isset($foto['url']) && !empty($foto['url'])) {
                $url = preg_replace('/\.jpg.*/i', '.jpg', $foto['url']);
                $urls_processadas[] = $url;
            }
        }

        update_post_meta($post_id, 'fotos', implode(',', $urls_processadas));
    }

    private function get_post_by_codigo($codigo) {
        $query = new \WP_Query([
            'post_type' => 'apartamentos',
            'meta_query' => [
                [
                    'key' => 'codigo',
                    'value' => $codigo,
                    'compare' => '='
                ]
            ]
        ]);

        return $query->have_posts() ? $query->posts[0]->ID : null;
    }
}