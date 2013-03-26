var uploadingFiles = {};
Files={
	cancelUpload:function(filename) {
		if(uploadingFiles[filename]) {
			uploadingFiles[filename].abort();
			delete uploadingFiles[filename];
			return true;
		}
		return false;
	},
	cancelUploads:function() {
		$.each(uploadingFiles,function(index,file) {
			if(typeof file['abort'] === 'function') {
				file.abort();
				var filename = $('tr').filterAttr('data-file',index);
				filename.hide();
				filename.find('input[type="checkbox"]').removeAttr('checked');
				filename.removeClass('selected');
			} else {
				$.each(file,function(i,f) {
					f.abort();
					delete file[i];
				});
			}
			delete uploadingFiles[index];
		});
		procesSelection();
	},
	updateMaxUploadFilesize:function(response) {
		if(response == undefined) {
			return;
		}
		if(response.data !== undefined && response.data.uploadMaxFilesize !== undefined) {
			$('#max_upload').val(response.data.uploadMaxFilesize);
			$('#upload.button').attr('original-title', response.data.maxHumanFilesize);
			$('#usedSpacePercent').val(response.data.usedSpacePercent);
			Files.displayStorageWarnings();
		}
		if(response[0] == undefined) {
			return;
		}
		if(response[0].uploadMaxFilesize !== undefined) {
			$('#max_upload').val(response[0].uploadMaxFilesize);
			$('#upload.button').attr('original-title', response[0].maxHumanFilesize);
			$('#usedSpacePercent').val(response[0].usedSpacePercent);
			Files.displayStorageWarnings();
		}

	},
	isFileNameValid:function (name) {
		if (name === '.') {
			OC.Notification.show(t('files', '\'.\' is an invalid file name.'));
			return false;
		}
		if (name.length == 0) {
			OC.Notification.show(t('files', 'File name cannot be empty.'));
			return false;
		}

		// check for invalid characters
		var invalid_characters = ['\\', '/', '<', '>', ':', '"', '|', '?', '*'];
		for (var i = 0; i < invalid_characters.length; i++) {
			if (name.indexOf(invalid_characters[i]) != -1) {
				OC.Notification.show(t('files', "Invalid name, '\\', '/', '<', '>', ':', '\"', '|', '?' and '*' are not allowed."));
				return false;
			}
		}
		OC.Notification.hide();
		return true;
	},
	displayStorageWarnings: function() {
		if (!OC.Notification.isHidden()) {
			return;
		}

		var usedSpacePercent = $('#usedSpacePercent').val();
		if (usedSpacePercent > 98) {
			OC.Notification.show(t('files', 'Your storage is full, files can not be updated or synced anymore!'));
			return;
		}
		if (usedSpacePercent > 90) {
			OC.Notification.show(t('files', 'Your storage is almost full ({usedSpacePercent}%)', {usedSpacePercent: usedSpacePercent}));
		}
	}
};
$(document).ready(function() {
	Files.bindKeyboardShortcuts(document, jQuery);
	$('#fileList tr').each(function(){
		//little hack to set unescape filenames in attribute
		$(this).attr('data-file',decodeURIComponent($(this).attr('data-file')));
	});

	$('#file_action_panel').attr('activeAction', false);

	//drag/drop of files
	$('#fileList tr td.filename').each(function(i,e){
		if ($(e).parent().data('permissions') & OC.PERMISSION_DELETE) {
			$(e).draggable(dragOptions);
		}
	});
	$('#fileList tr[data-type="dir"] td.filename').each(function(i,e){
		if ($(e).parent().data('permissions') & OC.PERMISSION_CREATE){
			$(e).droppable(folderDropOptions);
		}
	});
	$('div.crumb:not(.last)').droppable(crumbDropOptions);
	$('ul#apps>li:first-child').data('dir','');
	if($('div.crumb').length){
		$('ul#apps>li:first-child').droppable(crumbDropOptions);
	}

	// Triggers invisible file input
	$('#upload a').on('click', function() {
		$(this).parent().children('#file_upload_start').trigger('click');
		return false;
	});

	// Show trash bin
	$('#trash a').live('click', function() {
		window.location=OC.filePath('files_trashbin', '', 'index.php');
	});

	var lastChecked;

	// Sets the file link behaviour :
	$('#fileList').on('click','td.filename a',function(event) {
		if (event.ctrlKey || event.shiftKey) {
			event.preventDefault();
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
			var checkbox = $(this).parent().children('input:checkbox');
			lastChecked = checkbox;
			if ($(checkbox).attr('checked')) {
				$(checkbox).removeAttr('checked');
				$(checkbox).parent().parent().removeClass('selected');
				$('#select_all').removeAttr('checked');
			} else {
				$(checkbox).attr('checked', 'checked');
				$(checkbox).parent().parent().toggleClass('selected');
				var selectedCount=$('td.filename input:checkbox:checked').length;
				if (selectedCount == $('td.filename input:checkbox').length) {
					$('#select_all').attr('checked', 'checked');
				}
			}
			procesSelection();
		} else {
			var filename=$(this).parent().parent().attr('data-file');
			var tr=$('tr').filterAttr('data-file',filename);
			var renaming=tr.data('renaming');
			if(!renaming && !FileList.isLoading(filename)){
				FileActions.currentFile = $(this).parent();
				var mime=FileActions.getCurrentMimeType();
				var type=FileActions.getCurrentType();
				var permissions = FileActions.getCurrentPermissions();
				var action=FileActions.getDefault(mime,type, permissions);
				if(action){
					event.preventDefault();
					action(filename);
				}
			}
		}

	});

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
		procesSelection();
	});

	$('#fileList').on('change', 'td.filename input:checkbox',function(event) {
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
		procesSelection();
	});

	$('.download').click('click',function(event) {
		var files=getSelectedFiles('name');
		var fileslist = JSON.stringify(files);
		var dir=$('#dir').val()||'/';
		OC.Notification.show(t('files','Your download is being prepared. This might take some time if the files are big.'));
		// use special download URL if provided, e.g. for public shared files
		if ( (downloadURL = document.getElementById("downloadURL")) ) {
			window.location=downloadURL.value+"&download&files="+encodeURIComponent(fileslist);
		} else {
			window.location=OC.filePath('files', 'ajax', 'download.php') + '?'+ $.param({ dir: dir, files: fileslist });
		}
		return false;
	});

	$('.delete-selected').click(function(event) {
		var files=getSelectedFiles('name');
		event.preventDefault();
		FileList.do_delete(files);
		return false;
	});

	// drag&drop support using jquery.fileupload
	// TODO use OC.dialogs
	$(document).bind('drop dragover', function (e) {
			e.preventDefault(); // prevent browser from doing anything, if file isn't dropped in dropZone
	});

	if ( document.getElementById('data-upload-form') ) {
	$(function() {
		$('#file_upload_start').fileupload({
			dropZone: $('#content'), // restrict dropZone to content div
			add: function(e, data) {
				var files = data.files;
				var totalSize=0;
				if(files){
					if (FileList.lastAction) {
						FileList.lastAction();
					}
					for(var i=0;i<files.length;i++){
						if(files[i].size ==0 && files[i].type== '')
						{
							OC.dialogs.alert(t('files', 'Unable to upload your file as it is a directory or has 0 bytes'), t('files', 'Upload Error'));
							return;
						}
						totalSize+=files[i].size;
					}
				}
				if(totalSize>$('#max_upload').val()){
					$( '#uploadsize-message' ).dialog({
						modal: true,
						buttons: {
							Close: {
								text:t('files', 'Close'),
								click:function() {
									$( this ).dialog( 'close' );
								}
							}
						}
					});
				}else{
					var dropTarget = $(e.originalEvent.target).closest('tr');
					if(dropTarget && dropTarget.attr('data-type') === 'dir') { // drag&drop upload to folder
						var dirName = dropTarget.attr('data-file')
					}

					var date=new Date();
					if(files){
						for(var i=0;i<files.length;i++){
							if(files[i].size>0){
								var size=files[i].size;
							}else{
								var size=t('files','Pending');
							}
							if(files && !dirName){
								var uniqueName = getUniqueName(files[i].name);
								if (uniqueName != files[i].name) {
									FileList.checkName(uniqueName, files[i].name, true);
									var hidden = true;
								} else {
									var hidden = false;
								}
								FileList.addFile(uniqueName,size,date,true,hidden);
							} else if(dirName) {
								var uploadtext = $('tr').filterAttr('data-type', 'dir').filterAttr('data-file', dirName).find('.uploadtext')
								var currentUploads = parseInt(uploadtext.attr('currentUploads'));
								currentUploads += 1;
								uploadtext.attr('currentUploads', currentUploads);
								if(currentUploads === 1) {
									var img = OC.imagePath('core', 'loading.gif');
									var tr=$('tr').filterAttr('data-file',dirName);
									tr.find('td.filename').attr('style','background-image:url('+img+')');
									uploadtext.text(t('files', '1 file uploading'));
									uploadtext.show();
								} else {
									uploadtext.text(t('files', '{count} files uploading', {count: currentUploads}));
								}
							}
						}
					}else{
						var filename=this.value.split('\\').pop(); //ie prepends C:\fakepath\ in front of the filename
						var uniqueName = getUniqueName(filename);
						if (uniqueName != filename) {
							FileList.checkName(uniqueName, filename, true);
							var hidden = true;
						} else {
							var hidden = false;
						}
						FileList.addFile(uniqueName,'Pending',date,true,hidden);
					}
					if($.support.xhrFileUpload) {
						for(var i=0;i<files.length;i++){
							var fileName = files[i].name
							var dropTarget = $(e.originalEvent.target).closest('tr');
							if(dropTarget && dropTarget.attr('data-type') === 'dir') { // drag&drop upload to folder
								var dirName = dropTarget.attr('data-file')
								var jqXHR =  $('#file_upload_start').fileupload('send', {files: files[i],
										formData: function(form) {
											var formArray = form.serializeArray();
											// array index 0 contains the max files size
											// array index 1 contains the request token
											// array index 2 contains the directory
											formArray[2]['value'] = dirName;
											return formArray;
										}}).success(function(result, textStatus, jqXHR) {
											var response;
											response=jQuery.parseJSON(result);
											if(response[0] == undefined || response[0].status != 'success') {
												OC.Notification.show(t('files', response.data.message));
											}
											Files.updateMaxUploadFilesize(response);
											var file=response[0];
											// TODO: this doesn't work if the file name has been changed server side
											delete uploadingFiles[dirName][file.name];
											if ($.assocArraySize(uploadingFiles[dirName]) == 0) {
												delete uploadingFiles[dirName];
											}
											//TODO update file upload size limit

											var uploadtext = $('tr').filterAttr('data-type', 'dir').filterAttr('data-file', dirName).find('.uploadtext')
											var currentUploads = parseInt(uploadtext.attr('currentUploads'));
											currentUploads -= 1;
											uploadtext.attr('currentUploads', currentUploads);
											if(currentUploads === 0) {
												var img = OC.imagePath('core', 'filetypes/folder.png');
												var tr=$('tr').filterAttr('data-file',dirName);
												tr.find('td.filename').attr('style','background-image:url('+img+')');
												uploadtext.text('');
												uploadtext.hide();
											} else {
												uploadtext.text(t('files', '{count} files uploading', {count: currentUploads}));
											}
										})
								.error(function(jqXHR, textStatus, errorThrown) {
									if(errorThrown === 'abort') {
										var currentUploads = parseInt(uploadtext.attr('currentUploads'));
										currentUploads -= 1;
										uploadtext.attr('currentUploads', currentUploads);
										if(currentUploads === 0) {
											var img = OC.imagePath('core', 'filetypes/folder.png');
											var tr=$('tr').filterAttr('data-file',dirName);
											tr.find('td.filename').attr('style','background-image:url('+img+')');
											uploadtext.text('');
											uploadtext.hide();
										} else {
											uploadtext.text(t('files', '{count} files uploading', {count: currentUploads}));
										}
										delete uploadingFiles[dirName][fileName];
										OC.Notification.show(t('files', 'Upload cancelled.'));
									}
								});
								//TODO test with filenames containing slashes
								if(uploadingFiles[dirName] === undefined) {
									uploadingFiles[dirName] = {};
								}
								uploadingFiles[dirName][fileName] = jqXHR;
							} else {
								var jqXHR =  $('#file_upload_start').fileupload('send', {files: files[i]})
										.success(function(result, textStatus, jqXHR) {
											var response;
											response=jQuery.parseJSON(result);
											Files.updateMaxUploadFilesize(response);

											if(response[0] != undefined && response[0].status == 'success') {
												var file=response[0];
												delete uploadingFiles[file.name];
												$('tr').filterAttr('data-file',file.name).data('mime',file.mime).data('id',file.id);
												var size = $('tr').filterAttr('data-file',file.name).find('td.filesize').text();
												if(size==t('files','Pending')){
													var sizeElement = $('tr').filterAttr('data-file',file.name).find('td.filesize');
													sizeElement.text(simpleFileSize(file.size));
													sizeElement.attr('title',humanFileSize(file.size));
												}
												//TODO update file upload size limit
												FileList.loadingDone(file.name, file.id);
											} else {
												Files.cancelUpload(this.files[0].name);
												OC.Notification.show(t('files', response.data.message));
												$('#fileList > tr').not('[data-mime]').fadeOut();
												$('#fileList > tr').not('[data-mime]').remove();
											}
									})
									.error(function(jqXHR, textStatus, errorThrown) {
										if(errorThrown === 'abort') {
											Files.cancelUpload(this.files[0].name);
											OC.Notification.show(t('files', 'Upload cancelled.'));
										}
									});
								uploadingFiles[uniqueName] = jqXHR;
							}
						}
					}else{
						data.submit().success(function(data, status) {
							// in safari data is a string
							response = jQuery.parseJSON(typeof data === 'string' ? data : data[0].body.innerText);
							Files.updateMaxUploadFilesize(response);
							if(response[0] != undefined && response[0].status == 'success') {
								var file=response[0];
								delete uploadingFiles[file.name];
								$('tr').filterAttr('data-file',file.name).data('mime',file.mime).data('id',file.id);
								var size = $('tr').filterAttr('data-file',file.name).find('td.filesize').text();
								if(size==t('files','Pending')){
									var sizeElement = $('tr').filterAttr('data-file',file.name).find('td.filesize');
									sizeElement.text(simpleFileSize(file.size));
									sizeElement.attr('title',humanFileSize(file.size));
								}
								//TODO update file upload size limit
								FileList.loadingDone(file.name, file.id);
							} else {
								//TODO Files.cancelUpload(/*where do we get the filename*/);
								OC.Notification.show(t('files', response.data.message));
								$('#fileList > tr').not('[data-mime]').fadeOut();
								$('#fileList > tr').not('[data-mime]').remove();
							}
						});
					}
				}
			},
			fail: function(e, data) {
				// TODO: cancel upload & display error notification
			},
			progress: function(e, data) {
				// TODO: show nice progress bar in file row
			},
			progressall: function(e, data) {
				//IE < 10 does not fire the necessary events for the progress bar.
				if($.browser.msie && parseInt($.browser.version) < 10) {
					return;
				}
				var progress = (data.loaded/data.total)*100;
				$('#uploadprogressbar').progressbar('value',progress);
			},
			start: function(e, data) {
				//IE < 10 does not fire the necessary events for the progress bar.
				if($.browser.msie && parseInt($.browser.version) < 10) {
					return;
				}
				$('#uploadprogressbar').progressbar({value:0});
				$('#uploadprogressbar').fadeIn();
				if(data.dataType != 'iframe ') {
					$('#upload input.stop').show();
				}
			},
			stop: function(e, data) {
				if(data.dataType != 'iframe ') {
					$('#upload input.stop').hide();
				}
				//IE < 10 does not fire the necessary events for the progress bar.
				if($.browser.msie && parseInt($.browser.version) < 10) {
					return;
				}

				$('#uploadprogressbar').progressbar('value',100);
				$('#uploadprogressbar').fadeOut();
			}
		})
	});
	}
	$.assocArraySize = function(obj) {
		// http://stackoverflow.com/a/6700/11236
		var size = 0, key;
		for (key in obj) {
			if (obj.hasOwnProperty(key)) size++;
		}
		return size;
	};

	// warn user not to leave the page while upload is in progress
	$(window).bind('beforeunload', function(e) {
		if ($.assocArraySize(uploadingFiles) > 0)
			return t('files','File upload is in progress. Leaving the page now will cancel the upload.');
	});

	//add multiply file upload attribute to all browsers except konqueror (which crashes when it's used)
	if(navigator.userAgent.search(/konqueror/i)==-1){
		$('#file_upload_start').attr('multiple','multiple')
	}

	//if the breadcrumb is to long, start by replacing foldernames with '...' except for the current folder
	var crumb=$('div.crumb').first();
	while($('div.controls').height()>40 && crumb.next('div.crumb').length>0){
		crumb.children('a').text('...');
		crumb=crumb.next('div.crumb');
	}
	//if that isn't enough, start removing items from the breacrumb except for the current folder and it's parent
	var crumb=$('div.crumb').first();
	var next=crumb.next('div.crumb');
	while($('div.controls').height()>40 && next.next('div.crumb').length>0){
		crumb.remove();
		crumb=next;
		next=crumb.next('div.crumb');
	}
	//still not enough, start shorting down the current folder name
	var crumb=$('div.crumb>a').last();
	while($('div.controls').height()>40 && crumb.text().length>6){
		var text=crumb.text()
		text=text.substr(0,text.length-6)+'...';
		crumb.text(text);
	}

	$(document).click(function(){
		$('#new>ul').hide();
		$('#new').removeClass('active');
		$('#new li').each(function(i,element){
			if($(element).children('p').length==0){
				$(element).children('form').remove();
				$(element).append('<p>'+$(element).data('text')+'</p>');
			}
		});
	});
	$('#new').click(function(event){
		event.stopPropagation();
	});
	$('#new>a').click(function(){
		$('#new>ul').toggle();
		$('#new').toggleClass('active');
	});
	$('#new li').click(function(){
		if($(this).children('p').length==0){
			return;
		}

		$('#new li').each(function(i,element){
			if($(element).children('p').length==0){
				$(element).children('form').remove();
				$(element).append('<p>'+$(element).data('text')+'</p>');
			}
		});

		var type=$(this).data('type');
		var text=$(this).children('p').text();
		$(this).data('text',text);
		$(this).children('p').remove();
		var form=$('<form></form>');
		var input=$('<input>');
		form.append(input);
		$(this).append(form);
		input.focus();
		form.submit(function(event){
			event.stopPropagation();
			event.preventDefault();
			var newname=input.val();
			if(type == 'web' && newname.length == 0) {
				OC.Notification.show(t('files', 'URL cannot be empty.'));
				return false;
			} else if (type != 'web' && !Files.isFileNameValid(newname)) {
				return false;
			} else if( type == 'folder' && $('#dir').val() == '/' && newname == 'Shared') {
				OC.Notification.show(t('files','Invalid folder name. Usage of \'Shared\' is reserved by Owncloud'));
				return false;
			}
			if (FileList.lastAction) {
				FileList.lastAction();
			}
			var name = getUniqueName(newname);
			if (newname != name) {
				FileList.checkName(name, newname, true);
				var hidden = true;
			} else {
				var hidden = false;
			}
			switch(type){
				case 'file':
					$.post(
						OC.filePath('files','ajax','newfile.php'),
						{dir:$('#dir').val(),filename:name},
						function(result){
							if (result.status == 'success') {
								var date=new Date();
								FileList.addFile(name,0,date,false,hidden);
								var tr=$('tr').filterAttr('data-file',name);
								tr.attr('data-mime','text/plain');
								tr.attr('data-id', result.data.id);
								getMimeIcon('text/plain',function(path){
									tr.find('td.filename').attr('style','background-image:url('+path+')');
								});
							} else {
								OC.dialogs.alert(result.data.message, 'Error');
							}
						}
					);
					break;
				case 'folder':
					$.post(
						OC.filePath('files','ajax','newfolder.php'),
						{dir:$('#dir').val(),foldername:name},
						function(result){
							if (result.status == 'success') {
								var date=new Date();
								FileList.addDir(name,0,date,hidden);
								var tr=$('tr').filterAttr('data-file',name);
								tr.attr('data-id', result.data.id);
							} else {
								OC.dialogs.alert(result.data.message, 'Error');
							}
						}
					);
					break;
				case 'web':
					if(name.substr(0,8)!='https://' && name.substr(0,7)!='http://'){
						name='http://'+name;
					}
					var localName=name;
					if(localName.substr(localName.length-1,1)=='/'){//strip /
						localName=localName.substr(0,localName.length-1)
					}
					if(localName.indexOf('/')){//use last part of url
						localName=localName.split('/').pop();
					}else{//or the domain
						localName=(localName.match(/:\/\/(.[^/]+)/)[1]).replace('www.','');
					}
					localName = getUniqueName(localName);
					//IE < 10 does not fire the necessary events for the progress bar.
					if($.browser.msie && parseInt($.browser.version) < 10) {
					} else {
						$('#uploadprogressbar').progressbar({value:0});
						$('#uploadprogressbar').fadeIn();
					}

					var eventSource=new OC.EventSource(OC.filePath('files','ajax','newfile.php'),{dir:$('#dir').val(),source:name,filename:localName});
					eventSource.listen('progress',function(progress){
						if($.browser.msie && parseInt($.browser.version) < 10) {
						} else {
							$('#uploadprogressbar').progressbar('value',progress);
						}
					});
					eventSource.listen('success',function(data){
						var mime=data.mime;
						var size=data.size;
						var id=data.id;
						$('#uploadprogressbar').fadeOut();
						var date=new Date();
						FileList.addFile(localName,size,date,false,hidden);
						var tr=$('tr').filterAttr('data-file',localName);
						tr.data('mime',mime).data('id',id);
						tr.attr('data-id', id);
						getMimeIcon(mime,function(path){
							tr.find('td.filename').attr('style','background-image:url('+path+')');
						});
					});
					eventSource.listen('error',function(error){
						$('#uploadprogressbar').fadeOut();
						alert(error);
					});
					break;
			}
			var li=form.parent();
			form.remove();
			li.append('<p>'+li.data('text')+'</p>');
			$('#new>a').click();
		});
	});

	//do a background scan if needed
	scanFiles();

	var lastWidth = 0;
	var breadcrumbs = [];
	var breadcrumbsWidth = 0;
	if ( document.getElementById("navigation") ) {
		breadcrumbsWidth = $('#navigation').get(0).offsetWidth;
	}
	var hiddenBreadcrumbs = 0;

	$.each($('.crumb'), function(index, breadcrumb) {
		breadcrumbs[index] = breadcrumb;
		breadcrumbsWidth += $(breadcrumb).get(0).offsetWidth;
	});


	$.each($('#controls .actions>div'), function(index, action) {
		breadcrumbsWidth += $(action).get(0).offsetWidth;
	});

	function resizeBreadcrumbs(firstRun) {
		var width = $(this).width();
		if (width != lastWidth) {
			if ((width < lastWidth || firstRun) && width < breadcrumbsWidth) {
				if (hiddenBreadcrumbs == 0) {
					breadcrumbsWidth -= $(breadcrumbs[1]).get(0).offsetWidth;
					$(breadcrumbs[1]).find('a').hide();
					$(breadcrumbs[1]).append('<span>...</span>');
					breadcrumbsWidth += $(breadcrumbs[1]).get(0).offsetWidth;
					hiddenBreadcrumbs = 2;
				}
				var i = hiddenBreadcrumbs;
				while (width < breadcrumbsWidth && i > 1 && i < breadcrumbs.length - 1) {
					breadcrumbsWidth -= $(breadcrumbs[i]).get(0).offsetWidth;
					$(breadcrumbs[i]).hide();
					hiddenBreadcrumbs = i;
					i++
				}
			} else if (width > lastWidth && hiddenBreadcrumbs > 0) {
				var i = hiddenBreadcrumbs;
				while (width > breadcrumbsWidth && i > 0) {
					if (hiddenBreadcrumbs == 1) {
						breadcrumbsWidth -= $(breadcrumbs[1]).get(0).offsetWidth;
						$(breadcrumbs[1]).find('span').remove();
						$(breadcrumbs[1]).find('a').show();
						breadcrumbsWidth += $(breadcrumbs[1]).get(0).offsetWidth;
					} else {
						$(breadcrumbs[i]).show();
						breadcrumbsWidth += $(breadcrumbs[i]).get(0).offsetWidth;
						if (breadcrumbsWidth > width) {
							breadcrumbsWidth -= $(breadcrumbs[i]).get(0).offsetWidth;
							$(breadcrumbs[i]).hide();
							break;
						}
					}
					i--;
					hiddenBreadcrumbs = i;
				}
			}
			lastWidth = width;
		}
	}

	$(window).resize(function() {
		resizeBreadcrumbs(false);
	});

	resizeBreadcrumbs(true);

	// display storage warnings
	setTimeout ( "Files.displayStorageWarnings()", 100 );
	OC.Notification.setDefault(Files.displayStorageWarnings);

	// file space size sync
	function update_storage_statistics() {
		$.getJSON(OC.filePath('files','ajax','getstoragestats.php'),function(response) {
			Files.updateMaxUploadFilesize(response);
		});
	}

	// start on load - we ask the server every 5 minutes
	var update_storage_statistics_interval = 5*60*1000;
	var update_storage_statistics_interval_id = setInterval(update_storage_statistics, update_storage_statistics_interval);

	// Use jquery-visibility to de-/re-activate file stats sync
	if ($.support.pageVisibility) {
		$(document).on({
			'show.visibility': function() {
				if (!update_storage_statistics_interval_id) {
					update_storage_statistics_interval_id = setInterval(update_storage_statistics, update_storage_statistics_interval);
				}
			},
			'hide.visibility': function() {
				clearInterval(update_storage_statistics_interval_id);
				update_storage_statistics_interval_id = 0;
			}
		});
	}
});

function scanFiles(force, dir){
	if (!OC.currentUser) {
		return;
	}

	if(!dir){
		dir = '';
	}
	force = !!force; //cast to bool
	scanFiles.scanning = true;
	var scannerEventSource = new OC.EventSource(OC.filePath('files','ajax','scan.php'),{force:force,dir:dir});
	scanFiles.cancel = scannerEventSource.close.bind(scannerEventSource);
	scannerEventSource.listen('count',function(count){
		console.log(count + 'files scanned')
	});
	scannerEventSource.listen('folder',function(path){
		console.log('now scanning ' + path)
	});
	scannerEventSource.listen('done',function(count){
		scanFiles.scanning=false;
		console.log('done after ' + count + 'files');
	});
}
scanFiles.scanning=false;

function boolOperationFinished(data, callback) {
	result = jQuery.parseJSON(data.responseText);
	Files.updateMaxUploadFilesize(result);
	if(result.status == 'success'){
		callback.call();
	} else {
		alert(result.data.message);
	}
}

function updateBreadcrumb(breadcrumbHtml) {
	$('p.nav').empty().html(breadcrumbHtml);
}

var createDragShadow = function(event){
	//select dragged file
	var isDragSelected = $(event.target).parents('tr').find('td input:first').prop('checked');
	if (!isDragSelected) {
		//select dragged file
		$(event.target).parents('tr').find('td input:first').prop('checked',true);
	}

	var selectedFiles = getSelectedFiles();

	if (!isDragSelected && selectedFiles.length == 1) {
		//revert the selection
		$(event.target).parents('tr').find('td input:first').prop('checked',false);
	}

	//also update class when we dragged more than one file
	if (selectedFiles.length > 1) {
		$(event.target).parents('tr').addClass('selected');
	}

	// build dragshadow
	var dragshadow = $('<table class="dragshadow"></table>');
	var tbody = $('<tbody></tbody>');
	dragshadow.append(tbody);

	var dir=$('#dir').val();

	$(selectedFiles).each(function(i,elem){
		var newtr = $('<tr data-dir="'+dir+'" data-filename="'+elem.name+'">'
						+'<td class="filename">'+elem.name+'</td><td class="size">'+humanFileSize(elem.size)+'</td>'
					 +'</tr>');
		tbody.append(newtr);
		if (elem.type === 'dir') {
			newtr.find('td.filename').attr('style','background-image:url('+OC.imagePath('core', 'filetypes/folder.png')+')');
		} else {
			getMimeIcon(elem.mime,function(path){
				newtr.find('td.filename').attr('style','background-image:url('+path+')');
			});
		}
	});

	return dragshadow;
}

//options for file drag/drop
var dragOptions={
	revert: 'invalid', revertDuration: 300,
	opacity: 0.7, zIndex: 100, appendTo: 'body', cursorAt: { left: -5, top: -5 },
	helper: createDragShadow, cursor: 'move',
	stop: function(event, ui) {
		$('#fileList tr td.filename').addClass('ui-draggable');
	}
}
// sane browsers support using the distance option
if ( ! $.browser.msie) {
	dragOptions['distance'] = 20;
} 

var folderDropOptions={
	drop: function( event, ui ) {
		//don't allow moving a file into a selected folder
		if ($(event.target).parents('tr').find('td input:first').prop('checked') === true) {
			return false;
		}

		var target=$.trim($(this).find('.nametext').text());

		var files = ui.helper.find('tr');
		$(files).each(function(i,row){
			var dir = $(row).data('dir');
			var file = $(row).data('filename');
			$.post(OC.filePath('files', 'ajax', 'move.php'), { dir: dir, file: file, target: dir+'/'+target }, function(result) {
				if (result) {
					if (result.status === 'success') {
						//recalculate folder size
						var oldSize = $('#fileList tr').filterAttr('data-file',target).data('size');
						var newSize = oldSize + $('#fileList tr').filterAttr('data-file',file).data('size');
						$('#fileList tr').filterAttr('data-file',target).data('size', newSize);
						$('#fileList tr').filterAttr('data-file',target).find('td.filesize').text(humanFileSize(newSize));

						FileList.remove(file);
						procesSelection();
						$('#notification').hide();
					} else {
						$('#notification').hide();
						$('#notification').text(result.data.message);
						$('#notification').fadeIn();
					}
				} else {
					OC.dialogs.alert(t('Error moving file'));
				}
			});
		});
	},
	tolerance: 'pointer'
}

var crumbDropOptions={
	drop: function( event, ui ) {
		var target=$(this).data('dir');
		var dir=$('#dir').val();
		while(dir.substr(0,1)=='/'){//remove extra leading /'s
				dir=dir.substr(1);
		}
		dir='/'+dir;
		if(dir.substr(-1,1)!='/'){
			dir=dir+'/';
		}
		if(target==dir || target+'/'==dir){
			return;
		}
		var files = ui.helper.find('tr');
		$(files).each(function(i,row){
			var dir = $(row).data('dir');
			var file = $(row).data('filename');
			$.post(OC.filePath('files', 'ajax', 'move.php'), { dir: dir, file: file, target: target }, function(result) {
				if (result) {
					if (result.status === 'success') {
						FileList.remove(file);
						procesSelection();
						$('#notification').hide();
					} else {
						$('#notification').hide();
						$('#notification').text(result.data.message);
						$('#notification').fadeIn();
					}
				} else {
					OC.dialogs.alert(t('Error moving file'));
				}
			});
		});
	},
	tolerance: 'pointer'
}

function procesSelection(){
	var selected=getSelectedFiles();
	var selectedFiles=selected.filter(function(el){return el.type=='file'});
	var selectedFolders=selected.filter(function(el){return el.type=='dir'});
	if(selectedFiles.length==0 && selectedFolders.length==0) {
		$('#headerName>span.name').text(t('files','Name'));
		$('#headerSize').text(t('files','Size'));
		$('#modified').text(t('files','Modified'));
		$('table').removeClass('multiselect');
		$('.selectedActions').hide();
	}
	else {
		$('.selectedActions').show();
		var totalSize=0;
		for(var i=0;i<selectedFiles.length;i++){
			totalSize+=selectedFiles[i].size;
		};
		for(var i=0;i<selectedFolders.length;i++){
			totalSize+=selectedFolders[i].size;
		};
		simpleSize=simpleFileSize(totalSize);
		$('#headerSize').text(simpleSize);
		$('#headerSize').attr('title',humanFileSize(totalSize));
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
			name:$(element).attr('data-file'),
			mime:$(element).data('mime'),
			type:$(element).data('type'),
			size:$(element).data('size')
		};
		if(property){
			files.push(file[property]);
		}else{
			files.push(file);
		}
	});
	return files;
}

function getMimeIcon(mime, ready){
	if(getMimeIcon.cache[mime]){
		ready(getMimeIcon.cache[mime]);
	}else{
		$.get( OC.filePath('files','ajax','mimeicon.php'), {mime: mime}, function(path){
			getMimeIcon.cache[mime]=path;
			ready(getMimeIcon.cache[mime]);
		});
	}
}
getMimeIcon.cache={};

function getUniqueName(name){
	if($('tr').filterAttr('data-file',name).length>0){
		var parts=name.split('.');
		var extension = "";
		if (parts.length > 1) {
			extension=parts.pop();
		}
		var base=parts.join('.');
		numMatch=base.match(/\((\d+)\)/);
		var num=2;
		if(numMatch && numMatch.length>0){
			num=parseInt(numMatch[numMatch.length-1])+1;
			base=base.split('(')
			base.pop();
			base=$.trim(base.join('('));
		}
		name=base+' ('+num+')';
		if (extension) {
			name = name+'.'+extension;
		}
		return getUniqueName(name);
	}
	return name;
}
