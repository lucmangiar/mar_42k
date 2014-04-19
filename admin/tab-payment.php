<?php
if( !defined( 'ABSPATH' ) ){
    header('HTTP/1.0 403 Forbidden');
    die('No Direct Access Allowed!');
}

// Globalize WPDB and Sigma Events Objects.
global $wpdb, $sigma_events;

// Construct Registration Table Name.
$payment_table = $wpdb->prefix . $sigma_events->payment_table;

// Get Recent Registrations from the Database.
$payments = $wpdb->get_results(
    "SELECT id, fechahora, codautorizacion, titular, tarjeta, ip, resultado, token, monto
    FROM $payment_table
    ORDER BY id
    DESC
    LIMIT 50
    ", ARRAY_A );

// Echo the header.
echo '<h3>Recent Payments</h3> Local Time: ' . current_time('mysql') . ' <br /><br />';

// Table Header.
echo '<table class="widefat">
	<thead>
  		<tr>
            <th class="row-title">ID</th>
            <th>DateTime</th>
            <th>AuthCode</th>
            <th>Name</th>
            <th>IP</th>
            <th>Medium</th>
            <th>Result</th>
            <th>Amount</th>
            <th>Token</th>
  		</tr>
	</thead>
	<tbody>';

// Loop through the $registrations and fill the table.
foreach($payments as $payment):

    echo'<tr>
            <td class="row-title">' . $payment['id'] . '</td>
            <td>' . $payment['fechahora'] . '</td>
            <td>' . $payment['codautorizacion'] . '</td>
            <td>' . $payment['titular'] . '</td>
            <td>' . $payment['ip'] . '</td>
            <td>' . $payment['tarjeta'] . '</td>
            <td>' . $payment['resultado'] . '</td>
            <td>' . $payment['monto'] . '</td>
            <td>' . $payment['token'] . '</td>
  		</tr>';

endforeach;

// Table Footer.
echo '<tfoot>
  		<tr>
            <th class="row-title">ID</th>
            <th>DateTime</th>
            <th>AuthCode</th>
            <th>Name</th>
            <th>IP</th>
            <th>Medium</th>
            <th>Result</th>
            <th>Amount</th>
            <th>Token</th>
  		</tr>
	</tfoot>
	</tbody>';
echo '</table>';
?>
