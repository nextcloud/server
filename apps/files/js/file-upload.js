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

/* global Files, FileList, jQuery, oc_requesttoken, humanFileSize, getUniqueName */

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
			data.formData.push({name:'resolution', value:'replace'}); //hack for ie8
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
			data.formData.push({name:'resolution', value:'autorename'}); //hack for ie8
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
	 * TODO checks the list of existing files prior to uploading and shows a simple dialog to choose
	 * skip all, replace all or choose which files to keep
	 * @param {array} selection of files to upload
	 * @param {object} callbacks - object with several callback methods
	 * @param {function} callbacks.onNoConflicts
	 * @param {function} callbacks.onSkipConflicts
	 * @param {function} callbacks.onReplaceConflicts
	 * @param {function} callbacks.onChooseConflicts
	 * @param {function} callbacks.onCancel
	 */
	checkExistingFiles: function (selection, callbacks) {
		/*
		$.each(selection.uploads, function(i, upload) {
			var $row = OCA.Files.App.fileList.findFileEl(upload.files[0].name);
			if ($row) {
				// TODO check filelist before uploading and show dialog on conflicts, use callbacks
			}
		});
		*/
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

	init: function() {
		if ( $('#file_upload_start').exists() ) {
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
						try {
							var reader = new FileReader();
							reader.readAsBinaryString(file);
						} catch (NS_ERROR_FILE_ACCESS_DENIED) {
							//file is a directory
							data.textStatus = 'dirorzero';
							data.errorThrown = t('files',
								'Unable to upload {filename} as it is a directory or has 0 bytes',
								{filename: file.name}
							);
						}
					}

					// add size
					selection.totalBytes += file.size;
					// update size of biggest file
					selection.biggestFileBytes = Math.max(selection.biggestFileBytes, file.size);

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
					if ( ! data.formData ) {
						var fileDirectory = '';
						if(typeof data.files[0].relativePath !== 'undefined') {
							fileDirectory = data.files[0].relativePath;
						}
						// noone set update parameters, we set the minimum
						data.formData = {
							requesttoken: oc_requesttoken,
							dir: data.targetDir || FileList.getCurrentDirectory(),
							file_directory: fileDirectory
						};
					}
				},
				fail: function(e, data) {
					OC.Upload.log('fail', e, data);
					if (typeof data.textStatus !== 'undefined' && data.textStatus !== 'success' ) {
						if (data.textStatus === 'abort') {
							OC.Notification.show(t('files', 'Upload cancelled.'));
						} else {
							// HTTP connection problem
							OC.Notification.show(data.errorThrown);
							if (data.result) {
								var result = JSON.parse(data.result);
								if (result && result[0] && result[0].data && result[0].data.code === 'targetnotfound') {
									// abort upload of next files if any
									OC.Upload.cancelUploads();
								}
							}
						}
						//hide notification after 10 sec
						setTimeout(function() {
							OC.Notification.hide();
						}, 10000);
					}
					OC.Upload.deleteUpload(data);
				},
				/**
				 * called for every successful upload
				 * @param {object} e
				 * @param {object} data
				 */
				done:function(e, data) {
					OC.Upload.log('done', e, data);
					// handle different responses (json or body from iframe for ie)
					var response;
					if (typeof data.result === 'string') {
						response = data.result;
					} else {
						//fetch response from iframe
						response = data.result[0].body.innerText;
					}
					var result = $.parseJSON(response);

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
					$('#uploadprogressbar').progressbar({value: 0});
					OC.Upload._showProgressBar();
				});
				fileupload.on('fileuploadprogress', function(e, data) {
					OC.Upload.log('progress handle fileuploadprogress', e, data);
					//TODO progressbar in row
				});
				fileupload.on('fileuploadprogressall', function(e, data) {
					OC.Upload.log('progress handle fileuploadprogressall', e, data);
					var progress = (data.loaded / data.total) * 100;
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

		$(document).click(function(ev) {
			// do not close when clicking in the dropdown
			if ($(ev.target).closest('#new').length){
				return;
			}
			$('#new>ul').hide();
			$('#new').removeClass('active');
			if ($('#new .error').length > 0) {
				$('#new .error').tipsy('hide');
			}
			$('#new li').each(function(i,element) {
				if ($(element).children('p').length === 0) {
					$(element).children('form').remove();
					$(element).append('<p>' + $(element).data('text') + '</p>');
				}
			});
		});
		$('#new').click(function(event) {
			event.stopPropagation();
		});
		$('#new>a').click(function() {
			$('#new>ul').toggle();
			$('#new').toggleClass('active');
		});
		$('#new li').click(function() {
			if ($(this).children('p').length === 0) {
				return;
			}

			$('#new .error').tipsy('hide');

			$('#new li').each(function(i, element) {
				if ($(element).children('p').length === 0) {
					$(element).children('form').remove();
					$(element).append('<p>' + $(element).data('text') + '</p>');
				}
			});

			var type = $(this).data('type');
			var text = $(this).children('p').text();
			$(this).data('text', text);
			$(this).children('p').remove();

			// add input field
			var form = $('<form></form>');
			var input = $('<input type="text" placeholder="https://…">');
			var newName = $(this).attr('data-newname') || '';
			var fileType = 'input-' + $(this).attr('data-type');
			if (newName) {
				input.val(newName);
				input.attr('id', fileType);
			}
			var label = $('<label class="hidden-visually" for="">' + escapeHTML(newName) + '</label>');
			label.attr('for', fileType);

			form.append(label).append(input);
			$(this).append(form);
			var lastPos;
			var checkInput = function () {
				var filename = input.val();
				if (type === 'web' && filename.length === 0) {
					throw t('files', 'URL cannot be empty');
				} else if (type !== 'web' && ! Files.isFileNameValid(filename)) {
					// Files.isFileNameValid(filename) throws an exception itself
				} else if (FileList.inList(filename)) {
					throw t('files', '{new_name} already exists', {new_name: filename});
				} else {
					return true;
				}
			};

			// verify filename on typing
			input.keyup(function(event) {
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
			});

			input.focus();
			// pre select name up to the extension
			lastPos = newName.lastIndexOf('.');
			if (lastPos === -1) {
				lastPos = newName.length;
			}
			input.selectRange(0, lastPos);
			form.submit(function(event) {
				event.stopPropagation();
				event.preventDefault();
				try {
					checkInput();
					var newname = input.val();
					if (FileList.lastAction) {
						FileList.lastAction();
					}
					var name = FileList.getUniqueName(newname);
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
								{
									dir: FileList.getCurrentDirectory(),
									filename: name
								},
								function(result) {
									if (result.status === 'success') {
										FileList.add(result.data, {hidden: hidden, animate: true, scrollTo: true});
									} else {
										OC.dialogs.alert(result.data.message, t('core', 'Could not create file'));
									}
								}
							);
							break;
						case 'folder':
							$.post(
								OC.filePath('files','ajax','newfolder.php'),
								{
									dir: FileList.getCurrentDirectory(),
									foldername: name
								},
								function(result) {
									if (result.status === 'success') {
										FileList.add(result.data, {hidden: hidden, animate: true, scrollTo: true});
									} else {
										OC.dialogs.alert(result.data.message, t('core', 'Could not create folder'));
									}
								}
							);
							break;
						case 'web':
							if (name.substr(0, 8) !== 'https://' && name.substr(0, 7) !== 'http://') {
								name = 'http://' + name;
							}
							var localName = name;
							if (localName.substr(localName.length-1, 1) === '/') {//strip /
								localName = localName.substr(0, localName.length-1);
							}
							if (localName.indexOf('/')) { //use last part of url
								localName = localName.split('/').pop();
							} else { //or the domain
								localName = (localName.match(/:\/\/(.[^\/]+)/)[1]).replace('www.', '');
							}
							localName = FileList.getUniqueName(localName);
							//IE < 10 does not fire the necessary events for the progress bar.
							if ($('html.lte9').length === 0) {
								$('#uploadprogressbar').progressbar({value: 0});
								OC.Upload._showProgressBar();
							}

							var eventSource = new OC.EventSource(
								OC.filePath('files', 'ajax', 'newfile.php'),
								{
									dir: FileList.getCurrentDirectory(),
									source: name,
									filename: localName
								}
							);
							eventSource.listen('progress', function(progress) {
								//IE < 10 does not fire the necessary events for the progress bar.
								if ($('html.lte9').length === 0) {
									$('#uploadprogressbar').progressbar('value',progress);
								}
							});
							eventSource.listen('success', function(data) {
								var file = data;
								OC.Upload._hideProgressBar();

								FileList.add(file, {hidden: hidden, animate: true});
							});
							eventSource.listen('error', function(error) {
								OC.Upload._hideProgressBar();
								var message = (error && error.message) || t('core', 'Error fetching URL');
								OC.Notification.show(message);
								//hide notification after 10 sec
								setTimeout(function() {
									OC.Notification.hide();
								}, 10000);
							});
							break;
					}
					var li = form.parent();
					form.remove();
					/* workaround for IE 9&10 click event trap, 2 lines: */
					$('input').first().focus();
					$('#content').focus();
					li.append('<p>' + li.data('text') + '</p>');
					$('#new>a').click();
				} catch (error) {
					input.attr('title', error);
					input.tipsy({gravity: 'w', trigger: 'manual'});
					input.tipsy('show');
					input.addClass('error');
				}
			});
		});
		window.file_upload_param = file_upload_param;
		return file_upload_param;
	}
};

$(document).ready(function() {
	OC.Upload.init();
});


