<?php
/**
 * Busca uma pessoa no Pipedrive pelo email.
 *
 * @param string $email Email da pessoa.
 * @return int|false Retorna o ID da pessoa se encontrada, ou false se não existir.
 */
function get_person_by_email( $email ) {

    if ( empty( $email ) ) {
        return false;
    }

    // Monta os parâmetros da busca
    $params = array(
        'term' => $email,
        'fields' => 'email',
        'exact_match' => 'true',
    );

    // Chama a API Pipedrive
    $response = pipedrive_api_request( 'v1/persons/search', 'GET', $params );

    error_log("Search response for email {$email}: " . print_r($response, true));

    if ( ! $response || empty($response['success']) || empty($response['data']['items']) ) {
        // Nenhum resultado encontrado
        error_log("No person found with email: {$email}");
        return false;
    }

    // Procura o primeiro item que tenha email exato
    foreach ( $response['data']['items'] as $item ) {
        $person = $item['item'];
        
        // Verifica se a pessoa tem emails no formato retornado pela API
        if (!empty($person['emails'])) {
            foreach ($person['emails'] as $email_value) {
                if ($email_value === $email) {
                    error_log("Person found with email {$email}: ID {$person['id']}");
                    return $person['id'];
                }
            }
        }
        
        // Verificação alternativa - primary_email
        if (!empty($person['primary_email']) && $person['primary_email'] === $email) {
            error_log("Person found with primary_email {$email}: ID {$person['id']}");
            return $person['id'];
        }
    }

    error_log("Exact email match not found for: {$email}");
    return false;
}