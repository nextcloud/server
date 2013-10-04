/**
 * Copyright (c) 2013
 *  Sam Tuke <samtuke@owncloud.com>
 *  Robin Appelman <icewind1991@gmail.com>
 *  Bjoern Schiessle <schiessle@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 */

OC.msg={
	startSaving:function(selector){
		$(selector)
			.html( t('settings', 'Saving...') )
			.removeClass('success')
			.removeClass('error')
			.stop(true, true)
			.show();
	},
	finishedSaving:function(selector, data){
		if( data.status === "success" ){
			 $(selector).html( data.data.message )
				.addClass('success')
				.stop(true, true)
				.delay(3000)
				.fadeOut(900);
		}else{
			$(selector).html( data.data.message ).addClass('error');
		}
	}
};

$(document).ready(function(){
	// Trigger ajax on recoveryAdmin status change
	var enabledStatus = $('#adminEnableRecovery').val();

	$('input:password[name="encryptionRecoveryPassword"]').keyup(function(event) {
		var recoveryPassword = $( '#encryptionRecoveryPassword' ).val();
		var recoveryPasswordRepeated = $( '#repeatEncryptionRecoveryPassword' ).val();
		var checkedButton = $('input:radio[name="adminEnableRecovery"]:checked').val();
		var uncheckedValue = (1+parseInt(checkedButton)) % 2;
		if (recoveryPassword !== '' && recoveryPassword === recoveryPasswordRepeated) {
			$('input:radio[name="adminEnableRecovery"][value="'+uncheckedValue.toString()+'"]').removeAttr("disabled");
		} else {
			$('input:radio[name="adminEnableRecovery"][value="'+uncheckedValue.toString()+'"]').attr("disabled", "true");
		}
	});

	$( 'input:radio[name="adminEnableRecovery"]' ).change(
		function() {
			var recoveryStatus = $( this ).val();
			var oldStatus = (1+parseInt(recoveryStatus)) % 2;
			var recoveryPassword = $( '#encryptionRecoveryPassword' ).val();
			$.post(
				OC.filePath( 'files_encryption', 'ajax', 'adminrecovery.php' )
				, { adminEnableRecovery: recoveryStatus, recoveryPassword: recoveryPassword }
				,  function( result ) {
					if (result.status === "error") {
						OC.Notification.show(t('admin', result.data.message));
						$('input:radio[name="adminEnableRecovery"][value="'+oldStatus.toString()+'"]').attr("checked", "true");
					} else {
						OC.Notification.hide();
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

	$('input:password[name="changeRecoveryPassword"]').keyup(function(event) {
		var oldRecoveryPassword = $('#oldEncryptionRecoveryPassword').val();
		var newRecoveryPassword = $('#newEncryptionRecoveryPassword').val();
		var newRecoveryPasswordRepeated = $('#repeatedNewEncryptionRecoveryPassword').val();

		if (newRecoveryPassword !== '' && oldRecoveryPassword !== '' && newRecoveryPassword === newRecoveryPasswordRepeated) {
			$('button:button[name="submitChangeRecoveryKey"]').removeAttr("disabled");
		} else {
			$('button:button[name="submitChangeRecoveryKey"]').attr("disabled", "true");
		}
	});


	$('button:button[name="submitChangeRecoveryKey"]').click(function() {
		var oldRecoveryPassword = $('#oldEncryptionRecoveryPassword').val();
		var newRecoveryPassword = $('#newEncryptionRecoveryPassword').val();
		OC.msg.startSaving('#encryption .msg');
		$.post(
		OC.filePath( 'files_encryption', 'ajax', 'changeRecoveryPassword.php' )
			, { oldPassword: oldRecoveryPassword, newPassword: newRecoveryPassword }
			,  function( data ) {
				if (data.status == "error") {
					OC.msg.finishedSaving('#encryption .msg', data);
				} else {
					OC.msg.finishedSaving('#encryption .msg', data);
				}
			}
		);
	});

});
