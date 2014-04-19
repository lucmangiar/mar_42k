<?php
if ( !class_exists('Sigma_Sequences') ) :
/**
 * Sigma Sequences
 *
 * Sigma Events are mostly sports events. Registrants are mostly athletes
 * for the sports events. A need to assign a unique number sequence for
 * an event was identified.
 *
 * This class registers a new post type to manage number sequences.
 * Additionally, this will serve some public helper methods.
 *
 * @package     SigmaEvents
 * @subpackage  Core
 * @since       Version 2.8
 */
class Sigma_Sequences{
	/**
	 * Sigma Sequences Constructor
	 */
	function __construct(){
		// Register post type.
		add_action('init', array($this, 'register_sigma_sequences_post_type'));

		// Add Current Sequencing Status.
		add_action('add_meta_boxes', array($this, 'add_meta_boxes'));

		// Save meta data.
		add_action('save_post', array($this, 'save_sequence_boxes'));

        // Answer is-number-available query
        add_action('wp_ajax_is_number_available',
            array( $this, 'is_number_available_pre' ));
        add_action('wp_ajax_nopriv_is_number_available',
            array( $this, 'is_number_available_pre' ));
	}

	/**
	 * Register Sigma Sequencess
	 *
	 * Add a custom post type 'sequences' to manage
	 * event number sequences.
	 */
	function register_sigma_sequences_post_type(){
		$labels = array(
			'name' => __('Sequences', 'se'),
			'singular_name' => __('Sequence', 'se'),
			'add_new' => __('Add Sequence', 'se'),
			'add_new_item' => __('Add New Sequence', 'se'),
			'edit_item' => __('Edit Sequence', 'se')
		);
		$args = array(
			'labels' => $labels,
			'public' => true,
			'supports' => array('title'),
			'menu_icon' => SIGMA_URL . 'assets/sigma.png'
		);
		register_post_type( 'sequences', $args );
	}

	/**
	 * Add Products Meta Boxes
	 *
	 * Add a meta box to manage product price.
	 */
	function add_meta_boxes(){
        /**
         * Starting and Ending Numbers of the Sequence
         */
		add_meta_box(
			'start_box',
			__('Starting and Ending Numbers', 'se'),
			array($this, 'limits_box_cb'),
			'sequences',
			'normal',
			'high');

        /**
         * An Overview of Current Sequences
         */
		add_meta_box(
			'status_box',
			__('Current Sequences', 'se'),
			array($this, 'status_box_cb'),
			'sequences',
			'normal',
			'default');

        /**
         * An Overview of Returned and Reserved Numbers
         */
		add_meta_box(
			'reserved_returned_box',
			__('Reserved and Returned Numbers', 'se'),
			array($this, 'reserved_returned_box_cb'),
			'sequences',
			'normal',
			'default');

        /**
         * A Table of All Assigned Numbers ( from database )
         */
		add_meta_box(
			'assigned_numbers_box',
			__('Latest Assignements in this Sequence', 'se'),
			array($this, 'assigned_numbers_box_cb'),
			'sequences',
			'normal',
			'default');

        /**
         * Facility to Add/Remove Numbers to Reserved/Returned Lists
         */
		add_meta_box(
			'edit_state_box',
			__('Edit Sequence State', 'se'),
			array($this, 'edit_state_box_cb'),
			'sequences',
			'side',
			'default');
	}

	/**
	 * Sequence Starting and Ending Number
	 */
	function limits_box_cb($post){
		wp_nonce_field('sigma_sequence_meta_save', 'sigma_sequence_meta_data');

		$sequence = get_post_meta($post->ID, 'sigma_sequence', true);
        if( '' == $sequence ){
            $sequence['start'] = 100;
        }

        // Move this to above later (2013-05-21)
        if( ! isset($sequence['end']) ){
            $sequence['end'] = 1000;
        }

        $output = '<table>';

        $output .= '<tr><td><label>' . __('Starting Number', 'se') . '</label></td>';
		$output .= "<td><input type='text' class='newtag' name='start' value='" . $sequence['start'] . "' ></td></tr>";
        $output .= '<tr><td><label>' . __('End Number', 'se') . '</label></td>';
		$output .= "<td><input type='text' class='newtag' name='end'   value='" . $sequence['end']   . "' ></td></tr>";

        $output .= '</table>';

		echo $output;
	}

    /**
     * Global Sequence Status
     */
	function status_box_cb($post){
        $sequences = get_option( 'sigma_sequences' );
        if( '' == $sequences ){
            echo 'No Sequences Defined Yet';
            return false;
        }

        $output = '';

        /**
         * Output next possible number, if possible
         * Update the 'sigma_sequences' option as well
         */
        if( isset($sequences[$post->ID]) ):
            $next = $this->get_next_number($post->ID);
            $sequences[$post->ID]['current'] = $next;
            update_option( 'sigma_sequences', $sequences );
            $output  .= '<h3><br />Next Sequence Number: ' . $next . '<br /><br /></h3>';
        endif;

        $output .= '<table class="widefat" >';
        $output .= '<thead>
                        <tr>
                            <th>Sequence Name</th>
                            <th>Starting Number</th>
                            <th>Ending Number</th>
                        </tr>
                    </thead>
                    <tbody>';

        foreach( $sequences as $id => $sequence ):

            if( isset($sequence['name']) && isset($sequence['name']) ):

            $output .= '<tr>';
            $output .=      '<td>' . $sequence['name'] . ' (id:' . $id . ')</td>';
            $output .=      '<td>' . $sequence['start'] . '</td>';
            $output .=      '<td>' . $sequence['end'] . '</td>';
            $output .= '</tr>';

            endif;

        endforeach;

        $output .= '</tbody>
                    <tfoot>
                        <tr>
                            <th>Sequence Name</th>
                            <th>Starting Number</th>
                            <th>Ending Number</th>
                        </tr>
                    </tfoot>';
        $output .= '</table>';

        echo $output;
	}

    /**
     * Returned Reserved Numbers
     */
    function reserved_returned_box_cb($post){
        $sequences = get_option( 'sigma_sequences' );
        if( '' == $sequences ){
            echo 'No Sequences Defined Yet';
            return false;
        }

        /**
         * Get the reserved list for the sequence
         */
        if( isset($sequences[$post->ID]['reserved'] ) ):
            $reserved = $sequences[$post->ID]['reserved'];
        else:
            $reserved = array();
        endif;

        /**
         * Get the returned list for the sequence
         */
        if( isset($sequences[$post->ID]['reserved'] ) ):
            $returned = $sequences[$post->ID]['returned'];
        else:
            $returned = array();
        endif;

        // Reserved Numbers
        $output  = '<table style="width:100%;">';
        $output .= '<tr><td style="width:50%;" valign="top">';
        $output .= '<table class="widefat">';
        $output .= '<thead>
                        <tr>
                            <th>Reserved Number</th>
                            <th>Token</th>
                        </tr>
                    </thead>
                    <tbody>';

        foreach( $reserved as $number => $token ):

            $output .= '<tr>';
            $output .=      '<td>' . $number . '</td>';
            $output .=      '<td>' . $token  . '</td>';
            $output .= '</tr>';

        endforeach;

        $output .= '</tbody>
                    <tfoot>
                        <tr>
                            <th>Reserved Number</th>
                            <th>Token</th>
                        </tr>
                    </tfoot>';
        $output .= '</table>';

        // Returned Numbers
        $output .= '</td><td style="width:50%;" valign="top">';
        $output .= '<table class="widefat">';
        $output .= '<thead>
                        <tr>
                            <th>Returned Number</th>
                            <th>Token</th>
                        </tr>
                    </thead>
                    <tbody>';

        foreach( $returned as $number => $token ):

            $output .= '<tr>';
            $output .=      '<td>' . $number . '</td>';
            $output .=      '<td>' . $token  . '</td>';
            $output .= '</tr>';

        endforeach;

        $output .= '</tbody>
                    <tfoot>
                        <tr>
                            <th>Returned Number</th>
                            <th>Token</th>
                        </tr>
                    </tfoot>';
        $output .= '</table>';
        $output .= '</td></tr></table>';
        echo $output;
    }

    /**
     * Assigned Numbers Box
     *
     * This shows recent assignments in a sequence
     */
    function assigned_numbers_box_cb($post){
        // Globalize WPDB and Sigma Events Objects.
        global $wpdb, $sigma_events;

        // Construct an ID ring.
        $id_ring = array();
        $args = array(
            'post_type' => 'events',
            'post_status' => 'publish',
            'numberposts' => -1
        );
        $events = get_posts( $args );
        foreach( $events as $event ):
            $sequence = get_post_meta( $event->ID, 'sigma_event_sequence', true );
            if( isset($sequence['sequence']) && $post->ID == $sequence['sequence'] ):
                array_push( $id_ring, $event->ID );
            endif;
        endforeach;

        // Empty ID ring. return!
        if( empty( $id_ring ) ) return false;

        // Compose the where clause
        $where = ' ( eid ) IN ( ' . implode( ', ', $id_ring ) . ' )'
            . " AND paid = 'paid' ";

        // Construct Registration Table Name.
        $registration_table = $wpdb->prefix . $sigma_events->registration_table;

        // Get Recent Registrations from the Database.
        $registrations = $wpdb->get_results(
            "SELECT id, fname, lname, token, reg_time, eid, ip, medium, paid, amount, seq_no
            FROM $registration_table
            WHERE $where
            ORDER BY seq_no
            DESC
            LIMIT 50
            ", ARRAY_A );

        // Echo the header.
        echo '<h3>Descending Order Number Assignment Report (source:database)</h3>';

        // Table Header.
        echo '<table class="widefat">
            <thead>
                <tr>
                    <th class="row-title">Seq No</th>
                    <th>Token</th>
                    <th>Details (Token)</th>
                    <th>EID</th>
                    <th>ID</th>
                    <th>Medium</th>
                    <th>Paid</th>
                    <th>Amount</th>
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
                <td class="row-title">' . $registration['seq_no']      . '</td>
                <td>'                   . $registration['token']       . '</td>
                <td>'                   . $tracker_link                . '</td>
                <td>'                   . $registration['eid']         . '</td>
                <td>'                   . $registration['id']          . '</td>
                <td>'                   . $registration['medium']      . '</td>
                <td>'                   . $registration['paid']        . '</td>
                <td>'                   . $registration['amount']      . '</td>
                </tr>';
        endforeach;

        // Table Footer.
        echo '<tfoot>
                <tr>
                    <th class="row-title">Seq No</th>
                    <th>Token</th>
                    <th>Details (Token)</th>
                    <th>EID</th>
                    <th>ID</th>
                    <th>Medium</th>
                    <th>Paid</th>
                    <th>Amount</th>
                </tr>
            </tfoot>
            </tbody>';

        echo '</table>';

        echo '<p>(query:' . $wpdb->last_query . ')</p>';

    }

    /**
     * Edit State Meta Box
     *
     * Allows admin to add numbers to the reserved list.
     * And to remove from the reserved list.
     *
     * Allows admin to add numbers to the returned list.
     * And to remove from the returned list.
     */
    function edit_state_box_cb($post){
        // Get sequence state meta
		$sequence_state = get_post_meta( $post->ID, 'sigma_sequence_state', true );
        if( '' == $sequence_state )
            $sequence_state = 'Add Remove Numbers to Reserved and Returned Lists';

        echo '<table style="width:100%;">';
        // Number
        echo '<tr>';
        echo '<td>Number</td>';
        echo '<td><input type="text" class="small-text" name="sequence_state_number"></td>';
        echo '</tr>';

        // Token
        echo '<tr>';
        echo '<td>Token</td>';
        echo '<td><input type="text" class="small-text" name="sequence_state_token" ></td>';
        echo '</tr>';

        // List
        echo '<tr>';
        echo '<td>List</td>';
        echo '<td><select name="sequence_state_list" >';
        echo '<option value="reserved" >Reserved</option>';
        echo '<option value="returned" >Returned</option>';
        echo '</select></td>';
        echo '</tr>';

        // Result
        echo '<tr>';
        echo '<td colspan="2">' . $sequence_state . '</td>';
        echo '</tr>';

        // Action
        echo '<tr>';
        echo '<td><input type="submit" name="sequence_state_change"
            value="Remove" class="button button-secondary"
            style="width:100%;"></td>';
        echo '<td><input type="submit"name="sequence_state_change"
            value="Add" class="button button-primary"
            style="width:100%;"></td>';
        echo '</tr>';
        echo '</table>';

        $sequence_state = 'Add Remove Numbers to Reserved and Returned Lists';
		update_post_meta( $post->ID, 'sigma_sequence_state', $sequence_state );
    }

	/**
	 * Save Sequence Boxes
	 */
	function save_sequence_boxes($post_id){
		// Return on autosave.
		if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
			return;

		// Nonce check.
		if(!isset($_POST['sigma_sequence_meta_data']) ||
			!wp_verify_nonce($_POST['sigma_sequence_meta_data'], 'sigma_sequence_meta_save'))
			return;

		// Permission check.
		if('sequences' != $_POST['post_type'] ||
			!current_user_can('edit_post', $post_id ))
			return;

		$start   = (int) sanitize_text_field($_POST['start']     );
		$end     = (int) sanitize_text_field($_POST['end']     );
		$name    =       sanitize_text_field($_POST['post_title']);

        // Get Current Status
        $sequences_option = get_option( 'sigma_sequences' );

        // Get Current Sequence Meta
		$sequence_meta = get_post_meta( $post_id, 'sigma_sequence', true );

        /**
         * If either
         *      no entry for this sequence in the 'sigma_sequences' option
         *  or
         *      'start' = 'end'
         *  allow to save new values for all.
         */
        if( !  isset( $sequences_option[$post_id]            )
            || isset( $sequences_option[$post_id]            )
            &&        $sequences_option[$post_id]['start']
            ==        $sequences_option[$post_id]['current'] ) {

            $sequences_option[$post_id] = array(
                'start'    => $start,
                'end'    => $end,
                'current'  => $start,
                'name'     => $name,
                'reserved' => array(),
                'returned' => array()
            );

            $sequence_meta['start'] = $start;
            $sequence_meta['end']   = $end;

        /**
         * Else
         *      Allow only to save 'end' and 'name'
         */
        } elseif(   isset( $sequences_option[$post_id]            )
                    &&     $sequences_option[$post_id]['start']
                    !=     $sequences_option[$post_id]['current'] ) {

            $sequence_meta['end']   = $end;
            $sequences_option[$post_id]['start'] = $sequence_meta['start'];
            $sequences_option[$post_id]['end']   = $end;
            $sequences_option[$post_id]['name']  = $name;
        }

		update_post_meta( $post_id, 'sigma_sequence', $sequence_meta );
        update_option( 'sigma_sequences', $sequences_option );

        /**
         * Process the actions and data from the 'State Change Meta Box'
         */
		$state_change = isset($_POST['sequence_state_change']) ? sanitize_text_field($_POST['sequence_state_change']) : '';
		$state_number = isset($_POST['sequence_state_number']) ? sanitize_text_field($_POST['sequence_state_number']) : '';
		$state_token  = isset($_POST['sequence_state_token'])  ? sanitize_text_field($_POST['sequence_state_token'])  : '';
		$state_list   = isset($_POST['sequence_state_list'])   ? sanitize_text_field($_POST['sequence_state_list'])   : '';

        /**
         * Take further actions if we have a valid token
         */
        if( '' != $state_number )
            $this->update_sequence_status( $post_id, $state_number, $state_token, $state_list, $state_change );
	}

    /**
     * Updates the sequence state
     *
     * The processor of edit_state_box_cb() meta box tasks.
     *
     * @see edit_state_box_cb()
     */
    function update_sequence_status( $post_id, $state_number, $state_token, $state_list, $state_change ){
           // Input Validation
        if(   '' == $state_number
           || '' == $state_list
             )      return false;

        $sequences_option = get_option( 'sigma_sequences' );

        // Remove this later (2013-05-23)
        if( ! isset($sequences_option[$post_id]['reserved']) ):
            $sequences_option[$post_id]['reserved'] = array();
        endif;
        if( ! isset($sequences_option[$post_id]['returned']) ):
            $sequences_option[$post_id]['returned'] = array();
        endif;


        // Add 'Reserved' or 'Returned' pair.
        if( 'Add' == $state_change ):
            $new_pair = array( $state_number => $state_token );

            // Add 'Reserved' Pair.
            if( 'reserved' == $state_list ):
                $reserved = $sequences_option[$post_id]['reserved'];

                // Is number already present in the 'Reserved' list.
                if( isset( $reserved[$state_number] )):
                    $r_token = $reserved[$state_number];
                    $message = $state_number . ' is currently reserved for the token ' .$r_token;

                // Is number NOT already present in the 'Reserved' list.
                else:
                    $reserved = $reserved + $new_pair;
                    $message = $state_number . ' is now reserved for the token ' .$state_token;
                endif;
                $sequences_option[$post_id]['reserved'] = $reserved;

            // Add 'Returned' Pair.
            elseif( 'returned' == $state_list ):
                $returned = $sequences_option[$post_id]['returned'];

                // Is number already present in the 'Returned' list.
                if( isset( $returned[$state_number] )):
                    $r_token = $returned[$state_number];
                    $message = $state_number . ' is currently in the returned list with the token ' .$r_token;

                // Is number NOT already present in the 'Returned' list.
                else:
                    $returned = $returned + $new_pair;
                    $message = $state_number . ' is now listed as returned with the token ' .$state_token;
                endif;
                $sequences_option[$post_id]['returned'] = $returned;
            endif;

        // Remove 'Reserved' or 'Returned' pair.
        elseif( 'Remove' == $state_change ):
            // Remove 'Reserved' Pair.
            if( 'reserved' == $state_list ):
                $reserved = $sequences_option[$post_id]['reserved'];

                // Is number already present in the 'Reserved' list.
                if( isset( $reserved[$state_number] )):
                    $r_token = $reserved[$state_number];
                    unset($reserved[$state_number]);
                    $message = $state_number . ' is removed from the reserved list with a token ' .$r_token;

                // Does number NOT already present in the 'Reserved' list.
                else:
                    $message = $state_number . ' number has no record in the reserved list ' .$state_token;
                endif;
                $sequences_option[$post_id]['reserved'] = $reserved;

            // Remove 'Returned' Pair.
            elseif( 'returned' == $state_list ):
                $returned = $sequences_option[$post_id]['returned'];

                // Is number already present in the 'Returned' list.
                if( isset( $returned[$state_number] )):
                    $r_token = $returned[$state_number];
                    unset($returned[$state_number]);
                    $message = $state_number . ' is removed from the returned list with a token ' .$r_token;

                // Does number NOT already present in the 'Returned' list.
                else:
                    $message = $state_number . ' number has no record in the returned list ' .$state_token;
                endif;
                $sequences_option[$post_id]['returned'] = $returned;
            endif;
        endif;
        update_option( 'sigma_sequences', $sequences_option );
		update_post_meta( $post_id, 'sigma_sequence_state', $message );
    }

    /**
     * Number Selection Form
     *
     * Outputs the number selection form for an event.
     */
    function get_number_selection_form( $event_id ){
        $output = '
            <div class="se-row">
            <label id="se-number-selection-handle">Number Reservations: Select and Reserve Your Number Now.
            <span id="se-selection-handle"> (expand) </span></label>
            </div>
            <div class="se-row" id="se-expandable-number-selection">
            <div class="se-half">
                <label>Enter Your Number</label>
                <input type="text" name="selected_number" id="se-number" value="" >
                <input type="hidden" name="event_id" id="se-selection-event-id"
                    value="' . $event_id . '" >
            </div>
            <div class="se-half">
                <input type="button" id="se-number-query" value="Check Availability" >
                <input type="hidden" id="se-number-query-url" value="' .
                admin_url( 'admin-ajax.php' ) . '" >
                <label id="se-selection-result" >Select a custom number for your registration.</label>
            </div>';
            // Check for failed number assignment message
            $status = isset( $_GET['status'] ) ? sanitize_text_field( $_GET['status'] ) : '';
            if( 'number_not_available' == $status ):
                $output .= '<span id="se-number-not-available" >The number you
                    entered is not available. Please try again or leave blank to assign the next available number.</span>';
            endif;
                $output .= '<span class="se-number-notice" >* Number reservation fee will be charged, if you reserve your number.</span>';
                $output .= '<span class="se-number-notice" >* Leave blank to assign the next available number automatically.</span>';
            $output .= '</div>';
            $loading = '<img src="' . SIGMA_URL . 'assets/number-loading.gif">';
            $output .= '<div class="se-row" id="se-number-selection-loading">Loading  ' . $loading . '</div>';
        return $output;
    }

    /**
     * Is number available?
     *
     * This will be called upon receiving the Ajax POST to check for
     * a number to verify its availability
     */
    function is_number_available_pre(){
        // Sanitization
        $number = isset( $_POST['number'] )
            ? sanitize_text_field( $_POST['number'] ) : '';
        $event_id = isset( $_POST['number'] )
            ? sanitize_text_field( $_POST['event_id'] ) : '';

        // Validation
        if(     '' == $number   ):
            echo 'Enter your preferred number'; exit;
        elseif( '' == $event_id ):
            echo 'Invalid event id';            exit;
        endif;

        $result = $this->is_number_not_available( $number, $event_id );
        if( $result ):
            echo $result;
        else:
            echo 'Your Number is Available';
        endif;
        exit;
    }

    /**
     * Verify for a number in the POST is not available
     *
     * @since   version 3.0
     */
    function is_number_not_available( $number, $event_id ){
        // Get the sequence meta information from the event
        $sequence = get_post_meta($event_id, 'sigma_event_sequence', true);
        if($sequence['sequence'] == ''):
            $sequence = 0;
        else:
            $sequence = $sequence['sequence'];
        endif;
        if( 0 == $sequence ):
            return 'Internal Error ( No sequence associated with this event )';
        endif;

        // Get the sequence information from sequences array option
        $sequences_option = get_option( 'sigma_sequences' );
        $sequence_option = isset( $sequences_option[$sequence] )
           ? $sequences_option[$sequence] : '';
        if( '' == $sequence_option ):
            return 'Internal Error ( No record regarding the current event sequence )';
        endif;

        // Check for the number range
        $error_string = 'Enter a value between ' . $sequence_option['start']
            . ' and ' .  $sequence_option['end'];
        if(    $number < $sequence_option['start']
            || $number > $sequence_option['end'] ):
            return $error_string;
        endif;

        // Get the current reservations
        $reserved = $sequence_option['reserved'];
        $r = in_array( $number, array_keys( $reserved ) );
        if( $r ):
            return $number . ' is already reserved. Please try another.';
        endif;

        // Check database records
        $r = $this->is_number_assigned( $number, $event_id );
        if( $r ):
            return $number . ' is already assigned. Please try another.';
        endif;

        return false;
    }

    /**
     * Checks whether a sequence number has already been assigned.
     *
     * Checks whether the number is present in the database
     *
     * @since   version 3.0
     *
     * @var     string  $number     Number to find ( needle )
     * @var     string  $event_id   For which event ( heystack identifier )
     * @return  boolean Number present? true or false
     */
    function is_number_assigned( $number, $event_id ){
        global $wpdb, $sigma_events;
        $table_name     = $wpdb->prefix . $sigma_events->registration_table;
        $where          = " seq_no = '" . $number . "' AND eid = '" . $event_id . "'";
        $registration   = $wpdb->get_results(
            "
            SELECT *
            FROM $table_name
            WHERE $where
            ", ARRAY_A
        );

        if($registration) return true;
        return false;
    }

    /**
     * Number Reservation Function
     *
     * To be called after checking availability of the
     * number.
     *
     * Adds the number to the reserved number list.
     *
     * Clears the number from the returned list, if present.
     */
    function reserve_number( $number, $event_id, $token ){
        // Get the sequence meta information from the event
        $sequence = get_post_meta($event_id, 'sigma_event_sequence', true);
        if(! isset( $sequence['sequence'] )) return false;

        $sequence = $sequence['sequence'];

        // Get the sequence information from sequences array option
        $sequences_option = get_option( 'sigma_sequences' );
        if(! isset( $sequences_option[$sequence] )) return false;

        // Clear Returned array if present this number
        if( isset( $sequences_option[$sequence]['returned'][$number] ) )
            unset( $sequences_option[$sequence]['returned'][$number] );

        $sequences_option[$sequence]['reserved'] += array( $number => $token );

        update_option( 'sigma_sequences', $sequences_option );
        return true;
    }

    /**
     * Get the Next Sequence Number for an Event
     *
     * Advance Number Sequence to the the Next Level.
     *
     * @var     string  $event_id   Event Identifier to retrive next sequence number
     * @var     string  $token      Token of the current registrant
     * @return  string  Next Sequence Number for the event
     *                  or 'none' if no sequence is associated with the event
     *                  or a previously reserved number which could be found
     *                  in the reservations array.
     */
    function get_sequence_number( $event_id, $token ){
        // Get the global 'sigma_sequences' array
        $sequences_option = get_option( 'sigma_sequences' );

        // Get the event specific sequence identifier ( Event Meta Information ),
        // which is also a top level node in the sequences array.
        $current_sequence = get_post_meta($event_id, 'sigma_event_sequence', true);
        if($current_sequence['sequence'] == ''):
            $current_sequence = 0;
        else:
            $current_sequence = $current_sequence['sequence'];
        endif;

        // Is this event really associated with any sequence?
        if( ! isset( $sequences_option[$current_sequence] ) ) return 'none';

        // get the reservations in this sequence.
        $reserved = $sequences_option[$current_sequence]['reserved'];

        // If this token has a previously reserved number associated with it.
        $number = array_search( $token, $reserved );
        if( $number ):
            // Unset this reservation. This is assigned and no longer reserved.
            unset($reserved[$number]);
            // Update this change back in the 'sigma_sequences' option.
            $sequences_option[$current_sequence]['reserved'] = $reserved;
            update_option( 'sigma_sequences', $sequences_option );
            return $number;
        endif;

        // return from returned tokens
        $returned = $sequences_option[$current_sequence]['returned'];
        if( sizeof( $returned ) > 0 ):
            $number = array_pop( array_keys( $returned ) );
            unset( $returned[$number] );
            // Update this change back in the 'sigma_sequences' option.
            $sequences_option[$current_sequence]['returned'] = $returned;
            update_option( 'sigma_sequences', $sequences_option );
            return $number;
        endif;

        // Increment current index by one.
        $number = $this->get_next_number($current_sequence);

        $sequences_option[$current_sequence]['current'] = $number;

        // Store this increment back in the 'sigma_sequences' option.
        update_option( 'sigma_sequences', $sequences_option );
        return $number;
    }

    /**
     * Get the Next Number
     */
    function get_next_number( $sequence ){
        // Get the already assigned numbers array
        $assigned = $this->get_assigned_numbers_array( $sequence );

        // Get the reserved numbers array
        $sequences = get_option( 'sigma_sequences' );

        if( isset($sequences[$sequence]['reserved']) ):
            $reserved = $sequences[$sequence]['reserved'];
        else:
            $reserved = array();
        endif;

        if( ! $assigned )
            $assigned = array();

        // Get starting number
        if( isset($sequences[$sequence]['start']) ):
            $start = $sequences[$sequence]['start'];
        else:
            $start = 0;
        endif;

        /**
         * Remove Later
         */
        $i = $start;
        while( true ){
            if(isset($assigned[$i]) || isset($reserved[$i])):
                $i++;
                continue;
            endif;
            return $i;
        }
    }

    /**
     * Query Database and Get All Assigned Numbers
     */
    function get_assigned_numbers_array( $sequence ){
        // Globalize WPDB and Sigma Events Objects.
        global $wpdb, $sigma_events;

        // Construct the where clause.
        $id_ring = array();
        $args = array(
            'post_type' => 'events',
            'post_status' => 'publish',
            'numberposts' => -1
        );
        $events = get_posts( $args );
        foreach( $events as $event ):
            $event_sequence = get_post_meta( $event->ID, 'sigma_event_sequence', true );
            if( $event_sequence['sequence'] == $sequence ):
                array_push( $id_ring, $event->ID );
            endif;
        endforeach;

        if( empty( $id_ring ) ) return false;

        $where = ' ( eid ) IN ( ' . implode( ', ', $id_ring ) . ' )'
            . " AND paid = 'paid' ";

        // Construct Registration Table Name.
        $registration_table = $wpdb->prefix . $sigma_events->registration_table;

        // Get Recent Registrations from the Database.
        $registrations = $wpdb->get_results(
            "SELECT seq_no
            FROM $registration_table
            WHERE $where
            ORDER BY seq_no
            DESC
            ", ARRAY_A );

        /**
         * Delete these later
         */
        $assigned = array();
        foreach( $registrations as $registration ):
            $assigned[$registration['seq_no']] = true;
        endforeach;

        return $assigned;
    }

    /**
     * Returns the sequence number for a token
     */
    function return_sequence_number( $event_id, $token ){
        // Get the global 'sigma_sequences' array
        $sequences_option = get_option( 'sigma_sequences' );

        // Get the event specific sequence identifier ( Event Meta Information ),
        // which is also a top level node in the sequences array.
        $current_sequence = get_post_meta($event_id, 'sigma_event_sequence', true);

        if($current_sequence['sequence'] == '' || $current_sequence['sequence'] == 0 ):
            return true;
        else:
            $current_sequence = $current_sequence['sequence'];
        endif;

        $reserved = $sequences_option[$current_sequence]['reserved'];
        $returned = $sequences_option[$current_sequence]['returned'];
        $number   = array_search( $token, $reserved );
        if( $number ):
            unset($reserved[$number]);
            $returned[$number] = $token;
            $sequences_option[$current_sequence]['reserved'] = $reserved;
            $sequences_option[$current_sequence]['returned'] = $returned;
            update_option( 'sigma_sequences', $sequences_option );
            return true;
        endif;

        return true;
    }
}
endif;
?>
