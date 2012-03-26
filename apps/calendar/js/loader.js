/**
 * Copyright (c) 2012 Georg Ehrke <ownclouddev at georgswebsite dot de>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
Calendar_Import={
	importdialog: function(filename){
		var path = $('#dir').val();
		$('body').append('<div id="calendar_import"></div>');
		$('#calendar_import').load(OC.filePath('calendar', 'ajax/import', 'dialog.php'), {filename:filename, path:path},	function(){Calendar_Import.initdialog(filename);});
	},
	initdialog: function(filename){
		$('#calendar_import_dialog').dialog({
			width : 500,
			close : function() {
				$(this).dialog('destroy').remove();
				$('#calendar_import').remove();
			}
		});
		$('#import_done_button').click(function(){
			$('#calendar_import_dialog').dialog('destroy').remove();
			$('#calendar_import').remove();
		});
		$('#progressbar').progressbar({value: 0});
		$('#startimport').click(function(){
			var filename = $('#filename').val();
			var path = $('#path').val();
			var calid = $('#calendar option:selected').val();
			if($('#calendar option:selected').val() == 'newcal'){
				var method = 'new';
				var calname = $('#newcalendar').val();
				var calname = $.trim(calname);
				if(calname == ''){
					$('#newcalendar').css('background-color', '#FF2626');
					$('#newcalendar').focus(function(){
						$('#newcalendar').css('background-color', '#F8F8F8');
					});
					return false;
				}
			}else{
				var method = 'old';
			}
			$('#newcalendar').attr('readonly', 'readonly');
			$('#calendar').attr('disabled', 'disabled');
			var progressfile = $('#progressfile').val();
			$.post(OC.filePath('calendar', 'ajax/import', 'import.php'), {method: String (method), calname: String (calname), path: String (path), file: String (filename), id: String (calid)}, function(data){
				if(data.status == 'success'){
					$('#progressbar').progressbar('option', 'value', 100);
					$('#import_done').css('display', 'block');
				}
			});
			$('#form_container').css('display', 'none');
			$('#progressbar_container').css('display', 'block');
			window.setTimeout('Calendar_Import.getimportstatus(\'' + progressfile + '\')', 500);
		});
		$('#calendar').change(function(){
			if($('#calendar option:selected').val() == 'newcal'){
				$('#newcalform').slideDown('slow');
			}else{
				$('#newcalform').slideUp('slow');
			}
		});
	},
	getimportstatus: function(progressfile){
		$.get(OC.filePath('calendar', 'import_tmp', progressfile), function(percent){
			$('#progressbar').progressbar('option', 'value', parseInt(percent));
			if(percent < 100){
				window.setTimeout('Calendar_Import.getimportstatus(\'' + progressfile + '\')', 500);
			}else{
				$('#import_done').css('display', 'block');
			}
		});
	}
}
$(document).ready(function(){
	if(typeof FileActions !== 'undefined'){
		FileActions.register('text/calendar','importcal', '', Calendar_Import.importdialog); 
		FileActions.setDefault('text/calendar','importcal');
	};
});
