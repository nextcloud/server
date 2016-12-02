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
			$(document).bind('drop dragover', function (e) {
				// Prevent the default browser drop action:
				e.preventDefault();
			});
			var output = this.template();
			$('#public-upload').fileupload({
				url: OC.linkTo('files', 'ajax/upload.php'),
				dataType: 'json',
				dropZone: $('#public-upload'),
				formData: {
					dirToken: $('#sharingToken').val()
				},
				add: function(e, data) {
					var errors = [];
					if(data.files[0]['size'] && data.files[0]['size'] > $('#maxFilesizeUpload').val()) {
						errors.push('File is too big');
					}

					$('#drop-upload-done-indicator').addClass('hidden');
					$('#drop-upload-progress-indicator').removeClass('hidden');
					_.each(data['files'], function(file) {
						if(errors.length === 0) {
							$('#public-upload ul').append(output({isUploading: true, name: escapeHTML(file.name)}));
							$('[data-toggle="tooltip"]').tooltip();
							data.submit();
						} else {
							OC.Notification.showTemporary(OC.L10N.translate('files_sharing', 'Could not upload "{filename}"', {filename: file.name}));
							$('#public-upload ul').append(output({isUploading: false, name: escapeHTML(file.name)}));
							$('[data-toggle="tooltip"]').tooltip();
						}
					});
				},
				success: function (response) {
					if(response.status !== 'error') {
						var mimeTypeUrl = OC.MimeType.getIconUrl(response['mimetype']);
						$('#public-upload ul li[data-name="' + escapeHTML(response['filename']) + '"]').html('<img src="' + escapeHTML(mimeTypeUrl) + '"/> ' + escapeHTML(response['filename']));
						$('[data-toggle="tooltip"]').tooltip();
					} else {
						var name = response[0]['data']['filename'];
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

