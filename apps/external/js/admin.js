$(document).ready(function(){



	$('#s1name').blur(function(event){
		event.preventDefault();
		var post = $( "#s1name" ).serialize();
		$.post( OC.filePath('external','ajax','seturls.php') , post, function(data){ OC.msg.finishedSaving('#s1name .msg', data);   });
	});

	$('#s2name').blur(function(event){
		event.preventDefault();
		var post = $( "#s2name" ).serialize();
		$.post( OC.filePath('external','ajax','seturls.php') , post, function(data){ OC.msg.finishedSaving('#s2name .msg', data);   });
	});

	$('#s3name').blur(function(event){
		event.preventDefault();
		var post = $( "#s3name" ).serialize();
		$.post( OC.filePath('external','ajax','seturls.php') , post, function(data){ OC.msg.finishedSaving('#s3name .msg', data);   });
	});

	$('#s4name').blur(function(event){
		event.preventDefault();
		var post = $( "#s4name" ).serialize();
		$.post( OC.filePath('external','ajax','seturls.php') , post, function(data){ OC.msg.finishedSaving('#s4name .msg', data);   });
	});

	$('#s5name').blur(function(event){
		event.preventDefault();
		var post = $( "#s5name" ).serialize();
		$.post( OC.filePath('external','ajax','seturls.php') , post, function(data){ OC.msg.finishedSaving('#s5name .msg', data);   });
	});

	$('#s1url').blur(function(event){
		event.preventDefault();
		var post = $( "#s1url" ).serialize();
		$.post( OC.filePath('external','ajax','seturls.php') , post, function(data){ OC.msg.finishedSaving('#s1url .msg', data);   });
	});

	$('#s2url').blur(function(event){
		event.preventDefault();
		var post = $( "#s2url" ).serialize();
		$.post( OC.filePath('external','ajax','seturls.php') , post, function(data){ OC.msg.finishedSaving('#s2url .msg', data);   });
	});

	$('#s3url').blur(function(event){
		event.preventDefault();
		var post = $( "#s3url" ).serialize();
		$.post( OC.filePath('external','ajax','seturls.php') , post, function(data){ OC.msg.finishedSaving('#s3url .msg', data);   });
	});

	$('#s4url').blur(function(event){
		event.preventDefault();
		var post = $( "#s4url" ).serialize();
		$.post( OC.filePath('external','ajax','seturls.php') , post, function(data){ OC.msg.finishedSaving('#s4url .msg', data);   });
	});

	$('#s5url').blur(function(event){
		event.preventDefault();
		var post = $( "#s5url" ).serialize();
		$.post( OC.filePath('external','ajax','seturls.php') , post, function(data){ OC.msg.finishedSaving('#s5url .msg', data);   });
	});


});


