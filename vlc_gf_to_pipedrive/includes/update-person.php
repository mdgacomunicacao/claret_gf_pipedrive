<?php
/**
 * Atualiza uma pessoa existente no Pipedrive.
 *
 * @param int   $person_id   ID da pessoa a ser atualizada.
 * @param array $person_data Dados da pessoa (nome, email, telefone, etc).
 * @return bool Retorna true se atualizado com sucesso, false em caso de erro.
 */
function update_person_in_pipedrive( $person_id, $person_data ) {
    if ( empty( $person_id ) || empty( $person_data ) ) {
        error_log('update_person_in_pipedrive: ID ou dados da pessoa vazios.');
        return false;
    }

    $endpoint = 'v1/persons/' . $person_id;
    $response = pipedrive_api_request($endpoint, 'PUT', $person_data);

    if ( ! $response || empty($response['success']) || $response['success'] !== true ) {
        $error_msg = isset($response['error']) ? $response['error'] : 'Erro desconhecido';
        error_log("update_person_in_pipedrive: Falha ao atualizar pessoa. ID: $person_id. Erro: $error_msg");
        
        // Log adicional para debugging
        if (isset($response['error_info'])) {
            error_log("update_person_in_pipedrive: Error Info: " . $response['error_info']);
        }
        
        error_log("update_person_in_pipedrive: Dados enviados: " . print_r($person_data, true));
        return false;
    }

    error_log("update_person_in_pipedrive: Pessoa atualizada com sucesso. ID: $person_id");
    return true;
}