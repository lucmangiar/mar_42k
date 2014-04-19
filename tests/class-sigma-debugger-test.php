<?php
/**
 * Sigma Debugger Tests
 */

/**
 * Define PATH and URL constants
 */
if ( !defined('SIGMA_PATH') )
    define( 'SIGMA_PATH', '/media/www/sigma/wp-content/plugins/sigma-events/' );
if ( !defined('SIGMA_URL') )
    define( 'SIGMA_URL', 'http://localhost/sigma/wp-content/plugins/sigma-events/' );

/**
 * Require Files
 */
require_once 'sigma-events.php';
require_once 'inc/class-sigma-debugger.php';

/**
 * Test Dineromail Payment Processor
 */
class SigmaTest extends WP_UnitTestCase{
    /**
     * Class Under Test
     */
    private $debugger;

    /**
     * Set Testable Options
     *
     */
    public function setUp(){
        $sigma      = new Sigma_Events();
        $sigma->init();
        $sigma->activation();
        $this->debugger = new Sigma_Debugger();
    }

    public function testPrepareIdArray(){
        $_POST['tokens'] = 'testcase4_1';
        $r = $this->debugger->prepare_id_array();
        $response = $this->debugger->get_dineromail_response( $r );
        //$this->log_data( $r );
        $this->assertCount( 1, $r, 'Invalid token count' );

        $_POST['tokens'] = 'testcase4_1, testcase2, testcaset';
        $r = $this->debugger->prepare_id_array();
        $response = $this->debugger->get_dineromail_response( $r );
        //$this->log_data( $r );
        $this->assertCount( 3, $r, 'Invalid token count' );
    }

    public function testGetDineromailResponse(){
        $id_array = array( 'testcase4_1' );
        $response = $this->debugger->get_dineromail_response( $id_array );
        //$this->log_data( $response );
        $this->assertInstanceOf( 'SimpleXMLElement', $response,
            'Invalid Dineromail Response' );
    }

    public function testProcessToken(){
        $id_array = array( 'testcase4_1' );
        $response = $this->debugger->get_dineromail_response( $id_array );
        $r = $this->debugger->create_registration_array( $response );
        //$this->log_data( $r );
        $this->assertInternalType( 'array', $r,
            'Dineromail Response Processing Failed' );
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

    /**
     * Log Data Received
     *
     * @param   string  $response    String to log
     *
     * @return  int     Length of the written message
     */
    function log_data( $response ){
        $sigma_log = SIGMA_PATH . 'tests/logs/general.log';
        $data      = "\n-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+";
        $data     .= "\nTime : " . date('r');
        $data     .= "\nIP    : " . $_SERVER['REMOTE_ADDR'];
        $data     .= "\n" . print_r($response , true);
        $data     .= "\n-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+\n";
        $r         = file_put_contents($sigma_log, $data, FILE_APPEND | LOCK_EX);
        return $r;
    }

}
?>
