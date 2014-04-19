<?php
/**
 * Sigma Event's Implementation of its Own Abstract Payment Processor.
 *
 * Outputs a form with standard variables to POST to special kind of payment processing.
 * Creates a endpoint to process POSTs from its own forms with debug/free transaction information.
 * Updates the database according to the payment status received from the forms.
 * Logs all the POSTs to Sigma Processor url.
 * Sends emails about transaction status to user and the admin.
 *
 * @package     SigmaEvents
 * @subpackage  PaymentProcessing
 */

if ( !class_exists('Sigma_Processor') ) :
/**
 * Sigma Payment Processor
 *
 * Extends Sigma Payment Processor Abstract class to handle
 * Event related payments.
 *
 * @package     SigmaEvents
 * @subpackage  PaymentProcessing
 */
class Sigma_Processor extends Sigma_Payment_Processor
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
     * Payment Processor Log File Name
     *
     * @var string
     */
    protected $data_log  = 'sigma_payments_data.log';
    protected $error_log = 'sigma_payments_error.log';

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
     * Post processing of the transaction information.
     *
     * @var string
     */
    private $payment_endpoint = 'sigma_payment_processor';

    /**
     * Construct the Sigma DECIDIR SPS object.
     */
    function __construct( $registration_table, $payment_table ){
        global $sigma_events;

        // Setup table name.
        $this->registration_table = $registration_table;
        $this->payment_table = $payment_table;

        // Add rewrite for Decidir response.
        add_action('init', array($this, 'payment_rewrite'));

        // Process rewritten Decidir POSTs.
        add_action('template_redirect', array($this, 'redirect_payment_requests'));
    }

    /**
     * Get Sigma Debugger Form
     *
     * This form displays a complete form with all input buttons to enable all registration
     * record fields.
     */
    function get_debugger_form( $registration ){
        $payment_id = $registration['payment'];
        $payment = $this->get_payment_record($payment_id);
        $form = '<div class="debug-panel" >';
        $form .= '<div class="debug-response-panel" ></div>';
        $form .= '</form><form action="debugger" id="se-debug-record-form" class="se-debug-general-form" method="post" >';
        $form .= '<h2 class="debug-heading">Registration Record for the token: ' . $registration['token'] . '</h2>';

        // Close Button
        $form .= '<div class="debug-close-button"><img src="' . SIGMA_URL . 'assets/debug-close-button.png"></div>';

        $form .= '<table class="form-table">';

        // POST URL
        $form .= '<input type="hidden" id="debug-url" value="' . get_site_url() . '/' . $this->payment_endpoint . '" >';
        $form .= '<input type="hidden" id="tracker-url" value="' . get_site_url() . '/sigma-events/tracker/?sigma_token=' . $registration['token'] . '" >';

        // 1. Token.
        $form .= '<tr><th scope="row" ><label>Token</lable></th><td>';
        $form .= '<input type="text" name="token" id="token" value="' . $registration['token'] . '" class="regular-text" >';
        $form .= '</td></tr>';

        // 2. Registration Time.
        $form .= '<tr><th scope="row" ><label>Registration Time</lable></th><td>';
        $form .= '<input type="text" name="reg_time" value="' . $registration['reg_time'] . '" class="regular-text" >';
        $form .= '</td></tr>';

        // 3. Event ID.
        $form .= '<tr><th scope="row" ><label>Event ID</lable></th><td>';
        $form .= '<input type="text" name="eid" value="' . $registration['eid'] . '" class="regular-text" disabled="disabled" >';
        $form .= '</td></tr>';

        // 4. First Name.
        $form .= '<tr><th scope="row" ><label>First Name</lable></th><td>';
        $form .= '<input type="text" name="fname" value="' . $registration['fname'] . '" class="regular-text" >';
        $form .= '</td></tr>';

        // 5. Last Name.
        $form .= '<tr><th scope="row" ><label>Last Name</lable></th><td>';
        $form .= '<input type="text" name="lname" value="' . $registration['lname'] . '" class="regular-text" >';
        $form .= '</td></tr>';

        // 6. Argentinian.
        $form .= '<tr><th scope="row" ><label>Argentinian</lable></th><td>';
        $form .= '<input type="checkbox" name="argentinian" ' . checked($registration['argentinian'], true, false) . '" class="regular-text" >';
        $form .= '</td></tr>';

        // 7. Country.
        $form .= '<tr><th scope="row" ><label>Country</lable></th><td>';
        $form .= '<input type="text" name="country" value="' . $registration['country'] . '" class="regular-text" >';
        $form .= '</td></tr>';

        // 8. DNI.
        $form .= '<tr><th scope="row" ><label>DNI</lable></th><td>';
        $form .= '<input type="text" name="dni" value="' . $registration['dni'] . '" class="regular-text" >';
        $form .= '</td></tr>';

        // 9. Email.
        $form .= '<tr><th scope="row" ><label>Email</lable></th><td>';
        $form .= '<input type="text" name="email" value="' . $registration['email'] . '" class="regular-text" >';
        $form .= '</td></tr>';

        // 10. Gender.
        $form .= '<tr><th scope="row" ><label>Gender</lable></th><td>';
        $form .= '<input type="text" name="gender" value="' . $registration['gender'] . '" class="regular-text" >';
        $form .= '</td></tr>';

        // 11. Birth Day.
        $form .= '<tr><th scope="row" ><label>Birth Day</lable></th><td>';
        $form .= '<input type="text" name="bday" value="' . $registration['bday'] . '" class="regular-text" >';
        $form .= '</td></tr>';

        // 12. Phone.
        $form .= '<tr><th scope="row" ><label>Phone</lable></th><td>';
        $form .= '<input type="text" name="phone" value="' . $registration['phone'] . '" class="regular-text" >';
        $form .= '</td></tr>';

        // 13. Address.
        $form .= '<tr><th scope="row" ><label>Address</lable></th><td>';
        $form .= '<input type="text" name="addr" value="' . $registration['addr'] . '" class="regular-text" >';
        $form .= '</td></tr>';

        // 14. Club.
        $form .= '<tr><th scope="row" ><label>Club</lable></th><td>';
        $form .= '<input type="text" name="club" value="' . $registration['club'] . '" class="regular-text" >';
        $form .= '</td></tr>';

        // 15. Discount Code.
        $form .= '<tr><th scope="row" ><label>Discount Code</lable></th><td>';
        $form .= '<input type="text" name="disc_code" value="' . $registration['disc_code'] . '" class="regular-text" >';
        $form .= '</td></tr>';

        // 16. Answer.
        $form .= '<tr><th scope="row" ><label>Answer</lable></th><td>';
        $form .= '<input type="text" name="ans" value="' . $registration['ans'] . '" class="regular-text" >';
        $form .= '</td></tr>';

        // 17. Extra Items.
        $form .= '<tr><th scope="row" ><label>Extra Items</lable></th><td>';
        $form .= '<input type="text" name="extra_items" value="' . $registration['extra_items'] . '" class="regular-text" >';
        $form .= '</td></tr>';

        // 18. Rate.
        $form .= '<tr><th scope="row" ><label>Rate</lable></th><td>';
        $form .= '<input type="text" name="rate" value="' . $registration['rate'] . '" class="regular-text" disabled="disabled">';
        $form .= '</td></tr>';

        // 19. IP Address.
        $form .= '<tr><th scope="row" ><label>IP Address</lable></th><td>';
        $form .= '<input type="text" name="ip" value="' . $registration['ip'] . '" class="regular-text" >';
        $form .= '</td></tr>';

        // 20. Amount.
        $form .= '<tr><th scope="row" ><label>Amount</lable></th><td>';
        $form .= '<input type="text" name="amount" value="' . $registration['amount'] . '" class="regular-text" >';
        $form .= '</td></tr>';

        // 21. Medium.
        $form .= '<tr><th scope="row" ><label>Medium</lable></th><td>';
        $form .= '<input type="text" name="medium" value="' . $registration['medium'] . '" class="regular-text" >';
        $form .= '</td></tr>';

        // 22. Paid.
        $form .= '<tr><th scope="row" ><label>Paid</lable></th><td>';
        $form .= '<input type="text" name="paid" value="' . $registration['paid'] . '" class="regular-text" >';
        $form .= '</td></tr>';

        // 22.1 Reason.
        $form .= '<tr><th scope="row" ><label>Reason</lable></th><td>';
        $form .= '<input type="text" name="reason" value="' . $payment['motivo'] . '" class="regular-text" > Enter the reason for payment cancellation, if applicable. To be used in the email.';
        $form .= '</td></tr>';

        // 23. Payment.
        $form .= '<tr><th scope="row" ><label>Payment</lable></th><td>';
        $form .= '<input type="text" name="payment" value="' . $registration['payment'] . '" class="regular-text" >';
        $form .= '</td></tr>';

        // 24. Sequence Number.
        $form .= '<tr><th scope="row" ><label>Sequence Number</lable></th><td>';
        $form .= '<input type="text" name="seq_no" value="' . $registration['seq_no'] . '" class="regular-text" >';
        $form .= ' <input type="checkbox" name="override_seq" > Override Sequence Number';
        $form .= '</td></tr>';

        // 25. Send Emails.
        $form .= '<tr><th scope="row" ><label>Send Emails</lable></th><td>';
        $form .= '<input type="checkbox" name="send_emails" checked="checked"  >';
        $form .= '</td></tr>';

        // Submit Button.
        $form .= '<tr><th scope="row" ><label></lable></th><td>';
        $form .= '<input class="button-primary se-debug-general-record" type="submit" id="se-debug-record" value="Update Registration Record" >';
        $form .= '</td></tr>';

        // Update Registration Record Indicator.
        $form .= '<input type="hidden" name="debugger" value="update_registration_record" />';

        $form .= '</table>';
        $form .= '</form>';
        $form .= '</div>';
        $form .= '</div>';
        return $form;
    }

    /**
     * Get Sigma Payments Checkout Form
     *
     * This form displays the final confirmation message to the customer,
     * with a button to proceed to Sigma Payments.
     */
    function get_form( $registration ){
            $form = '</form><form action="debugger" id="se-debug-record-form" method="post" >';
            $form .= '<h3>Registration Record for the token: ' . $registration['token'] . '</h3>';
            $form .= '<table class="form-table">';

            // POST URL
            $form .= '<input type="hidden" id="debug-url" value="' . get_site_url() . '/' . $this->payment_endpoint . '" >';

            // Token.
            $form .= '<tr><th scope="row" ><label>Token</lable></th><td>';
            $form .= '<input type="text" name="token" value="' . $registration['token'] . '" class="regular-text" >';
            $form .= '</td></tr>';

            // Amount.
            $form .= '<tr><th scope="row" ><label>Amount</lable></th><td>';
            $form .= '<input type="text" name="amount" value="' . $registration['amount'] . '" class="regular-text" >';
            $form .= '</td></tr>';

            // Result.
            $form .= '<tr><th scope="row" ><label>Result</lable></th><td>';
            $form .= '<input type="text" name="resultado" value="' . $registration['paid'] . '" class="regular-text" >';
            $form .= '</td></tr>';

            // Auth Code.
            $form .= '<tr><th scope="row" ><label>AuthCode</lable></th><td>';
            $form .= '<input type="text" name="codeauth" value="none" class="regular-text" >';
            $form .= '</td></tr>';

            // Medium.
            $form .= '<tr><th scope="row" ><label>Medium</lable></th><td>';
            $form .= '<input type="text" name="medium" value="' . $registration['medium'] . '" class="regular-text" >';
            $form .= '</td></tr>';

            // Email.
            $form .= '<tr><th scope="row" ><label>Email</lable></th><td>';
            $form .= '<input type="text" name="email" value="' . $registration['email'] . '" class="regular-text" >';
            $form .= '</td></tr>';

            // Time.
            $form .= '<tr><th scope="row" ><label>Payment Time</lable></th><td>';
            $form .= '<input type="text" name="time" value="' . $registration['reg_time'] . '" class="regular-text" >';
            $form .= '</td></tr>';

            // Title.
            $form .= '<tr><th scope="row" ><label>Title</lable></th><td>';
            $form .= '<input type="text" name="title" value="' . $registration['fname'] . '_' . $registration['lname'] . '" class="regular-text" >';
            $form .= '</td></tr>';

            // Reason.
            $form .= '<tr><th scope="row" ><label>Reason</lable></th><td>';
            $form .= '<input type="text" name="motivo" value="' . $registration['medium'] . '" class="regular-text" >';
            $form .= '</td></tr>';

            // Submit Button.
            $form .= '<tr><th scope="row" ><label></lable></th><td>';
            $form .= '<input class="button-primary" type="submit" id="se-debug-record" value="Update Registration Record" >';
            $form .= '</td></tr>';

            $form .= '</table>';
            $form .= '</form>';
        return $form;
    }

    /**
     * Sigma Processor Rewrite
     *
     * Add rewrite rule to handle POSTs from Decidir SPS.
     */
    function payment_rewrite(){
        $payment_regex = $this->payment_endpoint . '/?$';
        add_rewrite_rule($payment_regex,
        'index.php?' . $this->payment_endpoint . '=debug', 'top');
        add_rewrite_tag('%' . $this->payment_endpoint . '%', '([^&]+)');
    }

    /**
     * Redirect Sigma Processor Requests.
     *
     * Handle redirected requests from Sigma Processor via custom rewrite rules.
     */
    function redirect_payment_requests(){
        global $wp_query;
        if(!isset($wp_query->query_vars[$this->payment_endpoint]))
            return;

        $this->process_payment_request();
    }

    /**
     * Process Sigma Processor Request
     */
    function process_payment_request(){
        // Immediately exit if login cookies doesn't present
        if( ! is_user_logged_in() ):
            echo 'Access Denied';
            exit;
        endif;

        /**
         * Collect special debugging related data
         */
        $debugger = isset($_POST['debugger']) ? sanitize_text_field($_POST['debugger']) : '';
        $send_emails = isset($_POST['send_emails']) && 'on' == $_POST['send_emails'] ? true : false;

        // Setup Sigma Options
        $this->options = get_option('sigma_options');

        // Write Log and send debug emails.
        $this->log_and_debug_emails( $this->options, true );

        // Setup $post and $registration arrays.
        if('update_registration_record' == $debugger):
            $r = $this->setup_post_and_registration_data_debugger( $_POST, $this->registration_table );
        else:
            $r = $this->setup_post_and_registration_data( $_POST, $this->registration_table );
        endif;

        if( -1 == $r ):
            echo "No Token Found in the Request";
            exit;
        elseif( -2 == $r ):
            echo "No Registration Record for the Token";
            exit;
        elseif( -3 == $r ):
            echo "Amounts does not Match";
            exit;
        elseif( $r ):
            $this->post         = $r['post'];
            $this->registration = $r['registration'];
        else:
            echo "Error Processing Request";
            exit;
        endif;

        $this->update_tables_debugger( $this->payment_table, $this->registration_table, $this->post, $this->registration );

        // Send emails
        if( 'update_registration_record' == $debugger && ! $send_emails ):
            // Don't send emails.
        else:
            $this->send_emails( $this->options, $this->post, $this->registration );
        endif;

        echo 'Processed Successfully';
        exit;
    }

    /**
     * Setup $post and $registration arrays
     */
    function setup_post_and_registration_data( $POST, $registration_table ){
        // (1) Token
        $post['token'] = isset($POST['token']) ? sanitize_text_field($POST['token']) : '';
        if( '' == $post['token']):
            $this->log_error( "\nError: No 'noperacion' field in the POST Error" );
            return -1;
        endif;

        // Registration data
        $registration = $this->get_registration_record( $registration_table, $post["token"] );
        if( ! $registration ):
            $this->log_error( "\nError: No registration record | Token: " . $post["token"] );
            return -2;
        endif;

        // (2) Amount
        $post['monto'] = isset($POST['amount']) ? sanitize_text_field($POST['amount']) : '';
        $price = $registration['amount'];
        if($price != $post['monto']):
            $this->log_error( "\nError: Transaction amounts not matched | Token: " . $post["token"] );
            return -3;
        endif;

        // (3) Paid
        $post['resultado']    = isset($POST['resultado']) ? sanitize_text_field($POST['resultado']) : '';

        // don't assign a new sequence number if already assigned a sequence number
        if( 0 >= $registration['seq_no'] ):
            // Sequence Number
            global $sigma_events;
            if( 'paid' == $post['resultado'] ):
                $post['seq_no']         = $sigma_events->sequences->get_sequence_number( $registration['eid'], $post["token"] );
                $registration['seq_no'] = $post['seq_no'];
            elseif( 'pending' == $post['resultado'] ):
                $post['seq_no']         = $registration['seq_no'];
            elseif( 'notpaid' == $post['resultado'] || 'cancelled' == $post['resultado'] ):
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

        // (4) Auth Code
        $post['codeauth'] = isset($POST['codeauth']) ? sanitize_text_field($POST['codeauth']) : '';

        // (5) Card
        $post['tarjeta'] = isset($POST['medium']) ? sanitize_text_field($POST['medium']) : '';

        // (6) Email
        $post['email'] = isset($POST['email']) ? sanitize_email($POST['email']) : '';

        // (7) DateTime
        $post['fechahora'] = isset($POST['time']) ? sanitize_text_field($POST['time']) : '';

        // (8) Title
        $post['titular'] = isset($POST['title']) ? sanitize_text_field($POST['title']) : '';

        // (9) Reason
        $post['motivo'] = isset($POST['reason']) ? sanitize_text_field($POST['reason']) : '';

        // (10) Free
        $post['free'] = isset($POST['free']) ? sanitize_text_field($POST['free']) : '';

        // (11) Processor
        $post['processor'] = 'debugger';

        $output = array(
            'registration' => $registration,
            'post'         => $post
        );
        return $output;
    }

    /**
     * Return Payment Endpoint URL
     */
    function get_payment_endpoint(){
        return $this->payment_endpoint;
    }

    /**
     * Setup $post and $registration arrays for debugger POSTS
     */
    function setup_post_and_registration_data_debugger( $POST, $registration_table ){
        // (1) Token
        $post['token'] = isset($POST['token']) ? sanitize_text_field($POST['token']) : '';
        if( '' == $post['token']):
            $this->log_error( "\nError: No 'noperacion' field in the POST Error" );
            return -1;
        endif;

        // Registration data
        $registration = $this->get_registration_record( $registration_table, $post["token"] );
        if( ! $registration ):
            $this->log_error( "\nError: No registration record | Token: " . $post["token"] );
            return -2;
        endif;

        // (2) Amount
        $post['monto'] = isset($POST['amount']) ? sanitize_text_field($POST['amount']) : '';
        $price = $registration['amount'];
        if($price != $post['monto']):
            $this->log_error( "\nError: Transaction amounts not matched | Token: " . $post["token"] );
            return -3;
        endif;

        // (3) Paid
        $post['resultado']    = isset($POST['paid']) ? sanitize_text_field($POST['paid']) : '';

        // don't assign a new sequence number if already assigned a sequence number
        if( 0 >= $registration['seq_no'] ):
            // Sequence Number
            global $sigma_events;
            if( 'paid' == $post['resultado'] ):
                $post['seq_no']         = $sigma_events->sequences->get_sequence_number( $registration['eid'], $post["token"] );
                $registration['seq_no'] = $post['seq_no'];
            elseif( 'pending' == $post['resultado'] ):
                $post['seq_no']         = $registration['seq_no'];
            elseif( 'notpaid' == $post['resultado'] || 'cancelled' == $post['resultado'] ):
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

        // (4) Auth Code
        $post['codeauth'] = isset($POST['codeauth']) ? sanitize_text_field($POST['codeauth']) : 'debugger';

        // (5) Card
        $post['tarjeta'] = isset($POST['medium']) ? sanitize_text_field($POST['medium']) : '';

        // (6) Email
        $post['email'] = isset($POST['email']) ? sanitize_email($POST['email']) : '';

        // (7) DateTime
        $post['fechahora'] = current_time('mysql');

        // (8) Title or FirstName
        $post['titular'] = isset($POST['fname']) ? sanitize_text_field($POST['fname']) : '';

        // (9) Reason
        $post['motivo'] = isset($POST['reason']) ? sanitize_text_field($POST['reason']) : '';

        // (10) Free
        $post['free'] = isset($POST['free']) ? sanitize_text_field($POST['free']) : '';

        // (11) Processor
        $post['processor'] = 'debugger';

        // (12) Registration Data Array
        $registration = $this->extract_registration_record_data($POST, $registration);

        $output = array(
            'registration' => $registration,
            'post'         => $post
        );
        return $output;
    }
    /**
     * Extract Registration Record Data from the Debugger
     */
    function extract_registration_record_data($POST, $registration){
        $registration['token'       ] = isset($POST['token'       ] ) ? sanitize_text_field($POST['token'       ] ) : '';
        $registration['reg_time'    ] = isset($POST['reg_time'    ] ) ? sanitize_text_field($POST['reg_time'    ] ) : '';
        $registration['eid'         ] = $registration['eid'];
        $registration['fname'       ] = isset($POST['fname'       ] ) ? sanitize_text_field($POST['fname'       ] ) : '';
        $registration['lname'       ] = isset($POST['lname'       ] ) ? sanitize_text_field($POST['lname'       ] ) : '';
        $registration['argentinian' ] = isset($POST['argentinian' ] ) ? true                                        : false;
        $registration['country'     ] = isset($POST['country'     ] ) ? sanitize_text_field($POST['country'     ] ) : '';
        $registration['dni'         ] = isset($POST['dni'         ] ) ? sanitize_text_field($POST['dni'         ] ) : '';
        $registration['email'       ] = isset($POST['email'       ] ) ? sanitize_text_field($POST['email'       ] ) : '';
        $registration['gender'      ] = isset($POST['gender'      ] ) ? sanitize_text_field($POST['gender'      ] ) : '';
        $registration['bday'        ] = isset($POST['bday'        ] ) ? sanitize_text_field($POST['bday'        ] ) : '';
        $registration['phone'       ] = isset($POST['phone'       ] ) ? sanitize_text_field($POST['phone'       ] ) : '';
        $registration['addr'        ] = isset($POST['addr'        ] ) ? sanitize_text_field($POST['addr'        ] ) : '';
        $registration['club'        ] = isset($POST['club'        ] ) ? sanitize_text_field($POST['club'        ] ) : '';
        $registration['disc_code'   ] = isset($POST['disc_code'   ] ) ? sanitize_text_field($POST['disc_code'   ] ) : '';
        $registration['ans'         ] = isset($POST['ans'         ] ) ? sanitize_text_field($POST['ans'         ] ) : '';
        $registration['extra_items' ] = isset($POST['extra_items' ] ) ? sanitize_text_field($POST['extra_items' ] ) : '';
        $registration['rate'        ] = $registration['rate'];
        $registration['ip'          ] = isset($POST['ip'          ] ) ? sanitize_text_field($POST['ip'          ] ) : '';
        $registration['amount'      ] = isset($POST['amount'      ] ) ? sanitize_text_field($POST['amount'      ] ) : '';
        $registration['medium'      ] = isset($POST['medium'      ] ) ? sanitize_text_field($POST['medium'      ] ) : '';
        $registration['paid'        ] = isset($POST['paid'        ] ) ? sanitize_text_field($POST['paid'        ] ) : '';
        $registration['payment'     ] = isset($POST['payment'     ] ) ? sanitize_text_field($POST['payment'     ] ) : '';
        $override_sequence_number     = isset($POST['override_seq'] ) ?  true : false;
        if($override_sequence_number):
            $registration['seq_no'  ] = isset($POST['seq_no'      ] ) ? sanitize_text_field($POST['seq_no'      ] ) : '';
        else:
            $registration['seq_no'  ] = $registration['seq_no'];
        endif;
        return $registration;
    }
}
endif;
?>
