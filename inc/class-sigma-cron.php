<?php
if ( !class_exists('Sigma_Cron') ) :
/**
 * Sigma Cron Daemon
 *
 * @package     SigmaEvents
 * @subpackage  Utilities
 * @since       version 3.9
 */
class Sigma_Cron{
    /**
     * Sigma Processor Options
     */
    private $processor_options;

    /**
     * CuentaDigital Interval
     */
    private $cuentadigital_interval;

    /**
     * Sigma Cron Constructor
     */
    function __construct(){
        $this->processor_options = get_option('sigma_processor_options');
        $this->cuentadigital_interval = $this->processor_options['cuentadigital']['cron_interval'];
        add_filter( 'cron_schedules', array($this, 'add_cuentadigital_interval'));
        add_action( 'cuentadigital_cron_hook', array($this, 'cuentadigital_cron'));
    }

    /**
     * Install Cron Jobs on Plugin Activation.
     */
    function install_cron(){
        wp_schedule_event(
            time(),
            'cuentadigital_interval',
            'cuentadigital_cron_hook'
        );
    }

    /**
     * Uninstall Cron Jobs on Plugin Uninstallation
     */
    function uninstall_cron(){
        wp_clear_scheduled_hook( 'cuentadigital_cron_hook' );
    }

    /**
     * Add CuentaDigital Interval to Cron Schedules Array
     */
    function add_cuentadigital_interval($schedules){
        $schedules['cuentadigital_interval'] = array(
            'interval' => $this->cuentadigital_interval,
            'display' => __( 'CuentaDigital Interval', 'se' )
        );
        return $schedules;
    }

    /**
     * CuentaDigital Cron
     */
    function cuentadigital_cron(){
        global $sigma_events;
        $sigma_events->payments_cuentadigital->run_cron($this->cuentadigital_interval);
    }

    /**
     * Reschedule Cron Event
     */
    function reschedule_event($interval){
        $this->cuentadigital_interval = $interval;
        wp_clear_scheduled_hook( 'cuentadigital_cron_hook' );
        if(true):
        wp_schedule_event(
            current_time( 'timestamp' ),
            'cuentadigital_interval',
            'cuentadigital_cron_hook'
        );
        endif;
    }

    /**
     * Get CuentaDigital Cron Details
     */
    function get_cuentadigital_schedule(){
        $next = wp_next_scheduled('cuentadigital_cron_hook');
        $output  = '<p>Current Time: ' . current_time('mysql') . '</p>';
        $output .= '<p>Next Scheduled Run: ' . date('Y-m-d H:i:s', $next) . '</p>';
        return $output;
    }

}
endif;
?>
