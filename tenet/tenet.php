<?php
/**
 * Plugin Name: Tenet
 * Description: Gerador de conteúdo autônomo e inteligente com memória e integração visual.
 * Version: 1.0.0
 * Author: Jules
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'TENET_PATH', plugin_dir_path( __FILE__ ) );
define( 'TENET_URL', plugin_dir_url( __FILE__ ) );

require_once TENET_PATH . 'includes/class-tenet-admin.php';
require_once TENET_PATH . 'includes/class-tenet-generator.php';

class Tenet {
    public function run() {
        $admin = new Tenet_Admin();
        $admin->init();
    }
}

$tenet = new Tenet();
$tenet->run();
