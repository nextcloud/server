/**
 * Copyright (c) 2013
 *  Bjoern Schiessle <schiessle@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 */


$(document).ready(function(){
	$('form[name="login"]').on('submit', function(ev) {
		var user = $('#user').val();
		var password = $('#password').val();
		$.ajax({
			type: 'POST',
			url: OC.linkTo('files_encryption', 'ajax/getMigrationStatus.php'),
			dataType: 'json',
			data: {user: user, password: password},
			async: false,
			success: function(response) {
				if (response.data.migrationCompleted === false) {
					var message = t('files_encryption', 'Initial encryption started... This can take some time. Please wait.');
					$('p[name="message"]').html('<img src="' + OC.imagePath('core', 'loading-dark-small.gif') + '"/>  ' + message);
					$('p[name="message"]').removeClass('hidden').addClass('info');
				}
			}
		});
	});

});
