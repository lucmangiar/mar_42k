<?php
/**
 * This template is used to serve tracking queries.
 * Called by class-sigma-payment-tracker.
 */
if( !defined( 'ABSPATH' ) ){
    header('HTTP/1.0 403 Forbidden');
    die('No Direct Access Allowed!');
}

// For every request, we'll be responding. First send the header.
get_header();

echo '<div class="se-wrapper" >';
    // Get Sigma Options
    $options = get_option('sigma_options');

    $token = '';

    // Is the token POSTed?
    if($options['enable_tracker_post'] && isset($_POST['sigma_token']) && $_POST['sigma_token'] != ''):
        $token = sanitize_text_field($_POST['sigma_token']);
    // Else the token be GET?
    elseif($options['enable_tracker_get'] && isset($_GET['sigma_token']) && $_GET['sigma_token'] != ''):
        $token = sanitize_text_field($_GET['sigma_token']);
    endif;

    // No token presented?
    if($token == ''):
        // Is tracker enabled?
        if($options['enable_tracker_post']):
            // This should be a tracker form request. Let's serve the form.
            echo '<h1>' . __('Sigma Payment Tracker', 'se') . '</h1>';
            echo '<p>' . __('Enter your Unique Booking ID code to the below box, to view the payment status.', 'se') . '</p>';

            // Tracker Form. Grab the tracking id. Sigma Unique ID.
            echo "<form id='se-tracker' name='se-tracker' method='POST' action='"
                . get_home_url() . "/sigma-events/tracker' >";

            // Dummy fields to trick bots
            echo "<div class='se-row' ><div class='se-firstname se-half' >";
            echo "<label>" . __("Enter First Name: ", 'se'). "</label>";
            echo "<input type='text' id='firstname' name='firstname' value='' >";
            echo "</div>";
            echo "<div class='se-lastname se-half' >";
            echo "<label>" . __("Enter Last Name: ", 'se') . "</label>";
            echo "<input type='text' id='lastname' name='lastname' value='' >";
            echo "</div></div>";

            // Nonce Fields
            wp_nonce_field('sigma-tracker-form-action', 'sigma-tracker-form-data');

            echo "<div id='se-form-errors' >";
            echo "</div>";

            echo "<div class='se-row' ><div class='se-club se-half' >";
            echo "<label>" . __("Enter your Booking ID (token) code: ", 'se') . "</label>";
            echo "<input STYLE='margin-left: 10px' type='text' name='sigma_token' value='' >";
            echo "</div>";

            echo "<div class='se-row' ><div class='se-club se-half' >";
            echo "<input type='submit' id='se-reg-button' name='submit' value='" .
                __("View Payment Status", 'se') . "'>";
            echo '<br /><br /><p><a id="se-retrieve" class="button" href="' . get_home_url() . '/forget_code/" >' . __('I don\'t remember my ID and want to retrieve', 'se') . '</a></p>';
            echo "</form>";
            echo "</div>";
        else:
            echo __('Sigma Tracker is not available.', 'se');
        endif;
    // We have a token. Let's process the token and give their payment details.
    else:
        // Dummy field for robots is filled?
        if(isset($_POST['firstname']) && $_POST['firstname'] != '') :
            wp_die( __( 'Rob: Cheatin&#8217; uh?' ) );
        // Can nonce be verified?
        elseif(isset($_POST['sigma-tracker-form-data'])
            && !wp_verify_nonce($_POST['sigma-tracker-form-data'], 'sigma-tracker-form-action')) :
            wp_die( __( 'Non: Cheatin&#8217; uh?' ) );
        endif;

        // We need the registration record.
        $events = new Sigma_Event( $this->registration_table );
        $registration = $events->get_registration_record( $this->registration_table, $token );
        if(!$registration):
            echo "<h1>" . __('Are you sure?', 'se') . "</h1>";
            echo "<p>" . __('The Transaction ID you entered', 'se') .
                " ( " . $token . " ) " .
                __("doesn't exist!", "se") . "</p>";
            echo '<p>' . __('Make sure you did not input any space. Please try again.', 'se') . '</p>';
            echo '<p><a class="button" href="' . get_home_url() . '/forget_code/" >' . __('Receive code and the other details via email.', 'se') . '</a></p>';
            exit;
        endif;

        // Get Event Data
        $extra_data = $events->confirmation_get_data($registration);
        $extra_data = array_merge($extra_data, $registration);
        $event_data = $events->get_event_data($registration);
        $event_data = array_merge($extra_data, $event_data);

        echo '<div id="sigma-tracker" >';

        // Event Title
        echo "<div class='se-header' >";
        echo $event_data['title'];
        echo "</div>";

        // Event Picture/Thumbnail
        echo "<div class='se-thumbnail' >";
        echo $event_data['thumbnail'];
        echo "</div>";

        // Organizer Logo
        echo "<div class='se-ologo se-half' >";
          echo "<img src='" . $event_data['organizer']['logo'] . "' alt='Event Logo'>";
        echo "</div>";

        // Organizer Name
        echo "<div class='se-oname se-half' >";
          echo __('Organized by: ', 'se') . $event_data['organizer']['name'];
        echo "</div>";

        // Organizer Url/Website
        echo "<div class='se-ourl se-half' >";
        echo " <a target='_blank' href='" .
            $event_data['organizer']['url'] . "' title='" . __('Visit Event Organizer Site', 'se') .
            "'>" . __('Visit Organizer Site', 'se') . "</a>";
        echo "</div>";

        // Final Day of Registration Of The Event
        echo "<div class='se-pend se-half' >";
          echo __('Booking for this event will be finalized on: ', 'se') . date('Y-m-d', $event_data['period']['end']);
        echo "</div>";

        // Event Description
        echo "<div class='se-content se-half' >";
        echo $event_data['content'];
        echo "</div>";


        echo "<div id='se-tracker-greeting' >
            <p class='se-tracker-name'>" . __('Hi, ', 'se') .
            $event_data['fname'] . ",</p>";

        if($event_data['paid'] == 'paid' && $event_data['price']['value'] == 0):
            echo "<p class='se-payment-status'>" .
            __('Your booking', 'se') .
            ' (' . __('ID: ', 'se')  . '<b>' . $token . '</b>' . ') ' .
            __('has been <b>confirmed</b>', 'se') . '</p>';

            // Sequence Number
            if( 0 != $event_data['seq_no'] ):
                echo "<p class='se-sequence-number'>" .
                    __('Your Sequence Number is ', 'se') .
                    $event_data['seq_no']  . '</p>';
            endif;
        elseif($event_data['paid'] != 'paid'):

            echo "<p class='se-payment-status'>" .
            __('Your booking', 'se') .
            ' (' . __('ID: ', 'se') . '<b>' . $token . '</b>' . ') ' .
            __('has <b>NOT</b> been processed yet.', 'se') . '</p>';

            echo '<p><b>' . __('Note that payment information can take a while to update. If you have already paid, do not attempt to pay again until you are sure not charge was made to your credit card.', 'se') . '</b></p>';

            $resume_link = get_home_url() . '/sigma-events/checkout/?token=' .
                $event_data['token'] ;

            $resume_link_with_additional_product_selection = get_home_url()
                . '/sigma-events/payment/?sigma_token=' .
                $event_data['token'] ;

            echo '<p><b>' . __('Note:', 'se') . ' ' . '</b>' .
                __("If you was't able to complete the payment for some reason, you can resume your payment.", "se") . "<br />
                    <a href=" . $resume_link_with_additional_product_selection .
                    " class='button' id='se-resume-payment'>" .
                    __('Resume Payment', 'se') . "</a></p>";
        else:
            echo "<p class='se-payment-status'>" .
			__('Your booking', 'se') .
            ' (' . __('ID:', 'se') . '<b>' . $token . '</b>' . ') ' .
            __('has been <b>confirmed</b>.', 'se') . "</p>";

            // Sequence Number
            if( 'none' != $event_data['seq_no'] ):
                echo "<p class='se-sequence-number'>" .
                    __('Your Sequence (bib) Number is ', 'se') .
                    $event_data['seq_no']  . '</p>';
            endif;
        endif;

        echo '<a class="print-preview">' .
        __('Print this page', 'se') . '</a></div>';

        // Display Event Information.
        require SIGMA_PATH . 'templates/sigma-profile-template.php';
        if($event_data['argentinian']):
            $price_unit = ' ARS';
        else:
            $price_unit = ' USD';
        endif;

        // Display the details of the event_data['registration'].
        $registration_fee = $event_data['amount'] - $event_data['products_total'];
        $total_price = $event_data['amount'];
        if($event_data['amount'] > 0):
            echo '<div id="se-reciept" >';
            echo '<table>';

            if($event_data['argentinian']):
                echo '<tr><td>Registration Fee : </td><td>' . $registration_fee / 100 . $price_unit . '</td></tr>';
            else:
                echo '<tr><td>Registration Fee : </td><td>' . $registration_fee / $event_data['price']['rate'] /100 . $price_unit . '</td></tr>';
            endif;

            echo $event_data['products_rows'];

            if($event_data['argentinian']):
                echo '<tr><td>Total Payment : </td><td>' . $total_price / 100 . $price_unit . '</td></tr>';
            else:
                echo '<tr><td>Total Payment : </td><td>' . $total_price / $event_data['price']['rate'] /100 . $price_unit . '</td></tr>';
            endif;

            echo '</table>';
            echo '</div>';
        endif;

        echo '<a class="print-preview">Print this page</a>';
        endif;
echo '</div>';
get_footer();
?>
