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
	},
	Dialog:{
		open: function(filename){
			OC.addStyle('calendar', 'import');
			Calendar_Import.Store.file = filename;
			Calendar_Import.Store.path = $('#dir').val();
			$('body').append('<div id="calendar_import"></div>');
			$('#calendar_import').load(OC.filePath('calendar', 'ajax/import', 'dialog.php'), {filename:Calendar_Import.Store.file, path:Calendar_Import.Store.path},function(){
					Calendar_Import.Dialog.init();
			});
		},
		close: function(){
			Calendar_Import.reset();
			$(this).dialog('destroy').remove();
			$('#calendar_import_dialog').remove();
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
			$('#calendar_import_done').click(function(){
				Calendar_Import.Dialog.close();
			});
			$('#calendar_import_submit').click(function(){
				Calendar_Import.Core.process();
			});
			$('#calendar_import_calendar').change(function(){
				if($('#calendar_import_calendar option:selected').val() == 'newcal'){
					$('#calendar_import_newcalform').slideDown('slow');
					Calendar_Import.Dialog.mergewarning($('#calendar_import_newcalendar').val());
				}else{
					$('#calendar_import_newcalform').slideUp('slow');
					$('#calendar_import_mergewarning').slideUp('slow');
				}
			});
			$('#calendar_import_newcalendar').keyup(function(){
				Calendar_Import.Dialog.mergewarning($('#calendar_import_newcalendar').val());
			});
			//init progressbar
			$('#calendar_import_progressbar').progressbar({value: Calendar_Import.Store.percentage});
			Calendar_Import.Store.progresskey = $('#calendar_import_progresskey').val();
		},
		mergewarning: function(newcalname){
			$.post(OC.filePath('calendar', 'ajax/import', 'calendarcheck.php'), {calname: newcalname}, function(data){
				if(data.message == 'exists'){
					$('#calendar_import_mergewarning').slideDown('slow');
				}else{
					$('#calendar_import_mergewarning').slideUp('slow');
				}
			});
		},
		update: function(){
			if(Calendar_Import.Store.percentage == 100){
				return false;
			}
			$.post(OC.filePath('calendar', 'ajax/import', 'import.php'), {progresskey: Calendar_Import.Store.progresskey, getprogress: true}, function(data){
 				if(data.status == 'success'){
 					if(data.percent == null){
	 					return false;
 					}
 					Calendar_Import.Store.percentage = parseInt(data.percent);
					$('#calendar_import_progressbar').progressbar('option', 'value', parseInt(data.percent));
					if(data.percent < 100 ){
						window.setTimeout('Calendar_Import.Dialog.update()', 250);
					}else{
						$('#calendar_import_done').css('display', 'block');
					}
				}else{
					$('#calendar_import_progressbar').progressbar('option', 'value', 100);
					$('#calendar_import_progressbar > div').css('background-color', '#FF2626');
					$('#calendar_import_status').html(data.message);
				}
			});
			return 0;
		},
		warning: function(selector){
			$(selector).addClass('calendar_import_warning');
			$(selector).focus(function(){
				$(selector).removeClass('calendar_import_warning');
			});
		}
	},
	Core:{
		process: function(){
			var validation = Calendar_Import.Core.prepare();
			if(validation){
				$('#calendar_import_form').css('display', 'none');
				$('#calendar_import_process').css('display', 'block');
				$('#calendar_import_newcalendar').attr('readonly', 'readonly');
				$('#calendar_import_calendar').attr('disabled', 'disabled');
				Calendar_Import.Core.send();
				window.setTimeout('Calendar_Import.Dialog.update()', 250);
			}
		},
		send: function(){
			$.post(OC.filePath('calendar', 'ajax/import', 'import.php'), 
			{progresskey: Calendar_Import.Store.progresskey, method: String (Calendar_Import.Store.method), calname: String (Calendar_Import.Store.calname), path: String (Calendar_Import.Store.path), file: String (Calendar_Import.Store.file), id: String (Calendar_Import.Store.id)}, function(data){
				if(data.status == 'success'){
					$('#calendar_import_progressbar').progressbar('option', 'value', 100);
					Calendar_Import.Store.percentage = 100;
					$('#calendar_import_done').css('display', 'block');
					$('#calendar_import_status').html(data.message);
				}else{
					$('#calendar_import_progressbar').progressbar('option', 'value', 100);
					$('#calendar_import_progressbar > div').css('background-color', '#FF2626');
					$('#calendar_import_status').html(data.message);
				}
			});
		},
		prepare: function(){
			Calendar_Import.Store.id = $('#calendar_import_calendar option:selected').val();
			if($('#calendar_import_calendar option:selected').val() == 'newcal'){
				Calendar_Import.Store.method = 'new';
				Calendar_Import.Store.calname = $.trim($('#calendar_import_newcalendar').val());
				if(Calendar_Import.Store.calname == ''){
					Calendar_Import.Dialog.warning('#calendar_import_newcalendar');
					return false;
				}
			}else{
				Calendar_Import.Store.method = 'old';
			}
			return true;
		}
	},
	reset: function(){
		Calendar_Import.Store.file = '';
		Calendar_Import.Store.path = '';
		Calendar_Import.Store.id = 0;
		Calendar_Import.Store.method = '';
		Calendar_Import.Store.calname = '';
		Calendar_Import.Store.progresskey = '';
		Calendar_Import.Store.percentage = 0;
	}
}
$(document).ready(function(){
	if(typeof FileActions !== 'undefined'){
		FileActions.register('text/calendar','importCalendar', '', Calendar_Import.Dialog.open); 
		FileActions.setDefault('text/calendar','importCalendar');
	};
});
