/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { getCurrentUser } from '@nextcloud/auth'

/**
 * Check whether this is a public share
 * @return {boolean} Whether this is a public share
 */
export function isPublic() {
	return !getCurrentUser()
}

/**
 * Get the sharing token
 * @return {string|null} The sharing token
 */
export function getToken() {
	const tokenElement = document.getElementById('sharingToken') as (HTMLInputElement | null)
	return tokenElement?.value
}
