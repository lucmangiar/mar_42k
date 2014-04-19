<?php
$options           = get_option('sigma_security_options');
$registration_data = get_option('registration_gate_data');
$confirmation_data = get_option('confirmation_gate_data');

$registration_data['access'] = array(
    '111.222.333.444' => array( 2, 1370950795 ),
    '222.333.444.256' => array( 2, 1370950795 ),
    '221.222.333.434' => array( 4, 1370950795 ),
    '151.222.333.444' => array( 2, 1370950795 ),
    '111.272.333.454' => array( 7, 1370950795 ),
    '181.222.533.444' => array( 2, 1370950795 ),
);

$registration_data['blocked'] = array(
    '011.222.333.444' => array( 2, 1370950795 ),
    '722.333.444.256' => array( 2, 1370950795 ),
);

$confirmation_data['access'] = array(
    '221.222.333.434' => array( 2, 1370950795 ),
    '111.222.333.444' => array( 3, 1370950795 ),
    '222.333.444.256' => array( 2, 1370950795 ),
    '111.272.333.454' => array( 2, 1370950795 ),
    '181.222.533.444' => array( 5, 1370950795 ),
    '151.222.333.444' => array( 2, 1370950795 ),
);

$confirmation_data['blocked'] = array(
    '999.222.333.434' => array( 2, 1370950795 ),
);

$registration_data = get_option('registration_gate_data');
$confirmation_data = get_option('confirmation_gate_data');
$registration_data = clear_expired_entries($registration_data, 'registration', $options);
$confirmation_data = clear_expired_entries($confirmation_data, 'confirmation', $options);

echo '<p>Server Time ' . current_time('mysql') . '</p>';

echo '<div style="width:45%; float:left; margin-right:10px;" >';

// Echo the header.
echo '<h3>Registration Gate Access Data</h3>';
table_header();
// Loop through the $registrations and fill the table.
if(isset($registration_data['access']) && !empty($registration_data['access'])):
    foreach($registration_data['access'] as $ip => $data):
        render_table_body($ip, $data, 'registration', $options);
    endforeach;
endif;
table_footer();

echo '</table>';

// Echo the header.
echo '<h3>Registration Gate Blocked Data</h3>';

table_header();
// Loop through the $registrations and fill the table.
if(isset($registration_data['blocked']) && !empty($registration_data['blocked'])):
    foreach($registration_data['blocked'] as $ip => $data):
        render_table_body($ip, $data, 'registration', $options);
    endforeach;
endif;
table_footer();

echo '</table>';

echo '<h3>Registration Gate Security Policy</h3>';
security_settings('registration', $options);

echo '</div>';

echo '<div style="width:45%; float:left;" >';

// Echo the header.
echo '<h3>Confirmation Gate Access Data</h3>';

table_header();
// Loop through the $registrations and fill the table.
if(isset($confirmation_data['access']) && !empty($confirmation_data['access'])):
    foreach($confirmation_data['access'] as $ip => $data):
        render_table_body($ip, $data, 'confirmation', $options);
    endforeach;
endif;
table_footer();

echo '</table>';

// Echo the header.
echo '<h3>Confirmation Gate Blocked Data</h3>';


table_header();
// Loop through the $registrations and fill the table.
if(isset($confirmation_data['blocked']) && !empty($confirmation_data['blocked'])):
    foreach($confirmation_data['blocked'] as $ip => $data):
        render_table_body($ip, $data, 'confirmation', $options);
    endforeach;
endif;
table_footer();
echo '</table>';

echo '<h3>Confirmation Gate Security Policy</h3>';
security_settings('confirmation', $options);

echo '</div>';

function table_header(){
    // Table Header.
    echo '<table class="widefat">
        <thead>
            <tr>
                <th>IP</th>
                <th>Attempts</th>
                <th>Last Access</th>
                <th>Time to Expire</th>
            </tr>
        </thead>
        <tbody>';
}

function render_table_body($ip, $data, $gate, $options){
    $time_to_keep   = $options[$gate]['time_to_keep'];
    $time_elapsed   = time() - $data[1];
    $time_to_expire = $time_to_keep - $time_elapsed;
    $time_elapsed   = ' (' . $time_elapsed . ') ';
    echo'<tr>
        <td>'  . $ip      . '</td>
        <td>'  . $data[0] . '</td>
        <td>'  . date( 'Y-m-d H:i:s', $data[1]) . $time_elapsed . '</td>
        <td>'  . $time_to_expire . '</td>
        </tr>';
}

function table_footer(){
    // Table Footer.
    echo '<tfoot>
            <tr>
                <th>IP</th>
                <th>Attempts</th>
                <th>Last Access</th>
                <th>Time to Expire</th>
            </tr>
        </tfoot>
        </tbody>';
}

function security_settings($gate, $options){
    echo '<table class="widefat">
        <thead>
            <tr>
                <th>Setting</th>
                <th>Value</th>
            </tr>
        </thead>
        <tbody>';

    foreach($options[$gate] as $setting => $value):
        echo'<tr>
            <td>'  . $setting      . '</td>
            <td>'  . $value . '</td>
            </tr>';
    endforeach;

    echo '<tfoot>
            <tr>
                <th>Setting</th>
                <th>Value</th>
            </tr>
        </tfoot>
        </tbody></table>';
}

function clear_expired_entries($gate_data, $gate, $options){
    $time_to_keep = $options[$gate]['time_to_keep'];
    foreach($gate_data['access'] as $ip => $data):
        $time_elapsed = time() - $data[1];
        if($time_elapsed > $time_to_keep):
            unset($gate_data['access'][$ip]);
        endif;
    endforeach;
    foreach($gate_data['blocked'] as $ip => $data):
        $time_elapsed = time() - $data[1];
        if($time_elapsed > $time_to_keep):
            unset($gate_data['blocked'][$ip]);
        endif;
    endforeach;
    $option = $gate . '_gate_data';
    update_option($option, $gate_data);
    return $gate_data;
}
?>
