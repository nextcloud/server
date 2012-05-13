function switchPublicFolder()
{
	var publicEnable = $('#publicEnable').is(':checked');
	var sharingaimGroup = $('input:radio[name=sharingaim]'); //find all radiobuttons of that group
	$.each(sharingaimGroup, function(index, sharingaimItem) {
		sharingaimItem.disabled = !publicEnable;	 //set all buttons to the correct state
	});
}

$(document).ready(function(){
	switchPublicFolder(); // Execute the function after loading DOM tree
	$('#publicEnable').click(function(){
			switchPublicFolder(); // To get rid of onClick()
	});

	$('#allowZipDownload').bind('change', function() {
		if($('#allowZipDownload').attr('checked')) {
			$('#maxZipInputSize').removeAttr('disabled');
		} else {
			$('#maxZipInputSize').attr('disabled', 'disabled');
		}
	});
});
