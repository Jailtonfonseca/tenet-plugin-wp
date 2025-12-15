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

        // Register Cron Event Hook
        add_action( 'tenet_cron_event', array( $this, 'process_automated_post' ) );

        // Handle Cron Scheduling on Update
        add_action( 'update_option_tenet_cron_frequency', array( $this, 'update_cron_schedule' ), 10, 3 );
    }

    public function update_cron_schedule( $old_value, $value, $option ) {
        wp_clear_scheduled_hook( 'tenet_cron_event' );

        if ( $value !== 'off' ) {
            if ( ! wp_next_scheduled( 'tenet_cron_event' ) ) {
                wp_schedule_event( time(), $value, 'tenet_cron_event' );
            }
        }
    }

    public function process_automated_post() {
        $generator = new Tenet_Generator();
        $generator->generate_automated_post();
    }
}

$tenet = new Tenet();
$tenet->run();

// Register activation hook to clear cron (clean slate)
register_deactivation_hook( __FILE__, function() {
    wp_clear_scheduled_hook( 'tenet_cron_event' );
});
