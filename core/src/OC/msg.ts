/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { t } from '@nextcloud/l10n'

/**
 * A little class to manage a status field for a "saving" process.
 * It can be used to display a starting message (e.g. "Saving...") and then
 * replace it with a green success message or a red error message.
 */
export default {
	/**
	 * Displayes a "Saving..." message in the given message placeholder
	 *
	 * @param selector - Query selectior for the element to display the message in
	 */
	startSaving(selector: string) {
		this.startAction(selector, t('core', 'Saving …'))
	},

	/**
	 * Displayes a custom message in the given message placeholder
	 *
	 * @param selector - Query selectior for the element to display the message in
	 * @param message - Plain text message to display (no HTML allowed)
	 */
	startAction(selector: string, message: string) {
		const el = document.querySelector(selector)
		if (!el || !(el instanceof HTMLElement)) {
			return
		}

		el.textContent = message
		el.classList.remove('success')
		el.classList.remove('error')
		el.getAnimations?.().forEach((animation) => animation.cancel())
		el.style.display = 'block'
	},

	/**
	 * Displayes an success/error message in the given selector
	 *
	 * @param selector - Query selectior for the element to display the message in
	 * @param response - Response of the server
	 * @param response.data - Data of the servers response
	 * @param response.data.message - Plain text message to display (no HTML allowed)
	 * @param response.status - is being used to decide whether the message is displayed as an error/success
	 */
	finishedSaving(selector: string, response: { data: { message: string }, status: string }) {
		this.finishedAction(selector, response)
	},

	/**
	 * Displayes an success/error message in the given selector
	 *
	 * @param selector - Query selector for the element to display the message in
	 * @param response - Response of the server
	 * @param response.data - Data of the servers response
	 * @param response.data.message - Plain text message to display (no HTML allowed)
	 * @param response.status . Is being used to decide whether the message is displayed as an error/success
	 */
	finishedAction(selector: string, response: { data: { message: string }, status: string }) {
		if (response.status === 'success') {
			this.finishedSuccess(selector, response.data.message)
		} else {
			this.finishedError(selector, response.data.message)
		}
	},

	/**
	 * Displayes an success message in the given selector
	 *
	 * @param selector - Query selector for the element to display the message in
	 * @param message - Plain text success message to display (no HTML allowed)
	 */
	finishedSuccess(selector: string, message: string) {
		const el = document.querySelector(selector)
		if (!el || !(el instanceof HTMLElement)) {
			return
		}

		el.textContent = message
		el.classList.remove('error')
		el.classList.add('success')
		el.getAnimations?.().forEach((animation) => animation.cancel())

		window.setTimeout(fadeOut, 3000)
		el.style.display = 'block'

		/**
		 * Fades out the message element
		 */
		function fadeOut() {
			if (!el || !(el instanceof HTMLElement)) {
				return
			}

			// eslint-disable-next-line @stylistic/exp-list-style
			const animation = el.animate?.(
				[
					{ opacity: 1 },
					{ opacity: 0 },
				],
				{
					duration: 900,
					fill: 'forwards',
				},
			)

			if (animation) {
				animation.addEventListener('finish', () => {
					el.style.display = 'none'
				})
			} else {
				window.setTimeout(() => {
					el.style.display = 'none'
				}, 900)
			}
		}
	},

	/**
	 * Displayes an error message in the given selector
	 *
	 * @param selector - Query selector for the element to display the message in
	 * @param message - Plain text error message to display (no HTML allowed)
	 */
	finishedError(selector: string, message: string) {
		const el = document.querySelector(selector)
		if (!el || !(el instanceof HTMLElement)) {
			return
		}

		el.textContent = message
		el.classList.remove('success')
		el.classList.add('error')
		el.style.display = 'block'
	},
}
