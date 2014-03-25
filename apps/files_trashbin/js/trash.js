
$(document).ready(function() {

	Files.updateStorageStatistics = function() {
		// no op because the trashbin doesn't have
		// storage info like free space / used space
	};

	if (typeof FileActions !== 'undefined') {
		FileActions.register('all', 'Restore', OC.PERMISSION_READ, OC.imagePath('core', 'actions/history'), function(filename) {
			var tr = FileList.findFileEl(filename);
			var deleteAction = tr.children("td.date").children(".action.delete");
			deleteAction.removeClass('delete-icon').addClass('progress-icon');
			disableActions();
			$.post(OC.filePath('files_trashbin', 'ajax', 'undelete.php'),
					{files: JSON.stringify([$('#dir').val() + '/' + filename]), dirlisting: tr.attr('data-dirlisting')},
					function(result) {
						for (var i = 0; i < result.data.success.length; i++) {
							var row = document.getElementById(result.data.success[i].filename);
							row.parentNode.removeChild(row);
						}
						if (result.status !== 'success') {
							OC.dialogs.alert(result.data.message, t('core', 'Error'));
						}
						enableActions();
						FileList.updateFileSummary();
						FileList.updateEmptyContent();
					}
			);

		});
	};

	FileActions.register('all', 'Delete', OC.PERMISSION_READ, function() {
		return OC.imagePath('core', 'actions/delete');
	}, function(filename) {
		$('.tipsy').remove();
		var tr = FileList.findFileEl(filename);
		var deleteAction = tr.children("td.date").children(".action.delete");
		deleteAction.removeClass('delete-icon').addClass('progress-icon');
		disableActions();
		$.post(OC.filePath('files_trashbin', 'ajax', 'delete.php'),
				{files: JSON.stringify([$('#dir').val() + '/' +filename]), dirlisting: tr.attr('data-dirlisting')},
				function(result) {
					for (var i = 0; i < result.data.success.length; i++) {
						var row = document.getElementById(result.data.success[i].filename);
						row.parentNode.removeChild(row);
					}
					if (result.status !== 'success') {
						OC.dialogs.alert(result.data.message, t('core', 'Error'));
					}
					enableActions();
					FileList.updateFileSummary();
					FileList.updateEmptyContent();
				}
		);

	});

	// Sets the select_all checkbox behaviour :
	$('#select_all').click(function() {
		if ($(this).attr('checked')) {
			// Check all
			$('td.filename input:checkbox').attr('checked', true);
			$('td.filename input:checkbox').parent().parent().addClass('selected');
		} else {
			// Uncheck all
			$('td.filename input:checkbox').attr('checked', false);
			$('td.filename input:checkbox').parent().parent().removeClass('selected');
		}
		procesSelection();
	});

	$('.undelete').click('click', function(event) {
		event.preventDefault();
		var files = getSelectedFiles('file');
		var fileslist = JSON.stringify(files);
		var dirlisting = getSelectedFiles('dirlisting')[0];
		disableActions();
		for (var i = 0; i < files.length; i++) {
			var deleteAction = FileList.findFileEl(files[i]).children("td.date").children(".action.delete");
			deleteAction.removeClass('delete-icon').addClass('progress-icon');
		}

		$.post(OC.filePath('files_trashbin', 'ajax', 'undelete.php'),
				{files: fileslist, dirlisting: dirlisting},
				function(result) {
					for (var i = 0; i < result.data.success.length; i++) {
						var row = document.getElementById(result.data.success[i].filename);
						row.parentNode.removeChild(row);
					}
					if (result.status !== 'success') {
						OC.dialogs.alert(result.data.message, t('core', 'Error'));
					}
					enableActions();
					FileList.updateFileSummary();
					FileList.updateEmptyContent();
				}
		);
	});

	$('.delete').click('click', function(event) {
		event.preventDefault();
		var allFiles = $('#select_all').is(':checked');
		var files = [];
		var params = {};
		if (allFiles) {
			params = {
			   allfiles: true,
			   dir: $('#dir').val()
			};
		}
		else {
			files = getSelectedFiles('file');
			params = {
				files: JSON.stringify(files),
				dirlisting: getSelectedFiles('dirlisting')[0]
			};
		};

		disableActions();
		if (allFiles) {
			FileList.showMask();
		}
		else {
			for (var i = 0; i < files.length; i++) {
				var deleteAction = FileList.findFileEl(files[i]).children("td.date").children(".action.delete");
				deleteAction.removeClass('delete-icon').addClass('progress-icon');
			}
		}

		$.post(OC.filePath('files_trashbin', 'ajax', 'delete.php'),
				params,
				function(result) {
					if (allFiles) {
						FileList.hideMask();
						// simply remove all files
						$('#fileList').empty();
					}
					else {
						for (var i = 0; i < result.data.success.length; i++) {
							var row = document.getElementById(result.data.success[i].filename);
							row.parentNode.removeChild(row);
						}
					}
					if (result.status !== 'success') {
						OC.dialogs.alert(result.data.message, t('core', 'Error'));
					}
					enableActions();
					FileList.updateFileSummary();
					FileList.updateEmptyContent();
				}
		);

	});

	$('#fileList').on('click', 'td.filename input', function() {
		var checkbox = $(this).parent().children('input:checkbox');
		$(checkbox).parent().parent().toggleClass('selected');
		if ($(checkbox).is(':checked')) {
			var selectedCount = $('td.filename input:checkbox:checked').length;
			if (selectedCount === $('td.filename input:checkbox').length) {
				$('#select_all').prop('checked', true);
			}
		} else {
			$('#select_all').prop('checked',false);
		}
		procesSelection();
	});

	$('#fileList').on('click', 'td.filename a', function(event) {
		var mime = $(this).parent().parent().data('mime');
		if (mime !== 'httpd/unix-directory') {
			event.preventDefault();
		}
		var filename = $(this).parent().parent().attr('data-file');
		var tr = FileList.findFileEl(filename);
		var renaming = tr.data('renaming');
		if(!renaming && !FileList.isLoading(filename)){
			if(mime.substr(0, 5) === 'text/'){ //no texteditor for now
				return;
			}
			var type = $(this).parent().parent().data('type');
			var permissions = $(this).parent().parent().data('permissions');
			var action = FileActions.getDefault(mime, type, permissions);
			if(action){
				event.preventDefault();
				action(filename);
			}
		}

		// event handlers for breadcrumb items
		$('#controls').delegate('.crumb:not(.home) a', 'click', onClickBreadcrumb);
	});

	FileActions.actions.dir = {
		// only keep 'Open' action for navigation
		'Open': FileActions.actions.dir.Open
	};
});

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
			file:$('#dir').val() + "/" + $(element).attr('data-file'),
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

function fileDownloadPath(dir, file) {
	return OC.filePath('files_trashbin', '', 'download.php') + '?file='+encodeURIComponent(file);
}

function enableActions() {
	$(".action").css("display", "inline");
	$(":input:checkbox").css("display", "inline");
}

function disableActions() {
	$(".action").css("display", "none");
	$(":input:checkbox").css("display", "none");
}

