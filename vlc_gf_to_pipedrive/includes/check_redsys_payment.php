<?php
/**
 * Handles Redsys payment callbacks and updates Pipedrive deals
 */

add_action('init', 'handle_redsys_payment_callback');

function handle_redsys_payment_callback() {
    // Verificar se é um callback do Redsys
    if (!isset($_GET['rfg_return']) || !isset($_GET['Ds_MerchantParameters'])) {
        return;
    }

    error_log("=== REDSYS CALLBACK DETECTADO ===");

    try {
        // Decodificar parâmetros do Redsys
        $merchant_params = base64_decode($_GET['Ds_MerchantParameters']);
        $data = json_decode($merchant_params, true);
        
        error_log("Dados Redsys: " . print_r($data, true));

        // Verificar se tem MerchantData
        if (empty($data['Ds_MerchantData'])) {
            error_log("Erro: Ds_MerchantData não encontrado");
            return;
        }

        // DECODIFICAR URL encoding primeiro!
        $merchant_data = urldecode($data['Ds_MerchantData']); // Agora será "15|1"
        
        error_log("MerchantData decodificado: " . $merchant_data);

        // Extrair entry_id do MerchantData (formato: "15|1")
        $parts = explode('|', $merchant_data);
        
        if (count($parts) < 2) {
            error_log("Erro: Formato inválido do MerchantData: " . $merchant_data);
            return;
        }

        $entry_id = $parts[0];  // "15"
        $form_id = $parts[1];   // "1"
        
        error_log("Entry ID: $entry_id, Form ID: $form_id");

        // Verificar se o pagamento foi aprovado
        if ($data['Ds_Response'] === '0000') {
            error_log("Pagamento aprovado para entry: $entry_id");
            
            // Processar pagamento bem-sucedido
            process_successful_payment($entry_id, $data);
            
        } else {
            error_log("Pagamento recusado. Response: " . $data['Ds_Response']);
        }

    } catch (Exception $e) {
        error_log("Erro no processamento do callback Redsys: " . $e->getMessage());
    }
}

/**
 * Processa pagamento bem-sucedido
 */
function process_successful_payment($entry_id, $redsys_data) {
    // Buscar a entry no Gravity Forms
    $entry = GFAPI::get_entry($entry_id);
    
    if (is_wp_error($entry)) {
        error_log("Erro ao buscar entry $entry_id: " . $entry->get_error_message());
        return;
    }

    error_log("Entry encontrada: " . print_r($entry, true));

    // Recuperar o deal_id do campo hidden (campo 38)
    $deal_id = rgar($entry, 38);
    
    if (empty($deal_id)) {
        error_log("Deal ID não encontrado na entry $entry_id");
        
        // Debug: mostrar todos os campos da entry
        error_log("Todos os campos da entry:");
        foreach ($entry as $key => $value) {
            if (!empty($value)) {
                error_log("Campo $key: $value");
            }
        }
        return;
    }

    error_log("Deal ID encontrado: $deal_id");

    // Dados para atualizar no Pipedrive
    $deal_update = [
        'stage_id' => 4, // Pago validado
        '80305b87b0b7ea7a091263039d051924fa660b95' => 'Pago OK', // Campo Redsys
    ];

    // Atualizar o deal no Pipedrive
    $updated = update_deal_in_pipedrive($deal_id, $deal_update);
    
    if ($updated) {
        error_log("✅ Deal $deal_id movido para stage 4 - Pago validado");
        
        // Opcional: Atualizar a entry do Gravity Forms
        GFAPI::update_entry_property($entry_id, 'payment_status', 'Approved');
        
    } else {
        error_log("❌ Falha ao atualizar deal $deal_id");
    }
}
