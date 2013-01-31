
$(document).ready(function() {

	if (typeof FileActions !== 'undefined') {
		FileActions.register('all', 'Restore', OC.PERMISSION_READ,  OC.imagePath('core', 'actions/undelete.png'), function(filename) {
			var tr=$('tr').filterAttr('data-file', filename);
			var spinner = '<img class="move2trash" title="'+t('files_trashbin', 'perform restore operation')+'" src="'+ OC.imagePath('core', 'loader.gif') +'"></a>';
			var undeleteAction = $('tr').filterAttr('data-file',filename).children("td.date");
			undeleteAction[0].innerHTML = undeleteAction[0].innerHTML+spinner;
			$.post(OC.filePath('files_trashbin','ajax','undelete.php'),
				{files:tr.attr('data-file'), dirlisting:tr.attr('data-dirlisting') },
				function(result){
					for (var i = 0; i < result.data.success.length; i++) {
						var row = document.getElementById(result.data.success[i].filename);
						row.parentNode.removeChild(row);
					}
					if (result.status != 'success') {
						OC.dialogs.alert(result.data.message, 'Error');
					}
				});
			
			});
		};
		
		// Sets the select_all checkbox behaviour :
		$('#select_all').click(function() {
			if($(this).attr('checked')){
				// Check all
				$('td.filename input:checkbox').attr('checked', true);
				$('td.filename input:checkbox').parent().parent().addClass('selected');
			}else{
				// Uncheck all
				$('td.filename input:checkbox').attr('checked', false);
				$('td.filename input:checkbox').parent().parent().removeClass('selected');
			}
			processSelection();
		});

		$('td.filename input:checkbox').live('change',function(event) {
			if (event.shiftKey) {
				var last = $(lastChecked).parent().parent().prevAll().length;
				var first = $(this).parent().parent().prevAll().length;
				var start = Math.min(first, last);
				var end = Math.max(first, last);
				var rows = $(this).parent().parent().parent().children('tr');
				for (var i = start; i < end; i++) {
					$(rows).each(function(index) {
						if (index == i) {
							var checkbox = $(this).children().children('input:checkbox');
							$(checkbox).attr('checked', 'checked');
							$(checkbox).parent().parent().addClass('selected');
						}
					});
				}
			}
			var selectedCount=$('td.filename input:checkbox:checked').length;
			$(this).parent().parent().toggleClass('selected');
			if(!$(this).attr('checked')){
				$('#select_all').attr('checked',false);
			}else{
				if(selectedCount==$('td.filename input:checkbox').length){
					$('#select_all').attr('checked',true);
				}
			}
			processSelection();
		});		
		
		$('.undelete').click('click',function(event) {
			var spinner = '<img class="move2trash" title="'+t('files_trashbin', 'perform undelete operation')+'" src="'+ OC.imagePath('core', 'loader.gif') +'"></a>';
			var files=getSelectedFiles('file');
			var fileslist=files.join(';');
			var dirlisting=getSelectedFiles('dirlisting')[0];
			
			for (var i in files) {
				var undeleteAction = $('tr').filterAttr('data-file',files[i]).children("td.date");
				undeleteAction[0].innerHTML = undeleteAction[0].innerHTML+spinner;
			}
			
			$.post(OC.filePath('files_trashbin','ajax','undelete.php'),
					{files:fileslist, dirlisting:dirlisting},
					function(result){
						for (var i = 0; i < result.data.success.length; i++) {
							var row = document.getElementById(result.data.success[i].filename);
							row.parentNode.removeChild(row);
						}
						if (result.status != 'success') {
							OC.dialogs.alert(result.data.message, 'Error');
						}
					});		
			});
	

});

function processSelection(){
	var selected=getSelectedFiles();
	var selectedFiles=selected.filter(function(el){return el.type=='file'});
	var selectedFolders=selected.filter(function(el){return el.type=='dir'});
	if(selectedFiles.length==0 && selectedFolders.length==0) {
		$('#headerName>span.name').text(t('files','Name'));
		$('#modified').text(t('files','Deleted'));
		$('table').removeClass('multiselect');
		$('.selectedActions').hide();
	}
	else {
		$('.selectedActions').show();
		var selection='';
		if(selectedFolders.length>0){
			if(selectedFolders.length==1){
				selection+=t('files','1 folder');
			}else{
				selection+=t('files','{count} folders',{count: selectedFolders.length});
			}
			if(selectedFiles.length>0){
				selection+=' & ';
			}
		}
		if(selectedFiles.length>0){
			if(selectedFiles.length==1){
				selection+=t('files','1 file');
			}else{
				selection+=t('files','{count} files',{count: selectedFiles.length});
			}
		}
		$('#headerName>span.name').text(selection);
		$('#modified').text('');
		$('table').addClass('multiselect');
	}
}

/**
 * @brief get a list of selected files
 * @param string property (option) the property of the file requested
 * @return array
 *
 * possible values for property: name, mime, size and type
 * if property is set, an array with that property for each file is returnd
 * if it's ommited an array of objects with all properties is returned
 */
function getSelectedFiles(property){
	var elements=$('td.filename input:checkbox:checked').parent().parent();
	var files=[];
	elements.each(function(i,element){
		var file={
			name:$(element).attr('data-filename'),
			file:$(element).attr('data-file'),
			timestamp:$(element).attr('data-timestamp'),
			type:$(element).attr('data-type'),
			dirlisting:$(element).attr('data-dirlisting')
		};
		if(property){
			files.push(file[property]);
		}else{
			files.push(file);
		}
	});
	return files;
}