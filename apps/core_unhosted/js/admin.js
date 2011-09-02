$(document).ready(function() {
	$("button.revoke").live('click', function( event ) {
		event.preventDefault();
		var token=$(this).attr('data-token');
		var data="token="+token;
		$.ajax({
			type: 'GET',
			url: 'ajax/deletetoken.php',
			cache: false,
			data: data,
			success: function(){
				$('#'+token).remove();
			}
		});
	});
});
