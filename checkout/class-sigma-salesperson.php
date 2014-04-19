<?php
if ( !class_exists('Sigma_SalesPerson') ) :
/**
 * Sigma Salesperson
 *
 * Payment handling with agents( Salespeople )
 *
 * @package     SigmaEvents
 * @subpackage  PaymentProcessing
 * @since       version 2.9
 */
class Sigma_SalesPerson extends Sigma_Payment_Processor
{
    /**
     * Sigma Registration Table Name
     *
     * @var string
     */
    private $registration_table;

    /**
     * Sigma Payment Table Name
     *
     * @var string
     */
    private $payment_table;

    /**
     * Salesperson Page.
     *
     * @var string
     */
    private $salesperson_endpoint = 'cpago';

    /**
     * Construct the Sigma SalesPerson object.
     */
    function __construct( $registration_table, $payment_table ){
        // Setup table name.
        $this->registration_table = $registration_table;
        $this->payment_table = $payment_table;

        $this->salesperson_endpoint = get_home_url() . '/' .  $this->salesperson_endpoint;
    }

    /**
     * Get Sigma Salesperson Checkout Form
     *
     * This form is used to POST data to the salesperson endpoint
     * Eventhough that page isn't expecting data we're POSTing anyway.
     *
     * Maybe we can use this data to customize the page
     * to provide more personalized experience to the visitor.
     */
    function get_form( $operation_number, $amount, $submit ){
        /* Whether the payment button should be present or not */
        $submit = $submit
            ? "<input type='submit' id='se-proceed' value='Proceed to payment' ><a
                id='se-modify' class='button' href='" . get_home_url() .
                "/sigma-events/payment/?sigma_token=" . $operation_number . "#se-order'>Modify</a>"
            : '';

        // Premium Event?
        if($amount > 0):
            $form = '<form action="' . $this->salesperson_endpoint . '" id="se-salesperson-form" method="post" >';
            // Operacion Number.
            $form .= '<input type="hidden" name="NROOPERACION" value="' . $operation_number . '" size=10 maxlength=10 >';
            // Amount.
            $form .= '<input type="hidden" name="MONTO" value="' . $amount . '" size=12 maxlength=12 >';

            $form .= $submit . '</form>';

        // Free Event?
        else:
            $form  = $this->get_free_event_form($operation_number);
        endif;

        return $form;
    }

    /**
     * Return Payment Endpoint URL
     */
    function get_payment_endpoint(){
        return $this->salesperson_endpoint;
    }
}
endif;
?>
