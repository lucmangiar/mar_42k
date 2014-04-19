<?php
/**
 * Sigma Sequence Number Checker for Duplication
 *
 * Checks for duplicated sequence numbers and reassigns as needed.
 *
 * Logs the assigned numbers in a seperate log file for each run.
 *
 * @package     SigmaEvents
 * @subpackage  SigmaCron
 * @since       Version 4.3
 */
include_once 'cron-config.php';

class Sigma_Seq_Number_Checker{
    /**
     * Per run update limit
     */
    private $limit = 1000;

    /**
     * Event ID
     */
    private $eid;

    /**
     * Run session id
     */
    private $session_id;

    /**
     * Start
     */
    private $start;

    /**
     * Constructor
     */
    function __construct($eid){
        $this->eid = $eid;
    }

    /**
     * Get Duplicated Numbers
     */
    function get_duplicated_numbers(){
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

        $table_name = TABLE_PREFIX . 'sigma_events';
        $query = sprintf("select seq_no, count(*) as count from $table_name
            where eid = $this->eid and paid = 'paid' group by seq_no having count > 1 limit $this->limit;");

        $result = mysql_query($query);

        if (!$result) {
            $message  = 'Invalid query: ' . mysql_error() . "\n";
            $message .= 'Whole query: ' . $query;
            die($message);
        }

        $output = array();
        while ($row = mysql_fetch_assoc($result)) {
            array_push($output, $row);

        }
        mysql_close($link);
        return $output;
    }

    /**
     * Fix Duplicates
     */
    function fix_duplicates($sequence_numbers){
        global $sigma_events;
        /**
         * Construct a Session Identifier
         */
        $this->session_id = 'event_' . $this->eid . '_' . time();

        $table_name = TABLE_PREFIX . 'sigma_events';

        /**
         * Get the where clause
         */
        $where = $this->get_where_clause($this->eid);

        /**
         * Get Unassigned Sequence Numbers
         */
        $numbers = $this->get_next_numbers($where, sizeof($sequence_numbers) * 2);

        foreach($sequence_numbers as $seq_no){
            $i = 0;
            $count = $seq_no['count'];
            $seq_no = $seq_no['seq_no'];

            if(0 == $seq_no) continue;
            if(10 < $count) continue;

            $query = sprintf("select token, reg_time, fname, lname, eid, medium, paid, seq_no from $table_name
                where eid = $this->eid and seq_no = $seq_no;");

            $result = mysql_query($query);

            while ($row = mysql_fetch_assoc($result)) {
                if(0 == $i) {
                    $row['new_seq_no'] = 'Unchanged';
                    $this->log($row);
                } else {
                    $row['new_seq_no'] = array_shift($numbers);
                    $this->update_sequence_number(
                        $row['token'],
                        $row['seq_no'],
                        $row['new_seq_no']
                    );
                    $this->log($row);
                }
                $i++;
            }
            $this->log('', true);
        }
    }

    /**
     * Update Sequence Number
     */
    function update_sequence_number($token, $seq_no, $new_seq_no){
        $table_name = TABLE_PREFIX . 'sigma_events';
        $query = sprintf("update $table_name set seq_no = '%d'
            where token = '%s' and seq_no = '%s';",
            mysql_real_escape_string($new_seq_no),
            mysql_real_escape_string($token),
            mysql_real_escape_string($seq_no)
        );

        global $wpdb;
        $result = $wpdb->query($query);

        if (!$result) {
            $message  = 'Invalid query: ' . mysql_error() . "\n";
            $message .= 'Whole query: ' . $query;
            die($message);
        }

        return $result;
    }

    /**
     * Get New Sequence Number
     */
    function get_next_numbers($where, $total){
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
            WHERE $where
            ORDER BY seq_no
            DESC
            ", ARRAY_A );

        $assigned = array();
        foreach( $registrations as $registration ):
            $assigned[$registration['seq_no']] = true;

        endforeach;

        $i = $this->start;
        $j = 0;
        $numbers = array();
        while( $j < $total ){
            $i++;
            if(isset($assigned[$i])){
                continue;
            } else {
                array_push($numbers, $i);
                $j++;
            }
        }

        return $numbers;
    }

    /**
     * Get Where Clause
     */
    function get_where_clause($event_id){
        /**
         * Get the global 'sigma_sequences' array
         */
        $sequences_option = get_option( 'sigma_sequences' );

        /**
         * Get the event specific sequence identifier ( Event Meta Information ),
         *  which is also a top level node in the sequences array.
         */
        $current_sequence = get_post_meta($event_id, 'sigma_event_sequence', true);
        if($current_sequence['sequence'] == ''):
            $sequence = 0;
        else:
            $sequence = $current_sequence['sequence'];
        endif;

        $sequences = get_option( 'sigma_sequences' );

        // Get starting number
        if( isset($sequences[$sequence]['start']) ):
            $this->start = $sequences[$sequence]['start'];
        else:
            $this->start = 0;
        endif;

        /**
         * Globalize WPDB and Sigma Events Objects.
         */
        global $wpdb, $sigma_events;

        /**
         * Construct the where clause.
         */
        $id_ring = array();
        $args = array(
            'post_type' => 'events',
            'post_status' => 'publish',
            'numberposts' => -1
        );
        $events = get_posts( $args );
        foreach( $events as $event ):
            $event_sequence = get_post_meta( $event->ID, 'sigma_event_sequence', true );
            if( $event_sequence['sequence'] == $sequence ):
                array_push( $id_ring, $event->ID );
            endif;
        endforeach;

        if( empty( $id_ring ) ) return false;

        $where = ' ( eid ) IN ( ' . implode( ', ', $id_ring ) . ' )'
            . " AND paid = 'paid' ";

        return $where;
    }

    /**
     * Log
     */
    function log($data, $seperator = false){
        $seq_log = SIGMA_PATH . 'logs/csv-cron/log_' . $this->session_id . '.log';
        $output   = '';
        if($seperator){
            $output  .= " | -----------------------------------------------------";
            $output  .= "-----------------------------------";
        } else {
            $output  .= " | " . str_pad($data['token'], 15);
            $output  .= " | " . str_pad($data['seq_no'], 10);
            $output  .= " | " . str_pad($data['new_seq_no'], 10);
            $output  .= " | " . str_pad($data['eid'], 5);
            $output  .= " | " . $data['reg_time'];
            $output  .= " | " . $data['paid'];
            $output  .= " | " . $data['fname'];
            $output  .= " | " . $data['lname'];
            $output  .= " | " . $data['medium'];
        }
        $output  .= "\n";
        $r      = file_put_contents($seq_log, $output, FILE_APPEND | LOCK_EX);
        return $r;
    }
}

$sigma_seq_no_checker = new Sigma_Seq_Number_Checker(111);
$output = $sigma_seq_no_checker->get_duplicated_numbers();

if(sizeof($output)){
    include_once ROOT . 'wp-load.php';
    $sigma_seq_no_checker->fix_duplicates($output);
    echo date('Y-m-d H:i:s') . " Duplicated resolved: " . sizeof($output) . "\n";
    exit;
}

echo date('Y-m-d H:i:s') . " No Duplicated Numbers " . "\n";
exit;
?>