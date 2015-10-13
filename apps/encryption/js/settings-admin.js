/**
 * Copyright (c) 2013
 *  Sam Tuke <samtuke@owncloud.com>
 *  Robin Appelman <icewind1991@gmail.com>
 *  Bjoern Schiessle <schiessle@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 */

$(document).ready(function () {
	
	$('input:button[name="enableRecoveryKey"]').click(function () {

		var recoveryStatus = $(this).attr('status');
		var newRecoveryStatus = (1 + parseInt(recoveryStatus)) % 2;
		var buttonValue = $(this).attr('value');

		var recoveryPassword = $('#encryptionRecoveryPassword').val();
		var confirmPassword = $('#repeatEncryptionRecoveryPassword').val();
		OC.msg.startSaving('#encryptionSetRecoveryKey .msg');
		$.post(
			OC.generateUrl('/apps/encryption/ajax/adminRecovery'),
			{
				adminEnableRecovery: newRecoveryStatus,
				recoveryPassword: recoveryPassword,
				confirmPassword: confirmPassword
			}
		).done(function (data) {
				OC.msg.finishedSuccess('#encryptionSetRecoveryKey .msg', data.data.message);

				if (newRecoveryStatus === 0) {
					$('p[name="changeRecoveryPasswordBlock"]').addClass("hidden");
					$('input:button[name="enableRecoveryKey"]').attr('value', 'Enable recovery key');
					$('input:button[name="enableRecoveryKey"]').attr('status', '0');
				} else {
					$('input:password[name="changeRecoveryPassword"]').val("");
					$('p[name="changeRecoveryPasswordBlock"]').removeClass("hidden");
					$('input:button[name="enableRecoveryKey"]').attr('value', 'Disable recovery key');
					$('input:button[name="enableRecoveryKey"]').attr('status', '1');
				}
			})
			.fail(function (jqXHR) {
				$('input:button[name="enableRecoveryKey"]').attr('value', buttonValue);
				$('input:button[name="enableRecoveryKey"]').attr('status', recoveryStatus);
				OC.msg.finishedError('#encryptionSetRecoveryKey .msg', JSON.parse(jqXHR.responseText).data.message);
			});


	});

	$("#repeatEncryptionRecoveryPassword").keyup(function (event) {
		if (event.keyCode == 13) {
			$("#enableRecoveryKey").click();
		}
	});

	// change recovery password

	$('button:button[name="submitChangeRecoveryKey"]').click(function () {
		var oldRecoveryPassword = $('#oldEncryptionRecoveryPassword').val();
		var newRecoveryPassword = $('#newEncryptionRecoveryPassword').val();
		var confirmNewPassword = $('#repeatedNewEncryptionRecoveryPassword').val();
		OC.msg.startSaving('#encryptionChangeRecoveryKey .msg');
		$.post(
			OC.generateUrl('/apps/encryption/ajax/changeRecoveryPassword'),
			{
				oldPassword: oldRecoveryPassword,
				newPassword: newRecoveryPassword,
				confirmPassword: confirmNewPassword
			}
		).done(function (data) {
				OC.msg.finishedSuccess('#encryptionChangeRecoveryKey .msg', data.data.message);
			})
			.fail(function (jqXHR) {
				OC.msg.finishedError('#encryptionChangeRecoveryKey .msg', JSON.parse(jqXHR.responseText).data.message);
			});
	});

	$('#encryptHomeStorage').change(function() {
		$.post(
			OC.generateUrl('/apps/encryption/ajax/setEncryptHomeStorage'),
			{
				encryptHomeStorage: this.checked
			}
		);
	});

});
