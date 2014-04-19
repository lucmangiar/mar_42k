<?php
/**
 * Sigma t-Events Template
 *
 * @package     SigmaEvents
 * @subpackage  SigmaTempaltes
 * @since version 3.6
 */

if( !defined( 'ABSPATH' ) ){
    header('HTTP/1.0 403 Forbidden');
    die('No Direct Access Allowed!');
}
get_header();
echo "<form action='" . get_home_url() . "/tourism/agent/' method='post'>
    <table>
    <tr>
		<td colspan='2'><h1>" . __("Tourist Agent Interface", "se") . "</h1></td>
    </tr>
    <tr>
		<td>" . __("Token", "se") . "</td><td><input type='text' name='token' value=''>" . __("Original Token of the Registrant", "se") . "</td>
    </tr>
    <tr>
    <td>" . __("Currency", "se") . "</td><td>
        <input type='radio' name='currency' value='ars'> " . __("ARS", "se") . "
        <input type='radio' name='currency' value='usd'> " . __("USD", "se") . "
    </td>
    </tr>
    <tr>
		<td>" . __("Amount", "se") . "</td><td><input type='text' name='amount' value=''>" . __("New Amount (XXX.XX format)", "se") . "</td>
    </tr>
    <tr>
		<td>" . __("Password", "se") . "</td><td><input type='text' name='password' value=''>" . __("Agent Password", "se") . "</td>
    </tr>
    <tr>
		<td colspan='2'><input type='submit' value='" . __("Update Transaction", "se") . "'></td>
    </tr>
    <tr>
		<td colspan='2'><pre>" . print_r($registration, true) . "</pre></td>
    </tr>
    <table>
	</form>";
get_footer();
?>
