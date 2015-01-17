/*
 * Copyright (c) 2014
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
 *
 */

/* global FileActions, Files, FileList */
/* global dragOptions, folderDropOptions */
if (!OCA.Sharing) {
	OCA.Sharing = {};
}
if (!OCA.Files) {
	OCA.Files = {};
}
/**
 * @namespace
 */
OCA.Sharing.PublicApp = {
	_initialized: false,

	/**
	 * Initializes the public share app.
	 *
	 * @param $el container
	 */
	initialize: function ($el) {
		var self = this;
		var fileActions;
		if (this._initialized) {
			return;
		}
		fileActions = new OCA.Files.FileActions();
		// default actions
		fileActions.registerDefaultActions();
		// legacy actions
		fileActions.merge(window.FileActions);
		// regular actions
		fileActions.merge(OCA.Files.fileActions);

		// in case apps would decide to register file actions later,
		// replace the global object with this one
		OCA.Files.fileActions = fileActions;

		this._initialized = true;
		this.initialDir = $('#dir').val();

		// file list mode ?
		if ($el.find('#filestable').length) {
			this.fileList = new OCA.Files.FileList(
				$el,
				{
					id: 'files.public',
					scrollContainer: $(window),
					dragOptions: dragOptions,
					folderDropOptions: folderDropOptions,
					fileActions: fileActions
				}
			);
			this.files = OCA.Files.Files;
			this.files.initialize();
			// TODO: move to PublicFileList.initialize() once
			// the code was split into a separate class
			OC.Plugins.attach('OCA.Sharing.PublicFileList', this.fileList);
		}

		var mimetype = $('#mimetype').val();
		var mimetypeIcon = $('#mimetypeIcon').val();
		mimetypeIcon = mimetypeIcon.substring(0, mimetypeIcon.length - 3);
		mimetypeIcon = mimetypeIcon + 'svg';

		var previewSupported = $('#previewSupported').val();

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
		var params = {
			x: $(document).width() * window.devicePixelRatio,
			y: $(document).height() * window.devicePixelRatio,
			a: 'true',
			file: encodeURIComponent(this.initialDir + $('#filename').val()),
			t: $('#sharingToken').val(),
			scalingup: 0
		};

		var img = $('<img class="publicpreview" alt="">');

		var fileSize = parseInt($('#filesize').val(), 10);
		var maxGifSize = parseInt($('#maxSizeAnimateGif').val(), 10);

		if (mimetype === 'image/gif' &&
			(maxGifSize === -1 || fileSize <= (maxGifSize * 1024 * 1024))) {
			img.attr('src', $('#downloadURL').val());
			img.appendTo('#imgframe');
		} else if (previewSupported === 'true' ||
			mimetype.substr(0, mimetype.indexOf('/')) === 'image' &&
			mimetype !== 'image/svg+xml') {
			img.attr('src', OC.filePath('files_sharing', 'ajax', 'publicpreview.php') + '?' + OC.buildQueryString(params));
			img.appendTo('#imgframe');
		} else if (mimetype.substr(0, mimetype.indexOf('/')) !== 'video') {
			img.attr('src', OC.Util.replaceSVGIcon(mimetypeIcon));
			img.attr('width', 128);
			img.appendTo('#imgframe');
		}

		if (this.fileList) {
			// TODO: move this to a separate PublicFileList class that extends OCA.Files.FileList (+ unit tests)
			this.fileList.getDownloadUrl = function (filename, dir) {
				if ($.isArray(filename)) {
					filename = JSON.stringify(filename);
				}
				var path = dir || FileList.getCurrentDirectory();
				var token = $('#sharingToken').val();
				var params = {
					path: path,
					files: filename
				};
				return OC.generateUrl('/s/'+token+'/download') + '?' + OC.buildQueryString(params);
			};

			this.fileList.getAjaxUrl = function (action, params) {
				params = params || {};
				params.t = $('#sharingToken').val();
				return OC.filePath('files_sharing', 'ajax', action + '.php') + '?' + OC.buildQueryString(params);
			};

			this.fileList.linkTo = function (dir) {
				var token = $('#sharingToken').val();
				var params = {
					dir: dir
				};
				return OC.generateUrl('/s/'+token+'') + '?' + OC.buildQueryString(params);
			};

			this.fileList.generatePreviewUrl = function (urlSpec) {
				urlSpec.t = $('#dirToken').val();
				return OC.generateUrl('/apps/files_sharing/ajax/publicpreview.php?') + $.param(urlSpec);
			};

			var file_upload_start = $('#file_upload_start');
			file_upload_start.on('fileuploadadd', function (e, data) {
				var fileDirectory = '';
				if (typeof data.files[0].relativePath !== 'undefined') {
					fileDirectory = data.files[0].relativePath;
				}

				// Add custom data to the upload handler
				data.formData = {
					requesttoken: $('#publicUploadRequestToken').val(),
					dirToken: $('#dirToken').val(),
					subdir: data.targetDir || self.fileList.getCurrentDirectory(),
					file_directory: fileDirectory
				};
			});

			// do not allow sharing from the public page
			delete this.fileList.fileActions.actions.all.Share;

			this.fileList.changeDirectory(this.initialDir || '/', false, true);

			// URL history handling
			this.fileList.$el.on('changeDirectory', _.bind(this._onDirectoryChanged, this));
			OC.Util.History.addOnPopStateHandler(_.bind(this._onUrlChanged, this));

			$('#download').click(function (e) {
				e.preventDefault();
				OC.redirect(FileList.getDownloadUrl());
			});
		}

		$(document).on('click', '#directLink', function () {
			$(this).focus();
			$(this).select();
		});

		$('.save-form').submit(function (event) {
			event.preventDefault();

			var remote = $(this).find('input[type="text"]').val();
			var token = $('#sharingToken').val();
			var owner = $('#save').data('owner');
			var name = $('#save').data('name');
			var isProtected = $('#save').data('protected') ? 1 : 0;
			OCA.Sharing.PublicApp._saveToOwnCloud(remote, token, owner, name, isProtected);
		});

		$('#save #save-button').click(function () {
			$(this).hide();
			$('.save-form').css('display', 'inline');
			$('#remote_address').focus();
		});

		// legacy
		window.FileList = this.fileList;
	},

	_onDirectoryChanged: function (e) {
		OC.Util.History.pushState({
			// arghhhh, why is this not called "dir" !?
			path: e.dir
		});
	},

	_onUrlChanged: function (params) {
		this.fileList.changeDirectory(params.path || params.dir, false, true);
	},

	_saveToOwnCloud: function(remote, token, owner, name, isProtected) {
		var location = window.location.protocol + '//' + window.location.host + OC.webroot;

		var url = remote + '/index.php/apps/files#' + 'remote=' + encodeURIComponent(location) // our location is the remote for the other server
			+ "&token=" + encodeURIComponent(token) + "&owner=" + encodeURIComponent(owner) + "&name=" + encodeURIComponent(name) + "&protected=" + isProtected;


		if (remote.indexOf('://') > 0) {
			OC.redirect(url);
		} else {
			// if no protocol is specified, we automatically detect it by testing https and http
			// this check needs to happen on the server due to the Content Security Policy directive
			$.get(OC.generateUrl('apps/files_sharing/testremote'), {remote: remote}).then(function (protocol) {
				if (protocol !== 'http' && protocol !== 'https') {
					OC.dialogs.alert(t('files_sharing', 'No ownCloud installation (7 or higher) found at {remote}', {remote: remote}),
						t('files_sharing', 'Invalid ownCloud url'));
				} else {
					OC.redirect(protocol + '://' + url);
				}
			});
		}
	}
};

$(document).ready(function () {
	var App = OCA.Sharing.PublicApp;
	// defer app init, to give a chance to plugins to register file actions
	_.defer(function () {
		App.initialize($('#preview'));
	});

	if (window.Files) {
		// HACK: for oc-dialogs previews that depends on Files:
		Files.lazyLoadPreview = function (path, mime, ready, width, height, etag) {
			return App.fileList.lazyLoadPreview({
				path: path,
				mime: mime,
				callback: ready,
				width: width,
				height: height,
				etag: etag
			});
		};
	}
});

