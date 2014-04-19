<?php
if( !defined( 'ABSPATH' ) ){
        header('HTTP/1.0 403 Forbidden');
        die('No Direct Access Allowed!');
}

add_settings_section(
    'csv_to_db',
    __('Upload CSV and Update Database', 'se'),
    'csv_to_db_callback',
    'csv-to-db');

function csv_to_db_callback(){
    echo '<table>';
    echo '<tr>';

    echo '<td>';
        $output = '<label>' . __('CSV File', 'se') . '</label></td>';
        $output .= '<td><input id="csv_file" type="text" class="regular-text" name="sigma_csv_options[csv_file]"
            value="Path to CSV File" />';
        $editor_id = 'csv_file';
        $post = get_post();
        wp_enqueue_media( array('post' => $post) );
        $img = '<span class="wp-media-buttons-icon"></span> ';
        $output .= ' <a href="#" class="button insert-media add_media" data-editor="' . esc_attr( $editor_id ) .
            '" id="csv_file" title="' . esc_attr__( 'Upload Product Email Attachment' ) . '"> ' .
            $img . __( 'Select CSV File' ) . '</a>';
        echo $output;
    echo '</td>';
    echo '<td>';
    echo ' Delimiter <input name="sigma_csv_options[delimiter]"
            type="text" class="small-text" value="," />';
    echo '</td>';
    echo '<td>';
    echo ' Enclosure <input name="sigma_csv_options[enclosure]"
            type="text" class="small-text" value="' . htmlspecialchars('"') . '" />';
    echo '</td>';

    echo '<td>';
    echo '<input name="sigma_csv_options[process_csv]"
            type="submit" class="button-primary" value="' . esc_attr__('Upload and Process CSV', 'se') .'" />';
    echo '</td>';

    echo '</tr>';
    echo '</table>';
}
