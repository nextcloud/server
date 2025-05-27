/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

const form = document.querySelector('form')
form.addEventListener('submit', function(event) {
	const wrapper = document.getElementById('submit-wrapper')
	if (wrapper === null) {
		return
	}

	if (OC.PasswordConfirmation.requiresPasswordConfirmation()) {
		// stop the event
		event.preventDefault()
		event.stopPropagation()

		// handle password confirmation
		OC.PasswordConfirmation.requirePasswordConfirmation(function () {
			// when password is confirmed we submit the form
			form.submit()
		})

		return false
	}

	Array.from(wrapper.getElementsByClassName('icon-confirm-white')).forEach(function(el) {
		el.classList.remove('icon-confirm-white')
		el.classList.add(OCA.Theming && OCA.Theming.inverted ? 'icon-loading-small' : 'icon-loading-small-dark')
		el.disabled = true
	})
})
