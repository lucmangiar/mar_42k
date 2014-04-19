<?php
/**
 * Forget Code
 *
 * Retrieve the forgotten code by entering the email.
 *
 * @package     SigmaEvents
 * @subpackage  Utilities
 * @since       version 4.0
 */
class Sigma_Forget_Code{
    /**
     * Forget Code Endpoint
     */
    private $endpoint = 'forget_code';

    /**
     * Registration Table
     */
    private $registration_table;

    /**
     * Forget code constructor
     */
    function __construct($registration_table){
        /**
         * Add rewrite rule Forget Code.
         */
        add_action('init', array($this, 'forget_code_rewrite'));

        /**
         * A redirect to serve code forgotten users.
         */
        add_action('template_redirect', array($this, 'redirect_forget_code'));

        $this->registration_table = $registration_table;
    }

    /**
     * Sigma Forget Code Rewrite
     */
    function forget_code_rewrite(){
        $forget_code = 'forget_code/?$';
        add_rewrite_rule($forget_code,   'index.php?' . $this->endpoint . '=yes',    'top');
        add_rewrite_tag('%' . $this->endpoint . '%', '([^&]+)');
    }

    /**
     * Redirect Code Forgotten Users
     */
    function redirect_forget_code(){
        global $wp_query;
        if(!isset($wp_query->query_vars[$this->endpoint]))
            return;

        $query_var = $wp_query->query_vars[$this->endpoint];
        if($query_var == 'yes'):
            $this->output_forget_password_page();
        endif;
        exit;
    }

    /**
     * Forget Password Form
     */
    function output_forget_password_page(){
        $error     = false;
        $captcha   = false;
        $code_page = 'email_input_form';

        $code_email = isset($_POST['code_email']) ? sanitize_text_field($_POST['code_email']) : '';
        $code_nonce = isset($_POST['sigma-forget-code-data']) ? sanitize_text_field($_POST['sigma-forget-code-data']) : '';

        $options = get_option('sigma_options');
        if($options['enable_forget_code_captcha']) $captcha = true;

        if('' != $code_email):
            /**
             * Veriy Nonce
             */
            if(!wp_verify_nonce($code_nonce, 'sigma-forget-code-action')) wp_die('Something went wrong', 'se');

            /**
             * Verify Captcha
             */
            if($options['enable_forget_code_captcha'] && class_exists('ReallySimpleCaptcha')):
                $captcha_instance = new ReallySimpleCaptcha();
                $captcha_prefix = isset($_POST['captcha_prefix']) ? sanitize_text_field($_POST['captcha_prefix']) : '';
                $captcha_solution = isset($_POST['captcha_solution']) ? sanitize_text_field($_POST['captcha_solution']) : '';
                $correct = $captcha_instance->check( $captcha_prefix, $captcha_solution );
                if(!$correct):
                    $error = 'Captcha Verification Failed!';
                    $code_page = 'email_input_form';
                    include SIGMA_PATH . 'templates/sigma-forget-code.php';
                else:
                    if($options['enable_forget_code_email']):
                        $this->send_code_email($code_email);
                    else:
                        $records = $this->get_record_by_email($code_email);
                        if($records):
                            $code_page = 'data_display_form';
                        else:
                            $error = 'No record is associated with your email';
                            $code_page = 'email_input_form';
                        endif;
                    endif;
                endif;
                $captcha_instance->remove( $captcha_prefix );

            /**
             * No Captcha Enabled
             */
            else:
                if($options['enable_forget_code_email']):
                    $this->send_code_email($code_email);
                else:
                    $records = $this->get_record_by_email($code_email);
                    if($records):
                        $code_page = 'data_display_form';
                    else:
                        $error = 'No record is associated with your email';
                        $code_page = 'email_input_form';
                    endif;
                endif;
            endif;

        /**
         * New Visitor. Display the form.
         */
        else:
            $code_page = 'email_input_form';
        endif;
        include SIGMA_PATH . 'templates/sigma-forget-code.php';
    }

    /**
     * Send code email
     */
    function send_code_email($code_email){
        $error     = false;
        $captcha   = false;
        $records = $this->get_record_by_email($code_email);
        if($records):
            $this->send_email($records);
            $code_page = 'email_sent_form';
            include SIGMA_PATH . 'templates/sigma-forget-code.php';
        else:
            $error = 'No code is associated with your email';
            $code_page = 'email_input_form';
            include SIGMA_PATH . 'templates/sigma-forget-code.php';
        endif;
    }

    /**
     * Get record by email
     */
    function get_record_by_email($email){
        global $wpdb;
        $table_name     = $wpdb->prefix . $this->registration_table;
        $where          = "'" . $email . "'";
        $paid_registrations  = $wpdb->get_results(
            "
            SELECT id, lname, fname, eid, token, reg_time, seq_no, paid
            FROM $table_name
            WHERE email = $where
            AND paid = 'paid'
            ORDER BY reg_time DESC
            ", ARRAY_A
        );

        $other_registrations  = $wpdb->get_results(
            "
            SELECT lname, fname, eid, token, reg_time, seq_no, paid
            FROM $table_name
            WHERE email = $where
            AND paid != 'paid'
            ORDER BY reg_time DESC
            ", ARRAY_A
        );

        $registrations = array_merge( $paid_registrations, $other_registrations);

        if(!$registrations)
            return false;

        return $registrations;
    }

    /**
     * Send the email informing the code
     */
    function send_email($records){
        $record    = $records[0];
        $options   = get_option('sigma_options');
        $to        = $record['email'];
        $headers[] = 'From: ' . $options['send_name'] . ' <' . $options['send_email'] . '>';
        if($options['enable_debug_dev_email'])
            $headers[] = 'Cc: Developer <' . $options['dev_email'] . '>';

        $event      = get_post($record['eid']);
        $event_name = $event->post_title;

        $data = array(
            'Name'            => $record['fname'] . ' ' . $record['lname'],
            'Event Name'      => $event_name,
            'Token ID'        => $record['token'],
            'Registered Date' => $record['reg_time'],
            'Sequence Number' => $record['seq_no']
        );
        $subject = 'Code: ' . $record['seq_no'] . ' | EID : ' . $record['eid'];
        $message = print_r($data, true);
        $r = wp_mail($to, $subject, $message, $headers, null);
    }
}
?>
