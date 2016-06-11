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
	var Drop = {
		initialize: function () {
			$(document).bind('drop dragover', function (e) {
				// Prevent the default browser drop action:
				e.preventDefault();
			});
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
							$('#public-upload ul').append('<li data-toggle="tooltip" title="'+escapeHTML(file.name)+'" data-name="'+escapeHTML(file.name)+'"><span class="icon-loading-small"></span> '+escapeHTML(file.name)+'</li>');
							$('[data-toggle="tooltip"]').tooltip();
							data.submit();
						} else {
							OC.Notification.showTemporary(OC.L10N.translate('files_sharing', 'Could not upload "{filename}"', {filename: file.name}));
							$('#public-upload ul').append('<li data-toggle="tooltip" title="'+escapeHTML(file.name)+'" data-name="'+escapeHTML(file.name)+'"><img src="'+OC.imagePath('core', 'actions/error.svg')+'"/> '+escapeHTML(file.name)+'</li>');
							$('[data-toggle="tooltip"]').tooltip();
						}
					});
				},
				success: function (response) {
					if(response.status !== 'error') {
						var mimeTypeUrl = OC.MimeType.getIconUrl(response['mimetype']);
						$('#public-upload ul li[data-name="' + escapeHTML(response['filename']) + '"]').html('<img src="' + escapeHTML(mimeTypeUrl) + '"/> ' + escapeHTML(response['filename']));
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
		}
	};

	$(document).ready(function() {
		if($('#upload-only-interface').val() === "1") {
			$('.avatardiv').avatar($('#sharingUserId').val(), 128, true);
		}

		OCA.Files_Sharing_Drop = Drop;
		OCA.Files_Sharing_Drop.initialize();
	});


})(jQuery);
