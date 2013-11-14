var FileList={
	useUndo:true,
	postProcessList: function() {
		$('#fileList tr').each(function() {
			//little hack to set unescape filenames in attribute
			$(this).attr('data-file',decodeURIComponent($(this).attr('data-file')));
		});
	},
	update:function(fileListHtml) {
		var $fileList = $('#fileList');
		$fileList.empty().html(fileListHtml);
		FileList.updateEmptyContent();
		$fileList.find('tr').each(function () {
			FileActions.display($(this).children('td.filename'));
		});
		$fileList.trigger(jQuery.Event("fileActionsReady"));
		FileList.postProcessList();
		// "Files" might not be loaded in extending apps
		if (window.Files) {
			Files.setupDragAndDrop();
		}
		FileList.updateFileSummary();
		$fileList.trigger(jQuery.Event("updated"));
	},
	createRow:function(type, name, iconurl, linktarget, size, lastModified, permissions) {
		var td, simpleSize, basename, extension;
		//containing tr
		var tr = $('<tr></tr>').attr({
			"data-type": type,
			"data-size": size,
			"data-file": name,
			"data-permissions": permissions
		});
		// filename td
		td = $('<td></td>').attr({
			"class": "filename",
			"style": 'background-image:url('+iconurl+'); background-size: 32px;'
		});
		var rand = Math.random().toString(16).slice(2);
		td.append('<input id="select-'+rand+'" type="checkbox" /><label for="select-'+rand+'"></label>');
		var link_elem = $('<a></a>').attr({
			"class": "name",
			"href": linktarget
		});
		//split extension from filename for non dirs
		if (type !== 'dir' && name.indexOf('.') !== -1) {
			basename=name.substr(0,name.lastIndexOf('.'));
			extension=name.substr(name.lastIndexOf('.'));
		} else {
			basename=name;
			extension=false;
		}
		var name_span=$('<span></span>').addClass('nametext').text(basename);
		link_elem.append(name_span);
		if (extension) {
			name_span.append($('<span></span>').addClass('extension').text(extension));
		}
		//dirs can show the number of uploaded files
		if (type === 'dir') {
			link_elem.append($('<span></span>').attr({
				'class': 'uploadtext',
				'currentUploads': 0
			}));
		}
		td.append(link_elem);
		tr.append(td);

		//size column
		if (size !== t('files', 'Pending')) {
			simpleSize = humanFileSize(size);
		} else {
			simpleSize=t('files', 'Pending');
		}
		var sizeColor = Math.round(160-Math.pow((size/(1024*1024)),2));
		var lastModifiedTime = Math.round(lastModified.getTime() / 1000);
		td = $('<td></td>').attr({
			"class": "filesize",
			"style": 'color:rgb('+sizeColor+','+sizeColor+','+sizeColor+')'
		}).text(simpleSize);
		tr.append(td);

		// date column
		var modifiedColor = Math.round((Math.round((new Date()).getTime() / 1000)-lastModifiedTime)/60/60/24*5);
		td = $('<td></td>').attr({ "class": "date" });
		td.append($('<span></span>').attr({
			"class": "modified",
			"title": formatDate(lastModified),
			"style": 'color:rgb('+modifiedColor+','+modifiedColor+','+modifiedColor+')'
		}).text( relative_modified_date(lastModified.getTime() / 1000) ));
		tr.append(td);
		return tr;
	},
	addFile:function(name, size, lastModified, loading, hidden, param) {
		var imgurl;

		if (!param) {
			param = {};
		}

		var download_url = null;
		if (!param.download_url) {
			download_url = OC.Router.generate('download', { file: $('#dir').val()+'/'+name });
		} else {
			download_url = param.download_url;
		}

		if (loading) {
			imgurl = OC.imagePath('core', 'loading.gif');
		} else {
			imgurl = OC.imagePath('core', 'filetypes/file.png');
		}
		var tr = this.createRow(
			'file',
			name,
			imgurl,
			download_url,
			size,
			lastModified,
			$('#permissions').val()
		);

		FileList.insertElement(name, 'file', tr);
		if (loading) {
			tr.data('loading', true);
		} else {
			tr.find('td.filename').draggable(dragOptions);
		}
		if (hidden) {
			tr.hide();
		}
		return tr;
	},
	addDir:function(name, size, lastModified, hidden) {

		var tr = this.createRow(
			'dir',
			name,
			OC.imagePath('core', 'filetypes/folder.png'),
			OC.linkTo('files', 'index.php')+"?dir="+ encodeURIComponent($('#dir').val()+'/'+name).replace(/%2F/g, '/'),
			size,
			lastModified,
			$('#permissions').val()
		);

		FileList.insertElement(name, 'dir', tr);
		var td = tr.find('td.filename');
		td.draggable(dragOptions);
		td.droppable(folderDropOptions);
		if (hidden) {
			tr.hide();
		}
		FileActions.display(tr.find('td.filename'), true);
		return tr;
	},
	getCurrentDirectory: function(){
		return $('#dir').val() || '/';
	},
	/**
	 * @brief Changes the current directory and reload the file list.
	 * @param targetDir target directory (non URL encoded)
	 * @param changeUrl false if the URL must not be changed (defaults to true)
	 * @param {boolean} force set to true to force changing directory
	 */
	changeDirectory: function(targetDir, changeUrl, force) {
		var $dir = $('#dir'),
			url,
			currentDir = $dir.val() || '/';
		targetDir = targetDir || '/';
		if (!force && currentDir === targetDir) {
			return;
		}
		FileList.setCurrentDir(targetDir, changeUrl);
		$('#fileList').trigger(
			jQuery.Event('changeDirectory', {
				dir: targetDir,
				previousDir: currentDir
			}
		));
		FileList.reload();
	},
	linkTo: function(dir) {
		return OC.linkTo('files', 'index.php')+"?dir="+ encodeURIComponent(dir).replace(/%2F/g, '/');
	},
	setCurrentDir: function(targetDir, changeUrl) {
		$('#dir').val(targetDir);
		if (changeUrl !== false) {
			if (window.history.pushState && changeUrl !== false) {
				url = FileList.linkTo(targetDir);
				window.history.pushState({dir: targetDir}, '', url);
			}
			// use URL hash for IE8
			else{
				window.location.hash = '?dir='+ encodeURIComponent(targetDir).replace(/%2F/g, '/');
			}
		}
	},
	/**
	 * @brief Reloads the file list using ajax call
	 */
	reload: function() {
		FileList.showMask();
		if (FileList._reloadCall) {
			FileList._reloadCall.abort();
		}
		FileList._reloadCall = $.ajax({
			url: OC.filePath('files','ajax','list.php'),
			data: {
				dir : $('#dir').val(),
				breadcrumb: true
			},
			error: function(result) {
				FileList.reloadCallback(result);
			},
			success: function(result) {
				FileList.reloadCallback(result);
			}
		});
	},
	reloadCallback: function(result) {
		var $controls = $('#controls');

		delete FileList._reloadCall;
		FileList.hideMask();

		if (!result || result.status === 'error') {
			OC.Notification.show(result.data.message);
			return;
		}

		if (result.status === 404) {
			// go back home
			FileList.changeDirectory('/');
			return;
		}

		// TODO: should rather return upload file size through
		// the files list ajax call
		Files.updateStorageStatistics(true);

		if (result.data.permissions) {
			FileList.setDirectoryPermissions(result.data.permissions);
		}

		if (typeof(result.data.breadcrumb) !== 'undefined') {
			$controls.find('.crumb').remove();
			$controls.prepend(result.data.breadcrumb);

			var width = $(window).width();
			Files.initBreadCrumbs();
			Files.resizeBreadcrumbs(width, true);

			// in case svg is not supported by the browser we need to execute the fallback mechanism
			if (!SVGSupport()) {
				replaceSVG();
			}
		}

		FileList.update(result.data.files);
	},
	setDirectoryPermissions: function(permissions) {
		var isCreatable = (permissions & OC.PERMISSION_CREATE) !== 0;
		$('#permissions').val(permissions);
		$('.creatable').toggleClass('hidden', !isCreatable);
		$('.notCreatable').toggleClass('hidden', isCreatable);
	},
	/**
	 * Shows/hides action buttons
	 *
	 * @param show true for enabling, false for disabling
	 */
	showActions: function(show){
		$('.actions,#file_action_panel').toggleClass('hidden', !show);
		if (show){
			// make sure to display according to permissions
			var permissions =  $('#permissions').val();
			var isCreatable = (permissions & OC.PERMISSION_CREATE) !== 0;
			$('.creatable').toggleClass('hidden', !isCreatable);
			$('.notCreatable').toggleClass('hidden', isCreatable);
		}
		else{
			$('.creatable, .notCreatable').addClass('hidden');
		}
	},
	/**
	 * Enables/disables viewer mode.
	 * In viewer mode, apps can embed themselves under the controls bar.
	 * In viewer mode, the actions of the file list will be hidden.
	 * @param show true for enabling, false for disabling
	 */
	setViewerMode: function(show){
		this.showActions(!show);
		$('#filestable').toggleClass('hidden', show);
	},
	remove:function(name){
		$('tr').filterAttr('data-file',name).find('td.filename').draggable('destroy');
		$('tr').filterAttr('data-file',name).remove();
		FileList.updateFileSummary();
		if ( ! $('tr[data-file]').exists() ) {
			$('#emptycontent').removeClass('hidden');
			$('#filescontent th').addClass('hidden');
		}
	},
	insertElement:function(name, type, element) {
		//find the correct spot to insert the file or folder
		var pos, fileElements=$('tr[data-file][data-type="'+type+'"]:visible');
		if (name.localeCompare($(fileElements[0]).attr('data-file')) < 0) {
			pos = -1;
		} else if (name.localeCompare($(fileElements[fileElements.length-1]).attr('data-file')) > 0) {
			pos = fileElements.length - 1;
		} else {
			for(pos = 0; pos<fileElements.length-1; pos++) {
				if (name.localeCompare($(fileElements[pos]).attr('data-file')) > 0
					&& name.localeCompare($(fileElements[pos+1]).attr('data-file')) < 0)
				{
					break;
				}
			}
		}
		if (fileElements.exists()) {
			if (pos === -1) {
				$(fileElements[0]).before(element);
			} else {
				$(fileElements[pos]).after(element);
			}
		} else if (type === 'dir' && $('tr[data-file]').exists()) {
			$('tr[data-file]').first().before(element);
		} else if (type === 'file' && $('tr[data-file]').exists()) {
			$('tr[data-file]').last().before(element);
		} else {
			$('#fileList').append(element);
		}
		$('#emptycontent').addClass('hidden');
		$('#filestable th').removeClass('hidden');
		FileList.updateFileSummary();
	},
	loadingDone:function(name, id) {
		var mime, tr = $('tr[data-file="'+name+'"]');
		tr.data('loading', false);
		mime = tr.data('mime');
		tr.attr('data-mime', mime);
		if (id) {
			tr.attr('data-id', id);
		}
		var path = getPathForPreview(name);
		Files.lazyLoadPreview(path, mime, function(previewpath) {
			tr.find('td.filename').attr('style','background-image:url('+previewpath+')');
		}, null, null, tr.attr('data-etag'));
		tr.find('td.filename').draggable(dragOptions);
	},
	isLoading:function(name) {
		return $('tr[data-file="'+name+'"]').data('loading');
	},
	rename:function(oldname) {
		var tr, td, input, form;
		tr = $('tr[data-file="'+oldname+'"]');
		tr.data('renaming',true);
		td = tr.children('td.filename');
		input = $('<input type="text" class="filename"/>').val(oldname);
		form = $('<form></form>');
		form.append(input);
		td.children('a.name').hide();
		td.append(form);
		input.focus();
		//preselect input
		var len = input.val().lastIndexOf('.');
		if (len === -1) {
			len = input.val().length;
		}
		input.selectRange(0, len);

		var checkInput = function () {
			var filename = input.val();
			if (filename !== oldname) {
				if (!Files.isFileNameValid(filename)) {
					// Files.isFileNameValid(filename) throws an exception itself
				} else if($('#dir').val() === '/' && filename === 'Shared') {
					throw t('files','In the home folder \'Shared\' is a reserved filename');
				} else if (FileList.inList(filename)) {
					throw t('files', '{new_name} already exists', {new_name: filename});
				}
			}
			return true;
		};
		
		form.submit(function(event) {
			event.stopPropagation();
			event.preventDefault();
			try {
				var newname = input.val();
				if (newname !== oldname) {
					checkInput();
					// save background image, because it's replaced by a spinner while async request
					var oldBackgroundImage = td.css('background-image');
					// mark as loading
					td.css('background-image', 'url('+ OC.imagePath('core', 'loading.gif') + ')');
					$.ajax({
						url: OC.filePath('files','ajax','rename.php'),
						data: {
							dir : $('#dir').val(),
							newname: newname,
							file: oldname
						},
						success: function(result) {
							if (!result || result.status === 'error') {
								OC.dialogs.alert(result.data.message, t('core', 'Could not rename file'));
								// revert changes
								newname = oldname;
								tr.attr('data-file', newname);
								var path = td.children('a.name').attr('href');
								td.children('a.name').attr('href', path.replace(encodeURIComponent(oldname), encodeURIComponent(newname)));
								if (newname.indexOf('.') > 0 && tr.data('type') !== 'dir') {
									var basename=newname.substr(0,newname.lastIndexOf('.'));
								} else {
									var basename=newname;
								}
								td.find('a.name span.nametext').text(basename);
								if (newname.indexOf('.') > 0 && tr.data('type') !== 'dir') {
									if ( ! td.find('a.name span.extension').exists() ) {
										td.find('a.name span.nametext').append('<span class="extension"></span>');
									}
									td.find('a.name span.extension').text(newname.substr(newname.lastIndexOf('.')));
								}
								tr.find('.fileactions').effect('highlight', {}, 5000);
								tr.effect('highlight', {}, 5000);
							}
							// reinsert row
							tr.detach();
							FileList.insertElement( tr.attr('data-file'), tr.attr('data-type'),tr );
							// remove loading mark and recover old image
							td.css('background-image', oldBackgroundImage);
						}
					});
				}
				input.tipsy('hide');
				tr.data('renaming',false);
				tr.attr('data-file', newname);
				var path = td.children('a.name').attr('href');
				// FIXME this will fail if the path contains the filename.
				td.children('a.name').attr('href', path.replace(encodeURIComponent(oldname), encodeURIComponent(newname)));
				var basename = newname;
				if (newname.indexOf('.') > 0 && tr.data('type') !== 'dir') {
					basename = newname.substr(0, newname.lastIndexOf('.'));
				} 
				td.find('a.name span.nametext').text(basename);
				if (newname.indexOf('.') > 0 && tr.data('type') !== 'dir') {
					if ( ! td.find('a.name span.extension').exists() ) {
						td.find('a.name span.nametext').append('<span class="extension"></span>');
					}
					td.find('a.name span.extension').text(newname.substr(newname.lastIndexOf('.')));
				}
				form.remove();
				td.children('a.name').show();
			} catch (error) {
				input.attr('title', error);
				input.tipsy({gravity: 'w', trigger: 'manual'});
				input.tipsy('show');
				input.addClass('error');
			}
			return false;
		});
		input.keyup(function(event) {
			// verify filename on typing
			try {
				checkInput();
				input.tipsy('hide');
				input.removeClass('error');
			} catch (error) {
				input.attr('title', error);
				input.tipsy({gravity: 'w', trigger: 'manual'});
				input.tipsy('show');
				input.addClass('error');
			}
			if (event.keyCode === 27) {
				input.tipsy('hide');
				tr.data('renaming',false);
				form.remove();
				td.children('a.name').show();
			}
		});
		input.click(function(event) {
			event.stopPropagation();
			event.preventDefault();
		});
		input.blur(function() {
			form.trigger('submit');
		});
	},
	inList:function(filename) {
		return $('#fileList tr[data-file="'+filename+'"]').length;
	},
	replace:function(oldName, newName, isNewFile) {
		// Finish any existing actions
		$('tr[data-file="'+oldName+'"]').hide();
		$('tr[data-file="'+newName+'"]').hide();
		var tr = $('tr[data-file="'+oldName+'"]').clone();
		tr.attr('data-replace', 'true');
		tr.attr('data-file', newName);
		var td = tr.children('td.filename');
		td.children('a.name .span').text(newName);
		var path = td.children('a.name').attr('href');
		td.children('a.name').attr('href', path.replace(encodeURIComponent(oldName), encodeURIComponent(newName)));
		if (newName.indexOf('.') > 0) {
			var basename = newName.substr(0, newName.lastIndexOf('.'));
		} else {
			var basename = newName;
		}
		td.children('a.name').empty();
		var span = $('<span class="nametext"></span>');
		span.text(basename);
		td.children('a.name').append(span);
		if (newName.indexOf('.') > 0) {
			span.append($('<span class="extension">'+newName.substr(newName.lastIndexOf('.'))+'</span>'));
		}
		FileList.insertElement(newName, tr.data('type'), tr);
		tr.show();
		FileList.replaceCanceled = false;
		FileList.replaceOldName = oldName;
		FileList.replaceNewName = newName;
		FileList.replaceIsNewFile = isNewFile;
		FileList.lastAction = function() {
			FileList.finishReplace();
		};
		if (!isNewFile) {
			OC.Notification.showHtml(t('files', 'replaced {new_name} with {old_name}', {new_name: newName}, {old_name: oldName})+'<span class="undo">'+t('files', 'undo')+'</span>');
		}
	},
	finishReplace:function() {
		if (!FileList.replaceCanceled && FileList.replaceOldName && FileList.replaceNewName) {
			$.ajax({url: OC.filePath('files', 'ajax', 'rename.php'), async: false, data: { dir: $('#dir').val(), newname: FileList.replaceNewName, file: FileList.replaceOldName }, success: function(result) {
				if (result && result.status === 'success') {
					$('tr[data-replace="true"').removeAttr('data-replace');
				} else {
					OC.dialogs.alert(result.data.message, 'Error moving file');
				}
				FileList.replaceCanceled = true;
				FileList.replaceOldName = null;
				FileList.replaceNewName = null;
				FileList.lastAction = null;
			}});
		}
	},
	do_delete:function(files) {
		if (files.substr) {
			files=[files];
		}
		for (var i=0; i<files.length; i++) {
			var deleteAction = $('tr[data-file="'+files[i]+'"]').children("td.date").children(".action.delete");
			deleteAction.removeClass('delete-icon').addClass('progress-icon');
		}
		// Finish any existing actions
		if (FileList.lastAction) {
			FileList.lastAction();
		}

		var fileNames = JSON.stringify(files);
		$.post(OC.filePath('files', 'ajax', 'delete.php'),
				{dir:$('#dir').val(),files:fileNames},
				function(result) {
					if (result.status === 'success') {
						$.each(files,function(index,file) {
							var files = $('tr[data-file="'+file+'"]');
							files.remove();
							files.find('input[type="checkbox"]').removeAttr('checked');
							files.removeClass('selected');
						});
						procesSelection();
						checkTrashStatus();
						FileList.updateFileSummary();
						FileList.updateEmptyContent();
						Files.updateStorageStatistics();
					} else {
						if (result.status === 'error' && result.data.message) {
							OC.Notification.show(result.data.message);
						}
						else {
							OC.Notification.show(t('files', 'Error deleting file.'));
						}
						// hide notification after 10 sec
						setTimeout(function() {
							OC.Notification.hide();
						}, 10000);
						$.each(files,function(index,file) {
							var deleteAction = $('tr[data-file="' + file + '"] .action.delete');
							deleteAction.removeClass('progress-icon').addClass('delete-icon');
						});
					}
				});
	},
	createFileSummary: function() {
		if( $('#fileList tr').exists() ) {
			var summary = this._calculateFileSummary();

			// Get translations
			var directoryInfo = n('files', '%n folder', '%n folders', summary.totalDirs);
			var fileInfo = n('files', '%n file', '%n files', summary.totalFiles);

			var infoVars = {
				dirs: '<span class="dirinfo">'+directoryInfo+'</span><span class="connector">',
				files: '</span><span class="fileinfo">'+fileInfo+'</span>'
			};

			var info = t('files', '{dirs} and {files}', infoVars);

			// don't show the filesize column, if filesize is NaN (e.g. in trashbin)
			if (isNaN(summary.totalSize)) {
				var fileSize = '';
			} else {
				var fileSize = '<td class="filesize">'+humanFileSize(summary.totalSize)+'</td>';
			}

			var $summary = $('<tr class="summary"><td><span class="info">'+info+'</span></td>'+fileSize+'<td></td></tr>');
			$('#fileList').append($summary);

			var $dirInfo = $summary.find('.dirinfo');
			var $fileInfo = $summary.find('.fileinfo');
			var $connector = $summary.find('.connector');

			// Show only what's necessary, e.g.: no files: don't show "0 files"
			if (summary.totalDirs === 0) {
				$dirInfo.hide();
				$connector.hide();
			}
			if (summary.totalFiles === 0) {
				$fileInfo.hide();
				$connector.hide();
			}
		}
	},
	_calculateFileSummary: function() {
		var result = {
			totalDirs: 0,
			totalFiles: 0,
			totalSize: 0
		};
		$.each($('tr[data-file]'), function(index, value) {
			var $value = $(value);
			if ($value.data('type') === 'dir') {
				result.totalDirs++;
			} else if ($value.data('type') === 'file') {
				result.totalFiles++;
			}
			if ($value.data('size') !== undefined && $value.data('id') !== -1) {
				//Skip shared as it does not count toward quota
				result.totalSize += parseInt($value.data('size'));
			}
		});
		return result;
	},
	updateFileSummary: function() {
		var $summary = $('.summary');

		// Check if we should remove the summary to show "Upload something"
		if ($('#fileList tr').length === 1 && $summary.length === 1) {
			$summary.remove();
		}
		// If there's no summary create one (createFileSummary checks if there's data)
		else if ($summary.length === 0) {
			FileList.createFileSummary();
		}
		// There's a summary and data -> Update the summary
		else if ($('#fileList tr').length > 1 && $summary.length === 1) {
			var fileSummary = this._calculateFileSummary();
			var $dirInfo = $('.summary .dirinfo');
			var $fileInfo = $('.summary .fileinfo');
			var $connector = $('.summary .connector');

			// Substitute old content with new translations
			$dirInfo.html(n('files', '%n folder', '%n folders', fileSummary.totalDirs));
			$fileInfo.html(n('files', '%n file', '%n files', fileSummary.totalFiles));
			$('.summary .filesize').html(humanFileSize(fileSummary.totalSize));

			// Show only what's necessary (may be hidden)
			if (fileSummary.totalDirs === 0) {
				$dirInfo.hide();
				$connector.hide();
			} else {
				$dirInfo.show();
			}
			if (fileSummary.totalFiles === 0) {
				$fileInfo.hide();
				$connector.hide();
			} else {
				$fileInfo.show();
			}
			if (fileSummary.totalDirs > 0 && fileSummary.totalFiles > 0) {
				$connector.show();
			}
		}
	},
	updateEmptyContent: function() {
		var $fileList = $('#fileList');
		var permissions = $('#permissions').val();
		var isCreatable = (permissions & OC.PERMISSION_CREATE) !== 0;
		$('#emptycontent').toggleClass('hidden', !isCreatable || $fileList.find('tr').exists());
		$('#filestable th').toggleClass('hidden', $fileList.find('tr').exists() === false);
	},
	showMask: function() {
		// in case one was shown before
		var $mask = $('#content .mask');
		if ($mask.exists()) {
			return;
		}

		$mask = $('<div class="mask transparent"></div>');

		$mask.css('background-image', 'url('+ OC.imagePath('core', 'loading.gif') + ')');
		$mask.css('background-repeat', 'no-repeat');
		$('#content').append($mask);

		// block UI, but only make visible in case loading takes longer
		FileList._maskTimeout = window.setTimeout(function() {
			// reset opacity
			$mask.removeClass('transparent');
		}, 250);
	},
	hideMask: function() {
		var $mask = $('#content .mask').remove();
		if (FileList._maskTimeout) {
			window.clearTimeout(FileList._maskTimeout);
		}
	},
	scrollTo:function(file) {
		//scroll to and highlight preselected file
		var $scrolltorow = $('tr[data-file="'+file+'"]');
		if ($scrolltorow.exists()) {
			$scrolltorow.addClass('searchresult');
			$(window).scrollTop($scrolltorow.position().top);
			//remove highlight when hovered over
			$scrolltorow.one('hover', function() {
				$scrolltorow.removeClass('searchresult');
			});
		}
	},
	filter:function(query) {
		$('#fileList tr:not(.summary)').each(function(i,e) {
			if ($(e).data('file').toString().toLowerCase().indexOf(query.toLowerCase()) !== -1) {
				$(e).addClass("searchresult");
			} else {
				$(e).removeClass("searchresult");
			}
		});
		//do not use scrollto to prevent removing searchresult css class
		var first = $('#fileList tr.searchresult').first();
		if (first.exists()) {
			$(window).scrollTop(first.position().top);
		}
	},
	unfilter:function() {
		$('#fileList tr.searchresult').each(function(i,e) {
			$(e).removeClass("searchresult");
		});
	}
};

$(document).ready(function() {
	var isPublic = !!$('#isPublic').val();

	// handle upload events
	var file_upload_start = $('#file_upload_start');

	file_upload_start.on('fileuploaddrop', function(e, data) {
		OC.Upload.log('filelist handle fileuploaddrop', e, data);

		var dropTarget = $(e.originalEvent.target).closest('tr, .crumb');
		if (dropTarget && (dropTarget.data('type') === 'dir' || dropTarget.hasClass('crumb'))) { // drag&drop upload to folder

			// remember as context
			data.context = dropTarget;

			var dir = dropTarget.data('file');
			// if from file list, need to prepend parent dir
			if (dir) {
				var parentDir = $('#dir').val() || '/';
				if (parentDir[parentDir.length - 1] !== '/') {
					parentDir += '/';
				}
				dir = parentDir + dir;
			}
			else{
				// read full path from crumb
				dir = dropTarget.data('dir') || '/';
			}

			// update folder in form
			data.formData = function(form) {
				return [
					{name: 'dir', value: dir},
					{name: 'requesttoken', value: oc_requesttoken}
				];
			};
		} 

	});
	file_upload_start.on('fileuploadadd', function(e, data) {
		OC.Upload.log('filelist handle fileuploadadd', e, data);

		//finish delete if we are uploading a deleted file
		if (FileList.deleteFiles && FileList.deleteFiles.indexOf(data.files[0].name)!==-1) {
			FileList.finishDelete(null, true); //delete file before continuing
		}

		// add ui visualization to existing folder
		if (data.context && data.context.data('type') === 'dir') {
			// add to existing folder

			// update upload counter ui
			var uploadtext = data.context.find('.uploadtext');
			var currentUploads = parseInt(uploadtext.attr('currentUploads'));
			currentUploads += 1;
			uploadtext.attr('currentUploads', currentUploads);

			var translatedText = n('files', 'Uploading %n file', 'Uploading %n files', currentUploads);
			if (currentUploads === 1) {
				var img = OC.imagePath('core', 'loading.gif');
				data.context.find('td.filename').attr('style','background-image:url('+img+')');
				uploadtext.text(translatedText);
				uploadtext.show();
			} else {
				uploadtext.text(translatedText);
			}
		}

	});
	/*
	 * when file upload done successfully add row to filelist
	 * update counter when uploading to sub folder
	 */
	file_upload_start.on('fileuploaddone', function(e, data) {
		OC.Upload.log('filelist handle fileuploaddone', e, data);
		
		var response;
		if (typeof data.result === 'string') {
			response = data.result;
		} else {
			// fetch response from iframe
			response = data.result[0].body.innerText;
		}
		var result=$.parseJSON(response);

		if (typeof result[0] !== 'undefined' && result[0].status === 'success') {
			var file = result[0];

			if (data.context && data.context.data('type') === 'dir') {

				// update upload counter ui
				var uploadtext = data.context.find('.uploadtext');
				var currentUploads = parseInt(uploadtext.attr('currentUploads'));
				currentUploads -= 1;
				uploadtext.attr('currentUploads', currentUploads);
				var translatedText = n('files', 'Uploading %n file', 'Uploading %n files', currentUploads);
				if (currentUploads === 0) {
					var img = OC.imagePath('core', 'filetypes/folder.png');
					data.context.find('td.filename').attr('style','background-image:url('+img+')');
					uploadtext.text(translatedText);
					uploadtext.hide();
				} else {
					uploadtext.text(translatedText);
				}

				// update folder size
				var size = parseInt(data.context.data('size'));
				size += parseInt(file.size);
				data.context.attr('data-size', size);
				data.context.find('td.filesize').text(humanFileSize(size));

			} else {
				// only append new file if dragged onto current dir's crumb (last)
				if (data.context && data.context.hasClass('crumb') && !data.context.hasClass('last')) {
					return;
				}

				// add as stand-alone row to filelist
				var size=t('files', 'Pending');
				if (data.files[0].size>=0) {
					size=data.files[0].size;
				}
				var date=new Date();
				var param = {};
				if ($('#publicUploadRequestToken').exists()) {
					param.download_url = document.location.href + '&download&path=/' + $('#dir').val() + '/' + file.name;
				}
				//should the file exist in the list remove it
				FileList.remove(file.name);

				// create new file context
				data.context = FileList.addFile(file.name, file.size, date, false, false, param);

				// update file data
				data.context.attr('data-mime',file.mime).attr('data-id',file.id).attr('data-etag', file.etag);

				var permissions = data.context.data('permissions');
				if (permissions !== file.permissions) {
					data.context.attr('data-permissions', file.permissions);
					data.context.data('permissions', file.permissions);
				}
				FileActions.display(data.context.find('td.filename'), true);

				var path = getPathForPreview(file.name);
				Files.lazyLoadPreview(path, file.mime, function(previewpath) {
					data.context.find('td.filename').attr('style','background-image:url('+previewpath+')');
				}, null, null, file.etag);
			}
		}
	});
	file_upload_start.on('fileuploadstop', function(e, data) {
		OC.Upload.log('filelist handle fileuploadstop', e, data);

		//if user pressed cancel hide upload chrome
		if (data.errorThrown === 'abort') {
			//cleanup uploading to a dir
			var uploadtext = $('tr .uploadtext');
			var img = OC.imagePath('core', 'filetypes/folder.png');
			uploadtext.parents('td.filename').attr('style','background-image:url('+img+')');
			uploadtext.fadeOut();
			uploadtext.attr('currentUploads', 0);
		}
	});
	file_upload_start.on('fileuploadfail', function(e, data) {
		OC.Upload.log('filelist handle fileuploadfail', e, data);

		//if user pressed cancel hide upload chrome
		if (data.errorThrown === 'abort') {
			//cleanup uploading to a dir
			var uploadtext = $('tr .uploadtext');
			var img = OC.imagePath('core', 'filetypes/folder.png');
			uploadtext.parents('td.filename').attr('style','background-image:url('+img+')');
			uploadtext.fadeOut();
			uploadtext.attr('currentUploads', 0);
		}
	});

	$('#notification').hide();
	$('#notification').on('click', '.undo', function() {
		if (FileList.deleteFiles) {
			$.each(FileList.deleteFiles,function(index,file) {
				$('tr[data-file="'+file+'"]').show();
			});
			FileList.deleteCanceled=true;
			FileList.deleteFiles=null;
		} else if (FileList.replaceOldName && FileList.replaceNewName) {
			if (FileList.replaceIsNewFile) {
				// Delete the new uploaded file
				FileList.deleteCanceled = false;
				FileList.deleteFiles = [FileList.replaceOldName];
			} else {
				$('tr[data-file="'+FileList.replaceOldName+'"]').show();
			}
			$('tr[data-replace="true"').remove();
			$('tr[data-file="'+FileList.replaceNewName+'"]').show();
			FileList.replaceCanceled = true;
			FileList.replaceOldName = null;
			FileList.replaceNewName = null;
			FileList.replaceIsNewFile = null;
		}
		FileList.lastAction = null;
		OC.Notification.hide();
	});
	$('#notification:first-child').on('click', '.replace', function() {
		OC.Notification.hide(function() {
			FileList.replace($('#notification > span').attr('data-oldName'), $('#notification > span').attr('data-newName'), $('#notification > span').attr('data-isNewFile'));
		});
	});
	$('#notification:first-child').on('click', '.suggest', function() {
		$('tr[data-file="'+$('#notification > span').attr('data-oldName')+'"]').show();
		OC.Notification.hide();
	});
	$('#notification:first-child').on('click', '.cancel', function() {
		if ($('#notification > span').attr('data-isNewFile')) {
			FileList.deleteCanceled = false;
			FileList.deleteFiles = [$('#notification > span').attr('data-oldName')];
		}
	});
	FileList.useUndo=(window.onbeforeunload)?true:false;
	$(window).bind('beforeunload', function () {
		if (FileList.lastAction) {
			FileList.lastAction();
		}
	});
	$(window).unload(function () {
		$(window).trigger('beforeunload');
	});

	function decodeQuery(query) {
		return query.replace(/\+/g, ' ');
	}

	function parseHashQuery() {
		var hash = window.location.hash,
			pos = hash.indexOf('?'),
			query;
		if (pos >= 0) {
			return hash.substr(pos + 1);
		}
		return '';
	}

	function parseCurrentDirFromUrl() {
		var query = parseHashQuery(),
			params,
			dir = '/';
		// try and parse from URL hash first
		if (query) {
			params = OC.parseQueryString(decodeQuery(query));
		}
		// else read from query attributes
		if (!params) {
			params = OC.parseQueryString(decodeQuery(location.search));
		}
		return (params && params.dir) || '/';
	}

	// disable ajax/history API for public app (TODO: until it gets ported)
	if (!isPublic) {
		// fallback to hashchange when no history support
		if (!window.history.pushState) {
			$(window).on('hashchange', function() {
				FileList.changeDirectory(parseCurrentDirFromUrl(), false);
			});
		}
		window.onpopstate = function(e) {
			var targetDir;
			if (e.state && e.state.dir) {
				targetDir = e.state.dir;
			}
			else{
				// read from URL
				targetDir = parseCurrentDirFromUrl();
			}
			if (targetDir) {
				FileList.changeDirectory(targetDir, false);
			}
		};

		if (parseInt($('#ajaxLoad').val(), 10) === 1) {
			// need to initially switch the dir to the one from the hash (IE8)
			FileList.changeDirectory(parseCurrentDirFromUrl(), false, true);
		}
	}

	FileList.createFileSummary();
});
