$(document).ready(function(){
	$('header .avatardiv').avatar(OC.currentUser, 32);
	// Personal settings
	$('#avatar .avatardiv').avatar(OC.currentUser, 128);
	// User settings
	$.each($('td.avatar .avatardiv'), function(i, data) {
		$(data).avatar($(data).parent().parent().data('uid'), 32);
	});
});
