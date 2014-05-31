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
     * Paypal Log File Name
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
     * String that defines the BN Code. Is only applicable for partners
     */
    private $sBNCode = "PP-ECWizard";

    private $api_username;
    private $api_password;
    private $api_signature;

    /**
     * Construct the Paypal Payment Processor Object
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

        // Add rewrite for Paypal response.
        add_action('init', array($this, 'paypal_rewrite'));

        // Process rewritten Paypal POSTs.
        add_action('template_redirect', array($this, 'redirect_paypal_requests'));

        // Processor Options
        $this->processor_options = get_option('sigma_processor_options');

        $this->set_api_credentials();
    }

    private function set_api_credentials() {
        if ($this->using_sandbox) {
            $this->api_username = 'javierpetrucci+PPSANDBOX_api1.gmail.com';
            $this->api_password = '1396537509';
            $this->api_signature = 'AFcWxV21C7fd0v3bYYYRCpSSRl31Ab9bBR1gFzYLlq6YDCzrZnUmglFK';
        } else {
            $this->api_username = '';
            $this->api_password = '';
            $this->api_signature = '';
        }
    }


    private function get_api_credentials() {
        return array(
            'username' => $this->api_username,
            'password' => $this->api_password,
            'signature' => $this->api_signature
        );
    }

    /**
     * Get Paypal Checkout Form
     *
     * This form displays the final confirmation message to the customer,
     * with a button to proceed to Paypal.
     * IMPORTANT: This method only constructs the form, does not make any logic or POST method
     *
     * @param   object   $event_data   Registration Token
     * @param   boolean  $submit             Visa, Amex, etc.
     * @return  string  Paypal Payment Form
     */
    function get_form( $event_data, $submit ){
        /* Whether the payment button should be present or not */
        $input_payment_proceed = '';

        $operation_number = $event_data['token'];
        $amount = $event_data['price']['value'];
        $event_name = $event_data['title_'];
        $event_id = $event_data['id'];

        if (!empty($submit)) {
            $input_payment_proceed .= "<input type='submit' id='se-proceed' value='Proceed to payment' ><a
                id='se-modify' class='button' href='" . get_home_url() .
                "/sigma-events/payment/?sigma_token=" . $operation_number . "#se-order'>Modify</a>";
        }

        // Premium Event?
        if($amount > 0) {
            $form = '<form action="' . Paypal_Utilities::get_paypal_url() . '" id="se-paypal-form" method="post" >';

            // This is a shipping cart
            $form .= '<input type="hidden" name="cmd" value="x_cart">';

            // Send the operation number
            $form .= '<input type="hidden" name="custom" value="' . $operation_number . '">';

            // The bill is in dollars
            $form .= '<input type="hidden" name="currency_code" value="USD">';

            // Item name and value
            $form .= '<input type="hidden" name="item_name" value="' . $event_name . '">';
            $form .= '<input type="hidden" name="item_value" value="' . $event_id . '">';

            // Amount.
            $form .= '<input type="hidden" name="amount" value="' . $amount . '">';

            // Quantity
            $form .= '<input type="hidden" name="quantity" value="1" >';

            // Notify URL. Is the URL used by Paypal for POSTing me the information of the payment
            $form .= '<input type="hidden" name="notify_url" value="' . get_home_url() . '/' . Paypal_Utilities::get_paypal_endpoint() . '" >';

            $form .= $input_payment_proceed . '</form>';
        }

        return $form;
    }

    /**
     * Paypal Rewrite
     *
     * Add rewrite rule to handle POSTs from Paypal.
     */
    function paypal_rewrite(){
        $paypal_regex = Paypal_Utilities::get_paypal_endpoint() . '/?$';
        add_rewrite_rule($paypal_regex,
        'index.php?' . Paypal_Utilities::get_paypal_endpoint() . '=sigma', 'top');
        add_rewrite_tag('%' . Paypal_Utilities::get_paypal_endpoint() . '%', '([^&]+)');
    }

    /**
     * Redirect Paypal Requests.
     *
     * Handle redirected requests from Paypal via custom rewrite rules.
     */
    function redirect_paypal_requests(){
        global $wp_query;
        if(!isset($wp_query->query_vars[Paypal_Utilities::get_paypal_endpoint()]))
            return;

        $this->process_paypal_request();
    }

    /**
     * Process Paypal Request
     *
     * Initializes the processing. The method calles the rest of the methods
     * to process the request.
     *
     * Silently exits at the end of successfull processing.
     */
    function process_paypal_request(){
        // Setup Sigma Options
        $this->options = get_option('sigma_options');

        // Write Log and send debug emails.
        $this->log_and_debug_emails( $this->options, $this->processor_options['paypal']['enable_logging'] );

        // Setup $post and $registration arrays.
        $r = $this->setup_post_and_registration_data( $_POST, $this->registration_table );
        if( $r ):
            $this->post         = $r['post'];
            $this->registration = $r['registration'];
        else:
            exit;
        endif;

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

    // TODO Liquidar este metodo
    function setup_post_and_registration_data( $POST, $registration_table ){
        // We get the operation number form custom
        $operation_number = $POST['custom'];
        if(empty($operation_number)) {
            $this->log_error( "\nError: No 'noperacion' field in the POST Error" );
            return false;
        }

        // (1.1) Registration data
        $registration = $this->get_registration_record( $registration_table, $operation_number );
        if( ! $registration ):
            $this->log_error( "\nError: No registration record | Token: " . $operation_number );
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

        // (5) Email | emailcomprador
        $post['email'] = isset($POST['emailcomprador'])
          ? sanitize_email($POST['emailcomprador']) : '';

        // (6) DateTime | fechahora
        $post['fechahora'] = isset($POST['fechahora'])
          ? sanitize_text_field($POST['fechahora']) : '';

        /**
         * DateTime Formatting
         */
        $datetime = explode(' ', $post['fechahora']);
        $date = explode('/', $datetime[0]);
        $post['fechahora'] = $date[2] . '-' . $date[1] . '-' . $date[0] . ' ' . $datetime[1];

        // (7) Title | titular
        $post['titular'] = isset($POST['titular'])
          ? sanitize_text_field($POST['titular']) : '';

        // (8) Reason | motivo
        $post['motivo'] = isset($POST['motivo'])
          ? sanitize_text_field($POST['motivo']) : '';

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
        $check_ip   = $this->processor_options['paypal']['enable_ip'];
        $paypal_ip = $this->processor_options['paypal']['ip_address'];
        $ip         = $_SERVER['REMOTE_ADDR'];
        if( $check_ip && $ip != $paypal_ip):
            $this->log_error( "\nError: IP Address not matched error | Token: " . $post["token"] );
            return false;
        else:
            return true;
        endif;
    }

    /**
     * Handle Free Events
     *
     * Free event registrations are handled by Paypal Endpoint. Upon
     * detecting a request to process a free event registration, this
     * method fills out the missing data using the registration record
     * and calls db updates and email sending.
     *
     * @return  void    Redirects to the tracker page

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
    */
}