/**
 * Copyright (c) 2013, Sam Tuke <samtuke@owncloud.com>, Robin Appelman 
 * <icewind1991@gmail.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 */


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
			var recoveryPassword = $( '#recoveryPassword' ).val();
			$.post(
				OC.filePath( 'files_encryption', 'ajax', 'adminrecovery.php' )
				, { adminEnableRecovery: recoveryStatus, recoveryPassword: recoveryPassword }
				,  function( data ) {
					alert( data );
				}
			);
		}
	);
	
})