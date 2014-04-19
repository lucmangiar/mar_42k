<?php
if( !defined( 'ABSPATH' ) ){
    header('HTTP/1.0 403 Forbidden');
    die('No Direct Access Allowed!');
}

$options = get_option('sigma_processor_options');

/**
 * EasyPlanners Settings Section
 */
add_settings_section(
    'processor_settings',
    __('EasyPlanners Settings', 'se'),
    'processor_settings_callback',
    'manage-sigma-processors'
);

function processor_settings_callback(){
    echo 'EasyPlanners Configuration for Sigma Payment Processing';
}

/**
 * Method of Transportation
 */
add_settings_field(
    'method',
    __('Transport Mode', 'se'),
    'method_callback',
    'manage-sigma-processors',
    'processor_settings',
    $options
);

function method_callback($options){
    echo '<select name="sigma_processor_options[easyplanners][method]" >';
    echo '<option value="POST" ' . selected($options['easyplanners']['method'], 'POST', false) . ' >POST</option>';
    echo '<option value="GET" ' . selected($options['easyplanners']['method'], 'GET', false) . ' >GET</option>';
    echo '</select>';
    echo ' <label class="description" for="sigma_processor_options[easyplanners][method]" >'
        . __('Transport Method', 'se') . '</label>';
}

/**
 * EasyPlanners URL
 */
add_settings_field(
    'easyplanners_url',
    __('EasyPlanners URL', 'se'),
    'easyplanners_url_callback',
    'manage-sigma-processors',
    'processor_settings',
    $options
);

function easyplanners_url_callback($options){
    echo '<input name="sigma_processor_options[easyplanners][easyplanners_url]" type="text" class="large-text"
        value="' . $options['easyplanners']['easyplanners_url'] . '" >';
}

/**
 * EasyPlanners Password
 */
add_settings_field(
    'password',
    __('EasyPlanners Password', 'se'),
    'password_callback',
    'manage-sigma-processors',
    'processor_settings',
    $options
);

function password_callback($options){
    echo '<input name="sigma_processor_options[easyplanners][password]" type="text" class="large-text"
        value="' . $options['easyplanners']['password'] . '" >';
}

/**
 * Save or Reset EasyPlanners Settings
 */
add_settings_section(
    'save_reset',
    __('Save Settings', 'se'),
    'save_reset_callback',
    'manage-sigma-processors'
);

function save_reset_callback(){
    echo '<input name="sigma_processor_options[save_easyplanners]"
        type="submit" class="button-primary" value="' . esc_attr__('Save EasyPlanners Settings', 'se') .'" />';
    echo ' <input name="sigma_processor_options[reset_easyplanners]"
        type="submit" class="button-secondary" value="' . esc_attr__('Reset', 'se') .'" />';
}
?>
