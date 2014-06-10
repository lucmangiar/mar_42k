<?php
if ( !class_exists('Sigma_Event') ) :
/**
 * Sigma Events
 *
 * Registers a custom post type to manage Sigma Events. Sigma Events CPT has
 * meta boxes to collect meta information about an event. Start and End dates,
 * Event Organizer Information, Event Payment Processors, etc.
 *
 * @package     SigmaEvents
 * @subpackage  Core
 *
 * A. Registrations are handled in this class.
 * B. Payment process initialization is done with in this class.
 * C. Selected Additional Products are Updated with this class.
 *
 * Types of Pages Handled through Class 'Sigma_Events'
 * ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
 *
 *  1. Sigma Events Registration Pages.
 *  2. /registration/ page.
 *  3. /payment/ page.
 *  4. /confirmation page.
 *
 *  @see redirect_events_template()
 *  @see sigma_rewrite()
 */
class Sigma_Event{
    /**
     * Sigma Rewrite Endpoint and Tag
     */
    private $endpoint = 'sigma-events';
    private $tag = 'sigma';

    /**
     * Registration Table Name
     */
    private $registration_table;

    /**
     * Replace Array
     *
     * To be used for passing email template pairs
     * to registration email composing
     */
    private $replace_array;

    /**
     * Sigma Events Constructor
     */
    function __construct( $registration_table ){
        // Setup Registration Table
        $this->registration_table = $registration_table;
        // A new custom post type to manage events.
        add_action('init', array($this, 'register_events_post_type'));

        // Add meta boxes for Organizer, Period and Price.
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));

        // Save custom meta boxes for events.
        add_action('save_post', array($this, 'save_meta_boxes'));

        // A redirect to serve event post types.
        add_action('template_redirect', array($this, 'redirect_events_template'));

        // Process sigma endpoint requests.
        add_action('template_redirect', array($this, 'redirect_endpoint_requests'));

        // Add rewrite rules rules for sigma endpoint.
        add_action('init', array($this, 'sigma_rewrite'));

        // Admin Scripts.
        add_action('admin_print_scripts-post-new.php', array($this, 'admin_scripts'));
        add_action('admin_print_scripts-post.php', array($this, 'admin_scripts'));
        add_action('admin_print_scripts-edit.php', array($this, 'admin_scripts'));

        // Help Tabs.
        //http://make.wordpress.org/core/2011/12/06/help-and-screen-api-changes-in-3-3/
        add_action('admin_print_scripts-post.php', array($this, 'add_help_tabs'));

        // Event Updated Messages
        add_filter( 'post_updated_messages', array($this, 'event_updated_messages') );

        /**
         * Update registration recored via Ajax
         */
        add_action('wp_ajax_update_registration_record', array($this, 'ajax_update_registration_record'));
        add_action('wp_ajax_nopriv_update_registration_record', array($this, 'ajax_update_registration_record'));
    }

    /**
     * Register Events Post Type
     *
     * A custom post type to manage events
     * for the admin from the admin end. Custom meta
     * boxes will be attached to collect meta information
     * about the events.
     */
    function register_events_post_type(){
        $labels = array(
            'name'          => __('Events', 'se'),
            'singular_name' => __('Event', 'se'),
            'add_new'       => __('Add Event', 'se'),
            'view_item'     => __('View Event Registration Page', 'se'),
            'add_new_item'  => __('Add New Event', 'se'),
            'edit_item'     => __('Edit Event', 'se')
        );
        $args = array(
            'labels'        => $labels,
            'public'        => true,
            'supports'      => array('title'),
            'menu_icon'     => SIGMA_URL . 'assets/sigma.png'
        );
        register_post_type( 'events', $args );
    }

    /**
     * Add Meta Boxes
     *
     * Add meta information about the event.
     * The information is collected in meta boxes,
     * Organizer, Period and Price.
     */
    function add_meta_boxes(){
        // Event Header Image meta box.
        add_meta_box(
            'header_image_box',
            __('Event Header Image', 'se'),
            array($this, 'header_image_box_cb'),
            'events',
            'normal',
            'high');

        // Event Description meta box.
        add_meta_box(
            'event_description_box',
            __('Event Description', 'se'),
            array($this, 'event_description_box_cb'),
            'events',
            'normal',
            'high');

        // Organizer meta box.
        add_meta_box(
            'organizer_box',
            __('Event Organizer', 'se'),
            array($this, 'organizer_box_cb'),
            'events',
            'normal',
            'high');

        // Event period meta box.
        add_meta_box(
            'period_box',
            __('Event Period', 'se'),
            array($this, 'period_box_cb'),
            'events',
            'side',
            'default');
			
			 // Event Age meta box.
        add_meta_box(
            'age_box',
            __('Event Age Range', 'se'),
            array($this, 'age_box_cb'),
            'events',
            'side',
            'default');

        // Price meta box.
        add_meta_box(
            'price_box',
            __('Event Price', 'se'),
            array($this, 'price_box_cb'),
            'events',
            'side',
            'default');

        // Payment Processor meta box.
        add_meta_box(
            'processor_box',
            __('Payment Processor', 'se'),
            array($this, 'processor_box_cb'),
            'events',
            'side',
            'default');

        // Sequences and Reservations meta box.
        add_meta_box(
            'sequences_box',
            __('Number Sequences and Reservations', 'se'),
            array($this, 'sequences_box_cb'),
            'events',
            'side',
            'default');

        // Discount Codes meta box.
        add_meta_box(
            'codes_box',
            __('Discount Codes', 'se'),
            array($this, 'codes_box_cb'),
            'events',
            'side',
            'default');

        // Additional products meta box.
        add_meta_box(
            'additional_products_box',
            __('Additional Products', 'se'),
            array($this, 'additional_products_box_cb'),
            'events',
            'side',
            'default');

        // Legal information meta box.
        add_meta_box(
            'legal_information_box',
            __('Legal Information', 'se'),
            array($this, 'legal_information_box_cb'),
            'events',
            'normal',
            'default');

        // Registration Email Template.
        add_meta_box(
            'registration_email_template_box',
            __('Registration Email Template', 'se'),
            array($this, 'registration_email_template_box_cb'),
            'events',
            'normal',
            'default');

        // Payment Confirmation Email Template.
        add_meta_box(
            'email_template_box',
            __('Payment Confirmation Email Template', 'se'),
            array($this, 'email_template_box_cb'),
            'events',
            'normal',
            'default');

        // Payment Page Banner
        add_meta_box(
            'payment_banner_box',
            __('Payment Page Banner', 'se'),
            array($this, 'payment_banner_box_cb'),
            'events',
            'normal',
            'default');
    }

    /**
     * Draw Event Header Image Meta Box
     */
    function header_image_box_cb($post){
        $event_details = get_post_meta($post->ID, 'sigma_event_details', true);
        $sample_banner = SIGMA_URL . 'assets/sigma-sample-image.jpg';

        // First Run
        if( !isset($event_details['header_image'])):
            $event_details['header_image'] = '';
        endif;

        // Prepare preview image source. Use sample image if none set.
        $preview_banner = isset($event_details['header_image'])
            && '' != $event_details['header_image']
            ?        $event_details['header_image']
            :        $sample_banner;

        $output = '<table style="width: 100%; max-width: 100%;" >';

        $output .= '<tr><td colspan="2">';
        $output .= '<img id="event-header-preview" class="sigma-image-preview" src="' . $preview_banner . '" >';
        $output .= '</td>';
        $output .= '</tr>';

        $output .= '<tr><td><label>' . __('Banner', 'se') . '</label></td>';
        $output .= '<td><input id="header-image" type="text" class="regular-text" name="event_header_image"
            value="' .  esc_attr( $event_details['header_image'] ) . '" />';

        // Payment Banner Upload Button
        // media.php:385
        $editor_id = 'header_image';
        $post = get_post();
        wp_enqueue_media( array('post' => $post) );
        $img = '<span class="wp-media-buttons-icon"></span> ';
        $output .= ' <a href="#" class="button insert-media add_media" data-editor="' . esc_attr( $editor_id ) .
            '" id="header_image_uploader" title="' . esc_attr__( 'Add Media' ) . '"> ' . $img . __( 'Upload Header Image' ) . '</a>';

        $output .= '</td></tr>';
        $output .= '</table>';

        echo $output;
    }

    /**
     * Draw Event Description Meta Box
     */
    function event_description_box_cb($post){
        $event_details = get_post_meta($post->ID, 'sigma_event_details', true);
        if( !isset($event_details['description']) ):
            $event_details['description'] = '';
        endif;

        $output = '<table style="width: 100%; max-width: 100%;" >';
        $output .= "<tr><td>";
            $msg = apply_filters('the_content', $event_details['description']);
            ob_start();
            wp_editor($msg, 'event_description', array('media_buttons' => false));
        $output .= ob_get_clean();
        $output .= "</td></tr>";

        $output .= '</table>';
        echo $output;
    }

    /**
     * Draw Organizer Meta Box
     *
     * Organizer meta box callback function.
     */
    function organizer_box_cb($post){
        // Add a nonce field to raise security level.
        wp_nonce_field('sigma_event_meta_save', 'sigma_event_meta_data');
        $organizer = get_post_meta($post->ID, 'sigma_event_organizer', true);
        $sample_logo = SIGMA_URL . 'assets/sample-logo.png';

        if($organizer == ''){
			$organizer['eventcode']='';
            $organizer['name']     = '';
            $organizer['mail']     = '';
            $organizer['url']      = '';
            $organizer['logo']     = $sample_logo;
            $organizer['question'] = '';
        }

        /**
         * New Fields
         */
        if(!isset($organizer['above_disclimer_text'])){
            $organizer['above_disclimer_text'] = '';
        }
        if(!isset($organizer['answer'])){
            $organizer['answer'] = 'Answer 1;Answer 2;Answer 3';
        }
        if(!isset($organizer['salesperson'])){
            $organizer['salesperson'] = $organizer['mail'];
        }
		
		if(!isset($organizer['eventcode'])){
            $organizer['eventcode'] = $organizer['eventcode'];
        }

        // Prepare preview organier logo. Use sample logo if none set.
        $logo_preview = isset($organizer['logo'])
            && '' != $organizer['logo']
            ?        $organizer['logo']
            :        $sample_logo;

        $output = '<table style="width: 100%; max-width: 100%;" ><tr><td><label>' . __('Organizer Name', 'se') . '</label></td>';
        $output .= "<td><input type='text' class='regular-text' name='oname' value='" . $organizer['name'] . "' ></td></tr>";
		
		$output .= '<tr><td><label>' . __('Event Code', 'se') . '</label></td>';
        $output .= "<td><input type='text' class='regular-text' name='eventcode' value='" . $organizer['eventcode'] . "' ></td></tr>";

        $output .= '<tr><td><label>' . __('Organizer Email', 'se') . '</label></td>';
        $output .= "<td><input type='text' class='regular-text' name='omail' value='" . $organizer['mail'] . "' ></td></tr>";

        $output .= '<tr><td><label>' . __('Salesperson Email', 'se') . '</label></td>';
        $output .= "<td><input type='text' class='regular-text' name='osalemail' value='" . $organizer['salesperson'] . "' ></td></tr>";

        $output .= '<tr><td><label>' . __('Organizer URL', 'se') . '</label></td>';
        $output .= "<td><input type='text' class='regular-text' name='ourl' value='" . $organizer['url'] . "' ></td></tr>";

        $output .= '<tr><td><label>' . __('Organizer Logo', 'se') . '</label></td>';

        $output .= '<td><input id="organizer_logo" type="text" class="regular-text" name="ologo" value="' .  esc_attr( $organizer['logo'] ) . '" />';

        // Organizer Logo Upload Button
        // media.php:385
        $editor_id = 'organizer_logo';
        $post = get_post();
        wp_enqueue_media( array('post' => $post) );
        $img = '<span class="wp-media-buttons-icon"></span> ';
        $output .= ' <a href="#" class="button insert-media add_media" data-editor="' . esc_attr( $editor_id ) . '" id="organizer_logo_uploader" title="' . esc_attr__( 'Upload Logo' ) . '"> ' . $img . __( 'Upload Organizer Logo' ) . '</a>';

        $output .= '</td>';

        // Logo Preview
        $output .= '<tr><td></td><td>';
        $output .= '<img id="organizer-logo-preview" class="sigma-image-preview" src="' . $logo_preview . '" >';
        $output .= '</td>';

        /**
         * Organizer Question
         */
        $output .= '<tr><td><label>' . __('Organizer Question', 'se') . '</label></td>';
        $output .= "<td><textarea class='regular-text' name='question'
                style='width: 100%; max-width: 100%;' >" . $organizer['question'] . "</textarea></td></tr>";

        /**
         * Organizer Answer List
         */
        $output .= '<tr><td><label>' . __('Organizer Answer', 'se') . '</label></td>';
        $output .= "<td><textarea class='regular-text' name='answer'
                style='width: 100%; max-width: 100%;' >" . $organizer['answer'] . "</textarea></td></tr>";

        /**
         * Above Disclaimer Text
         */
        $output .= '<tr><td><label>' . __('Above Disclaimer Text', 'se') . '</label></td>';
        $output .= "<td><textarea class='regular-text' name='above_disclimer_text'
                style='width: 100%; max-width: 100%;' >" . $organizer['above_disclimer_text'] . "</textarea></td></tr></table>";

        echo $output;
    }

    /**
     * Draw Period Meta Box
     *
     * Period meta box callback function.
     */
    function period_box_cb($post){
        $period = get_post_meta($post->ID, 'sigma_event_period', true);

        if($period == ''){ // Default Event Period. Starts in a day. Ends in a day after the start.
            $period['start'] = time() + DAY_IN_SECONDS;
            $period['end'] = time() + DAY_IN_SECONDS*2;
        }

        $output = '<table><tr><td><label>' . __('Event Start Date', 'se') . '</label></td>';
        $output .= "<td><input type='text' id='startdate' class='newtag' name='pstart' value='" . date('Y-m-d', $period['start']) . "' ></td></tr>";

        $output .= '<tr><td><label>' . __('Event End Date', 'se') . '</label></td>';
        $output .= "<td><input type='text' id='enddate' class='newtag' name='pend' value='" . date('Y-m-d', $period['end']) . "' ></td></tr></table>";

        echo $output;
    }
	
	
	/**
     * Draw Age Range Meta Box
     *
     * Period meta box callback function.
     */
    function age_box_cb($post){
        $age = get_post_meta($post->ID, 'sigma_event_age', true);

        if($age == ''){ // Default Event Age Range. Min = 16 and Max = 100.
            $age['min'] = 16;
            $age['max'] = 100;
        }
        $output = '<table><tr><td><label>' . __('Event Min Age', 'se') . '</label></td>';
        $output .= "<td><input type='text' id='minage' class='newtag' name='amin' value='" . $age['min'] . "' ></td></tr>";

        $output .= '<tr><td><label>' . __('Event Max Age', 'se') . '</label></td>';
        $output .= "<td><input type='text' id='maxage' class='newtag' name='amax' value='" . $age['max'] . "' ></td></tr></table>";

        echo $output;
    }

    /**
     * Draw Price Meta Box
     *
     * Price meta box callback function.
     */
    function price_box_cb($post){
        $price = get_post_meta($post->ID, 'sigma_event_price', true);

        if($price == ''){
            $price['local'] = '200';
            $price['rate'] = '5';
            $price['foreign'] = '40';
        }

        $output = '<table><tr><td><label>' . __('Local (ARS)', 'se') . '</label></td>';
        $output .= "<td><input type='text' class='newtag' name='localp' value='" . $price['local'] . "' ></td></tr>";

        $output .= '<tr><td><label>' . __('Foreign (USD)', 'se') . '</label></td>';
        $output .= "<td><input type='text' class='newtag' name='foreignp' value='" . $price['foreign'] . "' ></td></tr>";

        $output .= '<tr><td><label>' . __('Rate(ARS/USD)', 'se') . '</label></td>';
        $output .= "<td><input type='text' class='newtag' name='ratep' value='" . $price['rate'] . "' ></td></tr></table>";

        echo $output;
    }

    /**
     * Draw Processor Meta Box
     *
     * @param mixed $post current post
     * @access public
     * @return void
     */
    function processor_box_cb($post){
        $processor = get_post_meta($post->ID, 'sigma_event_processor', true);
        if($processor == ''){
            $processor['local']                  = 'decidir';
            $processor['foreign']                = 'decidir';

            $processor['local_decidir']          = false;
            $processor['local_dineromail']       = false;
            $processor['local_salesperson']      = false;
            $processor['local_ep']               = false;
            $processor['local_cuentadigital']    = false;

            $processor['foreign_decidir']        = false;
            $processor['foreign_dineromail']     = false;
            $processor['foreign_salesperson']    = false;
            $processor['foreign_ep']             = false;
            $processor['foreign_cuentadigital']  = false;
        }

        // Move this to above later ( 2013-05-26 )
        if( ! isset($processor['local_cuentadigital']) ):
            $processor['local_decidir']          = false;
            $processor['local_dineromail']       = false;
            $processor['local_salesperson']      = false;
            $processor['local_cuentadigital']    = false;


            $processor['foreign_decidir']        = false;
            $processor['foreign_dineromail']     = false;
            $processor['foreign_salesperson']    = false;
            $processor['foreign_cuentadigital']  = false;
        endif;

        // Move this to above later ( 2013-05-30 )
        if( ! isset($processor['local_ep']) ):
            $processor['local_ep']            = false;
            $processor['foreign_ep']          = false;
        endif;

        $output = '<table>';

        $output .= '<tr><td colspan="2" ><b>Local Payment Processing</b></td></tr>';
        $output .= '<tr><td><label>' . __('Local (ARS) Processor', 'se') . '</label></td>';
        $output .= "<td>
            <select name='localprocessor' value='" . $processor['local'] . "' >
            <option value='decidir'  " . selected( $processor['local'], 'decidir' , false ) . ">Decidir</option>
            <option value='dineromail' " . selected( $processor['local'], 'dineromail' , false ) . ">Dineromail</option>
            <option value='salesperson' " . selected( $processor['local'], 'salesperson' , false ) . ">Salesperson</option>
            <option value='cuentadigital' " . selected( $processor['local'], 'cuentadigital' , false ) . ">CuentaDigital</option>
            <option value='ep' " . selected( $processor['local'], 'ep' , false ) . ">EasyPlanners</option>
            <option value='paypal'" . selected( $processor['local'], 'paypal', false ) . ">Paypal</option>
            </select>
            </td></tr>
            <tr><td></td><td>Additional Processors for Locals</td></tr>
            <tr><td></td><td>
            <input name='localprocessordecidir' type='checkbox' " . checked( $processor['local_decidir'], true , false ) . ">Decidir
            </td></td><tr><td></td><td>
            <input name='localprocessordineromail' type='checkbox' " . checked( $processor['local_dineromail'], true , false ) . ">Dineromail
            </td></td><tr><td></td><td>
            <input name='localprocessorsalesperson' type='checkbox' " . checked( $processor['local_salesperson'], true , false ) . ">Salesperson
            </td></td><tr><td></td><td>
            <input name='localprocessorcuentadigital' type='checkbox' " . checked( $processor['local_cuentadigital'], true , false ) . ">CuentaDigital
            </td></td><tr><td></td><td>
            <input name='localprocessorep' type='checkbox' " . checked( $processor['local_ep'], true , false ) . ">EasyPlanners
            </td></td><tr><td></td><td>
            <input name='localpaypalrep' type='checkbox' " . checked( $processor['local_paypal'], true , false ) . ">Paypal
            </td></tr>";

        $output .= '<tr><td colspan="2" style="padding-top: 20px;" ><b>Foreign Payment Processing</b></td></tr>';
        $output .= '<tr><td><label>' . __('Foreign (USD) Processor', 'se') . '</label></td>';
        $output .= "<td>
            <select name='foreignprocessor' value='" . $processor['foreign'] . "' >
            <option value='decidir'  " . selected( $processor['foreign'], 'decidir' , false ) . ">Decidir</option>
            <option value='dineromail' " . selected( $processor['foreign'], 'dineromail' , false ) . ">Dineromail</option>
            <option value='salesperson' " . selected( $processor['foreign'], 'salesperson' , false ) . ">Salesperson</option>
            <option value='cuentadigital' " . selected( $processor['foreign'], 'cuentadigital' , false ) . ">CuentaDigital</option>
            <option value='ep' " . selected( $processor['foreign'], 'ep' , false ) . ">EasyPlanners</option>
            <option value='paypal'" . selected( $processor['foreign'], 'paypal', false ) . ">Paypal</option>
            </select>
            </td></tr>
            <tr><td></td><td>Additional Processors for Foreigners</td></tr>
            <tr><td></td><td>
            <input name='foreignprocessordecidir' type='checkbox' " . checked( $processor['foreign_decidir'], true , false ) . ">Decidir
            </td></td><tr><td></td><td>
            <input name='foreignprocessordineromail' type='checkbox' " . checked( $processor['foreign_dineromail'], true , false ) . ">Dineromail
            </td></td><tr><td></td><td>
            <input name='foreignprocessorsalesperson' type='checkbox' " . checked( $processor['foreign_salesperson'], true , false ) . ">Salesperson
            </td></td><tr><td></td><td>
            <input name='foreignprocessorcuentadigital' type='checkbox' " . checked( $processor['foreign_cuentadigital'], true , false ) . ">CuentaDigital
            </td></td><tr><td></td><td>
            <input name='foreignprocessorep' type='checkbox' " . checked( $processor['foreign_ep'], true , false ) . ">EasyPlanners
            </td></td><tr><td></td><td>
            <input name='foreignpaypalrep' type='checkbox' " . checked( $processor['foreign_paypal'], true , false ) . ">Paypal
            </td></tr>";

        $output .= '</table>';

        echo $output;
    }

    /**
     * Draw Sequences Meta Box
     *
     * @param mixed $post current post
     * @access public
     * @return void
     */
    function sequences_box_cb($post){
        $current_sequence = get_post_meta($post->ID, 'sigma_event_sequence', true);

        if( ! is_array( $current_sequence ) ){
            $new_sequence['sequence'] = $current_sequence;
            $new_sequence['enable_number_reservation'] = false;
            $new_sequence['local_reservation_fee']     = 3.1;
            $new_sequence['foreign_reservation_fee']   = 1;
            $current_sequence = $new_sequence;
        }

        $sequences = get_option( 'sigma_sequences' );
        if( '' == $sequences ){
            echo "<input name='sequence' type='hidden' value='none' >" ;
            echo "<input name='local_reservation_fee' type='hidden' value='0' >" ;
            echo "<input name='foreign_reservation_fee' type='hidden' value='0' >" ;
            echo 'No Sequences Found.';
            return false;
        }

        echo '<h4>Select the Number Sequence</h4>';
        echo '<select name="sequence" >';
                echo '<option value="0" >None</option>';
            foreach( $sequences as $id => $sequence ):
                $name = $sequence['name'] . ' [ ' . $sequence['start']
                    . ' - ' . $sequence['current'] . ' ]';
                $value = $id;
                $selected = selected( $id , $current_sequence['sequence'], false );
                echo '<option value="' . $value . '" ' . $selected . ' >'
                    . $name . '</option>';
            endforeach;
        echo '</select>';

        echo '<table>';

        // Heading
        echo '<tr>';
        echo '<td colspan="2">';
        echo '<h4>Number Reservation (Selling) Options</h4>';
        echo '</td>';
        echo '</tr>';

        // Enable Reservation
        echo '<tr>';
        echo '<td><label>Enable Number Reservation</label></td><td>';
        echo "<input name='enable_number_reservation' type='checkbox' "
            . checked( $current_sequence['enable_number_reservation'], true , false ) . ">";
        echo '</td>';
        echo '</tr>';

        // Number Reservation Fee for Locals
        echo '<tr>';
        echo '<td><label>Local Reservation Fee (ARS)</label></td><td>';
        echo "<input name='local_reservation_fee' type='text' "
            . " value='" . $current_sequence['local_reservation_fee'] . "' class='small-text' >";
        echo '</td>';
        echo '</tr>';

        // Number Reservation Fee for Foreigners
        echo '<tr>';
        echo '<td><label>Foreign Reservation Fee (USD)</label></td><td>';
        echo "<input name='foreign_reservation_fee' type='text' "
            . " value='" . $current_sequence['foreign_reservation_fee'] . "' class='small-text' >";
        echo '</td>';
        echo '</tr>';

        echo '</table>';
    }

    /**
     * Discount Codes
     *
     * Enable Discount Codes for this event
     */
    function codes_box_cb($post){
        $codes = get_post_meta($post->ID, 'sigma_event_codes', true);
        if(!$codes):
            $codes['enable_discount_codes'] = false;
        endif;

        echo '<table>';

        // Enable Codes
        echo '<tr>';
        echo "<td><input name='enable_discount_codes' type='checkbox' "
            . checked( $codes['enable_discount_codes'], true , false ) . ">";
        echo '</td>';
        echo '<td><label>Enable Discount Codes</label></td>';
        echo '</tr>';

        echo '</table>';
    }

    /**
     * Draw Additional Products Meta Box
     *
     * Repeatable Products Fields.
     */
    function additional_products_box_cb($post){
        $products = get_post_meta($post->ID, 'sigma_event_products', true);
        $query = new WP_Query('post_type=products');

        if($query->have_posts()):
            echo '<table>';
            foreach( $query->posts as $product ):
                $product_id = $product->ID;
                $product_state = isset($products[$product_id]) ? $products[$product_id] : false ;
                echo '<tr><td>';
                    echo  "<input type='checkbox' value='1' name='product_" . $product_id .  "' " . checked($product_state, true, false)  . ">";
                echo '</td><td>';
                    echo $product->post_title;
                echo '</td></tr>';
            endforeach;
            echo '</table>';
        else:
            echo 'No Products Found.';
        endif;
    }

    /**
     * Legal Information Meta Box
     *
     * "terms and conditions",
     *
     * "privacy policy" and
     * "organizer terms and conditions"
     */
    function legal_information_box_cb($post){
        $legal_information = get_post_meta($post->ID, 'sigma_event_legal_information', true);

        if($legal_information == ''){
            $legal_information['terms_and_conditions'] = get_home_url() . '/condiciones-de-uso/';
            $legal_information['privacy_policy'] = get_home_url() . '/politicas-de-privacidad/';
            $legal_information['organizer_terms_and_conditions'] = '';
        }

        $output = '<table style="width: 100%; max-width: 100%;" ><tr><td><label>' . __('Terms and Conditions', 'se') . '</label></td>';
        $output .= "<td><input type='text' class='regular-text' name='tandc' value='" . $legal_information['terms_and_conditions'] . "' ></td></tr>";

        $output .= '<tr><td><label>' . __('Privacy Policy', 'se') . '</label></td>';
        $output .= "<td><input type='text' class='regular-text' name='ppolicy' value='" . $legal_information['privacy_policy'] . "' ></td></tr>";

        $output .= '<tr><td><label>' . __('Organizer Terms and Conditions', 'se') . '</label></td>';
        $output .= "<td><input type='text' class='regular-text' name='otandc' value='" . $legal_information['organizer_terms_and_conditions'] . "' ></td></tr>";

        $output .= '</table>';

        echo $output;
    }

    /**
     * Registration Email Template Meta Box
     *
     * Approved and Rejected Email Template
     */
    function registration_email_template_box_cb($post){
        $email_template = get_post_meta($post->ID, 'sigma_registration_email_template', true);
        $sigma_logo = '<img src="' . SIGMA_URL . 'assets/sigma-logo.png" alt="sigma-logo" >';

        if($email_template == ''){
            $email_template['subject'] = 'Event: {{ename}} | Event ID: {{eid}}';
            $email_template['attachment'] = '';
            $resume_link = get_home_url() . '/sigma-events/payment/?sigma_token={{token}}#se-order';
            $email_template['registration'] = '<h2>Hi, {{Fname}} </h2>
            <span style="color: #000080;">Thank you for registering for Event: <b>{{ename}}</b></span>.<br />
                    <h1>Your Registration ID: <b>{{token}}</b>.</h1>
            <i><b>If you didn\'t complete your payment, <a href=' . $resume_link
            . '" >Resume Here</a></b><i><br />
            <i>Sigma Ticketing - Sigma Secure Pay<i><br />
            <i><a href="http://sigmasecurepay.info" >
            Visit Sigma Secure Pay Website</a><i><br /><br />' . $sigma_logo;
        }

        // Email Subject.
        $output = '<table style="width: 100%; max-width: 100%;" ><tr><td><label>' . __('Email Subject', 'se') . '</label></td>';
        $output .= "<td><input type='text' class='regular-text' name='reg_esubject' value='" . $email_template['subject'] . "' ></td></tr>";

        // Email Attachment Upload Button
        $output .= '<tr><td><label>' . __('Email Attachment', 'se') . '</label></td>';
        $output .= '<td><input id="reg_eattachment" type="text" class="regular-text" name="reg_eattachment"
            value="' .  esc_attr( $email_template['attachment'] ) . '" />';
        $editor_id = 'reg_eattachment';
        $post = get_post();
        wp_enqueue_media( array('post' => $post) );
        $img = '<span class="wp-media-buttons-icon"></span> ';
        $output .= ' <a href="#" class="button insert-media add_media" data-editor="' . esc_attr( $editor_id ) .
            '" id="reg_eattachment_uploader" title="' . esc_attr__( 'Upload Registration Attachment' ) . '"> ' .
            $img . __( 'Upload Registration Attachment' ) . '</a>';
        $output .= '</td></tr>';

        // Email Body Title.
        $output .= '<tr><td colspan="2" ><label><br />'
            . __('Registration Email Body. See help for available email template tags.', 'se') . '</label></td></tr>';

        // Email Body Editor.
        $output .= "<tr><td colspan='2' ><div class='se-email-body'>";
        $msg = apply_filters('the_content', $email_template['registration']);
        ob_start();
            wp_editor($msg, 'emessageregistration', array('media_buttons' => false));
        $output .= ob_get_clean();
        $output .= "</div></td></tr>";

        $output .= "</table>";
        echo $output;
    }

    /**
     * Payment Confirmation Email Template Meta Box
     *
     * Approved and Rejected Email Template
     */
    function email_template_box_cb($post){
        $email_template = get_post_meta($post->ID, 'sigma_email_template', true);
        $sigma_logo = '<img src="' . SIGMA_URL . 'assets/sigma-logo.png" alt="sigma-logo" >';

        if($email_template == ''){
            $email_template['subject'] = 'Event: {{ename}} | Event ID: {{eid}}';
            $email_template['attachment'] = '';
            $email_template['message_approved'] = '<h2>Hi, {{Fname}} </h2>
            <span style="color: #000080;">Thank you for registering for Event: <b>{{ename}}</b></span>.<br />
            <h3>Your payment has been approved</h3><br />
                    <h1>Your Registration ID: <b>{{token}}</b>.</h1>
                    <h1>Your Sequence Nunmber: <b>{{seq_no}}</b>.</h1>
            <i>Sigma Ticketing - Sigma Secure Pay<i><br />
            <i><a href="http://sigmasecurepay.info" >
            Visit Sigma Secure Pay Website</a><i><br /><br />' . $sigma_logo;
            $email_template['message_not_approved'] = 'Hi, {{fname}} <br />
                Thank you for registering for event: {{eid}}.<br />
                Unfortunately, Your payment was unsuccessful<br /><br />' . $sigma_logo;
        }

        // Email Subject.
        $output = '<table style="width: 100%; max-width: 100%;" ><tr><td><label>' . __('Email Subject', 'se') . '</label></td>';
        $output .= "<td><input type='text' class='regular-text' name='esubject' value='" . $email_template['subject'] . "' ></td></tr>";

        // Email Attachment Upload Button
        $output .= '<tr><td><label>' . __('Email Attachment', 'se') . '</label></td>';
        $output .= '<td><input id="eattachment" type="text" class="regular-text" name="eattachment"
            value="' .  esc_attr( $email_template['attachment'] ) . '" />';
        $editor_id = 'eattachment';
        $post = get_post();
        wp_enqueue_media( array('post' => $post) );
        $img = '<span class="wp-media-buttons-icon"></span> ';
        $output .= ' <a href="#" class="button insert-media add_media" data-editor="' . esc_attr( $editor_id ) .
            '" id="eattachment_uploader" title="' . esc_attr__( 'Upload Attachment' ) . '"> ' .
            $img . __( 'Upload Attachment' ) . '</a>';
        $output .= '</td></tr>';

        // Approved Email Body Title.
        $output .= '<tr><td colspan="2" ><label><br />'
            . __('Payment Approved Email Body. See help for available email template tags.', 'se') . '</label></td></tr>';

        // Email Body Editor.
        $output .= "<tr><td colspan='2' ><div class='se-email-body'>";
        $msg = apply_filters('the_content', $email_template['message_approved']);
        ob_start();
            wp_editor($msg, 'emessageapproved', array('media_buttons' => false));
        $output .= ob_get_clean();
        $output .= "</div></td></tr>";

        // Not Approved Email Body Title.
        $output .= '<tr><td colspan="2" ><label><br />'
            . __('Payment Not Approved Email Body. See help for available email template tags.', 'se') . '</label></td></tr>';

        // Email Body Editor.
        $output .= "<tr><td colspan='2' ><div class='se-email-body'>";
        $msg = apply_filters('the_content', $email_template['message_not_approved']);
        ob_start();
            wp_editor($msg, 'emessagenapproved', array('media_buttons' => false));
        $output .= ob_get_clean();
        $output .= "</div></td></tr>";

        $output .= "</table>";
        echo $output;
    }

    /**
     * Payment Page Banner
     *
     * Tourist Agent related Advertisement
     */
    function payment_banner_box_cb($post){
        $payment_banner = get_post_meta($post->ID, 'sigma_payment_banner', true);

        $organizer = get_post_meta($post->ID, 'sigma_event_organizer', true);
        $sample_banner = SIGMA_URL . 'assets/sigma-sample-image.jpg';

        if($organizer == '') $organizer['mail'] = '';

        // New Event. Defaults.
        if( !isset($payment_banner['agent_email']) ):
            $payment_banner['agent_email'] = $organizer['mail'];
            $payment_banner['banner_image'] = '';
        endif;

        // Prepare preview image source. Use sample image if none set.
        $preview_banner = isset($payment_banner['banner_image'])
            && '' != $payment_banner['banner_image']
            ?        $payment_banner['banner_image']
            :        $sample_banner;

        $output = '<table style="width: 100%; max-width: 100%;" >';

        $output .= '<tr><td><label>' . __('Agent Email', 'se') . '</label></td>';
        $output .= "<td><input type='text' class='regular-text' name='payment_banner_email' value='"
            . $payment_banner['agent_email'] . "' ></td></tr>";

        $output .= '<tr><td><label>' . __('Banner', 'se') . '</label></td>';
        $output .= '<td><input id="payment_banner" type="text" class="regular-text" name="payment_banner_image"
            value="' .  esc_attr( $payment_banner['banner_image'] ) . '" />';

        // Payment Banner Upload Button
        // media.php:385
        $editor_id = 'payment_banner';
        $post = get_post();
        wp_enqueue_media( array('post' => $post) );
        $img = '<span class="wp-media-buttons-icon"></span> ';
        $output .= ' <a href="#" class="button insert-media add_media" data-editor="' . esc_attr( $editor_id ) . '" id="payment_banner_uploader" title="' . esc_attr__( 'Add Media' ) . '"> ' . $img . __( 'Upload Banner' ) . '</a>';

        $output .= '</td></tr>';

        $output .= '<tr><td colspan="2">';
        $output .= '<img id="payment-banner-preview" class="sigma-image-preview" src="' . $preview_banner . '" >';
        $output .= '</td>';

        $output .= '</tr></table>';

        echo $output;
    }

    /**
     * Save Meta Boxes
     *
     * Save all meta data related to the event.
     * Do a nonce check. Don't execute on autosave.
     */
    function save_meta_boxes($post_id){
        // Return on autosave.
        if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
            return;

        // Nonce check.
        if(!isset($_POST['sigma_event_meta_data']) || !wp_verify_nonce($_POST['sigma_event_meta_data'], 'sigma_event_meta_save'))
            return;

        // Permission check.
        if('events' != $_POST['post_type'] || !current_user_can('edit_post', $post_id ))
            return;

        // Collect Basic Event Information
        $event_details['header_image']      =  sanitize_text_field($_POST['event_header_image']);
        $event_details['description']       = wp_kses($_POST['event_description'], wp_kses_allowed_html( 'post' ));

        // Collect organizer information.
        $organizer['name']                  = sanitize_text_field($_POST['oname']);
		$organizer['eventcode']			=sanitize_text_field($_POST['eventcode']);
        $organizer['mail']                  = sanitize_email($_POST['omail']);
        $organizer['salesperson']           = sanitize_email($_POST['osalemail']);
        $organizer['url']                   = esc_url($_POST['ourl']);
        $organizer['logo']                  = esc_url($_POST['ologo']);
        $organizer['question']              = wp_kses($_POST['question'], wp_kses_allowed_html( 'post' ));
        $organizer['answer']                = wp_kses($_POST['answer'], wp_kses_allowed_html( 'post' ));
        $organizer['above_disclimer_text']  = wp_kses($_POST['above_disclimer_text'], wp_kses_allowed_html( 'post' ));

        // Collect event period data.
        $period['start']    = strtotime(sanitize_text_field($_POST['pstart']));
        $period['end']      = strtotime(sanitize_text_field($_POST['pend']));
		
		  // Collect event age range data.
        $age['min']      = sanitize_text_field($_POST['amin']);
        $age['max']      = sanitize_text_field($_POST['amax']);

        // Collect price and conversion rate.
        $price['local']     = (float) sanitize_text_field($_POST['localp']);
        $price['foreign']   = (float) sanitize_text_field($_POST['foreignp']);
        $price['rate']      = (float) sanitize_text_field($_POST['ratep']);

        // Collect legal information urls.
        $legal_information['terms_and_conditions']              = esc_url($_POST['tandc']);
        $legal_information['privacy_policy']                    = esc_url($_POST['ppolicy']);
        $legal_information['organizer_terms_and_conditions']    = esc_url($_POST['otandc']);

        // Collect registration email template formatting information.
        $reg_email_template['subject']              = sanitize_text_field($_POST['reg_esubject']);
        $reg_email_template['attachment']           = sanitize_text_field($_POST['reg_eattachment']);
        $reg_email_template['registration']         = wp_kses($_POST['emessageregistration'], wp_kses_allowed_html( 'post' ));

        // Collect payment confirmation email template formatting information.
        $email_template['subject']              = sanitize_text_field($_POST['esubject']);
        $email_template['attachment']           = sanitize_text_field($_POST['eattachment']);
        $email_template['message_approved']     = wp_kses($_POST['emessageapproved'], wp_kses_allowed_html( 'post' ));
        $email_template['message_not_approved'] = wp_kses($_POST['emessagenapproved'], wp_kses_allowed_html( 'post' ));

        // Collect payment processor details
        $processor['local']                = sanitize_text_field($_POST['localprocessor']);
        $processor['local_decidir']        = isset($_POST['localprocessordecidir']) ? true : false;
        $processor['local_dineromail']     = isset($_POST['localprocessordineromail']) ? true : false;
        $processor['local_salesperson']    = isset($_POST['localprocessorsalesperson']) ? true : false;
        $processor['local_cuentadigital']  = isset($_POST['localprocessorcuentadigital']) ? true : false;
        $processor['local_ep']             = isset($_POST['localprocessorep']) ? true : false;
        $processor['local_paypal']         = isset($_POST['localprocessorpaypal']) ? true : false;

        $processor['foreign']                = sanitize_text_field($_POST['foreignprocessor']);
        $processor['foreign_decidir']        = isset($_POST['foreignprocessordecidir']) ? true : false;
        $processor['foreign_dineromail']     = isset($_POST['foreignprocessordineromail']) ? true : false;
        $processor['foreign_salesperson']    = isset($_POST['foreignprocessorsalesperson']) ? true : false;
        $processor['foreign_cuentadigital']  = isset($_POST['foreignprocessorcuentadigital']) ? true : false;
        $processor['foreign_ep']             = isset($_POST['foreignprocessorep']) ? true : false;
        $processor['foreign_paypal']         = isset($_POST['foreignprocessorpaypal']) ? true : false;

        // Collect sequence details
        $sequence['sequence']                  = sanitize_text_field($_POST['sequence']);
        $sequence['enable_number_reservation'] = isset($_POST['enable_number_reservation']) ? true : false;
        $sequence['local_reservation_fee']     = sanitize_text_field($_POST['local_reservation_fee']);
        $sequence['foreign_reservation_fee']   = sanitize_text_field($_POST['foreign_reservation_fee']);

        // Discount Codes
        $codes['enable_discount_codes']        = isset($_POST['enable_discount_codes']) ? true : false;

        // Payment Banner
        $payment_banner['agent_email']         = sanitize_text_field($_POST['payment_banner_email']);
        $payment_banner['banner_image']        = esc_url($_POST['payment_banner_image']);

        // Save additional product data.
        $query = get_posts('post_type=products');
        foreach($query as $product):
            $product_id             = $product->ID;
            $product_name           = 'product_' . $product_id ;
            $product_state          = isset($_POST[$product_name]) ? true : false ;
            $products[$product_id]  = $product_state;
        endforeach;
        if(!isset($products)):
            $products = '';
        endif;

        // Update event meta data.
        update_post_meta($post_id, 'sigma_event_details',               $event_details      );
        update_post_meta($post_id, 'sigma_event_organizer',             $organizer          );
        update_post_meta($post_id, 'sigma_event_period',                $period             );		
        update_post_meta($post_id, 'sigma_event_age', 	                $age                );
        update_post_meta($post_id, 'sigma_event_price',                 $price              );
        update_post_meta($post_id, 'sigma_event_processor',             $processor          );
        update_post_meta($post_id, 'sigma_event_sequence',              $sequence           );
        update_post_meta($post_id, 'sigma_event_codes',                 $codes              );
        update_post_meta($post_id, 'sigma_event_products',              $products           );
        update_post_meta($post_id, 'sigma_event_legal_information',     $legal_information  );
        update_post_meta($post_id, 'sigma_email_template',              $email_template     );
        update_post_meta($post_id, 'sigma_registration_email_template', $reg_email_template );
        update_post_meta($post_id, 'sigma_payment_banner',              $payment_banner     );
    }

    /**
     * Redirect Event Pages
     *
     * Usually themes serve different templates for cpts.
     * Events post types should be served with a custom template
     * with custom format for sigma events.
     */
    function redirect_events_template(){
        if('events' == get_post_type()) :
            add_action('wp_enqueue_scripts', array($this, 'enqueue'));
            include SIGMA_PATH . 'templates/sigma-events-template.php';
            die();
        endif;
    }

    /**
     * Sigma Rewrite
     *
     * Define an endpoint to submit the registration form.
     * Add a rewrite tag to save the query variable.
     */
    function sigma_rewrite(){
        $registration_regex     = $this->endpoint . '/registration/?$';
        $payment_regex          = $this->endpoint . '/payment/?$';
        $checkout               = $this->endpoint . '/checkout/?$';

        add_rewrite_rule($registration_regex,   'index.php?' . $this->tag . '=registration',    'top');
        add_rewrite_rule($payment_regex,        'index.php?' . $this->tag . '=payment',         'top');
        add_rewrite_rule($checkout,             'index.php?' . $this->tag . '=checkout',        'top');

        add_rewrite_tag('%' . $this->tag . '%', '([^&]+)');
    }

    /**
     * Sigma Endpoint Redirect
     *
     * Check for endpoint requests.
     *  1. Registration
     *  2. Payment
     *  3. Checkout
     */
    function redirect_endpoint_requests(){
        global $wp_query, $sigma_events;
        /**
         * Does Sigma Tag Present?
         */
        if(!isset($wp_query->query_vars[$this->tag]))
            return;

        /**
         * Enqueue Sigma specific scripts and styles.
         */
        add_action('wp_enqueue_scripts', array($this, 'enqueue'));

        /**
         * Where to redirect?
         */
        $query_var = $wp_query->query_vars[$this->tag];

        /*
         ♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎
         STEP 1 - Registration
         ☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯
         */
        if($query_var == 'registration'):
            $sigma_events->security->check_visitor('registration');
            $token = $this->process_registration($this->registration_table);
            if($token):
                // Construct the payment location URL.
                $payment_location = get_home_url() . '/' . $this->endpoint . '/payment?sigma_token=' . $token . '#se-order';
                // Redirect to the payment location.
                $location = 'Location: ' . $payment_location;
                header($location);
                exit;
            endif;

        /*
         ♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎
         STEP 2 - Additional Product Selection ( Exit to Processors )
         ☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯
         */
        elseif($query_var == 'payment' && isset($_GET['sigma_token'])):
            $registration = $this->redirect_to_tracker($_GET['sigma_token']);
            $registration = $this->clear_additional_products( $registration );
            $this->update_registration_record($registration);
            $event_data = $this->get_event_data($registration);
            $event_data = array_merge($event_data, $registration);
            include  SIGMA_PATH . 'templates/sigma-payments-template.php';

        /*
         ♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎♎
         STEP 3 - Details of the Transaction. Exit to Processors
         ☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯☯
         */
        elseif($query_var == 'checkout' && isset($_POST['token'])):
            $sigma_events->security->check_visitor('confirmation');
            $event_data = $this->process_confirmation_page_request($_POST['token']);
            include  SIGMA_PATH . 'templates/sigma-confirmation-template.php';
        endif;
    }

    /**
     * Process Registration Data
     *
     * After submitting event registration form, db update is
     * handled in this method.
     *
     * @param   string  $table_name Registration table name
     * @return  string  Token for the registration record. Death if unable to
     *                  to insert new db record.
     */
    function process_registration( $table_name ){
        global $sigma_events;
        // A call without POSTing data? (This is a dummy field. But required.)
        if(!isset($_POST['firstname'])) :
            wp_die( __( 'Reg: Cheatin&#8217; uh?' ) );
        // Dummy field for robots is filled?
        elseif($_POST['firstname'] != '') :
            wp_die( __( 'Fil: Cheatin&#8217; uh?' ) );
        // Can nonce be verified?
        elseif(!isset($_POST['sigma-registration-form-data'])
            || !wp_verify_nonce($_POST['sigma-registration-form-data'], 'sigma-registration-form-action')) :
            wp_die( __( 'Non: Cheatin&#8217; uh?' ) );
        // Valid POSTing.
        else :
			$eventcode=  isset($_POST['eventcode'])            ? sanitize_text_field($_POST['eventcode'])    : 'none';
            $fname       = isset($_POST['fname'])            ? sanitize_text_field($_POST['fname'])    : '';
            $lname       = isset($_POST['lname'])            ? sanitize_text_field($_POST['lname'])    : '';

            $fname       = str_replace('\\', '', $fname);
            $lname       = str_replace('\\', '', $lname);

            $eid         = isset($_POST['event_id'])         ? sanitize_text_field($_POST['event_id']) : '';

            $argentinian = isset($_POST['argentinian'])      ? true  : false;
            $argentinian = isset($_POST['nonargentinian'])   ? false : true;
            $country     = isset($_POST['nonargentinian'])
                                && isset($_POST['country'])
                                && $_POST['country'] != ''   ? sanitize_text_field($_POST['country']) : '';

            $ar_resident = isset($_POST['ar_resident'])      ? true : false;
            if($ar_resident) $argentinian = true;

            $dni         = isset($_POST['dni'])              ? sanitize_text_field($_POST['dni'])     : '';

            $email       = isset($_POST['email'])
                                && is_email($_POST['email']) ? sanitize_email($_POST['email'])        : '';

            $gender      = isset($_POST['gender'])           ? sanitize_text_field($_POST['gender'])          : '';
			
			
			 $day=isset($_POST['day'])  ? sanitize_text_field($_POST['day'])          : '';
			$month         =isset($_POST['month'])  ? sanitize_text_field($_POST['month'])          : '';
			$year         =isset($_POST['year'])  ? sanitize_text_field($_POST['year'])          : '';
			$bday=$year.'-'.$month.'-'.$day;
		
		
		
          //  $bday        = isset($_POST['bday'])             ? sanitize_text_field($_POST['bday'])            : '';
            $phone       = isset($_POST['phone'])            ? sanitize_text_field($_POST['phone'])           : '';
            $addr        = isset($_POST['addr'])             ? sanitize_text_field($_POST['addr'])            : '';
            $club        = isset($_POST['club'])             ? sanitize_text_field($_POST['club'])            : '';
            $number      = isset($_POST['selected_number'])  ? sanitize_text_field($_POST['selected_number']) : '';
            $ans         = isset($_POST['answer'])           ? wp_kses($_POST['answer'],
                                                                wp_kses_allowed_html( 'post' ))               : '';
            $dcode       = isset($_POST['dcode'])            ? sanitize_text_field($_POST['dcode'])           : '';

            // Check if required fields are blank
            if($fname == '' || $lname == '' || $dni == '' || $email == '' ||
               $gender == '' )
                die('Some of the required fields (First Name, Last Name, DNI, Email, Gender, Birthday) are missing or invalid!');

            /**
             * Check for correct birth day format
             */
            $year_margin = 5;
            if(!(preg_match('/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/', $bday, $matches)
                && $matches[0] < date('Y') - $year_margin)){
                die(' Enter your birthday "YYYY-MM-DD" format. ');
            }

            /**
             * Check whether the number has already been assigned.
             * Yes, true, we have checked it on the registration page,
             * (via ajax)
             * but, who knows how much time the registrant had been waiting
             * on that page before arrived here.
             *
             * If the number has already been assigned to another quick registrant
             * we are in trouble. So check it and return redirect if
             * the need be!
             */
            if( 0 < $number && $sigma_events->sequences->is_number_not_available( $number, $eid ) ):
                $link = get_permalink( $eid ) . '?status=number_not_available#se-number-selection-anchor';
                wp_redirect( $link );
                exit;
            endif;

            // Normalize Strings
            setlocale(LC_CTYPE, 'en_US.UTF-8');
            $f_fname = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $fname);
            $f_lname = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $lname);

	        $f_fname = preg_replace( '/[^a-z]/', '', strtolower($f_fname) );
	        $f_lname = preg_replace( '/[^a-z]/', '', strtolower($f_lname) );

            //DEPRECATED TOKEN STYLE
			// Generate a unique token				            // length = 10
          /*  $token  = substr(strtolower(trim($f_fname)), 0, 3) 	// first 3 letters of first name
                . substr(strtolower(trim($f_lname)), 0, 3) 	    // first 3 letters of last name
                . rand(1000, 9999);				                // 4 digit random number
			*/
			
			//Generate Token: first 2 digits of event code (as set in event edit page) + dni number
			$eventcode=substr(strtoupper(trim($eventcode)), 0, 2);
				$token=$eventcode.$dni;
				
				

            // Get Price Meta Information
            $price = get_post_meta($eid, 'sigma_event_price', true);
            $rate = $price['rate'];
            if($argentinian):
                $price = $price['local'];
            else:
                $price = $price['foreign'] * $rate;
            endif;

            /**
             * Apply Discount, if possible
             */
            $disc_code = '';
            if('' != $dcode):
                $r = $sigma_events->codes->apply_discount($price, $dcode, $eid);
                if($r):
                    $price     = $r['discounted_price'];
                    $club      = $r['team_name'];
                    $disc_code = $r['disc_code'];
                endif;
            endif;

            if( 0 < $number ):
                $r = $sigma_events->sequences->reserve_number( $number, $eid, $token );
                if( ! $r ) wp_die( 'number reservation failed' );

                // Store the reserved number as (-)reserved_number e.g. -1234
                $seq_no = '-' . $number;

                // Add the reservation price too.
                $sequence = get_post_meta($eid, 'sigma_event_sequence', true);
                if($argentinian):
                    $reservation_fee = $sequence['local_reservation_fee'];
                else:
                    $reservation_fee = $sequence['foreign_reservation_fee'] * $rate;
                endif;
                $price = $price + $reservation_fee;
            else:
                $seq_no = 0;
            endif;
			

            $data['id']             = null;
            $data['token']          = $token;
            $data['reg_time']       = current_time('mysql');
            $data['eid']            = $eid;
            $data['fname']          = $fname;
            $data['lname']          = $lname;
            $data['argentinian']    = $argentinian;
            $data['country']        = '' == $country ? 'ar' : $country;
            $data['dni']            = $dni;
            $data['email']          = $email;
            $data['gender']         = $gender;
            $data['bday']           = $bday;
            $data['phone']          = '' == $phone       ? 'none' : $phone;
            $data['addr']           = '' == $addr        ? 'none' : $addr;
            $data['club']           = '' == $club        ? 'none' : $club;
            $data['disc_code']      = '' == $disc_code   ? 'none' : $disc_code;
            $data['ans']            = '' == $ans         ? 'none' : $ans;
            $data['extra_items']    = 'none';
            $data['medium']         = 'none';
            $data['payment']        = 'none';
            $data['paid']           = 'notpaid';
            $data['amount']         = $price * 100;
            $data['rate']           = $rate;
            $data['ip']             = $_SERVER['REMOTE_ADDR'];
            $data['seq_no']         = $seq_no;

           
			global $wpdb;
        
		//Recheck if DNI alredy exist in DB for this event. Note that event if it was done before, POSTED data would be tweaked and duplicated token will be created.
		$table     = $wpdb->prefix . 'sigma_events';
        $where_1   = "'". $dni."'";
		$where_2   = "'". $eid."'";
        $check = $wpdb->get_results(
            "
            SELECT *
            FROM $table
            WHERE dni = $where_1 AND eid = $where_2
            ", ARRAY_A 
        );
		
		$cnt=count($check); //If there was a match this will be greater than zero - This line seems useless
		
		$table_name = $wpdb->prefix . $table_name;
       if(!$check){ 
            $r = $wpdb->insert( $table_name, $data);
            if(!$r):
                die('Database Error!');
            else:
                $this->send_registration_email( $data );
                return $token;
            endif;
	   }
	   else{$redirect = get_home_url() . '/sigma-events/tracker/?sigma_token=' .$token;
            wp_redirect($redirect);
			}
	   
	   endif;
	  
    }

    /**
     * Redirect to Tracker page, if already paid
     *
     * Original Query: Additional Product Selection Page.
     *
     * Gets the registration data for a token.
     * Dies if no record found for the token.
     * Redirects to the traker page if already paid.
     *
     * @uses    get_registration_record
     *
     * @param   string      $token  Token of the registration
     * @return  array|void  registration data. death or redirect.
     */
    function redirect_to_tracker( $token ){
        // Get Registration Record
        $token = sanitize_text_field( $token );
        $registration = $this->get_registration_record( $this->registration_table, $token );
        if(!$registration)
            wp_die( __( 'No registration record for the token: Cheatin&#8217; uh?' ) );

        // Redirect to tracker page for already paid tokens.
        if($registration['paid'] == 'paid'):
            $redirect = get_home_url() . '/sigma-events/tracker/?sigma_token=' .  $registration['token'];
            wp_redirect($redirect);
            exit;
        endif;

        return $registration;
    }

    /**
     * Retrieve the Registration Record for a Token
     *
     * After receiving a POST the token is recognized. Then the token
     * is used to retrieve the registration record. Registration record
     * contains the information about the event registration.
     *
     * @param   string  $registration_table Registration Table Name
     * @param   string  $token              Registration Token
     *
     * @return  array|false Registration Record for the Token or false if
     *                      unable to retrieve the record.
     */
    function get_registration_record( $registration_table, $token ){
        global $wpdb;
        $table_name     = $wpdb->prefix . $registration_table;
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
     * Clear Additional Products in a Registration Record
     *
     * Extra Items = 'none'
     * Amount = Amount - Extra Items Price
     *
     * @param   array $registration Registration record
     * @return  array Additional Products cleared registration record
     */
    function clear_additional_products( $registration ){
        if( 'none' != $registration['extra_items']):
            $extra_items = unserialize($registration['extra_items']);
            $products_total_price = 0;
            foreach($extra_items as $extra_item):
                if($registration['argentinian']):
                    $products_total_price = $products_total_price + $extra_item[1] * 100;
                else:
                    $products_total_price = $products_total_price + $extra_item[1] * $registration['rate'] * 100;
                endif;
            endforeach;

            $registration['extra_items'] = 'none';
            $registration['medium']      = 'none';
            $registration['amount']      = $registration['amount'] - $products_total_price;
        endif;
        return $registration;
    }

    /**
     * Get Event Data
     *
     * Returns a complete event information array for a given registration record
     * Price, Organizer, Payment Processor, Products, etc.
     *
     * @param   array $registration   Registration record
     * @return  array Complete Event Information Array
     */
    function get_event_data( $registration ){
        // Get the event details.
        $event_args = array(
            'post_type' => 'events',
            'p'         => $registration['eid']
        );
        $event_query  = new WP_Query($event_args);
        if(!$event_query->post_count):
            $event_query = $this->get_event_query($registration);
            $registration['eid'] = $event_query->post->ID;
        endif;

        $event_id     = $registration['eid'];
        $event_title_ = str_replace(' ', '_', strtolower( $event_query->post->post_title ) );
        $greeting     = __('Hi, ', 'se') . $registration['fname']
            . '. '. __('<span class="se-token-string" >Your Booking ID (token) is: ', 'se')
			. ' </span>'
			. '<span class="se-token-string-code" >'
            . $registration['token'] . ' </span>';

        // Retrieve the 'Event Period' Information
        $period = get_post_meta($event_id, 'sigma_event_period', true);
		
		// Retrieve the 'Event Age Range' Information
        $age = get_post_meta($event_id, 'sigma_event_age', true);

        /**
         * Event Price.
         *
         * Earlier,
         * $price = get_post_meta($event_id, 'sigma_event_price', true);
         */
        $price = array(
            'local'   => ($registration['amount'] ) / 100,
            'foreign' => ($registration['amount'] ) / $registration['rate'] / 100,
            'rate'    => $registration['rate']
        );

        // Event banner.
        $banner = get_post_meta($event_id, 'sigma_payment_banner', true);
        if( isset($banner['banner_image']) && '' != $banner['banner_image'] ):
            $banner = $banner['banner_image'];
        else:
            $banner = false;
        endif;

        // Number Reservations
        $reserved = 0 > $registration['seq_no'] ? true : false;
        if( $reserved ):
            $sequence = get_post_meta($event_id, 'sigma_event_sequence', true);
            if($registration['argentinian']):
                $reservation_fee = $sequence['local_reservation_fee'];
            else:
                $reservation_fee = $sequence['foreign_reservation_fee'];
            endif;
        else:
            $reservation_fee = 0;
        endif;

        if($registration['argentinian']):
            $event_price = $price['local'];
            if($event_price > 0):
                $price_string  =  __('Event Price: ', 'se') . $event_price . ' ARS';
                $price_string .= $reserved
                    ? __('<span class="se-reservation-fee">  + Number Reservation Fee: ', 'se')
                    . $reservation_fee . ' ARS</span>' : '';
            else:
                $price_string  =  __('Event Price: ', 'se') . __('free', 'se');
            endif;
            $event_price = $price['local'] + $reservation_fee;
        else:
            $event_price = $price['foreign'];
            if($event_price > 0):
                $price_string  =  __('Event Price: ', 'se') . $event_price . ' USD';
                $price_string .= $reserved
                    ? __('<span class="se-reservation-fee">  + Number Reservation Fee: ', 'se')
                    . $reservation_fee . ' USD</span>' : '';
            else:
                $price_string  =  __('Event Price: ', 'se') . __('free', 'se');
            endif;
            $event_price = ( $price['foreign'] + $reservation_fee ) * $price['rate'];
        endif;

        $processor_meta = get_post_meta($event_id, 'sigma_event_processor', true);
        if($registration['argentinian']):
            $processor                = $processor_meta['local'];
            $freedom['decidir']       = $processor_meta['local_decidir'];
            $freedom['dineromail']    = $processor_meta['local_dineromail'];
            $freedom['salesperson']   = $processor_meta['local_salesperson'];
            $freedom['cuentadigital'] = $processor_meta['local_cuentadigital'];
            $freedom['ep']            = $processor_meta['local_ep'];
        else:
            $processor                = $processor_meta['foreign'];
            $freedom['decidir']       = $processor_meta['foreign_decidir'];
            $freedom['dineromail']    = $processor_meta['foreign_dineromail'];
            $freedom['salesperson']   = $processor_meta['foreign_salesperson'];
            $freedom['cuentadigital'] = $processor_meta['foreign_cuentadigital'];
            $freedom['ep']            = $processor_meta['foreign_ep'];
        endif;

        // Organizer Details
        $organizer = get_post_meta($event_id, 'sigma_event_organizer', true);

        // Event Header Image.
        $details      = get_post_meta($event_id, 'sigma_event_details', true);
        $header_image = '<img src="' . $details['header_image'] . '" >';

        // Concepto to be used in CuentaDigital form
        $concepto = 'Token ID: ' . $registration['token'] . '(' . $registration['lname'] . ') - '
            . substr($event_query->post->post_title, 0, 12);

        $event_data = array(
            'title'     => $event_query->post->post_title,
            'title_'    => $event_title_,
            'thumbnail' => $header_image,
            'greeting'  => $greeting,
            'content'   => $event_query->post->post_content,
            'period'    => $period,
            'products'  => $this->get_product_data( $event_id, $registration ),
            'processor' => $processor,
            'freedom'   => $freedom,
            'price'     => array(
                'value'     => $event_price * 100,
                'string'    => $price_string,
                'rate'      => $price['rate']
            ),
            'organizer' => array(
                'logo'      => $organizer['logo'],
                'name'      => $organizer['name'],
                'url'       => $organizer['url'],
                'question'  => $organizer['question']
            ),
            'banner'        => $banner,
            'concepto'      => $concepto
        );
        return $event_data;
    }

    /**
     * Get Event Query for tourist agent modified records
     */
    function get_event_query($registration){
        /**
         * Get all the t-Events
         */
        $tevents = get_posts('post_type=tevents&numberposts=-1');

        /**
         * Find the Event ID
         */
        foreach($tevents as $tevent):
            $meta = get_post_meta($tevent->ID, 'sigma_tevent_meta', true);
            if($registration['eid'] == $tevent->ID):
                // Get the event details.
                $event_args = array(
                    'post_type' => 'events',
                    'p'         => $meta['event']
                );
                $event_query  = new WP_Query($event_args);
                return $event_query;
            endif;
        endforeach;
        wp_die(__('No Event Assoicated with your Token', 'se'));
    }

    /**
     * Process Confirmation Page Request
     *
     * Process GET or POST request to the checkout endpoint. Prepare table rows
     * for the additional products.
     *
     * @return array Event data. Complete Event related information bundle
     */
    function process_confirmation_page_request($token){
        /**
         * Sigma Registration Token
         */
        $token = sanitize_text_field($token);
        if( '' == $token )
            wp_die( __( 'Tok: Cheatin&#8217; uh?' ) );

        /**
         * Get the registration record for the token
         */
        $registration = $this->get_registration_record( $this->registration_table, $token );
        if(!$registration)
            wp_die( __( 'No registration record for the token: Cheatin&#8217; uh?' ) );

        /**
         * Redirect to tracker page for already paid tokens.
         */
        if($registration['paid'] == 'paid'):
            $redirect = get_home_url() . '/sigma-events/tracker/?sigma_token=' .  $registration['token'];
            wp_redirect($redirect);
        endif;

        $posted_data = $this->confirmation_post_data($registration);
        $registration = $this->clear_additional_products($registration);
        $registration['medium'] = $posted_data['medium'];
        $registration['extra_items'] = 'none' == $posted_data['extra_items']
            ? 'none'
            : serialize($posted_data['extra_items']);
        $registration['amount'] = $registration['amount'] + $posted_data['products_total'];
        $this->update_registration_record($registration);

        /**
         * Send Salesperson Email if needed
         */
        if( 'salesperson' == $posted_data['medium'] ):
            $this->send_salesperson_email($registration);
        endif;

        /**
         * Send Tourist Agent Email if needed
         */
        if( 'yes' == $posted_data['tourist_info'] ):
            $this->send_tourist_agent_email($registration);
        endif;

        /**
         * Get Event Data
         */
        $event_data = $this->get_event_data($registration);
        $extra_data = array_merge($registration, $posted_data);
        $event_data = array_merge($event_data, $extra_data);
        return $event_data;
    }

    /**
     * Confirmation Page( GET ) Data Array
     *
     * If the confirmation page is requested by GET, retrieve the previous
     * record and prepare strings.
     *
     * Returns an array of data related to the GET. Dies if errors present.
     *
     * @return array Data processed based on GOT variables
     */
    function confirmation_get_data( $registration ){
        $products_total_price  = 0;
        $product_rows          = '';

        /**
         * Prepare Additional Products Related Data
         */
        if( 'none' != $registration['extra_items']):
            /**
             * Unserialize extra items array.
             */
            $registration['extra_items'] = unserialize($registration['extra_items']);

            /**
             * Iterate over the extra items and prepare table rows and totals
             */
            foreach($registration['extra_items'] as $item):
                /**
                 * $total_price => total price to be used in the form
                 * format: ARS*100
                 */
                if($registration['argentinian']):
                    $price = $item[1];
                    $product_rows .= '<tr><td>' . $item[2] . ' : </td><td>' . $item[1] . ' ARS</td></tr>';
                    $products_total_price = $products_total_price + $item[1] * 100;
                else:
                    $price = $item[1];
                    $product_rows .= '<tr><td>' . $item[2] . ' : </td><td>' . $item[1] . ' USD</td></tr>';
                    $products_total_price = $products_total_price + $item[1] * $registration['rate'] * 100;
                endif;
            endforeach;
        endif;

        $output = array(
            'medium'            => $registration['medium'],
            'extra_items'       => $registration['extra_items'],
            'products_rows'     => $product_rows,
            'products_total'    => $products_total_price,
            'tourist_info'      => ''
        );
        return $output;
    }

    /**
     * Confirmation Page( POST ) Data Array
     *
     * If the confirmation page is requested by POST, process additional
     * products data and medium of payment.
     *
     * Returns an array of data related to the POST. Dies if errors present.
     *
     * @return array Data processed based on POSTed variables
     */
    function confirmation_post_data($registration){
        // (1) Can nonce be verified?
        if(!isset($_POST['se-payment-options-data'])
            || !wp_verify_nonce($_POST['se-payment-options-data'], 'se-payment-options-action'))
            wp_die( __( 'Non: Cheatin&#8217; uh?' ) );

        // (2) Check the Payment Processor.
        $payment_processor = isset($_POST['payment_processor']) ? sanitize_text_field($_POST['payment_processor']) : '';
        if( '' == $payment_processor )
            wp_die( __( 'Invalid Processor: Cheatin&#8217; uh?' ) );

        /**
         * (3) Grab the payment method.
         *
         *   (i)
         * - decidir_visa
         * - decidir_amex
         * - decidir_mastercard
         *
         *   (ii)
         * - dineromail_credit_cards
         * - dineromail_cash
         *
         *   (iii)
         * - salesperson
         *
         *   (iv)
         * - EasyPlanners
         *
         *   (v)
         * - CuentaDigital
         *
         *   (vi)
         * - Paypal
         */
        if( 'decidir' == $payment_processor ):
            $medium = isset($_POST['medio_de_pago'])     ? sanitize_text_field($_POST['medio_de_pago'])     : '';
        elseif( 'dineromail' == $payment_processor ):
            $medium = isset($_POST['dineromail_medium']) ? sanitize_text_field($_POST['dineromail_medium']) : '';
        elseif( 'salesperson' == $payment_processor ):
            $medium = 'salesperson';
        elseif( 'ep' == $payment_processor ):
            $medium = 'ep';
        elseif( 'cuentadigital' == $payment_processor ):
            $medium = 'cuentadigital';
        elseif( 'paypal' == $payment_processor ):
            $medium = 'paypal';
        endif;
        if( '' == $medium )
            wp_die( __( 'Med: Cheatin&#8217; uh?' ) );

        // (4) Tourism Email.
        $tourist_info  = isset($_POST['tourist_info']) ? sanitize_text_field($_POST['tourist_info']) : '';

        /**
         * Extra Items Related Variables: Defaults
         *
         * To be changed, if additional products've been POSTed.
         */
        $products_total_price        = 0;
        $product_rows                = '';
        $extra_items                 = 'none';

        /**
         * Process Extra Items, if they were present
         */
        if( ! empty($_POST['extra_items']) ):
            $extra_items = array();
            foreach($_POST['extra_items'] as $item):
                $product  = get_post($item);
                $price    = get_post_meta($product->ID, 'sigma_product_price', true);

                /**
                 * $products_total_price => To be used in the payment processor form
                 * format: ARS*100
                 */
                if($registration['argentinian']):
                    $price = $price['local'];
                    $product_rows .= '<tr><td>' . $product->post_title .' : </td><td>' . $price . ' ARS</td></tr>';
                    $products_total_price = $products_total_price + $price * 100;
                else:
                    $price = $price['foreign'];
                    $product_rows .= '<tr><td>' . $product->post_title . ' : </td><td>' . $price . ' USD</td></tr>';
                    $products_total_price = $products_total_price + $price * $registration['rate'] * 100;
                endif;

                /**
                 * Extra Items Registration Field is defined here
                 */
                $extra_items[] = array(
                    $product->ID,           // Product ID
                    $price,                 // Product Price
                    $product->post_title    // Product Name
                );
            endforeach;
        endif;

        $posted_data = array(
            'medium'            => $medium,
            'extra_items'       => $extra_items,
            'products_rows'     => $product_rows,
            'products_total'    => $products_total_price,
            'tourist_info'      => $tourist_info
        );
        return $posted_data;
    }

    /**
     * Send Post-Registration Email
     *
     * Send an email to all registrants based on the registration email
     * template corresponding to the event being registered.
     */
    function send_registration_email( $data ) {
        $options = get_option( 'sigma_options' );
        if($options['email_user']):
            $event                      = get_post($data['eid']);
            $sigma_logo                 = '<img src="' . SIGMA_URL . 'assets/sigma-logo.png" alt="sigma-logo" >';
            $event_logo                 = get_the_post_thumbnail($event->ID, 'thumbnail');

            // Prepare replace array to be used on preg_replace_callback.
            $replace_array              = $data;
            $replace_array['sigmalogo'] = $sigma_logo;
            $replace_array['eventlogo'] = $event_logo;
            $replace_array['ename']     = $event->post_title;
            $replace_array['econtent']  = $event->post_content;
            $replace_array['Fname']     = ucfirst(strtolower($data['fname']));
            $replace_array['Lname']     = ucfirst(strtolower($data['lname']));
            $this->replace_array        = $replace_array;

            // Get Event Meta Data.
            $event_id                   = $event->ID;
            $email_template             = get_post_meta($event_id, 'sigma_registration_email_template', true);
            $organizer                  = get_post_meta($event_id, 'sigma_event_organizer', true);

            $to             = $data['email'];
            $subject        = preg_replace_callback('!\{\{(\w+)\}\}!', array($this, 'replace_value'), $email_template['subject']);

            $message    = preg_replace_callback('!\{\{(\w+)\}\}!', array($this, 'replace_value'), $email_template['registration']);
            $message    = apply_filters('the_content', $message);

            $headers[]      = 'From: ' . $options['send_name'] . ' <' . $options['send_email'] . '>';

            $attachment     = array($email_template['attachment']);

            add_filter( 'wp_mail_content_type', array($this, 'set_html_content_type') );
            $r = wp_mail($to, $subject, $message, $headers, $attachment);
            remove_filter( 'wp_mail_content_type', array($this, 'set_html_content_type') );
            return $r;

        endif;
    }

    /**
     * Retrieve Product Data for an event
     *
     * Product should be registred for the event. Product price should be
     * non-zero for the nationality of the current registrant.
     *
     * If both of the conditions are met then extract and format the product
     * information to be used in payments page template.
     *
     * @param   int     $event_id       ID of the event the products should be get
     * @param   array   $registration   Current Registration record
     * @return  array   Array of product information for the event
     */
    function get_product_data( $event_id, $registration ){
        // Additional Product Details for this event.
        $products = get_post_meta($event_id, 'sigma_event_products', true);

        // Retrive all products available.
        $query = get_posts('post_type=products');
        foreach($query as $product):
            // Get the product id.
            $product_id = $product->ID;

            // Is this product is available for this event?
            // (Even if this is available, displaying this will depend on the price.
            // If the price is 0 this will not be displayed.)
            $product_state = isset($products[$product_id]) ? $products[$product_id] : false ;

            // Get product meta information. (Product Price)
            $price = get_post_meta($product_id, 'sigma_product_price', true);
            if($registration['argentinian']):
                $product_price_state    = (int) $price['local'] == 0 ? false : true;
                $product_price          = $price['local'];
                $product_price_tag      = 'Price (Local) : ' . $price['local'] . ' ARS';
            else:
                $product_price_state    = (int) $price['foreign'] == 0 ? false : true;
                $product_price          = $price['foreign'];
                $product_price_tag      = 'Price (Foreign) : ' . $price['foreign'] . ' USD';
            endif;

            // Product available for this event (Checked/ticked in the events edit page.
            // &&
            // Product has a price for the current type of customer. Proceed.
            if($product_state && $product_price_state):

                // Display Product Excerpt.
                // ---------------------------------------------------------
                // Is there a simple way to get the excerpt outside the loop?
                $excerpt = $product->post_content;
                $excerpt = strip_shortcodes( $excerpt );
                $excerpt = apply_filters('the_content', $excerpt);
                $excerpt = str_replace(']]>', ']]&gt;', $excerpt);
                $excerpt = strip_tags($excerpt);
                $excerpt_length = 20;
                $excerpt_more = '...';
                $words = preg_split("/[\n\r\t ]+/", $excerpt, $excerpt_length + 1, PREG_SPLIT_NO_EMPTY);
                if ( count($words) > $excerpt_length ) {
                    array_pop($words);
                    $excerpt = implode(' ', $words);
                    $excerpt = $excerpt . $excerpt_more;
                } else {
                    $excerpt = implode(' ', $words);
                }

                // Get the product id.
                $product = array(
                    'id'        => $product->ID,
                    'title'     => $product->post_title,
                    'excerpt'   => $excerpt,
                    'thumbnail' => get_the_post_thumbnail($product->ID, 'full'),
                    'link'      => get_home_url() . '/products/' . $product->post_name . '?sigma_token=' . $registration['token'],
                    'price_tag' => $product_price_tag
                );
                $product_data[] = $product;
            endif;
        endforeach;
        if( isset( $product_data )):
            return $product_data;
        else:
            return false;
        endif;
    }

    /**
     * Update Sigma Registration Record
     *
     * Update Sigma Registration Table with additional data.
     *
     * @param   string  $registration     Sigma Registration Record
     *
     * @return  boolean Result of the Registration table db update
     */
    function update_registration_record($registration){
        global $wpdb;
        $table_name                 = $wpdb->prefix . $this->registration_table;
        $where                      = array( 'token' => $registration['token'] );

        $r = $wpdb->update( $table_name, $registration, $where);
        return $r;
    }

    /**
     * Send Salesperson Email
     *
     * If the payment is to be processed through a
     * salesperson, the salesperson should be informed
     * about the registration. This method does that.
     *
     * @var     array   $registration  Registration Array
     * @return  void
     */
    function send_salesperson_email($registration){
        $organizer = get_post_meta($registration['eid'], 'sigma_event_organizer', true);
        if( isset($organizer['salesperson'] ) ):
            $options = get_option( 'sigma_options' );

            $to       = $organizer['salesperson'];
            $subject  = 'New Registration | Contact Registrant | ' . $registration['eid'];
            $message  = 'New Registration - Contact the registrant and assist them.<br />';
            $message .= '<pre>';
            $message .= print_r( $registration, true );
            $message .= '</pre>';
            $headers[]      = 'From: ' . $options['send_name'] . ' <' . $options['send_email'] . '>';

            add_filter( 'wp_mail_content_type', array($this, 'set_html_content_type') );
            $r = wp_mail($to, $subject, $message, $headers);
            remove_filter( 'wp_mail_content_type', array($this, 'set_html_content_type') );

        endif;
    }

    /**
     * Send Tourist Agent Email
     *
     * If the registrant is interested in tourist information
     * let the tourist agent know about this registration.
     *
     * @var     array   $registration  Registration Array
     * @return  void
     */
    function send_tourist_agent_email( $registration ){
        $banner = get_post_meta($registration['eid'], 'sigma_payment_banner', true);
        if( isset($banner['agent_email']) && '' != $banner['agent_email']):
            $options = get_option( 'sigma_options' );

            $to       = $banner['agent_email'];
            $subject  = 'New Registration | Contact Registrant | ' . $registration['eid'];
            $message  = 'New Registration - Registrant needs tourism related information.<br />';
            $message .= '<pre>';
            $message .= print_r( $registration, true );
            $message .= '</pre>';
            $headers[]      = 'From: ' . $options['send_name'] . ' <' . $options['send_email'] . '>';

            add_filter( 'wp_mail_content_type', array($this, 'set_html_content_type') );
            $r = wp_mail($to, $subject, $message, $headers);
            remove_filter( 'wp_mail_content_type', array($this, 'set_html_content_type') );
        else:
            $tourism_log = SIGMA_PATH . 'logs/tourism_info_opt_in_2.log';
            $data        = "\n-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+";
            $data       .= "\nTime : " . current_time('mysql');
            $data       .= "\nIP    : " . $_SERVER['REMOTE_ADDR'];
            $data       .= "\n" . print_r($registration , true);
            $data       .= "\n-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+\n";
            $r           = file_put_contents($tourism_log, $data, FILE_APPEND | LOCK_EX);
        endif;
    }

    /**
     * Replace Matched String
     *
     * Replace each of the template tags in the email templates
     * defined in the event and product edit pages.
     *
     * @param   array $matches Matched strings
     *
     * @return  string  $replacement the replacement string or false
     *                  if no replacement array defined
     */
    function replace_value( $matches ) {
        if( $this->replace_array ):
            return $this->replace_array[$matches[1]];
        else:
            return false;
        endif;
    }

    /**
     * Set Content Type
     *
     * Return HTML Content Type. HTML formatting is used for
     * admin, user and products emails.
     *
     * @return  string  content type
     */
    function set_html_content_type(){
        /* return content type */
        return 'text/html';
    }

    /**
     * Enqueue Styles and Scripts
     *
     * No need to check either for front end or for events post page,
     * This calls after checking for events
     * at template redirect
     */
    function enqueue(){
        global $post_type;
        // Events page stylesheet.
        wp_register_style('sigma-events-style', SIGMA_URL . 'css/sigma-events.css');
        wp_enqueue_style('sigma-events-style');

        // Events page javascripts.
        wp_register_script('sigma-events-script',
            SIGMA_URL . 'js/sigma-events.js', array('jquery', 'jquery-ui-datepicker'), '1.0', true);
        wp_enqueue_script('sigma-events-script');

        // Localize Javascript error strings.
        $errors = array(
          'first_name_error'    => __('Fill your first name',       'se'),
          'last_name_error'     => __('Fill your last name',        'se'),
          'dni_error'           => __('Fill your DNI',              'se'),
		  'dni_onlynum_error' => __('DNI can only be number.',    'se'),
          'birthday_error'      => __('Fill your birthdate',        'se'),
          'birthday_year_error' => __('Check the year of birth',    'se'),
          'gender_error'        => __('Select your gender',         'se'),
          'email_error'         => __('Fill your email',            'se'),
          'country_error'       => __('Select your country.',       'se'),
          'nationality_error'   => __('Select your nationality.',   'se'),
		   'phone_error'   	=> __('Fill your telephone number.','se'),
		   'address_error'       => __('Fill your full address.',    'se'),
		    'fname_onlyletternum_error' => __('First Name must be only letters and numbers.',    'se'),
	  'name_onlyletternum_error' => __('Last Name must be only letters and numbers.',    'se'),
	  'address_onlyletternum_error' => __('Full adress must be only letters and numbers without leading or trailing spaces.',    'se'),
	  'club_onlyletternum_error' => __('Running team must be only letters and numbers.',    'se'),
	  'phone_onlynum_error' => __('Telephone Number can be number only.',    'se'),
	  
          'update_record_url'   => admin_url('admin-ajax.php'),
          'current_year'        => date( 'Y', current_time('timestamp') )
        );
        wp_localize_script('sigma-events-script', 'se_errors', $errors);

        // Enqueue styles for jQuery datepicker.
        wp_enqueue_style( 'sigma-jquery-ui', 'http://code.jquery.com/ui/1.10.1/themes/base/jquery-ui.css' );
    }

    /**
     * Sigma Admin Scripts
     */
    function admin_scripts(){
        global $post_type;
        if('events' == $post_type || 'products' == $post_type){
            // Events admin page stylesheet.
            wp_register_style('sigma-events-admin-style', SIGMA_URL . 'css/sigma-events-admin.css');
            wp_enqueue_style('sigma-events-admin-style');

            // Events admin page javascripts.
            wp_register_style('sigma-events-admin-style', SIGMA_URL . 'css/sigma-events-admin.css');
            wp_register_script('sigma-events-admin-script',
              SIGMA_URL . 'js/sigma-events-admin.js', array('jquery', 'jquery-ui-datepicker'), '1.0', true);
            wp_enqueue_script('sigma-events-admin-script');
            $upload_dir = wp_upload_dir();
            $upload_base = $upload_dir['basedir'];
            $translation_array = array(
                'sigma_upload_base_dir' => $upload_base
            );
            wp_localize_script( 'sigma-events-admin-script', 'sigma_admin_vars', $translation_array );

            // Enqueue styles for jQuery datepicker.
            wp_enqueue_style( 'sigma-jquery-ui', 'http://code.jquery.com/ui/1.10.1/themes/base/jquery-ui.css' );
        }
    }

    /**
     * Sigma Events Update Messages
     *
     * Filters and modifies post update messages for Sigma Events.
     *
     * @uses 'post_updated_messages' filter
     */
    function event_updated_messages( $messages ){
        global $post;
        if( 'events' == $post->post_type ):
            $event_id = $post->ID;
            $event_name = $post->post_title;
            $event_link = get_permalink($event_id);
            $messages['post'][1] = 'Event updated. <a href="' . $event_link . '" >View Event</a>';
            $messages['post'][6] = 'Event published. <a href="' . $event_link . '" >View Event</a>';
        elseif( 'products' == $post->post_type ):
            $event_id = $post->ID;
            $event_name = $post->post_title;
            $event_link = get_permalink($event_id);
            $messages['post'][1] = 'Product updated. <a href="' . $event_link . '" >View Product</a>';
            $messages['post'][6] = 'Product published. <a href="' . $event_link . '" >View Product</a>';
        endif;
        return $messages;
    }

    /**
     * Add Sigma Events Administration Help Tabs
     *
     * Add help tabs describing options available during various
     * administrative tasks in WordPress backend.
     */
    function add_help_tabs(){
        global $post_type;
        global $action;
        if('events' == $post_type || 'products' == $post_type && 'edit' == $action ):
            include SIGMA_PATH . 'admin/help/help-events.php';
            $screen = get_current_screen();
            $screen->add_help_tab( sigma_email_template_help() );
            $screen->set_help_sidebar( sigma_events_help_sidebar() );
        endif;
    }

    /**
     * Update registration record via Ajax
     *
     * Process payment page update request just before leaving to the
     * payment processor.
     */
    function ajax_update_registration_record(){
        if(isset($_POST['token'])):
            global $sigma_events;
            $sigma_events->security->check_visitor('confirmation');
            $event_data = $this->process_confirmation_page_request($_POST['token']);
            echo 'success';
            //echo print_r($event_data, true);
        else:
            echo 'error!';
        endif;
        exit;
    }
}
endif;
?>
