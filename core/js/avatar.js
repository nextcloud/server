$(document).ready(function(){
	if (OC.currentUser) {
		// Personal settings
		$('#avatar .avatardiv').avatar(OC.currentUser, 128);
	}
	// User settings
	$.each($('td.avatar .avatardiv'), function(i, element) {
		$(element).avatar($(element).parent().parent().data('uid'), 32);
	});
});
