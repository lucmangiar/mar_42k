<?php
/**
 * Sigma Dineromail Payment Processor
 *
 * Procesess all transaction details POSTed to
 * Sigma Dineromail endpoint.
 *
 * @package     SigmaEvents
 * @subpackage  PaymentProcessing
 */
require_once SIGMA_PATH . 'checkout/abstract-sigma-payment-processor.php';

if ( !class_exists('Sigma_Dineromail') ) :
/**
 * Sigma Dineromail Payment Processor
 *
 * Sigma Dineromail inherits all the parent methods of
 * Sigma Payment Processor abstract class. Implements
 * Dineromail specific methods.
 *
 * @package     SigmaEvents
 * @subpackage  PaymentProcessing
 */
class Sigma_Dineromail extends Sigma_Payment_Processor
{
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
     * Dineromail Log File Name
     *
     * @var string
     */
    protected $data_log  = 'dineromail_payments_data.log';
    protected $error_log = 'dineromail_payments_error.log';

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

    /*  */
    /**
     * Dineromail Endpoint
     *
     * @var string
     */
    private $dineromail_endpoint = 'post_dm_ipn' ;

    /**
     * Dineromail Query Var
     *
     * @var string
     */
    private $dineromail_var = 'dineromail';

    /**
     * Dineromail IPN Details
     *
     * @var string
     */
    private $url = 'http://argentina.dineromail.com/Vender/Consulta_IPN.asp';

    /**
     * Dineromail Password
     *
     * @var string
     */
    private $password = '12345679';

    /**
     * Dineromail Client Number
     *
     * @var string
     */
    private $client_no = '3585257';

    /**
     * Construct the Sigma Dineromail object
     */
    function __construct( $registration_table, $payment_table ){
        /* Essential Information */
        $this->registration_table = $registration_table;
        $this->payment_table = $payment_table;

        // Setup Sigma Options
        $this->options = get_option( 'sigma_options' );

        /* Add Dineromail Rewrite */
        add_action( 'init', array( $this, 'dineromail_rewrite' ) );

        /* Process Dineromail POSTs */
        add_action( 'template_redirect', array($this, 'redirect_dineromail_requests' ) );

        /**
         * Processor Options
         */
        $this->processor_options = get_option('sigma_processor_options');
    }

    /**
     * Dineromail IPN URL Setter
     */
    function set_ipn_url( $url ){
        $this->url = $url;
        return true;
    }

    /**
     * Dineromail IPN URL Getter
     */
    function get_ipn_url( $url ){
        return $this->url;
    }

    /**
     * Return Dineromail payment form
     */
    function get_form( $event_data, $submit ){
        /**
         * Prepare Form Data
         */
        $token          = $event_data['token'];
        $price          = $event_data['price']['value'];
        $event_name     = $event_data['title_'];
        $event_id       = $event_data['id'];
        $payment_method = 'dineromail_credit_cards';

        /* Whether the payment button should be present or not */
        $submit = $submit
            ? "<input type='submit' id='se-proceed' value='Proceed to payment' ><a
                id='se-modify' class='button' href='" . get_home_url() .
                "/sigma-events/payment/?sigma_token=" . $token . "#se-order'>Modify</a>"
            : '';

        /**
         * Payment Method Mapping
         */
        if( 'dineromail_credit_cards' == $payment_method ):
            $method = 'ar_visa,1;ar_amex,1;ar_master,1;ar_cabal,1;ar_tnaranja,1;ar_italcred,1;ar_tshopping,1;ar_argencard,1';
        elseif( 'dineromail_cash' == $payment_method ):
            $method = 'ar_pagofacil;ar_rapipago;ar_cobroexpress;ar_bapropago;ar_ripsa';
        endif;

        /* Dineromail Form */
        if( 0 < $price ):
            $form_html = '<form id="se-dineromail-form" action="https://checkout.dineromail.com/CheckOut"
                method="post">
            <input type="hidden" name="transaction_id" value="' . $token . '">
            <input type="hidden" name="item_ammount_1" value="' . $price . '">
            <input type="hidden" name="item_name_1" value="' . $event_name . '">
            <input type="hidden" name="item_code_1" value="' . $event_id . '_' . $price . '">
			<input type="hidden" name="item_quantity_1" value="1">
			<input type="hidden" name="currency" value="ars">
			<input type="hidden" name="merchant" value="' . $this->client_no . '">
            <input type="hidden" name="header_image" value="https://argentina.dineromail.com/imagenes/LogosVendedores/712054.jpg">
            <input type="hidden" name="ok_url" value="http://sigmasecurepay.info/operacion-procesada/">
            <input type="hidden" name="error_url" value="http://sigmasecurepay.info/operacion-no-procesada/">
			<input type="hidden" name="pending_url" value="http://sigmasecurepay.info/operacion-pendiente/">
			<input type="hidden" name="DireccionEnvio" value="0">
            <input type="hidden" name="buyer_message" value="0">
			<input type="hidden" name="payment_method_available" value="' . $method . '">
			<input type="hidden" name="country_id" value="1">
			<input type="hidden" name="seller_name" value="Carreras y Maratones Ñandú, Buenos Aires, Argentina">
			<input type="hidden" name="url_redirect_enabled" value="1">';

            /**
             * Extra Fields for Express Checkout ( exclusive to Cash payments )
             *
             * May be removed by jQuery for credit card customers.
             */
            $buyer_name            = $event_data['fname'];
            $buyer_lastname        = $event_data['lname'];
            $buyer_sex             = 'female' == $event_data['gender'] ? 'F' : 'M';
            $buyer_document_number = $event_data['dni'];
            $buyer_phone           = 3 < strlen($event_data['phone']) ? $event_data['phone'] : '12345678';
            $buyer_email           = $event_data['email'];

            $form_html .= '<input type="hidden" name="buyer_name" value="' . $buyer_name . '" />
            <input type="hidden" name="buyer_lastname" value="' . $buyer_lastname . '" />
            <input type="hidden" name="buyer_sex" value="' . $buyer_sex . '" />
            <input type="hidden" name="buyer_document_type" value="dni" />
            <input type="hidden" name="buyer_document_number" value="' . $buyer_document_number . '" />
            <input type="hidden" name="buyer_phone" value="' . $buyer_phone . '" />
            <input type="hidden" name="buyer_email" value="' . $buyer_email . '" />';

            $form_html .= $submit . '</form>';
        else:
            $form_html  = $this->get_free_event_form($token);
        endif;

        return $form_html;
    }

    /**
     * Rewrite Dineromail requests
     */
    function dineromail_rewrite(){
        $dineromail_regex = $this->dineromail_endpoint . '$';
        add_rewrite_rule($dineromail_regex,
            'index.php?' . $this->dineromail_var . '=ipn', 'top');
        add_rewrite_tag('%' . $this->dineromail_var . '%', '([^&]+)');
    }

    /**
     * Redirect Dineromail requests
     */
    function redirect_dineromail_requests( $return = false ){
        global $wp_query;

        /* Check the parsed query for the presence of Dineromail endpoint */
        if(!isset($wp_query->query_vars[$this->dineromail_var]))
            return;

        /* Is this a POST to Dineromail IPN */
        if( 'ipn' == $wp_query->query_vars[$this->dineromail_var] ):
            $this->process_payment();
            if( $return )
                return true;
        endif;

        exit;
    }

    /**
     * Process Dineromail POSTs
     *
     * Processes Notificacion POST varialbe content
     *
     * @return boolean  false if error occurred or
     *                  UNLESS process_tokens() returned true.
     */
    function process_payment(){
        /* Get the Notificacion */
		
		
        $notificacion = isset($_POST['Notificacion'])
            ? $_POST['Notificacion']
            : '';

        /* Blank Notification ? */
        if( '' == $notificacion ):
            $this->log_error( 'Blank Notificacion Massage Received. Got the following: ' . json_encode($_POST) );
            return false;
        endif;

        $matched = preg_match('/<notificacion.*notificacion>/is', $notificacion, $notification);
        if( 1 != $matched ):
            $this->log_error( 'Notification Count Error' );
            return false;
        endif;

        /* Log and Debug Emails */
        $this->log_and_debug_emails( $this->options, $this->processor_options['dineromail']['enable_logging'] );

        /* Get the token array */
        $token_array = $this->get_token_array( $notification[0] );

        /* Get the details of each token */
        $token_id_array = '';
        foreach ( $token_array as $token ) {
            $token_id_array .= '<ID>' . $token . '</ID>';
        }

        $token_details = $this->get_token_details( $token_id_array );

        /**
         * Process Received Tokens
         */
        if( $token_details ):
            $r = $this->process_tokens( $token_details );
            return $r;
        endif;

        return false;
    }

    /**
     * Retrieve an array of tokens from a POST
     */
    function get_token_array( $notification ){
        $token_array = array();
        $notification = iconv("ISO-8859-1", "UTF-8//TRANSLIT", $notification);
        $doc = new SimpleXMLElement($notification);

        foreach ( $doc->operaciones->operacion as $op ) {
            if( in_array( $op->id, $token_array ) ) continue;
            $token_array[] = (string)$op->id;
        }

        return $token_array;
    }

    /**
     * Get details of each token, POSTed by Dineromail
     */
    function get_token_details( $id_array ){
        $url       = $this->url;
        $password  = $this->password;
        $client_no = $this->client_no;
        $data      = 'DATA=<REPORTE><NROCTA>'.$client_no.'</NROCTA><DETALLE><CONSULTA><CLAVE>'.$password.'</CLAVE><TIPO>1</TIPO><OPERACIONES>'.$id_array.'</OPERACIONES></CONSULTA></DETALLE></REPORTE>';

        $parsed = parse_url( $url );

        $response = wp_remote_post( $url, array(
            'method'        => 'POST',
            'timeout'       => 45,
            'redirection'   => 5,
            'user-agent'    => 'Sigma Payment Processor 1.0',
            'httpversion'   => '1.0',
            'blocking'      => true,
            'body'          => $data,
            'headers'       => array(
                    'Host'              => $parsed['host'],
                    'Content-type'      => 'application/x-www-form-urlencoded',
                    'Connection' => 'close'
            )
            )
        );

        if( is_wp_error($response) || '200' != $response['response']['code'] ) {
            $this->log_error( 'Unable to Process Token Details Error: ' . $id_array );
            return false;
        } elseif( 5 > strlen($response['body']) ){
            $this->log_error( 'Invalid Response for the Token Detail Request: ' . $id_array );
            return false;
        } else {
            $this->log( $response['body'] );
            return $response['body'];
        }
    }

    /**
     * Process tokens
     *
     * @return boolean  false if unable to process Token Details passed.
     *                  true if reached the end of the function.
     */
    function process_tokens( $token_details ){
        $matched = preg_match('/<operaciones.*operaciones>/is', $token_details, $tokens);

        if( '' == $token_details || 1 != $matched ):
            $this->log_error( 'Unable to Process Token Details Error' );
            return false;
        endif;

        $tokens = iconv("ISO-8859-1", "UTF-8//TRANSLIT", $tokens[0]);
        $operations = new SimpleXMLElement( $tokens );

        foreach( $operations as $operation ):
            /**
             * Errors are logged inside process_token() function.
             */
            $this->process_token( $operation );
        endforeach;

        return true;
    }

    /**
     * Process a single token, a single Dineromail transaction
     *
     * Errors will be logged.
     *
     * @return void
     */
    function process_token( $operation ){
        /* Fill the transaction array */
        $transaction["token"]      = (string)$operation->ID;
        $transaction["date"]       = (string)$operation->FECHA;
        $transaction["amount"]     = (string)$operation->MONTO;
        $transaction["tr_no"]      = (string)$operation->NUMTRANSACCION;
        $transaction["method"]     = (string)$operation->METODOPAGO;
        $transaction["paid"]       = (string)$operation->ESTADO;
        $transaction["name"]       = (string)$operation->COMPRADOR->NOMBRE;
        $transaction["email"]      = (string)$operation->COMPRADOR->EMAIL;
        $transaction["dni"]        = (string)$operation->COMPRADOR->NUMERODOC;

        // Setup $post and $registration arrays.
        $r = $this->setup_post_and_registration_data( $transaction, $this->registration_table );
        if( $r ):
            $this->post         = $r['post'];
            $this->registration = $r['registration'];
        else:
            $this->log_error( "\nDidn't returned two element array | Token: " . $transaction["token"] );
            return;
        endif;

        // Update tables
        $this->update_tables( $this->payment_table, $this->registration_table, $this->post, $this->registration );

        // Send emails
        $this->send_emails( $this->options, $this->post, $this->registration );
    }

    /**
     * Setup $post and $registration arrays
     *
     * @return array|boolean    array of two elements if processed without errors
     *                          false if errors occurred
     */
    function setup_post_and_registration_data( $POST, $registration_table ){
        /**
         * Get the registration record
         */
        $registration = $this->get_registration_record( $registration_table, $POST["token"] );
        if( ! $registration ):
            $this->log_error( "\nError: No registration record | Token: " . $POST["token"] );
            return false;
        endif;

        /**
         * Check Paid Amount
         */
        $POST["amount"] = (int) ( $POST["amount"] * 100 );
        if( $registration['amount']  != $POST['amount'] ):
            $this->log_error( "\nError: Amounts Not Matched | Token : " . $POST["token"]
                . ' | Registration : ' . $registration['amount'] . ' | POST : ' . $POST['amount']);
            return false;
        endif;

        $paid = $POST["paid"] == 1 ? 'pending'   : '';
        $paid = $POST["paid"] == 2 ? 'paid'      : $paid;
        $paid = $POST["paid"] == 3 ? 'cancelled' : $paid;

        /**
         * Stop here if invalid paid status
         */
        if( '' == $paid ):
            $this->log_error( "\nError: Invalid 'paid' status Error" );
            return false;
        endif;

        /**
         * Prepare motivo string
         */
        $motivo = 2 == $POST["method"]
            ? 'dineromail_cash'
            : 'dineromail_unknown';
        $motivo = 3 == $POST["method"]
            ? 'dineromail_credit_cards'
            : $motivo;

        // don't assign a new sequence number if already assigned a sequence number
        if( 0 >= $registration['seq_no'] ):
            // Sequence Number
            global $sigma_events;
            if( 'paid' == $paid ):
                $registration['seq_no'] = $sigma_events->sequences->get_sequence_number( $registration['eid'], $post["token"] );
            elseif( 'pending' == $post['resultado'] ):
            elseif( 'notpaid' == $paid || 'cancelled' == $paid ):
                $sigma_events->sequences->return_sequence_number( $registration['eid'], $post["token"] );
                $registration['seq_no'] = 'none';
            else:
                $registration['seq_no'] = 'none';
            endif;
        endif;

        /**
         * DateTime Formatting
         */
        $datetime = explode(' ', $POST["date"]);
        $date = explode('/', $datetime[0]);
        $datetime = $date[2] . '-' . $date[0] . '-' . $date[1] . ' ' . $datetime[1];

        /**
         * Setup data to be inserted in to the payments table
         */
        $post = array(
            'token'     => $POST["token"],
            'codeauth'  => $POST["tr_no"],
            'monto'     => $POST["amount"],
            'fechahora' => $datetime,
            'titular'   => $POST["name"],
            'email'     => $POST["email"],
            'resultado' => $paid,
            'tarjeta'   => $motivo,
            'motivo'    => 'Dineromail',
            'seq_no'    => $registration['seq_no'],
            'processor' => 'dineromail'
        );

        $output = array(
            'registration' => $registration,
            'post'         => $post
        );
        return $output;
    }
}
endif;
?>
