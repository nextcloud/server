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

// jsdom does not implement `ResizeObserver`, but it is used at module scope by
// composables like `useFileListWidth`, so importing them would throw.
// Specs that assert on resizing replace this with a full mock (`mockResizeObserver`).
if (!('ResizeObserver' in window)) {
	window.ResizeObserver = class ResizeObserver {
		observe() {}
		unobserve() {}
		disconnect() {}
	}
}

// jsdom does not implement `innerText` at all, while the specification defines it
// to fall back to `textContent` for elements that are not being rendered.
// @see https://github.com/jsdom/jsdom/issues/1245
if (!('innerText' in HTMLElement.prototype)) {
	Object.defineProperty(HTMLElement.prototype, 'innerText', {
		configurable: true,
		get() {
			return this.textContent
		},
		set(value) {
			this.textContent = value
		},
	})
}
