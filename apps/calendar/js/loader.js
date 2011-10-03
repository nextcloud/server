function importdialog(directory, filename){
	$("body").append("<div id=\"importdialogholder\"></div>");
	$("#importdialogholder").load(OC.filePath('calendar', 'ajax', 'importdialog.php?filename=' + filename + '&path=' + directory));
}

$(document).ready(function(){
	$('tr[data-file$=".ics"]').attr("data-mime", "text/calendar");
	$('tr[data-file$=".vcs"]').attr("data-mime", "text/calendar");
	$('tr[data-file$=".ical"]').attr("data-mime", "text/calendar");
	if(typeof FileActions!=='undefined'){
		FileActions.register('text/calendar','Import to Calendar','',function(filename){
			importdialog($('#dir').val(),filename);
		});
		FileActions.setDefault('text/calendar','Import to Calendar');
	}
});