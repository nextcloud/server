/**
 * Copyright (c) 2011, Robin Appelman <icewind1991@gmail.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 */

$(document).ready(function(){
	$("#passwordbutton").click( function(){
		// Serialize the data
		var post = $( "#passwordform" ).serialize();
		$('#passwordchanged').hide();
		$('#passworderror').hide();
		// Ajax foo
		$.post( 'ajax/changepassword.php', post, function(data){
			if( data.status == "success" ){
				$('#pass1').val('');
				$('#pass2').val('');
				$('#passwordchanged').show();
			}
			else{
				$('#passworderror').html( data.data.message );
				$('#passworderror').show();
			}
		});
		return false;
	});
	
	$("#languageinput").change( function(){
		// Serialize the data
		var post = $( "#languageinput" ).serialize();
		// Ajax foo
		$.post( 'ajax/setlanguage.php', post, function(data){
			if( data.status == "success" ){
				location.reload();
			}
			else{
				$('#passworderror').html( data.data.message );
			}
		});
		return false;
	});

	// reset value when edited, workaround because of .select() not working with disabled inputs
	$('#webdav').focus(function(event){
		openidValue = $('#webdav').val();
	});
	$('#webdav').blur(function(event){
		$('#webdav').val(openidValue);
	});
} );
