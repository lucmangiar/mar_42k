<?php
if( !defined( 'ABSPATH' ) ){
    header('HTTP/1.0 403 Forbidden');
    die('No Direct Access Allowed!');
}

$options = get_option('sigma_processor_options');

/**
 * Sigma Settings Section
 */
add_settings_section(
    'processor_settings',
    __('Sigma Settings', 'se'),
    'processor_settings_callback',
    'manage-sigma-processors'
);

function processor_settings_callback(){
    echo 'Sigma Configuration for Sigma Payment Processing';
}

/**
 * Save or Reset Sigma Settings
 */
add_settings_section(
    'save_reset',
    __('Save Settings', 'se'),
    'save_reset_callback',
    'manage-sigma-processors'
);

function save_reset_callback(){
    echo '<input name="sigma_processor_options[save_sigma]"
        type="submit" class="button-primary" value="' . esc_attr__('Save Sigma Settings', 'se') .'" />';
    echo ' <input name="sigma_processor_options[reset_sigma]"
        type="submit" class="button-secondary" value="' . esc_attr__('Reset', 'se') .'" />';
}
?>
