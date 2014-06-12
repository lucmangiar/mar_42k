<?php
if( !defined( 'ABSPATH' ) ){
    header('HTTP/1.0 403 Forbidden');
    die('No Direct Access Allowed!');
}

$options = get_option('sigma_processor_options');

/**
 * Paypal Settings Section
 */
add_settings_section(
    'processor_settings',
    __('Paypal Settings', 'se'),
    'processor_settings_callback',
    'manage-sigma-processors'
);

function processor_settings_callback(){
    echo 'Paypal Configuration for Sigma Payment Processing';
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
    echo '<input name="sigma_processor_options[paypal][enable_logging]" type="checkbox"
        value="true" ' . checked($options['paypal']['enable_logging'], true, false) . ' >';
    echo ' <label class="description" for="sigma_processor_options[paypal][enable_logging]" >'
        . __('Enable Paypal Logging', 'se') . '</label>';
}

/**
 * Enable IP Check
 */
add_settings_field(
    'enable_ip',
    __('Validate IP', 'se'),
    'enable_ip_callback',
    'manage-sigma-processors',
    'processor_settings',
    $options
);

function enable_ip_callback($options){
    echo '<input name="sigma_processor_options[paypal][enable_ip]" type="checkbox"
        value="true" ' . checked($options['paypal']['enable_ip'], true, false) . ' >';
    echo ' <input name="sigma_processor_options[paypal][ip_address]" type="text"
        value="' . $options['paypal']['ip_address'] . '" class="regular-text" >';
    echo ' <label class="description" for="sigma_processor_options[paypal][enable_ip]" >'
        . __('Restrict POSTs to Paypal IP', 'se') . '</label>';
}

/**
 * Save or Reset Paypal Settings
 */
add_settings_section(
    'save_reset',
    __('Save Settings', 'se'),
    'save_reset_callback',
    'manage-sigma-processors'
);

function save_reset_callback(){
    echo '<input name="sigma_processor_options[save_paypal]"
        type="submit" class="button-primary" value="' . esc_attr__('Save Paypal Settings', 'se') .'" />';
    echo ' <input name="sigma_processor_options[reset_paypal]"
        type="submit" class="button-secondary" value="' . esc_attr__('Reset', 'se') .'" />';
}
?>
