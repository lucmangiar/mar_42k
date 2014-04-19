jQuery(document).ready( function($) {
	startdate = $('#startdate').val();
	startDate = new Date(startdate);
	$('#startdate').datepicker().datepicker("setDate", startDate);
	$('#startdate').datepicker('option', 'dateFormat', 'yy-mm-dd' );

	enddate = $('#enddate').val();
	endDate = new Date(enddate);
	$('#enddate').datepicker().datepicker("setDate", endDate);
	$('#enddate').datepicker('option', 'dateFormat', 'yy-mm-dd' );
});

window.sigma_admin_image = 'none';

jQuery('#organizer_logo_uploader').click(function () {
    window.sigma_admin_image = 'organizer';
});

jQuery('#reg_eattachment_uploader').click(function () {
    window.sigma_admin_image = 'reg_eattachment';
});

jQuery('#eattachment_uploader').click(function () {
    window.sigma_admin_image = 'eattachment';
});

jQuery('#payment_banner_uploader').click(function () {
    window.sigma_admin_image = 'payment_banner';
});

jQuery('#header_image_uploader').click(function () {
    window.sigma_admin_image = 'header_image';
});

window.send_to_editor = function (html) {
    var imgurl = jQuery('img', html).attr('src');
    var url = jQuery(html).attr('href');

    var regexp = /\.*uploads(.*)/;
    var matches_array = url.match(regexp);
    path = sigma_admin_vars.sigma_upload_base_dir + matches_array[1]

    // set the url as the value
    if( 'organizer' == window.sigma_admin_image ) {
        jQuery('#organizer_logo').val(imgurl);
        jQuery('#organizer-logo-preview').attr( 'src', imgurl);
    } else if( 'reg_eattachment' == window.sigma_admin_image ) {
        jQuery('#reg_eattachment').val(path);
    } else if( 'eattachment' == window.sigma_admin_image ) {
        jQuery('#eattachment').val(path);
    } else if( 'payment_banner' == window.sigma_admin_image ) {
        jQuery('#payment_banner').val(imgurl);
        jQuery('#payment-banner-preview').attr( 'src', imgurl);
    } else if( 'header_image' == window.sigma_admin_image ) {
        jQuery('#header-image').val(imgurl);
        jQuery('#event-header-preview').attr( 'src', imgurl);
    }
    tb_remove();
};
