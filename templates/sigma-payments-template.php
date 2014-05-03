<?php
/**
 * Sigma Additional Product Selection and Payment Resume
 *
 * This template is to serve two kinds of requests.
 *
 * 1. Redirected visitors from the registration.
 * 2. Payment resume with additional product reselection.
 *
 * @package     SigmaEvents
 * @subpackage  SigmaTempaltes
 */

if( !defined( 'ABSPATH' ) ){
    header('HTTP/1.0 403 Forbidden');
    die('No Direct Access Allowed!');
}
get_header();
echo '<div class="se-wrapper se-payments-wrapper" >';
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

    // Dummy Anchor.
    echo '<div id="se-order" ></div>';

    // Greeting
    echo '<div id="se-greeting" >';
    echo $event_data['greeting'];
    echo '</div>';

    echo '<div id="event-price" >';
    echo $event_data['price']['string'];
    echo '</div>';

    // This page collects addtional products data and medio de pago.
    echo '<form id="se-payment-options"
        action="' . get_home_url() . '/sigma-events/checkout" method="post" >';

        // Send some more information to identify the transaction.
        echo '<input id="token" type="hidden" name="token" value="' . $event_data['token'] . '" >';

        // Confirmation page url to be used in ajax call just before leaving to the payment processor.
        echo '<input id="se-confirmation-url" type="hidden" name="se-confirmation-url" value="' . get_home_url() . '/sigma-events/checkout" >';

        // Nonce Security Field.
        wp_nonce_field('se-payment-options-action', 'se-payment-options-data');
        if( true == $event_data['products'] ):
        foreach($event_data['products'] as $product):
            echo '<div class="sigma-products se-half" >';
                echo '<h3>' . $product['title'] . '</h3>';      // Product Title
                echo "<div class='se-product-thumbnail' >";     // Product Thumbnail
                echo $product['thumbnail'];
                echo "</div>";
                echo '<p>' . $product['excerpt']  . '</p>';     // Product Excerpt
                echo '<p><a href="'. $product['link'] . '" >More Information</a></p>';
                echo '<p>' . $product['price_tag'] . '</p>';    // product Link and Price Tag
                echo '<p><input type="checkbox"  value="' . $product['id'] . '"
                    name="extra_items[]" class="se-product-checkbox" >' . '  Add This Product</p>';
            echo '</div>';                                         // Product Add to cart button
        endforeach;
        endif;

        /**
         * Maybe output payment page banner.
         */
        echo '<div style="clear:both;" ></div>';
        echo payment_page_banner( $event_data );
        echo '<div style="clear:both;" ></div>';

        // 'Medium of Payment' only display for non-free events.
        if($event_data['price']['value'] > 0):
            echo '<div id="se-medio-de-pago" >';
        else:
            echo '<div id="se-medio-de-pago" style="display:none;" >';
        endif;

        if( $event_data['freedom']['decidir'] || $event_data['freedom']['dineromail'] || $event_data['freedom']['salesperson'] ):
            echo '<p><label>' . __( 'Select a Payment Method' , 'se') . '</label></p>';

            // EP
            if( $event_data['freedom']['ep']  || 'ep' == $event_data['processor'] ):
                echo '<p class="se-processor-box"><input id="ep" type="radio" value="ep" name="payment_processor" ' .
                checked( $event_data['processor'], 'ep' , false )  . ' >' . __( ' Credit Cards', 'se') . '<span    id="se-ep-logo" ></span></p>';
            endif;

            // Decidir
            if( $event_data['freedom']['decidir'] || 'decidir' == $event_data['processor'] ):
                echo '<p class="se-processor-box"><input id="decidir"    type="radio" value="decidir" name="payment_processor"' .
                checked( $event_data['processor'], 'decidir' , false )     . ' >' . __( ' Credit Cards', 'se') . '<span   id="se-decidir-logo" ></span></p>';
            endif;

            // Dineromail
            if( $event_data['freedom']['dineromail']  || 'dineromail' == $event_data['processor'] ):
                echo '<p class="se-processor-box"><input id="dineromail" type="radio" value="dineromail" name="payment_processor" ' .
                checked( $event_data['processor'], 'dineromail' , false )  . ' >' . __( ' Credit Cards', 'se') . '<span    id="se-dineromail-logo" ></span></p>';
            endif;

            // Paypal
            if( $event_data['freedom']['paypal']  || 'paypal' == $event_data['processor'] ):
                echo '<p class="se-processor-box"><input id="paypal" type="radio" value="paypal" name="payment_processor" ' .
                    checked( $event_data['processor'], 'paypal' , false )  . ' >' . __( ' Paypal', 'se') . '<span    id="se-paypal-logo" ></span></p>';
            endif;

            // CuentaDigital
            if( $event_data['freedom']['cuentadigital']  || 'cuentadigital' == $event_data['processor'] ):
                echo '<p class="se-processor-box"><input id="cuentadigital" type="radio" value="cuentadigital" name="payment_processor" ' .
                checked( $event_data['processor'], 'cuentadigital' , false )  . ' >' . __( ' Pay Cash', 'se') . '<span    id="se-cuentadigital-logo" ></span></p>';
            endif;

            // Salesperson
            if( $event_data['freedom']['salesperson']  || 'salesperson' == $event_data['processor'] ):
                echo '<p class="se-processor-box"><input id="salesperson" type="radio" value="salesperson" name="payment_processor" ' .
                checked( $event_data['processor'], 'salesperson' , false )  . ' >' . __( ' Through official travel agency', 'se') . '<span    id="se-salesperson-logo" ></span></p>';
            endif;

            echo '<div id="se-payment-processor-spacer" ></div>';
        else:
            echo '<input type="hidden" name="payment_processor" value="' . $event_data['processor'] . '" >';
        endif;

        echo '<div style="clear:both;" ></div>';

        $style = 'decidir' != $event_data['processor'] ? 'style="display:none;"' : '';
        echo '<div id="decidir-payment-options" ' . $style . ' >';
        echo '<p><label>' . __( 'Payment Options', 'se') . '</label></p>';
        echo '<p class="se-processor-methods"><input id="visa" type="radio" value="decidir_visa"       name="medio_de_pago" checked="checked" > Visa<span id="se-visa" ></span></p>';
        echo '<p class="se-processor-methods"><input id="amex" type="radio" value="decidir_amex"       name="medio_de_pago"                   > American Express<span id="se-amex" ></span></p>';
        echo '<p class="se-processor-methods"><input id="mc"   type="radio" value="decidir_mastercard" name="medio_de_pago"                   > MasterCard<span id="se-mc" ></span></p>';
        echo '</div>';

        echo '<div style="clear:both;" ></div>';

        $style = 'dineromail' != $event_data['processor'] ? 'style="display:none;"' : '';
        echo '<div id="dineromail-payment-options" ' . $style . ' >';
        echo '</br></br><p><b>' . __( 'Payment Options', 'se') . '</b></p>';
        echo '<p class="se-processor-methods"><input id="cash"         type="radio" value="dineromail_cash"         name="dineromail_medium"			DISABLED> Cash<span id="se-cash" ></span></p>';
        echo '<p class="se-processor-methods"><input id="credit_cards" type="radio" value="dineromail_credit_cards" name="dineromail_medium" checked="checked"	> Credit Cards<span id="se-credit-cards" ></span></p>';
        echo '</div>';

        $style = 'paypal' != $event_data['processor'] ? 'style="display:none;"' : '';
        echo '<div id="paypal-payment-options" ' . $style . ' >';
        echo '<p><b>' . __( 'Payment Options', 'se') . '</b></p>';
        echo '<p class="se-processor-methods"><input id="credit_cards" type="radio" value="paypal_credit_cards" name="paypal_medium"					> Tarjetas de Cr&eacute;dito<span id="se-credit-cards" ></span></p>';
        echo '</div>';

        $style = 'salesperson' != $event_data['processor'] ? 'style="display:none;"' : '';
        echo '<div id="salesperson-payment-options" ' . $style . ' >';
        echo '<p><b>' . __( 'Pay through Play Patagonia, our official travel agency', 'se') . '</b></p>';
        echo '<img src="' . SIGMA_URL . 'assets/salesperson-payment-method.jpg" >';
        echo '</div>';

        $style = 'cuentadigital' != $event_data['processor'] ? 'style="display:none;"' : '';
        echo '<div id="cuentadigital-payment-options" ' . $style . ' >';
        echo '</br></br><p><b>' . __( 'Print barcode to pay cash', 'se') . '</b></p>';
        echo '<img src="' . SIGMA_URL . 'assets/cuentadigital-barcode.png" >';
        echo '</div>';

		$style = 'ep' != $event_data['processor'] ? 'style="display:none;"' : '';
        echo '<div id="ep-payment-options" ' . $style . ' >';
        echo '</br></br><p><b>' . __( 'Payment Options', 'se') . '</b></p>';
        echo '<img src="' . SIGMA_URL . 'assets/ep-payment-methods.png" >';
        // Use the following to set card by the registrant. You may need to adjust the confirmation page code too.
        //echo '<p class="se-processor-methods"><input id="visa" type="radio" value="ep_visa"       name="medio_de_pago" checked="checked" >Visa<span id="se-ep-visa" ></span></p>';
        //echo '<p class="se-processor-methods"><input id="mc"   type="radio" value="ep_mastercard" name="medio_de_pago"                   >MasterCard<span id="se-ep-mc" ></span></p>';
        echo '</div>';


        echo '</div>';

        echo '<div style="clear:both;" ></div>';

        echo '<div id="se-update-pending-status" >';
        echo '<img src="' . SIGMA_URL . 'assets/loading.gif" >';
        echo '</div>';

        echo '<div style="clear:both;" ></div>';

        // Submit Button
        echo "<div class='se-payment-submit'  >";
        echo "<input type='submit' id='se-pay-button' name='submit' value='" . __("Proceed", 'se') . "'>";
        echo "</div>";
    echo '</form>';

    global $sigma_events;

    /* Present the Decidir Payment Form. */
    if( 'decidir' == $event_data['processor'] || $event_data['freedom']['decidir'] ):
        echo $sigma_events->payments_decidir->get_form(
            $event_data['token'],
            $event_data['price']['value'],
            'decidir_visa',
            false
        );
    endif;

    /* Present the Dineromail Payment Form. */
    if( 'dineromail' == $event_data['processor'] || $event_data['freedom']['dineromail'] )
        echo $sigma_events->payments_dineromail->get_form($event_data, false);

    /* Present the SalePerson Payment Form. */
    if( 'salesperson' == $event_data['processor'] || $event_data['freedom']['salesperson'] ):
        echo $sigma_events->payments_salesperson->get_form(
            $event_data['token'],
            $event_data['price']['value'],
            false
        );
    endif;

    /* Present the CuentaDigital Payment Form. */
    if( 'cuentadigital' == $event_data['processor'] || $event_data['freedom']['cuentadigital'] ):
        echo $sigma_events->payments_cuentadigital->get_form($event_data, false);
    endif;

    /* Present the EP Payment Form. */
    if( 'ep' == $event_data['processor'] || $event_data['freedom']['ep'] ):
        echo $sigma_events->payments_ep->get_form( $event_data, false);
    endif;

echo '</div>';
get_footer();
exit;

/**
 * Payment page banner
 *
 * If banner is set for the event return it.
 *
 * @return string banner and checkbox output
 */
function payment_page_banner( $event_data ){
    if( $event_data['banner'] ):
        $output  = '<div id="se-payment-banner" >';
        $output .= '<img class="se-payment-banner-image" src="' . $event_data['banner'] . '" >';
        $output .= '<div style="clear:both;" ></div>';
        $output .= '<input class="se-tourist-options" type="radio" value="yes" name="tourist_info" checked="checked" >I\'m interested on air tickets, hotels, tours and related products.   ';
        $output .= '<input class="se-tourist-options" type="radio" value="no"  name="tourist_info"                   >I\'m not interested.';
        $output .= '</div>';
        return $output;
    else:
        return '';
    endif;
}
?>
