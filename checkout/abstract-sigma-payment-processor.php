<?php
/**
 * Sigma Payment Processor Abstraction
 *
 * An abstract class to be extended by each of the Sigma Payment
 * processors. Provides concrete implementations of some methods
 * to be used in child Sigma Payment Processors.
 *
 * @abstract
 * @package     SigmaEvents
 * @subpackage  PaymentProcessing
 */

if ( !class_exists('Sigma_Payment_Processor') ) :
/**
 * Sigma Payment Processor Abstract Class
 *
 * Provides concrete implementations of several utility methods to be used
 * in Child Sigma Payment Processor classes.
 *
 * e.g. Database update methods, Email sending methods, Logging, etc.
 *
 * @package     SigmaEvents
 * @subpackage  PaymentProcessing
 */
abstract class Sigma_Payment_Processor
{
    /**
     * Log $_POST varialbe and send emails to admins and developers
     *
     * Logs $_POST variable to the log file if enabled in the sigma options.
     * Sends $_POST content to admins and developers as a plain text email.
     *
     * @param   array   $options    Sigma Options Wordpress Option
     * @param   boolean $enabled    Whether logging is enabled or not
     * @param   array   $data       Sigma Data Array
     *
     * @return  boolean Result of the last wp_mail() function
     */
    function log_and_send_debug_emails($options, $enabled, $data_array, $ip){
        return true;

        $data   = "\n------------------------------------------------------------------";
        $data  .= "\n" . current_time('mysql') . " " . $ip;
        $data  .= "\n" . print_r($data_array , true);
        $data  .= "------------------------------------------------------------------\n";

        if( $enabled ):
            $sigma_log = SIGMA_PATH . 'logs/' . $this->data_log;
            $r  = file_put_contents($sigma_log, $data, FILE_APPEND | LOCK_EX);
        endif;

        $title      = 'Data Received | Size ('. sizeof($data_array) . ') | From: ' . $ip ;
        $message    = "Someone Sent Data to a Sigma Payment Processor Endpoint \n $data";

        if($options['enable_post_dev_email'])
            $r = wp_mail($options['dev_email'], $title, $message);

        if($options['enable_post_admin_email'])
            $r = wp_mail($options['admin_email'], $title, $message);

        return $r;
    }

    /**
     * Wrapper for log_and_send_debug_emails()
     */
    function log_and_debug_emails( $options, $enabled ){
        $r = $this->log_and_send_debug_emails($options, $enabled, $_POST, $_SERVER['REMOTE_ADDR']);
        return $r;
    }

    /**
     * Retrive the Registration Record for a Token
     *
     * After receiving a POST the token is recognized. Then the token
     * is used to retrieve the registration record. Registration record
     * contains the information about the event registration.
     *
     * @param   string  $registration_table Registration Table Name
     * @param   string  $token              Registration Token
     *
     * @return  array   Registration Record for the Token
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
     * Retrive the Payment Record for an ID
     *
     * @param   string  $token Payment Identfier
     *
     * @return  array   Payment Record for an ID
     */
    function get_payment_record( $payment_id ){
        if('none' == $payment_id)
            return array('motivo' => '');

        global $wpdb, $sigma_events;
        $table_name     = $wpdb->prefix . $sigma_events->payment_table;
        $payments   = $wpdb->get_results(
            "
            SELECT *
            FROM $table_name
            WHERE id = $payment_id
            ", ARRAY_A
        );

        if(!$payments)
            return array('motivo' => '');

        return $payments[0];
    }


    /**
     * Retrive the Registration Records for a Token Array
     *
     * After receiving a POST the id array is recognized. Then registrations
     * related to id array are retrieved.
     *
     * @param   string  $registration_table Registration Table Name
     * @param   string  $token              Registration Token
     *
     * @return  array   Registration Record for the Token
     */
    function get_registration_records( $registration_table, $id_array ){
        global $wpdb;
        $table_name     = $wpdb->prefix . $registration_table;
        $where = ' ( token ) IN ( "' . implode( '","', $id_array ) . '" )';
        $registrations = $wpdb->get_results(
            "
            SELECT *
            FROM $table_name
            WHERE $where
            ", ARRAY_A
        );

        if(!$registrations)
            return false;

        return $registrations;
    }

    /**
     * Update Sigma Tables
     *
     * After collecting data about the POST and POSTs token
     * has a registration record associated with it,
     * Sigma database tables should be updated with the
     * transaction status received by POST.
     *
     * This method calls individual table update methods
     * sequentially.
     *
     * @param   string  $payment_table          Sigma Payment Table Name
     * @param   string  $registration_table     Sigma Registration Table Name
     * @param   array   $post                   Collected data from the POST
     * @param   array   $registration           Registration data retrived
     *                                          through the token POSTed
     *
     * @return  boolean Result of the db update operation.
     *                  If error occured, silently exits
     */
    function update_tables( $payment_table, $registration_table, $post, $registration ){
        /**
         * Update Sigma Payment Table
         */
        $r = $this->update_payment_table( $payment_table, $post );
        if( $r['result'] ):
            $registration['paid'] = $post['resultado'];
            $post['payment_id']   = $r['payment_record_id'];
        else:
            exit;
        endif;

        /**
         * Update Sigma Registration Table
         */
        $r = $this->update_sigma_table( $registration_table, $post );
        if( ! $r )
            exit;
    }

    /**
     * Update Sigma Tables for Debugger
     *
     * This is used to update tables via a debugger invocation.
     *
     * @param   string  $payment_table          Sigma Payment Table Name
     * @param   string  $registration_table     Sigma Registration Table Name
     * @param   array   $post                   Collected data from the POST
     * @param   array   $registration           Registration data retrived
     *                                          through the token POSTed
     *
     * @return  boolean Result of the db update operation.
     *                  If error occured, silently exits
     */
    function update_tables_debugger( $payment_table, $registration_table, $post, $registration ){
        $r = $this->update_payment_table( $payment_table, $post );
        if( $r['result'] ):
            $registration['paid'] = $post['resultado'];
            $post['payment_id']   = $r['payment_record_id'];
            $registration['payment'] = $r['payment_record_id'];
        else:
            exit;
        endif;

        global $wpdb;
        $table_name                 = $wpdb->prefix . $registration_table;
        $where                      = array( 'token' => $registration['token'] );

        $r = $wpdb->update( $table_name, $registration, $where);
        if(false === $r) {
            $this->log_error( "\nError: Sigma DB Update Error | Token: " . $post["token"] );
            exit;
        }

        return $r;
    }

    /**
     * Update Sigma Payment Table
     *
     * Update Sigma Payment Table with POSTed data. This updates
     * per each valid POST to a payment processor.
     *
     * Returns Payment Record ID to be updated in Sigma Registration Table.
     *
     * @param   string  $payment_table          Sigma Payment Table Name
     * @param   array   $post                   Collected data from the POST
     *
     * @return  boolean Result of the payment database update operation
     */
    function update_payment_table( $payment_table, $post ){
        global $wpdb;
        $table_name = $wpdb->prefix . $payment_table;

        $data = array(
            'id'                => null,
            'codautorizacion'   => $post['codeauth'],
            'tarjeta'           => $post['tarjeta'],
            'emailcomprador'    => $post['email'],
            'resultado'         => $post['resultado'],
            'fechahora'         => $post['fechahora'],
            'processor'         => $post['processor'],
            'titular'           => $post['titular'],
            'token'             => $post['token'],
            'motivo'            => $post['motivo'],
            'monto'             => $post['monto'],
            'ip'                => $_SERVER['REMOTE_ADDR']
        );

        $r = $wpdb->insert( $table_name, $data );
        if( ! $r )
            $this->log_error( "\nError: Payment DB Update Error | Token: " . $post["token"] );

        $output = array(
            'result'    => $r,
            'payment_record_id' => $wpdb->insert_id
        );
        return $output;
    }

    /**
     * Update Sigma Registration Table
     *
     * Update Sigma Registration Table with payment state. This updates
     * per each valid POST to a payment processor.
     *
     * Returns updated rows in Sigma Registration Table.
     *
     * @param   string  $registration_table     Sigma Registration Table Name
     * @param   array   $post                   Collected data from the POST
     *
     * @return  boolean Result of the Registration table db update
     */
    function update_sigma_table( $registration_table, $post ){
        global $wpdb;
        $table_name                 = $wpdb->prefix . $registration_table;
        $where                      = array( 'token' => $post['token'] );

        $data = array(
            'paid'      => $post['resultado'],
            'medium'    => $post['tarjeta'],
            'payment'   => $post['payment_id'],
            'seq_no'    => $post['seq_no']
        );
        $r = $wpdb->update( $table_name, $data, $where);
        if( ! $r )
            $this->log_error( "\nError: Sigma DB Update Error | Token: " . $post["token"] );

        return $r;
    }

    /**
     * Send Emails
     *
     * Sends emails to user, admin and organizer after a successful
     * verification of the POST and after the database updates.
     *
     * Most of the email settings are configurable in the admin backend.
     *
     * Email tempalates can be defined per event via event edit pages.
     *
     * @access public
     * @param   array   $options                Sigma Options Wordpress Option
     * @param   array   $post                   Collected data from the POST
     * @param   array   $registration           Registration data retrived
     *                                          through the token POSTed
     *
     * @return  boolean Result of the last wp_mail() function
     */
    function send_emails( $options, $post, $registration ){
        add_filter( 'wp_mail_content_type', array($this, 'set_html_content_type') );
            $r = $this->send_admin_email( $options, $post, $registration );
            $r = $this->send_user_email( $options, $post, $registration );
            $r = $this->send_products_email( $options, $post, $registration );
        remove_filter( 'wp_mail_content_type', array($this, 'set_html_content_type') );
        return $r;
    }

    /**
     * Send Admin Email
     *
     * Sends admin an email notifying the POST. This is HTML formatted
     * email with POSTed data, registration record and IP of the POSTer.
     *
     * @param   array   $options                Sigma Options Wordpress Option
     * @param   array   $post                   Collected data from the POST
     * @param   array   $registration           Registration data retrived
     *
     * @return  boolean|int     Result of the Admin mail function
     */
    function send_admin_email( $options, $post, $registration ){
        if($options['enable_admin_email']):
            $event = get_post($registration['eid']);

            // Get Event Meta Data.
            $event_id = $event->ID;
            $organizer = get_post_meta($event_id, 'sigma_event_organizer', true);

            $to = $options['admin_email'];
            $subject = 'Tok: ' . $registration['token'] .
                ' | St: ' . ucfirst($post['resultado']) . ' | ' .
                $registration['eid'];

            $message  = '<h2>Name: ' . $registration['fname'] . ' ' . $registration['lname'] . '</h2>';
            $message .= '<h3>Registration Data: </h3>';
            $message .= '<pre>' . print_r($registration, true) . '</pre>';
            $message .= '<h3>IP Address: </h3>';
            $message .= $_SERVER['REMOTE_ADDR'];
            $message .= '<h3>POSTed Data: </h3>';
            $message .= '<pre>' . print_r($post, true) . '</pre>';
            $message .= '<img src="' . SIGMA_URL . 'assets/sigma-logo.png" alt="sigma-logo" >';

            $headers[] = 'From: ' . $options['send_name'] . ' <' . $options['send_email'] . '>';

            // Copy Developer?
            if($options['enable_dev_email'])
                $headers[] = 'Cc: Developer <' . $options['dev_email'] . '>';

            // Copy Organizer?
            if($options['enable_organizer_email'])
                $headers[] = 'Cc: ' . $organizer['name'] . ' <' . $organizer['mail'] . '>';

            $r = wp_mail($to, $subject, $message, $headers );
            return $r;
        endif;
    }

    /**
     * Send User Email
     *
     * Sends an email to user using the event specific template that is
     * defined in the event edit page.
     *
     * Seperate templates for payment success and failure.
     *
     * @param   array   $options                Sigma Options Wordpress Option
     * @param   array   $post                   Collected data from the POST
     * @param   array   $registration           Registration data retrived
     *
     * @return  boolean|int     Result of the User mail function
     */
    function send_user_email( $options, $post, $registration ){
        if($options['email_user']):
            $event                      = get_post($registration['eid']);
            $sigma_logo                 = '<img src="' . SIGMA_URL . 'assets/sigma-logo.png" alt="sigma-logo" >';
            $event_logo                 = get_the_post_thumbnail($event->ID, 'thumbnail');

            // Prepare replace array to be used on preg_replace_callback.
            $replace_array              = $registration;
            $replace_array['sigmalogo'] = $sigma_logo;
            $replace_array['eventlogo'] = $event_logo;
            $replace_array['ename']     = $event->post_title;
            $replace_array['econtent']  = $event->post_content;
            $replace_array['Fname']     = ucfirst(strtolower($registration['fname']));
            $replace_array['Lname']     = ucfirst(strtolower($registration['lname']));
            $replace_array['seq_no']    = $registration['seq_no'];
            $replace_array['reason']    = isset($post['motivo']) ? $post['motivo'] : '';
            $this->replace_array        = $replace_array;

            // Get Event Meta Data.
            $event_id                   = $event->ID;
            $email_template             = get_post_meta($event_id, 'sigma_email_template', true);
            $organizer                  = get_post_meta($event_id, 'sigma_event_organizer', true);

            $to             = $registration['email'];
            $subject        = preg_replace_callback('!\{\{(\w+)\}\}!', array($this, 'replace_value'), $email_template['subject']);

            if($post['resultado'] == 'paid'):
                $message    = preg_replace_callback('!\{\{(\w+)\}\}!', array($this, 'replace_value'), $email_template['message_approved']);
                $message    = apply_filters('the_content', $message);
            elseif($post['resultado'] == 'notpaid' || $post['resultado'] == 'cancelled'):
                $message    = preg_replace_callback('!\{\{(\w+)\}\}!', array($this, 'replace_value'), $email_template['message_not_approved']);
                $message    = apply_filters('the_content', $message);
            endif;

            $headers[]      = 'From: ' . $options['send_name'] . ' <' . $options['send_email'] . '>';

            // Copy Organizer?
            if($options['enable_organizer_user_email'])
                $headers[]  = 'Cc: ' . $organizer['name'] . ' <' . $organizer['mail'] . '>';

            $attachment     = array($email_template['attachment']);

            if($post['resultado'] == 'paid' || $post['resultado'] == 'notpaid' || $post['resultado'] == 'cancelled'):
                $r = wp_mail($to, $subject, $message, $headers, $attachment);
                return $r;
            endif;
            return false;
        endif;
    }

    /**
     * Send User Product Emails
     *
     * Sends an email for each additinal product purchased upon registering
     * for the event.
     *
     * Product email templates are defined in the edit product page( email
     * template per product ).
     *
     * @param   array   $options                Sigma Options Wordpress Option
     * @param   array   $post                   Collected data from the POST
     * @param   array   $registration           Registration data retrived
     *
     * @return  boolean|int     Result of the User products mail function
     */
    function send_products_email( $options, $post, $registration ){
        $extra_items = $registration['extra_items'];
        $extra_items = 4 < strlen( $extra_items ) ? true : false;
        if($options['user_products_email'] && $extra_items):

            // Get Additional Products.
            $products   = unserialize($registration['extra_items']);
            $organizer  = get_post_meta($registration['eid'], 'sigma_event_organizer', true);

            // No additional products?
            if( ! $products ) return;

            foreach( $products as $product ):
                // Setup 'pid' tag for email templates.
                $this->replace_array['pid'] = $product[0];

                // Setup 'pname' tag for email templates.
                $this->replace_array['pname'] = $product[2];

                // Get Product Meta Data (Email Template)
                $event_id = $product[0];
                $email_template = get_post_meta($event_id, 'sigma_email_template', true);

                $to = $registration['email'];
                $subject = preg_replace_callback('!\{\{(\w+)\}\}!',
                    array($this, 'replace_value'), $email_template['subject']);

                if($post['resultado'] == 'paid'):
                    $message = preg_replace_callback('!\{\{(\w+)\}\}!',
                    array($this, 'replace_value'), $email_template['message_approved']);
                    $message = apply_filters('the_content', $message);

                    $headers = null;
                    $headers[] = 'From: ' . $options['send_name'] . ' <' . $options['send_email'] . '>';

                    // Copy Organizer?
                    if($options['enable_organizer_user_product_email'])
                        $headers[] = 'Cc: ' . $organizer['name'] . ' <' . $organizer['mail'] . '>';

                    $attachment = array($email_template['attachment']);

                    $r = wp_mail($to, $subject, $message, $headers, $attachment);
                endif;
            endforeach;
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
     * Log Messages
     *
     * Log strings to log file.
     *
     * @param   string  $message    String to log
     *
     * @return  int     Length of the written message
     */
    function log( $message ){
        $sigma_log = SIGMA_PATH . 'logs/' . $this->data_log;
        $data   = "\n------------------------------------------------------------------";
        $data  .= "\n" . current_time('mysql') . " " . $_SERVER['REMOTE_ADDR'];
        $data  .= "\n" . print_r($message , true);
        $data  .= "------------------------------------------------------------------\n";
        $r         = file_put_contents($sigma_log, $data, FILE_APPEND | LOCK_EX);
        return $r;
    }

    /**
     * Log Error Messages
     *
     * Log Errors to log file.
     * Prepends date time and formats the error
     * string before logging.
     *
     * @param   string  $message    String to log
     *
     * @return  int     Length of the written message
     */
    function log_error( $message ){
        $sigma_log = SIGMA_PATH . 'logs/' . $this->error_log;
        $msg       = "\n" . current_time('mysql') . " " . $message;
        $r         = file_put_contents($sigma_log, $msg, FILE_APPEND | LOCK_EX);
        return $r;
    }

    /**
     * Log Data Received
     *
     * @param   string  $response    String to log
     *
     * @return  int     Length of the written message
     */
    function log_data( $response ){
        $sigma_log = SIGMA_PATH . 'logs/' . $this->error_log;
        $data   = "\n------------------------------------------------------------------";
        $data  .= "\n" . current_time('mysql') . " " . $_SERVER['REMOTE_ADDR'];
        $data  .= "\n" . print_r($response , true);
        $data  .= "------------------------------------------------------------------\n";
        $r         = file_put_contents($sigma_log, $data, FILE_APPEND | LOCK_EX);
        return $r;
    }

    /**
     * Get Free Event Registration Form
     *
     * Outputs an HTML form which will post to an endpoint as if it were sent
     * from a Payment Processor( e.g Decidir ) which makes the event registration
     * record a paid one.
     *
     * @param   string  $token      Token to which the form should be tailored
     * @return  string  Free event payment form
     */
    function get_free_event_form($token){
        global $sigma_events;
        $endpoint = $sigma_events->payments_decidir->get_decidir_endpoint();
        $datetime = date('d/m/Y H:i:s');

        $form = '<form action="' . get_home_url() . '/' . $endpoint .
            '" id="se-decidir-form" method="post" >';
        // Operacion Number.
        $form .= '<input type="hidden" name="noperacion" value="' . $token .'" size=8 maxlength=8 >';
        // Result String.
        $form .= '<input type="hidden" name="resultado" value="APROBADA" size=10 maxlength=10 >';
        // Amount.
        $form .= '<input type="hidden" name="monto" value="0" size=12 maxlength=12 >';
        // Submit Button.
        $form .= '<input type="submit" id="se-proceed" value="Proceed to payment" >';
        // Date Time
        $form .= '<input type="hidden" name="fechahora" value="' . $datetime . '" >';
        // free indicator.
        $form .= '<input type="hidden" name="free" value="FREE" size=8 maxlength=8 >';

        return $form;
    }
}
endif;
?>
