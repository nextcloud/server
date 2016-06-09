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
					_.each(data['files'], function(file) {
						$('#public-upload ul').append('<li data-name="'+escapeHTML(file.name)+'"><span class="icon-loading-small"></span> '+escapeHTML(file.name)+'</li>');
					});
					data.submit();
				},
				success: function (response) {
					var mimeTypeUrl = OC.MimeType.getIconUrl(response['mimetype']);
					$('#public-upload ul li[data-name="'+escapeHTML(response['filename'])+'"]').html('<img src="'+escapeHTML(mimeTypeUrl)+'"/> '+escapeHTML(response['filename']));
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

