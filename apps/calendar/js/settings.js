$(document).ready(function(){
	$('#timezone').change( function(){
		var post = $( '#timezone' ).serialize();
		$.post( OC.filePath('calendar', 'ajax/settings', 'settimezone.php'), post, function(data){return;});
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
	$('#firstday').change( function(){
		var data = $('#firstday').serialize();
		$.post( OC.filePath('calendar', 'ajax/settings', 'setfirstday.php'), data, function(data){
			if(data == 'error'){
				console.log('saving firstday failed');
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
		$('#timeformat_chzn').css('width', '100px');
	});
	$.getJSON(OC.filePath('calendar', 'ajax/settings', 'gettimezonedetection.php'), function(jsondata, status){
		if(jsondata.detection == 'true'){
			$('#timezonedetection').attr('checked', 'checked');
		}
	});
	$.getJSON(OC.filePath('calendar', 'ajax/settings', 'getfirstday.php'), function(jsondata, status) {
		$('#' + jsondata.firstday).attr('selected',true);
		$('#firstday').chosen();
		$('#firstday_chzn').css('width', '100px');
	});
	$('#cleancalendarcache').click(function(){
		$.getJSON(OC.filePath('calendar', 'ajax/cache', 'rescan.php'), function(){
			calendarcachecheck();
		});
	});
	calendarcachecheck();
	
});
function calendarcachecheck(){
	$.getJSON(OC.filePath('calendar', 'ajax/cache', 'status.php'), function(jsondata, status) {
		$('#cleancalendarcache').attr('title', jsondata.l10n.text);
		if(jsondata.status == 'success'){
			$('#cleancalendarcache').css('background', '#F8F8F8');
			$('#cleancalendarcache').css('color', '#333');
			$('#cleancalendarcache').css('text-shadow', '#fff 0 1px 0');
		}else{
			$('#cleancalendarcache').css('background', '#DC143C');
			$('#cleancalendarcache').css('color', '#FFFFFF');
			$('#cleancalendarcache').css('text-shadow', '0px 0px 0px #fff, 0px 0px #fff');
		}
	});
}