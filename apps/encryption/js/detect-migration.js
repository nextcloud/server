/**
 * Copyright (c) 2013
 *  Bjoern Schiessle <schiessle@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 */


$(document).ready(function(){
	$('form[name="login"]').on('submit', function() {
		var user = $('#user').val();
		var password = $('#password').val();
		$.ajax({
			type: 'POST',
			url: OC.linkTo('files_encryption', 'ajax/getMigrationStatus.php'),
			dataType: 'json',
			data: {user: user, password: password},
			async: false,
			success: function(response) {
				if (response.data.migrationStatus === OC.Encryption.MIGRATION_OPEN) {
					var message = t('files_encryption', 'Initial encryption started... This can take some time. Please wait.');
					$('#messageText').text(message);
					$('#message').removeClass('hidden').addClass('update');
				} else if (response.data.migrationStatus === OC.Encryption.MIGRATION_IN_PROGRESS) {
					var message = t('files_encryption', 'Initial encryption running... Please try again later.');
					$('#messageText').text(message);
					$('#message').removeClass('hidden').addClass('update');
				}
			}
		});
	});

});
