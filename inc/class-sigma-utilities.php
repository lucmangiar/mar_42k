<?php
if ( !class_exists('Sigma_Utilities') ) :
/**
 * Sigma Utilities
 *
 * @package     SigmaEvents
 * @subpackage  Utilities
 * @since       version 3.6
 */
class Sigma_Utilities{
    /**
     * Utilities Service Endpoint to External World
     */
    private $endpoint = 'utilities';

    /**
     * Sigma Utilities Constructor
     */
    function __construct(){
        /**
         * Add rewrite rules rules for sigma utilities.
         */
        add_action('init', array($this, 'sigma_utility_rewrite'));

        /**
         * A redirect to serve sigma utilities.
         */
        add_action('template_redirect', array($this, 'redirect_sigma_utilities'));
    }

    /**
     * Sigma Utility Rewrite
     */
    function sigma_utility_rewrite(){
        $tourism_list     = $this->endpoint . '/tourism_list/?$';
        add_rewrite_rule($tourism_list,   'index.php?' . $this->endpoint . '=tourism_list',    'top');
        add_rewrite_tag('%' . $this->endpoint . '%', '([^&]+)');
    }

    /**
     * Redirect Utility Requests
     */
    function redirect_sigma_utilities(){
        global $wp_query;
        if(!isset($wp_query->query_vars[$this->endpoint]))
            return;

        $query_var = $wp_query->query_vars[$this->endpoint];
        if($query_var == 'tourism_list'):
            $this->output_tourism_info_opt_in_log();
        endif;
        exit;
    }

    /**
     * Output Sigma Tourism Opt-in Details
     */
    function output_tourism_info_opt_in_log(){
        $filename  = SIGMA_PATH . 'logs/tourism_info_opt_in.log';
        $file_name = 'tourism_info_opt_in.log';

  		if( ! file_exists($filename) ):
            echo 'file removed';
            exit;
  		else:
            $content = file_get_contents( $filename );
  		endif;

  		header("Content-type: text/x-csv");
  		header("Content-Disposition: attachment; filename=".$file_name);
        echo $content;
        exit;
    }
}
endif;
