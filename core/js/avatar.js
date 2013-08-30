$(document).ready(function(){
	$('header .avatardiv').avatar(OC.currentUser, 32);
	// Personal settings
	$('#avatar .avatardiv').avatar(OC.currentUser, 128);
	// User settings
	$.each($('td.avatar .avatardiv'), function(i, data) {
		$(data).avatar($(data).parent().parent().data('uid'), 32); // TODO maybe a better way of getting the current name … – may be fixed by new-user-mgmt
	});
	// TODO when creating a new user, he gets a previously used avatar – may be fixed by new user-mgmt
});
