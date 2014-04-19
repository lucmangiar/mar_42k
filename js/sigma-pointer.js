jQuery(document).ready( function($) {
    sigma_open_pointer(0);
    function sigma_open_pointer(i) {
        pointer = sigmaPointer.pointers[i];
        options = $.extend( pointer.options, {
            close: function() {
                $.post( ajaxurl, {
                    pointer: pointer.pointer_id,
                    action: 'dismiss-wp-pointer'
                });
            }
        });

        $(pointer.target).pointer( options ).pointer('open');
    }
});
