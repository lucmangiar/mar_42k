<?php
if ( !class_exists('Sigma_Admin_Pointers') ) :
/**
 * Sigma Admin Pointers
 *
 * Introduces new features via WordPress Admin Pointers
 *
 * @package     SigmaEvents
 * @subpackage  Core
 * @since version 3.5
 */
class Sigma_Admin_Pointers
{
    /**
     * Contruct the Admin Pointer Instance
     */
    function __construct(){
        add_filter( 'sigma_admin_pointers-events', array( $this, 'sigma_events_help' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'sigma_pointer_load' ));
    }

    function sigma_pointer_load(){
        // Get the screen ID
        $screen = get_current_screen();
        $screen_id = $screen->id;

        // Get pointers for this screen
        $pointers = apply_filters( 'sigma_admin_pointers-' . $screen_id, array() );

        // (1) No pointers? Then we stop.
        if ( ! $pointers || ! is_array( $pointers ) )
            return;

        // Get dismissed pointers
        $dismissed = explode( ',', (string) get_user_meta( get_current_user_id(), 'dismissed_wp_pointers', true ) );
        $valid_pointers = array();

        // Check pointers and remove dismissed ones.
        foreach ( $pointers as $pointer_id => $pointer ) {

            // Sanity check
            if ( in_array( $pointer_id, $dismissed ) || empty( $pointer )  || empty( $pointer_id ) || empty( $pointer['target'] ) || empty( $pointer['options'] ) )
                continue;

            $pointer['pointer_id'] = $pointer_id;

            // Add the pointer to $valid_pointers array
            $valid_pointers['pointers'][] = $pointer;
        }

        // (2) No valid pointers? Stop here.
        if ( empty( $valid_pointers ) )
            return;

        // Add pointers style to queue.
        wp_enqueue_style( 'wp-pointer' );

        // Add pointers script and our own custom script to queue.
        wp_enqueue_script( 'sigma-pointer', SIGMA_URL . 'js/sigma-pointer.js', array( 'wp-pointer' ) );

        // Add pointer options to script.
        wp_localize_script( 'sigma-pointer', 'sigmaPointer', $valid_pointers );

    }

    function sigma_events_help( $pointer ) {
        $pointer['sigma035_events_help'] = array(
            'target' => '#contextual-help-link',
            'options' => array(
                'content' => sprintf( '<h3> %s </h3> <p> %s </p>',
                    __( 'Email Template Tags' ,'se'),
                    __( 'Email template help moved to the help tab.','se')
                ),
                'position' => array( 'edge' => 'top', 'align' => 'left' )
            )
        );
        return $pointer;
    }
}
endif;
?>
