/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/**
 * @param {any} name -
 */
export default function getURLParameter(name) {
	return decodeURIComponent((new RegExp(`[?|&]${name}=([^&;]+?)(&|#|;|$)`).exec(location.search) || [undefined, ''])[1].replace(/\+/g, '%20')) || ''
}
