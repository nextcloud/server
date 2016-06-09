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
			$('#publicUploadDiv').fileupload({
				url: OC.linkTo('files', 'ajax/upload.php'),
				dataType: 'json',
				//maxFileSize: fileUploadContainer.data('maxupload'),
				messages: {
					maxFileSize: t('files_sharing', 'File is bigger than allowed.')
				},
				dropZone: $('#publicUploadDiv'),
				formData: {
					dirToken: $('#sharingToken').val()
				}
			});

		}
	};

	$(document).ready(function() {
		if($('#uploadOnlyInterface').val() === "1") {
			$('.avatardiv').avatar($('#sharingUserId').val(), 128, true);
		}

		OCA.Files_Sharing_Drop = Drop;
		OCA.Files_Sharing_Drop.initialize();
	});


})(jQuery);

