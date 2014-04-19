<?php
/**
 * Checks whether sequence numbers are yet to be assigned
 * and emails are yet to sent.
 *
 * If yes, then do them.
 *
 * Do *NOT* load whole WordPress just for checking.
 *
 * @package     SigmaEvents
 * @subpackage  SigmaCron
 * @since       Version 4.3
 */
include_once 'cron-config.php';
include(dirname(dirname(dirname(dirname(dirname(__FILE__))))).'/wp-load.php');
class Sigma_CSV_Cron{
    /**
     * Per run update limit
     */
    private $limit = 10;

    /**
     * Run Cron
     */
    function update_available(){
        $sigma_csv = $this->get_option('sigma_csv');

        $sigma_csv = isset($sigma_csv['option_value'])
            ? unserialize($sigma_csv['option_value'])
            : array();

        if(sizeof($sigma_csv)){
            return true;
        }

        return false;
    }

    /**
     * Get Option
     */
    function get_option($option_name){
        /**
         * Connect
         */
        $link = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
        if(!$link){
            die('Could not connect: ' . mysql_error());
        }

        /**
         * Select
         */
        $db_selected = mysql_select_db(DB_NAME, $link);
        if(!$db_selected){
            die('Could not select: ' . mysql_error());
        }


        $table_name = TABLE_PREFIX . 'options';

        $query = sprintf("SELECT option_value FROM $table_name
            WHERE option_name LIKE '%s'",
            mysql_real_escape_string($option_name)
        );

        $result = mysql_query($query);

        if (!$result) {
            $message  = 'Invalid query: ' . mysql_error() . "\n";
            $message .= 'Whole query: ' . $query;
            die($message);
        }

        $option = mysql_fetch_assoc($result);

        return $option;
    }

    /**
     * Run Cron
     */
    function run_the_cron(){
        $sigma_csv = get_option('sigma_csv');
        $i = 0;

        foreach($sigma_csv as $id => $token_array){
            foreach($token_array as $index => $token){
                if($i < $this->limit && $seq_no = $this->process($token)){
                    unset($sigma_csv[$id][$index]);
                    $this->log($id, $index, $token, $seq_no);
                    $i++;
                } else {
                    break;
                }
            }
            if(!$token_array) unset($sigma_csv[$id]);
        }

        update_option('sigma_csv', $sigma_csv);

    }

    /**
     * Assign Number and Send Email
     */
    function process($token){
        global $sigma_events;

        /**
         * Get the registration record
         */
        $registration = $sigma_events->payments_sigma->get_registration_record(
            $sigma_events->registration_table,
            $token );

        /**
         * Construct a post array
         */
        $post = array(
            'token' => $registration['token'],
            'monto' => $registration['amount'],
            'resultado' => $registration['paid'],
            'codeauth' => '',
            'tarjeta' => $registration['medium'],
            'processor' => 'debugger',
            'email' => $registration['email'],
            'fechahora' => current_time('mysql'),
            'titular' => $registration['fname'],
            'motivo' => 'CSV Cron'
        );

        /**
         * Assign a sequence number
         */
        if( 0 >= $registration['seq_no'] ):
            // Sequence Number
            if( 'paid' == $post['resultado'] ):
                $post['seq_no']         = $this->get_next_number($registration['eid']);
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

        /**
         * Update Tables
         */
        $sigma_events->payments_sigma->update_tables_debugger(
            $sigma_events->payment_table,
            $sigma_events->registration_table,
            $post,
            $registration
        );

        /**
         * Send emails
         */
        $options = get_option('sigma_options');
        $sigma_events->payments_sigma->send_emails($options, $post, $registration);

        return $post['seq_no'];
    }

    /**
     * Get New Sequence Number
     */
    function get_next_number($eid){
        /**
         * Globalize WPDB and Sigma Events Objects.
         */
        global $wpdb, $sigma_events;

        /**
         * Construct Registration Table Name.
         */
        $registration_table = $wpdb->prefix . $sigma_events->registration_table;

        /**
         * Get Recent Registrations from the Database.
         */
        $registrations = $wpdb->get_results(
            "SELECT seq_no
            FROM $registration_table
            WHERE eid = $eid
            ORDER BY seq_no
            DESC
            ", ARRAY_A );

        $assigned = array();
        foreach( $registrations as $registration ):
            $assigned[$registration['seq_no']] = true;

        endforeach;

        $i = 300;
        while(true){
            $i++;
            if(isset($assigned[$i])){
                continue;
            } else {
                return $i;
            }
        }

        return 0;
    }

    /**
     * Log
     */
    function log($id, $index, $token, $seq_no){
        $cron_log = SIGMA_PATH . 'logs/csv-cron/log-' . $id . '.log';
        $data   = current_time('mysql') . " " . $_SERVER['REMOTE_ADDR'];
        $data  .= " | " . 'processed: ' . str_pad($index, 3) . ' | ' . str_pad($token, 15) . ' | ' . $seq_no . "\n";
        $r      = file_put_contents($cron_log, $data, FILE_APPEND | LOCK_EX);
        return $r;
    }
}

$sigma_csv_cron = new Sigma_CSV_Cron();

/**
 * Do NOT load WordPress if there is nothing to update
 */
if(!$sigma_csv_cron->update_available()){
    echo date('Y-m-d H:i:s') . " CSV Cron | Nothing to update." . "\n";
    exit;
}

//include_once ROOT . 'wp-load.php';
$sigma_csv_cron->run_the_cron();
echo current_time('mysql') . " CSV Cron Finished." . "\n";
?>
