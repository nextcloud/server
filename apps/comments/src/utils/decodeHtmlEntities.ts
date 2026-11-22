/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/**
 * @param value - the string to decode
 * @param passes - the number of times to decode the string, default is 1
 */
export function decodeHtmlEntities(value: string, passes = 1) {
	const parser = new DOMParser()
	let decoded = value
	for (let i = 0; i < passes; i++) {
		decoded = parser.parseFromString(decoded, 'text/html').documentElement.textContent
	}
	return decoded
}
