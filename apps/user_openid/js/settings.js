$(document).ready(function(){
	$('#openidform #identity').blur(function(event){
		event.preventDefault();
		OC.msg.startSaving('#openidform .msg');
		var post = $( "#openidform" ).serialize();
		$.post( 'ajax/openid.php', post, function(data){
			OC.msg.finishedSaving('#openidform .msg', data);
		});
	});
});
