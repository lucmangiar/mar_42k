jQuery(document).ready( function($) {
	reportdate = $('#reportdate').val();
	endDate = new Date(reportdate);
	$('#reportdate').datepicker().datepicker("setDate", endDate);
	$('#reportdate').datepicker('option', 'dateFormat', 'yy-mm-dd' );
});
