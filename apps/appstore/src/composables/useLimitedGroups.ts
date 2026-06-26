/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { MaybeRefOrGetter } from 'vue'
import type { IAppstoreApp, IAppstoreExApp } from '../apps.d.ts'

import { readonly, ref, toValue, watch } from 'vue'
import { useGroupsStore } from '../store/groups.ts'

/**
 * Get the groups an app is limited to and keep it up to date
 *
 * @param app - The app to get the groups
 */
export function useLimitedGroups(app: MaybeRefOrGetter<IAppstoreApp | IAppstoreExApp>) {
	const groupsStore = useGroupsStore()
	const groupsAppIsLimitedTo = ref<{ id: string, displayName: string }[]>([])
	watch(() => toValue(app).groups, async () => {
		const groups = toValue(app).groups
		if (groups === undefined) {
			groupsAppIsLimitedTo.value = []
			return
		}

		const promises = groups.map((group) => groupsStore.fetchGroupById(group))
		const results = await Promise.all(promises)
		groupsAppIsLimitedTo.value = results.filter(Boolean) as { id: string, displayName: string }[]
	}, { immediate: true })

	return readonly(groupsAppIsLimitedTo)
}
