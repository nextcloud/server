/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { userAgentMap } from './userAgentMap.ts'

export interface DetectedUserAgent {
	id: string
	version?: string
	os?: string
}

/**
 * Detect the client from a user agent string.
 *
 * @param ua Raw user agent string
 * @return Detected client information or null if unknown
 */
export function detect(ua: string): DetectedUserAgent | null {
	for (const id in userAgentMap) {
		const matches = ua.match(userAgentMap[id])
		if (matches) {
			return {
				id,
				version: matches[2] ?? matches[1],
				os: matches[2] && matches[1],
			}
		}
	}

	return null
}
