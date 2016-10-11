/*
 * Copyright (c) 2014
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
 *
 */

(function() {
	if (!OCA.Files) {
		/**
		 * Namespace for the files app
		 * @namespace OCA.Files
		 */
		OCA.Files = {};
	}

	/**
	 * @namespace OCA.Files.Admin
	 */
	OCA.Files.Admin = {
		initialize: function() {
			$('#submitMaxUpload').on('click', _.bind(this._onClickSubmitMaxUpload, this));
		},

		_onClickSubmitMaxUpload: function () {
			OC.msg.startSaving('#maxUploadSizeSettingsMsg');

			var request = $.ajax({
				url: OC.generateUrl('/apps/files/settings/maxUpload'),
				type: 'POST',
				data: {
					maxUploadSize: $('#maxUploadSize').val()
				}
			});

			request.done(function (data) {
				$('#maxUploadSize').val(data.maxUploadSize);
				OC.msg.finishedSuccess('#maxUploadSizeSettingsMsg', 'Saved');
			});

			request.fail(function () {
				OC.msg.finishedError('#maxUploadSizeSettingsMsg', 'Error');
			});
		}
	}
})();

function switchPublicFolder() {
	var publicEnable = $('#publicEnable').is(':checked');
	// find all radiobuttons of that group
	var sharingaimGroup = $('input:radio[name=sharingaim]');
	$.each(sharingaimGroup, function(index, sharingaimItem) {
		// set all buttons to the correct state
		sharingaimItem.disabled = !publicEnable;
	});
}

$(document).ready(function() {
	OCA.Files.Admin.initialize();

	// Execute the function after loading DOM tree
	switchPublicFolder();
	$('#publicEnable').click(function() {
		// To get rid of onClick()
		switchPublicFolder();
	});
});
