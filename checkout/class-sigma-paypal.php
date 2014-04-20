<?php

class Sigma_PayPal extends Sigma_Payment_Processor {
    /**
     * Sigma Registration Table Name
     *
     * @var string
     */
    private $registration_table;

    /**
     * Sigma Payment Table Name
     *
     * @var string
     */
    private $payment_table;

    /**
     * Sigma Options Array
     * Stored as a WordPress Option
     *
     * @var array
     */
    private $options;

    /**
     * Sigma Processor Options
     */
    private $processor_options;

    /**
     * Decidir Log File Name
     *
     * @var string
     */
    protected $data_log  = 'paypal_payments_data.log';
    protected $error_log = 'paypal_payments_error.log';

    /**
     * POSTed information to the endpoint as an array
     *
     * @var array
     */
    private $post;

    /**
     * Retried Registration Record for the current Token
     *
     * @var array
     */
    private $registration;

    /**
     * String Pairs to be used in email template parsing
     *
     * @var array
     */
    protected $replace_array;

    /**
     * Decidir URL to POST the registration form
     *
     * @var string
     */
    //private $decidir_url = 'https://sps.decidir.com/sps-ar/Validar';
    // TODO definir esto
    private $paypal_url = '';

    /**
     * Construct the Decidir Payment Processor Object
     *
     * Sets up the registration table name and payment table name. Sets
     * up rewrites and redirects.
     *
     * @param   string  $registration_table     Sigma Registration Table Name
     * @param   string  $payment_table          Sigma Payment Table Name
     * @return  void
     */
    function __construct( $registration_table, $payment_table ){
        global $sigma_events;

        // Setup table name.
        $this->registration_table = $registration_table;
        $this->payment_table = $payment_table;

        // Add rewrite for Decidir response.
        add_action('init', array($this, 'paypal_rewrite'));

        // Process rewritten Decidir POSTs.
        add_action('template_redirect', array($this, 'redirect_decidir_requests'));

        // Processor Options
        $this->processor_options = get_option('sigma_processor_options');
    }

    /**
     * Get Decidir Checkout Form
     *
     * This form displays the final confirmation message to the customer,
     * with a button to proceed to Decidir SPS.
     *
     * @param   string   $operation_number   Registration Token
     * @param   string   $amount             Event Price + Additional Products Price
     * @param   string   $means_of_payment   Visa, Amex, etc.
     * @param   string   $submit             Visa, Amex, etc.
     * @return  string  Decidir Payment Form
     */
    function get_form( $operation_number, $amount, $means_of_payment, $submit ){
        /* Whether the payment button should be present or not */
        $input_payment_proceed = '';
        if (!empty($submit)) {
            $input_payment_proceed .= "<input type='submit' id='se-proceed' value='Proceed to payment' ><a
                id='se-modify' class='button' href='" . get_home_url() .
                "/sigma-events/payment/?sigma_token=" . $operation_number . "#se-order'>Modify</a>";
        }

        /**
         * Means of Payment Mapping
         */
        $means_of_payment_code = null;
        if( 'decidir_visa' == $means_of_payment ) {
            $means_of_payment_code = 1;
        } else if( 'decidir_amex' == $means_of_payment ) {
            $means_of_payment_code = 6;
        } else if( 'decidir_mastercard' == $means_of_payment ) {
            $means_of_payment_code = 15;
        }

        // Premium Event?
        if($amount > 0 && !is_null($means_of_payment_code)) {
            $form = '<form action="' . $this->paypal_url . '" id="se-decidir-form" method="post" >';
            // Shop Number.
            $form .= '<input type="hidden" name="NROCOMERCIO" value="' . $this->shop_number . '" size=8 maxlength=8 >';
            // Operacion Number.
            $form .= '<input type="hidden" name="NROOPERACION" value="' . $operation_number . '" size=10 maxlength=10 >';
            // Medio De Pago.
            $form .= '<input type="hidden" name="MEDIODEPAGO" value="' . $means_of_payment_code . '" size=12 >';
            // Amount.
            $form .= '<input type="hidden" name="MONTO" value="' . $amount . '" size=12 maxlength=12 >';
            // Installments.
            $form .= '<input type="hidden" name="CUOTAS" value="' . '1' . '" size=12 >';
            // Dinamica URL.
            $form .= '<input type="hidden" name="URLDINAMICA" value="' . get_home_url() . '/' . $this->paypal_endpoint . '" >';

            $form .= $input_payment_proceed . '</form>';

        // Free Event?
        } else {
            $form  = $this->get_free_event_form( $operation_number, $this->paypal_endpoint );
        }

        return $form;
    }

    /**
     * Decidir Rewrite
     *
     * Add rewrite rule to handle POSTs from Paypal.
     */
    function paypal_rewrite(){
        $decidir_regex = $this->paypal_endpoint . '/?$';
        add_rewrite_rule($decidir_regex,
        'index.php?' . $this->paypal_endpoint . '=sigma', 'top');
        add_rewrite_tag('%' . $this->paypal_endpoint . '%', '([^&]+)');
    }

    /**
     * Redirect Decidir Requests.
     *
     * Handle redirected requests from Decidir via custom rewrite rules.
     */
    function redirect_decidir_requests(){
        global $wp_query;
        if(!isset($wp_query->query_vars[$this->paypal_endpoint]))
            return;

        $this->process_decidir_request();
    }

    /**
     * Process Decidir Request
     *
     * Initializes the processing. The method calles the rest of the methods
     * to process the request.
     *
     * Silently exits at the end of successfull processing.
     */
    function process_decidir_request(){
        // Setup Sigma Options
        $this->options = get_option('sigma_options');

        // Write Log and send debug emails.
        $this->log_and_debug_emails( $this->options, $this->processor_options['decidir']['enable_logging'] );

        // Setup $post and $registration arrays.
        $r = $this->setup_post_and_registration_data( $_POST, $this->registration_table );
        if( $r ):
            $this->post         = $r['post'];
            $this->registration = $r['registration'];
        else:
            exit;
        endif;

        // Redirect Free Event Registrations.
        if($this->post['free']):
            $this->handle_free_events();
        endif;

        // Check IP Address
        $r = $this->check_ip( $this->post, $this->options );
        if( ! $r ) exit;

        // Update tables
        $this->update_tables( $this->payment_table, $this->registration_table, $this->post, $this->registration );

        // Send emails
        $this->send_emails( $this->options, $this->post, $this->registration );

        exit;
    }

    /**
     * Setup $post and $registration arrays
     *
     * $post array is a validated and formatted POSTed data array.
     *
     * $registration array is the registration record retrieved using the POSTed token.
     *
     * Dies if errors present in the POSTed data.
     *
     * @param   array $POST                 Unprocessed POSTed Data
     * @param   array $registration_table   Registration Table Name
     * @return  array Double element array. 'post' and 'registration' indices.
     */
    function setup_post_and_registration_data( $POST, $registration_table ){
        // (1.0) Token | Noperacion
        $post['token'] = isset($POST['noperacion']) ? sanitize_text_field($POST['noperacion']) : '';
        if( '' == $post['token']):
            $this->log_error( "\nError: No 'noperacion' field in the POST Error" );
            return false;
        endif;

        // (1.1) Registration data
        $registration = $this->get_registration_record( $registration_table, $post["token"] );
        if( ! $registration ):
            $this->log_error( "\nError: No registration record | Token: " . $post["token"] );
            return false;
        endif;

        // (2) Amount | Monto
        $post['monto'] = isset($POST['monto']) ? sanitize_text_field($POST['monto']) : '';
        $post['monto'] = str_replace(",", "", $post['monto']);
        $price = $registration['amount'];
        if($price != $post['monto']):
            $this->log_error( "\nError: Amounts Not Matched | Token : " . $post["token"]
                . ' | Registration : ' . $price . ' | POST : ' . $post['monto']);
            return false;
        endif;

        // (3) Paid | Resulado
        $post['resultado']    = isset($POST['resultado']) ? sanitize_text_field($POST['resultado']) : '';
        $post['resultado']    = $post['resultado'] == 'APROBADA' ? 'paid' : 'notpaid';

        // don't assign a new sequence number if already assigned a sequence number
        if( 0 >= $registration['seq_no'] ):
            // Sequence Number
            global $sigma_events;
            if( 'paid' == $post['resultado'] ):
                $post['seq_no']         = $sigma_events->sequences->get_sequence_number( $registration['eid'], $post["token"] );
                $registration['seq_no'] = $post['seq_no'];
            elseif( 'notpaid' == $post['resultado'] ):
                $sigma_events->sequences->return_sequence_number( $registration['eid'], $post["token"] );
                $post['seq_no']         = 'none';
                $registration['seq_no'] = 'none';
            else:
                $post['seq_no']         = 'none';
                $registration['seq_no'] = 'none';
            endif;
        else:
                $post['seq_no']         = $registration['seq_no'];
        endif;

        // (4) Auth Code | codautorizacion
        $post['codeauth'] = isset($POST['codautorizacion'])
          ? sanitize_text_field($POST['codautorizacion']) : '';

        /**
         * (5) Card | tarjeta
         *
         *   From the logs,
         *   Possible valued for 'tarjeta',
         *
         * - MasterCard
         * - Visa
         * - Amex
         */
        $post['tarjeta'] = isset($POST['tarjeta']) ? sanitize_text_field($POST['tarjeta']) : '';
        $post['tarjeta'] = str_replace(' ', '_', $post['tarjeta']);
        $post['tarjeta'] = 'decidir_' . strtolower( $post['tarjeta'] );

        // (6) Email | emailcomprador
        $post['email'] = isset($POST['emailcomprador'])
          ? sanitize_email($POST['emailcomprador']) : '';

        // (7) DateTime | fechahora
        $post['fechahora'] = isset($POST['fechahora'])
          ? sanitize_text_field($POST['fechahora']) : '';

        /**
         * DateTime Formatting
         */
        $datetime = explode(' ', $post['fechahora']);
        $date = explode('/', $datetime[0]);
        $post['fechahora'] = $date[2] . '-' . $date[1] . '-' . $date[0] . ' ' . $datetime[1];

        // (8) Title | titular
        $post['titular'] = isset($POST['titular'])
          ? sanitize_text_field($POST['titular']) : '';

        // (9) Reason | motivo
        $post['motivo'] = isset($POST['motivo'])
          ? sanitize_text_field($POST['motivo']) : '';

        // (10) Is Free?
        $post['free'] = isset($POST['free']) && $POST['free'] == 'FREE' ? true : false;

        // (11) Processor
        $post['processor'] = 'decidir';

        $output = array(
            'registration' => $registration,
            'post'         => $post
        );
        return $output;
    }

    /**
     * Check IP address for a non free event.
     *
     * @param   array $post     POSTed data
     * @param   array $options  Sigma Options Array
     * @return  boolean  Valid IP address or not
     */
    function check_ip( $post, $options ){
        $check_ip   = $this->processor_options['decidir']['enable_ip'];
        $decidir_ip = $this->processor_options['decidir']['ip_address'];
        $free       = $post['free'];
        $ip         = $_SERVER['REMOTE_ADDR'];
        if( $check_ip && ! $free && $ip != $decidir_ip):
            $this->log_error( "\nError: IP Address not matched error | Token: " . $post["token"] );
            return false;
        else:
            return true;
        endif;
    }

    /**
     * Handle Free Events
     *
     * Free event registrations are handled by Decidir Endpoint. Upon
     * detecting a request to process a free event registration, this
     * method fills out the missing data using the registration record
     * and calls db updates and email sending.
     *
     * @return  void    Redirects to the tracker page
     */
    function handle_free_events(){
        // Fill Free Event Missing Data from POST
        $this->post['codeauth']     = 'none';
        $this->post['tarjeta']      = 'sigma_free';
        $this->post['email']        = $this->registration['email'];
        $this->post['resultado']    = 'paid';
        $this->post['fechahora']    = current_time('mysql');
        $this->post['titular']      = $this->registration['fname'] . ' ' . $this->registration['lname'];
        $this->post['motivo']       = 'Sigma_Free_Event';

        // Update tables
        $this->update_tables( $this->payment_table, $this->registration_table, $this->post, $this->registration );

        // Send emails
        $this->send_emails( $this->options, $this->post, $this->registration );

        // Redirect to Tracker page
        $redirect = get_home_url() . '/sigma-events/tracker/?sigma_token=' .
            $this->registration['token'];
        wp_redirect($redirect);
        exit;
    }

    /**
     * Get Decidir Endpoint
     *
     * Free event registration are processed by Decidir Endpoint.
     * Decidir endpoint string( url ) is a private property of Decidir
     * SPS class which isn't accessible to outsiders. This method returns
     * the endpoint string.
     *
     * @return string   Decidir Endpoint
     */
    function get_paypal_endpoint(){
        return $this->paypal_endpoint;
    }
}