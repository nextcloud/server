$(document).ready(function(){
	$('#openidform #identity').blur(function(event){
		event.preventDefault();
		var post = $( "#openidform" ).serialize();
		$.post( 'ajax/openid.php', post, function(data){
			if( data.status == "success" ){
			}else{
				alert('error while setting OpenID');
			}
		});
	});

	// reset value when edited, workaround because of .select() not working with disabled inputs
	$('#openid').focus(function(event){
		openidValue = $('#openid').val();
	});
	$('#openid').blur(function(event){
		$('#openid').val(openidValue);
	});
});
