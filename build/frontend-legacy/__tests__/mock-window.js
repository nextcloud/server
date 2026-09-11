/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
window.OC = {
	...window.OC,
	config: {
		version: '32.0.0',
		...(window.OC?.config ?? {}),
	},
}
window.OCA = { ...window.OCA }
window.OCP = { ...window.OCP }

window._oc_webroot = ''

// jsdom does not implement `innerText` (jsdom/jsdom#1245).
// Libraries that strip markup by round-tripping through it - e.g. @nextcloud/dialogs'
// `showMessage` - would otherwise silently receive `undefined`.
Object.defineProperty(HTMLElement.prototype, 'innerText', {
	get() {
		return this.textContent
	},
	set(value) {
		this.textContent = value
	},
	configurable: true,
})
