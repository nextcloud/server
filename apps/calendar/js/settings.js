$(document).ready(function(){
	$('#timezone').change( function(){
		OC.msg.startSaving('#calendar .msg')
		// Serialize the data
		var post = $( '#timezone' ).serialize();
		$.post( OC.filePath('calendar', 'ajax/settings', 'settimezone.php'), post, function(data){
			//OC.msg.finishedSaving('#calendar .msg', data);
		});
		return false;
	});
	$('#timezone').chosen();
	$('#timeformat').change( function(){
		var data = $('#timeformat').serialize();
		$.post( OC.filePath('calendar', 'ajax/settings', 'settimeformat.php'), data, function(data){
			if(data == 'error'){
				console.log('saving timeformat failed');
			}
		});
	});
	$('#timezonedetection').change( function(){
		var post = $('#timezonedetection').serialize();
		$.post( OC.filePath('calendar', 'ajax/settings', 'timezonedetection.php'), post, function(data){
			
		});
	});
	$.getJSON(OC.filePath('calendar', 'ajax/settings', 'timeformat.php'), function(jsondata, status) {
		$('#' + jsondata.timeformat).attr('selected',true);
		$('#timeformat').chosen();
	});
	$.getJSON(OC.filePath('calendar', 'ajax/settings', 'gettimezonedetection.php'), function(jsondata, status){
		if(jsondata.detection == 'true'){
			$('#timezonedetection').attr('checked', 'checked');
		}
	});
});
