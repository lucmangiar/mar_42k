<?php
if( !defined( 'ABSPATH' ) ){
    header('HTTP/1.0 403 Forbidden');
    die('No Direct Access Allowed!');
}

// Globalize WPDB and Sigma Events Objects.
global $wpdb, $sigma_events;

// Construct Registration Table Name.
$registration_table = $wpdb->prefix . $sigma_events->registration_table;

// Get Recent Registrations from the Database.
$registrations = $wpdb->get_results(
    "SELECT id, fname, lname, token, reg_time, eid, extra_items, country, ip, medium, paid, amount, seq_no
    FROM $registration_table
    ORDER BY id
    DESC
    LIMIT 50
    ", ARRAY_A );

// Echo the header.
echo '<h3>Recent Registrations</h3> Local Time: ' . current_time('mysql') . ' <br /><br />';

// Table Header.
echo '<table class="widefat">
	<thead>
  		<tr>
            <th class="row-title">ID</th>
            <th>FName</th>
            <th>LName</th>
            <th>Reg Time</th>
            <th>EID</th>
            <th>Extra<br/> Items</th>
            <th>Country</th>
            <th>IP</th>
            <th>Medium</th>
            <th>Paid</th>
            <th>Amount</th>
            <th>Seq No</th>
            <th>Token</th>
            <th>Details (Token)</th>
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

    if( 4 < strlen($registration['extra_items']) ):
        $extra_items = '';
        $items = unserialize( $registration['extra_items'] );
        foreach( $items as $item ):
            $extra_items .= $item[2] . '<br />';
        endforeach;
        $registration['extra_items'] = $extra_items;
    endif;

    echo'<tr>
        <td class="row-title">' . $registration['id']          . '</td>
        <td>'                   . $registration['fname']       . '</td>
        <td>'                   . $registration['lname']       . '</td>
        <td>'                   . $registration['reg_time']    . '</td>
        <td>'                   . $registration['eid']         . '</td>
        <td>'                   . $registration['extra_items'] . '</td>
        <td>'                   . $registration['country']     . '</td>
        <td>'                   . $registration['ip']          . '</td>
        <td>'                   . $registration['medium']      . '</td>
        <td>'                   . $registration['paid']        . '</td>
        <td>'                   . $registration['amount']      . '</td>
        <td>'                   . $registration['seq_no']       . '</td>
        <td>'                   . $registration['token']       . '</td>
        <td>'                   . $tracker_link . '</td>
  		</tr>';
endforeach;

// Table Footer.
echo '<tfoot>
  		<tr>
            <th class="row-title">ID</th>
            <th>FName</th>
            <th>LName</th>
            <th>Reg Time</th>
            <th>EID</th>
            <th>Extra<br/> Items</th>
            <th>Country</th>
            <th>IP</th>
            <th>Medium</th>
            <th>Paid</th>
            <th>Amount</th>
            <th>Seq No</th>
            <th>Token</th>
            <th>Details (Token)</th>
  		</tr>
	</tfoot>
	</tbody>';

echo '</table>';
?>
