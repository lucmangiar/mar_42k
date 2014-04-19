<?php
if ( !class_exists('Sigma_Security') ) :
/**
 * Sigma Security
 *
 * @package     SigmaEvents
 * @subpackage  Utilities
 * @since       version 3.7
 */
class Sigma_Security{
    /**
     * Security Options
     */
    private $options;

    /**
     * Security Disabled?
     */
    private $disabled;

    /**
     * Sigma Security Module Constructor
     */
    function __construct(){
        $this->options = get_option('sigma_security_options');
        $sigma_options = get_option('sigma_options');
        $this->disabled = !$sigma_options['enable_security_module'];

    }

    /**
     * Main Security Checkpoint
     *
     * param string $gate Gate of which visitor tries to enter the site.
     * return true|void true if the security check passed by the visitor.
     *                       will be redirected, etc. otherwise.
     */
    function check_visitor($gate){
        /**
         * Do nothing if disabled
         */
        if($this->disabled)
            return true;

        /**
         * Current Visitor being checked
         */
        $visitor = sanitize_text_field($_SERVER['REMOTE_ADDR']);

        /**
         * A suspicious visitor?
         * Already blocked?
         */
        $blocked = $this->is_blocked_visitor($visitor, $gate);
        if($blocked):
            $blocked_ip_log   = $this->options[$gate]['blocked_ip_log'];
            $page_to_redirect = $this->options[$gate]['page_to_redirect'];
            $this->log_blocked_visitor($visitor, $gate, print_r($_POST, true), $blocked_ip_log);
            wp_redirect($page_to_redirect);
            exit;
        endif;

        $this->record_current_visitor($visitor, $gate);
        return true;
    }

    function is_blocked_visitor($visitor, $gate){
        $time_to_keep          = $this->options[$gate]['time_to_keep'];
        $allowed_attempts      = $this->options[$gate]['allowed_attempts'];
        $time_between_attempts = $this->options[$gate]['time_between_attempts'];

        $option     = $gate . '_gate_data';
        $gate_data  = get_option($option);
        $block_list = $gate_data['blocked'];

        /**
         *     Is blocked visitor?
         * (1) Blocking period expired?
         *     Block further if necessary.
         */
        if(isset($block_list[$visitor])):
            $last_access = $block_list[$visitor][1];
            $now = time();
            $time_elapsed = $now - $last_access;
            /**
             * Block period expired
             */
            if( $time_elapsed > $time_to_keep):
                unset($block_list[$visitor]);
                unset($access_list[$visitor]);
                $gate_data['blocked'] = $block_list;
                $gate_data['access'] = $access_list;
                update_option($option, $gate_data);
            /**
             * Block this visitor
             */
            else:
                $attempts = $block_list[$visitor][0] + 1;
                $block_list[$visitor] = array( $attempts, $block_list[$visitor][1] );
                $gate_data['blocked'] = $block_list;
                update_option($option, $gate_data);
                return true;
            endif;
        endif;

        /**
         * (2) Block Visits with High Frequency
         *     A new entry for block list(rapid attempts)
         */
        $access_list = $gate_data['access'];
        if(isset($access_list[$visitor])):
            $last_access = $access_list[$visitor][1];
            $current_attempt = $access_list[$visitor][0];
            $now = time();
            $time_elapsed = $now - $last_access;
            if( $time_elapsed < $time_between_attempts):
                $gate_data['blocked'][$visitor] = array($current_attempt, time());
                update_option($option, $gate_data);
                return true;
            endif;
        endif;

        /**
         * (3) Number of attempts exceeded
         *     A new entry for block list(many attempts)
         */
        if(isset($access_list[$visitor])):
            $last_access = $access_list[$visitor][1];
            $now = time();
            $time_elapsed = $now - $last_access;

            /**
             * Add to block list if exceeds the
             * number of attempts
             */
            $current_attempt = $access_list[$visitor][0];
            if( $current_attempt >= $allowed_attempts):
                $gate_data['blocked'][$visitor] = array($current_attempt, time());
                update_option($option, $gate_data);
                return true;
            endif;
        endif;

        return false;
    }

    /**
     * Record Current Visitor
     */
    function record_current_visitor($visitor, $gate){
        $option     = $gate . '_gate_data';
        $gate_data  = get_option($option);
        $access_list = $gate_data['access'];

        /**
         * Returning Visitor
         */
        if(isset($access_list[$visitor])):
            $last_access  = $access_list[$visitor][1];
            $time_elapsed = time() - $last_access;
            $time_to_keep = $this->options[$gate]['time_to_keep'];
            /**
             * Expired entry.
             * Clear access time and attempts
             */
            if($time_elapsed > $time_to_keep):
                $attempts = 1;
                $last_access = time();
                $access_list[$visitor] = array( $attempts, $last_access );
            /**
             * Non-expired entry.
             * Update number of visits/attempts
             */
            else:
                $attempts = $access_list[$visitor][0] + 1;
                $access_list[$visitor] = array( $attempts, $last_access );
            endif;

        /**
         * New Visitor
         */
        else:
            $attempts = 1;
            $last_access = time();
            $access_list[$visitor] = array( $attempts, $last_access );
        endif;

        $gate_data['access'] = $access_list;
        update_option($option, $gate_data);
    }

    function log_blocked_visitor($visitor, $gate, $data, $blocked_ip_log){
        $security_log = SIGMA_PATH . 'logs/' . $blocked_ip_log;
        $data      = current_time('mysql');
        $data     .= " " . $visitor;
        $data     .= " " . $gate . "\n";
        $r         = file_put_contents($security_log, $data, FILE_APPEND | LOCK_EX);
    }
}
endif;
