<?php
if( !defined( 'ABSPATH' ) ){
    header('HTTP/1.0 403 Forbidden');
    die('No Direct Access Allowed!');
}

$options = get_option('sigma_options');

// Settings for Tracker.
add_settings_section(
    'tracker_settings',
    __('Payment Tracker', 'se'),
    'tracker_settings_callback',
    'manage-sigma-events');

// Settings for Tracker Text
function tracker_settings_callback(){
    _e('Configure Tracker', 'se');
}

// Settings for Security Module.
add_settings_section(
    'security_module',
    __('Security Module', 'se'),
    'security_module_callback',
    'manage-sigma-events');

// Settings for Tracker Text
function security_module_callback(){
    _e('Configure Security Module', 'se');
}

// Settings for Forget Code.
add_settings_section(
    'forget_code',
    __('Forget Code Page', 'se'),
    'forget_code_callback',
    'manage-sigma-events');

// Forget Code Text
function forget_code_callback(){
    _e('Configure Forget Code Security', 'se');
}

// Enable Tracker GET.
add_settings_field(
    'enable_tracker_get',
    __('Use Tracker GET', 'se'),
    'enable_tracker_get_callback',
    'manage-sigma-events',
    'tracker_settings',
    $options
);

// Enable Tracker GET Callback
function enable_tracker_get_callback($options){
    echo '<input name="sigma_options[enable_tracker_get]" type="checkbox"
        value="true" ' . checked($options['enable_tracker_get'], true, false) . ' >';
    echo ' <label class="description" for="sigma_options[enable_tracker_get]" >'
        . __('Use GET method for the tracker.', 'se') . '</label>';
}

// Enable Tracker POST.
add_settings_field(
    'enable_tracker_post',
    __('Use Tracker POST', 'se'),
    'enable_tracker_post_callback',
    'manage-sigma-events',
    'tracker_settings',
    $options
);

// Enable Tracker GET Callback
function enable_tracker_post_callback($options){
    echo '<input name="sigma_options[enable_tracker_post]" type="checkbox"
        value="true" ' . checked($options['enable_tracker_post'], true, false) . ' >';
    echo ' <label class="description" for="sigma_options[enable_tracker_post]" >'
        . __('Use POST method for the tracker.', 'se') . '</label>';
}

// Enable Security Module.
add_settings_field(
    'enable_security',
    __('Sigma Security Module', 'se'),
    'enable_security_callback',
    'manage-sigma-events',
    'security_module',
    $options
);

// Enable Security Module Callback
function enable_security_callback($options){
    echo '<input name="sigma_options[enable_security_module]" type="checkbox"
        value="true" ' . checked($options['enable_security_module'], true, false) . ' >';
    echo ' <label class="description" for="sigma_options[enable_security_module]" >'
        . __('Enable Security Module.', 'se') . '</label>';
}

// Enable Forget Code Captcha.
add_settings_field(
    'enable_forget_code_captcha',
    __('Forget Code Captcha', 'se'),
    'enable_forget_code_captcha_callback',
    'manage-sigma-events',
    'forget_code',
    $options
);

// Enable Forget Code Captcha Callback
function enable_forget_code_captcha_callback($options){
    echo '<input name="sigma_options[enable_forget_code_captcha]" type="checkbox"
        value="true" ' . checked($options['enable_forget_code_captcha'], true, false) . ' >';
    echo ' <label class="description" for="sigma_options[enable_forget_code_captcha]" >'
        . __('Enable Really Simple Captcha.', 'se') . '</label>';
}

// Enable Forget Code Email.
add_settings_field(
    'enable_forget_code_email',
    __('Forget Code Email', 'se'),
    'enable_forget_code_email_callback',
    'manage-sigma-events',
    'forget_code',
    $options
);

// Enable Forget Code Email Callback
function enable_forget_code_email_callback($options){
    echo '<input name="sigma_options[enable_forget_code_email]" type="checkbox"
        value="true" ' . checked($options['enable_forget_code_email'], true, false) . ' >';
    echo ' <label class="description" for="sigma_options[enable_forget_code_email]" >'
        . __('Enable Email instead Display.', 'se') . '</label>';
}


// Save | Reset.
add_settings_section(
    'save_reset',
    __('Save Settings', 'se'),
    'save_reset_callback',
    'manage-sigma-events');

function save_reset_callback(){
    echo '<input name="sigma_options[save_advanced]"
        type="submit" class="button-primary" value="' . esc_attr__('Save Advanced Settings', 'se') .'" />';
    echo ' <input name="sigma_options[reset_advanced]"
        type="submit" class="button-secondary" value="' . esc_attr__('Reset', 'se') .'" />';
}
?>
