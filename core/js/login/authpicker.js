jQuery(document).ready(function() {
	$('#app-token-login').click(function (e) {
		e.preventDefault();
		$(this).addClass('hidden');
		$('#redirect-link').addClass('hidden');
		$('#app-token-login-field').removeClass('hidden');
	});

	$('#submit-app-token-login').click(function(e) {
		e.preventDefault();
		window.location.href = 'nc://login/server:'
			+ encodeURIComponent($('#serverHost').val())
			+ "&user:" +  encodeURIComponent($('#user').val())
			+ "&password:" + encodeURIComponent($('#password').val());
	});
});
