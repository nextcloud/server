/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import wrap from '@vue/web-component-wrapper'
import Vue from 'vue'

/**
 *
 * @param VueComponent {Object} The Vue component to turn into a Web Components custom element.
 * @param customElementId {string} The element name, it must be unique. Recommended pattern oca-$appid-(checks|operations)-$use_case, example: oca-my_app-checks-request_user_agent
 */
function registerCustomElement(VueComponent, customElementId) {
	const WrappedComponent = wrap(Vue, VueComponent)
	if (window.customElements.get(customElementId)) {
		console.error('Custom element with ID ' + customElementId + ' is already defined!')
		throw new Error('Custom element with ID ' + customElementId + ' is already defined!')
	}
	window.customElements.define(customElementId, WrappedComponent)

	// In Vue 2, wrap doesn't support disabling shadow :(
	// Disable with a hack
	Object.defineProperty(WrappedComponent.prototype, 'attachShadow', { value() { return this } })
	Object.defineProperty(WrappedComponent.prototype, 'shadowRoot', { get() { return this } })

	return customElementId
}

export { registerCustomElement }
