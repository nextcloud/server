/**
 * Copyright (c) 2013, Sam Tuke <samtuke@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 */

OC.Encryption = _.extend(OC.Encryption || {}, {
	updatePrivateKeyPassword: function () {
		var oldPrivateKeyPassword = $('input:password[id="oldPrivateKeyPassword"]').val();
		var newPrivateKeyPassword = $('input:password[id="newPrivateKeyPassword"]').val();
		OC.msg.startSaving('#ocDefaultEncryptionModule .msg');
		$.post(
			OC.generateUrl('/apps/encryption/ajax/updatePrivateKeyPassword'),
			{
				oldPassword: oldPrivateKeyPassword,
				newPassword: newPrivateKeyPassword
			}
		).done(function (data) {
				OC.msg.finishedSuccess('#ocDefaultEncryptionModule .msg', data.message);
			})
			.fail(function (jqXHR) {
				OC.msg.finishedError('#ocDefaultEncryptionModule .msg', JSON.parse(jqXHR.responseText).message);
			});
	}
});

$(document).ready(function () {

	// Trigger ajax on recoveryAdmin status change
	$('input:radio[name="userEnableRecovery"]').change(
		function () {
			var recoveryStatus = $(this).val();
			OC.msg.startAction('#userEnableRecovery .msg', 'Updating recovery keys. This can take some time...');
			$.post(
				OC.generateUrl('/apps/encryption/ajax/userSetRecovery'),
				{
					userEnableRecovery: recoveryStatus
				}
			).done(function (data) {
					OC.msg.finishedSuccess('#userEnableRecovery .msg', data.data.message);
				})
				.fail(function (jqXHR) {
					OC.msg.finishedError('#userEnableRecovery .msg', JSON.parse(jqXHR.responseText).data.message);
				});
			// Ensure page is not reloaded on form submit
			return false;
		}
	);

	// update private key password

	$('input:password[name="changePrivateKeyPassword"]').keyup(function (event) {
		var oldPrivateKeyPassword = $('input:password[id="oldPrivateKeyPassword"]').val();
		var newPrivateKeyPassword = $('input:password[id="newPrivateKeyPassword"]').val();
		if (newPrivateKeyPassword !== '' && oldPrivateKeyPassword !== '') {
			$('button:button[name="submitChangePrivateKeyPassword"]').removeAttr("disabled");
			if (event.which === 13) {
				OC.Encryption.updatePrivateKeyPassword();
			}
		} else {
			$('button:button[name="submitChangePrivateKeyPassword"]').attr("disabled", "true");
		}
	});

	$('button:button[name="submitChangePrivateKeyPassword"]').click(function () {
		OC.Encryption.updatePrivateKeyPassword();
	});

});
