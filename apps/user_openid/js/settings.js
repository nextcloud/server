$(document).ready(function(){
	$('#openidform input').blur(function(event){
		event.preventDefault();
		var post = $( "#openidform" ).serialize();
		$.post( 'ajax/openid.php', post, function(data){
			if( data.status == "success" ){
			}else{
				alert('error while setting OpenID');
			}
		});
	});
});
