/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { ComponentPublicInstance } from 'vue'

import { defineCustomElement } from 'vue'

/**
 * What a compiled single file component is, as `defineCustomElement` wants it.
 * Mirrors the constraint of its own overload.
 */
// eslint-disable-next-line @typescript-eslint/no-explicit-any
type CustomElementComponent = new (...args: any[]) => ComponentPublicInstance<any>
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
export function registerCustomElement(component: CustomElementComponent, customElementId: string): string {
	if (window.customElements.get(customElementId)) {
		logger.error(`Custom element with ID ${customElementId} is already defined!`)
		throw new Error(`Custom element with ID ${customElementId} is already defined!`)
	}

	window.customElements.define(customElementId, defineCustomElement(component, { shadowRoot: false }))

	return customElementId
}
