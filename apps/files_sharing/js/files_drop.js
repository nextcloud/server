/*
 * Copyright (c) 2016 Lukas Reschke <lukas@statuscode.ch>
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
 *
 */

(function ($) {
	var TEMPLATE =
		'<li data-toggle="tooltip" title="{{name}}" data-name="{{name}}">' +
		'{{#if isUploading}}' +
		'<span class="icon-loading-small"></span> {{name}}' +
		'{{else}}' +
		'<img src="' + OC.imagePath('core', 'actions/error.svg') + '"/> {{name}}' +
		'{{/if}}' +
		'</li>';
	var Drop = {
		/** @type {Function} **/
		_template: undefined,

		initialize: function () {

			var filesClient = new OC.Files.Client({
				host: OC.getHost(),
				port: OC.getPort(),
				userName: $('#sharingToken').val(),
				// note: password not be required, the endpoint
				// will recognize previous validation from the session
				root: OC.getRootPath() + '/public.php/webdav',
				useHTTPS: OC.getProtocol() === 'https'
			});

			$(document).bind('drop dragover', function (e) {
				// Prevent the default browser drop action:
				e.preventDefault();
			});
			var output = this.template();
			$('#public-upload').fileupload({
				type: 'PUT',
				dropZone: $('#public-upload'),
				sequentialUploads: true,
				add: function(e, data) {
					var errors = [];

					var name = data.files[0].name;

					var base = OC.getProtocol() + '://' + OC.getHost();
					data.url = base + OC.getRootPath() + '/public.php/webdav/' + encodeURI(name);

					data.multipart = false;

					if (!data.headers) {
						data.headers = {};
					}

					var userName = filesClient.getUserName();
					var password = filesClient.getPassword();
					if (userName) {
						// copy username/password from DAV client
						data.headers['Authorization'] =
							'Basic ' + btoa(userName + ':' + (password || ''));
					}

					$('#drop-upload-done-indicator').addClass('hidden');
					$('#drop-upload-progress-indicator').removeClass('hidden');
					_.each(data['files'], function(file) {
						$('#public-upload ul').append(output({isUploading: true, name: escapeHTML(file.name)}));
						$('[data-toggle="tooltip"]').tooltip();
						data.submit();
					});

					return true;
				},
				done: function(e, data) {
					// Created
					if (data.jqXHR.status === 201) {
						var mimeTypeUrl = OC.MimeType.getIconUrl(data.files[0].type);
						$('#public-upload ul li[data-name="' + escapeHTML(data.files[0].name) + '"]').html('<img src="' + escapeHTML(mimeTypeUrl) + '"/> ' + escapeHTML(data.files[0].name));
						$('[data-toggle="tooltip"]').tooltip();
					} else {
						var name = data.files[0].name;
						OC.Notification.showTemporary(OC.L10N.translate('files_sharing', 'Could not upload "{filename}"', {filename: name}));
						$('#public-upload ul li[data-name="' + escapeHTML(name) + '"]').html(output({isUploading: false, name: escapeHTML(name)}));
						$('[data-toggle="tooltip"]').tooltip();
					}
				},
				progressall: function (e, data) {
					var progress = parseInt(data.loaded / data.total * 100, 10);
					if(progress === 100) {
						$('#drop-upload-done-indicator').removeClass('hidden');
						$('#drop-upload-progress-indicator').addClass('hidden');
					} else {
						$('#drop-upload-done-indicator').addClass('hidden');
						$('#drop-upload-progress-indicator').removeClass('hidden');
					}
				}
			});
			$('#public-upload .button.icon-upload').click(function(e) {
				e.preventDefault();
				$('#public-upload #emptycontent input').focus().trigger('click');
			});
		},

		/**
		 * @returns {Function} from Handlebars
		 * @private
		 */
		template: function () {
			if (!this._template) {
				this._template = Handlebars.compile(TEMPLATE);
			}
			return this._template;
		}
	};

	$(document).ready(function() {
		if($('#upload-only-interface').val() === "1" && oc_config.enable_avatars) {
			$('.avatardiv').avatar($('#sharingUserId').val(), 128, true);
		}

		OCA.Files_Sharing_Drop = Drop;
		OCA.Files_Sharing_Drop.initialize();
	});


})(jQuery);

