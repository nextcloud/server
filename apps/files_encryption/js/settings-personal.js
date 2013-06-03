/**
 * Copyright (c) 2013, Sam Tuke <samtuke@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 */

$(document).ready(function(){
	// Trigger ajax on recoveryAdmin status change
	$( 'input:radio[name="userEnableRecovery"]' ).change( 
		function() {
			
			// Hide feedback messages in case they're already visible
			$('#recoveryEnabledSuccess').hide();
			$('#recoveryEnabledError').hide();
			
			var recoveryStatus = $( this ).val();
			
			$.post( 
				OC.filePath( 'files_encryption', 'ajax', 'userrecovery.php' )
				, { userEnableRecovery: recoveryStatus }
				,  function( data ) {
					if ( data.status == "success" ) {
						$('#recoveryEnabledSuccess').show();
					} else {
						$('#recoveryEnabledError').show();
					}
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
});