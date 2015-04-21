/**
 * Copyright (c) 2013, Sam Tuke <samtuke@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 */

if (!OC.Encryption) {
	OC.Encryption = {};
}

OC.Encryption = {
	updatePrivateKeyPassword: function() {
		var oldPrivateKeyPassword = $('input:password[id="oldPrivateKeyPassword"]').val();
		var newPrivateKeyPassword = $('input:password[id="newPrivateKeyPassword"]').val();
		OC.msg.startSaving('#encryption .msg');
		$.post(
			OC.generateUrl('/apps/encryption/ajax/updatePrivateKeyPassword'),
			{oldPassword: oldPrivateKeyPassword, newPassword: newPrivateKeyPassword}
		).success(function (response) {
			OC.msg.finishedSuccess('#encryption .msg', response.message);
		}).fail(function (response) {
			OC.msg.finishedError('#encryption .msg', response.responseJSON.message);
		});
	}
};

$(document).ready(function(){

	// Trigger ajax on recoveryAdmin status change
	$( 'input:radio[name="userEnableRecovery"]' ).change(
		function() {
			var recoveryStatus = $( this ).val();
			OC.msg.startAction('#userEnableRecovery .msg', 'Updating recovery keys. This can take some time...');
			$.post(
					OC.generateUrl('/apps/encryption/ajax/userSetRecovery'),
				{ userEnableRecovery: recoveryStatus },
				function( data ) {
					OC.msg.finishedAction('#userEnableRecovery .msg', data);
				}
			);
			// Ensure page is not reloaded on form submit
			return false;
		}
	);

	// update private key password

	$('input:password[name="changePrivateKeyPassword"]').keyup(function(event) {
		var oldPrivateKeyPassword = $('input:password[id="oldPrivateKeyPassword"]').val();
		var newPrivateKeyPassword = $('input:password[id="newPrivateKeyPassword"]').val();
		if (newPrivateKeyPassword !== '' && oldPrivateKeyPassword !== '' ) {
			$('button:button[name="submitChangePrivateKeyPassword"]').removeAttr("disabled");
			if(event.which === 13) {
				OC.Encryption.updatePrivateKeyPassword();
			}
		} else {
			$('button:button[name="submitChangePrivateKeyPassword"]').attr("disabled", "true");
		}
	});

	$('button:button[name="submitChangePrivateKeyPassword"]').click(function() {
		OC.Encryption.updatePrivateKeyPassword();
	});

});
