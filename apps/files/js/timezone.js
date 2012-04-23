//send the clients time zone to the server
$(document).ready(function() {
	var visitortimezone = (-new Date().getTimezoneOffset()/60);
	$.ajax({
		type: "GET",
		url: OC.filePath('files', 'ajax', 'timezone.php'),
		data: 'time='+ visitortimezone,
		success: function(){
			location.reload();
		}
	});
});