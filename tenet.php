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

class Tenet {
    public function run() {
        // Optimization: Only load admin logic if we are in the admin dashboard.
        // This ensures zero overhead on the frontend.
        if ( is_admin() ) {
            require_once TENET_PATH . 'includes/class-tenet-admin.php';
            $admin = new Tenet_Admin();
            $admin->init();
        }
    }
}

$tenet = new Tenet();
$tenet->run();
