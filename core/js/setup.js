$(document).ready(function() {
	$('#selectDbType').buttonset();
	$('#datadirField').hide(250);
	if($('#hasSQLite').val()=='true'){
		$('#databaseField').hide();
		$('#use_other_db').hide();
		$('#dbhost').hide();
		$('#dbhostlabel').hide();
	}

	$('#sqlite').click(function() {
		$('#use_other_db').slideUp(250);
		$('#dbhost').hide(250);
		$('#dbhostlabel').hide(250);
	});

	$('#mysql').click(function() {
		$('#use_other_db').slideDown(250);
		$('#dbhost').show(250);
		$('#dbhostlabel').show(250);
	});
	
	$('#pgsql').click(function() {
		$('#use_other_db').slideDown(250);
		$('#dbhost').show(250);
		$('#dbhostlabel').show(250);
	});

	$('#showAdvanced').click(function() {
		$('#datadirField').slideToggle(250);
		if($('#hasSQLite').val()=='true'){
			$('#databaseField').slideToggle(250);
		}
	});
});
