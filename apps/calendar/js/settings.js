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
		$("#weekend").multiselect({
			header: false,
			noneSelectedText: $('#weekend').attr('title'),
			selectedList: 2,
			minWidth:'auto',
		});
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
	$("#weekend").change( function(){
		var data = $("#weekend").serialize();
		$.post( OC.filePath('calendar', 'ajax', 'setdaysofweekend.php'), data, function(data){
			if(data == "error"){
				console.log("saving days of weekend failed");
			}
		});
	});
});
