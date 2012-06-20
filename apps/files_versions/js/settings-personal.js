// $(document).ready(function(){
// 	$('#versions').change( function(){
// 		OC.msg.startSaving('#calendar .msg')
// 		// Serialize the data
// 		var post = $( '#timezone' ).serialize();
// 		$.post( OC.filePath('calendar', 'ajax/settings', 'settimezone.php'), post, function(data){
// 			//OC.msg.finishedSaving('#calendar .msg', data);
// 		});
// 		return false;
// 	});
// });

$(document).ready(function(){
	//
	$('#expireAllBtn').click(function(){
		
		// Prevent page from reloading
		event.preventDefault();
		
		// Show loading gif
		$('.expireAllLoading').show();
	
		$.getJSON(
			OC.filePath('files_versions','ajax','expireAll.php'),
			function(result){
				if (result.status == 'success') {
					$('.expireAllLoading').hide();
					$('#expireAllBtn').html('Expiration successful');
				} else {
					
					// Cancel loading
					$('#expireAllBtn').html('Expiration failed');
					
					// Show Dialog
					OC.dialogs.alert(
						'Something went wrong, your files may not have been expired', 
						'An error has occurred', 
						function(){ 
							$('#expireAllBtn').html(t('files_versions', 'Expire all versions')+'<img style="display: none;" class="loading" src="'+OC.filePath('core','img','loading.gif')+'" />'); 
						}
						
					);
					
				}
 			}
			
		);

	});
	
});