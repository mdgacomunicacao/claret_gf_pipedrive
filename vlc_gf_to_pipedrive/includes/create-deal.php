<?php
/**
 * Cria um novo negócio (deal) no Pipedrive.
 *
 * @param array $deal_data Dados do negócio.
 * @param int   $person_id ID da pessoa associada.
 * @return int|false ID do negócio ou false em caso de erro.
 */
function create_deal_in_pipedrive( $deal_data, $person_id ) {
    if ( empty( $deal_data ) || empty( $person_id ) ) {
        error_log('create_deal_in_pipedrive: Dados do negócio ou ID da pessoa vazios.');
        return false;
    }

    // Adiciona a pessoa ao dados do negócio
    $deal_data['person_id'] = $person_id;

    error_log("Creating deal with data: " . print_r($deal_data, true));

    $response = pipedrive_api_request('v1/deals', 'POST', $deal_data);

    if ( ! $response || empty($response['success']) || $response['success'] !== true ) {
        $error_msg = isset($response['error']) ? $response['error'] : 'Erro desconhecido';
        error_log("create_deal_in_pipedrive: Falha ao criar negócio. Erro: $error_msg");
        
        // Log adicional para debugging
        if (isset($response['error_info'])) {
            error_log("create_deal_in_pipedrive: Error Info: " . $response['error_info']);
        }
        
        return false;
    }

    if ( empty($response['data']['id']) ) {
        error_log('create_deal_in_pipedrive: Resposta sem ID do negócio.');
        error_log("Resposta: " . print_r($response, true));
        return false;
    }

    $deal_id = $response['data']['id'];
    error_log("create_deal_in_pipedrive: Negócio criado com sucesso. ID: " . $deal_id);
    
    return $deal_id;
}