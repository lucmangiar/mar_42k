<?php
if( !defined( 'ABSPATH' ) ){
    header('HTTP/1.0 403 Forbidden');
    die('No Direct Access Allowed!');
}

echo '<div id="debugger-pending-status" >';
echo '<img src="' . SIGMA_URL . 'assets/loading.gif" >';
echo '</div>';

/* Let's check the intention of the sigma events administrator  */
echo wp_nonce_field(
    'sigma_debugging_operations',
    'sigma_debugging_operations_nonce'
);

echo '<div id="sigma-debug-panel" >';
    echo '<h3 class="debug-heading" >Debug Registration Record(s)</h3>';
    echo '<table class="widefat sigmafat">';
    echo '<thead>
		<tr>
			<th>Input Type</th>
			<th>Inputs ( Tokens )</th>
			<th>Action Button</th>
		</tr>
	</thead>';

    echo '<tr>';
    echo '<td><label>Token : </label></td><td><input id="debug-tokens" type="text" value="abcabc1234" class="large-text" ><br />
        <br />Single Token or Comma Separated Multiple Tokens</td>';
    echo '<td><input id="debug-button" type="submit" class="button-primary" value="' . __('Query Database', 'se') .'" /></td>';
    echo '</tr>';

    echo '<tr>';
    echo '<td><label>Tokens : </label></td><td><input id="dineromail-tokens" type="text" value="debtes4333" class="large-text" ><br />
        <br />Single Token or Comma Separated Multiple Tokens</td>';
    echo '<td><input id="dineromail-button" type="submit" class="button-primary" value="' . __('Query Dineromail', 'se') .'" /></td>';
    echo '</tr>';

    echo '<tfoot>
		<tr>
			<th>Input Type</th>
			<th>Inputs ( Tokens )</th>
			<th>Action Button</th>
		</tr>
	</tfoot>';
    echo '</table>';
echo '</div>';

echo '<div id="ajax-response" >';
echo '</div>';
?>
