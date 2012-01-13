Calendar_Import={
	importdialog: function(filename){
		var path = $('#dir').val();
		$('body').append('<div id="calendar_import"></div>');
		$('#calendar_import').load(OC.filePath('calendar', 'ajax', 'importdialog.php'), {filename:filename, path:path},	function(){Calendar_Import.initdialog(filename);});
	},
	initdialog: function(filename){
		$("#calendar_import_dialog").dialog({
			width : 500,
			close : function() {
				$(this).dialog('destroy').remove();
				$("#calendar_import").remove();
			}
		});
		$('#progressbar').progressbar({value: 87});
		$('#startimport').click(function(){
			var filename = $('#filename').val();
			var path = $('#path').val();
			if($('#calendar option:selected').val() == 'newcal'){
				var method = 'new';
				var calname = $('#newcalendar').val();
				var calname = $.trim(calname);
				if(calname == ''){
					$('#newcalendar').css('background-color', '#FF2626');
					return false;
				}
			}else{
				var method = 'old';
			}
			$('#newcalendar').attr('readonly', 'readonly');
			$('#calendar').attr('disabled', 'disabled');
			$.post(OC.filePath('calendar', '', 'import.php'), {'method':method, 'calname':calname, 'path':path, 'file':filename}, function(){});
			$('#progressbar').slideDown('slow');
		});
		$('#calendar').change(function(){
			if($('#calendar option:selected').val() == 'newcal'){
				$('#newcalform').slideDown('slow');
			}else{
				$('#newcalform').slideUp('slow');
			}
		});
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