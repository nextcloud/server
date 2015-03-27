/**
 * Copyright (c) 2013, Sam Tuke <samtuke@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 */

function updatePrivateKeyPasswd() {
	var oldPrivateKeyPassword = $('input:password[id="oldPrivateKeyPassword"]').val();
	var newPrivateKeyPassword = $('input:password[id="newPrivateKeyPassword"]').val();
	OC.msg.startSaving('#encryption .msg');
	$.post(
	OC.filePath( 'files_encryption', 'ajax', 'updatePrivateKeyPassword.php' )
		, { oldPassword: oldPrivateKeyPassword, newPassword: newPrivateKeyPassword }
		,  function( data ) {
			if (data.status === "error") {
				OC.msg.finishedSaving('#encryption .msg', data);
			} else {
				OC.msg.finishedSaving('#encryption .msg', data);
			}
		}
	);
}

$(document).ready(function(){

	// Trigger ajax on recoveryAdmin status change
	$( 'input:radio[name="userEnableRecovery"]' ).change(
		function() {
			var recoveryStatus = $( this ).val();
			OC.msg.startAction('#userEnableRecovery .msg', 'Updating recovery keys. This can take some time...');
			$.post(
				OC.filePath( 'files_encryption', 'ajax', 'userrecovery.php' )
				, { userEnableRecovery: recoveryStatus }
				,  function( data ) {
					OC.msg.finishedAction('#userEnableRecovery .msg', data);
				}
			);
			// Ensure page is not reloaded on form submit
			return false;
		}
	);

	$("#encryptAll").click(
		function(){

			// Hide feedback messages in case they're already visible
			$('#encryptAllSuccess').hide();
			$('#encryptAllError').hide();

			var userPassword = $( '#userPassword' ).val();
			var encryptAll = $( '#encryptAll' ).val();

			$.post(
				OC.filePath( 'files_encryption', 'ajax', 'encryptall.php' )
				, { encryptAll: encryptAll, userPassword: userPassword }
				,  function( data ) {
					if ( data.status == "success" ) {
						$('#encryptAllSuccess').show();
					} else {
						$('#encryptAllError').show();
					}
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
				updatePrivateKeyPasswd();
			}
		} else {
			$('button:button[name="submitChangePrivateKeyPassword"]').attr("disabled", "true");
		}
	});

	$('button:button[name="submitChangePrivateKeyPassword"]').click(function() {
		updatePrivateKeyPasswd();
	});

});
