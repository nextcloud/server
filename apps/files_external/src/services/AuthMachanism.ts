/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { IAuthMechanism } from '../types.ts'

import logger from '../utils/logger.ts'

interface IAuthConfigHandler {
	/**
	 * Unique identifier for the auth mechanism handler
	 */
	id: string

	/**
	 * Tag name used to register the web component.
	 *
	 * The registered web component must have the following props:
	 * - `authMechanism`: The auth mechanism configuration object {see IAuthMechanism}
	 * - `modelValue`: The current configuration values as an object
	 *
	 * The web component must emit the following events:
	 * - `update:modelValue`: Emitted when the configuration values change, with the new values as detail
	 *
	 * @see https://developer.mozilla.org/en-US/docs/Web/Web_Components/Using_custom_elements
	 */
	tagName: string

	/**
	 * Check if the auth mechanism is enabled
	 *
	 * @param authMechanism - The auth mechanism selected
	 */
	enabled(authMechanism: IAuthMechanism): boolean
}

export class AuthMechanism {
	#registeredAuthMechanisms = new Map<string, IAuthConfigHandler>()

	/**
	 * Register a custom auth mechanism handler
	 *
	 * @param authMechanism - The auth mechanism to register
	 */
	registerHandler(authMechanism: IAuthConfigHandler) {
		if (this.#registeredAuthMechanisms.has(authMechanism.id)) {
			logger.warn(`Auth mechanism handler with id '${authMechanism.id}' is already registered`)
		}
		this.#registeredAuthMechanisms.set(authMechanism.id, authMechanism)
	}

	/**
	 * Get the handler for a given auth mechanism
	 *
	 * @param authMechanism - The auth mechanism to get the handler for
	 */
	getHandler(authMechanism: IAuthMechanism): IAuthConfigHandler | undefined {
		return this.#registeredAuthMechanisms.values().find((handler) => handler.enabled(authMechanism))
	}
}
