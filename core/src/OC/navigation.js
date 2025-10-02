/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/**
 *
 * @param targetURL
 */
export function redirect(targetURL) {
	window.location = targetURL
}

/**
 * Reloads the current page
 *
 * @deprecated 17.0.0 use window.location.reload directly
 */
export function reload() {
	window.location.reload()
}
