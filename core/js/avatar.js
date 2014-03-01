$(document).ready(function(){
	if (OC.currentUser) {
		var callback = function() {
			// do not show display name on mobile when profile picture is present
			if($('#header .avatardiv').children().length > 0) {
				$('#header .avatardiv').addClass('avatardiv-shown');
			}
		};

		$('#header .avatardiv').avatar(
			OC.currentUser, 32, undefined, true, callback
		);
		// Personal settings
		$('#avatar .avatardiv').avatar(OC.currentUser, 128);
	}
	// User settings
	$.each($('td.avatar .avatardiv'), function(i, element) {
		$(element).avatar($(element).parent().parent().data('uid'), 32);
	});
});
