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

		var token = $('#sharingToken').val();

		// file list mode ?
		if ($el.find('#filestable').length) {
			var filesClient = new OC.Files.Client({
				host: OC.getHost(),
				port: OC.getPort(),
				userName: token,
				// note: password not be required, the endpoint
				// will recognize previous validation from the session
				root: OC.getRootPath() + '/public.php/webdav',
				useHTTPS: OC.getProtocol() === 'https'
			});

			this.fileList = new OCA.Files.FileList(
				$el,
				{
					id: 'files.public',
					scrollContainer: $('#content-wrapper'),
					dragOptions: dragOptions,
					folderDropOptions: folderDropOptions,
					fileActions: fileActions,
					detailsViewEnabled: false,
					filesClient: filesClient
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
		var bottomMargin = 350;
		var previewWidth = $(window).width();
		var previewHeight = $(window).height() - bottomMargin;
		previewHeight = Math.max(200, previewHeight);
		var params = {
			x: Math.ceil(previewWidth * window.devicePixelRatio),
			y: Math.ceil(previewHeight * window.devicePixelRatio),
			a: 'true',
			file: encodeURIComponent(this.initialDir + $('#filename').val()),
			t: token,
			scalingup: 0
		};

		var img = $('<img class="publicpreview" alt="">');
		img.css({
			'max-width': previewWidth,
			'max-height': previewHeight
		});

		var fileSize = parseInt($('#filesize').val(), 10);
		var maxGifSize = parseInt($('#maxSizeAnimateGif').val(), 10);

		if (mimetype === 'image/gif' &&
			(maxGifSize === -1 || fileSize <= (maxGifSize * 1024 * 1024))) {
			img.attr('src', $('#downloadURL').val());
			img.appendTo('#imgframe');
		} else if (mimetype.substr(0, mimetype.indexOf('/')) === 'text' && window.btoa) {
			// Undocumented Url to public WebDAV endpoint
			var url = parent.location.protocol + '//' + location.host + OC.linkTo('', 'public.php/webdav');
			$.ajax({
				url: url,
				headers: {
					Authorization: 'Basic ' + btoa(token + ':'),
					Range: 'bytes=0-1000'
				}
			}).then(function (data) {
				self._showTextPreview(data, previewHeight);
			});
		} else if ((previewSupported === 'true' && mimetype.substr(0, mimetype.indexOf('/')) !== 'video') ||
			mimetype.substr(0, mimetype.indexOf('/')) === 'image' &&
			mimetype !== 'image/svg+xml') {
			img.attr('src', OC.filePath('files_sharing', 'ajax', 'publicpreview.php') + '?' + OC.buildQueryString(params));
			img.appendTo('#imgframe');
		} else if (mimetype.substr(0, mimetype.indexOf('/')) !== 'video') {
			img.attr('src', OC.Util.replaceSVGIcon(mimetypeIcon));
			img.attr('width', 128);
			img.appendTo('#imgframe');
		}
		else if (previewSupported === 'true') {
			$('#imgframe > video').attr('poster', OC.filePath('files_sharing', 'ajax', 'publicpreview.php') + '?' + OC.buildQueryString(params));
		}

		if (this.fileList) {
			// TODO: move this to a separate PublicFileList class that extends OCA.Files.FileList (+ unit tests)
			this.fileList.getDownloadUrl = function (filename, dir, isDir) {
				var path = dir || this.getCurrentDirectory();
				if (_.isArray(filename)) {
					filename = JSON.stringify(filename);
				}
				var params = {
					path: path
				};
				if (filename) {
					params.files = filename;
				}
				return OC.generateUrl('/s/' + token + '/download') + '?' + OC.buildQueryString(params);
			};

			this.fileList.getAjaxUrl = function (action, params) {
				params = params || {};
				params.t = token;
				return OC.filePath('files_sharing', 'ajax', action + '.php') + '?' + OC.buildQueryString(params);
			};

			this.fileList.linkTo = function (dir) {
				return OC.generateUrl('/s/' + token + '', {dir: dir});
			};

			this.fileList.generatePreviewUrl = function (urlSpec) {
				urlSpec = urlSpec || {};
				if (!urlSpec.x) {
					urlSpec.x = 32;
				}
				if (!urlSpec.y) {
					urlSpec.y = 32;
				}
				urlSpec.x *= window.devicePixelRatio;
				urlSpec.y *= window.devicePixelRatio;
				urlSpec.x = Math.ceil(urlSpec.x);
				urlSpec.y = Math.ceil(urlSpec.y);
				urlSpec.t = $('#dirToken').val();
				return OC.generateUrl('/apps/files_sharing/ajax/publicpreview.php?') + $.param(urlSpec);
			};

			this.fileList.updateEmptyContent = function() {
				this.$el.find('#emptycontent .uploadmessage').text(
					t('files_sharing', 'You can upload into this folder')
				);
				OCA.Files.FileList.prototype.updateEmptyContent.apply(this, arguments);
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
			var ownerDisplayName = $('#save').data('owner-display-name');
			var name = $('#save').data('name');
			var isProtected = $('#save').data('protected') ? 1 : 0;
			OCA.Sharing.PublicApp._saveToOwnCloud(remote, token, owner, ownerDisplayName, name, isProtected);
		});

		$('#remote_address').on("keyup paste", function() {
			if ($(this).val() === '') {
				$('#save-button-confirm').prop('disabled', true);
			} else {
				$('#save-button-confirm').prop('disabled', false);
			}
		});

		$('#save #save-button').click(function () {
			$(this).hide();
			$('.save-form').css('display', 'inline');
			$('#remote_address').focus();
		});

		// legacy
		window.FileList = this.fileList;
	},

	_showTextPreview: function (data, previewHeight) {
		var textDiv = $('<div/>').addClass('text-preview');
		textDiv.text(data);
		textDiv.appendTo('#imgframe');
		var divHeight = textDiv.height();
		if (data.length > 999) {
			var ellipsis = $('<div/>').addClass('ellipsis');
			ellipsis.html('(&#133;)');
			ellipsis.appendTo('#imgframe');
		}
		if (divHeight > previewHeight) {
			textDiv.height(previewHeight);
		}
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

	_saveToOwnCloud: function (remote, token, owner, ownerDisplayName, name, isProtected) {
		var toggleLoading = function() {
			var iconClass = $('#save-button-confirm').attr('class');
			var loading = iconClass.indexOf('icon-loading-small') !== -1;
			if(loading) {
				$('#save-button-confirm')
				.removeClass("icon-loading-small")
				.addClass("icon-confirm");
				
			}
			else {
				$('#save-button-confirm')
				.removeClass("icon-confirm")
				.addClass("icon-loading-small");

			}
		};

		toggleLoading();
		var location = window.location.protocol + '//' + window.location.host + OC.webroot;
		
		if(remote.substr(-1) !== '/') {
			remote += '/'
		};

		var url = remote + 'index.php/apps/files#' + 'remote=' + encodeURIComponent(location) // our location is the remote for the other server
			+ "&token=" + encodeURIComponent(token) + "&owner=" + encodeURIComponent(owner) +"&ownerDisplayName=" + encodeURIComponent(ownerDisplayName) + "&name=" + encodeURIComponent(name) + "&protected=" + isProtected;


		if (remote.indexOf('://') > 0) {
			OC.redirect(url);
		} else {
			// if no protocol is specified, we automatically detect it by testing https and http
			// this check needs to happen on the server due to the Content Security Policy directive
			$.get(OC.generateUrl('apps/files_sharing/testremote'), {remote: remote}).then(function (protocol) {
				if (protocol !== 'http' && protocol !== 'https') {
					toggleLoading();
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
	// FIXME: replace with OC.Plugins.register()
	if (window.TESTING) {
		return;
	}

	var App = OCA.Sharing.PublicApp;
	// defer app init, to give a chance to plugins to register file actions
	_.defer(function () {
		App.initialize($('#preview'));
	});

	if (window.Files) {
		// HACK: for oc-dialogs previews that depends on Files:
		Files.generatePreviewUrl = function (urlSpec) {
			return App.fileList.generatePreviewUrl(urlSpec);
		};
	}
});

