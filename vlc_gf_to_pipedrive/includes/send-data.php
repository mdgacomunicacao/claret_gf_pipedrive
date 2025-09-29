<?php

add_action( 'gform_after_submission', 'send_data_to_pipedrive', 10, 2 );

/**
 * Manipula a criação da Person e Deal ao mudar para página 2
 */
add_action( 'gform_post_paging', function( $form, $source_page_number, $current_page_number ) {
    if ( $form['id'] != 1 ) {
        return $form;
    }

    if ( $current_page_number == 2 ) {
        error_log("=== INICIANDO PIPEDRIVE NO PAGE CHANGE ===");

        $entry = GFFormsModel::get_current_lead();

        $existing_person_id = rgar( $entry, 37 );
        $existing_deal_id   = rgar( $entry, 38 );

        if ( ! empty( $existing_person_id ) && ! empty( $existing_deal_id ) ) {
            error_log("IDs já existem. Person: $existing_person_id, Deal: $existing_deal_id");
            return $form;
        }

        // Monta person
        $person = [
            'name' => rgar($entry, 1) . ' ' . rgar($entry, 3)
        ];

        $email_value = rgar($entry, 4);
        if ( ! empty( $email_value ) ) {
            $person['email'] = [
                [ 'value' => $email_value, 'primary' => true, 'label' => 'work' ]
            ];
        }

        $phone_value = rgar($entry, 5);
        if ( ! empty( $phone_value ) ) {
            $person['phone'] = [
                [ 'value' => $phone_value, 'primary' => true, 'label' => 'work' ]
            ];
        }

        error_log("Dados da Person: " . print_r($person, true));

        // Busca ou cria
        $person_id = get_person_by_email( $email_value );
        if ( $person_id ) {
            error_log("Person encontrada. ID: " . $person_id);
            update_person_in_pipedrive( $person_id, $person );
        } else {
            error_log("Criando nova Person...");
            $person_id = create_person_in_pipedrive( $person );
        }

        if ( ! $person_id ) {
            error_log("Erro: Person não criada/atualizada");
            return $form;
        }

        // Cria Deal
        $deal = [
            'title' => 'Donacion ' . rgar($entry, 1) . ' ' . rgar($entry, 3),
            'stage_id' => 1, // Donación iniciada - primeiro estágio
        ];
        $deal_id = create_deal_in_pipedrive( $deal, $person_id );

        if ( ! $deal_id ) {
            error_log("Erro: Deal não criado");
            return $form;
        }

        // Aqui não dá para usar update_entry_field ainda → usamos $_POST
        $_POST['input_37'] = $person_id;
        $_POST['input_38'] = $deal_id;

        error_log("=== SUCESSO: Person ID: $person_id, Deal ID: $deal_id ===");
    }

    return $form;
}, 10, 3 );

/**
 * Função para o envio final - atualiza com dados completos e estágio correto
 */
function send_data_to_pipedrive( $entry, $form ) {
    error_log("=== INICIANDO PIPEDRIVE NO ENVIO FINAL ===");

    // Recupera os IDs dos hidden fields
    $person_id = rgar( $entry, 37 );
    $deal_id = rgar( $entry, 38 );
    
    error_log("IDs recuperados - Person: $person_id, Deal: $deal_id");

    // Se não tem IDs, cria agora (fallback)
    if ( empty( $person_id ) || empty( $deal_id ) ) {
        error_log( "IDs não encontrados, criando Person e Deal..." );
        
        // Chama a função do page change para criar os registros
        handle_pipedrive_on_page_change( $form['id'], 1, 2 );
        
        // Recupera os IDs novamente
        $person_id = rgar( $entry, 37 );
        $deal_id = rgar( $entry, 38 );
        
        error_log("IDs após criação - Person: $person_id, Deal: $deal_id");
    }

    // LÓGICA ATUALIZADA PARA ESTÁGIOS
    $date = new DateTime('now', new DateTimeZone('UTC'));
    $formattedDate = $date->format('Y-m-d\TH:i:s\Z');

    $pagoRedsys = '';
    $metodoDePago = rgar( $entry, 10 );
    
    // DEFINE O STAGE_ID CORRETAMENTE
    $stage_id = 3; // Padrão: Proceso de pago iniciado (Transferencia)
    
    $modalidad_pago = rgar( $entry, 8 );
    $metodo_pago = rgar( $entry, 10 );
    
    if( $modalidad_pago == 'Fraccionada' ) {
        $stage_id = 7; // Domiciliación en trámite
        $metodoDePago = 'Domiciliación';
    } elseif( $modalidad_pago == 'Puntual' ) {
        if( $metodo_pago == 'Redsys' ) {
            $stage_id = 3; // Pago validado
        } else {
            $stage_id = 3; // Proceso de pago iniciado (Transferencia)
        }
    }

    if( $metodo_pago == 'Redsys' ) {
        $pagoRedsys = 'Pago KO';
    }

    $titleDeal = 'Donacion ' . rgar($entry, 1) . ' ' . rgar($entry, 3);

    $person = [
        'name' => rgar($entry, 1) . ' ' . rgar($entry, 3)
    ];

    // Dados adicionais para Fraccionada
    if( $modalidad_pago == 'Fraccionada' ) {
        if( !empty(rgar($entry,35)) && !empty(rgar($entry,36))  ) {
            $person['name'] = rgar($entry, 35) . ' ' . rgar($entry, 36);
            $titleDeal = 'Donacion ' .rgar($entry, 35) . ' ' . rgar($entry, 36);
        }

        // Campos de endereço, DNI, IBAN
        $direccion = rgar($entry, 17);
        $pais = rgar($entry, 20);
        $codigo_postal = rgar($entry, 21);
        $ciudad = rgar($entry, 18);

        if (!empty($direccion)) {
            $direccion_completa = $direccion;
            if (!empty($ciudad)) $direccion_completa .= ', ' . $ciudad;
            if (!empty($codigo_postal)) $direccion_completa .= ', ' . $codigo_postal;
            if (!empty($pais)) $direccion_completa .= ', ' . $pais;
            $person['3887bd1a96a0bac504dcbd51ea3321c8a42cd301'] = $direccion_completa;
        }

        if (!empty($pais)) {
            $person['a4b0610d62a78f94d0ac593770ebea797e08eb5d'] = $pais;
        }

        $dni_value = rgar($entry, 22);
        if (!empty($dni_value)) {
            $person['279c41897ae0f687739b58a54a6d0e13cbf48912'] = $dni_value;
        }

        $iban = rgar($entry, 30);
        if (!empty($iban)) {
            $person['92b5528ebefb1a2b930df7de230f59153ad2ed43'] = $iban;
        }
    }

    // Dados básicos de contato
    $email_value = rgar($entry, 4);
    if (!empty($email_value)) {
        $person['email'] = [
            ['value' => $email_value, 'primary' => true, 'label' => 'work']
        ];
    }

    $phone_value = rgar($entry, 5);
    if (!empty($phone_value)) {
        $person['phone'] = [
            ['value' => $phone_value, 'primary' => true, 'label' => 'work']
        ];
    }

    // Atualiza Person com dados completos
    if ($person_id) {
        error_log("Atualizando Person com dados completos...");
        $updated = update_person_in_pipedrive($person_id, $person);
        if ($updated) {
            error_log("Person atualizada com sucesso");
        } else {
            error_log("Falha ao atualizar Person");
        }
    }

    // Atualiza Deal com dados completos E NOVO ESTÁGIO
    $deal = [
        'title' => $titleDeal,
        'value' => rgar($entry, 34),
        'currency' => GTPD_CURRENCY,
        'add_time' => $formattedDate,
        'stage_id' => $stage_id, // AGORA COM ESTÁGIO CORRETO
        'f761fc30acd9520a3467170e64586dfe3db2e1d1' => 'Donación',
        'c13096872fded17798a71c96c6d3f373fd8e54ed' => $modalidad_pago,
        'c23b423a74785f16dcc0dacd261f1bce6736deb7' => $metodoDePago,
        'ef919e27702702efc77b0f88555ccd5334d8f868' => rgar($entry, 12),
        '80305b87b0b7ea7a091263039d051924fa660b95' => $pagoRedsys,
        'f34578aaac0f17847d4788f5e6c2b34b932f751b' => 'Sí',
    ];

    // LOG PARA DEBUG DO ESTÁGIO
    error_log("Stage ID definido: {$stage_id}");
    error_log("Modalidad: {$modalidad_pago}, Método: {$metodo_pago}");

    if ($deal_id) {
        error_log("Atualizando Deal com dados completos e stage_id: {$stage_id}");
        $updated_deal = update_deal_in_pipedrive($deal_id, $deal);
        if ($updated_deal) {
            error_log("Deal atualizado com sucesso. Novo stage: {$stage_id}");
        } else {
            error_log("Falha ao atualizar Deal");
        }
    }

    error_log("=== FINALIZADO PIPEDRIVE ENVIO FINAL ===");

    return array(
        'person_id' => $person_id,
        'deal_id'   => $deal_id,
    );
}