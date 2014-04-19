<?php
if ( !class_exists('Sigma_Tourist_Agent') ) :
/**
 * Sigma Tourist Agent
 *
 * @package     SigmaEvents
 * @subpackage  Core
 * @since       version 3.6
 */
class Sigma_Tourist_Agent{
    /**
     * Registration Table Name
     */
    private $registration_table;

    /**
     * Sigma Rewrite Endpoint
     */
    private $endpoint = 'tourism';

	/**
	 * Sigma Tourist Agent Constructor
	 */
	function __construct($registration_table){
        // Setup Registration Table
        $this->registration_table = $registration_table;

		// Register post type.
		add_action('init', array($this, 'register_sigma_tourist_events_post_type'));

		// Add price meta box, etc.
		add_action('add_meta_boxes', array($this, 'add_meta_boxes'));

		// Save meta data.
		add_action('save_post', array($this, 'save_tourist_boxes'));

		// A redirect to serve product post types.
		add_action('template_redirect', array($this, 'redirect_tourist_agent_template'));

        // Add rewrite rules rules for sigma tourist agent.
        add_action('init', array($this, 'sigma_tourist_rewrite'));

        // Process tourist endpoint requests.
        add_action('template_redirect', array($this, 'redirect_tourist_endpoint_requests'));
	}

	/**
	 * Register Sigma Tourist Agent
	 *
	 * Add a custom post type 'tevents' to manage
	 * tourist events.
	 */
	function register_sigma_tourist_events_post_type(){
		$labels = array(
			'name' => __('t-Events', 'se'),
			'singular_name' => __('t-Event', 'se'),
			'add_new' => __('Add t-Event', 'se'),
			'add_new_item' => __('Add New t-Event', 'se'),
			'edit_item' => __('Edit t-Event', 'se')
		);
		$args = array(
			'labels' => $labels,
			'public' => true,
			'supports' => array('title'),
			'menu_icon' => SIGMA_URL . 'assets/sigma.png'
		);
		register_post_type( 'tevents', $args );
	}

	/**
	 * Add Tourist Agent Meta Boxes
	 *
	 * Add meta boxes to manage product price.
	 */
	function add_meta_boxes(){
		add_meta_box(
			'property_box',
			__('Manage t-Event', 'se'),
			array($this, 'property_box_cb'),
			'tevents',
			'normal',
			'default');

		add_meta_box(
			'tourist_box',
			__('Recent t-Event Updates', 'se'),
			array($this, 'tourist_box_cb'),
			'tevents',
			'normal',
			'default');
	}

	/**
	 * Password Box Callback
	 *
	 * Renders meta boxes for Sigma Tourist Agent.
	 */
	function property_box_cb($post){
		wp_nonce_field('sigma_tevents_meta_save', 'sigma_tevents_meta_data');

		$tevent = get_post_meta($post->ID, 'sigma_tevent_meta', true);
			if($tevent == ''){
                $tevent['event']    = '';
                $tevent['password'] = '';
            }

            // Move to above later
            if(!isset($tevent['token_suffix'])){
                $tevent['token_suffix']    = 't';
            }

            $output  = '<table>';
            $output .= '<tr><td><label>' . __('t-Event Original Event', 'se') . '</label></td>';
		    $output .= "<td><input type='text' class='newtag' name='teventevent' value='" . $tevent['event'] . "' ></td></tr>";
            $output .= '<tr><td><label>' . __('t-Event Password', 'se') . '</label></td>';
		    $output .= "<td><input type='text' class='newtag' name='teventpassword' value='" . $tevent['password'] . "' ></td></tr>";
            $output .= '<tr><td><label>' . __('t-Event Token Suffix', 'se') . '</label></td>';
		    $output .= "<td><input type='text' class='newtag' name='teventsuffix' value='" . $tevent['token_suffix'] . "' ></td></tr>";
            $output .= '</table>';
		echo $output;
	}

    /**
     * Recent Updates by Tourist Agent
     */
    function tourist_box_cb($post){
        global $wpdb;
        // Construct Registration Table Name.
        $registration_table = $wpdb->prefix . $this->registration_table;

        // Get Recent Registrations from the Database.
        $registrations = $wpdb->get_results(
            "SELECT id, fname, token, reg_time, eid, ip, medium, paid, amount, seq_no
            FROM $registration_table
            WHERE eid = $post->ID
            ORDER BY id
            DESC
            LIMIT 50
            ", ARRAY_A );

        // Echo the header.
        echo '<h3>t-Events Log</h3>';

        // Table Header.
        echo '<table class="widefat">
            <thead>
                <tr>
                    <th class="row-title">ID</th>
                    <th>FName</th>
                    <th>Reg Time</th>
                    <th>EID</th>
                    <th>IP</th>
                    <th>Medium</th>
                    <th>Paid</th>
                    <th>Amount</th>
                    <th>Seq No</th>
                    <th>Token</th>
                    <th>Details (Token)</th>
                </tr>
            </thead>
            <tbody>';

        // Loop through the $registrations and fill the table.
        foreach($registrations as $registration):

            // TODO
            $debug_link = '<a href="' .
            admin_url('edit.php?post_type=events&page=manage-sigma-events&tab=debugger')
            . '" >Debug ( ' . $registration['paid'] . ' )</a>';

            // Tracker Link
            $tracker_link = '<a target="blank" href="' .
                get_home_url() . '/sigma-events/tracker/?sigma_token=' . $registration['token']
            . '" >Details</a>';

            echo'<tr>
                <td class="row-title">' . $registration['id']          . '</td>
                <td>'                   . $registration['fname']       . '</td>
                <td>'                   . $registration['reg_time']    . '</td>
                <td>'                   . $registration['eid']         . '</td>
                <td>'                   . $registration['ip']          . '</td>
                <td>'                   . $registration['medium']      . '</td>
                <td>'                   . $registration['paid']        . '</td>
                <td>'                   . $registration['amount']      . '</td>
                <td>'                   . $registration['seq_no']       . '</td>
                <td>'                   . $registration['token']       . '</td>
                <td>'                   . $tracker_link . '</td>
                </tr>';
        endforeach;

        // Table Footer.
        echo '<tfoot>
                <tr>
                    <th class="row-title">ID</th>
                    <th>FName</th>
                    <th>Reg Time</th>
                    <th>EID</th>
                    <th>IP</th>
                    <th>Medium</th>
                    <th>Paid</th>
                    <th>Amount</th>
                    <th>Seq No</th>
                    <th>Token</th>
                    <th>Details (Token)</th>
                </tr>
            </tfoot>
            </tbody>';

        echo '</table>';
    }

	/**
	 * Save Tourist Boxes
	 *
	 * Saves product related meta data with the product.
	 */
	function save_tourist_boxes($post_id){
		// Return on autosave.
		if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
			return;

		// Nonce check.
		if(!isset($_POST['sigma_tevents_meta_data']) ||
			!wp_verify_nonce($_POST['sigma_tevents_meta_data'], 'sigma_tevents_meta_save'))
			return;

		// Permission check.
		if('tevents' != $_POST['post_type'] ||
			!current_user_can('edit_post', $post_id ))
			return;

		$tevent['event']        = sanitize_text_field($_POST['teventevent']);
		$tevent['password']     = sanitize_text_field($_POST['teventpassword']);
		$tevent['token_suffix'] = sanitize_text_field($_POST['teventsuffix']);

		update_post_meta($post_id, 'sigma_tevent_meta', $tevent);
	}

	/*
	 * Redirect Tourist Agent Template
	 */
	function redirect_tourist_agent_template(){
		if('tevents' == get_post_type()) :
			add_action('wp_enqueue_scripts', array($this, 'enqueue'));
			include SIGMA_PATH . 'templates/sigma-tevents.php';
			die();
		endif;
	}

    /**
     * Rewrite Sigma Tourist Agent Requests
     */
    function sigma_tourist_rewrite(){
        $agent_endpoint      = $this->endpoint . '/agent/?$';
        add_rewrite_rule($agent_endpoint,      'index.php?' . $this->endpoint . '=agent',    'top');
        add_rewrite_tag('%' . $this->endpoint . '%', '([^&]+)');
    }

    /**
     * Redirect Sigma Tourist Agent Endpoint Requests
     */
    function redirect_tourist_endpoint_requests(){
        global $wp_query;
        if(!isset($wp_query->query_vars[$this->endpoint]))
            return;

		add_action('wp_enqueue_scripts', array($this, 'enqueue'));
        $query_var = $wp_query->query_vars[$this->endpoint];
        if($query_var == 'agent'):
            add_action( 'wp_head', array( $this, 'prevent_indexing' ) );
            $registration = $this->process_agent_request();
			include SIGMA_PATH . 'templates/sigma-tevents.php';
            exit;
        endif;
    }

    /**
     * Keep Tourist Agent Interface being indexed
     */
    function prevent_indexing(){
        echo '<meta name="robots" content="noindex,nofollow"/>';
    }

    /**
     * Process Tourist Agent Request
     */
    function process_agent_request(){
        /**
         * Input Sanitization
         */
        $token    = isset($_POST['token'])    ? sanitize_text_field($_POST['token'])    : '';
        $currency = isset($_POST['currency']) ? sanitize_text_field($_POST['currency'])   : '';
        $amount   = isset($_POST['amount'])   ? sanitize_text_field($_POST['amount'])   : '';
        $password = isset($_POST['password']) ? sanitize_text_field($_POST['password']) : '';

        /**   /
         ** Input Validation
         **/
        if(  ''       == $token
           || ''     == $amount
            || ''   == $currency
             || '' == $password
               )   return 'Enter Transaction Details to Process';

        /**
         * Check Password
         */
        $tevent = $this->get_event_by_password($password);
        if(2 != sizeof($tevent))
            return 'Error. Check the information again.';

        /**
         * Check For Token Existance
         */
        $token = substr($token, 0, 10);
        $registration = $this->get_registration_record($token);
        if(!$registration)
            return 'No registration record found for the token';

        $numbers = explode('.', $amount);
        if(1 ==  sizeof($numbers)):
            $amount = $numbers[0] * 100;
        elseif(2 ==  sizeof($numbers)):
            if(1 == strlen($numbers[1])):
                $numbers[1] = $numbers[1] * 10;
            endif;
            $amount = $numbers[0] . substr($numbers[1], 0, 2);
        else:
            return 'Invalid number format (use xxx.xx format)';
        endif;

        if('ars' == $currency):
            $registration['amount'] = $amount;
        elseif('usd' == $currency):
            $registration['amount'] = (int) ($amount * $registration['rate']);
        else:
            return 'Error. Check the currency format.';
        endif;

        unset($registration['id']);
        $registration['token']  = $registration['token'] . $tevent['token_suffix'];
        $registration['eid']    = $tevent['event_id'];
        $registration['paid']   = 'notpaid';
        $registration['seq_no'] = 0;

        $this->update_t_registration_record($registration);
        return $registration;
    }

    /**
     * Retrieve the Registration Record for a Token
     *
     * After receiving a POST the token is recognized. Then the token
     * is used to retrieve the registration record. Registration record
     * contains the information about the event registration.
     *
     * @param   string  $token              Registration Token
     *
     * @return  array|false Registration Record for the Token or false if
     *                      unable to retrieve the record.
     */
    function get_registration_record($token){
        global $wpdb;
        $table_name     = $wpdb->prefix . $this->registration_table;
        $where          = "'" . $token . "'";
        $registration   = $wpdb->get_results(
            "
            SELECT *
            FROM $table_name
            WHERE token = $where
            ", ARRAY_A
        );

        if(!$registration)
            return false;

        return $registration[0];
    }

    /**
     * Get t-Event using the Password
     */
    function get_event_by_password($password){
        /**
         * Get all the t-Events
         */
        $tevents = get_posts('post_type=tevents&numberposts=-1');

        /**
         * Find the Event ID
         */
        foreach($tevents as $tevent):
            $meta = get_post_meta($tevent->ID, 'sigma_tevent_meta', true);
            if($password == $meta['password']):
                $tevent = array(
                    'event_id'     => $tevent->ID,
                    'token_suffix' => $meta['token_suffix']
                );
                return $tevent;
            endif;
        endforeach;
        return false;
    }

    /**
     * Update registration record as t-event registraiton
     */
    function update_t_registration_record($registration){
        global $wpdb;
        $table_name = $wpdb->prefix . $this->registration_table;

        $update = $this->get_registration_record($registration['token']);
        if($update):
            $where = array( 'token' => $registration['token'] );
            $r     = $wpdb->update($table_name, $registration, $where);
        else:
            $r     = $wpdb->insert($table_name, $registration);
        endif;

        return $registration;
    }

	/**
	 * Enqueue Styles and Scripts
	 */
	function enqueue(){
        global $post_type;
        if('tevents' == $post_type && !is_admin()){
            // Products page stylesheet.
            wp_register_style('sigma-events-style', SIGMA_URL . 'css/sigma-events.css');
            wp_enqueue_style('sigma-events-style');

            // Products page javascripts.
            wp_register_script('sigma-events-script',
            SIGMA_URL . 'js/sigma-events.js', array('jquery'), '1.0', true);
            wp_enqueue_script('sigma-events-script');
        }
	}
}
endif;
?>