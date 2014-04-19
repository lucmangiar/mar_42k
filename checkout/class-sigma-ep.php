<?php
/**
 * Sigma EP Processor
 *
 * @package     SigmaEvents
 * @subpackage  PaymentProcessing
 * @since version 3.5
 *
 * Post Form
 * {{ root_url }}/post_ep/
 */

if ( !class_exists('Sigma_EP') ) :
/**
 * Sigma EP
 *
 * @package     SigmaEvents
 * @subpackage  PaymentProcessing
 */
class Sigma_EP extends Sigma_Payment_Processor
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
     * Payment Processor Log File Name
     *
     * @var string
     */
    protected $data_log  = 'ep_payments_data.log';
    protected $error_log = 'ep_payments_error.log';

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
    private $payment_endpoint = 'post_ep';

    /**
     * EP Specific parameters.
     */
    private $url;
    private $method;
    private $password;


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

        // EP configuration.
        $this->options = get_option( 'sigma_options' );
        $this->processor_options = get_option( 'sigma_processor_options' );

        $this->url      = $this->processor_options['easyplanners']['easyplanners_url'];
        $this->method   = $this->processor_options['easyplanners']['method'];
        $this->password = $this->processor_options['easyplanners']['password'];
    }

    /*
     * Get Sigma Payments Checkout Form
     *
     * This form displays the final confirmation message to the customer,
     * with a button to proceed to Sigma Payments.
     */
    function get_form( $event_data, $submit ){
        $token   = $event_data['token'];
        $amount  = $event_data['price']['value'];
        $rate    = $event_data['price']['rate'];
        $email   = $event_data['email'];
        $fname   = $event_data['fname'];
        $lname   = $event_data['lname'];

        /* Whether the payment button should be present or not */
        $submit = $submit
            ? "<input type='submit' id='se-proceed' value='Proceed to payment' ><a
                id='se-modify' class='button' href='" . get_home_url() .
                "/sigma-events/payment/?sigma_token=" . $token . "#se-order'>Modify</a>"
            : '';

        // Price Conversion
        $amount = (int) ( $amount / $rate );

        // Calculate and Substring the Hash.
        $hash = substr( md5( $amount . $token . $this->password ), -6 );

        // Premium Event?
        if($amount > 0):
            $form = '<form action="' . $this->url . '" id="se-ep-form" method="' . $this->method . '" >';

            // Token.
            $form .= '<input type="hidden" name="token" value="' . $token . '" size=8 maxlength=8 >';

            // Amount.
            $form .= '<input type="hidden" name="amount" value="' . $amount . '" size=10 maxlength=10 >';

            // Dinamica URL.
            $form .= '<input type="hidden" name="auth_code" value="' . $hash . '" >';

            // Email.
            $form .= '<input type="hidden" name="email" value="' . $email . '" >';

            // First Name.
            $form .= '<input type="hidden" name="fname" value="' . $fname . '" >';

            // Last Name.
            $form .= '<input type="hidden" name="lname" value="' . $lname . '" >';

            $form .= $submit . '</form>';

        // Free Event?
        else:
            $form     = $this->get_free_event_form($token);
        endif;

        return $form;
    }

    /**
     * Sigma EP Rewrite
     *
     * Add rewrite rule to handle POSTs from EP.
     */
    function payment_rewrite(){
        $payment_regex = $this->payment_endpoint . '/?$';
        add_rewrite_rule($payment_regex,
        'index.php?' . $this->payment_endpoint . '=ep', 'top');
        add_rewrite_tag('%' . $this->payment_endpoint . '%', '([^&]+)');

        $payment_regex = 'reporting/ep_report/?$';
        add_rewrite_rule($payment_regex,
        'index.php?' . $this->payment_endpoint . '=ep_report', 'top');
        add_rewrite_tag('%' . $this->payment_endpoint . '%', '([^&]+)');

        $payment_regex = 'reporting/sample_ep/?$';
        add_rewrite_rule($payment_regex,
        'index.php?' . $this->payment_endpoint . '=sample_ep', 'top');
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

        if( 'ep_report' == $wp_query->query_vars[$this->payment_endpoint]):
            add_action( 'wp_head', array( $this, 'prevent_indexing' ) );
            include SIGMA_PATH . 'templates/sigma-ep-report.php';
            exit;
        elseif( 'sample_ep' == $wp_query->query_vars[$this->payment_endpoint]):
            include SIGMA_PATH . 'templates/sigma-sample-ep.php';
            exit;
        else:
            $this->process_payment_request();
        endif;
    }

    /**
     * Keep Sigma Report Page being indexed
     */
    function prevent_indexing(){
        echo '<meta name="robots" content="noindex,nofollow"/>';
    }

    /**
     * Process Sigma Processor Request
     */
    function process_payment_request(){
        // Setup Sigma Options
        $this->options = get_option('sigma_options');

        // Write Log and send debug emails.
        $this->log_and_debug_emails( $this->options, true );

        // Setup $post and $registration arrays.
        $r = $this->setup_post_and_registration_data( $_POST, $this->registration_table );
        if( -1 == $r ):
            echo "No Token Found in the Request";
            exit;
        elseif( -2 == $r ):
            echo "No Registration Record for the Token";
            exit;
        elseif( -3 == $r ):
            echo "Amounts does not Match";
            exit;
        elseif( -4 == $r ):
            echo "Hash verification failed";
            exit;
        elseif( $r ):
            $this->post         = $r['post'];
            $this->registration = $r['registration'];
        else:
            echo "Error Processing Request";
            exit;
        endif;

        // Update tables
        $this->update_tables( $this->payment_table, $this->registration_table, $this->post, $this->registration );

        // Send emails
        $this->send_emails( $this->options, $this->post, $this->registration );

        echo 'Processed Successfully';
        exit;
    }

    /**
     * Setup $post and $registration arrays
     */
    function setup_post_and_registration_data( $POST, $registration_table ){
        // (0) Validate
        $post['token'] = isset($POST['token'])      ? sanitize_text_field($POST['token'])       : '';
        $post['monto'] = isset($POST['amount'])     ? sanitize_text_field($POST['amount'])      : '';
        $post['hash']  = isset($POST['auth_code'])  ? sanitize_text_field($POST['auth_code'])   : '';
        $post['pwd']   = isset($POST['pwd'])        ? sanitize_text_field($POST['pwd'])         : '';

        $hash = substr( md5( $post['monto'] . $post['token'] . $post['pwd'] ), -6 );
        if( $hash != $post['hash'] ):
            $this->log_error( "\nError: Hash Verification Failed |" . $hash . "|" . $post['hash'] );
            return -4;
        endif;

        // (1) Token
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
        $price = (int) ( $registration['amount'] / $registration['rate'] );
        if($price != $post['monto']):
            $this->log_error( "\nError: Transaction amounts not matched | Token: " . $post["token"] .
                " | " . $price . " | " . $post['monto'] );
            return -3;
        endif;

        // (3) Paid
        $post['resultado']    = isset($POST['status']) ? sanitize_text_field($POST['status']) : '';

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
        $post['codeauth'] = isset($POST['codeauth']) ? sanitize_text_field($POST['codeauth']) : 'ep';

        // (5) Card
        $post['tarjeta'] = isset($POST['medium']) ? sanitize_text_field($POST['medium']) : 'ep';

        // (6) Email
        $post['email'] = isset($POST['email']) ? sanitize_email($POST['email']) : 'ep';

        // (7) DateTime
        $post['fechahora'] = current_time('mysql');

        // (8) Title
        $post['titular'] = isset($POST['title']) ? sanitize_text_field($POST['title']) : 'ep';

        // (9) Reason
        $post['motivo'] = isset($POST['reason']) ? sanitize_text_field($POST['reason']) : 'ep';

        // (10) Free
        $post['free'] = isset($POST['free']) ? sanitize_text_field($POST['free']) : 'ep';

        // (11) Processor
        $post['processor'] = 'ep';

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
}
endif;
?>
