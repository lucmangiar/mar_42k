<?php
if ( !class_exists('Sigma_Security_Menu') ) :
/**
 * Sigma Security Menu Class
 *
 * The Sigma Interface for managing and monitoring it's own security.
 * On the other hand, An admin menu for Sigma Events Security Component.
 *
 * @package     SigmaEvents
 * @subpackage  AdminMenu
 * @since       Version 3.6
 *
 */
class Sigma_Security_Menu{
    // Store the table name.
    private $table_name;

    // Sigma Default Options.
    private $options;

    // Sigma Default Options.
    private $security_options;

    // Module Status
    private $security_enabled;

	/**
  	 * Security Menu Constructor
  	 */
	function __construct($table_name){
        // Setup the table name
        $this->table_name = $table_name;

        // Setup Default Options
        $this->set_sigma_default_security_options();

        // Add Sigma Options if necessary.
        add_action('after_setup_theme', array($this, 'sigma_security_options_init'));

        // Set Sigma Settings Tabs.
        $this->set_security_settings_page_tabs();

  		// Hook to admin menu and create our menu.
  		add_action('admin_menu', array($this, 'security_menu'));

  		// Register Settings.
  		add_action('admin_init', array($this, 'register_security_settings'));

        // Security Setup.
        $this->options = get_option('sigma_options');
        $this->security_enabled = $this->options['enable_security_module'];
	}

    /**
     * Setup Sigma Options Defaults.
     */
    function set_sigma_default_security_options(){
        $this->security_options = array(
            'registration' => array(
                'time_to_keep' => 6000,
                'allowed_attempts' => 10, // 10 attempts within 6000 seconds.
                'time_between_attempts' => 1, // checked for each visit.
                'page_to_redirect' => get_home_url() . '/registration_not_allowed/',
                'blocked_ip_log' => 'blocked_registrations.log'
            ),
            'confirmation' => array(
                'time_to_keep' => 6000,
                'allowed_attempts' => 10, // 10 attempts within 6000 seconds.
                'time_between_attempts' => 1, // checked for each visit.
                'page_to_redirect' => get_home_url() . '/confirmation_not_allowed/',
                'blocked_ip_log' => 'blocked_confirmations.log'
            )
        );
    }

    /**
     * Add Sigma Options if necessary.
     */
    function sigma_security_options_init(){
        $sigma_options = get_option('sigma_security_options');
        if( false == $sigma_options )
            update_option('sigma_security_options', $this->security_options);
    }

    /**
     * Set Default Tabs
     */
    function set_security_settings_page_tabs(){
        $this->tabs = array(
            'monitor'     => 'Monitor',
            'settings'    => 'Settings'
        );
    }

    /**
     * Echo the tabs bar.
     */
    function sigma_security_settings_tabs(){
        if ( isset ( $_GET['tab'] ) ) :
            $current = $_GET['tab'];
        else:
            $current = 'monitor';
        endif;

        $links = array();
        foreach( $this->tabs as $tab => $name ) :
            if ( $tab == $current ) :
                $links[] = '<a class="nav-tab nav-tab-active"
                href="?page=manage-sigma-security&tab=' .
                $tab . '" > ' . $name . '</a>';
            else :
                $links[] = '<a class="nav-tab"
                href="?page=manage-sigma-security&tab=' .
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
  	 * Register Sigma Security Settings
  	 */
	function register_security_settings(){
        /* Store Sigma Settings in 'sigma_options' option */
  		register_setting( 'sigma_security_options', 'sigma_security_options', array( $this, 'sigma_security_options_validate' ) );
	}

	/**
  	 * Add Security Menu
  	 *
  	 * Register an security menu for Sigma Events.
  	 */
	function security_menu(){
  		$hook_suffix = add_submenu_page(
            'manage-sigma-events',
            __('Security', 'se'),
            __('Security', 'se'),
            'manage_options',
            'manage-sigma-security',
            array($this, 'render_security_menu')
  		);
  		add_action('admin_print_scripts-' . $hook_suffix, array($this, 'admin_scripts'));
	}

	/**
  	 * Render Security Menu
  	 *
  	 * Outputs the security menu
  	 */
	function render_security_menu(){
  		echo '<div class="wrap">';
            echo '<div id="icon-edit" class="icon32"><br /></div>';
            $security_status = $this->security_enabled ? '' : __(' ( Disabled ) ', 'se' );
            echo '<h2>' . __('Sigma Security Dashboard' . $security_status, 'se') . '</h2>';
            $this->sigma_security_settings_tabs();
            settings_errors();
            echo '<form action="options.php" method="post">';
                if ( isset ( $_GET['tab'] ) ) :
                    $tab = $_GET['tab'];
                else:
                    $tab = 'monitor';
                endif;

                switch ( $tab ) :
                case 'monitor' :
                    require SIGMA_PATH . 'admin/tab-security-monitor.php';
                    break;
                case 'settings' :
                    require SIGMA_PATH . 'admin/tab-security-settings.php';
                    break;
                endswitch;

                settings_fields('sigma_security_options');
                do_settings_sections('manage-sigma-security');

            echo '</form>';
  		echo '</div>';
	}

	/**
  	 * Sigma Security Options Validate
  	 */
	function sigma_security_options_validate($input){
        // Get Current Options.
        $options = get_option('sigma_security_options');
        $output = $options;

  		if(isset($input['save_security'])){
            $output['registration']['time_to_keep']             = sanitize_text_field($input['registration']['time_to_keep']);
            $output['registration']['allowed_attempts']         = sanitize_text_field($input['registration']['allowed_attempts']);
            $output['registration']['time_between_attempts']    = sanitize_text_field($input['registration']['time_between_attempts']);
            $output['registration']['page_to_redirect']         = sanitize_text_field($input['registration']['page_to_redirect']);
            $output['registration']['blocked_ip_log']           = sanitize_text_field($input['registration']['blocked_ip_log']);

            $output['confirmation']['time_to_keep']             = sanitize_text_field($input['confirmation']['time_to_keep']);
            $output['confirmation']['allowed_attempts']         = sanitize_text_field($input['confirmation']['allowed_attempts']);
            $output['confirmation']['time_between_attempts']    = sanitize_text_field($input['confirmation']['time_between_attempts']);
            $output['confirmation']['page_to_redirect']         = sanitize_text_field($input['confirmation']['page_to_redirect']);
            $output['confirmation']['blocked_ip_log']           = sanitize_text_field($input['confirmation']['blocked_ip_log']);

            $message = 'Sigma Security Settings Saved';
            $type = 'updated';
        }

  		if(isset($input['reset_security'])){
            $this->set_sigma_default_security_options();
            $options = $this->security_options;
            $output = $options;

            $message = 'Sigma Security Settings Reset';
            $type = 'updated';
        }

  		if(isset($input['reset_ip_lists'])){
            $registration_data['access']  = array();
            $registration_data['blocked'] = array();
            $confirmation_data['access']  = array();
            $confirmation_data['blocked'] = array();

            update_option('registration_gate_data', $registration_data);
            update_option('confirmation_gate_data', $confirmation_data);

            $message = 'Sigma IP Lists Reset';
            $type = 'updated';
        }

  		if(isset($input['download_blocked_registrations'])){
            $log = $options['registration']['blocked_ip_log'];
            $this->download_log($log);
        }

  		if(isset($input['download_blocked_confirmations'])){
            $log = $options['confirmation']['blocked_ip_log'];
            $this->download_log($log);
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
  	 * Security Scripts
  	 */
	function admin_scripts(){
  		// Events admin page stylesheet.
  		wp_register_style('sigma-events-admin-style', SIGMA_URL . 'css/sigma-events-admin.css');
  		wp_enqueue_style('sigma-events-admin-style');
	}

    function download_log($log){
        $filename  = SIGMA_PATH . 'logs/' . $log;

  		if( ! file_exists($filename) ):
            echo 'not found';
            exit;
  		else:
            $content = file_get_contents( $filename );
  		endif;

  		header("Content-type: text/x-csv");
  		header("Content-Disposition: attachment; filename=".$log);
        echo $content;
        exit;
    }
}
endif;
?>
