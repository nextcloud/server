/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import type { ComputedRef, Ref } from 'vue'
import type { IGroup } from '../views/user-types'

import { computed } from 'vue'

/**
 * Format a group to a menu entry
 *
 * @param group the group
 */
function formatGroupMenu(group?: IGroup) {
	if (typeof group === 'undefined') {
		return null
	}

	return {
		id: group.id,
		title: group.name,
		usercount: group.usercount ?? 0,
		count: Math.max(0, (group.usercount ?? 0) - (group.disabled ?? 0)),
	}
}

export const useFormatGroups = (groups: Ref<IGroup[]>|ComputedRef<IGroup[]>) => {
	/**
	 * All non-disabled non-admin groups
	 */
	const userGroups = computed(() => {
		const formatted = groups.value
			// filter out disabled and admin
			.filter(group => group.id !== 'disabled' && group.id !== '__nc_internal_recent' && group.id !== 'admin')
			// format group
			.map(group => formatGroupMenu(group))
			// remove invalid
			.filter(group => group !== null)
		return formatted as NonNullable<ReturnType<typeof formatGroupMenu>>[]
	})

	/**
	 * The admin group if found otherwise null
	 */
	const adminGroup = computed(() => formatGroupMenu(groups.value.find(group => group.id === 'admin')))

	/**
	 * The group of disabled users
	 */
	const disabledGroup = computed(() => formatGroupMenu(groups.value.find(group => group.id === 'disabled')))

	/**
	 * The group of recent users
	 */
	const recentGroup = computed(() => formatGroupMenu(groups.value.find(group => group.id === '__nc_internal_recent')))

	return { adminGroup, recentGroup, disabledGroup, userGroups }
}
