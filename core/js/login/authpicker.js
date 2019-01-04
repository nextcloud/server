jQuery(document).ready(function() {
	$('#app-token-login').click(function (e) {
		e.preventDefault();
		$(this).addClass('hidden');
		$('#redirect-link').addClass('hidden');
		$('#app-token-login-field').removeClass('hidden');
	});
});
