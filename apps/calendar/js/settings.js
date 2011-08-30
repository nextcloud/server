$(document).ready(function(){
	$("#timezone").change( function(){
		// Serialize the data
		var post = $( "#timezone" ).serialize();
		// Ajax foo
		$.post( oc_webroot + '/apps/calendar/ajax/settimezone.php', post, function(data){
			if( data.status == "success" ){
			}
			else{
				$('#timezoneerror').html( data.data.message );
			}
		});
		return false;
	});
});
