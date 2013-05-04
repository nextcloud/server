/**
 * Copyright (c) 2013, Sam Tuke <samtuke@owncloud.com>, Robin Appelman 
 * <icewind1991@gmail.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 */


$(document).ready(function(){
	// Trigger ajax on filetype blacklist change
	$('#encryption_blacklist').multiSelect({
		oncheck:blackListChange,
		onuncheck:blackListChange,
		createText:'...'
	});
	
	// Trigger ajax on recoveryAdmin status change
	$( 'input:radio[name="adminEnableRecovery"]' ).change( 
		function() {
			
			var recoveryStatus = $( this ).val();
			var recoveryPassword = $( '#recoveryPassword' ).val();
			
			if ( '' == recoveryPassword ) {
				
				// FIXME: add proper OC notification
				alert( 'You  must set a recovery account password first' );
				
			} else {
			
				$.post( 
					OC.filePath( 'files_encryption', 'ajax', 'adminrecovery.php' )
					, { adminEnableRecovery: recoveryStatus, recoveryPassword: recoveryPassword }
					,  function( data ) {
						alert( data );
					}
				);
			
			}
		}
	);
	
	function blackListChange(){
		var blackList=$( '#encryption_blacklist' ).val().join( ',' );
		OC.AppConfig.setValue( 'files_encryption', 'type_blacklist', blackList );
	}
})