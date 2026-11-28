/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { IGroup } from '../views/user-types.d.ts'

import axios from '@nextcloud/axios'
import { generateOcsUrl } from '@nextcloud/router'

interface Group {
	id: string
	displayname: string
	usercount: number
	disabled: number
	canAdd: boolean
	canRemove: boolean
}

/**
 *
 * @param group
 */
function formatGroup(group: Group): Required<IGroup> {
	return {
		id: group.id,
		name: group.displayname,
		usercount: group.usercount,
		disabled: group.disabled,
		canAdd: group.canAdd,
		canRemove: group.canRemove,
	}
}

/**
 * Search groups
 *
 * @param options Options
 * @param options.search Search query
 * @param options.offset Offset
 * @param options.limit Limit
 * @param options.signal Aborts the request, e.g. when a newer search replaces it
 */
export async function searchGroups({ search, offset, limit, signal }: { search: string, offset: number, limit: number, signal?: AbortSignal }): Promise<Required<IGroup>[]> {
	const { data } = await axios.get(generateOcsUrl('/cloud/groups/details?search={search}&offset={offset}&limit={limit}', { search, offset, limit }), { signal })
	const groups: Group[] = data.ocs?.data?.groups ?? []
	return groups.map(formatGroup)
}

/**
 * Load user groups
 *
 * @param options Options
 * @param options.userId User id
 */
export async function loadUserGroups({ userId }): Promise<Required<IGroup>[]> {
	const url = generateOcsUrl('/cloud/users/{userId}/groups/details', { userId })
	const { data } = await axios.get(url)
	const groups: Group[] = data.ocs?.data?.groups ?? []
	const formattedGroups = groups.map(formatGroup)
	return formattedGroups
}

/**
 * Load user subadmin groups
 *
 * @param options Options
 * @param options.userId User id
 */
export async function loadUserSubAdminGroups({ userId }): Promise<Required<IGroup>[]> {
	const url = generateOcsUrl('/cloud/users/{userId}/subadmins/details', { userId })
	const { data } = await axios.get(url)
	const groups: Group[] = data.ocs?.data?.groups ?? []
	const formattedGroups = groups.map(formatGroup)
	return formattedGroups
}
