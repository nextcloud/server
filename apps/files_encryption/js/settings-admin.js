/**
 * Copyright (c) 2013
 *  Sam Tuke <samtuke@owncloud.com>
 *  Robin Appelman <icewind1991@gmail.com>
 *  Bjoern Schiessle <schiessle@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 */

$(document).ready(function(){

	$( 'input:radio[name="adminEnableRecovery"]' ).change(
		function() {
			var recoveryStatus = $( this ).val();
			var oldStatus = (1+parseInt(recoveryStatus)) % 2;
			var recoveryPassword = $( '#encryptionRecoveryPassword' ).val();
			var confirmPassword = $( '#repeatEncryptionRecoveryPassword' ).val();
			OC.msg.startSaving('#encryptionSetRecoveryKey .msg');
			$.post(
				OC.filePath( 'files_encryption', 'ajax', 'adminrecovery.php' )
				, { adminEnableRecovery: recoveryStatus, recoveryPassword: recoveryPassword, confirmPassword: confirmPassword }
				,  function( result ) {
					OC.msg.finishedSaving('#encryptionSetRecoveryKey .msg', result);
					if (result.status === "error") {
						$('input:radio[name="adminEnableRecovery"][value="'+oldStatus.toString()+'"]').attr("checked", "true");
					} else {
						if (recoveryStatus === "0") {
							$('p[name="changeRecoveryPasswordBlock"]').addClass("hidden");
						} else {
							$('input:password[name="changeRecoveryPassword"]').val("");
							$('p[name="changeRecoveryPasswordBlock"]').removeClass("hidden");
						}
					}
				}
			);
		}
	);

	// change recovery password

	$('button:button[name="submitChangeRecoveryKey"]').click(function() {
		var oldRecoveryPassword = $('#oldEncryptionRecoveryPassword').val();
		var newRecoveryPassword = $('#newEncryptionRecoveryPassword').val();
		var confirmNewPassword = $('#repeatedNewEncryptionRecoveryPassword').val();
		OC.msg.startSaving('#encryptionChangeRecoveryKey .msg');
		$.post(
		OC.filePath( 'files_encryption', 'ajax', 'changeRecoveryPassword.php' )
			, { oldPassword: oldRecoveryPassword, newPassword: newRecoveryPassword, confirmPassword: confirmNewPassword }
			,  function( data ) {
					OC.msg.finishedSaving('#encryptionChangeRecoveryKey .msg', data);
				}
		);
	});

});
