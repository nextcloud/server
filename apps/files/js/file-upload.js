/**
 * 
 * and yet another idea how to handle file uploads:
 * let the jquery fileupload thing handle as much as possible
 * 
 * use singlefileupload
 * on first add of every selection
 * - check all files of originalFiles array with files in dir
 * - on conflict show dialog
 *   - skip all -> remember as default action
 *   - replace all -> remember as default action
 *   - choose -> show choose dialog
 *     - mark files to keep
 *       - when only existing -> remember as single skip action
 *       - when only new -> remember as single replace action
 *       - when both -> remember as single autorename action
 *     - continue -> apply marks, when nothing is marked continue == skip all
 * - start uploading selection
 * 
 * on send
 * - if single action or default action
 *   - when skip -> abort upload
 * ..- when replace -> add replace=true parameter
 * ..- when rename -> add newName=filename parameter
 * ..- when autorename -> add autorename=true parameter
 * 
 * on fail
 * - if server sent existserror
 *    - show dialog
 *      - on skip single -> abort single upload
 *      - on skip always -> remember as default action
 *      - on replace single -> replace single upload
 *      - on replace always -> remember as default action
 *      - on rename single -> rename single upload, propose autorename - when changed disable remember always checkbox
 *      - on rename always -> remember autorename as default action
 *    - resubmit data
 * 
 * on uplad done
 * - if last upload -> unset default action
 * 
 * -------------------------------------------------------------
 * 
 * use put t ocacnel upload before it starts? use chunked uploads?
 * 
 * 1. tracking which file to upload next -> upload queue with data elements added whenever add is called
 * 2. tracking progress for each folder individually -> track progress in a progress[dirname] object
 *   - every new selection increases the total size and number of files for a directory
 *   - add increases, successful done decreases, skip decreases, cancel decreases
 * 3. track selections -> the general skip / overwrite decision is selection based and can change
 *    - server might send already exists error -> show dialog & remember decision for selection again
 *    - server sends error, how do we find collection?
 * 4. track jqXHR object to prevent browser from navigationg away -> track in a uploads[dirname][filename] object [x]
 * 
 * selections can progress in parrallel but each selection progresses sequentially
 * 
 * -> store everything in context?
 * context.folder
 * context.element?
 * context.progressui?
 * context.jqXHR
 * context.selection
 * context.selection.onExistsAction?
 * 
 * context available in what events?
 * build in drop() add dir
 * latest in add() add file? add selection!
 * progress? -> update progress?
 * onsubmit -> context.jqXHR?
 * fail() -> 
 * done()
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
 *	
 *	progress always based on filesize
 *	number of files as text, bytes as bar
 *	
 */

// from https://github.com/New-Bamboo/example-ajax-upload/blob/master/public/index.html
// also see article at http://blog.new-bamboo.co.uk/2012/01/10/ridiculously-simple-ajax-uploads-with-formdata
// Function that will allow us to know if Ajax uploads are supported
function supportAjaxUploadWithProgress() {
	return supportFileAPI() && supportAjaxUploadProgressEvents() && supportFormData();

	// Is the File API supported?
	function supportFileAPI() {
		var fi = document.createElement('INPUT');
		fi.type = 'file';
		return 'files' in fi;
	};

	// Are progress events supported?
	function supportAjaxUploadProgressEvents() {
		var xhr = new XMLHttpRequest();
		return !! (xhr && ('upload' in xhr) && ('onprogress' in xhr.upload));
	};

	// Is FormData supported?
	function supportFormData() {
		return !! window.FormData;
	}
}

//TODO clean uploads when all progress has completed
OC.Upload = {
	_uploads: [],
	cancelUpload:function(dir, filename) {
		var self = this;
		var deleted = false;
		//FIXME _selections
		jQuery.each(this._uploads, function(i, jqXHR) {
			if (selection.dir === dir && selection.uploads[filename]) {
				deleted = self.deleteSelectionUpload(selection, filename);
				return false; // end searching through selections
			}
		});
		return deleted;
	},
	deleteUpload:function(data) {
		delete data.jqXHR;
	},
	cancelUploads:function() {
		console.log('canceling uploads');
		jQuery.each(this._uploads,function(i, jqXHR){
			jqXHR.abort();
		});
		this._uploads = [];
		
	},
	rememberUpload:function(jqXHR){
		if (jqXHR) {
			this._uploads.push(jqXHR);
		}
	},
	isProcessing:function(){
		var count = 0;
		
		jQuery.each(this._uploads,function(i, data){
			if (data.state() === 'pending') {
				count++;
			}
		});
		return count > 0;
	},
	onCancel:function(data) {
		this.cancelUploads();
	},
	onContinue:function(conflicts) {
		var self = this;
		//iterate over all conflicts
		jQuery.each(conflicts, function (i, conflict) {
			conflict = $(conflict);
			var keepOriginal = conflict.find('.original input[type="checkbox"]:checked').length === 1;
			var keepReplacement = conflict.find('.replacement input[type="checkbox"]:checked').length === 1;
			if (keepOriginal && keepReplacement) {
				// when both selected -> autorename
				self.onAutorename(conflict.data('data'));
			} else if (keepReplacement) {
				// when only replacement selected -> overwrite
				self.onReplace(conflict.data('data'));
			} else {
				// when only original seleted -> skip
				// when none selected -> skip
				self.onSkip(conflict.data('data'));
			}
		});
	},
	onSkip:function(data){
		this.logStatus('skip', null, data);
		this.deleteUpload(data);
	},
	onReplace:function(data){
		this.logStatus('replace', null, data);
		data.data.append('resolution', 'replace');
		data.submit();
	},
	onAutorename:function(data){
		this.logStatus('autorename', null, data);
		if (data.data) {
			data.data.append('resolution', 'autorename');
		} else {
			data.formData.push({name:'resolution',value:'autorename'}); //hack for ie8
		}
		data.submit();
	},
	logStatus:function(caption, e, data) {
		console.log(caption);
		console.log(data);
	},
	checkExistingFiles: function (selection, callbacks){
		// FIXME check filelist before uploading
		callbacks.onNoConflicts(selection);
	}
};

$(document).ready(function() {

	var file_upload_param = {
		dropZone: $('#content'), // restrict dropZone to content div
		autoUpload: false,
		sequentialUploads: true,
		
		//singleFileUploads is on by default, so the data.files array will always have length 1
		/**
		 * on first add of every selection
		 * - check all files of originalFiles array with files in dir
		 * - on conflict show dialog
		 *   - skip all -> remember as single skip action for all conflicting files
		 *   - replace all -> remember as single replace action for all conflicting files
		 *   - choose -> show choose dialog
		 *     - mark files to keep
		 *       - when only existing -> remember as single skip action
		 *       - when only new -> remember as single replace action
		 *       - when both -> remember as single autorename action
		 * - start uploading selection
		 * @param {type} e
		 * @param {type} data
		 * @returns {Boolean}
		 */
		add: function(e, data) {
			OC.Upload.logStatus('add', e, data);
			var that = $(this);
			
			// we need to collect all data upload objects before starting the upload so we can check their existence
			// and set individual conflict actions. unfortunately there is only one variable that we can use to identify
			// the selection a data upload is part of, so we have to collect them in data.originalFiles
			// turning singleFileUploads off is not an option because we want to gracefully handle server errors like
			// already exists
			
			// create a container where we can store the data objects
			if ( ! data.originalFiles.selection ) {
				// initialize selection and remember number of files to upload
				data.originalFiles.selection = {
					uploads: [],
					filesToUpload: data.originalFiles.length,
					totalBytes: 0
				};
			}
			var selection = data.originalFiles.selection;
			
			// add uploads
			if ( selection.uploads.length < selection.filesToUpload ){
				// remember upload
				selection.uploads.push(data);
			}
			
			//examine file
			var file = data.files[0];
			
			if (file.type === '' && file.size === 4096) {
				data.textStatus = 'dirorzero';
				data.errorThrown = t('files', 'Unable to upload {filename} as it is a directory or has 0 bytes',
					{filename: file.name}
				);
			}
			
			// add size
			selection.totalBytes += file.size;
			
			//check max upload size
			if (selection.totalBytes > $('#max_upload').val()) {
				data.textStatus = 'notenoughspace';
				data.errorThrown = t('files', 'Not enough space available');
			}
			
			// end upload for whole selection on error
			if (data.errorThrown) {
				// trigger fileupload fail
				var fu = that.data('blueimp-fileupload') || that.data('fileupload');
				fu._trigger('fail', e, data);
				return false; //don't upload anything
			}

			// check existing files when all is collected
			if ( selection.uploads.length >= selection.filesToUpload ) {
				
				//remove our selection hack:
				delete data.originalFiles.selection;

				var callbacks = {
					
					onNoConflicts: function (selection) {
						$.each(selection.uploads, function(i, upload) {
							upload.submit();
						});
					},
					onSkipConflicts: function (selection) {
					//TODO mark conflicting files as toskip
					},
					onReplaceConflicts: function (selection) {
						//TODO mark conflicting files as toreplace
					},
					onChooseConflicts: function (selection) {
						//TODO mark conflicting files as chosen
					},
					onCancel: function (selection) {
						$.each(selection.uploads, function(i, upload) {
							upload.abort();
						});
					}
				};

				OC.Upload.checkExistingFiles(selection, callbacks);
				
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

			return true; // continue adding files
		},
		/**
		 * called after the first add, does NOT have the data param
		 * @param e
		 */
		start: function(e) {
			OC.Upload.logStatus('start', e, null);
		},
		submit: function (e, data) {
			OC.Upload.rememberUpload(data);
		},
		fail: function(e, data) {
			OC.Upload.logStatus('fail', e, data);
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
			//var selection = OC.Upload.getSelection(data.originalFiles);
			//OC.Upload.deleteSelectionUpload(selection, data.files[0].name);
			OC.Upload.deleteUpload(data);
		},
		/**
		 * called for every successful upload
		 * @param e
		 * @param data
		 */
		done:function(e, data) {
			OC.Upload.logStatus('done', e, data);
			// handle different responses (json or body from iframe for ie)
			var response;
			if (typeof data.result === 'string') {
				response = data.result;
			} else {
				//fetch response from iframe
				response = data.result[0].body.innerText;
			}
			var result=$.parseJSON(response);

			delete data.jqXHR;
				
			if(typeof result[0] === 'undefined') {
				data.textStatus = 'servererror';
				data.errorThrown = t('files', 'Could not get result from server.');
				var fu = $(this).data('blueimp-fileupload') || $(this).data('fileupload');
				fu._trigger('fail', e, data);
			} else if (result[0].status === 'existserror') {
				//show "file already exists" dialog
				var original = result[0];
				var replacement = data.files[0];
				var fu = $(this).data('blueimp-fileupload') || $(this).data('fileupload');
				OC.dialogs.fileexists(data, original, replacement, OC.Upload, fu);
			} else if (result[0].status !== 'success') {
				//delete data.jqXHR;
				data.textStatus = 'servererror';
				data.errorThrown = t('files', result.data.message);
				var fu = $(this).data('blueimp-fileupload') || $(this).data('fileupload');
				fu._trigger('fail', e, data);
			}
			

		},
		/**
		 * called after last upload
		 * @param e
		 * @param data
		 */
		stop: function(e, data) {
			OC.Upload.logStatus('stop', e, data);
		}
	};

	if ( document.getElementById('data-upload-form') ) {
		// initialize jquery fileupload (blueimp)
		var fileupload = $('#file_upload_start').fileupload(file_upload_param);
		window.file_upload_param = fileupload;
		
		if(supportAjaxUploadWithProgress()) {
			
			// add progress handlers
			fileupload.on('fileuploadadd', function(e, data) {
				OC.Upload.logStatus('progress handle fileuploadadd', e, data);
				//show cancel button
				//if(data.dataType !== 'iframe') { //FIXME when is iframe used? only for ie?
				//	$('#uploadprogresswrapper input.stop').show();
				//}
			});
			// add progress handlers
			fileupload.on('fileuploadstart', function(e, data) {
				OC.Upload.logStatus('progress handle fileuploadstart', e, data);
				$('#uploadprogresswrapper input.stop').show();
				$('#uploadprogressbar').progressbar({value:0});
				$('#uploadprogressbar').fadeIn();
			});
			fileupload.on('fileuploadprogress', function(e, data) {
				OC.Upload.logStatus('progress handle fileuploadprogress', e, data);
				//TODO progressbar in row
			});
			fileupload.on('fileuploadprogressall', function(e, data) {
				OC.Upload.logStatus('progress handle fileuploadprogressall', e, data);
				var progress = (data.loaded / data.total) * 100;
				$('#uploadprogressbar').progressbar('value', progress);
			});
			fileupload.on('fileuploaddone', function(e, data) {
				OC.Upload.logStatus('progress handle fileuploaddone', e, data);
				//if user pressed cancel hide upload chrome
				//if (! OC.Upload.isProcessing()) {
				//	$('#uploadprogresswrapper input.stop').fadeOut();
				//	$('#uploadprogressbar').fadeOut();
				//}
			});
			fileupload.on('fileuploadstop', function(e, data) {
				OC.Upload.logStatus('progress handle fileuploadstop', e, data);
				//if(OC.Upload.progressBytes()>=100) { //only hide controls when all selections have ended uploading

					//OC.Upload.cancelUploads(); //cleanup

				//	if(data.dataType !== 'iframe') {
				//		$('#uploadprogresswrapper input.stop').hide();
				//	}

				//	$('#uploadprogressbar').progressbar('value', 100);
				//	$('#uploadprogressbar').fadeOut();
				//}
				//if user pressed cancel hide upload chrome
				//if (! OC.Upload.isProcessing()) {
				$('#uploadprogresswrapper input.stop').fadeOut();
				$('#uploadprogressbar').fadeOut();
				//}
			});
			fileupload.on('fileuploadfail', function(e, data) {
				OC.Upload.logStatus('progress handle fileuploadfail', e, data);
				//if user pressed cancel hide upload progress bar and cancel button
				if (data.errorThrown === 'abort') {
					$('#uploadprogresswrapper input.stop').fadeOut();
					$('#uploadprogressbar').fadeOut();
				}
			});
		
		} else {
			console.log('skipping file progress because your browser is broken');
		}
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
	$(window).on('beforeunload', function(e) {
		if (OC.Upload.isProcessing()) {
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
								tr.attr('data-size',result.data.size);
								tr.attr('data-mime', result.data.mime);
								tr.attr('data-id', result.data.id);
								tr.find('.filesize').text(humanFileSize(result.data.size));
								var path = getPathForPreview(name);
								lazyLoadPreview(path, result.data.mime, function(previewpath){
									tr.find('td.filename').attr('style','background-image:url('+previewpath+')');
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
					if($('html.lte9').length === 0) {
						$('#uploadprogressbar').progressbar({value:0});
						$('#uploadprogressbar').fadeIn();
					}
					var eventSource = new OC.EventSource(
						OC.filePath('files', 'ajax', 'newfile.php'),
						{dir:$('#dir').val(), source:name, filename:localName}
					);
					eventSource.listen('progress',function(progress){
						//IE < 10 does not fire the necessary events for the progress bar.
						if($('html.lte9').length === 0) {
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
						var path = $('#dir').val()+'/'+localName;
						lazyLoadPreview(path, mime, function(previewpath){
							tr.find('td.filename').attr('style','background-image:url('+previewpath+')');
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
