<?php
namespace ImoviewImporter\Core;

class Api {
    private $api_url = 'https://api.imoview.com.br/Imovel/RetornarImoveisDisponiveis';
    private $api_key = 'dfded630e300dd02806af6afb7b21c24';

    public function fetch_data($page) {
        $args = [
            'headers' => [
                'chave' => $this->api_key,
                'Content-Type' => 'application/json',
                'accept' => 'application/json'
            ],
            'body' => json_encode([
                'finalidade' => 2,
                'numeroPagina' => $page,
                'numeroRegistros' => 20
            ]),
            'timeout' => 30
        ];

        $response = wp_remote_post($this->api_url, $args);
        
        if (is_wp_error($response)) {
            (new Logger())->log(__('Erro ao acessar a API: ') . $response->get_error_message());
            return false;
        }

        return json_decode(wp_remote_retrieve_body($response), true);
    }
}