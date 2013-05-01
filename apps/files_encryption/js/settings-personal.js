/**
 * Copyright (c) 2013, Sam Tuke <samtuke@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 */

$(document).ready(function(){
	// Trigger ajax on recoveryAdmin status change
	$( 'input:radio[name="userEnableRecovery"]' ).change( 
		function() {
			
			var recoveryStatus = $( this ).val();
			
			$.post( 
				OC.filePath( 'files_encryption', 'ajax', 'userrecovery.php' )
				, { userEnableRecovery: recoveryStatus }
				,  function( data ) {
					alert( data );
				}
			);
		}
	);
})