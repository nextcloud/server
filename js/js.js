$(document).ready(function() {	
	// Hide the MySQL config div if needed :
	if(!$('#mysql').is(':checked') && $('#hasSQLite').val()=='true') {
		$('#use_mysql').hide();
	}
	
	$('#datadirField').hide(250);
	if($('#hasSQLite').val()=='true'){
		$('#databaseField').hide(250);
	}
	
	$('#sqlite').click(function() {
		$('#use_mysql').slideUp(250);
	});
	
	$('#mysql').click(function() {
		$('#use_mysql').slideDown(250);
	});
	
	$('#showAdvanced').click(function() {
		$('#datadirField').slideToggle(250);
		if($('#hasSQLite').val()=='true'){
			$('#databaseField').slideToggle(250);
		}
	});
});
