$(document).ready(function(){
	if (OC.currentUser) {
		$('#header .avatardiv').avatar(OC.currentUser, 32, undefined, true);
		// Personal settings
		$('#avatar .avatardiv').avatar(OC.currentUser, 128);
	}
	// User settings
	$.each($('td.avatar .avatardiv'), function(i, element) {
		$(element).avatar($(element).parent().parent().data('uid'), 32);
	});
});
