<?php
/**
 * Dineromail Test Template
 *
 * Basis for new tests.
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

/**
 * Test Dineromail Payment Processor
 */
class SigmaTest extends WP_UnitTestCase{
    /**
     * Unit Under Test
     */
    private $test_unit;

    /**
     * Setup Test Environment
     *
     */
    public function setUp(){
        /**
         * Setup Sigma Environment
         */
        $sigma = new Sigma_Events();
        $sigma->init();
        $sigma->activation();
    }

    /**
     * Clean Database After Testing
     */
    public function tearDown(){
        global $wpdb, $sigma_events;
        $sigma_events->deactivation();

        /**
         * Delete the registration table.
         */
        $table_name     = $wpdb->prefix . $sigma_events->registration_table;
        $sql            = "DROP TABLE IF EXISTS $table_name";
        $e              = $wpdb->query($sql);

        /**
         * Delete the payment table.
         */
        $table_name     = $wpdb->prefix . $sigma_events->payment_table;
        $sql            = "DROP TABLE IF EXISTS $table_name";
        $e              = $wpdb->query($sql);
    }
}
?>
