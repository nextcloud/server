$(document).ready(function(){



	$('#somesetting').blur(function(event){
		event.preventDefault();
		var post = $( "#somesetting" ).serialize();
		$.post( OC.filePath('apptemplate','ajax','seturl.php') , post, function(data){ OC.msg.finishedSaving('#somesetting .msg', data);   });
	});



});


