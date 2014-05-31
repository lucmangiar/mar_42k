<?php
if ( !class_exists('Sigma_CSV_To_DB') ) :
/**
 * Sigma CSV to Db Updater
 *
 * Takes a CSV file and uploads the containing data to the database.
 *
 * @package     SigmaEvents
 * @subpackage  AdminMenu
 * @since       Version 4.3
 *
 */
class Sigma_CSV_To_DB{
    /**
     * Store the table name.
     */
    private $table_name;
	
	  private $post;

    /**
     * Current token list being processed
     */
    private $token_array = array();

	/**
  	 * CSV to DB Menu Constructor
  	 */
	function __construct($table_name){
		
		 global $sigma_events;
        /**
         * Setup the table name
         */
        $this->table_name = $table_name;

        /**
         * Hook to admin menu and create our menu.
         */
  		add_action('admin_menu', array($this, 'csv_menu'));

        /**
         * Register Settings.
         */
  		add_action('admin_init', array($this, 'register_csv_settings'));
	}

	/**
  	 * Register Sigma CSV to DB Settings
  	 */
	function register_csv_settings(){
        /**
         * Store Sigma Settings in 'sigma_options' option
         */
  		register_setting( 'sigma_csv_options', 'sigma_csv_options', array( $this, 'sigma_csv_options_validate' ) );
	}

	/**
  	 * Add CSV to DB Menu
  	 *
  	 * Register an CSV to DB menu for Sigma Events.
  	 */
	function csv_menu(){
  		$hook_suffix = add_submenu_page(
            'manage-sigma-events',
            __('CSV to DB', 'se'),
            __('CSV to DB', 'se'),
            'manage_options',
            'csv-to-db',
            array($this, 'render_csv_menu')
  		);
  		add_action('admin_print_scripts-' . $hook_suffix, array($this, 'admin_scripts'));
	}

	/**
  	 * Render CSV to DB Menu
  	 *
  	 * Outputs the CSV to DB menu
  	 */
	function render_csv_menu(){
		
  		echo '<div class="wrap">';
            echo '<div id="icon-edit" class="icon32"><br /></div>';
            echo '<h2>' . __('Sigma CSV to Database', 'se') . '</h2>';
            settings_errors();
            echo '<form action="options.php" method="post">';
                require SIGMA_PATH . 'admin/tab-csv-to-db.php';
                settings_fields('sigma_csv_options');
                do_settings_sections('csv-to-db');

            echo '</form>';
echo '<h4>' . __('Set following cron job to make it work:', 'se') . '</h4>';
$file = dirname(dirname(__FILE__)). '/sigma-events.php';
$cron_url = plugin_dir_path($file ).'cron/sigma-csv-cron.php';
$cron_command = '/usr/bin/php -q '.$cron_url;
			 echo '<h4>' .$cron_command. '</h4>';
			 //Legacy Cron Job Command
			 echo '<p> Legacy Command:</br> wget -o /dev/null -O - http://sigmasecurepay.info/wp-content/plugins/sigma_events/cron/sigma-csv-cron.php >> /home/maratond/sigmasecurepay/wp-content/plugins/sigma_events/logs/csv-cron/cpanel-cron.log 2>&1 <p>';
			 
			 //Debug console usage instuctions for user
			 echo '</br><p><strong>Note:</strong> Once \'Debug Bar\' and \'Debug Bar Console\' plugins are installed you can see import progess under Debug->Console using the following code: </br></br>$sigma_csv = get_option( \'sigma_csv\' );</br>echo \'&lt;pre>\';</br>print_r($sigma_csv);</br>echo \'&lt;/pre>\';</br>echo time();</br></p>';
  		echo '</div>';
	}

	/**
  	 * Sigma CSV to DB Options Validate
  	 */
	function sigma_csv_options_validate($input){
  		if(isset($input['process_csv'])){
            $delimiter = isset($input['delimiter']) ? sanitize_text_field($input['delimiter']) : ',';
            $enclosure = isset($input['enclosure']) ? sanitize_text_field($input['enclosure']) : '|';
            $message = $this->csv_to_db(esc_url($input['csv_file']), $delimiter, $enclosure);
            $type = 'updated';
        }

        add_settings_error(
            'sigma',
            esc_attr('settings_updated'),
            __($message),
            $type
        );
        return $input;
	}

	/**
  	 * CSV to DB Scripts
  	 */
	function admin_scripts(){
        /**
         * Events admin page stylesheet.
         */
  		wp_register_style('sigma-events-admin-style', SIGMA_URL . 'css/sigma-events-admin.css');
  		wp_enqueue_style('sigma-events-admin-style');

        /**
         * CSV to DB javascripts.
         */
        wp_register_script('sigma-csv-script',
        SIGMA_URL . 'js/sigma-csv-to-db.js', array('jquery'), '1.0', true);
        wp_enqueue_script('sigma-csv-script');
        $upload_dir = wp_upload_dir();
        $upload_base = $upload_dir['basedir'];
        $translation_array = array(
            'sigma_upload_base_dir' => $upload_base
        );
        wp_localize_script( 'sigma-csv-script', 'sigma_admin_vars', $translation_array );
	}

    /**
     * Sigma CSV to DB
     */
    function csv_to_db($csv_file, $delimiter, $enclosure){
        setlocale(LC_CTYPE, 'en_US.UTF-8');
        $exists = file_exists($csv_file);
        $limit = 1000;
        if($exists) {
            /**
             * Fields Array
             */
            $fields = array(
                'Token',
                'Registration Time',
                'Event ID',
                'First Name',
                'Last Name',
                'Argentinian',
                'Country',
                'DNI',
                'Email',
                'Gender',
                'Birthday',
                'Phone',
                'Address',
                'Club',
                'Discount Code',
                'Answer',
                'Rate',
                'Amount',
                'Medium',
                'Paid',
                'IP',
                'Tracker Url'
            );

            /**
             * Output Header
             */
            $fp = $this->csv_header($fields, $delimiter, $enclosure);

            /**
             * Read and Parse CSV
             */
            $row_no= 0;
            $errors = 0;
            if(($handle = fopen($csv_file, 'r')) !== false){
                /**
                 * Get the Column Headers
                 */
                $header = fgetcsv($handle, 0, $delimiter, $enclosure);

                /**
                 * Read the rows
                 */
                while(($row = fgetcsv($handle, 0, $delimiter, $enclosure)) !== false && $row_no < $limit){
                    $columns = count($row);
                    if(18 == $columns){
                        $r = $this->csv_row($fp, $row, $delimiter, $enclosure);
                        if(!$r) $errors++;
                    } else {
                        $errors++;
                    }
                    $row_no++;
                }
                fclose($handle);
            }

            if ( sizeof($this->token_array) ) {
                /**
                 * Get the CSV Option
                 */
                $sigma_csv = get_option('sigma_csv');
                if ( $sigma_csv ) $sigma_csv = array();

                /**
                 * Update CSV Option
                 */
                $sigma_csv[current_time('timestamp')] = $this->token_array;
                update_option('sigma_csv', $sigma_csv);
            }

            /**
             * Output Footer
             */
            $this->csv_footer($fp);

           $message = 'File processed (' . $csv_file . ')<br /><br />';
        } else {
           $message = 'File does not exist (' . $csv_file . ')<br /><br />';
        }

        $message .= '<br /><br /><i>Executed ' . get_num_queries() . ' queries ';
        $message .= 'in  ' . timer_stop( 0, 5 ) . ' seconds</i><br />';
        return $message;
    }

    /**
     * Output Header
     */
    function csv_header($fields, $delimiter, $enclosure){
        $file_name = 'Sigma_CSV_Processing_Output_' . date('Y_m_d_H_i_s', current_time('timestamp')) . '.csv';

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
        fputcsv($fp, $fields, $delimiter, $enclosure);

        return $fp;
    }

    /**
     * Output A Row
     */
    function csv_row($fp, $row, $delimiter, $enclosure){
        global $wpdb;
		 $number='';
		
        $table_name = $wpdb->prefix . $this->table_name;

        if(18 != sizeof($row)) return false;

        $row[1] = $this->prepare_string($row[1]);
        $row[2] = $this->prepare_string($row[2]);

        $token = $this->get_token($row[0], $row[5]);

        $data = array(
            'token'       => sanitize_text_field($token),
            'reg_time'    => current_time('mysql'),
            'eid'         => (int) $row[0],
            'fname'       => sanitize_text_field($row[1]),
            'lname'       => sanitize_text_field($row[2]),
            'argentinian' => (bool) $row[3],
            'country'     => sanitize_text_field($row[4]),
            'dni'         => sanitize_text_field($row[5]),
            'email'       => sanitize_email($row[6]),
            'gender'      => sanitize_text_field($row[7]),
            'bday'        => $this->format_bday($row[8]),
            'phone'       => sanitize_text_field($row[9]),
            'addr'        => sanitize_text_field($row[10]),
            'club'        => sanitize_text_field($row[11]),
            'disc_code'   => sanitize_text_field($row[12]),
            'ans'         => sanitize_text_field($row[13]),
            'rate'        => (float) $row[14],
            'amount'      => (int) $row[15],
            'extra_items' => 'none',
            'medium'      => sanitize_text_field($row[16]),
            'paid'        => sanitize_text_field($row[17]),
            'ip'          => $_SERVER['REMOTE_ADDR'],
			
        );
		
		
		 $data2 = array(
            'id'                => null,
            'codautorizacion'   => 'csv',
            'tarjeta'           => 'csv',
            'emailcomprador'    => sanitize_email($row[6]),
            'resultado'         => 'paid',
            'fechahora'         => current_time('mysql'),
            'processor'         => 'none',
            'titular'           =>  sanitize_text_field($row[1]).' '.sanitize_text_field($row[2]),
            'token'             => sanitize_text_field($token),
            'motivo'            => 'csv',
            'monto'             =>(int) $row[15],
            'ip'                =>$_SERVER['REMOTE_ADDR']
        );
		
        /**
         * Push to array
         */
        array_push($this->token_array, sanitize_text_field($token));

        /**
         * Validate Data
         */
        $r = $this->validate_data($data);
        if(!$r){
            $this->log( " Error: CSV Update Validation Error | " . $data['token']   );
            $data['tracker'] = 'Unable to add record. Validation Error.';
        } else {
            $s = $wpdb->insert( $table_name, $data );
			$cid=$wpdb->insert_id;
			
			 global $sigma_events;
							
				if( 'paid' == $data['paid'] ):
					$number= $sigma_events->sequences->get_sequence_number($data['eid'], $token);
					 
				elseif( 'pending' == $data['paid']  ):
					$number = '';
				elseif( 'notpaid' == $data['paid']  || 'cancelled' == $data['paid']  ):
					$sigma_events->sequences->return_sequence_number($data['eid'],$token );
					$number        = 'none';
				else:
					$number         = 'none';
				endif;
				
		//	echo $number;
				
			
			 $payment_table=$wpdb->prefix .'sigma_payments';
			 $reg_table=$wpdb->prefix .'sigma_events';
			 	 
			 $rs = $wpdb->insert( $payment_table, $data2 );
				 if( ! $rs )
				   { $this->log_error( "\nError: Payment DB Update Error | Token: " . $data2["token"] );}
				else{	$output = array(
											'payment_record_id' => $wpdb->insert_id
										);
						$where   = array( 'token' => $data2['token'] );
					
			
						$data3 = array(
							'payment'   => $output['payment_record_id'],
							'seq_no'    => $number
						);
						$rss = $wpdb->update( $reg_table, $data3, $where);
						if( ! $rss )
							$this->log_error( "\nError: Sigma DB Update Error | Token: " . $data2["token"] );
							
					}
				
			
            if($s) {
                /**
                 * Tracker Link
                 */
				 
                $tracker_link = get_home_url() . '/sigma-events/tracker/?sigma_token=' . $token;
                $data['tracker'] = $tracker_link;
            } else {
                $this->log( " Error: CSV Update DB Error | " . $data['token'] );
                $data['tracker'] = 'Unable to add record';
                return false;
            }
        }

        unset($data['extra_items']);
        fputcsv($fp, array_values($data), $delimiter, $enclosure);
        return $r;
    }
	
	
	 function update_sigma_table( $table_name, $data ){
        global $wpdb;
        $where   = array( 'token' => $post['token'] );

        $data2 = array(
            'payment'   => $data['payment_id'],
            'seq_no'    => $data['seq_no']
        );
        $r = $wpdb->update( $table_name, $data2, $where);
        if( ! $r )
            $this->log_error( "\nError: Sigma DB Update Error | Token: " . $data["token"] );

        return $r;
    }

    /**
     * Format Date
     */
    function format_bday($date){
        $mysql = strpos($date, '-');
        if($mysql){
            return $date;
        }

        $parts = explode('/', $date);
        $mysql = $parts[2] . '-' . $parts[1] . '-' . $parts[0];
        return $mysql;
    }


    /**
     * Output Footer
     */
    function csv_footer($fp){
        fclose($fp);
        exit;
    }

    /**
     * Generate and return a token
     */
    function get_token($event_id, $dni){
        // Generate a unique token				            // length = 10
       /* $token  = substr(strtolower(trim($fname)), 0, 3) 	// first 3 letters of first name
            . substr(strtolower(trim($lname)), 0, 3) 	    // first 3 letters of last name
            . rand(1000, 9999);				                // 4 digit random number
        $token = preg_replace( '/[^a-z0-9]/', '', strtolower($token) );
        return $token;*/
		
		$organizer  = get_post_meta($event_id, 'sigma_event_organizer', true);
		$eventcode = $organizer['eventcode'];
		
		$eventcode=substr(strtoupper(trim($eventcode)), 0, 2);
				$token=$eventcode.$dni;
        return $token;
    }

    /**
     * Prepare String
     */
    function prepare_string($input){
        $pattern = "/[^\w.-]/";
        $output = preg_replace($pattern, "", $input);
        $output = sanitize_text_field($output);
        return $output;
    }

    /**
     * Validate Data
     */
    function validate_data($data){
		
        if($data['fname'] == '' || $data['lname'] == '' || $data['dni'] == '' || !is_email($data['email']) ||
           $data['gender'] == '' || $data['bday'] == '' )
           return false;
		   
		global $wpdb;
        $table     = $wpdb->prefix . 'sigma_events';
        $where   = "'".$data['dni']."'";
        $check = $wpdb->get_results(
            "
            SELECT dni
            FROM $table
            WHERE dni = $where
            ", ARRAY_A
        );
		
		$cnt=count($check);

        if($cnt>0)return false;

        if(strlen($data['token']) < 5) return false;

        $year_margin = 5;
        if(!(preg_match('/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/', $data['bday'], $matches)
            && $matches[0] < date('Y') - $year_margin)){
            return false;
        }

        return true;
    }

    /**
     * Log
     */
    function log( $response ){
        $sigma_log = SIGMA_PATH . 'logs/csv-to-db.log';
        $data   = "\n" . current_time('mysql') . $response;
        $r         = file_put_contents($sigma_log, $data, FILE_APPEND | LOCK_EX);
        return $r;
    }
}
endif;
?>
