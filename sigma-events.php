<?php
/**
 * Plugin Name: Sigma Events
 * Plugin URI: sigmasecurepay.info
 * Description: Handles Registrations for Sports Events with Third Party Payment Processors (Decidir, Dineromail, CuentaDigital, EasyPlanners, etc). </br> <strong>After Install:</strong> </br> </br> -Change the permalink structure to "Post name" (Settings->Permalinks->Post Name) </br> -Install "Responsive" theme. (Appearence->Themes->Add New) </br> -Set your timezone. (Settings->General->Timezone) </br> -Install "Really Simple CAPTCHA" plugin </br> -Install "Disable Comments" plugin, then setup propertly </br> -Install "WP Maintenance Mode" plugin </br> -Install "Language Switcher" plugin (zip file included inside sigma plugin package) </br> -Setup DB parameters in this file "cron-config.php", otherwise CSV to DB will not work </br> -Set "CSV to Database" cron job from Cpanel, for details see under "Dashboard"->"CSV to DB" </br> -Install "Debug Bar" and "Debug Bar Console" plugins, for usage details see under "Dashboard"->"CSV to DB"
 * Version: 4.4.1
 * Author: SigmaSystems
 * Author URI: sigmasecurepay.info
 * Text Domain: se
 * Domain Path: /languages/
 */
if( !defined( 'ABSPATH' ) ){
    header('HTTP/1.0 403 Forbidden');
    die('No Direct Access Allowed!');
}

require_once 'sigma-config.php';

if ( !class_exists('Sigma_Events') ) :
/**
 * Sigma Events Class.
 */
class Sigma_Events{
    /**
     * Sigma Events Table Name
     */
    public $registration_table = 'sigma_events';

    /**
     * Decidir POSTs Table
     */
    public $payment_table = 'sigma_payments';

    /**
     * Sigma Events
     */
    private $events;

    /**
     * Sigma Tourist Agent
     */
    private $tourist_agent;

    /**
     * Sigma Discount Codes
     */
    public $codes;

    /**
     * Sigma Forget Codes
     */
    public $forget_code;

    /**
     * Sigma Utilities
     */
    private $utilities;

    /**
     * Sigma Security
     */
    public $security;

    /**
     * Admin Menu
     */
    private $admin_menu;

    /**
     * Security Menu
     */
    private $security_menu;

    /**
     * Processor Menu
     */
    private $processor_menu;

    /**
     * Sigma Products
     */
    private $products;

    /**
     * Sigma Sequences
     */
    public $sequences;

    /**
     * Decidir Payments
     */
    public $payments_decidir;

    /**
     * Dineromail Payments
     */
    public $payments_dineromail;

    /**
     * Payments via a salesperson
     */
    public $payments_salesperson;

    /**
     * CuentaDigital Payments
     */
    public $payments_cuentadigital;

    /**
     * Payments via EP
     */
    public $payments_ep;

    /**
     * Sigma Payments
     */
    public $payments_sigma;

    /**
     * Payment Tracker
     */
    private $payment_tracker;

    /**
     * Payment Debugger
     */
    private $payment_debugger;

    /**
     * Admin Pointers
     */
    private $admin_pointers;

    /**
     * Misc Options
     */
    public $misc;

    /**
     * Sigma Cron Daemon
     */
    public $cron;

    /**
     * Sigma CSV to DB
     */
    public $csv_to_db;

    /**
     * Sigma Events Constructor
     */
    function __construct(){
        /**
         * Load translation files.
         */
        $this->load_language('se');

        /**
         * Create a table for registration data on plugin activation.
         */
        register_activation_hook( __FILE__, array( $this, 'activation' ));

        /**
         * Flush rewrite rules on deactivation.
         */
        register_deactivation_hook( __FILE__, array( $this, 'deactivation' ));

        /**
         * Events action links.
         */
        add_filter( 'plugin_action_links_' . plugin_basename(__FILE__),
            array( $this, 'plugin_action_links' ) );
    }

    /**
     * Initialize Sigma Events Plugin.
     */
    function init(){
        /**
         * Intantiate Sigma Event.
         */
        require SIGMA_PATH . 'inc/class-sigma-events.php';
        $this->events = new Sigma_Event( $this->registration_table );

        /**
         * Intantiate Sigma Tourist Agent.
         */
        require SIGMA_PATH . 'inc/class-sigma-tourist-agent.php';
        $this->tourist_agent = new Sigma_Tourist_Agent( $this->registration_table );

        /**
         * Intantiate Sigma Discount Codes.
         */
        require SIGMA_PATH . 'inc/class-sigma-discount-codes.php';
        $this->codes = new Sigma_Codes( $this->registration_table );

        /**
         * Intantiate Sigma Forget Codes.
         */
        require SIGMA_PATH . 'inc/class-sigma-forget-code.php';
        $this->forget_code = new Sigma_Forget_Code( $this->registration_table );

        /**
         * Intantiate Sigma Utilities.
         */
        require SIGMA_PATH . 'inc/class-sigma-utilities.php';
        $this->utilities = new Sigma_Utilities();

        /**
         * Intantiate Sigma Security.
         */
        require SIGMA_PATH . 'inc/class-sigma-security.php';
        $this->security = new Sigma_Security();

        /**
         * Instantiate the Admin Menu.
         */
        require SIGMA_PATH . 'admin/class-sigma-admin-menu.php';
        $this->admin_menu = new Sigma_Admin_Menu( $this->registration_table );

        /**
         * Instantiate the Security Menu.
         */
        require SIGMA_PATH . 'admin/class-sigma-security-menu.php';
        $this->security_menu = new Sigma_Security_Menu( $this->registration_table );

        /**
         * Instantiate the Processor Menu.
         */
        require SIGMA_PATH . 'admin/class-sigma-processors-menu.php';
        $this->processor_menu = new Sigma_Processors_Menu( $this->registration_table );

        /**
         * Instantiate the Admin Pointers.
         */
        require SIGMA_PATH . 'inc/class-sigma-admin-pointers.php';
        $this->admin_pointers = new Sigma_Admin_Pointers();

        /**
         * Intantiate Sigma Products.
         */
        require SIGMA_PATH . 'inc/class-sigma-products.php';
        $this->products = new Sigma_Products();

        /**
         * Intantiate Sigma Sequences.
         */
        require SIGMA_PATH . 'inc/class-sigma-sequences.php';
        $this->sequences = new Sigma_Sequences();

        /**
         * Sigma_Payment_Processor Abstract Class
         */
        require 'checkout/abstract-sigma-payment-processor.php';

        /**
         * DECIDIR Secure Payment System Integration.
         */
        require SIGMA_PATH . 'checkout/class-sigma-decidir-sps.php';
        $this->payments_decidir = new Sigma_Decidir_SPS(
            $this->registration_table,
            $this->payment_table);

        /* Dineromail Payment System Integration */
        require SIGMA_PATH . 'checkout/class-sigma-dineromail.php';
        $this->payments_dineromail = new Sigma_Dineromail(
            $this->registration_table,
            $this->payment_table);

        /* Paypal Payment System Integration */
        require SIGMA_PATH . 'checkout/class-sigma-paypal.php';
        $this->payments_paypal = new Sigma_PayPal(
            $this->registration_table,
            $this->payment_table);

        /* Payments via Salespeople */
        require SIGMA_PATH . 'checkout/class-sigma-salesperson.php';
        $this->payments_salesperson = new Sigma_SalesPerson(
            $this->registration_table,
            $this->payment_table);

        /* CuentaDigital Payments */
        require SIGMA_PATH . 'checkout/class-sigma-cuentadigital.php';
        $this->payments_cuentadigital = new Sigma_CuentaDigital(
            $this->registration_table,
            $this->payment_table);

        /* Payments via EP */
        require SIGMA_PATH . 'checkout/class-sigma-ep.php';
        $this->payments_ep = new Sigma_EP(
            $this->registration_table,
            $this->payment_table);

        /* Sigma Payment System Integration */
        require SIGMA_PATH . 'checkout/class-sigma-processor.php';
        $this->payments_sigma = new Sigma_Processor(
            $this->registration_table,
            $this->payment_table);

        /**
         * Sigma Payment Tracker.
         */
        require SIGMA_PATH . 'inc/class-sigma-payment-tracker.php';
        $this->payment_tracker = new Sigma_Payment_Tracker( $this->registration_table );

        /**
         * Intantiate Sigma Cron Daemon.
         */
        require SIGMA_PATH . 'inc/class-sigma-cron.php';
        $this->cron = new Sigma_Cron();

        /**
         * Instantiate Sigma CSV to DB
         */
        require SIGMA_PATH . 'admin/class-sigma-csv-to-db.php';
        $this->csv_to_db = new Sigma_CSV_To_DB($this->registration_table);

    }

    /**
     * Load Translation Files
     *
     * Translation domain is 'se',
     * abbreviation for 'sigma events'.
     */
    function load_language( $domain ){
        load_plugin_textdomain(
            $domain,
            null,
            dirname(plugin_basename(__FILE__)) . '/languages'
        );
    }

    /**
     * Plugin Action Links
     *
     * Makes it easy to find the settings page from the
     * plugins admin page.
     */
    function plugin_action_links($links){
        $settings = admin_url() . 'edit.php?post_type=events&page=manage-sigma-events';
        return array_merge(
        array('settings' => '<a href="' . $settings . '">' . __('Event Data', 'se') . '</a>'),
            $links);
    }

    /**
     * Sigma Events Activation
     *
     * Create a table to store data about registrations.
     */
    function activation(){
        global $wpdb;
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        $registration_table = $wpdb->prefix . $this->registration_table;
        $registration_sql = "CREATE TABLE IF NOT EXISTS $registration_table (
            id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
            token VARCHAR(20) NOT NULL,
            reg_time DATETIME NOT NULL,
            eid MEDIUMINT NOT NULL,
            fname VARCHAR(20) NOT NULL,
            lname VARCHAR(20) NOT NULL,
            argentinian BOOL NOT NULL,
            country VARCHAR(20) NOT NULL,
            dni VARCHAR(20) NOT NULL,
            email VARCHAR(50) NOT NULL,
            gender VARCHAR(9) NOT NULL,
            bday DATE NOT NULL,
            phone VARCHAR(20),
            addr VARCHAR(80),
            club VARCHAR(50),
            disc_code VARCHAR(20),
            ans VARCHAR(50),
            extra_items VARCHAR(400),
            rate FLOAT,
            ip VARCHAR(40) NOT NULL,
            amount INT DEFAULT 0,
            medium VARCHAR(30),
            paid VARCHAR(50),
            payment VARCHAR(20),
            seq_no INT(10)
        )
        engine = InnoDB
        default character set = utf8
        collate = utf8_unicode_ci;";
        $r = dbDelta($registration_sql);

        $payment_table = $wpdb->prefix . $this->payment_table;
        $payment_sql = "CREATE TABLE IF NOT EXISTS $payment_table (
            id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
            codautorizacion VARCHAR(20),
            tarjeta VARCHAR(30),
            processor VARCHAR(10),
            emailcomprador VARCHAR(40),
            resultado VARCHAR(20) NOT NULL,
            fechahora DATETIME,
            titular VARCHAR(30),
            token VARCHAR(20) NOT NULL,
            motivo VARCHAR(50),
            monto VARCHAR(20) NOT NULL,
            ip VARCHAR(20),
            index token_index (token)
        )
        engine = InnoDB
        default character set = utf8
        collate = utf8_unicode_ci;";
        $r = dbDelta($payment_sql);
        $this->flush_rewrites();
        $this->cron->install_cron();
    }

    /**
     * Sigma Events Deactivation
     */
    function deactivation(){
        /**
         * Flush once on plugin deactivation too.
         */
        flush_rewrite_rules();
        $this->cron->uninstall_cron();
    }

    /**
     * Add all rewrites and flush rules.
     */
    function flush_rewrites(){
        /**
         * Add custom post types.
         */
        $this->events->register_events_post_type();

        /**
         * Add tevents post types.
         */
        $this->tourist_agent->register_sigma_tourist_events_post_type();

        /**
         * Add discount codes post types.
         */
        $this->codes->register_sigma_codes_post_type();

        /**
         * Add new rewrite rules for events( /registration, /payment, /checkout ).
         */
        $this->events->sigma_rewrite();

        /**
         * Sigma Tourist Agent Rewrite
         */
        $this->tourist_agent->sigma_tourist_rewrite();

        /**
         * Sigma Utility Rewrite
         */
        $this->utilities->sigma_utility_rewrite();

        /**
         * Sigma Products Rewrite
         */
        $this->products->register_sigma_products_post_type();

        /**
         * Add rewrite rules for Decidir SPS.
         */
        $this->payments_decidir->decidir_rewrite();

        /**
         * Add rewrite rules for Dineromail.
         */
        $this->payments_dineromail->dineromail_rewrite();

        /**
         * Add rewrite rules for Sigma Processor.
         */
        $this->payments_sigma->payment_rewrite();

        /**
         * Add rewrite rules for Sigma EP Processor.
         */
        $this->payments_ep->payment_rewrite();

        /**
         * Add rewrite rules for Sigma Tracker.
         */
        $this->payment_tracker->tracker_rewrite();

        /**
         * Forget code rewrite
         */
        $this->forget_code->forget_code_rewrite();

        /**
         * Flush once on plugin activation.
         */
        flush_rewrite_rules();
    }
}

/**
 * Instantiate the Sigma Events Plugin.
 */
$sigma_events = new Sigma_Events();
$sigma_events->init();
endif;
?>
