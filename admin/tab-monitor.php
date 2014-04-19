<?php
if( !defined( 'ABSPATH' ) ){
    header('HTTP/1.0 403 Forbidden');
    die('No Direct Access Allowed!');
}

/**
 * Prepare Event Names Array
 */
$event_names = array();
$args = array(
    'post_type'   => array( 'events', 'tevents' ),
    'numberposts' => -1
);
$events = get_posts($args);
foreach($events as $event):
    $event_names[$event->ID] = $event->post_title;
endforeach;

// Mark for GC.
unset($events);

// Globalize WPDB and Sigma Events Objects.
global $wpdb, $sigma_events;

$pid = getmypid();

// Construct Registration Table Name.
$payment_table = $wpdb->prefix . $sigma_events->payment_table;
$registration_table = $wpdb->prefix . $sigma_events->registration_table;

// Get the payments.
$payments = $wpdb->get_results(
    "SELECT p.tarjeta, p.processor, p.resultado, p.fechahora, p.token
    FROM $payment_table as p
    ORDER BY fechahora
    ASC " );

/**
 * Sort Payment Records
 */
$paid_records = array();
$notpaid_records = array();
$cancelled_records = array();
$pending_records = array();
foreach($payments as $payment):
    if( 'paid' == $payment->resultado ) {
        $paid_records[$payment->token] = $payment;
    } elseif( 'notpaid' == $payment->resultado ) {
        $notpaid_records[$payment->token] = $payment;
    } elseif( 'cancelled' == $payment->resultado ) {
        $cancelled_records[$payment->token] = $payment;
    } elseif( 'pending' == $payment->resultado ) {
        $pending_records[$payment->token] = $payment;
    }
endforeach;

/**
 * Unset Paid tokens recorded as 'pending', 'cancelled' or 'notpaid'
 */
foreach($paid_records as $token => $paid_record):
    if(isset($pending_records[$token])) unset($pending_records[$token]);
    if(isset($notpaid_records[$token])) unset($notpaid_records[$token]);
    if(isset($cancelled_records[$token])) unset($cancelled_records[$token]);
endforeach;

// Mark for GC.
unset($payments);

// Get the payments.
$registrations = $wpdb->get_results(
    "SELECT r.token, r.eid
    FROM $registration_table as r " );

/**
 * Construct an eid_array.
 */
$eids = array();
foreach($registrations as $registration):
    $eids[$registration->token] = $registration->eid;
endforeach;

// Mark for GC.
unset($registrations);

/**
 * Summary
 */
display_table($paid_records, 'Paid Records Summary', $eids, $event_names);
display_table($pending_records, 'Pending Records Summary', $eids, $event_names);
display_table($notpaid_records, 'Notpaid Records Summary', $eids, $event_names);
display_table($cancelled_records, 'Cancelled Records Summary', $eids, $event_names);

/**
 * Details
 */
display_table_3d($paid_records, 'Paid Record Details', $eids, $event_names);
display_table_3d($pending_records, 'Pending Record Details', $eids, $event_names);
display_table_3d($notpaid_records, 'Notpaid Record Details', $eids, $event_names);
display_table_3d($cancelled_records, 'Cancelled Record Details', $eids, $event_names);

function display_table($records, $name, $eids, $event_names){

/**
 * Initialize Stats Array
 */
$stats = array();
foreach($eids as $eid):
        $stats[$eid]['today']      = 0;
        $stats[$eid]['last_24']    = 0;
        $stats[$eid]['last_48']    = 0;
        $stats[$eid]['last_week']  = 0;
        $stats[$eid]['last_month'] = 0;
        $stats[$eid]['older']      = 0;
        $stats[$eid]['total']      = 0;
endforeach;

/**
 * Fill Stats
 */
$t_now        = current_time('timestamp');
$t_today      = strtotime(date('Y-m-d', $t_now));
$t_last_24    = $t_today - DAY_IN_SECONDS;
$t_last_48    = $t_today - DAY_IN_SECONDS * 2;
$t_last_week  = $t_today - WEEK_IN_SECONDS;
$t_last_month = $t_today - WEEK_IN_SECONDS * 4;

foreach($records as $token => $record):
    $t = strtotime($record->fechahora);
    $eid = isset($eids[$record->token]) ? $eids[$record->token] : '';
    if('' != $eid) {
        if( $t > $t_today )     {
            $stats[$eid]['today']      += 1;
        }

        if( $t_today > $t && $t > $t_last_24 )   {
            $stats[$eid]['last_24']    += 1;
        }

        if( $t_today > $t && $t > $t_last_48 )   {
            $stats[$eid]['last_48']    += 1;
        }

        if( $t_today > $t && $t > $t_last_week ) {
            $stats[$eid]['last_week']  += 1;
        }

        if( $t_today > $t && $t > $t_last_month ){
            $stats[$eid]['last_month'] += 1;
        }

        if( $t < $t_last_month )                 {
            $stats[$eid]['older']      += 1;
        }

        $stats[$eid]['total']          += 1;
    }
endforeach;

echo '<h3>' . $name . '</h3>';

echo '<table class="widefat">
	<thead>
  		<tr>
            <th>Event</th>
            <th>Today</th>
            <th>Last 24H</th>
            <th>Last 48H</th>
            <th>Last Week</th>
            <th>Last Month</th>
            <th>Older</th>
            <th>All</th>
  		</tr>
	</thead>
	<tbody>';

foreach($stats as $eid => $event):
    if(!isset($event_names[$eid])) continue;
    $event_name = $event_names[$eid];

    echo '<tr>
        <td>' . $event_name          . '</td>
        <td>' . $event['today']      . '</td>
        <td>' . $event['last_24']    . '</td>
        <td>' . $event['last_48']    . '</td>
        <td>' . $event['last_week']  . '</td>
        <td>' . $event['last_month'] . '</td>
        <td>' . $event['older']      . '</td>
        <td>' . $event['total']      . '</td>
  		</tr>';

endforeach;

// Table Footer.
echo '<tfoot>
  		<tr>
            <th>Event</th>
            <th>Today</th>
            <th>Last 24H</th>
            <th>Last 48H</th>
            <th>Last Week</th>
            <th>Last Month</th>
            <th>Older</th>
            <th>All</th>
  		</tr>
	</tfoot>
	</tbody>';

echo '</table>';
}

function display_table_3d($records, $name, $eids, $event_names){

/**
 * Initialize Stats Array
 */
$stats = array();
foreach($eids as $eid):
        $stats[$eid]['today']      = array();
        $stats[$eid]['last_24']    = array();
        $stats[$eid]['last_48']    = array();
        $stats[$eid]['last_week']  = array();
        $stats[$eid]['last_month'] = array();
        $stats[$eid]['older']      = array();
        $stats[$eid]['total']      = array();
endforeach;

$processors = array('decidir', 'dineromail', 'cuentadigi', 'ep', 'debugger');

foreach($eids as $eid):
    foreach($processors as $processor):
            $stats[$eid]['today'][$processor]      = 0;
            $stats[$eid]['last_24'][$processor]    = 0;
            $stats[$eid]['last_48'][$processor]    = 0;
            $stats[$eid]['last_week'][$processor]  = 0;
            $stats[$eid]['last_month'][$processor] = 0;
            $stats[$eid]['older'][$processor]      = 0;
            $stats[$eid]['total'][$processor]      = 0;
    endforeach;
endforeach;

/**
 * Fill Stats
 */
$t_now        = current_time('timestamp');
$t_today      = strtotime(date('Y-m-d', $t_now));
$t_last_24    = $t_today - DAY_IN_SECONDS;
$t_last_48    = $t_today - DAY_IN_SECONDS * 2;
$t_last_week  = $t_today - WEEK_IN_SECONDS;
$t_last_month = strtotime(date('Y-m', $t_today));
foreach($records as $token => $record):
    $t = strtotime($record->fechahora);
    $p = $record->processor;
    $eid = isset($eids[$record->token]) ? $eids[$record->token] : '';
    if('' != $eid) {
        if( $t > $t_today )     {
            $stats[$eid]['today'][$p]      += 1;
        }

        if( $t_today > $t && $t > $t_last_24 )   {
            $stats[$eid]['last_24'][$p]    += 1;
        }

        if( $t_today > $t && $t > $t_last_48 )   {
            $stats[$eid]['last_48'][$p]    += 1;
        }

        if( $t_today > $t && $t > $t_last_week ) {
            $stats[$eid]['last_week'][$p]  += 1;
        }

        if( $t_today > $t && $t > $t_last_month ){
            $stats[$eid]['last_month'][$p] += 1;
        }

        if( $t < $t_last_month )                 {
            $stats[$eid]['older'][$p]      += 1;
        }

        $stats[$eid]['total'][$p]          += 1;
    }
endforeach;

echo '<h3>' . $name . '</h3>';

echo '<table class="widefat">
	<thead>
  		<tr>
            <th>Event</th>
            <th>Processor</th>
            <th>Today</th>
            <th>Last 24H</th>
            <th>Last 48H</th>
            <th>Last Week</th>
            <th>Last Month</th>
            <th>Older</th>
            <th>All</th>
  		</tr>
	</thead>
	<tbody>';

foreach($stats as $eid => $event):
    if(!isset($event_names[$eid])) continue;
    $event_name = $event_names[$eid];

    echo'<tr>
        <td>' . $event_name                                . '</td>
        <td>' . processor_table_header()                   . '</td>
        <td>' . processor_table($event['today'])           . '</td>
        <td>' . processor_table($event['last_24'])         . '</td>
        <td>' . processor_table($event['last_48'])         . '</td>
        <td>' . processor_table($event['last_week'])       . '</td>
        <td>' . processor_table($event['last_month'])      . '</td>
        <td>' . processor_table($event['older'])           . '</td>
        <td>' . processor_total($stats, $eid, $processors) . '</td>
  		</tr>';

endforeach;

// Table Footer.
echo '<tfoot>
  		<tr>
            <th>Event</th>
            <th>Processor</th>
            <th>Today</th>
            <th>Last 24H</th>
            <th>Last 48H</th>
            <th>Last Week</th>
            <th>Last Month</th>
            <th>Older</th>
            <th>All</th>
  		</tr>
	</tfoot>
	</tbody>';

echo '</table>';
}

function processor_table_header(){
    $output = ' <table>
                <tr><td>Decidir</td></tr>
                <tr><td>Dineromail</td></tr>
                <tr><td>CuentaDigital</td></tr>
                <tr><td>EasyPlanners</td></tr>
                <tr><td>Debugger</td></tr>
                <tr><td><b>Total</b></td></tr>
                </table>';
    return $output;
}

function processor_table($data){
    $output = '<table>';
        $total = 0;
        foreach($data as $processor):
        $total += $processor;
        $output .= '<tr><td>' . $processor . '</td></tr>';
        endforeach;
        $output .= '<tr><td><b>' . $total . '</b></td></tr>';
    $output .= '</table>';
    return $output;
}

function processor_total($stats, $eid, $processors){
    $output = '<table>';
        $grand_total = 0;
        foreach($processors as $p):
            $total = $stats[$eid]['total'][$p];
            $grand_total += $total;
        $output .= '<tr><td><b>' . $total . '</b></td></tr>';
        endforeach;
        $output .= '<tr><td><b>[ ' . $grand_total . ' ]</b></td></tr>';
    $output .= '</table>';
    return $output;
}
?>
