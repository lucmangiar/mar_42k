<?php
if ( !class_exists('Sigma_Payment_Tracker') ) :
/**
 * Sigma Payment Tracker.
 *
 * Allows users to view their payment status. Some payment processors are sent
 * a url to the tracker, after finishing the payment there are redirected to
 * tracker to view their payment status.
 *
 * @package     SigmaEvents
 * @subpackage  Utilities
 */
class Sigma_Payment_Tracker{
    // Sigma Rewrite Endpoint and Tag
    private $endpoint = 'sigma-events';
    private $tag = 'sigma';
    private $registration_table;

    /**
     * Sigma Payment Tracker Constructor.
     */
    function __construct( $registration_table ){
        $this->registration_table = $registration_table;

        // A redirect to serve product post types.
        add_action('template_redirect', array($this, 'redirect_tracker_template'));

        // Add rewrite rules rules for sigma endpoint.
        add_action('init', array($this, 'tracker_rewrite'));
    }

    /**
     * Add rewrites for the tracker.
     */
    function tracker_rewrite(){
        $sigma_tracker = $this->endpoint . '/tracker/?$';
        add_rewrite_rule($sigma_tracker, 'index.php?' . $this->tag . '=tracker', 'top');
    }

    /**
     * Redirect tracker templates.
     */
    function redirect_tracker_template(){
        // Not the sigma tag is present?
        global $wp_query;
        if(!isset($wp_query->query_vars[$this->tag]))
            return;

        $query_var = $wp_query->query_vars[$this->tag];
        if($query_var == 'tracker'):
            add_action('wp_enqueue_scripts', array($this, 'enqueue'));
            require SIGMA_PATH . 'templates/sigma-tracker-template.php';
            die();
        endif;
    }

    /**
     * Enqueue Styles and Scripts
     *
     * Enqueue the styles and scripts for the tracker
     * page. These are same as for the other Sigma pages.
     */
    function enqueue(){
        // Tracker page stylesheet.
        wp_register_style('sigma-events-style', SIGMA_URL . 'css/sigma-eventsisdi.css');
        wp_enqueue_style('sigma-events-style');

        // Print Stylesheet.
        wp_register_style('sigma-events-print-style',
            SIGMA_URL . 'css/sigma-events-print.css',
            false,
            false,
            'print'    );
        wp_enqueue_style('sigma-events-print-style');

        // Tracker page javascripts.
        wp_register_script('sigma-events-script',
            SIGMA_URL . 'js/sigma-events.js', array('print-preview-script'), false, true);
        wp_enqueue_script('sigma-events-script');
    }
}
endif;
?>
