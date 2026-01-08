/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
function showEmailAddressPromptForm() {
	// Shows email prompt
	const emailInput = document.getElementById('email-input-form')
	emailInput.style.display = 'block'

	// Shows back button
	const backButton = document.getElementById('request-password-back-button')
	backButton.style.display = 'block'

	// Hides password prompt and 'request password' button
	const passwordRequestButton = document.getElementById('request-password-button-not-talk')
	const passwordInput = document.getElementById('password-input-form')
	passwordRequestButton.style.display = 'none'
	passwordInput.style.display = 'none'

	// Hides identification result messages, if any
	const identificationResultSuccess = document.getElementById('identification-success')
	const identificationResultFailure = document.getElementById('identification-failure')
	if (identificationResultSuccess) {
		identificationResultSuccess.style.display = 'none'
	}
	if (identificationResultFailure) {
		identificationResultFailure.style.display = 'none'
	}
}

document.addEventListener('DOMContentLoaded', function() {
	// Enables password submit button only when user has typed something in the password field
	const passwordInput = document.getElementById('password')
	const passwordButton = document.getElementById('password-submit')
	let eventListener = function() {
		passwordButton.disabled = passwordInput.value.length === 0
	}
	passwordInput.addEventListener('click', eventListener)
	passwordInput.addEventListener('keyup', eventListener)
	passwordInput.addEventListener('change', eventListener)

	// Enables email request button only when user has typed something in the email field
	const emailInput = document.getElementById('email')
	const emailButton = document.getElementById('password-request')
	eventListener = function() {
		emailButton.disabled = emailInput.value.length === 0
	}
	emailInput.addEventListener('click', eventListener)
	emailInput.addEventListener('keyup', eventListener)
	emailInput.addEventListener('change', eventListener)

	// Adds functionality to the request password button
	const passwordRequestButton = document.getElementById('request-password-button-not-talk')
	if (passwordRequestButton) {
		passwordRequestButton.addEventListener('click', showEmailAddressPromptForm)
	}
})
