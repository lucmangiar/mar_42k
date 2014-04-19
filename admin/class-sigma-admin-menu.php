<?php
if ( !class_exists('Sigma_Admin_Menu') ) :
/**
 * Sigma Admin Menu Class
 *
 * An admin menu for Sigma Events Booking Plugin.
 *
 * @package     SigmaEvents
 * @subpackage  AdminMenu
 */
class Sigma_Admin_Menu{
    // Store the table name.
    private $table_name;

    // Sigma Default Options.
    private $options;

    // Sigma Debugger
    private $debugger;

	/**
  	 * Admin Menu Constructor
  	 */
	function __construct($table_name){
        // Setup the table name
        $this->table_name = $table_name;

        // Setup Default Options
        $this->set_sigma_default_options();

        // Add Sigma Options if necessary.
        add_action('after_setup_theme', array($this, 'sigma_options_init'));

        // Set Sigma Settings Tabs.
        $this->set_settings_page_tabs();

  		// Hook to admin menu and create our menu.
  		add_action('admin_menu', array($this, 'admin_menu'));

  		// Register Settings.
  		add_action('admin_init', array($this, 'register_settings'));

        require SIGMA_PATH . 'inc/class-sigma-debugger.php';
        $this->debugger = new Sigma_Debugger();
	}

    /**
     * Setup Sigma Options Defaults.
     */
    function set_sigma_default_options(){
		 global $wpdb;
            $table = $wpdb->prefix .'sigma_events';
        $this->options = array(
            'sql_query'     => 'select id from'.$table.'where token = 000',
            'send_name'     => 'Sigma Payments',
            'send_email'    => 'payments@sigmasecurepay.info',

            'email_user'                => true,
            'user_products_email'       => true,

            'admin_email'               => get_option('admin_email'),
            'enable_admin_email'        => true,
            'enable_post_admin_email'   => false,
            'enable_debug_admin_email'  => false,

            'enable_organizer_email'                => true,
            'enable_organizer_user_email'           => true,
            'enable_organizer_user_product_email'   => true,

            'dev_email'                 => 'javierpetrucci@gmail.com',
            'enable_dev_email'          => false,
            'enable_post_dev_email'     => true,
            'enable_debug_dev_email'    => true,

            'download_registration_data'    => true,
            'download_payment_data'         => true,

            'enable_tracker_get'        => true,
            'enable_tracker_post'       => true,

            'enable_security_module'     => true,
            'enable_forget_code_captcha' => true,
            'enable_forget_code_email'   => false
        );
    }

    /**
     * Add Sigma Options if necessary.
     */
    function sigma_options_init(){
        $sigma_options = get_option('sigma_options');
        if( false == $sigma_options )
            update_option('sigma_options', $this->options);
    }

    /**
     * Set Default Tabs
     */
    function set_settings_page_tabs(){
        $this->tabs = array(
            'utilities'     => 'Utilities',
            'emails'        => 'Emails',
            'advanced'      => 'Advanced',
            'debugger'      => 'Debugger',
            'registration'  => 'Registration',
            'payment'       => 'Payment',
            'monitor'       => 'Monitor'
        );
    }

    /**
     * Echo the tabs bar.
     */
    function sigma_settings_tabs(){
        if ( isset ( $_GET['tab'] ) ) :
            $current = $_GET['tab'];
        else:
            $current = 'utilities';
        endif;

        $links = array();
        foreach( $this->tabs as $tab => $name ) :
            if ( $tab == $current ) :
                $links[] = '<a class="nav-tab nav-tab-active"
                href="?post_type=events&page=manage-sigma-events&tab=' .
                $tab . '" > ' . $name . '</a>';
            else :
                $links[] = '<a class="nav-tab"
                href="?post_type=events&page=manage-sigma-events&tab=' .
                $tab . '" >' . $name . '</a>';
            endif;
        endforeach;

        echo '<h2 class="nav-tab-wrapper">';
            foreach ( $links as $link ):
                echo $link;
            endforeach;
        echo '</h2>';
    }

	/**
  	 * Register Sigma Settings
  	 */
	function register_settings(){
        /* Store Sigma Settings in 'sigma_options' option */
  		register_setting( 'sigma_options', 'sigma_options', array( $this, 'sigma_options_validate' ) );
	}

	/**
  	 * Add Admin Menu
  	 *
  	 * Register an admin menu for Sigma Events.
  	 */
	function admin_menu(){
  		$hook_suffix = add_menu_page(
            __('Sigma Events', 'se'),
            __('Dashboard', 'se'),
            'manage_options',
            'manage-sigma-events',
            array($this, 'render_admin_menu'),
            SIGMA_URL . 'assets/sigma.png',
            25
  		);
  		add_action('admin_print_scripts-' . $hook_suffix, array($this, 'admin_scripts'));
	}

	/**
  	 * Render Admin Menu
  	 *
  	 * Outputs the admin menu
  	 */
	function render_admin_menu(){
  		echo '<div class="wrap">';
            echo '<div id="icon-edit" class="icon32"><br /></div>';
            echo '<h2>' . __('Sigma Events Dashboard', 'se') . '</h2>';
            $this->sigma_settings_tabs();
            settings_errors();
            echo '<form action="options.php" method="post">';
                if ( isset ( $_GET['tab'] ) ) :
                    $tab = $_GET['tab'];
                else:
                    $tab = 'utilities';
                endif;

                switch ( $tab ) :
                case 'utilities' :
                    require SIGMA_PATH . 'admin/tab-utilities.php';
                    break;
                case 'emails' :
                    require SIGMA_PATH . 'admin/tab-emails.php';
                    break;
                case 'advanced' :
                    require SIGMA_PATH . 'admin/tab-advanced.php';
                    break;
                case 'debugger' :
                    require SIGMA_PATH . 'admin/tab-debugger.php';
                    break;
                case 'registration' :
                    require SIGMA_PATH . 'admin/tab-registration.php';
                    break;
                case 'payment' :
                    require SIGMA_PATH . 'admin/tab-payment.php';
                    break;
                case 'monitor' :
                    require SIGMA_PATH . 'admin/tab-monitor.php';
                    break;
                endswitch;

                settings_fields('sigma_options');
                do_settings_sections('manage-sigma-events');

            echo '</form>';
  		echo '</div>';
	}

	/**
  	 * Sigma Options Validate
  	 *
  	 * Output the csv file on clicking 'download data'.
  	 */
	function sigma_options_validate($input){
        // Get Current Options.
        $options = get_option('sigma_options');
        $output = $options;

  		// Process the CSV download request.
  		if(isset($input['download_registration_data'])){
            $this->download_file($input['event_id'],
                sanitize_text_field(trim($input['delimiter'])),
                sanitize_text_field(trim($input['enclosure'])));
  		}

  		// Process the CSV download request.
  		if(isset($input['download_payment_data'])){
    			$this->download_payments_file($input['payment_processor']);
  		}

  		// Download Logs.
  		if(isset($input['payment_processor_logs'])){
    		$r = $this->download_logs($input['event_processor'], $input['log_type']);
            if( $r ):
                $message = 'Log is being downloaded';
                $type = 'updated';
            else:
                $message = 'Unable to find the log';
                $type = 'error';
            endif;
  		}

  		// Process record deletion request.
  		if(isset($input['delete_registration_records'])){
    			$this->delete_registration_records( $input );
  		}

        // Save Emails Settings.
  		if(isset($input['save_emails'])){
            $output['send_name']    = sanitize_text_field($input['send_name']);
            $output['send_email']   = sanitize_email($input['send_email']);

            $output['email_user']           = isset($input['email_user'])           ? true : false;
            $output['user_products_email']  = isset($input['user_products_email'])  ? true : false;

            $output['admin_email']              = sanitize_email($input['admin_email']);
            $output['enable_admin_email']       = isset($input['enable_admin_email'])       ? true : false;
            $output['enable_post_admin_email']  = isset($input['enable_post_admin_email'])  ? true : false;
            $output['enable_debug_admin_email'] = isset($input['enable_debug_admin_email']) ? true : false;

            $output['enable_organizer_email']               = isset($input['enable_organizer_email'])               ? true : false;
            $output['enable_organizer_user_email']          = isset($input['enable_organizer_user_email'])          ? true : false;
            $output['enable_organizer_user_product_email']  = isset($input['enable_organizer_user_product_email'])  ? true : false;

            $output['dev_email']                = $input['dev_email'];
            $output['enable_dev_email']         = isset($input['enable_dev_email'])         ? true : false;
            $output['enable_post_dev_email']    = isset($input['enable_post_dev_email'])    ? true : false;
            $output['enable_debug_dev_email']   = isset($input['enable_debug_dev_email'])   ? true : false;

            $message = 'Sigma Email Settings Saved';
            $type = 'updated';
        }

        // Reset Emails Settings.
  		if(isset($input['reset_emails'])){
            $options = $this->options;
            $output['send_name']    = $options['send_name'];
            $output['send_email']   = $options['send_email'];

            $output['email_user']           = $options['email_user'];
            $output['user_products_email']  = $options['user_products_email'];

            $output['admin_email']              = $options['admin_email'];
            $output['enable_admin_email']       = $options['enable_admin_email'];
            $output['enable_post_admin_email']  = $options['enable_post_admin_email'];
            $output['enable_debug_admin_email'] = $options['enable_debug_admin_email'];

            $output['enable_organizer_email']               = $options['enable_organizer_email'];
            $output['enable_organizer_user_email']          = $options['enable_organizer_user_email'];
            $output['enable_organizer_user_product_email']  = $options['enable_organizer_user_product_email'];

            $output['dev_email']                = $options['dev_email'];
            $output['enable_dev_email']         = $options['enable_dev_email'];
            $output['enable_post_dev_email']    = $options['enable_post_dev_email'];
            $output['enable_debug_dev_email']   = $options['enable_debug_dev_email'];

            $message = 'Sigma Email Settings Reset';
            $type = 'updated';
        }

        // Save Advanced Settings
  		if(isset($input['save_advanced'])){
            $output['enable_tracker_get']       = isset($input['enable_tracker_get'])     ? true : false;
            $output['enable_tracker_post']      = isset($input['enable_tracker_post'])    ? true : false;

            $output['enable_security_module']       = isset($input['enable_security_module']) ? true : false;
            $output['enable_forget_code_captcha']   = isset($input['enable_forget_code_captcha']) ? true : false;
            $output['enable_forget_code_email']   = isset($input['enable_forget_code_email']) ? true : false;

            $message = 'Sigma Advanced Settings Saved';
            $type = 'updated';
        }

        // Reset Advanced Settings
  		if(isset($input['reset_advanced'])){
            $options = $this->options;
            $output['enable_tracker_get']       = $options['enable_tracker_get'];
            $output['enable_tracker_post']      = $options['enable_tracker_post'];

            $output['enable_security_module']       = $options['enable_security_module'];
            $output['enable_forget_code_captcha']   = $options['enable_forget_code_captcha'];
            $output['enable_forget_code_email']   = $options['enable_forget_code_email'];

            $message = 'Sigma Advanced Settings Reset';
            $type = 'updated';
        }

  		if(isset($input['query_database'])){
            $output['sql_query']             = $input['sql_query'];

            $message = 'Query Updated';
            $type = 'updated';
        }

        add_settings_error(
            'sigma',
            esc_attr('settings_updated'),
            __($message),
            $type
        );
        return $output;
	}

	/**
  	 * Download Registration Data CSV File
  	 */
	function download_file($eid, $delimiter = ',', $enclosure = '|'){
  		global $sigma_events;
  		global $wpdb;
  		$table_name = $wpdb->prefix . $sigma_events->registration_table;

        $block_size = 1000;
        $current_row = 0;

        /**
         * Get Registration Data
         */
  		if($eid == 'all'):
            $file_name = 'Sigma-All-Registration-Data_' . date('Y-m-d', current_time('timestamp')) . '.csv';
  		else:
            $file_name = 'Event-'. $eid . '-Sigma-Registration-Data_' . date('Y-m-d', current_time('timestamp')) . '.csv';
  		endif;

        $fp = $this->output_csv_file_header($table_name, $file_name, $delimiter);

        /**
         * Determine the number of rows
         */
        $rows = mysql_query("select id from $table_name");
        $no_of_rows = mysql_num_rows($rows);

        while($current_row < $no_of_rows){
            $block_end = $current_row + $block_size;
            if($eid == 'all'):
                $rows = $wpdb->get_results("select * from $table_name where id > $current_row && id <= $block_end", ARRAY_A);
            else:
                $rows = $wpdb->get_results("select * from $table_name where eid=$eid && id > $current_row && id <= $block_end", ARRAY_A);
            endif;

            /**
             * Put data in the CSV
             */
            foreach($rows as $row){
                $this->sigma_fputcsv($fp, $row, $delimiter, $enclosure);
            }
            $current_row += $block_size;
        }

        unset($rows);

        $this->output_csv_file_footer($fp);
    }

    /**
     * Custom fputcsv function
     */
    function sigma_fputcsv($fp, $row, $delimiter = '^', $enclosure = '|'){
        $str_row = '';
        $i = 0;
        foreach($row as $cell){
            if(null == $cell || false == $cell || '' == trim($cell)){
                $column = 'None';
            } else {
                $cell = str_replace('\\', '', $cell);
                $cell = str_replace($enclosure, '', $cell);
                $cell = str_replace($delimiter, '', $cell);
            }
            $str_row .= $enclosure . $cell . $enclosure . $delimiter;
            $i++;
        }
        $str_row .= PHP_EOL;
        fwrite($fp, $str_row);
    }

    /**
     * Download Payment Data CSV File
     */
	function download_payments_file($processor){
  		global $sigma_events;
  		global $wpdb;
  		$table_name = $wpdb->prefix . $sigma_events->payment_table;

        $block_size = 1000;
        $current_row = 0;

        /**
         * Get Payment Data
         */
  		if($processor == 'all'):
            $file_name = 'Sigma-All-Payment-Data_' . date('Y-m-d', current_time('timestamp')) . '.csv';
  		else:
            $file_name = 'Processor-'. ucfirst($processor) . '-Payment-Data_' . date('Y-m-d', current_time('timestamp')) . '.csv';
  		endif;

        $fp = $this->output_csv_file_header($table_name, $file_name);

        /**
         * Determine the number of rows
         */
        $rows = mysql_query("select id from $table_name");
        $no_of_rows = mysql_num_rows($rows);

        while($current_row < $no_of_rows){
            $block_end = $current_row + $block_size;
            if($processor == 'all'):
                $rows = $wpdb->get_results("select * from $table_name where id > $current_row && id <= $block_end", ARRAY_A);
            else:
                $rows = $wpdb->get_results("select * from $table_name where processor='" . $processor . "' && id > $current_row && id <= $block_end", ARRAY_A);
            endif;

            /**
             * Put data in the CSV
             */
            foreach($rows as $row){
                $this->sigma_fputcsv($fp, $row);
            }
            $current_row += $block_size;
        }

        unset($rows);

        $this->output_csv_file_footer($fp);
    }


    /**
     * Output CSV File Header
     */
    function output_csv_file_header($table_name, $file_name, $delimiter = '^', $enclosure = '|'){
        /**
         * A sample query to table
         */
  		$samplequery = mysql_query("select * from " . $table_name . " limit 1");

        /**
         * Retrieves the number of fields from a query
         */
  		$no_of_fields = mysql_num_fields($samplequery);

        /**
         * Collect table column names into an array
         */
        $fields = array();
  		for($i=0; $i < $no_of_fields; $i++){
            $field = mysql_fetch_field($samplequery, $i);
            array_push($fields, $field->name);
  		}

        /**
         * Free sample query memory
         */
        mysql_free_result($samplequery);

        /**
         * Output file as CSV
         */
  		header("Content-type: text/csv");
  		header("Content-Disposition: attachment; filename=".$file_name);

        /**
         * Open a file pointer (fp)
         */
        $fp = fopen("php://output", 'w');

        /**
         * Add Column Names to CSV
         */
        $this->sigma_fputcsv($fp, $fields, $delimiter, $enclosure);

        return $fp;
    }

    /**
     * Output CSV File Footer
     */
    function output_csv_file_footer($fp){
        fclose($fp);
  		exit;
	}

    /**
     * Download Logs.
     */
	function download_logs($processor, $type){
        /**
         * Prepare the file name
         */
        $filename  = SIGMA_PATH . 'logs/' . $processor . '_payments_' . $type . '.log';
        $file_name = $processor . '_payments_' . $type . '.log';

  		if( ! file_exists($filename) ):
            return false;
  		else:
            $content = file_get_contents( $filename );
  		endif;

  		header("Content-type: text/plain");
  		header("Content-Disposition: attachment; filename=".$file_name);

  		echo $content;
        exit;
	}

    /**
     * Delete Registration Records
     */
    function delete_registration_records( $input ){
        // Input Sanitization
        $delete_event_id = isset( $input['delete_event_id'] )
            ? (int) $input['delete_event_id'] : 0;
        $deletion_days = isset( $input['deletion_days'] )
            ? (int) $input['deletion_days'] : 0;
        $deletion_criterion = isset( $input['deletion_criterion'] )
            ? sanitize_text_field( $input['deletion_criterion'] ) : '';

           //          Input Validation
        if(       0 == $delete_event_id
          ||      0 == $deletion_days
          ||     '' == $deletion_criterion
          || 'none' == $deletion_criterion
           ):

            $message = 'Invalid Deletion Input';
            $type    = 'error';
        else:
            // Prepare deletion condition
            switch( $deletion_criterion ){
                case 'epaid':
                    $delete_paid_string = " paid != 'paid' ";
                    break;
                case 'pending':
                    $delete_paid_string = " paid = 'pending' ";
                    break;
                case 'cancelled':
                    $delete_paid_string = " paid = 'cancelled' ";
                    break;
                case 'notpaid':
                    $delete_paid_string = " paid = 'notpaid' ";
                    break;
                case 'null':
                    $delete_paid_string = " paid IS NULL ";
                    break;
                default:
                    return false;
            }

            $delete_older_than = date( 'Y-m-d',  current_time('timestamp') - $deletion_days * DAY_IN_SECONDS );

            global $wpdb, $sigma_events;
            $table_name = $wpdb->prefix . $sigma_events->registration_table;
            $wpdb->query(
                "
                DELETE
                FROM  $table_name
                WHERE eid = $delete_event_id
                AND   $delete_paid_string
                AND   reg_time < '" . $delete_older_than . "'
                "
            );
            $message = 'Records older than ' . $deletion_days . ' days (eid:'
                . $delete_event_id . ') were deleted.'
                . '<br />(query:' . $wpdb->last_query . ') (today:'
                . date( 'Y-m-d', current_time('timestamp') ) . ')';
            $type   = 'updated';

        endif;

        add_settings_error(
            'sigma',
            esc_attr('settings_updated'),
            __($message),
            $type
        );
        return $output;
    }

	/**
  	 * Admin Scripts
  	 */
	function admin_scripts(){
  		// Events admin page stylesheet.
  		wp_register_style('sigma-events-admin-style', SIGMA_URL . 'css/sigma-events-admin.css');
  		wp_enqueue_style('sigma-events-admin-style');


        if(isset($_GET['tab']) && $_GET['tab'] == 'debugger'):
            // debugger javascripts.
            wp_register_script('sigma-debugger-script',
              SIGMA_URL . 'js/sigma-debugger.js', array('jquery'), '1.0', true);
            wp_enqueue_script('sigma-debugger-script');
        endif;
	}
}
endif;
?>