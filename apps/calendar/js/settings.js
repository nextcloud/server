$(document).ready(function(){
	$("#timezone").change( function(){
		OC.msg.startSaving('#calendar .msg')
		// Serialize the data
		var post = $( "#timezone" ).serialize();
		$.post( OC.filePath('calendar', 'ajax', 'settimezone.php'), post, function(data){
			OC.msg.finishedSaving('#calendar .msg', data);
		});
		return false;
	});
});
