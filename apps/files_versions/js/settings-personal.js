// TODO: allow the button to be clicked only once

$( document ).ready(function(){
	//
	$( '#expireAllBtn' ).click(

		function( event ) {

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
		}
	);
});