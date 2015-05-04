/**
 * Copyright (c) 2013
 *  Sam Tuke <samtuke@owncloud.com>
 *  Robin Appelman <icewind1991@gmail.com>
 *  Bjoern Schiessle <schiessle@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 */

$(document).ready(function () {

	$('input:radio[name="adminEnableRecovery"]').change(
		function () {
			var recoveryStatus = $(this).val();
			var oldStatus = (1 + parseInt(recoveryStatus)) % 2;
			var recoveryPassword = $('#encryptionRecoveryPassword').val();
			var confirmPassword = $('#repeatEncryptionRecoveryPassword').val();
			OC.msg.startSaving('#encryptionSetRecoveryKey .msg');
			$.post(
				OC.generateUrl('/apps/encryption/ajax/adminRecovery'),
				{
					adminEnableRecovery: recoveryStatus,
					recoveryPassword: recoveryPassword,
					confirmPassword: confirmPassword
				}
			).done(function (data) {
					OC.msg.finishedSuccess('#encryptionSetRecoveryKey .msg', data.data.message);

					if (recoveryStatus === "0") {
						$('p[name="changeRecoveryPasswordBlock"]').addClass("hidden");
					} else {
						$('input:password[name="changeRecoveryPassword"]').val("");
						$('p[name="changeRecoveryPasswordBlock"]').removeClass("hidden");
					}
				})
				.fail(function (jqXHR) {
					$('input:radio[name="adminEnableRecovery"][value="' + oldStatus.toString() + '"]').attr("checked", "true");
					OC.msg.finishedError('#encryptionSetRecoveryKey .msg', JSON.parse(jqXHR.responseText).data.message);
				});
		}
	);

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

});
