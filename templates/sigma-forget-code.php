<?php
/**
 * This template is used to serve code forgotten users
 */
if( !defined( 'ABSPATH' ) ){
    header('HTTP/1.0 403 Forbidden');
    die('No Direct Access Allowed!');
}

get_header();
echo '<div class="se-wrapper" >';
    echo '<h1>' . __('Retrieve my code', 'se') . '</h1>';
    if('email_input_form' == $code_page):
        echo '<p>' . __('Enter the email address used for registration.', 'se') . '</p>';
        // Tracker Form. Grab the tracking id. Sigma Unique ID.
        echo "<form id='se-tracker' name='se-tracker' method='POST' action='"
            . get_home_url() . "/forget_code/' >";

        // Nonce Fields
        wp_nonce_field('sigma-forget-code-action', 'sigma-forget-code-data');

        echo '<table>';
        if($error):
            echo '<tr>';
                echo '<td colspan="2" />' . $error . '</td>';
            echo '</tr>';
        endif;
        echo '<tr>';
            echo '<td>' . __('Email', 'se') . '</td>';
            echo '<td><input type="text" name="code_email" id="code_email" /></td>';
        echo '</tr>';

        /**
         * Display Captcha If Enabled
         */
        $options = get_option('sigma_options');
        if($options['enable_forget_code_captcha'] && class_exists('ReallySimpleCaptcha')):
            $captcha_instance = new ReallySimpleCaptcha();
            $captcha_instance->img_size = array( 120, 50 );
            $captcha_instance->font_size = 20;
            $captcha_instance->base = array( 20, 28 );
            $captcha_instance->font_char_width = 20;
            $word = $captcha_instance->generate_random_word();
            $prefix = mt_rand();
            $image = $captcha_instance->generate_image( $prefix, $word );
            echo '<tr>';
                echo '<td>' . __('Please type this security code:', 'se') . '</td>';
                echo '<td><img src="' . WP_PLUGIN_URL . '/really-simple-captcha/tmp/' .  $image . '" />';
                echo '<input type="hidden" name="captcha_prefix" id="captcha_prefix" value="' . $prefix . '" />';
                echo '<br /><br /><input type="text" name="captcha_solution" id="captcha_solution" value="" /></td>';
            echo '</tr>';
        endif;

        echo '<tr>';
            echo '<td colspan="2" /><input type="submit" name="code_submit" id="code_submit" value="' . __('Retrieve the Code', 'se') . '" /></td>';
        echo '</tr>';
        echo '</table>';

        echo '</form>';

    elseif('email_sent_form' == $code_page):
        echo '<h3>' . __('Your code has been sent to your email address. Check your inbox.', 'se') . '</h3>';
    elseif('data_display_form' == $code_page):
        foreach($records as $record):
            $event      = get_post($record['eid']);
            $event_name = $event->post_title;
            $border_color = 8 == sizeof($record) ? '#0c5' : '#c04';
            $background_color = 8 == sizeof($record) ? '#fff' : '#eee';
            echo '<table style="border-width:4px;border-color:' . $border_color . ';border-style:solid;background-color:' . $background_color . ';" >';
                echo '<tr>';
                    echo '<td width="30%">' . __('Name', 'se') . '</td><td>' . $record['fname'] . ' ' . $record['lname'] . '</td>';
                echo '</tr>';
                echo '<tr>';
                    echo '<td>' . __('Event Name', 'se') . '</td><td><strong>' . $event_name . '</strong></td>';
                echo '</tr>';
                echo '<tr>';
                    echo '<td><strong>' . __('Token ID', 'se') . '</strong></td><td><a href='. get_home_url() .'/sigma-events/tracker/?sigma_token='. $record['token'] .'><strong>' . $record['token'] . '</strong></a> ' . __('Click to see status', 'se') . '</td>';
                echo '</tr>';
                echo '<tr>';
                    echo '<td>' . __('Registration Time', 'se') . '</td><td>' . $record['reg_time'] . '</td>';
                echo '</tr>';
                echo '<tr>';
                    echo '<td>' . __('Sequence Number', 'se') . '</td><td>' . $record['seq_no'] . '</td>';
                echo '</tr>';
                echo '<tr>';
                    echo '<td>' . __('Payment Status', 'se') . '</td><td>' . $record['paid'] . '</td>';
                echo '</tr>';
            echo '</table>';
        endforeach;
    endif;
echo '</div>';
get_footer();
?>