/*!
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

export function randomString(length: number) {
	const characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz'
	const alphaNumeric = characters + '0123456789'
	let result = ''
	for (let i = 0; i < length; i++) {
		// Ensure the first character is alphabetic
		if (i === 0) {
			result += characters.charAt(Math.floor(Math.random() * characters.length))
			continue
		}
		result += alphaNumeric.charAt(Math.floor(Math.random() * alphaNumeric.length))
	}
	return result
}
