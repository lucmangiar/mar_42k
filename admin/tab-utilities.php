<?php
if( !defined( 'ABSPATH' ) ){
        header('HTTP/1.0 403 Forbidden');
        die('No Direct Access Allowed!');
}

add_settings_section(
    'download_data',
    __('Download Registration Data', 'se'),
    'download_data_callback',
    'manage-sigma-events');

add_settings_section(
    'post_data',
    __('Download Payment Processor POSTed Data', 'se'),
    'post_data_callback',
    'manage-sigma-events');

add_settings_section(
    'log_data',
    __('Download Payment Processor Logs', 'se'),
    'log_data_callback',
    'manage-sigma-events');

add_settings_section(
    'delete_registrations',
    __('Delete Registration Records', 'se'),
    'delete_registrations_callback',
    'manage-sigma-events');

function download_data_callback(){
    echo '<table>';
    echo '<tr>';

    echo '<td>';
    echo '<select name="sigma_options[event_id]" >';
    echo '<option value="all" >All Events</option>';
    $args = array(
        'post_type'   => array( 'events', 'tevents' ),
        'numberposts' => -1
    );
    $events = get_posts($args);
    foreach($events as $event):
        $event_id = $event->ID;
        echo '<option value="' . $event_id . '" >' . $event->post_title . '</option>';
    endforeach;
    echo '</select>';
    echo '</td>';
    echo '<td>';
    echo ' Delimiter <input name="sigma_options[delimiter]"
            type="text" class="small-text" value="^" />';
    echo '</td>';
    echo '<td>';
    echo ' Enclosure <input name="sigma_options[enclosure]"
            type="text" class="small-text" value="&quot;" />';
    echo '</td>';

    echo '<td>';
    echo '<input name="sigma_options[download_registration_data]"
            type="submit" class="button-primary" value="' . esc_attr__('Download Registration Data', 'se') .'" />';
    echo '</td>';

    echo '</tr>';
    echo '</table>';
}

function post_data_callback(){
    echo '<table>';
    echo '<tr>';

    echo '<td>';
    echo '<select name="sigma_options[payment_processor]" >';
    echo '<option value="all" >All Processors</option>';
        echo '<option value="decidir" >Decidir</option>';
        echo '<option value="dineromail" >Dineromail</option>';
        echo '<option value="debugger" >Sigma Debugger</option>';
        echo '<option value="ep" >EP</option>';
        echo '<option value="cuentadigital" >CuentaDigital</option>';
    echo '</select>';
    echo '</td>';

    echo '<td>';
    echo '<input name="sigma_options[download_payment_data]"
            type="submit" class="button-primary" value="' . esc_attr__('Download Payment Data', 'se') .'" />';
    echo '</td>';

    echo '</tr>';
    echo '</table>';
}

function log_data_callback(){
    echo '<table>';
    echo '<tr>';

    echo '<td>';
    echo '<select name="sigma_options[event_processor]" >';
        echo '<option value="decidir" >Decidir</option>';
        echo '<option value="dineromail" >Dineromail</option>';
        echo '<option value="sigma" >Sigma Debugger</option>';
        echo '<option value="ep" >EP</option>';
        echo '<option value="cuentadigital" >CuentaDigital</option>';
    echo '</select>';
    echo '</td>';

    echo '<td>';
    echo '<select name="sigma_options[log_type]" >';
        echo '<option value="error" >Error</option>';
        echo '<option value="data" >Data</option>';
    echo '</select>';
    echo '</td>';

    echo '<td>';
    echo '<input name="sigma_options[payment_processor_logs]"
            type="submit" class="button-primary" value="' . esc_attr__('Download Log', 'se') .'" />';
    echo '</td>';

    echo '</tr>';
    echo '</table>';
}

add_settings_section(
    'sql_queries',
    __('SQL Queries', 'se'),
    'sql_queries_callback',
    'manage-sigma-events');

function sql_queries_callback(){
	 global $wpdb;
        $table_name     = $wpdb->prefix . $registration_table;

    $options = get_option('sigma_options');

    if(!isset($options['sql_query']))
        $options['sql_query'] = '';

    echo '<table class="sigma-100">';
    echo '<tr>';

    echo '<td>';
    echo '<input type="text" class="large-text" name="sigma_options[sql_query]" value="' . $options['sql_query'] . '" >';
    echo '</td>';

    echo '<td>';
    echo '<input name="sigma_options[query_database]"
            type="submit" class="button-primary" value="' . esc_attr__('Execute Query', 'se') .'" />';
    echo '</td>';

    echo '</tr>';

    echo '<tr><td colspan="2">';
    $query = sigma_sanitize_query($options['sql_query']);
    if(!$query):
        echo ' Invalid Query. Try Again. ';
    else:
        $r = sigma_display_query($query);
        if($r):
            echo $r;
        else:
            echo ' Invalid Query. Try Again. ';
        endif;
    endif;
	

    $query = "select eid, count(*) FROM ".$table_name." where paid = 'paid' group by eid";
    echo sigma_display_query($query);

    $query = "select eid, paid, count(*) FROM ".$table_name." group by eid, paid";
    echo sigma_display_query($query);

    echo '</td></tr>';

    echo '</table>';
}

function delete_registrations_callback(){
    echo '<table class="form-table">';

    // Select the event
    echo '<tr>';
    echo '<td><label>Select the Event</label></td>';
    echo '<td><select name="sigma_options[delete_event_id]" >';
    echo '<option value="none" >Select Event</option>';
    $args = array(
        'post_type'   => array( 'events', 'tevents' ),
        'numberposts' => -1
    );
    $events = get_posts($args);
    foreach($events as $event):
        $event_id = $event->ID;
        echo '<option value="' . $event_id . '" >' . $event->post_title . '</option>';
    endforeach;
    echo '</select></td>';
    echo '</tr>';

    // Select the number of days
    echo '<tr>';
    echo '<td><label>Older than</label></td>';
    echo '<td><input name="sigma_options[deletion_days]" value="60" type="text" class="small-text" >';
    echo ' days</td>';
    echo '</tr>';

    // Deletion Criteria
    echo '<tr>';
    echo '<td><label>Deletion Criterion</label></td>';
    echo '<td><select name="sigma_options[deletion_criterion]" >';
    echo '<option value="none" >Select Criterion</option>';
        echo '<option value="epaid" >All Except Paid</option>';
        echo '<option value="pending" >Only Pending</option>';
        echo '<option value="notpaid" >Only Not Paid</option>';
        echo '<option value="cancelled" >Only Cancelled</option>';
        echo '<option value="null" >Only Null</option>';
    echo '</select></td>';
    echo '</tr>';

    // Submit Button
    echo '<tr>';
    echo '<td></td><td><input name="sigma_options[delete_registration_records]"
        type="submit" class="button-primary" value="' . esc_attr__('Delete Registration Records', 'se') .'" /></td>';
    echo '</tr>';
    echo '</table>';
}

function sigma_sanitize_query($query){
    $query = str_replace(';', '', $query);
    if( false !== strpos($query, 'update')):
        echo ' Update queries ain\'t allowed ';
        return false;
    elseif( false !== strpos($query, 'delete')):
        echo ' Delete queries ain\'t allowed ';
        return false;
    elseif( false !== strpos($query, 'join')):
        return false;
    else:
        return $query;
    endif;
}

function sigma_display_query($query){
    global $wpdb;
    $results   = $wpdb->get_results($query, ARRAY_A);

    if(!$results)
        return false;

    $output  = '<div class="sigma-sql-query" >';
    $output .= '<h3>' . $query . '</h3>';
    $output .= get_table_header($results[0]);
    $output .= get_table_body($results);
    $output .= get_table_footer($results[0]);
    $output .= '</div>';
    return $output;
}

function get_table_header($row){
    $header  = '<table class="widefat">
                <thead>
                <tr>';

    foreach( $row as $field => $value ):
        $header .= '<th>' . $field . '</th>';
    endforeach;

    $header .= '</tr>
                </thead>
                <tbody>';

    return $header;
}

function get_table_body($data){
    $body  = '';
    foreach($data as $row ):
        $body .= '<tr>';
        foreach( $row as $field => $value ):
            $body .= '<td>' . $value . '</td>';
        endforeach;
        $body .= '</tr>';
    endforeach;

    return $body;
}

function get_table_footer($row){
    $footer  = '<tfoot>
                <tr>';

    foreach( $row as $field => $value ):
        $footer .= '<th>' . $field . '</th>';
    endforeach;

    $footer .= '</tr>
                </tfoot>
                </tbody></table>';

    return $footer;
}
?>
