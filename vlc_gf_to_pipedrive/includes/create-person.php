<?php
/**
 * Cria uma nova pessoa no Pipedrive.
 *
 * @param array $person_data Dados da pessoa (nome, email, telefone, etc).
 * @return int|false Retorna o ID da pessoa criada ou false em caso de erro.
 */
function create_person_in_pipedrive($person_data) {
    if (empty($person_data)) {
        error_log('create_person_in_pipedrive: Dados da pessoa vazios.');
        return false;
    }

    $response = pipedrive_api_request('v1/persons', 'POST', $person_data);

    if (!$response || !isset($response['success']) || $response['success'] !== true) {
        $error_msg = isset($response['error']) ? $response['error'] : 'Erro desconhecido';
        error_log("create_person_in_pipedrive: Falha ao criar pessoa. Erro: $error_msg");
        error_log("Resposta completa: " . print_r($response, true));
        return false;
    }

    if (empty($response['data']['id'])) {
        error_log('create_person_in_pipedrive: Resposta sem ID da pessoa.');
        error_log("Resposta: " . print_r($response, true));
        return false;
    }

    return $response['data']['id'];
}