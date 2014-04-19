(function ($){
    $('#debug-button').click(function( e ) {
        e.preventDefault();
        $('#sigma-debug-panel').hide();
        $('#debugger-pending-status').fadeIn();
        $('#debug-button button').prop('disabled', true);

        $('#ajax-response').hide();
        tokens = $('#debug-tokens').val();

        var ajaxProcessor = $.ajax({
            type: "POST",

            url: ajaxurl,

            data: {
                tokens: tokens,
                action: 'sigma_debugger_get_record',
                '_sigma_debugger_nonce': $('#sigma_debugging_operations_nonce').val(),
            },

            success: function (data, textStatus, jqXHR){
                $('#debugger-pending-status').hide();
                $('#ajax-response').html(data).fadeIn();
                $("html, body").animate({ scrollTop: 0 }, 1000);
            },

            error: function (jqXHR, textStatus, errorThrown){
                $('#ajax-response').html(textStatus + '<br />' + errorThrown).fadeIn();
            },

            complete: function(jqXHR, textStatus){
                $('#debug-button button').prop('disabled', false);
            }
        });
        return false;
    });

    $(document).on( 'click', 'span.raw-debug-response-handle', function( e ) {
        row = $(this).closest('tr')
        raw_content = $(row).find('.raw-debug-response')
        handle = $(row).find('.raw-debug-response-handle');
        if( 'Show Raw Response' == $(handle).text() ){
            handle.html('Hide Raw Response');
            $(raw_content).slideDown(600);
        } else {
            handle.html('Show Raw Response');
            $(raw_content).slideUp();
        }
        return false;
    });

    $(document).on( 'click', 'div.debug-close-button', function( e ) {
        form = $(this).closest('form')
        $(form).slideUp();
        return false;
    });

    $('#dineromail-button').click(function( e ) {
        e.preventDefault();
        $('#sigma-debug-panel').hide();
        $('#debugger-pending-status').fadeIn();
        $('#dineromail-button button').prop('disabled', true);

        $('#ajax-response').hide();
        tokens = $('#dineromail-tokens').val();

        var ajaxProcessor = $.ajax({
            type: "POST",

            url: ajaxurl,

            data: {
                tokens: tokens,
                action: 'sigma_dineromail_query',
                '_sigma_debugger_nonce': $('#sigma_debugging_operations_nonce').val(),
            },

            success: function (data, textStatus, jqXHR){
                $('#debugger-pending-status').hide();
                $('#ajax-response').html(data).fadeIn();
                $("html, body").animate({ scrollTop: 0 }, 1000);
            },

            error: function (jqXHR, textStatus, errorThrown){
                $('#ajax-response').html(textStatus + '<br />' + errorThrown).fadeIn();
            },

            complete: function(jqXHR, textStatus){
                $('#dineromail-button button').prop('disabled', false);
            }
        });
        return false;
    });

    $(document).on( 'click', '.se-debug-dineromail-record', function( e ) {
        var form = $(this).closest('form');
        var panel = $(form).closest('div');
        var windowScrollTop = $(window).scrollTop();
        e.preventDefault();

        token = $(form).find('#token').val();
        processor_url = $(form).find('#debug-url').val();
        tracker_url = $(form).find('#tracker-url').val();
        link = '<a target="_blank" href="' + tracker_url + '" title="Tracker URL">Tracker Link</a>'
        edit_link = '<a class="debug-edit-link" href="#" title="Click to View and Edit the Dineromail Response Again">Edit</a>'

        var ajaxProcessor = $.ajax({
            type: "POST",

            url: processor_url,

            data: $(form).serialize(),

            success: function (data, textStatus, jqXHR){
                $(panel).find('.debug-response-panel').html('<p class="debug-updated" ><b>Response ( Token: ' + token + ' )</b><br />' +
                    data + '<br />' + link + ' | ' + edit_link +
                    '</p>').fadeIn();
                $(form).fadeOut();
            },

            error: function (jqXHR, textStatus, errorThrown){
                $('#ajax-response').html('<h3>' + textStatus + '<br />' + errorThrown + '</h3>').fadeIn();
            },

            complete: function(jqXHR, textStatus){
                newScrollTop = $(panel).offset().top - 30;
                $("html, body").animate({ scrollTop: newScrollTop }, 1000);
            }
        });
        return false;
    });

    $(document).on( 'click', '.debug-edit-link', function( e ) {
        var panel = $(this).closest('.debug-panel');
        var response = $(panel).find('.debug-response-panel');
        var form = $(panel).find('form');
        $(response).slideUp();
        $(form).slideDown();
        return false;
    });

    /**
     * General Debug Handling
     */
    $(document).on( 'click', '.se-debug-general-record', function( e ) {
        var form = $(this).closest('form');
        var panel = $(form).closest('div');
        var windowScrollTop = $(window).scrollTop();
        e.preventDefault();

        token = $(form).find('#token').val();
        processor_url = $(form).find('#debug-url').val();
        tracker_url = $(form).find('#tracker-url').val();
        link = '<a target="_blank" href="' + tracker_url + '" title="Tracker URL">Tracker Link</a>'
        edit_link = '<a class="debug-edit-link" href="#" title="Click to View and Edit the Dineromail Response Again">Edit</a>'

        var ajaxProcessor = $.ajax({
            type: "POST",

            url: processor_url,

            data: $(form).serialize(),

            success: function (data, textStatus, jqXHR){
                $(panel).find('.debug-response-panel').html('<p class="debug-updated" ><b>Response ( Token: ' + token + ' )</b><br />' +
                    data + '<br />' + link + ' | ' + edit_link +
                    '</p>').fadeIn();
                $(form).fadeOut();
            },

            error: function (jqXHR, textStatus, errorThrown){
                $('#ajax-response').html('<h3>' + textStatus + '<br />' + errorThrown + '</h3>').fadeIn();
            },

            complete: function(jqXHR, textStatus){
                newScrollTop = $(panel).offset().top - 30;
                $("html, body").animate({ scrollTop: newScrollTop }, 1000);
            }
        });
        return false;
    });
}(jQuery));
