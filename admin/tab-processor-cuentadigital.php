<?php
if( !defined( 'ABSPATH' ) ){
    header('HTTP/1.0 403 Forbidden');
    die('No Direct Access Allowed!');
}
?>
<?php
$options = get_option('sigma_processor_options');
$options1 = get_option('sigma_cuentadigital_acno');

/**
 * CuentaDigital Settings Section
 */
add_settings_section(
    'processor_settings',
    __('CuentaDigital Settings', 'se'),
    'processor_settings_callback',
    'manage-sigma-processors'
);

function processor_settings_callback(){
    echo 'CuentaDigital Configuration for Sigma Payment Processing';
}

/**
 * Enable Sandbox Field
 */
add_settings_field(
    'enable_sandbox',
    __('Enable Sandbox Mode', 'se'),
    'enable_sandbox_callback',
    'manage-sigma-processors',
    'processor_settings',
    $options
);

function enable_sandbox_callback($options){
    echo '<input name="sigma_processor_options[cuentadigital][enable_sandbox]" type="checkbox"
        value="true" ' . checked($options['cuentadigital']['enable_sandbox'], true, false) . ' >';
    echo ' <label class="description" for="sigma_processor_options[cuentadigital][enable_sandbox]" >'
        . __('Enable Sandbox for Testing', 'se') . '</label>';
}

/**
 * SandBox Report URL
 */
add_settings_field(
    'sandbox_report_url',
    __('SandBox Report URL', 'se'),
    'sandbox_report_url_callback',
    'manage-sigma-processors',
    'processor_settings',
    $options
);

function sandbox_report_url_callback($options){
    echo '<input name="sigma_processor_options[cuentadigital][sandbox_report_url]" type="text" class="large-text"
        value="' . $options['cuentadigital']['sandbox_report_url'] . '" >';
}

/**
 * Production Report URL
 */
add_settings_field(
    'production_report_url',
    __('Production Report URL', 'se'),
    'production_report_url_callback',
    'manage-sigma-processors',
    'processor_settings',
    $options
);

function production_report_url_callback($options){
    echo '<input name="sigma_processor_options[cuentadigital][production_report_url]" type="text" class="large-text"
        value="' . $options['cuentadigital']['production_report_url'] . '" >';
}

/**
 * Authorization Code
 */
add_settings_field(
    'auth_code',
    __('Authorization Code', 'se'),
    'auth_code_callback',
    'manage-sigma-processors',
    'processor_settings',
    $options
);

function auth_code_callback($options){
    echo '<input name="sigma_processor_options[cuentadigital][auth_code]" type="text" class="large-text"
        value="' . $options['cuentadigital']['auth_code'] . '" >';
}





/**
 * Account Number
 */
add_settings_field(
    'account_no',
    __('Account number', 'se'),
    'account_no_callback',
    'manage-sigma-processors',
    'processor_settings',
    $options
);

function account_no_callback($options){
    echo '<input name="sigma_processor_options[cuentadigital][acno]" id="account_no_cu" type="text" class="regular-text"
        value="' . $options['cuentadigital']['acno']. '" >';
}

/**
 * Enable Logging
 */
add_settings_field(
    'enable_logging',
    __('Enable Logging', 'se'),
    'enable_logging_callback',
    'manage-sigma-processors',
    'processor_settings',
    $options
);

function enable_logging_callback($options){
    echo '<input name="sigma_processor_options[cuentadigital][enable_logging]" type="checkbox"
        value="true" ' . checked($options['cuentadigital']['enable_logging'], true, false) . ' >';
    echo ' <label class="description" for="sigma_processor_options[cuentadigital][enable_logging]" >'
        . __('Log all data related to CuentaDigital Processor', 'se') . '</label>';
}

/**
 * Cron Interval
 */
add_settings_field(
    'cron_interval',
    __('Cron Interval', 'se'),
    'cron_interval_callback',
    'manage-sigma-processors',
    'processor_settings',
    $options
);

function cron_interval_callback($options){
    echo '<input name="sigma_processor_options[cuentadigital][cron_interval]" type="text" class="regular-text"
        value="' . $options['cuentadigital']['cron_interval'] . '" >';
    echo ' <label class="description" for="sigma_processor_options[cuentadigital][cron_interval]" >'
        . __('In Seconds', 'se') . '</label>';
}

/**
 * Save or Reset CuentaDigital Settings
 */
add_settings_section(
    'save_reset',
    __('Save Settings', 'se'),
    'save_reset_callback',
    'manage-sigma-processors'
);

function save_reset_callback(){
    echo '<input name="sigma_processor_options[save_cuentadigital]"
        type="submit" class="button-primary" value="' . esc_attr__('Save CuentaDigital Settings', 'se') .'" />';
    echo ' <input name="sigma_processor_options[reset_cuentadigital]"
        type="submit" class="button-secondary" value="' . esc_attr__('Reset', 'se') .'" />';
}

/**
 * CuentaDigital Utilities
 */
add_settings_section(
    'cuentadigital_utilities',
    __('CuentaDigital Utilities', 'se'),
    'cuentadigital_utilities_callback',
    'manage-sigma-processors'
);

function cuentadigital_utilities_callback(){
    $options = get_option('sigma_processor_options');
	echo  $options['cuentadigital']['report_date'];
    if($options['cuentadigital']['enable_sandbox']):
        $report_url = $options['cuentadigital']['sandbox_report_url'];
    else:
        $report_url = $options['cuentadigital']['production_report_url'];
    endif;

    echo '<input name="sigma_processor_options[cuentadigital][report_url]" type="text" class="regular-text"
        value="' . $report_url . '" >';
    echo ' <input name="sigma_processor_options[cuentadigital][report_date]" id="reportdate"
        type="text" class="regular-text" value="' . $options['cuentadigital']['report_date'] .'" />';
    echo ' <input name="sigma_processor_options[cuentadigital_report]"
        type="submit" class="button-primary" value="' . esc_attr__('CuentaDigital Report', 'se') .'" />';

    if($options['cuentadigital']['output_report']):
        $options['cuentadigital']['output_report'] = false;

        global $sigma_events;
        $sigma_events->misc['cuentadigital_report'] = false;
        update_option('sigma_processor_options', $options);

        $records = $sigma_events->payments_cuentadigital->get_data_array(
            $report_url,
            $options['cuentadigital']['report_date']
        );

        echo '<p>' . $report_url . '</p>';

        echo '<table class="widefat">
            <thead>
                <tr>
                    <th>Payment date</th>
                    <th>Transaction Time</th>
                    <th>Amount</th>
                    <th>Net Amount</th>
                    <th>Fee</th>
                    <th>BarCode</th>
                    <th>Token</th>
                    <th>Payment Method</th>
                    <th>Index</th>
                    <th>Details</th>
                </tr>
            </thead>
            <tbody>';

        // Loop through the $registrations and fill the table.
        foreach($records as $record):
            // Tracker Link
            $tracker_link = '<a target="blank" href="' .
                get_home_url() . '/sigma-events/tracker/?sigma_token=' . $record[6]
            . '" >Details</a>';

            echo'<tr>
                <td>' . date('Y-m-d', strtotime($record[0])) . '</td>
                <td>' . date('H:i:s', strtotime($record[1])) . '</td>
                <td>' . $record[2] . '</td>
                <td>' . $record[3] . '</td>
                <td>' . $record[4] . '</td>
                <td>' . $record[5] . '</td>
                <td>' . $record[6] . '</td>
                <td>' . $record[7] . '</td>
                <td>' . $record[8] . '</td>
                <td>' . $tracker_link . '</td>
                <tr>';
        endforeach;

        // Table Footer.
        echo '<tfoot>
                <tr>
                    <th>Payment date</th>
                    <th>Transaction Time</th>
                    <th>Amount</th>
                    <th>Net Amount</th>
                    <th>Fee</th>
                    <th>BarCode</th>
                    <th>Token</th>
                    <th>Payment Method</th>
                    <th>Index</th>
                    <th>Details</th>
                </tr>
            </tfoot>
            </tbody>';

        echo '</table>';

        echo '<br /><br />';
        echo ' <input name="sigma_processor_options[process_cuentadigital_report]"
            type="submit" class="button-primary" value="' . esc_attr__('Process CuentaDigital Report', 'se') .'" />';

    endif;
}

/**
 * CuentaDigital Cron Daemon
 */
add_settings_section(
    'cron_daemon',
    __('CuentaDigital Cron Daemon', 'se'),
    'cron_daemon_callback',
    'manage-sigma-processors'
);

function cron_daemon_callback(){
    global $sigma_events;
    $cron_details = $sigma_events->cron->get_cuentadigital_schedule();
    echo $cron_details;
}

/**
 * Download CuentaDigital Logs
 */
add_settings_section(
    'download_logs',
    __('Download Logs', 'se'),
    'download_logs_callback',
    'manage-sigma-processors'
);

function download_logs_callback(){
    echo '<input name="sigma_processor_options[cuentadigital_logs]"
        type="submit" class="button-primary" value="' . esc_attr__('Data Log', 'se') .'" />';
    echo ' <input name="sigma_processor_options[cuentadigital_logs]"
        type="submit" class="button-secondary" value="' . esc_attr__('Error Log', 'se') .'" />';
    echo ' <input name="sigma_processor_options[cuentadigital_logs]"
        type="submit" class="button-secondary" value="' . esc_attr__('Cron Log', 'se') .'" />';
}

?>
