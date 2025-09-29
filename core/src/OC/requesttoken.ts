/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { emit } from '@nextcloud/event-bus'
import { generateUrl } from '@nextcloud/router'

/**
 * Get the current CSRF token.
 */
export function getRequestToken(): string {
	return document.head.dataset.requesttoken!
}

/**
 * Set a new CSRF token (e.g. because of session refresh).
 * This also emits an event bus event for the updated token.
 *
 * @param token - The new token
 * @fires Error - If the passed token is not a potential valid token
 */
export function setRequestToken(token: string): void {
	if (!token || typeof token !== 'string') {
		throw new Error('Invalid CSRF token given', { cause: { token } })
	}

	document.head.dataset.requesttoken = token
	emit('csrf-token-update', { token })
}

/**
 * Fetch the request token from the API.
 * This does also set it on the current context, see `setRequestToken`.
 *
 * @fires Error - If the request failed
 */
export async function fetchRequestToken(): Promise<string> {
	const url = generateUrl('/csrftoken')

	const response = await fetch(url)
	if (!response.ok) {
		throw new Error('Could not fetch CSRF token from API', { cause: response })
	}

	const { token } = await response.json()
	setRequestToken(token)
	return token
}
