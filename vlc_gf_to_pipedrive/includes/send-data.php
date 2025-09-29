<?php

add_action( 'gform_after_submission', 'send_data_to_pipedrive', 10, 2 );

/**
 * Orquestra o envio de dados do Gravity Forms para o Pipedrive.
 *
 * @param array $entry Dados enviados pelo Gravity Forms.
 * @param array $form  Estrutura do formulário.
 * @return array|false Retorna person + deal ou false em caso de erro.
 */

function send_data_to_pipedrive( $entry, $form ) {

    /*
    * Deal Stages:
    * 1: Donácion iniciada
    * 3: Proceso de pago iniciado
    * 4: Pago validado
    * 7: Domiciliación en trámite
    * 8: Llamada reservada
    * 9: Cualificado
    * 10: Reunión agendada
    * 11: Propuesta enviada
    * 12: En negociación
    */

    //

    // Cria objeto DateTime com timezone UTC
    $date = new DateTime('now', new DateTimeZone('UTC'));

    // Formata no padrão desejado
    $formattedDate = $date->format('Y-m-d\TH:i:s\Z');

    $pagoRedsys = '';
    $metodoDePago = rgar( $entry, 10 );
    //Proceso de pago iniciado
    $stage_id = 3;


    $titleDeal = 'Donacion ' . rgar($entry, 1) . ' ' . rgar($entry, 3);

    if( rgar( $entry, 10 ) == 'Redsys' ) {
        $pagoRedsys = 'Pago OK';
    }

    $person = [
        'name' => rgar($entry, 1) . ' ' . rgar($entry, 3)
    ];

    
    if( rgar( $entry, 8 ) == 'Fraccionada' ) {

        if( !empty(rgar($entry,35)) && !empty(rgar($entry,36))  ) {
            $person = [
                'name' => rgar($entry, 35) . ' ' . rgar($entry, 36)
            ];
            $titleDeal = 'Donacion ' .rgar($entry, 35) . ' ' . rgar($entry, 36);
            
        }

        $metodoDePago = 'Domiciliación';
        
        //Domiciliación en trámite
        $stage_id = 7; 

        //Direccion
        $direccion = rgar($entry, 17);
        $pais = rgar($entry, 20);
        $codigo_postal = rgar($entry, 21);
        $ciudad = rgar($entry, 18);

        
        if (!empty($direccion)) {
            $direccion_completa = $direccion;
            
            if (!empty($ciudad)) {
                $direccion_completa .= ', ' . $ciudad;
            }
            
            if (!empty($codigo_postal)) {
                $direccion_completa .= ', ' . $codigo_postal;
            }
            
            if (!empty($pais)) {
                $direccion_completa .= ', ' . $pais;
            }
            
            $person['3887bd1a96a0bac504dcbd51ea3321c8a42cd301'] = $direccion_completa;
            
        }

        // Campo país separado
        if (!empty($pais)) {
            $person['a4b0610d62a78f94d0ac593770ebea797e08eb5d'] = $pais;
        }

        // DNI custom field - adiciona apenas se não estiver vazio
        $dni_value = rgar($entry, 22);
        if (!empty($dni_value)) {
            $person['279c41897ae0f687739b58a54a6d0e13cbf48912'] = $dni_value;
        }

        $iban = rgar($entry, 30);
        if (!empty($iban)) {
            $person['92b5528ebefb1a2b930df7de230f59153ad2ed43'] = $iban;
        }


    } 

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

    // Endereço - adiciona apenas se algum campo não estiver vazio
    /*
    $address_value = rgar($entry, 17);
    $country_value = rgar($entry, 20);
    $postal_code_value = rgar($entry, 21);
    $locality_value = rgar($entry, 18);

    if (!empty($address_value) || !empty($country_value) || !empty($postal_code_value) || !empty($locality_value)) {
        $person['postal_address'] = [
            'value' => $address_value,
            'country' => $country_value,
            'postal_code' => $postal_code_value,
            'locality' => $locality_value
        ];
    }*/

        

    // 2. Monta os dados do negócio
    $deal = [
        'title' => $titleDeal,
        'value' => rgar($entry, 34),
        'currency' => GTPD_CURRENCY,
        'add_time' => $formattedDate,
        'stage_id' => $stage_id,
        // Tipo de Donación
        'f761fc30acd9520a3467170e64586dfe3db2e1d1' => 'Donación',
        // Modalidad de pago: Puntual o Fraccionada
        'c13096872fded17798a71c96c6d3f373fd8e54ed' => rgar($entry, 8),
        // Método de pago: Redsys, Transferencia o Domiciliación
        'c23b423a74785f16dcc0dacd261f1bce6736deb7' => $metodoDePago,
        // Número de meses (pago fraccionado)
        'ef919e27702702efc77b0f88555ccd5334d8f868' => rgar($entry, 12),
        // Pago con Redsys: Pago OK o Pago KO
        '80305b87b0b7ea7a091263039d051924fa660b95' => $pagoRedsys,
        // Pago iniciado: Sí
        'f34578aaac0f17847d4788f5e6c2b34b932f751b' => 'Sí',
    ];

    error_log("Pipedrive Person Data: " . print_r($person, true));

    $email = rgar($entry, 4);
    $person_id = get_person_by_email($email);

    if ($person_id) {
        error_log("Pessoa encontrada no Pipedrive. ID: " . $person_id);
        $updated = update_person_in_pipedrive($person_id, $person);
        if ($updated) {
            error_log("Pessoa atualizada com sucesso. ID: " . $person_id);
        } else {
            error_log("Falha ao atualizar a pessoa. ID: " . $person_id);
        }
    } else {
        error_log("Pessoa não encontrada no Pipedrive. Criando nova...");
        $person_id = create_person_in_pipedrive($person);
        if ($person_id) {
            error_log("Pessoa criada com sucesso. ID: " . $person_id);
        } else {
            error_log("Falha ao criar a pessoa no Pipedrive.");
        }
    }

    if (!$person_id) {
        error_log("Erro crítico: pessoa não criada nem atualizada. Abortando.");
        return false;
    }

    // 4. Cria o negócio vinculado à pessoa
    $deal_id = create_deal_in_pipedrive( $deal, $person_id );

    if ( ! $deal_id ) {
        return false; // Falha ao criar negócio
    }

    // 5. Retorna resultado consolidado
    return array(
        'person_id' => $person_id,
        'deal_id'   => $deal_id,
    );
}