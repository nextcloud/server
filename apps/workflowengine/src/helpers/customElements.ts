/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { Component } from 'vue'

import { defineCustomElement } from 'vue'
import { logger } from '../logger.ts'

/**
 * Register a component as a custom element, so it can be used as the value
 * editor of a check or as the options editor of an operation.
 *
 * The element renders in the light DOM: the settings page styles it, and its
 * own styles come from the app stylesheet rather than from a shadow root.
 *
 * @param component - The component to register
 * @param customElementId - Unique element name, prefixed with the app
 *   namespace, e.g. `oca-myapp-checks-request_user_agent`
 * @return The element name, to be used as `CheckPlugin.element`
 */
export function registerCustomElement(component: Component, customElementId: string): string {
	if (window.customElements.get(customElementId)) {
		logger.error(`Custom element with ID ${customElementId} is already defined!`)
		throw new Error(`Custom element with ID ${customElementId} is already defined!`)
	}

	window.customElements.define(customElementId, defineCustomElement(component, { shadowRoot: false }))

	return customElementId
}
