function showEmailAddressPromptForm() {
	// Shows email prompt
	var emailInput = document.getElementById('email-input-form');
	emailInput.style.display="block";

	// Shows back button
	var backButton = document.getElementById('request-password-back-button');
	backButton.style.display="block";

	// Hides password prompt and 'request password' button
	var passwordRequestButton = document.getElementById('request-password-button-not-talk');
	var passwordInput = document.getElementById('password-input-form');
	passwordRequestButton.style.display="none";
	passwordInput.style.display="none";

	// Hides identification result messages, if any
	var identificationResultSuccess = document.getElementById('identification-success');
	var identificationResultFailure = document.getElementById('identification-failure');
	if (identificationResultSuccess) {
		identificationResultSuccess.style.display="none";
	}
	if (identificationResultFailure) {
		identificationResultFailure.style.display="none";
	}
}

document.addEventListener('DOMContentLoaded', function() {
	// Enables password submit button only when user has typed something in the password field
	var passwordInput = document.getElementById('password');
	var passwordButton = document.getElementById('password-submit');
	var eventListener = function() {
		passwordButton.disabled = passwordInput.value.length === 0;
	};
	passwordInput.addEventListener('click', eventListener);
	passwordInput.addEventListener('keyup', eventListener);
	passwordInput.addEventListener('change', eventListener);

	// Enables email request button only when user has typed something in the email field
	var emailInput = document.getElementById('email');
	var emailButton = document.getElementById('password-request');
	eventListener = function() {
		emailButton.disabled = emailInput.value.length === 0;
	};
	emailInput.addEventListener('click', eventListener);
	emailInput.addEventListener('keyup', eventListener);
	emailInput.addEventListener('change', eventListener);

	// Adds functionality to the request password button
	var passwordRequestButton = document.getElementById('request-password-button-not-talk');
	if (passwordRequestButton) {
		passwordRequestButton.addEventListener('click', showEmailAddressPromptForm);
	}

});
