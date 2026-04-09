/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/**
 * Simple non-secure hashing function similar to Java's `hashCode`
 *
 * @param str The string to hash
 * @return a non secure hash of the string
 */
export function hashCode(str: string): number {
	let hash = 0
	for (let i = 0; i < str.length; i++) {
		hash = ((hash << 5) - hash + str.charCodeAt(i)) | 0
	}
	return (hash >>> 0)
}
