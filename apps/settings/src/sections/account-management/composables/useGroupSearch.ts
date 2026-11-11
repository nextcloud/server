/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { isCancel } from '@nextcloud/axios'
import { searchGroups } from '../../../service/groups.ts'

/**
 * Search groups where only the newest search counts. Starting a search aborts
 * the one still running.
 */
export function useGroupSearch() {
	let latest: AbortController | null = null

	/**
	 * @param options Search options
	 * @param options.search Search query
	 * @param options.offset Offset
	 * @param options.limit Limit
	 * @return The groups, or null if a newer search replaced this one
	 */
	return async function search(options: { search: string, offset: number, limit: number }) {
		latest?.abort()
		const controller = new AbortController()
		latest = controller
		try {
			const groups = await searchGroups({ ...options, signal: controller.signal })
			return latest === controller ? groups : null
		} catch (error) {
			if (latest !== controller || isCancel(error)) {
				return null
			}
			throw error
		}
	}
}
