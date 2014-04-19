<?php
if ( !class_exists('Sigma_Debugger') ) :
/**
 * Sigma Debugger
 *
 * Outputs a panel to admin dashboard tab where an admin can change the
 * registration record( payment status ).
 *
 * @package     SigmaEvents
 * @subpackage  Utilities
 */
class Sigma_Debugger
{
    function __construct(){
        add_action('wp_ajax_sigma_debugger_get_record',
            array( $this, 'get_record' ) );
        add_action('wp_ajax_sigma_dineromail_query',
            array( $this, 'dineromail_query' ) );
    }

    function get_record(){
        /* Verify the intent of the Sigma Event Administrator  */
        $res = check_ajax_referer( 'sigma_debugging_operations', '_sigma_debugger_nonce' );
        $id_array = $this->prepare_id_array();
        if( false == $id_array ):
            echo "<h3>Invalid Token Array</h3>";
            exit;
        endif;

        global $sigma_events;
        $processor = $sigma_events->payments_sigma;
        $registration_table = $sigma_events->registration_table;

        // Registration data
        $registrations = $processor->get_registration_records( $registration_table, $id_array );
        if( ! $registrations ):
            echo "<h3>Error: No registration records found for the tokens</h3>";
            exit;
        endif;

        $form = '';
        $found_in_db = array();
        foreach($registrations as $registration):
            array_push($found_in_db, $registration['token']);
            $form .= $processor->get_debugger_form( $registration );
        endforeach;

        foreach($id_array as $token){
            if(!in_array($token, $found_in_db)){
                echo '<div class="se-debug-general-form"><h2 class="debug-heading">Not Found - token: '
                 . $token . ' </h2></div>';
            }
        }

        echo $form;
        exit;
    }

    function dineromail_query(){
        /* Verify the intent of the Sigma Event Administrator  */
        $res = check_ajax_referer( 'sigma_debugging_operations', '_sigma_debugger_nonce' );
        $tokens = isset( $_POST['tokens'] ) ? sanitize_text_field( $_POST['tokens'] ) : '' ;

        $id_array = $this->prepare_id_array();
        if( false == $id_array ):
            echo "<h3>Invalid Token Array</h3>";
            exit;
        endif;

        /**
         * Get Dineromail Response from $id_array
         */
        $response = $this->get_dineromail_response( $id_array );
        if( false == $response ):
            echo "<h3>Invalid Token Array</h3>";
            exit;
        endif;

        /**
         * Create Registration Array from Dineromail Response
         */
        $registrations = $this->create_registration_array( $response );

        /**
         * Find Missing Tokens
         */
        $found_in_db = array();
        foreach( $registrations as $registration ):
            array_push($found_in_db, $registration['token']);
        endforeach;

        foreach($id_array as $token){
            if(!in_array($token, $found_in_db)){
                echo '<div class="se-debug-general-form"><h2 class="debug-heading">Not Found - token: '
                 . $token . ' </h2></div>';
            }
        }

        foreach( $registrations as $registration ):
            $form = $this->get_dineromail_form( $registration );
            echo $form;
        endforeach;

        exit;
    }

    function prepare_id_array(){
        $tokens = $_POST['tokens'];
        if( '' == $tokens ) return false;
        $tokens = array_map('trim', explode( ',', $tokens ) );
        return $tokens;
    }

    /**
     * Query Dineromail and Get the Response
     *
     * @var     array           $id_array array of tokens
     * @return  array|false     $array of token details
     *                          false if error occurred
     */
    function get_dineromail_response( $token_array ){
        $id_array = '';
        foreach ( $token_array as $token ) {
            $id_array .= '<ID>' . $token . '</ID>';
        }

        global $sigma_events;
        $processor = $sigma_events->payments_dineromail;

        $response = $processor->get_token_details( $id_array );

        $matched = preg_match('/<operaciones.*operaciones>/is', $response, $tokens);

        if( '' == $response || 1 != $matched ):
            return false;
        endif;

        $tokens = iconv("ISO-8859-1", "UTF-8//TRANSLIT", $tokens[0]);
        $operations = new SimpleXMLElement( $tokens );
        return $operations;
    }

    function create_registration_array( $response ){
        foreach( $response as $operation ):
            $transaction["token"]      = (string)$operation->ID;
            $transaction["date"]       = (string)$operation->FECHA;
            $transaction["amount"]     = (string)$operation->MONTO;
            $transaction["tr_no"]      = (string)$operation->NUMTRANSACCION;
            $transaction["method"]     = (string)$operation->METODOPAGO;
            $transaction["paid"]       = (string)$operation->ESTADO;
            $transaction["name"]       = (string)$operation->COMPRADOR->NOMBRE;
            $transaction["email"]      = (string)$operation->COMPRADOR->EMAIL;
            $transaction["dni"]        = (string)$operation->COMPRADOR->NUMERODOC;
            /**
             * Include raw transaction
             */
            $transaction["raw"]        = $operation;
            $registration[]            = $transaction;
        endforeach;

        return $registration;
    }

    /**
     * Get Special Dineromail Form for Debugging
     */
    function get_dineromail_form( $new_registration ){
        $token = $new_registration['token'];

        global $wpdb, $sigma_events;
        $processor = $sigma_events->payments_sigma;

        /**
         * Get the Current Registration Record
         */
        $table_name = $wpdb->prefix . $sigma_events->registration_table;
        $where          = "'" . $token . "'";
        $registration   = $wpdb->get_results(
            "
            SELECT *
            FROM $table_name
            WHERE token = $where
            ", ARRAY_A
        );
        if( ! $registration ):
            echo "<h2 class='debug-heading' >Error: No registration record found for the token: " . $token . "</h2>";
            exit;
        endif;
        $registration = $registration[0];

        /**
         * Get the Previous Payment Record
         */
        $table_name = $wpdb->prefix . $sigma_events->payment_table;
        $where          = "'" . $registration['payment'] . "'";
        $payment   = $wpdb->get_results(
            "
            SELECT *
            FROM $table_name
            WHERE id = $where
            ", ARRAY_A
        );
        if( ! $payment ):
            echo "<h2 class='debug-heading' >Error: No payment record found for the token: " . $token . "</h2>";
            $payment = array(
                'codautorizacion'  => 'Not Yet Received',
                'tarjeta'          => 'Not Yet Received',
                'emailcomprador'   => 'Not Yet Received',
                'fechahora'        => 'Not Yet Received',
                'titular'          => 'Not Yet Received',
                'motivo'           => 'Not Yet Received'
            );
        else:
            $payment = $payment[0];
        endif;

            $form = '<div class="debug-panel" >';
            $form .= '<div class="debug-response-panel" ></div>';
            $form .= '</form><form action="debugger" class="se-debug-dineromail-form" method="post" >';
            $form .= '<h2 class="debug-heading" >Registration Record for the token: ' . $registration['token'] . '</h2>';
            $form .= '<table class="form-table">';

            // Close Button
            $form .= '<div class="debug-close-button"><img src="' . SIGMA_URL . 'assets/debug-close-button.png"></div>';

            // POST URL
            $form .= '<input type="hidden" id="debug-url" value="' . get_site_url() . '/' . $processor->get_payment_endpoint() . '" >';
            $form .= '<input type="hidden" id="tracker-url" value="' . get_site_url() . '/sigma-events/tracker/?sigma_token=' . $registration['token'] . '" >';

            // Token.
            $form .= '<tr><th scope="row" ><label>Token</lable></th><td>';
            $form .= '<input id="token" type="text" name="token" value="' . $new_registration['token']
                . '" class="regular-text" > Current Value: ' . $registration['token'] . ' ( Don\'t change the token )';
            $form .= '</td></tr>';

            // Amount.
            $amount = (int) ( $new_registration['amount'] * 100 );
            $form .= '<tr><th scope="row" ><label>Amount</lable></th><td>';
            $form .= '<input type="text" name="amount" value="' . $amount . '" class="regular-text" > Current Value: '
                . $registration['amount'] . ' ( Amounts must match in order to update )';
            $form .= '</td></tr>';

            // Result.
            $form .= '<tr><th scope="row" ><label>Payment Status</lable></th><td>';
            $form .='<select name="resultado"">
                    <option value="pending" ' . selected( $new_registration['paid'], 1, false ) . '>Pending</option>
                    <option value="paid" ' . selected( $new_registration['paid'], 2, false ) . '>Paid</option>
                    <option value="cancelled" ' . selected( $new_registration['paid'], 3, false ) . '>Cancelled</option>
                </select> Current Status: '. $registration['paid'];
            $form .= '</td></tr>';

            // Auth Code.
            $form .= '<tr><th scope="row" ><label>AuthCode</lable></th><td>';
            $form .= '<input type="text" name="codeauth" value="' . $new_registration['tr_no']
                . '" class="regular-text" > Current Value: ' . $payment['codautorizacion'];
            $form .= '</td></tr>';

            // Medium.
            $motivo = 2 == $new_registration['method']
                ? 'Dineromail_Cash'
                : 'Dineromail_Unknown';
            $motivo = 3 == $new_registration['method']
                ? 'Dineromail_Credit_Card'
                : $motivo;
            $form .= '<tr><th scope="row" ><label>Medium</lable></th><td>';
            $form .= '<input type="text" name="medium" value="' . $motivo
                . '" class="regular-text" > Current Value: ' . $payment['tarjeta'];
            $form .= '</td></tr>';

            // Email.
            $form .= '<tr><th scope="row" ><label>Email</lable></th><td>';
            $form .= '<input type="text" name="email" value="' . $new_registration['email']
                . '" class="regular-text" > Current Value: ' . $payment['emailcomprador'];
            $form .= '</td></tr>';

            // Time.
            $form .= '<tr><th scope="row" ><label>Payment Time</lable></th><td>';
            $form .= '<input type="text" name="time" value="' . $new_registration['date']
                . '" class="regular-text" > Current Value: ' . $payment['fechahora'];
            $form .= '</td></tr>';

            // Title.
            $form .= '<tr><th scope="row" ><label>Title</lable></th><td>';
            $form .= '<input type="text" name="title" value="' . $new_registration['name'] . '" class="regular-text" > Current Value: '
                . $payment['titular'] ;
            $form .= '</td></tr>';

            // Reason.
            $form .= '<tr><th scope="row" ><label>Reason/Notes</lable></th><td>';
            $form .= '<input type="text" name="reason" value="' . 'Dineromail'
                . '" class="regular-text" > Current Value: ' . $payment['motivo'] . '<br />Use this field to store remarks, notes, etc.';
            $form .= '</td></tr>';

            $form .= '<tr><th scope="row" ><label>Raw Response</lable></th><td>';
            $form .= '<span class="raw-debug-response-handle" >Show Raw Response</span><br />';
            $form .= '<div class="raw-debug-response" >';
            $form .= '<pre>';
            $form .= print_r( $new_registration['raw'], true );
            $form .= '</pre>';
            $form .= '</div>';
            $form .= '</td></tr>';

            // Submit Button.
            $form .= '<tr><th scope="row" ><label></lable></th><td>';
            $form .= '<input class="button-primary se-debug-dineromail-record" type="submit" value="Update Registration Record" >';
            $form .= '</td></tr>';

            $form .= '</table>';
            $form .= '</form>';
            $form .= '</div>';
        return $form;
    }
}
endif;
?>
