$(document).ready(function () {
		var visitortimezone = (-new Date().getTimezoneOffset() / 60);
		$('#timezone-offset').val(visitortimezone);

		// only enable the submit button once we are sure that the timezone is set
		var $loginForm = $('form[name="login"]');
		if ($loginForm.length) {
			$loginForm.find('input#submit').prop('disabled', false);
		}
});
