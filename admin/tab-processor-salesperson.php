<?php
if( !defined( 'ABSPATH' ) ){
    header('HTTP/1.0 403 Forbidden');
    die('No Direct Access Allowed!');
}

$options = get_option('sigma_processor_options');

/**
 * SalesPerson Settings Section
 */
add_settings_section(
    'processor_settings',
    __('SalesPerson Settings', 'se'),
    'processor_settings_callback',
    'manage-sigma-processors'
);

function processor_settings_callback(){
    echo 'SalesPerson Configuration for Sigma Payment Processing';
}

/**
 * Save or Reset SalesPerson Settings
 */
add_settings_section(
    'save_reset',
    __('Save Settings', 'se'),
    'save_reset_callback',
    'manage-sigma-processors'
);

function save_reset_callback(){
    echo '<input name="sigma_processor_options[save_salesperson]"
        type="submit" class="button-primary" value="' . esc_attr__('Save SalesPerson Settings', 'se') .'" />';
    echo ' <input name="sigma_processor_options[reset_salesperson]"
        type="submit" class="button-secondary" value="' . esc_attr__('Reset', 'se') .'" />';
}
?>
