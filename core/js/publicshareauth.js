$(document).ready(function(){
 	$('#password').on('keyup input change', function() {
		if ($('#password').val().length > 0) {
			$('#password-submit').prop('disabled', false);
		} else {
			$('#password-submit').prop('disabled', true);
		}
	});
});
