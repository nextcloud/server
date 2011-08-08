$(document).ready(function() {
	// Hide the PostgreSQL config div if needed:
	if(!$('#pgsql').is(':checked')) {
		$('#use_postgresql').hide();
	}

	$('#datadirField').hide(250);
	if($('#hasSQLite').val()=='true'){
		$('#databaseField').hide(250);
	}

	$('#sqlite').click(function() {
		$('#use_mysql').slideUp(250);
		$('#use_postgresql').slideUp(250);
	});

	$('#mysql').click(function() {
		$('#use_mysql').slideDown(250);
		$('#use_postgresql').slideUp(250);
	});
	
	$('#pgsql').click(function() {
		$('#use_postgresql').slideDown(250);
		$('#use_mysql').slideUp(250);
	});

	$('#showAdvanced').click(function() {
		$('#datadirField').slideToggle(250);
		if($('#hasSQLite').val()=='true'){
			$('#databaseField').slideToggle(250);
		}
	});
	
	// Hide the MySQL config div if needed :
	if($('#dbtype').val()=='sqlite') {
		$('#databaseField').hide();
	}
});
