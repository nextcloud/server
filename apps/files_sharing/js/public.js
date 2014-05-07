/*
 * Copyright (c) 2014
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
 *
 */

/* global OC, FileActions, FileList, Files */

$(document).ready(function() {

	var mimetype = $('#mimetype').val();

	if (typeof FileActions !== 'undefined') {
		// Show file preview if previewer is available, images are already handled by the template
		if (mimetype.substr(0, mimetype.indexOf('/')) !== 'image' && $('.publicpreview').length === 0) {
			// Trigger default action if not download TODO
			var action = FileActions.getDefault(mimetype, 'file', OC.PERMISSION_READ);
			if (typeof action !== 'undefined') {
				action($('#filename').val());
			}
		}
	}

	// dynamically load image previews
	if (mimetype.substr(0, mimetype.indexOf('/')) === 'image' ) {

		var params = {
			x: $(document).width() * window.devicePixelRatio,
			a: 'true',
			file: encodeURIComponent($('#dir').val() + $('#filename').val()),
			t: $('#sharingToken').val()
		};

		var img = $('<img class="publicpreview">');
		img.attr('src', OC.filePath('files_sharing', 'ajax', 'publicpreview.php') + '?' + OC.buildQueryString(params));
		img.appendTo('#imgframe');
	}

	// override since the format is different
	if (typeof Files !== 'undefined') {
		Files.getDownloadUrl = function(filename, dir) {
			if ($.isArray(filename)) {
				filename = JSON.stringify(filename);
			}
			var path = dir || FileList.getCurrentDirectory();
			var params = {
				service: 'files',
				t: $('#sharingToken').val(),
				path: path,
				files: filename,
				download: null
			};
			return OC.filePath('', '', 'public.php') + '?' + OC.buildQueryString(params);
		};

		Files.getAjaxUrl = function(action, params) {
			params = params || {};
			params.t = $('#sharingToken').val();
			return OC.filePath('files_sharing', 'ajax', action + '.php') + '?' + OC.buildQueryString(params);
		};

		FileList.linkTo = function(dir) {
			var params = {
				service: 'files',
				t: $('#sharingToken').val(),
				dir: dir
			};
			return OC.filePath('', '', 'public.php') + '?' + OC.buildQueryString(params);
		};

		Files.generatePreviewUrl = function(urlSpec) {
			urlSpec.t = $('#dirToken').val();
			return OC.generateUrl('/apps/files_sharing/ajax/publicpreview.php?') + $.param(urlSpec);
		};

		var file_upload_start = $('#file_upload_start');
		file_upload_start.on('fileuploadadd', function(e, data) {
			var fileDirectory = '';
			if(typeof data.files[0].relativePath !== 'undefined') {
				fileDirectory = data.files[0].relativePath;
			}

			// Add custom data to the upload handler
			data.formData = {
				requesttoken: $('#publicUploadRequestToken').val(),
				dirToken: $('#dirToken').val(),
				subdir: $('input#dir').val(),
				file_directory: fileDirectory
			};
		});
	}

	$(document).on('click', '#directLink', function() {
		$(this).focus();
		$(this).select();
	});

});
