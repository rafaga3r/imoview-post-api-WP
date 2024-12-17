<?php
namespace ImoviewImporter\Core;

class Importer {
    private $api;
    private $logger;
    private $post_processor;

    public function __construct() {
        $this->api = new Api();
        $this->logger = new Logger();
        $this->post_processor = new PostProcessor();
    }

    /**
     * Run the import process
     */
    public function run_import() {
        try {
            $page = 1;
            $has_more_data = true;
            $api_codigos = [];
            $total_processed = 0;

            while ($has_more_data) {
                $response = $this->api->fetch_data($page);

                if (!$response) {
                    throw new \Exception(__('Falha ao obter dados da API'));
                }

                if (isset($response['lista']) && !empty($response['lista'])) {
                    foreach ($response['lista'] as $item) {
                        $api_codigos[] = $item['codigo'];
                        $this->process_item($item);
                        $total_processed++;
                    }
                    $page++;
                } else {
                    $has_more_data = false;
                }

                // Add a small delay to prevent overwhelming the API
                usleep(100000); // 100ms delay
            }

            $this->remove_missing_posts($api_codigos);
            $this->logger->log(sprintf(
                __('Importação concluída. Total processado: %d imóveis'),
                $total_processed
            ));

        } catch (\Exception $e) {
            $this->logger->log(__('Erro durante importação: ') . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Process a single item
     */
    private function process_item($data) {
        try {
            $this->post_processor->create_or_update($data);
        } catch (\Exception $e) {
            $this->logger->log(sprintf(
                __('Erro ao processar imóvel %s: %s'),
                $data['codigo'] ?? 'unknown',
                $e->getMessage()
            ));
        }
    }

    /**
     * Remove posts that no longer exist in the API
     */
    private function remove_missing_posts($api_codigos) {
        $query = new \WP_Query([
            'post_type' => 'apartamentos',
            'posts_per_page' => -1,
            'fields' => 'ids'
        ]);

        if ($query->have_posts()) {
            foreach ($query->posts as $post_id) {
                $codigo = get_post_meta($post_id, 'codigo', true);
                if (!in_array($codigo, $api_codigos)) {
                    wp_delete_post($post_id, true);
                    $this->logger->log(__('Post removido: ') . $codigo);
                }
            }
        }
    }
}