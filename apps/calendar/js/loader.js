Calendar_Import={
	importdialog: function(filename){
		var path = $('#dir').val();
		$('body').append('<div id="calendar_import"></div>');
		$('#calendar_import').load(OC.filePath('calendar', 'ajax', 'importdialog.php'), {filename:filename, path:path},	Calendar_Import.initdialog());
	},
	initdialog: function(){
		
	},
	getimportstatus: function(){
		
	}
}
$(document).ready(function(){
	if(typeof FileActions !== 'undefined'){
		FileActions.register('text/calendar','importcal', '', Calendar_Import.importdialog); 
		FileActions.setDefault('text/calendar','importcal');
	};
});