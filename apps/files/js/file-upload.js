/**
 * 
 * when versioning app is active -> always overwrite
 * 
 * fileupload scenario: empty folder & d&d 20 files
 *		queue the 20 files
 *		check list of files for duplicates -> empty
 *		start uploading the queue (show progress dialog?)
 *		- no duplicates -> all good, add files to list
 *		- server reports duplicate -> show skip, replace or rename dialog (for individual files)
 *
 * fileupload scenario: files uploaded & d&d 20 files again
 *		queue the 20 files
 *		check list of files for duplicates -> find n duplicates ->
 *			show skip, replace or rename dialog as general option
 *				- show list of differences with preview (win 8)
 *			remember action for each file
 *		start uploading the queue (show progress dialog?)
 *		- no duplicates -> all good, add files to list
 *		- server reports duplicate -> use remembered action
 *		
 * dialoge:
 *	-> skip, replace, choose (or abort) ()
 *	-> choose left or right (with skip) (when only one file in list also show rename option and remember for all option)
 */


OC.upload = {
	_isProcessing:false,
	isProcessing:function(){
		return this._isProcessing;
	},
	_uploadQueue:[],
	addUpload:function(data){
		this._uploadQueue.push(data);

		if ( ! OC.upload.isProcessing() ) {
			OC.upload.startUpload();
		}
	},
	startUpload:function(){
		if (this._uploadQueue.length > 0) {
			this._isProcessing = true;
			this.nextUpload();
			return true;
		} else {
			return false;
		}
	},
	nextUpload:function(){
		if (this._uploadQueue.length > 0) {
			var data = this._uploadQueue.pop();
			var jqXHR = data.submit();

			// remember jqXHR to show warning to user when he navigates away but an upload is still in progress
			if (typeof data.context !== 'undefined' && data.context.data('type') === 'dir') {
				var dirName = data.context.data('file');
				if(typeof uploadingFiles[dirName] === 'undefined') {
					uploadingFiles[dirName] = {};
				}
				uploadingFiles[dirName][data.files[0].name] = jqXHR;
			} else {
				uploadingFiles[data.files[0].name] = jqXHR;
			}
		} else {
			//queue is empty, we are done
			this._isProcessing = false;
		}
	},
	onCancel:function(data){
		//TODO cancel all uploads
		Files.cancelUploads();
		this._uploadQueue = [];
		this._isProcessing = false;
	},
	onSkip:function(data){
		this.nextUpload();
	},
	onReplace:function(data){
		//TODO overwrite file
		data.data.append('replace', true);
		data.submit();
	},
	onRename:function(data, newName){
		//TODO rename file in filelist, stop spinner
		data.data.append('new_name', newName);
		data.submit();
	}
};

$(document).ready(function() {

	var file_upload_param = {
		dropZone: $('#content'), // restrict dropZone to content div
		//singleFileUploads is on by default, so the data.files array will always have length 1
		add: function(e, data) {
			var that = $(this);

			if (typeof data.originalFiles.checked === 'undefined') {
				
				var totalSize = 0;
				$.each(data.originalFiles, function(i, file) {
					totalSize += file.size;

					if (file.type === '' && file.size === 4096) {
						data.textStatus = 'dirorzero';
						data.errorThrown = t('files', 'Unable to upload {filename} as it is a directory or has 0 bytes',
							{filename: file.name}
						);
						return false;
					}
				});

				if (totalSize > $('#max_upload').val()) {
					data.textStatus = 'notenoughspace';
					data.errorThrown = t('files', 'Not enough space available');
				}

				if (data.errorThrown) {
					//don't upload anything
					var fu = that.data('blueimp-fileupload') || that.data('fileupload');
					fu._trigger('fail', e, data);
					return false;
				}
				
				data.originalFiles.checked = true; // this will skip the checks on subsequent adds
			}
			
			//TODO check filename already exists
			/*
			if ($('tr[data-file="'+data.files[0].name+'"][data-id]').length > 0) {
				data.textStatus = 'alreadyexists';
				data.errorThrown = t('files', '{filename} already exists',
					{filename: data.files[0].name}
				);
				//TODO show "file already exists" dialog
				var fu = that.data('blueimp-fileupload') || that.data('fileupload');
				fu._trigger('fail', e, data);
				return false;
			}
			*/

			//add files to queue
			OC.upload.addUpload(data);
			
			//TODO refactor away:
			//show cancel button
			if($('html.lte9').length === 0 && data.dataType !== 'iframe') {
				$('#uploadprogresswrapper input.stop').show();
			}
			return true;
		},
		/**
		 * called after the first add, does NOT have the data param
		 * @param e
		 */
		start: function(e) {
			//IE < 10 does not fire the necessary events for the progress bar.
			if($('html.lte9').length > 0) {
				return true;
			}
			$('#uploadprogressbar').progressbar({value:0});
			$('#uploadprogressbar').fadeIn();
		},
		fail: function(e, data) {
			if (typeof data.textStatus !== 'undefined' && data.textStatus !== 'success' ) {
				if (data.textStatus === 'abort') {
					$('#notification').text(t('files', 'Upload cancelled.'));
				} else {
					// HTTP connection problem
					$('#notification').text(data.errorThrown);
				}
				$('#notification').fadeIn();
				//hide notification after 5 sec
				setTimeout(function() {
					$('#notification').fadeOut();
				}, 5000);
			}
			delete uploadingFiles[data.files[0].name];
		},
		progress: function(e, data) {
			// TODO: show nice progress bar in file row
		},
		progressall: function(e, data) {
			//IE < 10 does not fire the necessary events for the progress bar.
			if($('html.lte9').length > 0) {
				return;
			}
			var progress = (data.loaded/data.total)*100;
			$('#uploadprogressbar').progressbar('value', progress);
		},
		/**
		 * called for every successful upload
		 * @param e
		 * @param data
		 */
		done:function(e, data) {
			// handle different responses (json or body from iframe for ie)
			var response;
			if (typeof data.result === 'string') {
				response = data.result;
			} else {
				//fetch response from iframe
				response = data.result[0].body.innerText;
			}
			var result=$.parseJSON(response);

			if(typeof result[0] !== 'undefined' && result[0].status === 'success') {
				OC.upload.nextUpload();
			} else {
				if (result[0].status === 'existserror') {
					//TODO open dialog and retry with other name?
					// get jqXHR reference
					if (typeof data.context !== 'undefined' && data.context.data('type') === 'dir') {
						var dirName = data.context.data('file');
						var jqXHR = uploadingFiles[dirName][filename];
					} else {
						var jqXHR = uploadingFiles[filename];
					}
					//filenames can only be changed on the server side
					//TODO show "file already exists" dialog
					//options: abort | skip | replace / rename
							//TODO reset all-files flag? when done with selection?
					var original = result[0];
					var replacement = data.files[0];
					OC.dialogs.fileexists(data, original, replacement, OC.upload);
				} else {
					data.textStatus = 'servererror';
					data.errorThrown = t('files', result.data.message);
					var fu = $(this).data('blueimp-fileupload') || $(this).data('fileupload');
					fu._trigger('fail', e, data);
				}
			}

			var filename = result[0].originalname;

			// delete jqXHR reference
			if (typeof data.context !== 'undefined' && data.context.data('type') === 'dir') {
				var dirName = data.context.data('file');
				delete uploadingFiles[dirName][filename];
				if ($.assocArraySize(uploadingFiles[dirName]) === 0) {
					delete uploadingFiles[dirName];
				}
			} else {
				delete uploadingFiles[filename];
			}

		},
		/**
		 * called after last upload
		 * @param e
		 * @param data
		 */
		stop: function(e, data) {
			if(data.dataType !== 'iframe') {
				$('#uploadprogresswrapper input.stop').hide();
			}

			//IE < 10 does not fire the necessary events for the progress bar.
			if($('html.lte9').length > 0) {
				return;
			}

			$('#uploadprogressbar').progressbar('value', 100);
			$('#uploadprogressbar').fadeOut();
		}
	};
	
	var file_upload_handler = function() {
		$('#file_upload_start').fileupload(file_upload_param);
	};

	if ( document.getElementById('data-upload-form') ) {
		$(file_upload_handler);
	}
	$.assocArraySize = function(obj) {
		// http://stackoverflow.com/a/6700/11236
		var size = 0, key;
		for (key in obj) {
			if (obj.hasOwnProperty(key)) {
				size++;
			}
		}
		return size;
	};

	// warn user not to leave the page while upload is in progress
	$(window).bind('beforeunload', function(e) {
		if ($.assocArraySize(uploadingFiles) > 0) {
			return t('files', 'File upload is in progress. Leaving the page now will cancel the upload.');
		}
	});

	//add multiply file upload attribute to all browsers except konqueror (which crashes when it's used)
	if(navigator.userAgent.search(/konqueror/i) === -1) {
		$('#file_upload_start').attr('multiple', 'multiple');
	}

	//if the breadcrumb is to long, start by replacing foldernames with '...' except for the current folder
	var crumb = $('div.crumb').first();
	while($('div.controls').height() > 40 && crumb.next('div.crumb').length > 0) {
		crumb.children('a').text('...');
		crumb = crumb.next('div.crumb');
	}
	//if that isn't enough, start removing items from the breacrumb except for the current folder and it's parent
	var crumb = $('div.crumb').first();
	var next = crumb.next('div.crumb');
	while($('div.controls').height() > 40 && next.next('div.crumb').length > 0) {
		crumb.remove();
		crumb = next;
		next = crumb.next('div.crumb');
	}
	//still not enough, start shorting down the current folder name
	var crumb = $('div.crumb>a').last();
	while($('div.controls').height() > 40 && crumb.text().length > 6) {
		var text = crumb.text();
		text = text.substr(0, text.length-6)+'...';
		crumb.text(text);
	}

	$(document).click(function() {
		$('#new>ul').hide();
		$('#new').removeClass('active');
		$('#new li').each(function(i, element) {
			if($(element).children('p').length === 0) {
				$(element).children('form').remove();
				$(element).append('<p>' + $(element).data('text') + '</p>');
			}
		});
	});
	$('#new li').click(function() {
		if($(this).children('p').length === 0) {
			return;
		}

	$('#new li').each(function(i, element) {
		if($(element).children('p').length === 0) {
			$(element).children('form').remove();
			$(element).append('<p>' + $(element).data('text') + '</p>');
		}
	});

	var type = $(this).data('type');
	var text = $(this).children('p').text();
	$(this).data('text', text);
	$(this).children('p').remove();
	var form = $('<form></form>');
	var input = $('<input>');
	form.append(input);
	$(this).append(form);
	input.focus();
	form.submit(function(event) {
		event.stopPropagation();
		event.preventDefault();
		var newname=input.val();
		if(type === 'web' && newname.length === 0) {
			OC.Notification.show(t('files', 'URL cannot be empty.'));
			return false;
		} else if (type !== 'web' && !Files.isFileNameValid(newname)) {
			return false;
		} else if( type === 'folder' && $('#dir').val() === '/' && newname === 'Shared') {
			OC.Notification.show(t('files', 'Invalid folder name. Usage of \'Shared\' is reserved by ownCloud'));
			return false;
		}
		if (FileList.lastAction) {
			FileList.lastAction();
		}
		var name = getUniqueName(newname);
		if (newname !== name) {
			FileList.checkName(name, newname, true);
			var hidden = true;
		} else {
			var hidden = false;
		}
		switch(type) {
			case 'file':
				$.post(
					OC.filePath('files', 'ajax', 'newfile.php'),
					{dir:$('#dir').val(), filename:name},
					function(result) {
						if (result.status === 'success') {
							var date = new Date();
							FileList.addFile(name, 0, date, false, hidden);
							var tr = $('tr').filterAttr('data-file', name);
							tr.attr('data-mime', result.data.mime);
							tr.attr('data-id', result.data.id);
							getMimeIcon(result.data.mime, function(path) {
						tr.find('td.filename').attr('style', 'background-image:url('+path+')');
							});
						} else {
							OC.dialogs.alert(result.data.message, t('core', 'Error'));
						}
					}
				);
				break;
			case 'folder':
				$.post(
					OC.filePath('files', 'ajax', 'newfolder.php'),
					{dir:$('#dir').val(), foldername:name},
					function(result) {
						if (result.status === 'success') {
							var date = new Date();
							FileList.addDir(name, 0, date, hidden);
							var tr = $('tr').filterAttr('data-file', name);
							tr.attr('data-id', result.data.id);
						} else {
							OC.dialogs.alert(result.data.message, t('core', 'Error'));
						}
					}
				);
				break;
			case 'web':
				if (name.substr(0, 8) !== 'https://' && name.substr(0, 7) !== 'http://') {
					name = 'http://' + name;
				}
				var localName = name;
				if(localName.substr(localName.length-1, 1) === '/') { //strip /
					localName = localName.substr(0, localName.length-1);
				}
				if (localName.indexOf('/')) { //use last part of url
					localName = localName.split('/').pop();
				} else { //or the domain
					localName = (localName.match(/:\/\/(.[^\/]+)/)[1]).replace('www.', '');
				}
				localName = getUniqueName(localName);
				//IE < 10 does not fire the necessary events for the progress bar.
				if ($('html.lte9').length > 0) {
				} else {
					$('#uploadprogressbar').progressbar({value:0});
					$('#uploadprogressbar').fadeIn();
				}

				var eventSource = new OC.EventSource(
					OC.filePath('files', 'ajax', 'newfile.php'),
					{dir:$('#dir').val(), source:name, filename:localName}
				);
				eventSource.listen('progress', function(progress) {
					//IE < 10 does not fire the necessary events for the progress bar.
					if($('html.lte9').length > 0) {
					} else {
						$('#uploadprogressbar').progressbar('value', progress);
					}
				});
				eventSource.listen('success', function(data) {
					var mime = data.mime;
					var size = data.size;
					var id = data.id;
					$('#uploadprogressbar').fadeOut();
					var date = new Date();
					FileList.addFile(localName, size, date, false, hidden);
					var tr = $('tr').filterAttr('data-file', localName);
					tr.data('mime', mime).data('id', id);
					tr.attr('data-id', id);
					getMimeIcon(mime, function(path) {
						tr.find('td.filename').attr('style', 'background-image:url(' + path + ')');
					});
				});
				eventSource.listen('error', function(error) {
					$('#uploadprogressbar').fadeOut();
					alert(error);
				});
				break;
			}
			var li = form.parent();
			form.remove();
			li.append('<p>' + li.data('text') + '</p>');
			$('#new>a').click();
		});
		
	});
	
});
