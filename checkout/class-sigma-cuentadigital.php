<?php
if ( !class_exists('Sigma_CuentaDigital') ) :
/**
 * Sigma CuentaDigital Integration
 *
 * @package     SigmaEvents
 * @subpackage  PaymentProcessing
 * @since       version 3.9
 */
class Sigma_CuentaDigital extends Sigma_Payment_Processor
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
     * Sigma Processor Options
     */
    private $options;

    /**
     * Processed Records
     * How many records were processed out of total records in the report
     */
    private $processed;

    /**
     * Unprocessed Records for Logging
     */
    protected $raw_records;

    /**
     * CuentaDigital Log File Names
     *
     * @var string
     */
    protected $data_log  = 'cuentadigital_payments_data.log';
    protected $error_log = 'cuentadigital_payments_error.log';

    /**
     * Cron Run Interval
     */
    private $interval;

    /**
     * Construct the Sigma SalesPerson object.
     */
    function __construct( $registration_table, $payment_table ){
        // Setup table name.
        $this->registration_table = $registration_table;
        $this->payment_table = $payment_table;
        $this->processed = 0;
    }

    /**
     * Get CuentaDigital Form
     */
    function get_form($event_data, $submit){
        /**
         * Prepare Form Data
         */
        $token          = $event_data['token'];
        $amount         = $event_data['price']['value'];
        $concepto       = $event_data['concepto'];
        $event_id       = $event_data['id'];
        $email          = $event_data['email'];
        $payment_method = 'dineromail_cash';

        $payment_ends   = (int) ( ( $event_data['period']['end'] - current_time('timestamp') ) / DAY_IN_SECONDS );
		

        /* Whether the payment button should be present or not */
        $submit = $submit
            ? "<input type='submit' id='se-proceed' value='Proceed to payment' ><a
                id='se-modify' class='button' href='" . get_home_url() .
                "/sigma-events/payment/?sigma_token=" . $token . "#se-order'>Modify</a>"
            : '';
			
        // Premium Event?
        if($amount > 0):
		 $options = get_option('sigma_processor_options');
		 $acc=$options['cuentadigital']['acno'];
            $amount = number_format((float)($amount/100), 2, '.', '');
            $form = '<form action="https://www.cuentadigital.com/api.php" id="se-cuentadigital-form" method="get" >';
            $form .= '<input type="hidden" value="'.$acc.'" name="id">
                <input type="hidden" value="' . $token . '" name="codigo">
                <input type="hidden" value="ARS" name="moneda">
                <input type="hidden" value="' . $amount . '" name="precio">
                <input type="hidden" value="' . ($payment_ends + 1) . '" name="venc">
                <input type="hidden" value="' . $amount . '" name="precio2">
                <input type="hidden" value="' . ($payment_ends + 1) . '" name="venc2">
                <input type="hidden" value="' . $email . '" name="hacia">
                <input type="hidden" value="' . $concepto . '" name="concepto">';
            $form .= $submit . '</form>';

        // Free Event?
        else:
            $form  = $this->get_free_event_form($token);
        endif;

        return $form;
    }

    /**
     * Process CuentaDigital Report
     */
    function process_report($url, $date){
        $records    = $this->get_data_array($url, $date);
        $total      = sizeof($records);

        $this->process_cuentadigital_request($records);
    }

    /**
     * Get Data Array from CuentaDigital
     */
    function get_data_array($url, $date){
        /**
         * Calculate the parameters for query string
         */
        $day_start = strtotime(date('Y-m-d', current_time('timestamp')));
        $interval = $this->interval * HOUR_IN_SECONDS;

        $current_hour = date('H', current_time('timestamp'));
        $current_time = strtotime(date('Y-m-d H:0:0', current_time('timestamp')));

        if(($current_time - $interval) < $day_start){
            $lower_limit = $day_start;
        } else {
            $lower_limit = $current_time - $interval;
        }

        if(($current_time + $interval) >= ($day_start + DAY_IN_SECONDS)){
            $upper_limit = $day_start + DAY_IN_SECONDS - 1;
        } else {
            $upper_limit = $current_time + $interval;
        }

        $upper_limit = date('H', $upper_limit);
        $lower_limit = date('H', $lower_limit);

        $upper_limit = '23';
        $lower_limit = '01';

        $options = get_option('sigma_processor_options');
		
        $url = $url
             . '?control=' . $options['cuentadigital']['auth_code']
             . '&fecha=' . date('Ymd', strtotime($date) )
             . '&hour1=' . $lower_limit . '&min1=00&hour2=' . $upper_limit . '&min2=59';

        $report = wp_remote_retrieve_body( wp_remote_get($url) );

        $records = explode(PHP_EOL , $report);
        $this->raw_records = $records;

        $this->cron_log(print_r($records, true));

        $output = array();
        foreach($records as $record):
            $record = explode( '|', $record);
            if(3 < sizeof($record)):
                $fields = array();
                foreach($record as $field):
                    $fields[] = $field;
                endforeach;
                $output[] = $fields;
            endif;
        endforeach;
        return $output;
    }

    /**
     * Process CuentaDigital Records One by One
     */
    function process_cuentadigital_request($records){
        // Setup Sigma Options
        $this->options = get_option('sigma_options');
        $this->processor_options = get_option('sigma_processor_options');

        // Write Log and send debug emails.
        $this->log_and_send_debug_emails(
            $this->options,
            $this->processor_options['cuentadigital']['enable_logging'],
            $this->raw_records,
            'CuentaDigital'
        );

        foreach($records as $record):
            // Setup $post and $registration arrays.
            $r = $this->setup_post_and_registration_data( $record, $this->registration_table );
            if( $r ):
                $this->processed    = $this->processed + 1;
                $this->post         = $r['post'];
                $this->registration = $r['registration'];

                // Update tables
                $this->update_tables( $this->payment_table, $this->registration_table, $this->post, $this->registration );

                // Send emails
                $this->send_emails( $this->options, $this->post, $this->registration );
            endif;
        endforeach;
    }

    /**
     * Setup $post and $registration arrays
     *
     * $post array is a validated and formatted data array.
     *
     * $registration array is the registration record retrieved using a given token.
     *
     * Dies if errors present in the passed data.
     *
     * @param   array $record               Unprocessed Record Data
     * @param   array $registration_table   Registration Table Name
     * @return  array Double element array. 'post' and 'registration' indices.
     */
    function setup_post_and_registration_data( $record, $registration_table ){
        // (1.0) Token | Noperacion
        $post['token'] = sanitize_text_field($record[6]);
        if( '' == $post['token']):
            $this->log_error( "\nError: No token field in the record-6." );
            return false;
        endif;

        // (1.1) Registration data
        $registration = $this->get_registration_record( $registration_table, $post["token"] );
        if( ! $registration ):
            $this->log_error( "\nError: No registration record | Token: " . $post["token"] );
            return false;
        endif;

        // (2) Amount | Monto
        $post['monto'] = sanitize_text_field($record[2]);
        $price = $registration['amount'];
        if($price != $post['monto']):
            $this->log_error( "\nError: Amounts Not Matched | Token : " . $post["token"]
                . ' | Registration : ' . $price . ' | POST : ' . $post['monto']);
            return false;
        endif;

        // (3) Paid | Resulado
        // All retrieved records are paid.
        $post['resultado'] = 'paid';

        // (3.1) Already Paid
        /**
         * The below piece of code moved upwards a step to avoid the calculation of
         * registration number for unpaid records
         */
        if('paid' == $registration['paid']):
            //$this->log_error( "\nError: Already Paid | Token : " . $post["token"] );
            return false;
        endif;

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
        $post['codeauth'] = sanitize_text_field($record[5]);

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
        $post['tarjeta'] = sanitize_text_field($record[7]);
        $post['tarjeta'] = str_replace(' ', '_', $post['tarjeta']);
        $post['tarjeta'] = 'cuentadigital_' . strtolower( $post['tarjeta'] );

        // (6) Email | emailcomprador
        $post['email'] = 'no@email.com';

        // (7) DateTime | fechahora
        $post['fechahora'] = date('Y-m-d', strtotime($record[0])) . ' '
            . date('H:i:s', strtotime($record[1]));

        // (8) Title | titular
        $post['titular'] = 'No Title';

        // (9) Reason | motivo
        $post['motivo'] = $record[4];

        // (11) Processor
        $post['processor'] = 'cuentadigital';

        $output = array(
            'registration' => $registration,
            'post'         => $post
        );
        return $output;
    }

    /**
     * Run Cron Routine
     */
    function run_cron($interval){
        /**
         * Setup interval in hours
         *
         * uw@uw:~$ php -r "echo ceil(3500/3600) . PHP_EOL;"
         */
        $this->interval = ceil($interval/HOUR_IN_SECONDS);
        $options = get_option('sigma_processor_options');
        if($options['cuentadigital']['enable_sandbox']):
            $report_url = $options['cuentadigital']['sandbox_report_url'];
        else:
            $report_url = $options['cuentadigital']['production_report_url'];
        endif;

        $date = date('Y-m-d', current_time('timestamp'));

        $summary = $this->process_report($report_url, $date);
        $this->cron_log($summary);
        return 'done';
    }

    /**
     * Special Cron Log for CuentaDigital
     */
    function cron_log($message){
        $cron_log = SIGMA_PATH . 'logs/cuentadigital_cron.log';
        $data  = "\n" .  current_time('mysql') . " " . $message;
        $r     = file_put_contents($cron_log, $data, FILE_APPEND | LOCK_EX);
        return $r;
    }
}
endif;
?>
