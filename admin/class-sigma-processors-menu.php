<?php
if ( !class_exists('Sigma_Processors_Menu') ) :
/**
 * Sigma Processors Menu Class
 *
 * The Sigma Interface for managing Payment Processors.
 *
 * @package     SigmaEvents
 * @subpackage  AdminMenu
 * @since       Version 3.9
 *
 */
class Sigma_Processors_Menu{
    // Store the table name.
    private $table_name;

    // Sigma Default Options.
    private $options;

    // Sigma Processor Options.
    private $processor_options;
	
	
	/**
  	 * Security Menu Constructor
  	 */
	function __construct($table_name){
        // Setup the table name
        $this->table_name = $table_name;
		
		
		
        // Setup Default Options
        $this->set_sigma_default_processor_options();

        // Add Sigma Options if necessary.
        add_action('after_setup_theme', array($this, 'sigma_processor_options_init'));

        // Set Sigma Settings Tabs.
        $this->set_processor_settings_page_tabs();

  		// Hook to admin menu and create our menu.
  		add_action('admin_menu', array($this, 'processor_menu'));

  		// Register Settings.
  		add_action('admin_init', array($this, 'register_processor_settings'));

        // Processor Setup.
        $this->options = get_option('sigma_options');
	}

    /**
     * Setup Sigma Options Defaults.
     */
			
    function set_sigma_default_processor_options(){

        $this->processor_options = array(			
            'cuentadigital' => array(				
                'enable_sandbox'          => false,
                'sandbox_report_url'      => 'https://www.cuentadigital.com/exportacionsandbox.php',
                'production_report_url'   => 'https://www.cuentadigital.com/exportacion.php',
                'auth_code'               => '7e06daf6753464c271f7bc7b177c35b4',
                'report_url'              => 'https://www.cuentadigital.com/exportacion.php',
                'report_date'             => date('Y-m-d', current_time( 'timestamp')),
                'output_report'           => false,
                'enable_logging'          => true,
                'cron_interval'           => DAY_IN_SECONDS,
				'acno'					  => '565005'
            ),
            'easyplanners' => array(
                'method'                  => 'POST',
                'easyplanners_url'        => 'https://www.easyplanners.net/s/maraton/',
                'password'                => 'x8$3Z@@ulw,!716&ds'
            ),
            'decidir' => array(
                'enable_logging'          => true,
                'enable_ip'               => false,
                'ip_address'              => '200.69.248.4'
            ),
            'dineromail' => array(
                'enable_logging'          => true,
                'enable_ip'               => false,
                'ip_address'              => '200.41.53.129'
            ),
            'paypal' => array(
                'enable_logging'          => true
            ),
        );
    }

    /**
     * Add Sigma Options if necessary.
     */
	
    function sigma_processor_options_init(){
        $sigma_options = get_option('sigma_processor_options');
        if( false == $sigma_options )
            update_option('sigma_processor_options', $this->processor_options);
    }

    /**
     * Set Default Tabs
     */
    function set_processor_settings_page_tabs(){
        $this->tabs = array(
            'decidir'         => 'Decidir',
            'dineromail'      => 'Dineromail',
            'sigma'           => 'Sigma',
            'salesperson'     => 'Salesperson',
            'easyplanners'    => 'EasyPlanners',
            'cuentadigital'   => 'CuentaDigital'
        );
    }

    /**
     * Echo the tabs bar.
     */
    function sigma_processor_settings_tabs(){
        if ( isset ( $_GET['tab'] ) ) :
            $current = $_GET['tab'];
        else:
            $current = 'decidir';
        endif;

        $links = array();
        foreach( $this->tabs as $tab => $name ) :
            if ( $tab == $current ) :
                $links[] = '<a class="nav-tab nav-tab-active"
                href="?page=manage-sigma-processors&tab=' .
                $tab . '" > ' . $name . '</a>';
            else :
                $links[] = '<a class="nav-tab"
                href="?page=manage-sigma-processors&tab=' .
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
  	 * Register Sigma Processor Settings
  	 */
	function register_processor_settings(){
        /* Store Sigma Settings in 'sigma_processor_options' option */
  		register_setting( 'sigma_processor_options', 'sigma_processor_options', array( $this, 'sigma_processor_options_validate' ) );
	}


	/**
  	 * Add Processor Menu
  	 *
  	 * Register a processor menu for Sigma Events.
  	 */
	function processor_menu(){
  		$hook_suffix = add_submenu_page(
            'manage-sigma-events',
            __('Processors', 'se'),
            __('Processors', 'se'),
            'manage_options',
            'manage-sigma-processors',
            array($this, 'render_processor_menu')
  		);
  		add_action('admin_print_scripts-' . $hook_suffix, array($this, 'admin_scripts'));
	}

	/**
  	 * Render Processor Menu
  	 *
  	 * Outputs the processor menu
  	 */
	function render_processor_menu(){
  		echo '<div class="wrap">';
            echo '<div id="icon-edit" class="icon32"><br /></div>';
            echo '<h2>' . __('Sigma Payment Processors', 'se') . '</h2>';
            $this->sigma_processor_settings_tabs();
            settings_errors();
            echo '<form action="options.php" method="post">';
                if ( isset ( $_GET['tab'] ) ) :
                    $tab = $_GET['tab'];
                else:
                    $tab = 'decidir';
                endif;

                switch ( $tab ) :
                case 'decidir' :
                    require SIGMA_PATH . 'admin/tab-processor-decidir.php';
                    break;
                case 'dineromail' :
                    require SIGMA_PATH . 'admin/tab-processor-dineromail.php';
                    break;
                case 'sigma' :
                    require SIGMA_PATH . 'admin/tab-processor-sigma.php';
                    break;
                case 'salesperson' :
                    require SIGMA_PATH . 'admin/tab-processor-salesperson.php';
                    break;
                case 'easyplanners' :
                    require SIGMA_PATH . 'admin/tab-processor-easyplanners.php';
                    break;
                case 'cuentadigital' :
                    require SIGMA_PATH . 'admin/tab-processor-cuentadigital.php';
                    break;
                endswitch;

                settings_fields('sigma_processor_options');
                do_settings_sections('manage-sigma-processors');

            echo '</form>';
  		echo '</div>';
	}

	/**
  	 * Sigma Processor Options Validate
  	 */
	function sigma_processor_options_validate($input){
        // Get Current Options.
        $options = get_option('sigma_processor_options');
		
        $output = $options;
		
        global $sigma_events;
        if(isset($sigma_events->misc['cuentadigital_report'])):
            $input['cuentadigital']['output_report'] = false;
            $output['cuentadigital'] =  $input['cuentadigital'];
            return $output;
        endif;
		
	
        /**
         * CuentDigital
         */
  		if(isset($input['save_cuentadigital'])){		  
			$output['cuentadigital']['acno'] = sanitize_text_field($input['cuentadigital']['acno']);
            $output['cuentadigital']['enable_sandbox']          = isset($input['cuentadigital']['enable_sandbox']) ? true : false;
            $output['cuentadigital']['sandbox_report_url']      = sanitize_text_field($input['cuentadigital']['sandbox_report_url']);
            $output['cuentadigital']['production_report_url']   = sanitize_text_field($input['cuentadigital']['production_report_url']);
            $output['cuentadigital']['auth_code']               = sanitize_text_field($input['cuentadigital']['auth_code']);
			 $output['cuentadigital']['enable_logging']          = isset($input['cuentadigital']['enable_logging']) ? true : false;

            $message = '';
            $new_cron_interval = sanitize_text_field($input['cuentadigital']['cron_interval']);
            if($output['cuentadigital']['cron_interval'] != $new_cron_interval):
                global $sigma_events;
                $sigma_events->cron->reschedule_event($new_cron_interval);
                $message .= 'Cron Schedule Updated. ';
            endif;
            $output['cuentadigital']['cron_interval']           = $new_cron_interval;
            $message .= 'Sigma CuentaDigital Settings Saved';
            $type = 'updated';
        }

  		if(isset($input['reset_cuentadigital'])){
            $this->set_sigma_default_processor_options();
            $options = $this->processor_options;
            $output['cuentadigital'] = $options['cuentadigital'];

            $message = 'Sigma CuentaDigital Settings Reset';
            $type = 'updated';
        }

        /**
         * CuentaDigital Report
         */
  		if(isset($input['cuentadigital_report'])){
            $output['cuentadigital']['report_url']      = sanitize_text_field($input['cuentadigital']['report_url']);
            $output['cuentadigital']['report_date']     = sanitize_text_field($input['cuentadigital']['report_date']);
            $output['cuentadigital']['output_report']   = true;

            $message = 'Sigma CuentaDigital Report Generated';
            $type = 'updated';
        }

  		if(isset($input['process_cuentadigital_report'])){
            global $sigma_events;
            $report_summary = $sigma_events->payments_cuentadigital->process_report(
                $output['cuentadigital']['report_url'],
                $output['cuentadigital']['report_date']
            );
            $message = $report_summary;
            $type = 'updated';
        }

        /**
         * CuentDigital Logs
         */
  		if(isset($input['cuentadigital_logs'])){
            $log = sanitize_text_field($input['cuentadigital_logs']);
            if( 'Data Log' == $log):
                $log = 'cuentadigital_payments_data.log';
            elseif( 'Error Log' == $log):
                $log = 'cuentadigital_payments_error.log';
            elseif( 'Cron Log' == $log):
                $log = 'cuentadigital_cron.log';
            endif;
            $this->download_log($log);
        }

        /**
         * EasyPlanners
         */
  		if(isset($input['save_easyplanners'])){
            $output['easyplanners']['method']             = sanitize_text_field($input['easyplanners']['method']);
            $output['easyplanners']['easyplanners_url']   = sanitize_text_field($input['easyplanners']['easyplanners_url']);
            $output['easyplanners']['password']           = sanitize_text_field($input['easyplanners']['password']);

            $message = 'Sigma EasyPlanners Settings Saved';
            $type = 'updated';
        }

  		if(isset($input['reset_easyplanners'])){
            $this->set_sigma_default_processor_options();
            $options = $this->processor_options;
            $output['easyplanners'] = $options['easyplanners'];

            $message = 'Sigma EasyPlanners Settings Reset';
            $type = 'updated';
        }

        /**
         * Decidir
         */
  		if(isset($input['save_decidir'])){
            $output['decidir']['enable_logging']  = isset($input['decidir']['enable_logging']) ? true : false;
            $output['decidir']['enable_ip']       = isset($input['decidir']['enable_ip']) ? true : false;
            $output['decidir']['ip_address']      = sanitize_text_field($input['decidir']['ip_address']);

            $message = 'Sigma Decidir Settings Saved';
            $type = 'updated';
        }

  		if(isset($input['reset_decidir'])){
            $this->set_sigma_default_processor_options();
            $options = $this->processor_options;
            $output['decidir'] = $options['decidir'];

            $message = 'Sigma Decidir Settings Reset';
            $type = 'updated';
        }

        /**
         * Dineromail
         */
  		if(isset($input['save_dineromail'])){
            $output['dineromail']['enable_logging']  = isset($input['dineromail']['enable_logging']) ? true : false;
            $output['dineromail']['enable_ip']       = isset($input['dineromail']['enable_ip']) ? true : false;
            $output['dineromail']['ip_address']      = sanitize_text_field($input['dineromail']['ip_address']);

            $message = 'Sigma Dineromail Settings Saved';
            $type = 'updated';
        }

  		if(isset($input['reset_dineromail'])){
            $this->set_sigma_default_processor_options();
            $options = $this->processor_options;
            $output['dineromail'] = $options['dineromail'];

            $message = 'Sigma Dineromail Settings Reset';
            $type = 'updated';
        }

        if(isset($message)):
            add_settings_error(
                'sigma',
                esc_attr('settings_updated'),
                __($message),
                $type
            );
        endif;
        return $output;
	}

	/**
  	 * Processor Scripts
  	 */
	function admin_scripts(){
        // Common Admin StyleSheets.
  		wp_register_style('sigma-events-admin-style', SIGMA_URL . 'css/sigma-events-admin.css');
  		wp_enqueue_style('sigma-events-admin-style');

        /**
         * Processor Scripts
         */
        wp_register_script('sigma-processor-admin-script',
          SIGMA_URL . 'js/sigma-processor-admin.js', array('jquery', 'jquery-ui-datepicker'), '1.0', true);
        wp_enqueue_script('sigma-processor-admin-script');

        // Enqueue styles for jQuery datepicker.
        wp_enqueue_style( 'sigma-jquery-ui', 'http://code.jquery.com/ui/1.10.1/themes/base/jquery-ui.css' );
	}

    /**
     * Download Log
     */
    function download_log($log){
        /**
         * Prepare the file name
         */
        $filename  = SIGMA_PATH . 'logs/' . $log;

  		if( ! file_exists($filename) ):
            return false;
  		else:
            $content = file_get_contents( $filename );
  		endif;

  		header("Content-type: text/plain");
  		header("Content-Disposition: attachment; filename=".$log);

  		echo $content;
        exit;
    }
}
endif;
?>
