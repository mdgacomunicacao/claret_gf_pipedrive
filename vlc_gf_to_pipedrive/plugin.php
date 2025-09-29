<?php
/**
 * Plugin Name: Gravity Forms a Pipedrive
 * Description: Integración de Gravity Forms con Pipedrive para crear/actualizar personas y acuerdos automáticamente.
 * Version: 1.0.0
 * Author: Luan Oliveira
 * Author URI: http://volcanicinternet.com/
 * Text Domain: gravity-to-pipedrive
 * Requires Plugins: gravityforms
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'GTPD_API_TOKEN', '291fea214f0995b31cc4f9b267ef54de1efa6b6a' );
define( 'GTPD_COMPANY_DOMAIN', 'claret' );
define( 'GTPD_CURRENCY', 'EUR' );

// Carrega arquivos de funções
require_once plugin_dir_path( __FILE__ ) . 'includes/send-data.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/get-person-by-email.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/create-person.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/update-person.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/create-deal.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/pipedrive-api-request.php';