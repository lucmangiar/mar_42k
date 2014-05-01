jQuery(document).ready( function($) {
    // ------------------------------------------------------------------------
    // (1) Registration Page Functions

    $("#se-update-pending-status").hide();
    $("#nonargentinian").change(function() {
        $('#argentinian').removeAttr('checked');
        if(this.checked){
            $(".se-country").fadeIn();
        } else {
            $(".se-country").fadeOut();
        }
		
		 jQuery.sigma.custom_sequence_number = $("#se-number").val();

        jQuery.sigma.check_number(e);
    });
    $("#argentinian").change(function() {
        $('#nonargentinian').removeAttr('checked');
        $(".se-country").fadeOut();
    });
    $('#bday').datepicker({
        changeMonth: true,
        changeYear: true,
        yearRange: "1920:2013"
    });

    $('#bday').datepicker('option', 'dateFormat', 'yy-mm-dd' );
	
	$('#track').submit(function(e){
		$('#se-form-errors').empty();
		 if($('#dni-track').val() == ''){
			 $('#dni-track').css('border', '1px solid red');
             $('#se-form-errors').append('<div class="se-form-error">' + se_errors.dni_error + '</div>');
			 e.preventDefault();
		 }
		 else { $('#dni-track').css('border', '1px solid #ccc'); }
		  
		  if($('#dni-track').val() != '')
			{
				var isnum = /^\d+$/.test($('#dni-track').val());
				if(!isnum)
					{
					   $('#se-form-errors').append('<div class="se-form-error">' + se_errors.dni_onlynum_error + '</div>');			
					   $('#dni-track').css('border', '1px solid red');
						e.preventDefault();
				}
				else { $('#dni-track').css('border', '1px solid #ccc'); }
			}
	});
	
	
	

    $('#se-registration').submit(function(e){
        $('#se-form-errors').empty();
			
        // Check firstname
        if($('#se-fname').val() == ''){
            $('#se-fname').css('border', '1px solid red');
            $('#se-form-errors').append('<div class="se-form-error">' + se_errors.first_name_error + '</div>');
            e.preventDefault();
        } else { $('#se-fname').css('border', '1px solid #ccc'); }

        // Check lastname
        if($('#se-lname').val() == ''){
            $('#se-lname').css('border', '1px solid red');
            $('#se-form-errors').append('<div class="se-form-error">' + se_errors.last_name_error + '</div>');
            e.preventDefault();
        } else { $('#se-lname').css('border', '1px solid #ccc'); }
		
		  // Check Argentinian
        if($("#argentinian").is(':checked') || $("#nonargentinian").is(':checked')){
            $('.se-nationality').css('border', 'none');
        } else {
            $('.se-nationality').css('border', '1px solid red');
            $('#se-form-errors').append('<div class="se-form-error">' + se_errors.nationality_error + '</div>');
            e.preventDefault();
        }

		
		// Check other country selection.
        if( $("#nonargentinian").is(':checked') && $("#se-country").val() == "not-selected" ){
            $('.se-nationality').css('border', '1px solid red');
            $('#se-form-errors').append('<div class="se-form-error">' + se_errors.country_error + '</div>');
            e.preventDefault();
        } else {
            $('.se-nationality').css('border', 'none');
        }

        // Check DNI
        if($('#se-dni').val() == ''){
            $('#se-dni').css('border', '1px solid red');
            $('#se-form-errors').append('<div class="se-form-error">' + se_errors.dni_error + '</div>');
            e.preventDefault();
        } else { $('#se-dni').css('border', '1px solid #ccc'); }
		
		
        // Check email
        if( $('#se-email').val() == '' || ! IsEmail($('#se-email').val())){
            $('#se-email').css('border', '1px solid red');
            $('#se-form-errors').append('<div class="se-form-error">' + se_errors.email_error + '</div>');
            e.preventDefault();
        } else { $('#se-email').css('border', '1px solid #ccc'); }

		
		 // Check Gender
        if($('#gender').val() == 'select'){
            $('#gender').css('border', '1px solid red');
            $('#se-form-errors').append('<div class="se-form-error">' + se_errors.gender_error + '</div>');
            e.preventDefault();
        } else { $('#gender').css('border', '1px solid #ccc'); }

        // Check Birthdate
		
		 if($('#day').val() == '' || $('#month').val() == '' || $('#year').val() == '' ){
            $('#bday').css('border', '1px solid red');
            $('#se-form-errors').append('<div class="se-form-error">' + se_errors.birthday_error + '</div>');
            e.preventDefault();
         
        } 
		else if( jQuery.sigma.is_current_year( $('#year').val() ) ){
            $('#bday').css('border', '1px solid red');
            $('#se-form-errors').append('<div class="se-form-error">' + se_errors.birthday_year_error + '</div>');
            e.preventDefault();
        }
		else { $('#bday').css('border', '1px solid #ccc'); }
		
		
     /*   if($('#bday').val() == '' || !isGoodDate($('#bday').val())){
            $('#bday').css('border', '1px solid red');
            $('#se-form-errors').append('<div class="se-form-error">' + se_errors.birthday_error + '</div>');
            e.preventDefault();
        } else if( jQuery.sigma.is_current_year( $('#bday').val() ) ){
            $('#bday').css('border', '1px solid red');
            $('#se-form-errors').append('<div class="se-form-error">' + se_errors.birthday_year_error + '</div>');
            e.preventDefault();
        } else { $('#bday').css('border', '1px solid #ccc'); }*/

       

         if($('#phone').val() == ''){
            $('#phone').css('border', '1px solid red');
            $('#se-form-errors').append('<div class="se-form-error">' + se_errors.phone_error + '</div>');
            e.preventDefault();
        } else { $('#phone').css('border', '1px solid #ccc'); }

        if($('#phone').val() != '')
	{
		var isnum = /^\d+$/.test($('#phone').val());
		if(!isnum)
	        {
		       $('#se-form-errors').append('<div class="se-form-error">' + se_errors.phone_onlynum_error + '</div>');			
		       $('#phone').css('border', '1px solid red');
e.preventDefault();
		}
		else { $('#phone').css('border', '1px solid #ccc'); }
	}
	
	 if($('#address').val() == ''){
            $('#address').css('border', '1px solid red');
            $('#se-form-errors').append('<div class="se-form-error">' + se_errors.address_error + '</div>');
            e.preventDefault();
         } else { $('#address').css('border', '1px solid #ccc'); }

        if($('#address').val() != '')
	{
		var isnum = /^[\w\d\., ñÑáéíóú]+$/.test($('#address').val());
		if(!isnum)
	        {
		       $('#se-form-errors').append('<div class="se-form-error">' + se_errors.address_onlyletternum_error + '</div>');			
		       $('#address').css('border', '1px solid red');
e.preventDefault();
		}
		else { $('#address').css('border', '1px solid #ccc'); }
	}

        if($('#club').val() != '')
	{
		var isnum = /^\w+$/.test($('#club').val());
		if(!isnum)
	        {
		       $('#se-form-errors').append('<div class="se-form-error">' + se_errors.club_onlyletternum_error + '</div>');			
		       $('#club').css('border', '1px solid red');
		}
		else { $('#club').css('border', '1px solid #ccc'); }
	}

      
        jQuery.sigma.custom_sequence_number = $("#se-number").val();

        jQuery.sigma.check_number(e);

    });

    // Expandable Number Selection box
    $('#se-number-selection-handle').click(function(e){
        if( $('#se-selection-handle').text() == ' (expand) ' ){
            $('#se-expandable-number-selection').slideDown();
            $('#se-selection-handle').text(' (collapse) ')
        } else {
            $('#se-expandable-number-selection').slideUp();
            $('#se-selection-handle').text(' (expand) ')
        }
    });

    if( window.location.search.length > 5 ){
        $('#se-expandable-number-selection').slideDown(1000);
        $('#se-selection-handle').text(' (collapse) ')
    }

    $('#se-number-query').click(function(e){
        $('#se-number-not-available').fadeOut(1000);
        jQuery.sigma.is_number_available($('#se-number').val());
    });

    // ------------------------------------------------------------------------
    // (2) Payments Page Functions

    $('#se-payment-options').submit(function(e){
        // Count how many products are checked.
        str = 0;
        $(':checkbox').each(function() {
            str += this.checked ? 1 : 0;
        });

        // Selected Processor
        processor = $("input:radio[name='payment_processor']:checked").val();

        // No changes needed. Proceed to Payment
        if(str == 0 && 'decidir' == processor){
            e.preventDefault();
            $("#se-pay-button").hide();
            $("#se-update-pending-status").fadeIn();
            updateRegistrationRecord();
            $('#se-decidir-form').submit();
        } else if(str == 0 && 'dineromail' == processor){
            e.preventDefault();
            $("#se-pay-button").hide();
            $("#se-update-pending-status").fadeIn();
            updateRegistrationRecord();
            jQuery.sigma.remove_express_fields();
            $('#se-dineromail-form').submit();
        } else if(str == 0 && 'salesperson' == processor){
            e.preventDefault();
            $("#se-pay-button").hide();
            $("#se-update-pending-status").fadeIn();
            updateRegistrationRecord();
            $('#se-salesperson-form').submit();
        } else if(str == 0 && 'cuentadigital' == processor){
            e.preventDefault();
            $("#se-pay-button").hide();
            $("#se-update-pending-status").fadeIn();
            updateRegistrationRecord();
            $('#se-cuentadigital-form').submit();
        } else if(str == 0 && 'ep' == processor){
            e.preventDefault();
            $("#se-pay-button").hide();
            $("#se-update-pending-status").fadeIn();
            updateRegistrationRecord();
            $('#se-ep-form').submit();
        } else if(str == 0){
            e.preventDefault();
            $("#se-pay-button").hide();
            $("#se-update-pending-status").fadeIn();
            updateRegistrationRecord();
            jQuery.sigma.remove_express_fields();
            $('#se-decidir-form').submit();
            $('#se-dineromail-form').submit();
            $('#se-ep-form').submit();
            $('#se-cuentadigital-form').submit();
        }

    });

    // Change the 'Medium of Payment' in Decidir Form on the fly.
    $('#visa').change(function(){
        $('[name="MEDIODEPAGO"]').val('1');
    });
    $('#amex').change(function(){
        $('[name="MEDIODEPAGO"]').val('6');
    });
    $('#mc').change(function(){
        $('[name="MEDIODEPAGO"]').val('15');
    });

    // Change the 'Payment Method Available' in Dineromail Form on the fly.
    $('#credit_cards').change(function(){
        $('[name="payment_method_available"]').val('ar_visa,1;ar_amex,1;ar_master,1;ar_cabal,1;ar_tnaranja,1;ar_italcred,1;ar_tshopping,1;ar_argencard,1');
        jQuery.sigma.remove_express_data = true;

    });
    $('#cash').change(function(){
        $('[name="payment_method_available"]').val('ar_pagofacil;ar_rapipago;ar_cobroexpress;ar_bapropago;ar_ripsa');
        jQuery.sigma.remove_express_data = false;
    });

    // Change the 'Payment Options' on the fly if 'freedom to select a payment processor' is given
    $('#decidir').change(function(){
        $('#dineromail-payment-options').hide();
        $('#salesperson-payment-options').hide();
        $('#ep-payment-options').hide();
        $('#cuentadigital-payment-options').hide();
        $('#decidir-payment-options').fadeIn();
    });
    $('#dineromail').change(function(){
        $('#decidir-payment-options').hide();
        $('#salesperson-payment-options').hide();
        $('#ep-payment-options').hide();
        $('#cuentadigital-payment-options').hide();
        $('#dineromail-payment-options').fadeIn();
    });
    $('#salesperson').change(function(){
        $('#decidir-payment-options').hide();
        $('#dineromail-payment-options').hide();
        $('#ep-payment-options').hide();
        $('#cuentadigital-payment-options').hide();
        $('#salesperson-payment-options').fadeIn();
    });
    $('#cuentadigital').change(function(){
        $('#decidir-payment-options').hide();
        $('#dineromail-payment-options').hide();
        $('#ep-payment-options').hide();
        $('#cuentadigital-payment-options').fadeIn();
    });
    $('#ep').change(function(){
        $('#decidir-payment-options').hide();
        $('#dineromail-payment-options').hide();
        $('#salesperson-payment-options').hide();
        $('#cuentadigital-payment-options').hide();
        $('#ep-payment-options').fadeIn();
    });
    $('#paypal').change(function(){
        $('#decidir-payment-options').hide();
        $('#dineromail-payment-options').hide();
        $('#salesperson-payment-options').hide();
        $('#cuentadigital-payment-options').hide();
        $('#paypal-payment-options').fadeIn();
    });

    // ------------------------------------------------------------------------
    // (3) Tracker Page Functions

    $('a.print-preview').click( function (e) {
        window.print();
    });

});

// Simple function to check email validity.
function IsEmail(email) {
    var regex = /^([a-zA-Z0-9_\.\-\+])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/;
    return regex.test(email);
}

// Simple function to check bday validity.
function isGoodDate(bday){
    var reGoodDate = /^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/;
    return reGoodDate.test(bday);
}

function updateRegistrationRecord(){
    data = jQuery('#se-payment-options').serialize();
    data += '&action=update_registration_record';
    url  = se_errors.update_record_url;
    jQuery.ajax({
      url   : url,
      async : false,
      type  : "POST",
      data  : data
    });
}

/**
 * Sigma jQuery Plugin
 */
jQuery.sigma = {
    custom_sequence_number : '',

    is_available : false,
    remove_express_data: false,

    is_number_available : function( number, sync ){
        jQuery('#se-expandable-number-selection').hide();
        jQuery('#se-number-selection-loading').fadeIn();
        // Prepare data
        data = {
            action   : 'is_number_available',
            number   : number,
            event_id : jQuery('#se-selection-event-id').val()
        }

        // Query Backend
        jQuery.ajax({
            url   : jQuery('#se-number-query-url').val(),
            type  : "POST",
            async : ! sync,
            data  : data,
            success: function (data, textStatus, jqXHR){
                if( 'Your Number is Available' == data ){
                    jQuery.sigma.is_available = true;
                } else {
                    data = '<span class="se-red" >' + data + '</span>';
                }
                jQuery('#se-number-selection-loading').hide();
                jQuery('#se-expandable-number-selection').fadeIn();
                jQuery('#se-selection-result').html( data );
            }
        });

        },

    check_number : function(e){
        if( jQuery.sigma.custom_sequence_number &&  ! jQuery.sigma.is_available ){
            jQuery.sigma.is_number_available(jQuery.sigma.custom_sequence_number, true );
            if( ! jQuery.sigma.is_available ){
                e.preventDefault();
            }
        }
    },

    is_current_year : function( birthday ){
        words = birthday.split('-');
        if( se_errors.current_year == words[0] ){
            return true;
        } else {
            return false;
        }
    },

    remove_express_fields: function(){
        if(jQuery.sigma.remove_express_data){
            jQuery('[name="buyer_name"]').remove();
            jQuery('[name="buyer_lastname"]').remove();
            jQuery('[name="buyer_sex"]').remove();
            jQuery('[name="buyer_document_type"]').remove();
            jQuery('[name="buyer_document_number"]').remove();
            jQuery('[name="buyer_phone"]').remove();
            jQuery('[name="buyer_email"]').remove();
        }
    }
}
