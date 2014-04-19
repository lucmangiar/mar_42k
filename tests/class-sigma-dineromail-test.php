<?php
/**
 * Define PATH and URL
 */
if ( !defined('SIGMA_PATH') )
    define( 'SIGMA_PATH', '/media/www/sigma/wp-content/plugins/sigma-events/' );
if ( !defined('SIGMA_URL') )
    define( 'SIGMA_URL', 'http://localhost/sigma/wp-content/plugins/sigma-events/' );

/**
 * Require Files
 */
require_once 'sigma-events.php';
require_once 'checkout/class-sigma-dineromail.php';

/**
 * Test Dineromail Payment Processor
 */
class SigmaDineromailTest extends WP_UnitTestCase{
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

        $api = SIGMA_URL . 'tests/api/dineromail-ipn.php';
        $this->dineromail->set_ipn_url( $api );
    }

    /**
     * Test Getting the Payment Form
     */
    public function testGetForm(){
        /**
         * Dummy Form Data
         */
        $token      = '1234abcabc';
        $price      = 100;
        $event_name = 'dummy event';
        $submit     = true;

        /**
         * Get the Form
         */
        $r = $this->dineromail->get_form(
            $token,
            $price,
            $event_name,
            $submit
        );

        /**
         * Assert the proper retrieval of a form
         */
        $this->assertRegExp( '/^<form.*\/form>$/s', $r,
            'Output form is not in the proper form' );
    }

    /**
     * Test Dineromail Rewrite pair insertion
     *
     * After running the rewrite method check $wp_rewrite global for proper
     * inclusion of the rewrite rule
     */
    public function testDineromailRewrite(){
        $match = 'post_dm_ipn$' ;
        $rule  = 'index.php?dineromail=ipn';

        $this->dineromail->dineromail_rewrite();

        flush_rewrite_rules();
        global $wp_rewrite;

        if( false):
            $msg = print_r( $wp_rewrite, true );
            $this->dineromail->log( $msg );
        endif;

        $rewrite = $wp_rewrite->extra_rules_top[$match];
        $this->assertEquals( $rewrite, $rule,
            'Cannot add the new rewrite rule');

    }

    /**
     * Test Dineromail request redirection.
     *
     * One of assertions does verify the return value
     * after adding the query var manually.
     */
    public function testRedirectDineromailRequests(){
        if( false):
            global $wp_query;
            $msg = print_r( $wp_query, true );
            $this->dineromail->log( $msg );
        endif;

        /**
         * Assert not getting anything before adding the query var
         */
        $r = $this->dineromail->redirect_dineromail_requests();
        $this->assertNull( $r, 'Redirect is not null without query var' );

        /**
         * Add the query var
         */
        global $wp_query;
        $wp_query->set( 'dineromail', 'ipn');

        /**
         * Now a false should be received instead null,
         * since the query var is set
         */
        $r = $this->dineromail->redirect_dineromail_requests( true );
        $this->assertTrue( $r, 'Redirect is not false without notificacion' );
    }

    /**
     * Create a dummy record and test the functionality
     */
    public function testSetupAndRegistrationData(){
        /**
         * Token is missing
         */
        $POST = array();
        global $sigma_events;
        $r = $this->dineromail->setup_post_and_registration_data(
            $POST,
            $sigma_events->registration_table
        );
        $this->assertFalse( $r, 'Not returning false when token is missing' );

        /**
         * Non existent token
         */
        $POST = array(
            'token' => 'helloworld'
        );
        $this->assertFalse( $r, 'Not returning false for a non-existant token' );

        /**
         * Add a dummy registration record and see
         * whether we can get returned an array of two elements
         */
        $data['id']             = null;
        $data['token']          = 'helloworld';
        $data['reg_time']       = date('Y-m-d H:i:s', time());
        $data['amount']         = 1200;
        $data['eid']            = 55;
        $data['fname']          = 'fname';
        $data['lname']          = 'lname';
        global $wpdb;
        $table_name = $wpdb->prefix . $sigma_events->registration_table;
        $r = $wpdb->insert( $table_name, $data);
        $POST = array(
            'token' => $data['token'],
            'paid' => 1,
            'amount' => 12,
            'method' => 2
        );
        $r = $this->dineromail->setup_post_and_registration_data(
            $POST,
            $sigma_events->registration_table
        );
        if( false ):
            $msg = print_r( $r, true);
            $this->dineromail->log( $msg );
        endif;
        $this->assertCount( 2, $r, 'Unable to Process Registration Data' );
    }

    /**
     * Assert Failure when no registration record in the DB
     * Assert correct returning of the the False for this case
     *
     * Set a Notificacion POST variable
     *
     * Process payment, inspect Logs and Database
     */
    public function testProcessPayment(){
        /**
         * Without 'Notification'
         */
        $r = $this->dineromail->process_payment();
        $this->assertFalse( $r, 'Payment Processing Failed - No Notificacion' );

        /**
         * Notification isn't properly formatted
         */
        $_POST['Notificacion'] = 'hello world';
        $r = $this->dineromail->process_payment();
        $this->assertFalse( $r, 'Payment Processing Failed - Invalid Format' );

        /**
         * Test with sample data
         */
        $sample_1 = '<notificacion>
                    <tiponotificacion>1</tiponotificacion>
                    <operaciones>
                    <operacion>
                    <tipo>1</tipo>
                    <id>XA5547</id>
                    </operacion>
                    </operaciones>
                    </notificacion>';
        $_POST['Notificacion'] = $sample_1;
        $r = $this->dineromail->process_payment();
        $this->assertFalse( $r, 'Payment Processing Failed - Request Failed' );
    }

    /**
     * Test a full transaction
     *
     * Adds a registration record
     * POSTs a notification
     * Assert everything processed as expected
     */
    public function testProcessPaymentFull(){
        global $sigma_events;
        /**
         * Add a dummy registration record and see
         * whether we can get returned an array of two elements
         */
        $data['id']             = null;
        $data['token']          = 'dinloc9211';
        $data['reg_time']       = date('Y-m-d H:i:s', time());
        $data['amount']         = 1330;
        $data['eid']            = 55;
        $data['fname']          = 'fname';
        $data['lname']          = 'lname';
        global $wpdb;
        $table_name = $wpdb->prefix . $sigma_events->registration_table;
        $r = $wpdb->insert( $table_name, $data);
        /**
         * Test with sample data
         */
        $sample_1 = '<notificacion>
                    <tiponotificacion>1</tiponotificacion>
                    <operaciones>
                    <operacion>
                    <tipo>1</tipo>
                    <id>dinloc9211</id>
                    </operacion>
                    </operaciones>
                    </notificacion>';
        $_POST['Notificacion'] = $sample_1;
        $r = $this->dineromail->process_payment();
        $this->assertTrue( $r, 'Payment Processing Failed - Request Failed' );
    }

    /**
     * Delete Tables after running tests
     */
    public function tearDown(){
        global $sigma_events;
        global $wpdb;
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
