<?php
if( !defined( 'ABSPATH' ) ){
        header('HTTP/1.0 403 Forbidden');
        die('No Direct Access Allowed!');
}

$options = get_option('sigma_options');

// Email Sender.
add_settings_section(
    'email_sender',
    __('Email Sender', 'se'),
    'email_sender_callback',
    'manage-sigma-events');

// Settings sections for User Emails.
add_settings_section(
    'user_emails',
    __('User Emails', 'se'),
    'user_emails_callback',
    'manage-sigma-events');

// Settings sections for Admin Emails.
add_settings_section(
    'admin_emails',
    __('Admin Emails', 'se'),
    'admin_emails_callback',
    'manage-sigma-events');

// Settings sections for Organizer Emails.
add_settings_section(
    'organizer_emails',
    __('Organizer Emails', 'se'),
    'organizer_emails_callback',
    'manage-sigma-events');

// Settings sections for Developer Emails.
add_settings_section(
    'developer_emails',
    __('Developer Emails', 'se'),
    'developer_emails_callback',
    'manage-sigma-events');

// Email Sender Text
function email_sender_callback(){
    _e('Configure Email Sender', 'se');
}

// User Email Section Text
function user_emails_callback(){
    _e('Configure User Email Settings', 'se');
}

// Admin Email Section Text
function admin_emails_callback(){
    _e('Configure Admin Email Settings', 'se');
}

// Organizer Email Section Text
function organizer_emails_callback(){
    _e('Configure Organizer Email Settings', 'se');
}

// Developer Email Section Text
function developer_emails_callback(){
    _e('Configure Developer Email Settings', 'se');
}

// Send Name
add_settings_field( 
    'send_name',
    __('Sender Name', 'se'),
    'send_name_callback',
    'manage-sigma-events',
    'email_sender',
    $options
);

// Send Name Callback
function send_name_callback($options){
    echo '<input name="sigma_options[send_name]" type="text"
        value="' . $options["send_name"] . '" class="regular-text" >';
    echo '<p class="description" >' . __('Email Senders Name', 'se') . '</p>';
}

// Send Email
add_settings_field( 
    'send_email',
    __('Sender Email', 'se'),
    'send_email_callback',
    'manage-sigma-events',
    'email_sender',
    $options
);

// Send Email Callback
function send_email_callback($options){
    echo '<input name="sigma_options[send_email]" type="text"
        value="' . $options["send_email"] . '" class="regular-text" >';
    echo '<p class="description" >' . __('Email Senders Email', 'se') . '</p>';
}

// Email User
add_settings_field( 
    'email_user',
    __('User Email', 'se'),
    'email_user_callback',
    'manage-sigma-events',
    'user_emails',
    $options
);

// Email User Callback
function email_user_callback($options){
    echo '<input name="sigma_options[email_user]" type="checkbox"
        value="true" ' . checked($options['email_user'], true, false) . ' >';
    echo ' <label class="description" for="sigma_options[email_user]" >'
        . __('Send an email to the user for each Approved and Rejected payment 
        <br />once the Decidir POST is received. Using the templates defined by 
        <br />the events admin page.', 'se') . '</label>';
}

// User Products Email
add_settings_field( 
    'user_products_email',
    __('User Products Email', 'se'),
    'user_products_email_callback',
    'manage-sigma-events',
    'user_emails',
    $options
);

// User Products Email Callback
function user_products_email_callback($options){
    echo '<input name="sigma_options[user_products_email]" type="checkbox"
        value="true" ' . checked($options['user_products_email'], true, false) . ' >';
    echo ' <label class="description" for="sigma_options[user_products_email]" >'
        . __('Send an email to the user for each Approved product. 
        <br />once the Decidir POST is received. Using the templates defined by 
        <br />the products admin page.', 'se') . '</label>';
}

// Admin Email
add_settings_field( 
    'admin_email',
    __('Admin Email Address', 'se'),
    'admin_email_callback',
    'manage-sigma-events',
    'admin_emails',
    $options
);

// Admin Email Callback
function admin_email_callback($options){
    echo '<input name="sigma_options[admin_email]" type="text"
        value="' . $options["admin_email"] . '" class="regular-text" >';
    echo '<p class="description" >' . __('Admin Email', 'se') . '</p>';
}

// Enable Admin Email
add_settings_field( 
    'enable_admin_email',
    __('Enable Admin Email', 'se'),
    'enable_admin_email_callback',
    'manage-sigma-events',
    'admin_emails',
    $options
);

// Enable Admin Email Callback
function enable_admin_email_callback($options){
    echo '<input name="sigma_options[enable_admin_email]" type="checkbox"
        value="true" ' . checked($options['enable_admin_email'], true, false) . ' >';
    echo ' <label class="description" for="sigma_options[enable_admin_email]" >'
        . __('Send an email to the admin for each Approved or Rejected payment 
        <br />once the Decidir POST is received. This is a raw data array of POST 
        <br />content received by Decidir and registration data about the user.', 'se') . '</label>';
}

// Enable POST Admin Email
add_settings_field( 
    'enable_post_admin_email',
    __('Enable POST Admin Email', 'se'),
    'enable_post_admin_email_callback',
    'manage-sigma-events',
    'admin_emails',
    $options
);

// Enable POST Admin Email Callback
function enable_post_admin_email_callback($options){
    echo '<input name="sigma_options[enable_post_admin_email]" type="checkbox"
        value="true" ' . checked($options['enable_post_admin_email'], true, false) . ' >';
    echo ' <label class="description" for="sigma_options[enable_post_admin_email]" >'
        . __('Send an email to the admin for each received POST from Decidir 
        <br />regardless of the validity of the POST. This is a raw POST content email 
        <br />without any processing.', 'se') . '</label>';
}

// Enable Debug Admin Email
add_settings_field( 
    'enable_debug_admin_email',
    __('Enable Debug Admin Email', 'se'),
    'enable_debug_admin_email_callback',
    'manage-sigma-events',
    'admin_emails',
    $options
);

// Enable Debug Admin Email Callback
function enable_debug_admin_email_callback($options){
    echo '<input name="sigma_options[enable_debug_admin_email]" type="checkbox"
        value="true" ' . checked($options['enable_debug_admin_email'], true, false) . ' >';
    echo ' <label class="description" for="sigma_options[enable_debug_admin_email]" >'
        . __('Send an email to the admin for each Approved or Rejected payment 
        <br />once the Decidir POST is processed completely. This is a detailed report of the tasks 
        <br />executed after receiving the POST. Can be used for debugging.', 'se') . '</label>';
}

// Enable Organizer Email
add_settings_field( 
    'enable_organizer_email',
    __('Enable Organizer Admin Email', 'se'),
    'enable_organizer_email_callback',
    'manage-sigma-events',
    'organizer_emails',
    $options
);

// Enable Debug Admin Email Callback
function enable_organizer_email_callback($options){
    echo '<input name="sigma_options[enable_organizer_email]" type="checkbox"
        value="true" ' . checked($options['enable_organizer_email'], true, false) . ' >';
    echo ' <label class="description" for="sigma_options[enable_organizer_email]" >'
        . __('Send a copy of Admin Email to the Organizer.', 'se') . '</label>';
}

// Enable Organizer User Email
add_settings_field( 
    'enable_organizer_user_email',
    __('Enable Oraganizer User Email', 'se'),
    'enable_organizer_user_email_callback',
    'manage-sigma-events',
    'organizer_emails',
    $options
);

// Enable Debug Admin Email Callback
function enable_organizer_user_email_callback($options){
    echo '<input name="sigma_options[enable_organizer_user_email]" type="checkbox"
        value="true" ' . checked($options['enable_organizer_user_email'], true, false) . ' >';
    echo ' <label class="description" for="sigma_options[enable_organizer_user_email]" >'
        . __('Send a copy of User Email to the Organizer.', 'se') . '</label>';
}

// Organizer User Products Email
add_settings_field( 
    'enable_organizer_user_product_email',
    __('Organizer User Products Email', 'se'),
    'enable_organizer_user_product_email_callback',
    'manage-sigma-events',
    'organizer_emails',
    $options
);

// Organizer User Products Email Callback
function enable_organizer_user_product_email_callback($options){
    echo '<input name="sigma_options[enable_organizer_user_product_email]" type="checkbox"
        value="true" ' . checked($options['enable_organizer_user_product_email'], true, false) . ' >';
    echo ' <label class="description" for="sigma_options[enable_organizer_user_product_email]" >'
        . __('Send an email to the organizer for each Approved product. 
        <br />once the Decidir POST is received. Using the templates defined by 
        <br />the products admin page.', 'se') . '</label>';
}

// Developer Email
add_settings_field( 
    'dev_email',
    __('Developer Email Address', 'se'),
    'dev_email_callback',
    'manage-sigma-events',
    'developer_emails',
    $options
);

// Developer Email Callback
function dev_email_callback($options){
    echo '<input name="sigma_options[dev_email]" type="text"
        value="' . $options["dev_email"] . '" class="regular-text" >';
    echo '<p class="description" >' . __('Developer Email', 'se') . '</p>';
}

// Enable Developer Email
add_settings_field( 
    'enable_dev_email',
    __('Enable Developer Email', 'se'),
    'enable_dev_email_callback',
    'manage-sigma-events',
    'developer_emails',
    $options
);

// Enable Developer Email Callback
function enable_dev_email_callback($options){
    echo '<input name="sigma_options[enable_dev_email]" type="checkbox"
        value="true" ' . checked($options['enable_dev_email'], true, false) . ' >';
    echo ' <label class="description" for="sigma_options[enable_dev_email]" >'
        . __('Send an email to the dev for each Approved or Rejected payment 
        <br />once the Decidir POST is received. This is a raw data array of POST 
        <br />content received by Decidir and registration data about the user.', 'se') . '</label>';
}

// Enable POST Developer Email
add_settings_field( 
    'enable_post_dev_email',
    __('Enable POST Developer Email', 'se'),
    'enable_post_dev_email_callback',
    'manage-sigma-events',
    'developer_emails',
    $options
);

// Enable POST Developer Email Callback
function enable_post_dev_email_callback($options){
    echo '<input name="sigma_options[enable_post_dev_email]" type="checkbox"
        value="true" ' . checked($options['enable_post_dev_email'], true, false) . ' >';
    echo ' <label class="description" for="sigma_options[enable_post_dev_email]" >'
        . __('Send an email to the dev for each received POST from Decidir 
        <br />regardless of the validity of the POST. This is a raw POST content email 
        <br />without any processing.', 'se') . '</label>';
}

// Enable Debug Developer Email
add_settings_field( 
    'enable_debug_dev_email',
    __('Enable Debug Developer Email', 'se'),
    'enable_debug_dev_email_callback',
    'manage-sigma-events',
    'developer_emails',
    $options
);

// Enable Debug Developer Email Callback
function enable_debug_dev_email_callback($options){
    echo '<input name="sigma_options[enable_debug_dev_email]" type="checkbox"
        value="true" ' . checked($options['enable_debug_dev_email'], true, false) . ' >';
    echo ' <label class="description" for="sigma_options[enable_debug_dev_email]" >'
        . __('Send an email to the dev for each Approved or Rejected payment 
        <br />once the Decidir POST is processed completely. This is a detailed report of the tasks 
        <br />executed after receiving the POST. Can be used for debugging.', 'se') . '</label>';
}

// Save | Reset.
add_settings_section(
    'save_reset',
    __('Save Settings', 'se'),
    'save_reset_callback',
    'manage-sigma-events');

function save_reset_callback(){
    echo '<input name="sigma_options[save_emails]" 
        type="submit" class="button-primary" value="' . esc_attr__('Save Email Settings', 'se') .'" />';  
    echo ' <input name="sigma_options[reset_emails]" 
        type="submit" class="button-secondary" value="' . esc_attr__('Reset', 'se') .'" />';  
}
?>
