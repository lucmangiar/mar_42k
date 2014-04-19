<?php
if( !defined( 'ABSPATH' ) ){
    header('HTTP/1.0 403 Forbidden');
    die('No Direct Access Allowed!');
}

$options = get_option('sigma_processor_options');

/**
 * Dineromail Settings Section
 */
add_settings_section(
    'processor_settings',
    __('Dineromail Settings', 'se'),
    'processor_settings_callback',
    'manage-sigma-processors'
);

function processor_settings_callback(){
    echo 'Dineromail Configuration for Sigma Payment Processing';
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
    echo '<input name="sigma_processor_options[dineromail][enable_logging]" type="checkbox"
        value="true" ' . checked($options['dineromail']['enable_logging'], true, false) . ' >';
    echo ' <label class="description" for="sigma_processor_options[dineromail][enable_logging]" >'
        . __('Enable Dineromail Logging', 'se') . '</label>';
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
    echo '<input name="sigma_processor_options[dineromail][enable_ip]" type="checkbox"
        value="true" ' . checked($options['dineromail']['enable_ip'], true, false) . ' >';
    echo ' <input name="sigma_processor_options[dineromail][ip_address]" type="text"
        value="' . $options['dineromail']['ip_address'] . '" class="regular-text" >';
    echo ' <label class="description" for="sigma_processor_options[dineromail][enable_ip]" >'
        . __('Restrict POSTs to Dineromail IP', 'se') . '</label>';
}

/**
 * Save or Reset Dineromail Settings
 */
add_settings_section(
    'save_reset',
    __('Save Settings', 'se'),
    'save_reset_callback',
    'manage-sigma-processors'
);

function save_reset_callback(){
    echo '<input name="sigma_processor_options[save_dineromail]"
        type="submit" class="button-primary" value="' . esc_attr__('Save Dineromail Settings', 'se') .'" />';
    echo ' <input name="sigma_processor_options[reset_dineromail]"
        type="submit" class="button-secondary" value="' . esc_attr__('Reset', 'se') .'" />';
}
?>
