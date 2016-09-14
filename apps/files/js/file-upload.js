/*
 * Copyright (c) 2014
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
 *
 */

/**
 * The file upload code uses several hooks to interact with blueimps jQuery file upload library:
 * 1. the core upload handling hooks are added when initializing the plugin,
 * 2. if the browser supports progress events they are added in a separate set after the initialization
 * 3. every app can add it's own triggers for fileupload
 *    - files adds d'n'd handlers and also reacts to done events to add new rows to the filelist
 *    - TODO pictures upload button
 *    - TODO music upload button
 */

/* global jQuery, oc_requesttoken, humanFileSize, FileList */

/**
 * Function that will allow us to know if Ajax uploads are supported
 * @link https://github.com/New-Bamboo/example-ajax-upload/blob/master/public/index.html
 * also see article @link http://blog.new-bamboo.co.uk/2012/01/10/ridiculously-simple-ajax-uploads-with-formdata
 */
function supportAjaxUploadWithProgress() {
	return supportFileAPI() && supportAjaxUploadProgressEvents() && supportFormData();

	// Is the File API supported?
	function supportFileAPI() {
		var fi = document.createElement('INPUT');
		fi.type = 'file';
		return 'files' in fi;
	}

	// Are progress events supported?
	function supportAjaxUploadProgressEvents() {
		var xhr = new XMLHttpRequest();
		return !! (xhr && ('upload' in xhr) && ('onprogress' in xhr.upload));
	}

	// Is FormData supported?
	function supportFormData() {
		return !! window.FormData;
	}
}

/**
 * Add form data into the given form data
 *
 * @param {Array|Object} formData form data which can either be an array or an object
 * @param {Object} newData key-values to add to the form data
 *
 * @return updated form data
 */
function addFormData(formData, newData) {
	// in IE8, formData is an array instead of object
	if (_.isArray(formData)) {
		_.each(newData, function(value, key) {
			formData.push({name: key, value: value});
		});
	} else {
		formData = _.extend(formData, newData);
	}
	return formData;
}

/**
 * keeps track of uploads in progress and implements callbacks for the conflicts dialog
 * @namespace
 */
OC.Upload = {
	_uploads: [],
	/**
	 * deletes the jqHXR object from a data selection
	 * @param {object} data
	 */
	deleteUpload:function(data) {
		delete data.jqXHR;
	},
	/**
	 * cancels all uploads
	 */
	cancelUploads:function() {
		this.log('canceling uploads');
		jQuery.each(this._uploads, function(i, jqXHR) {
			jqXHR.abort();
		});
		this._uploads = [];
	},
	rememberUpload:function(jqXHR) {
		if (jqXHR) {
			this._uploads.push(jqXHR);
		}
	},
	showUploadCancelMessage: _.debounce(function() {
		OC.Notification.showTemporary(t('files', 'Upload cancelled.'), {timeout: 10});
	}, 500),
	/**
	 * Checks the currently known uploads.
	 * returns true if any hxr has the state 'pending'
	 * @returns {boolean}
	 */
	isProcessing:function() {
		var count = 0;

		jQuery.each(this._uploads, function(i, data) {
			if (data.state() === 'pending') {
				count++;
			}
		});
		return count > 0;
	},
	/**
	 * callback for the conflicts dialog
	 * @param {object} data
	 */
	onCancel:function(data) {
		this.cancelUploads();
	},
	/**
	 * callback for the conflicts dialog
	 * calls onSkip, onReplace or onAutorename for each conflict
	 * @param {object} conflicts - list of conflict elements
	 */
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
	/**
	 * handle skipping an upload
	 * @param {object} data
	 */
	onSkip:function(data) {
		this.log('skip', null, data);
		this.deleteUpload(data);
	},
	/**
	 * handle replacing a file on the server with an uploaded file
	 * @param {object} data
	 */
	onReplace:function(data) {
		this.log('replace', null, data);
		if (data.data) {
			data.data.append('resolution', 'replace');
		} else {
			if (!data.formData) {
				data.formData = {};
			}
			addFormData(data.formData, {resolution: 'replace'});
		}
		data.submit();
	},
	/**
	 * handle uploading a file and letting the server decide a new name
	 * @param {object} data
	 */
	onAutorename:function(data) {
		this.log('autorename', null, data);
		if (data.data) {
			data.data.append('resolution', 'autorename');
		} else {
			if (!data.formData) {
				data.formData = {};
			}
			addFormData(data.formData, {resolution: 'autorename'});
		}
		data.submit();
	},
	_trace:false, //TODO implement log handler for JS per class?
	log:function(caption, e, data) {
		if (this._trace) {
			console.log(caption);
			console.log(data);
		}
	},
	/**
	 * checks the list of existing files prior to uploading and shows a simple dialog to choose
	 * skip all, replace all or choose which files to keep
	 *
	 * @param {array} selection of files to upload
	 * @param {object} callbacks - object with several callback methods
	 * @param {function} callbacks.onNoConflicts
	 * @param {function} callbacks.onSkipConflicts
	 * @param {function} callbacks.onReplaceConflicts
	 * @param {function} callbacks.onChooseConflicts
	 * @param {function} callbacks.onCancel
	 */
	checkExistingFiles: function (selection, callbacks) {
		var fileList = FileList;
		var conflicts = [];
		// only keep non-conflicting uploads
		selection.uploads = _.filter(selection.uploads, function(upload) {
			var fileInfo = fileList.findFile(upload.files[0].name);
			if (fileInfo) {
				conflicts.push([
					// original
					_.extend(fileInfo, {
						directory: fileInfo.directory || fileInfo.path || fileList.getCurrentDirectory()
					}),
					// replacement (File object)
					upload
				]);
				return false;
			}
			return true;
		});
		if (conflicts.length) {
			// wait for template loading
			OC.dialogs.fileexists(null, null, null, OC.Upload).done(function() {
				_.each(conflicts, function(conflictData) {
					OC.dialogs.fileexists(conflictData[1], conflictData[0], conflictData[1].files[0], OC.Upload);
				});
			});
		}

		// upload non-conflicting files
		// note: when reaching the server they might still meet conflicts
		// if the folder was concurrently modified, these will get added
		// to the already visible dialog, if applicable
		callbacks.onNoConflicts(selection);
	},

	_hideProgressBar: function() {
		$('#uploadprogresswrapper .stop').fadeOut();
		$('#uploadprogressbar').fadeOut(function() {
			$('#file_upload_start').trigger(new $.Event('resized'));
		});
	},

	_showProgressBar: function() {
		$('#uploadprogressbar').fadeIn();
		$('#file_upload_start').trigger(new $.Event('resized'));
	},

	/**
	 * Returns whether the given file is known to be a received shared file
	 *
	 * @param {Object} file file
	 * @return {bool} true if the file is a shared file
	 */
	_isReceivedSharedFile: function(file) {
		if (!window.FileList) {
			return false;
		}
		var $tr = window.FileList.findFileEl(file.name);
		if (!$tr.length) {
			return false;
		}

		return ($tr.attr('data-mounttype') === 'shared-root' && $tr.attr('data-mime') !== 'httpd/unix-directory');
	},

	init: function() {
		var self = this;
		if ( $('#file_upload_start').exists() ) {
			var file_upload_param = {
				dropZone: $('#app-content'), // restrict dropZone to app-content div
				pasteZone: null, 
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
				 * @param {object} e
				 * @param {object} data
				 * @returns {boolean}
				 */
				add: function(e, data) {
					OC.Upload.log('add', e, data);
					var that = $(this), freeSpace;

					// we need to collect all data upload objects before
					// starting the upload so we can check their existence
					// and set individual conflict actions. Unfortunately,
					// there is only one variable that we can use to identify
					// the selection a data upload is part of, so we have to
					// collect them in data.originalFiles turning
					// singleFileUploads off is not an option because we want
					// to gracefully handle server errors like 'already exists'

					// create a container where we can store the data objects
					if ( ! data.originalFiles.selection ) {
						// initialize selection and remember number of files to upload
						data.originalFiles.selection = {
							uploads: [],
							filesToUpload: data.originalFiles.length,
							totalBytes: 0,
							biggestFileBytes: 0
						};
					}
					var selection = data.originalFiles.selection;

					// add uploads
					if ( selection.uploads.length < selection.filesToUpload ) {
						// remember upload
						selection.uploads.push(data);
					}

					//examine file
					var file = data.files[0];
					try {
						// FIXME: not so elegant... need to refactor that method to return a value
						Files.isFileNameValid(file.name);
					}
					catch (errorMessage) {
						data.textStatus = 'invalidcharacters';
						data.errorThrown = errorMessage;
					}

					// in case folder drag and drop is not supported file will point to a directory
					// http://stackoverflow.com/a/20448357
					if ( ! file.type && file.size%4096 === 0 && file.size <= 102400) {
						var dirUploadFailure = false;
						try {
							var reader = new FileReader();
							reader.readAsBinaryString(file);
						} catch (NS_ERROR_FILE_ACCESS_DENIED) {
							//file is a directory
							dirUploadFailure = true;
						}
						if (file.size === 0) {
							// file is empty or a directory
							dirUploadFailure = true;
						}

						if (dirUploadFailure) {
							data.textStatus = 'dirorzero';
							data.errorThrown = t('files',
								'Unable to upload {filename} as it is a directory or has 0 bytes',
								{filename: file.name}
							);
						}
					}

					// only count if we're not overwriting an existing shared file
					if (self._isReceivedSharedFile(file)) {
						file.isReceivedShare = true;
					} else {
						// add size
						selection.totalBytes += file.size;
						// update size of biggest file
						selection.biggestFileBytes = Math.max(selection.biggestFileBytes, file.size);
					}

					// check PHP upload limit against biggest file
					if (selection.biggestFileBytes > $('#upload_limit').val()) {
						data.textStatus = 'sizeexceedlimit';
						data.errorThrown = t('files',
							'Total file size {size1} exceeds upload limit {size2}', {
							'size1': humanFileSize(selection.biggestFileBytes),
							'size2': humanFileSize($('#upload_limit').val())
						});
					}

					// check free space
					freeSpace = $('#free_space').val();
					if (freeSpace >= 0 && selection.totalBytes > freeSpace) {
						data.textStatus = 'notenoughspace';
						data.errorThrown = t('files',
							'Not enough free space, you are uploading {size1} but only {size2} is left', {
							'size1': humanFileSize(selection.totalBytes),
							'size2': humanFileSize($('#free_space').val())
						});
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

					return true; // continue adding files
				},
				/**
				 * called after the first add, does NOT have the data param
				 * @param {object} e
				 */
				start: function(e) {
					OC.Upload.log('start', e, null);
					//hide the tooltip otherwise it covers the progress bar
					$('#upload').tipsy('hide');
				},
				submit: function(e, data) {
					OC.Upload.rememberUpload(data);
					if (!data.formData) {
						data.formData = {};
					}

					var fileDirectory = '';
					if(typeof data.files[0].relativePath !== 'undefined') {
						fileDirectory = data.files[0].relativePath;
					}

					var params = {
						requesttoken: oc_requesttoken,
						dir: data.targetDir || FileList.getCurrentDirectory(),
						file_directory: fileDirectory,
					};
					if (data.files[0].isReceivedShare) {
						params.isReceivedShare = true;
					}

					addFormData(data.formData, params);
				},
				fail: function(e, data) {
					OC.Upload.log('fail', e, data);
					if (typeof data.textStatus !== 'undefined' && data.textStatus !== 'success' ) {
						if (data.textStatus === 'abort') {
							OC.Upload.showUploadCancelMessage();
						} else {
							// HTTP connection problem
							var message = t('files', 'Error uploading file "{fileName}": {message}', {
								fileName: escapeHTML(data.files[0].name),
								message: data.errorThrown
							}, undefined, {escape: false});
							OC.Notification.show(message, {timeout: 0, type: 'error'});
							if (data.result) {
								var result = JSON.parse(data.result);
								if (result && result[0] && result[0].data && result[0].data.code === 'targetnotfound') {
									// abort upload of next files if any
									OC.Upload.cancelUploads();
								}
							}
						}
					}
					OC.Upload.deleteUpload(data);
				},
				/**
				 * called for every successful upload
				 * @param {object} e
				 * @param {object} data
				 */
				done: function(e, data) {
					OC.Upload.log('done', e, data);
					// handle different responses (json or body from iframe for ie)
					var response;
					if (typeof data.result === 'string') {
						response = data.result;
					} else {
						//fetch response from iframe
						response = data.result[0].body.innerText;
					}
					var result = JSON.parse(response);

					delete data.jqXHR;

					var fu = $(this).data('blueimp-fileupload') || $(this).data('fileupload');

					if (result.status === 'error' && result.data && result.data.message){
						data.textStatus = 'servererror';
						data.errorThrown = result.data.message;
						fu._trigger('fail', e, data);
					} else if (typeof result[0] === 'undefined') {
						data.textStatus = 'servererror';
						data.errorThrown = t('files', 'Could not get result from server.');
						fu._trigger('fail', e, data);
					} else if (result[0].status === 'readonly') {
						var original = result[0];
						var replacement = data.files[0];
						OC.dialogs.fileexists(data, original, replacement, OC.Upload);
					} else if (result[0].status === 'existserror') {
						//show "file already exists" dialog
						var original = result[0];
						var replacement = data.files[0];
						OC.dialogs.fileexists(data, original, replacement, OC.Upload);
					} else if (result[0].status !== 'success') {
						//delete data.jqXHR;
						data.textStatus = 'servererror';
						data.errorThrown = result[0].data.message; // error message has been translated on server
						fu._trigger('fail', e, data);
					} else { // Successful upload
						// Checking that the uploaded file is the last one and contained in the current directory
						if (data.files[0] === data.originalFiles[data.originalFiles.length - 1] &&
							result[0].directory === FileList.getCurrentDirectory()) {
							// Scroll to the last uploaded file and highlight all of them
							var fileList = _.pluck(data.originalFiles, 'name');
							FileList.highlightFiles(fileList);
						}
					}
				},
				/**
				 * called after last upload
				 * @param {object} e
				 * @param {object} data
				 */
				stop: function(e, data) {
					OC.Upload.log('stop', e, data);
				}
			};

			// initialize jquery fileupload (blueimp)
			var fileupload = $('#file_upload_start').fileupload(file_upload_param);
			window.file_upload_param = fileupload;

			if (supportAjaxUploadWithProgress()) {
				//remaining time
				var lastUpdate = new Date().getMilliseconds();
				var lastSize = 0;
				var bufferSize = 20;
				var buffer = [];
				var bufferIndex = 0;
				var bufferTotal = 0;
				for(var i = 0; i < bufferSize;i++){
					buffer[i] = 0;    
				}
				// add progress handlers
				fileupload.on('fileuploadadd', function(e, data) {
					OC.Upload.log('progress handle fileuploadadd', e, data);
					//show cancel button
					//if (data.dataType !== 'iframe') { //FIXME when is iframe used? only for ie?
					//	$('#uploadprogresswrapper .stop').show();
					//}
				});
				// add progress handlers
				fileupload.on('fileuploadstart', function(e, data) {
					OC.Upload.log('progress handle fileuploadstart', e, data);
					$('#uploadprogresswrapper .stop').show();
					$('#uploadprogresswrapper .label').show();
					$('#uploadprogressbar').progressbar({value: 0});
					$('#uploadprogressbar .ui-progressbar-value').
						html('<em class="label inner"><span class="desktop">'
							+ t('files', 'Uploading...')
							+ '</span><span class="mobile">'
							+ t('files', '...')
							+ '</span></em>');
                    $('#uploadprogressbar').tipsy({gravity:'n', fade:true, live:true});
					OC.Upload._showProgressBar();
				});
				fileupload.on('fileuploadprogress', function(e, data) {
					OC.Upload.log('progress handle fileuploadprogress', e, data);
					//TODO progressbar in row
				});
				fileupload.on('fileuploadprogressall', function(e, data) {
					OC.Upload.log('progress handle fileuploadprogressall', e, data);
					var progress = (data.loaded / data.total) * 100;
					var thisUpdate = new Date().getMilliseconds();
					var diffUpdate = (thisUpdate - lastUpdate)/1000; // eg. 2s
					lastUpdate = thisUpdate;
					var diffSize = data.loaded - lastSize;
					lastSize = data.loaded;
					diffSize = diffSize / diffUpdate; // apply timing factor, eg. 1mb/2s = 0.5mb/s
					var remainingSeconds = ((data.total - data.loaded) / diffSize);
					if(remainingSeconds >= 0) {
						bufferTotal = bufferTotal - (buffer[bufferIndex]) + remainingSeconds;
						buffer[bufferIndex] = remainingSeconds; //buffer to make it smoother
						bufferIndex = (bufferIndex + 1) % bufferSize;
					}
					var smoothRemainingSeconds = (bufferTotal / bufferSize); //seconds
					var date = new Date(smoothRemainingSeconds * 1000);
					var timeStringDesktop = "";
					var timeStringMobile = ""; 
					if(date.getUTCHours() > 0){
						timeStringDesktop = t('files', '{hours}:{minutes}:{seconds} hour{plural_s} left' , { 
							hours:date.getUTCHours(),
							minutes: ('0' + date.getUTCMinutes()).slice(-2),
							seconds: ('0' + date.getUTCSeconds()).slice(-2),
							plural_s: ( smoothRemainingSeconds === 3600  ? "": "s") // 1 hour = 1*60m*60s = 3600s
						});						
						timeStringMobile = t('files', '{hours}:{minutes}h' , {
							hours:date.getUTCHours(),
							minutes: ('0' + date.getUTCMinutes()).slice(-2),
							seconds: ('0' + date.getUTCSeconds()).slice(-2)
						});
					} else if(date.getUTCMinutes() > 0){
						timeStringDesktop = t('files', '{minutes}:{seconds} minute{plural_s} left' , {
							minutes: date.getUTCMinutes(),
							seconds: ('0' + date.getUTCSeconds()).slice(-2),
							plural_s: (smoothRemainingSeconds === 60 ? "": "s") // 1 minute = 1*60s = 60s
						}); 
						timeStringMobile = t('files', '{minutes}:{seconds}m' , {
							minutes: date.getUTCMinutes(),
							seconds: ('0' + date.getUTCSeconds()).slice(-2)
						});
					} else if(date.getUTCSeconds() > 0){ 
						timeStringDesktop = t('files', '{seconds} second{plural_s} left' , {
							seconds: date.getUTCSeconds(),
							plural_s: (smoothRemainingSeconds === 1 ? "": "s") // 1 second = 1s = 1s
						});
						timeStringMobile = t('files', '{seconds}s' , {seconds: date.getUTCSeconds()});
					} else {
						timeStringDesktop = t('files', 'Any moment now...');
						timeStringMobile = t('files', 'Soon...');
					}
					$('#uploadprogressbar .label .mobile').text(timeStringMobile);
					$('#uploadprogressbar .label .desktop').text(timeStringDesktop);
					$('#uploadprogressbar').attr('original-title',
						t('files', '{loadedSize} of {totalSize} ({bitrate})' , {
							loadedSize: humanFileSize(data.loaded),
							totalSize: humanFileSize(data.total),
							bitrate: humanFileSize(data.bitrate) + '/s'
						})
					);
					$('#uploadprogressbar').progressbar('value', progress);
				});
				fileupload.on('fileuploadstop', function(e, data) {
					OC.Upload.log('progress handle fileuploadstop', e, data);
					OC.Upload._hideProgressBar();
				});
				fileupload.on('fileuploadfail', function(e, data) {
					OC.Upload.log('progress handle fileuploadfail', e, data);
					//if user pressed cancel hide upload progress bar and cancel button
					if (data.errorThrown === 'abort') {
						OC.Upload._hideProgressBar();
					}
				});
				var disableDropState = function() {
					$('#app-content').removeClass('file-drag');
					$('.dropping-to-dir').removeClass('dropping-to-dir');
					$('.dir-drop').removeClass('dir-drop');
					$('.icon-filetype-folder-drag-accept').removeClass('icon-filetype-folder-drag-accept');
				};
				var disableClassOnFirefox = _.debounce(function() {
					disableDropState();
				}, 100);
				fileupload.on('fileuploaddragover', function(e){
					$('#app-content').addClass('file-drag');
					// dropping a folder in firefox doesn't cause a drop event
					// this is simulated by simply invoke disabling all classes
					// once no dragover event isn't noticed anymore
					if ($.browser['mozilla']) {
						disableClassOnFirefox();
					}
					$('#emptycontent .icon-folder').addClass('icon-filetype-folder-drag-accept');

					var filerow = $(e.delegatedEvent.target).closest('tr');

					if(!filerow.hasClass('dropping-to-dir')){
						$('.dropping-to-dir .icon-filetype-folder-drag-accept').removeClass('icon-filetype-folder-drag-accept');
						$('.dropping-to-dir').removeClass('dropping-to-dir');
						$('.dir-drop').removeClass('dir-drop');
					}

					if(filerow.attr('data-type') === 'dir'){
						$('#app-content').addClass('dir-drop');
						filerow.addClass('dropping-to-dir');
						filerow.find('.thumbnail').addClass('icon-filetype-folder-drag-accept');
					}
				});
				fileupload.on('fileuploaddragleave fileuploaddrop', disableDropState);
			} else {
				// for all browsers that don't support the progress bar
				// IE 8 & 9

				// show a spinner
				fileupload.on('fileuploadstart', function() {
					$('#upload').addClass('icon-loading');
					$('#upload .icon-upload').hide();
				});

				// hide a spinner
				fileupload.on('fileuploadstop fileuploadfail', function() {
					$('#upload').removeClass('icon-loading');
					$('#upload .icon-upload').show();
				});
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
		if (navigator.userAgent.search(/konqueror/i) === -1) {
			$('#file_upload_start').attr('multiple', 'multiple');
		}

		window.file_upload_param = file_upload_param;
		return file_upload_param;
	}
};

$(document).ready(function() {
	OC.Upload.init();
});


