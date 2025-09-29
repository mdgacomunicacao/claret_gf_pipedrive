<?php
/**
 * Executa uma requisição para a API do Pipedrive.
 *
 * @param string $endpoint Endpoint da API, ex: 'v2/persons'.
 * @param string $method Método HTTP: 'GET', 'POST', 'PUT', etc.
 * @param array|null $data Array de dados a enviar (opcional).
 * @return array|false Retorna array decodificado ou false em caso de erro.
 */
function pipedrive_api_request($endpoint, $method = 'GET', $data = null) {
    $api_token = GTPD_API_TOKEN;
    $company_domain = GTPD_COMPANY_DOMAIN;

    $url = 'https://' . $company_domain . '.pipedrive.com/api/' . $endpoint;
    
    $ch = curl_init();
    
    // Prepara os parâmetros da requisição
    $query_params = ['api_token' => $api_token];
    
    if ($method === 'GET' && !empty($data)) {
        $query_params = array_merge($query_params, $data);
    }
    
    $url .= '?' . http_build_query($query_params);
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

    $headers = ['Accept: application/json'];

    if (in_array($method, ['POST', 'PUT', 'PATCH']) && !empty($data)) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        $headers[] = 'Content-Type: application/json';
    }

    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

    $output = curl_exec($ch);
    $error = curl_error($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($error) {
        error_log("Pipedrive API Error: $error");
        return false;
    }

    $result = json_decode($output, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("JSON decode error: " . json_last_error_msg());
        return false;
    }

    return $result;
}