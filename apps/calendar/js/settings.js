$(document).ready(function(){
	$("#timezone").change( function(){
		OC.msg.startSaving('#calendar .msg')
		// Serialize the data
		var post = $( "#timezone" ).serialize();
		$.post( OC.filePath('calendar', 'ajax', 'settimezone.php'), post, function(data){
			//OC.msg.finishedSaving('#calendar .msg', data);
		});
		return false;
	});
	$('#timezonedetection').change( function(){
		var post = $('#timezonedetection').serialize();
		$.post( OC.filePath('calendar', 'ajax', 'timezonedetection.php'), post, function(data){
			
		});
	});
	$("#timezone").chosen();
	$("#firstdayofweek").change( function(){
		var data = $("#firstdayofweek").serialize();
		$.post( OC.filePath('calendar', 'ajax', 'setfirstdayofweek.php'), data, function(data){
			if(data == "error"){
				console.log("saving first day of week failed");
			}
		});
	});
	$.getJSON(OC.filePath('calendar', 'ajax', 'firstdayofweek.php'), function(jsondata, status) {
		$("#select_" + jsondata.firstdayofweek).attr('selected',true);
		$("#firstdayofweek").chosen();
	});
	$.getJSON(OC.filePath('calendar', 'ajax', 'daysofweekend.php'), function(jsondata, status) {
		for(day in jsondata){
			if(jsondata[day] == "true"){
				$("#selectweekend_" + day).attr('selected',true);
			}
		}
		$("#weekend").chosen();
	});
	$("#timeformat").change( function(){
		var data = $("#timeformat").serialize();
		$.post( OC.filePath('calendar', 'ajax', 'settimeformat.php'), data, function(data){
			if(data == "error"){
				console.log("saving timeformat failed");
			}
		});
	});
	$.getJSON(OC.filePath('calendar', 'ajax', 'timeformat.php'), function(jsondata, status) {
		$("#" + jsondata.timeformat).attr('selected',true);
		$("#timeformat").chosen();
	});
	$("#duration").blur( function(){
		var data = $("#duration").val();
		$.post( OC.filePath('calendar', 'ajax', 'setduration.php'), {duration: data}, function(data){
			if(data == "error"){
				console.log("saving duration failed");
			}
		});
	});
	$.getJSON(OC.filePath('calendar', 'ajax', 'duration.php'), function(jsondata, status) {
		$("#duration").val(jsondata.duration);
	});
	$.getJSON(OC.filePath('calendar', 'ajax', 'gettimezonedetection.php'), function(jsondata, status){
		if(jsondata.detection == 'true'){
			$('#timezonedetection').attr('checked', 'checked');
		}
	});
	$("#weekend").change( function(){
		var data = $("#weekend").serialize();
		$.post( OC.filePath('calendar', 'ajax', 'setdaysofweekend.php'), data, function(data){
			if(data == "error"){
				console.log("saving days of weekend failed");
			}
		});
	});
});
