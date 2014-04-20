<?php
if( !defined( 'ABSPATH' ) ){
    header('HTTP/1.0 403 Forbidden');
    die('No Direct Access Allowed!');
}

/**
 * Decidir Visa
 */
if( 'decidir_visa' == $event_data['medium'] ) :
    $method_of_payment = 'Visa ( via Decidir ) <span id="se-visa" ></span>';

/**
 * Decidir American Express
 */
elseif( 'decidir_amex' == $event_data['medium'] ):
    $method_of_payment = 'American Express ( via Decidir ) <span id="se-amex" ></span>';

/**
 * Decidir MasterCard
 */
elseif( 'decidir_mastercard' == $event_data['medium'] ):
    $method_of_payment = 'MasterCard ( via Decidir ) <span id="se-mc" ></span>';

/**
 * Dineromail Credit Cards
 */
elseif( 'dineromail_credit_cards' == $event_data['medium'] ):
    $method_of_payment = 'Credit Cards ( via Dineromail ) <img src="' . SIGMA_URL . 'assets/dineromail-credit-cards.png" >';

/**
 * Dineromail Cash
 */
elseif( 'dineromail_cash' == $event_data['medium'] ):
    $method_of_payment = 'Cash ( via Dineromail ) <img src="' . SIGMA_URL . 'assets/dineromail-cash.png" >';

/**
 * Sigma Salesperson
 */
elseif( 'salesperson' == $event_data['medium'] ):
    $method_of_payment = 'Via a Salesperson <img src="' . SIGMA_URL . 'assets/salesperson-payment-method.png" >';

/**
 * Paypal
 */
elseif( 'paypal' == $event_data['medium'] ):
    $method_of_payment = 'Via a Paypal <img src="' . SIGMA_URL . 'assets/paypal-payment-method.png" >';

/**
 * Sigma EP
 */
elseif( 'ep' == $event_data['medium'] ):
    $method_of_payment = 'Via EP <img src="' . SIGMA_URL . 'assets/ep-payment-methods.png" >';

/**
 * Sigma CuentaDigital
 */
elseif( 'cuentadigital' == $event_data['medium'] ):
    $method_of_payment = '<img src="' . SIGMA_URL . 'assets/cuentadigital-logo-wide.gif" >';
endif;

// Set price unit.
$price_unit = $event_data['argentinian'] ? ' ARS' : 'USD';

// Get the site header.
get_header();
echo '<div class="se-wrapper" >';

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

// Display the details of the transaction.
echo '<div id="se-reciept" >';
echo '<div id="se-greeting" class="se-greeting-checkout" >';
    echo $event_data['greeting'];
echo '</div>';
echo '<table>';
    // Event Price
    $registration_fee = $event_data['amount'] - $event_data['products_total'];
    if($event_data['argentinian']):
        echo '<tr><td>Registration Fee : </td><td>' . $registration_fee / 100 . $price_unit . '</td></tr>';
    else:
        echo '<tr><td>Registration Fee : </td><td>' . $registration_fee / $event_data['price']['rate'] /100 . $price_unit . '</td></tr>';
    endif;

    // Display Product Information
    echo $event_data['products_rows'];

    // Total Price
    $total_price = $event_data['amount'];
    if($event_data['argentinian']):
        echo '<tr><td>Total Payment : </td><td>' . $total_price / 100 . $price_unit . '</td></tr>';
    else:
        echo '<tr><td>Total Payment : </td><td>' . $total_price / $event_data['price']['rate'] /100 . $price_unit . '</td></tr>';
    endif;

    /**
     * Payment method image
     */
    echo '<tr><td>Method of Payment : </td><td>' . $method_of_payment . '</td></tr>';

    echo '<tr><td colspan=2>';

    global $sigma_events;
    /**
     * Decidir Processor Form
     */
    if( in_array( $event_data['medium'], array( 'decidir_visa', 'decidir_amex', 'decidir_mastercard' ) ) ):
        $card_holder = $event_data['fname'] . ' ' . $event_data['lname'];
        echo $sigma_events->payments_decidir->get_form(
            $event_data['token'],
            $total_price,
            $event_data['medium'],
            true
        );

    /**
     * Dineromail Processor Form
     */
    elseif( in_array( $event_data['medium'], array( 'dineromail_credit_cards', 'dineromail_cash' ) ) ):
        echo $sigma_events->payments_dineromail->get_form($event_data, true);

    /**
     * Salesperson Processor Form
     */
    elseif( 'salesperson' == $event_data['medium'] ):
        echo $sigma_events->payments_salesperson->get_form(
            $event_data['token'],
            $total_price,
            true
        );

    /**
     * Paypal Processor Form
     * TODO LUCHO: el get_form recibe parametros distintos a los que se le envia. Chequear bien esto
     */
    elseif( 'paypal' == $event_data['medium'] ):
        echo $sigma_events->payments_paypal->get_form(
            $event_data['token'],
            $total_price,
            true
        );

    /**
     * CuentaDigital Processor Form
     */
    elseif( in_array( $event_data['medium'], array( 'cuentadigital' ) ) ):
        echo $sigma_events->payments_cuentadigital->get_form($event_data, true);

    /**
     * EP Processor Form
     */
    elseif( 'ep' == $event_data['medium'] ):
        echo $sigma_events->payments_ep->get_form( $event_data, true);
    endif;

    echo '</td></tr>';
echo '</table>';
echo '</div>';

// Get the site footer.
echo '</div>';
get_footer();
exit;
?>
