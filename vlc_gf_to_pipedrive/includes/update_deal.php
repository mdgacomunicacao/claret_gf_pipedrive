<?php
/**
 * Atualiza um negócio (deal) existente no Pipedrive.
 *
 * @param int   $deal_id   ID do negócio a ser atualizado.
 * @param array $deal_data Dados do negócio para atualização.
 * @return bool True em caso de sucesso, false em caso de erro.
 */
function update_deal_in_pipedrive( $deal_id, $deal_data ) {
    if ( empty( $deal_id ) || empty( $deal_data ) ) {
        error_log('update_deal_in_pipedrive: ID do negócio ou dados vazios.');
        return false;
    }

    error_log("Updating deal {$deal_id} with data: " . print_r($deal_data, true));

    $response = pipedrive_api_request("v1/deals/{$deal_id}", 'PUT', $deal_data);

    if ( ! $response || empty($response['success']) || $response['success'] !== true ) {
        $error_msg = isset($response['error']) ? $response['error'] : 'Erro desconhecido';
        error_log("update_deal_in_pipedrive: Falha ao atualizar negócio {$deal_id}. Erro: {$error_msg}");
        
        // Log adicional para debugging
        if (isset($response['error_info'])) {
            error_log("update_deal_in_pipedrive: Error Info: " . $response['error_info']);
        }
        
        return false;
    }

    error_log("update_deal_in_pipedrive: Negócio {$deal_id} atualizado com sucesso.");
    return true;
}