<?php
if ( !class_exists('Sigma_Codes') ) :
/**
 * Sigma Discount Codes
 *
 * @package     SigmaEvents
 * @subpackage  Utilities
 * @since       version 3.8
 */
class Sigma_Codes{
    /**
     * Sigma Registration Table
     */
    private $registration_table;

    /**
     * Sigma Codes Constructor
     */
    function __construct($registration_table){
        // Registration Table
        $this->registration_table = $registration_table;

		// Register post type.
		add_action('init', array($this, 'register_sigma_codes_post_type'));

		// Add Current Sequencing Status.
		add_action('add_meta_boxes', array($this, 'add_meta_boxes'));

		// Save meta data.
		add_action('save_post', array($this, 'save_codes_boxes'));

        // Discount Code Columns.
        add_filter( 'manage_edit-codes_columns', array($this, 'edit_codes_columns') ) ;
        add_action( 'manage_codes_posts_custom_column', array($this, 'manage_codes_columns'), 10, 2 );
        add_filter( 'manage_edit-codes_sortable_columns', array($this, 'codes_sortable_columns') );
        add_action( 'load-edit.php', array($this, 'edit_codes_load') );
    }

    function edit_codes_columns($columns){
        $columns = array(
            'cb'        => '<input type="checkbox" />',
            'title'     => __( 'Code', 'se' ),
            'name'      => __( 'Team Name', 'se' ),
            'total'     => __( 'Total Codes', 'se' ),
            'remaining' => __( 'Remaining Codes', 'se' ),
            'date'      => __( 'Since', 'se' )
        );
        return $columns;
    }

    function manage_codes_columns($column, $post_id){
        $code_meta = get_post_meta($post_id, 'sigma_code_meta', true);
        switch($column){
        case 'name' :
            echo $code_meta['team_name'];
            break;
        case 'total' :
            echo $code_meta['quantity'];
            break;
        case 'remaining' :
            echo $code_meta['quantity'] - $code_meta['current'];
            break;
        default:
            break;
        }
    }

    function codes_sortable_columns($columns){
        $columns['total']     = 'total';
        $columns['remaining'] = 'remaining';
        $columns['name']      = 'name';
        return $columns;
    }

    function edit_codes_load(){
        add_filter( 'the_posts', array($this, 'sort_codes_columns'), 10, 2 );
    }

    function sort_codes_columns($posts, $query){
        if(!is_admin())
            return;

        $list    = $posts;
        $orderby = $query->get('orderby');
        $order   = $query->get('order');
        $output  = array();

        if('asc' == $order):
            $r = asort($output);
        else:
            $r = arsort($output);
        endif;

        if(in_array($orderby, array('total', 'name', 'remaining'))):
            $list = $this->get_list($orderby, $order);
            return $list;
        endif;

        return $list;
    }

    /**
     * Get Posts Wrapper for Sorted Columns
     *
     * Get List
     */
    function get_list($meta_field, $order){
        /**
         * Get All of'em.
         */
        $posts = get_posts('post_type=codes&posts_per_page=-1');

        /**
         * Create a meta values array
         */
        $output = array();
        $i = 0;
        foreach($posts as $post){
            $code_meta = get_post_meta($post->ID, 'sigma_code_meta', true);
            if('total' == $meta_field){
                $output[$i] = $code_meta['quantity'];
            } else if ('remaining' == $meta_field) {
                $output[$i] = $code_meta['quantity'] - $code_meta['current'];
            } else if ('name' == $meta_field) {
                $output[$i] = $code_meta['team_name'];
            }
            $i++;
        }

        /**
         * Sort the Meta Values
         */
        if('asc' == $order):
            $r = asort($output);
        else:
            $r = arsort($output);
        endif;

        /**
         * Create the new list
         */
        $list = array();
        $j = 0;
        foreach($output as $post_id => $meta_value){
            $list[] = $posts[$post_id];
            if(20 == $j) return $list;
            $j++;
        }
    }

	/**
	 * Register Sigma Coupon Codes
	 *
	 * Add a custom post type 'codes' to manage
	 * discount codes.
	 */
	function register_sigma_codes_post_type(){
		$labels = array(
			'name' => __('Discount Codes', 'se'),
			'singular_name' => __('Discount Code', 'se'),
			'add_new' => __('Add Code', 'se'),
			'add_new_item' => __('Add New Code', 'se'),
			'edit_item' => __('Edit Code', 'se')
		);
		$args = array(
			'labels' => $labels,
			'public' => true,
			'supports' => array('title'),
			'menu_icon' => SIGMA_URL . 'assets/sigma.png'
		);
		register_post_type( 'codes', $args );
	}

	/**
	 * Add Discount Codes Meta Boxes
	 *
	 * Add a meta boxes to manage and monitor discount codes.
	 */
	function add_meta_boxes(){
		add_meta_box(
			'code_box',
			__('Manage Discount Code', 'se'),
			array($this, 'code_box_cb'),
			'codes',
			'normal',
			'default');

		add_meta_box(
			'code_status_box',
			__('Discount Code Status', 'se'),
			array($this, 'code_status_box_cb'),
			'codes',
			'normal',
			'default');
	}

	/**
	 * Collect Discount Code Data
	 *
	 * Dicount code property definitions.
	 */
	function code_box_cb($post){
		wp_nonce_field('sigma_codes_meta_save', 'sigma_codes_meta_data');

		$code = get_post_meta($post->ID, 'sigma_code_meta', true);
			if($code == ''){
                $code['team_name'] = '';
                $code['rate']      = '0.9';
                $code['quantity']  = '';
                $code['current']  = '0';
            }

            $output  = '<table>';
            $output .= '<tr><td><label>' . __('Team Name', 'se') . '</label></td>';
		    $output .= "<td><input type='text' class='newtag' name='codeteamname' value='" . $code['team_name'] . "' ></td></tr>";
            $output .= '<tr><td><label>' . __('Discount Rate', 'se') . '</label></td>';
		    $output .= "<td><input type='text' class='newtag' name='coderate' value='" . $code['rate'] . "' ></td></tr>";
            $output .= '<tr><td><label>' . __('Code Quantity', 'se') . '</label></td>';
		    $output .= "<td><input type='text' class='newtag' name='codequantity' value='" . $code['quantity'] . "' ></td></tr>";
		    $output .= "<input type='hidden' class='newtag' name='codecurrent' value='" . $code['current'] . "' >";
            $output .= '</table>';
		echo $output;
	}

    /**
     * Current status of codes
     */
    function code_status_box_cb($post){
		$code = get_post_meta($post->ID, 'sigma_code_meta', true);
        $remaining = $code['quantity'] - $code['current'];
        if(0 == $remaining):
            echo '<h3>Discount code has been expired.</h3>';
        else:
            echo '<h3>Remaining Codes: ' . $remaining . '</h3>';
        endif;

        global $wpdb;
        $table_name     = $wpdb->prefix . $this->registration_table;
        $where          = "'" . $post->post_name . "'";
        $registrations  = $wpdb->get_results(
            "
            SELECT *
            FROM $table_name
            WHERE disc_code = $where
            ORDER BY seq_no DESC
            ", ARRAY_A
        );

        if(!$registrations)
            return false;

        echo '<h3>Recent Discounted Registrations</h3>';

        $output = '<table class="widefat" >';
        $output .= '<thead>
                        <tr>
                            <th>Name</th>
                            <th>Token</th>
                            <th>Payment</th>
                            <th>Details</th>
                        </tr>
                    </thead>
                    <tbody>';

        foreach( $registrations as $registration ):

            // Tracker Link
            $tracker_link = '<a target="blank" href="' .
                get_home_url() . '/sigma-events/tracker/?sigma_token=' . $registration['token']
            . '" >Details</a>';

            $output .= '<tr>';
            $output .=      '<td>' . $registration['fname'] . ' ' . $registration['lname'] . '</td>';
            $output .=      '<td>' . $registration['token'] . '</td>';
            $output .=      '<td>' . $registration['paid']  . '</td>';
            $output .=      '<td>' . $tracker_link          . '</td>';
            $output .= '</tr>';
        endforeach;

        $output .= '</tbody>
                    <tfoot>
                        <tr>
                            <th>Name</th>
                            <th>Token</th>
                            <th>Payment</th>
                            <th>Details</th>
                        </tr>
                    </tfoot>';
        $output .= '</table>';

        echo $output;

    }

	/**
	 * Save Code Boxes
	 *
	 * Saves discount code related meta data with the code.
	 */
	function save_codes_boxes($post_id){
		// Return on autosave.
		if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
			return;

		// Nonce check.
		if(!isset($_POST['sigma_codes_meta_data']) ||
			!wp_verify_nonce($_POST['sigma_codes_meta_data'], 'sigma_codes_meta_save'))
			return;

		// Permission check.
		if('codes' != $_POST['post_type'] ||
			!current_user_can('edit_post', $post_id ))
			return;

		$code['team_name'] = sanitize_text_field($_POST['codeteamname']);
		$code['rate']      = sanitize_text_field($_POST['coderate']);
		$code['quantity']  = sanitize_text_field($_POST['codequantity']);
        $code['current']   = sanitize_text_field($_POST['codecurrent']);

		update_post_meta($post_id, 'sigma_code_meta', $code);
	}

    /**
     * Apply Discount
     *
     * Check if such a discount code exists in Sigma System
     * Check the event being registered is enabled discount codes
     * Apply Discount
     *
     * param int    $price  Original Registration Price
     * param string $code   Discount Code
     * param int    $event  Event ID
     * return int           Discounted Price
     */
    function apply_discount($price, $code, $event){
        // Get Discount Details
        $discount = $this->get_discount_by_code($code);

        // Invalid Discount Code?
        if(!$discount)
            return false;

        // Discounting not enabled for the event?
        $enabled = $this->is_discounting_enabled($event);
        if(!$enabled)
            return false;

        // Discounting limit reached?
        $discount['current'] = $discount['current'] + 1;
        if($discount['current'] > $discount['quantity'])
            return false;

        // Apply discount.
        $code_id = $discount['id'];
        unset($discount['id']);
        update_post_meta($code_id, 'sigma_code_meta', $discount);
        return array(
            'discounted_price' => $price * $discount['rate'],
            'team_name'        => $discount['team_name'],
            'disc_code'        => $code
        );
    }

    /**
     * Get Discound Code Details
     */
    function get_discount_by_code($input_code){
        $codes = new WP_Query('post_type=codes&posts_per_page=-1');
        if($codes->post_count > 0):
            foreach($codes->posts as $code):
                if($input_code == $code->post_title):
                    $code_id = $code->ID;
                    $code = get_post_meta($code_id, 'sigma_code_meta', true);
                    $code['id'] = $code_id;
                    return $code;
                endif;
            endforeach;
        endif;
        return false;
    }

    /**
     * Checks if discounting is enabled for an event
     */
    function is_discounting_enabled($event){
        $codes = get_post_meta($event, 'sigma_event_codes', true);
        if(isset($codes['enable_discount_codes']) && $codes['enable_discount_codes'])
            return true;

        return false;
    }
}
endif;
?>
