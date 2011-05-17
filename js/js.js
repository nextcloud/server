$(document).ready(function() {	
	// Hide the MySQL config div if needed :
	if(!$('#mysql').is(':checked')) {
		$('#use_mysql').hide();
	}

	$('#sqlite').click(function() {
		$('#use_mysql').slideUp(250);
	});
	
	$('#mysql').click(function() {
		$('#use_mysql').slideDown(250);
	});
});
