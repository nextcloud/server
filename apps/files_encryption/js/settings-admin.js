/**
 * Copyright (c) 2013, Sam Tuke <samtuke@owncloud.com>, Robin Appelman 
 * <icewind1991@gmail.com>
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

	$('input:password[name="recoveryPassword"]').keyup(function(event) {
		var recoveryPassword = $( '#recoveryPassword' ).val();
		var checkedButton = $('input:radio[name="adminEnableRecovery"]:checked').val();
		var uncheckedValue = (1+parseInt(checkedButton)) % 2;
		if (recoveryPassword != '' ) {
			$('input:radio[name="adminEnableRecovery"][value="'+uncheckedValue.toString()+'"]').removeAttr("disabled");
		} else {
			$('input:radio[name="adminEnableRecovery"][value="'+uncheckedValue.toString()+'"]').attr("disabled", "true");
		}
	});

	$( 'input:radio[name="adminEnableRecovery"]' ).change( 
		function() {
			var recoveryStatus = $( this ).val();
			var oldStatus = (1+parseInt(recoveryStatus)) % 2;
			var recoveryPassword = $( '#recoveryPassword' ).val();
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
							$('button:button[name="submitChangeRecoveryKey"]').attr("disabled", "true");
							$('input:password[name="changeRecoveryPassword"]').attr("disabled", "true");
							$('input:password[name="changeRecoveryPassword"]').val("");
						} else {
							$('input:password[name="changeRecoveryPassword"]').removeAttr("disabled");
						}
					}
				}
			);
		}
	);

	// change recovery password

	$('input:password[name="changeRecoveryPassword"]').keyup(function(event) {
		var oldRecoveryPassword = $('input:password[id="oldRecoveryPassword"]').val();
		var newRecoveryPassword = $('input:password[id="newRecoveryPassword"]').val();
		if (newRecoveryPassword != '' && oldRecoveryPassword != '' ) {
			$('button:button[name="submitChangeRecoveryKey"]').removeAttr("disabled");
		} else {
			$('button:button[name="submitChangeRecoveryKey"]').attr("disabled", "true");
		}
	});


	$('button:button[name="submitChangeRecoveryKey"]').click(function() {
		var oldRecoveryPassword = $('input:password[id="oldRecoveryPassword"]').val();
		var newRecoveryPassword = $('input:password[id="newRecoveryPassword"]').val();
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
