$(document).ready(function() {
	$('#datadirField').hide(250);
	if($('#hasSQLite').val()=='true'){
		$('#databaseField').hide(250);
		$('#use_other_db').slideUp(250);
	}

	$('#sqlite').click(function() {
		$('#use_other_db').slideUp(250);
	});

	$('#mysql').click(function() {
		$('#use_other_db').slideDown(250);
	});
	
	$('#pgsql').click(function() {
		$('#use_other_db').slideDown(250);
	});

	$('#showAdvanced').click(function() {
		$('#datadirField').slideToggle(250);
		if($('#hasSQLite').val()=='true'){
			$('#databaseField').slideToggle(250);
		}
	});
});
