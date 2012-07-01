/**
 * Copyright (c) 2012 Georg Ehrke <ownclouddev at georgswebsite dot de>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
Calendar_Import={
	Store:{
		file: '',
		path: '',
		id: 0,
		method: '',
		calname: '',
		progresskey: '',
		percentage: 0
	}
	Dialog:{
		open: function(filename){
			Calendar_Import.Store.file = filename;
			Calendar_Import.Store.path = $('#dir').val();
			$('body').append('<div id="calendar_import"></div>');
			$('#calendar_import').load(OC.filePath('calendar', 'ajax/import', 'dialog.php'), {filename:Calendar_Import.Store.file, path:Calendar_Import.Store.path},function(){
					Calendar_Import.Dialog.init();
			});
		},
		close: function(){
			$(this).dialog('destroy').remove();
			$('#calendar_import').remove();
		},
		init: function(){
			//init dialog
			$('#calendar_import_dialog').dialog({
				width : 500,
				close : function() {
					Calendar_Import.Dialog.close();
				}
			});
			//init buttons
			$('#import_done_button').click(function(){
				Calendar_Import.closedialog();
			});
			$('#startimport').click(function(){
				Calendar_import.Core.process();
			}
			$('#calendar').change(function(){
				if($('#calendar option:selected').val() == 'newcal'){
					$('#newcalform').slideDown('slow');
				}else{
					$('#newcalform').slideUp('slow');
				}
			});
			//init progressbar
			$('#progressbar').progressbar({value: Calendar_Import.Store.percentage});
			Calendar_Import.Store.progresskey = $('#progresskey').val();
		},
		mergewarning: function(){
			
		},
		update: function(){
			/*$.post(OC.filePath('calendar', 'ajax/import', 'import.php'), {progress:1,progresskey: progresskey}, function(percent){
				$('#progressbar').progressbar('option', 'value', parseInt(percent));
				if(percent < 100){
					window.setTimeout('Calendar_Import.getimportstatus(\'' + progresskey + '\')', 500);
				}else{
					$('#import_done').css('display', 'block');
				}
			});*/
		return 0;
		},
		warning: function(validation){
			
		}
	},
	Core:{
		process: function(){
			var validation = Calendar.Core.prepare();
			if(validation){
				$('#newcalendar').attr('readonly', 'readonly');
				$('#calendar').attr('disabled', 'disabled');
				Calendar.Core.send();
			}else{
				Calendar.Dialog.warning(validation);
			}
		},
		send: function(){
			$.post(OC.filePath('calendar', 'ajax/import', 'import.php'), 
			{progresskey: Calendar_Import.Store.progresskey, method: String (Calendar_Import.Store.method), calname: String (Calendar_Import.Store.calname), path: String (Calendar_Import.Store.path), file: String (Calendar_Import.Store.filename), id: String (Calendar_Import.Store.calid)}, function(data){
				if(data.status == 'success'){
					$('#progressbar').progressbar('option', 'value', 100);
					$('#import_done').css('display', 'block');
					$('#status').html(data.message);
				}
			});
			$('#form_container').css('display', 'none');
			$('#progressbar_container').css('display', 'block');
			window.setTimeout('Calendar_Import.Dialog.update', 500);
		},
		prepare: function(){
			Calendar_Import.Store.id = $('#calendar option:selected').val();
			if($('#calendar option:selected').val() == 'newcal'){
				Calendar_Import.Store.method = 'new';
				Calendar_Import.Store.calname = $.trim($('#newcalendar').val());
				if(Calendar_Import.Store.calname == ''){
					$('#newcalendar').css('background-color', '#FF2626');
					$('#newcalendar').focus(function(){
						$('#newcalendar').css('background-color', '#F8F8F8');
					});
					return false;
				}
			}else{
				var method = 'old';
			}
			
		}
	}
}
$(document).ready(function(){
	if(typeof FileActions !== 'undefined'){
		FileActions.register('text/calendar','importcal', '', Calendar_Import.importdialog); 
		FileActions.setDefault('text/calendar','importcal');
	};
});
