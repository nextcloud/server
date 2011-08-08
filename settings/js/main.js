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
			}
			else{
				$('#passworderror').html( data.data.message );
			}
		});
		return false;
	});
} );
