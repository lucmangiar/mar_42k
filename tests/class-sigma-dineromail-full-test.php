<?php
/**
 * Dineromail Complete Tests
 *
 * Case 1: An IPN with one token was POSTed.
 *  --  Verified the immediate request to Dineromail is in proper format.
 *      Additional and manual assertions can be made by inspecting the
 *      logged output.
 *
 * Case 2: An IPN with three tokens was POSTed.
 *  --  Verified the immediate request to Dineromail is in proper format.
 *      Additional and manual assertions can be made by inspecting the
 *      logged output.
 *
 * Case 3: Transaction details for Case 1 was POSTed back.
 *  --  Verified the DB updates properly after processing the POST.
 *      Additional and manual assertions can be made by inspecting the
 *      logged output.
 *
 */

/**
 * Define PATH and URL constants
 */
if ( !defined('SIGMA_PATH') )
    define( 'SIGMA_PATH', '/media/www/sigma/wp-content/plugins/sigma-events/' );
if ( !defined('SIGMA_URL') )
    define( 'SIGMA_URL', 'http://local.sigma.dev/wp-content/plugins/sigma-events/' );

/**
 * Require Files
 */
require_once 'sigma-events.php';
require_once 'checkout/class-sigma-dineromail.php';

/**
 * Test Dineromail Payment Processor
 */
class SigmaDineromailFullTest extends WP_UnitTestCase{
    /**
     * Class Under Test
     */
    private $dineromail;

    /**
     * Set Testable Options
     *
     */
    public function setUp(){
        $sigma      = new Sigma_Events();
        $sigma->init();
        $sigma->activation();
        $this->dineromail = new Sigma_Dineromail(
            $sigma->registration_table,
            $sigma->payment_table
        );

        /**
         * Setup Test IPN
         */
        $api = SIGMA_URL . 'tests/api/dineromail-ipn.php.public';
        $this->dineromail->set_ipn_url( $api );
    }

    /**
     * Case 1: ( See top )
     */
    public function testCase1(){
        //if(true) return;
        $sample_1 = '<notificacion>
                    <tiponotificacion>1</tiponotificacion>
                    <operaciones>
                    <operacion>
                    <tipo>1</tipo>
                    <id>testcase1</id>
                    </operacion>
                    </operaciones>
                    </notificacion>';
        $_POST['Notificacion'] = $sample_1;
        $r = $this->dineromail->process_payment();

        /**
         * Now observe the API log to see what has been posted there.
         *
         * Check whether the format is correct.
         *
         * get_token_details() should return false since a valid response
         * is never returned for this test token.
         *
         * process_payment() should return false in response to the above.
         */
        $this->assertFalse( $r, 'Case 1 Failed' );

    }

    /**
     * Case 2: ( See top )
     */
    public function testCase2(){
        if(true) return;
        $sample_2 = '<notificacion>
                    <tiponotificacion>1</tiponotificacion>
                    <operaciones>
                    <operacion>
                        <tipo>1</tipo>
                        <id>testcase2_1</id>
                    </operacion>
                    <operacion>
                        <tipo>1</tipo>
                        <id>testcase2_2</id>
                    </operacion>
                    <operacion>
                        <tipo>1</tipo>
                        <id>testcase2_3</id>
                    </operacion>
                    </operaciones>
                    </notificacion>';
        $_POST['Notificacion'] = $sample_2;
        $r = $this->dineromail->process_payment();
        /**
         * Now observe the API log to see what has been posted there.
         *
         * Check whether the format is correct.
         *
         * get_token_details() should return false since a valid response
         * is never returned for this test token.
         *
         * process_payment() should return false in response to the above.
         */
        $this->assertFalse( $r, 'Case 2 Failed' );

    }

    /**
     * Case 3 ( See top )
     */
    public function testCase3(){
        if(true) return;
        global $sigma_events;
        /**
         * Add a dummy registration record for testing
         */
        $data['id']             = null;
        $data['token']          = 'testcase3';
        $data['reg_time']       = date('Y-m-d H:i:s', time());
        $data['amount']         = 3333;
        $data['eid']            = 55;
        $data['fname']          = 'fname';
        $data['lname']          = 'lname';
        global $wpdb;
        $table_name = $wpdb->prefix . $sigma_events->registration_table;
        $r = $wpdb->insert( $table_name, $data);
        /**
         * Test with sample data
         */
        $sample_3 = '<notificacion>
                    <tiponotificacion>1</tiponotificacion>
                    <operaciones>
                    <operacion>
                    <tipo>1</tipo>
                    <id>testcase3</id>
                    </operacion>
                    </operaciones>
                    </notificacion>';
        $_POST['Notificacion'] = $sample_3;
        $r = $this->dineromail->process_payment();
        $this->assertTrue( $r, 'Case 3 Failed' );

        $record = $this->dineromail->get_registration_record(
            $sigma_events->registration_table,
            $data['token']
        );
        /**
         * Verify the DB record is updated
         * See the log file for a dump registration record
         */
        $this->dineromail->log_data( $record );
    }

    /**
     * Case 4 ( See top )
     */
    public function testCase4(){
        if(true) return;
        global $sigma_events;
        /**
         * Add three dummy registration record for testing
         */
        $data_1['id']             = null;
        $data_1['token']          = 'testcase4_1';
        $data_1['reg_time']       = date('Y-m-d H:i:s', time());
        $data_1['amount']         = 4411;
        $data_1['eid']            = 55;
        $data_1['fname']          = 'fname';
        $data_1['lname']          = 'lname';
        global $wpdb;
        $table_name = $wpdb->prefix . $sigma_events->registration_table;
        $r = $wpdb->insert( $table_name, $data_1);

        $data_2['id']             = null;
        $data_2['token']          = 'testcase4_2';
        $data_2['reg_time']       = date('Y-m-d H:i:s', time());
        $data_2['amount']         = 4422;
        $data_2['eid']            = 55;
        $data_2['fname']          = 'fname';
        $data_2['lname']          = 'lname';
        global $wpdb;
        $table_name = $wpdb->prefix . $sigma_events->registration_table;
        $r = $wpdb->insert( $table_name, $data_2);

        $data_3['id']             = null;
        $data_3['token']          = 'testcase4_3';
        $data_3['reg_time']       = date('Y-m-d H:i:s', time());
        $data_3['amount']         = 4433;
        $data_3['eid']            = 55;
        $data_3['fname']          = 'fname';
        $data_3['lname']          = 'lname';
        global $wpdb;
        $table_name = $wpdb->prefix . $sigma_events->registration_table;
        $r = $wpdb->insert( $table_name, $data_3);

        /**
         * Test with sample data
         */
        $sample_4 = '<notificacion>
                    <tiponotificacion>1</tiponotificacion>
                    <operaciones>
                    <operacion>
                        <tipo>1</tipo>
                        <id>testcase4_1</id>
                    </operacion>
                    <operacion>
                        <tipo>1</tipo>
                        <id>testcase4_2</id>
                    </operacion>
                    <operacion>
                        <tipo>1</tipo>
                        <id>testcase4_3</id>
                    </operacion>
                    <operacion>
                        <tipo>1</tipo>
                        <id>testcase4_3</id>
                    </operacion>
                    </operaciones>
                    </notificacion>';
        $_POST['Notificacion'] = $sample_4;
        $r = $this->dineromail->process_payment();
        $this->assertTrue( $r, 'Case 4 Failed' );

        /**
         * Verify the DB records are updated
         * See the log file for a dump registration record
         */
        $registration   = $wpdb->get_results(
            "
            SELECT *
            FROM $table_name
            ", ARRAY_A
        );
        $this->dineromail->log_error( 'Case 4 Registration Table' );
        $this->dineromail->log_data( $registration );

        $table_name = $wpdb->prefix . $sigma_events->payment_table;
        $payment   = $wpdb->get_results(
            "
            SELECT *
            FROM $table_name
            ", ARRAY_A
        );
        $this->dineromail->log_error( 'Case 4 Payment Table' );
        $this->dineromail->log_data( $payment );
    }

    /**
     * Delete Tables after running tests
     */
    public function tearDown(){
        global $wpdb, $sigma_events;
        $sigma_events->deactivation();


        // Delete the registration table.
        $table_name     = $wpdb->prefix . $sigma_events->registration_table;
        $sql            = "DROP TABLE IF EXISTS $table_name";
        $e              = $wpdb->query($sql);

        // Delete the payment table.
        $table_name     = $wpdb->prefix . $sigma_events->payment_table;
        $sql            = "DROP TABLE IF EXISTS $table_name";
        $e              = $wpdb->query($sql);
    }
}
?>
