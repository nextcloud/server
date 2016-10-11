/* global jstz */
$(document).ready(function () {
		$('#timezone-offset').val((-new Date().getTimezoneOffset() / 60));
		$('#timezone').val(jstz.determine().name());

		// only enable the submit button once we are sure that the timezone is set
		var $loginForm = $('form[name="login"]');
		if ($loginForm.length) {
			$loginForm.find('input#submit').prop('disabled', false);
		}
	}
);
