<?php
if( !defined( 'ABSPATH' ) ){
    header('HTTP/1.0 403 Forbidden');
    die('No Direct Access Allowed!');
}

$options = get_option('sigma_security_options');

// Registration Gate Security
add_settings_section(
    'registration_gate',
    __('Registration Gate', 'se'),
    'registration_gate_callback',
    'manage-sigma-security');

// Registration Gate Security Text
function registration_gate_callback(){
    _e('Manage Registration Gate Security', 'se');
}

// Time to Keep.
add_settings_field(
    'time_to_keep',
    __('Duration of an IP address memory', 'se'),
    'time_to_keep_callback',
    'manage-sigma-security',
    'registration_gate',
    $options
);

// Allowed Attempts.
add_settings_field(
    'allowed_attempts',
    __('Allowed no of Attempts', 'se'),
    'allowed_attempts_callback',
    'manage-sigma-security',
    'registration_gate',
    $options
);

// Allowed Attempts callback.
function allowed_attempts_callback($options){
    echo ' <input name="sigma_security_options[registration][allowed_attempts]" type="text"
        value="' . $options["registration"]["allowed_attempts"] . '" class="regular-text" >';
    echo ' <label class="description" for="sigma_security_options[registration][allowed_attempts]" >'
        . __('No of attempts to allow before blocking', 'se') . '</label>';
}

// Time to keep callback.
function time_to_keep_callback($options){
    echo ' <input name="sigma_security_options[registration][time_to_keep]" type="text"
        value="' . $options["registration"]["time_to_keep"] . '" class="regular-text" >';
    echo ' <label class="description" for="sigma_security_options[registration][time_to_keep]" >'
        . __('(s) Forget visits count of an IP after this period', 'se') . '</label>';
}

// Time between attempts.
add_settings_field(
    'time_between_attempts',
    __('Minimum Allowed Time Between Attempts', 'se'),
    'time_between_attempts_callback',
    'manage-sigma-security',
    'registration_gate',
    $options
);

// Time between attempts callback.
function time_between_attempts_callback($options){
    echo ' <input name="sigma_security_options[registration][time_between_attempts]" type="text"
        value="' . $options["registration"]["time_between_attempts"] . '" class="regular-text" >';
    echo ' <label class="description" for="sigma_security_options[registration][time_between_attempts]" >'
        . __('Block Visits with Higher Frequency', 'se') . '</label>';
}

// Page To Redirect.
add_settings_field(
    'page_to_redirect',
    __('Redirection Page', 'se'),
    'page_to_redirect_callback',
    'manage-sigma-security',
    'registration_gate',
    $options
);

// Page To Redirect callback.
function page_to_redirect_callback($options){
    echo ' <input name="sigma_security_options[registration][page_to_redirect]" type="text"
        value="' . $options["registration"]["page_to_redirect"] . '" class="regular-text" >';
    echo ' <label class="description" for="sigma_security_options[registration][page_to_redirect]" >'
        . __('Where to redirect when blocked', 'se') . '</label>';
}

// Blocked IP log.
add_settings_field(
    'blocked_ip_log',
    __('Log Location', 'se'),
    'blocked_ip_log_callback',
    'manage-sigma-security',
    'registration_gate',
    $options
);

// Blocked IP log callback.
function blocked_ip_log_callback($options){
    echo ' <input name="sigma_security_options[registration][blocked_ip_log]" type="text"
        value="' . $options["registration"]["blocked_ip_log"] . '" class="regular-text" >';
    echo ' <label class="description" for="sigma_security_options[registration][blocked_ip_log]" >'
        . __('Log filename', 'se') . '</label>';
    echo ' <input name="sigma_security_options[download_blocked_registrations]"
        type="submit" class="button-secondary" value="' . esc_attr__('Download Blocked IP List', 'se') .'" />';
}

/**
 * Confirmation Gate Security
 *
 * Check for evil requests @ confirmation page entrance.
 */
add_settings_section(
    'confirmation_gate',
    __('Confirmation Gate', 'se'),
    'confirmation_gate_callback',
    'manage-sigma-security');

// Confirmation Gate Security Text
function confirmation_gate_callback(){
    _e('Manage Confirmation Gate Security', 'se');
}

// Time to Keep.
add_settings_field(
    'time_to_keep',
    __('Duration of an IP address memory', 'se'),
    'confirmation_time_to_keep_callback',
    'manage-sigma-security',
    'confirmation_gate',
    $options
);

// Allowed Attempts.
add_settings_field(
    'confirmation_allowed_attempts',
    __('Allowed no of Attempts', 'se'),
    'confirmation_allowed_attempts_callback',
    'manage-sigma-security',
    'confirmation_gate',
    $options
);

// Allowed Attempts callback.
function confirmation_allowed_attempts_callback($options){
    echo ' <input name="sigma_security_options[confirmation][allowed_attempts]" type="text"
        value="' . $options["confirmation"]["allowed_attempts"] . '" class="regular-text" >';
    echo ' <label class="description" for="sigma_security_options[confirmation][allowed_attempts]" >'
        . __('No of attempts to allow before blocking', 'se') . '</label>';
}

// Time to keep callback.
function confirmation_time_to_keep_callback($options){
    echo ' <input name="sigma_security_options[confirmation][time_to_keep]" type="text"
        value="' . $options["confirmation"]["time_to_keep"] . '" class="regular-text" >';
    echo ' <label class="description" for="sigma_security_options[confirmation][time_to_keep]" >'
        . __('(s) Forget visits count of an IP after this period', 'se') . '</label>';
}

// Time between attempts.
add_settings_field(
    'confirmation_time_between_attempts',
    __('Minimum Allowed Time Between Attempts', 'se'),
    'confirmation_time_between_attempts_callback',
    'manage-sigma-security',
    'confirmation_gate',
    $options
);

// Time between attempts callback.
function confirmation_time_between_attempts_callback($options){
    echo ' <input name="sigma_security_options[confirmation][time_between_attempts]" type="text"
        value="' . $options["confirmation"]["time_between_attempts"] . '" class="regular-text" >';
    echo ' <label class="description" for="sigma_security_options[confirmation][time_between_attempts]" >'
        . __('Block Visits with Higher Frequency', 'se') . '</label>';
}

// Page To Redirect.
add_settings_field(
    'confirmation_page_to_redirect',
    __('Redirection Page', 'se'),
    'confirmation_page_to_redirect_callback',
    'manage-sigma-security',
    'confirmation_gate',
    $options
);

// Page To Redirect callback.
function confirmation_page_to_redirect_callback($options){
    echo ' <input name="sigma_security_options[confirmation][page_to_redirect]" type="text"
        value="' . $options["confirmation"]["page_to_redirect"] . '" class="regular-text" >';
    echo ' <label class="description" for="sigma_security_options[confirmation][page_to_redirect]" >'
        . __('Where to redirect when blocked', 'se') . '</label>';
}

// Blocked IP log.
add_settings_field(
    'confirmation_blocked_ip_log',
    __('Log Location', 'se'),
    'confirmation_blocked_ip_log_callback',
    'manage-sigma-security',
    'confirmation_gate',
    $options
);

// Blocked IP log callback.
function confirmation_blocked_ip_log_callback($options){
    echo ' <input name="sigma_security_options[confirmation][blocked_ip_log]" type="text"
        value="' . $options["confirmation"]["blocked_ip_log"] . '" class="regular-text" >';
    echo ' <label class="description" for="sigma_security_options[confirmation][blocked_ip_log]" >'
        . __('Log filname', 'se') . '</label>';
    echo ' <input name="sigma_security_options[download_blocked_confirmation]"
        type="submit" class="button-secondary" value="' . esc_attr__('Download Blocked IP List', 'se') .'" />';
}

// Save | Reset.
add_settings_section(
    'save_reset',
    __('Save Security Settings', 'se'),
    'save_reset_callback',
    'manage-sigma-security');

function save_reset_callback(){
    echo '<input name="sigma_security_options[save_security]"
        type="submit" class="button-primary" value="' . esc_attr__('Save Security Settings', 'se') .'" />';
    echo ' <input name="sigma_security_options[reset_security]"
        type="submit" class="button-secondary" value="' . esc_attr__('Reset Settings', 'se') .'" />';
    echo ' <input name="sigma_security_options[reset_ip_lists]"
        type="submit" class="button-secondary" value="' . esc_attr__('Reset IP Lists', 'se') .'" />';
}
?>
