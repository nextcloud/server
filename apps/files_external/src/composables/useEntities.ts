/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { MaybeRefOrGetter } from 'vue'

import svgAccountGroupOutline from '@mdi/svg/svg/account-group-outline.svg?raw'
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import { computed, reactive, toValue, watchEffect } from 'vue'

const displayNames = reactive(new Map<string, string>())
const groupDisplayNames = reactive(new Map<string, string>())
const pendingGroups = new Set<string>()

/**
 * Fetch and provide user display names for given UIDs
 *
 * @param uids - The user ids to fetch display names for
 */
export function useUsers(uids: MaybeRefOrGetter<string[]>) {
	const users = computed(() => toValue(uids).map((uid) => ({
		id: `user:${uid}`,
		user: uid,
		displayName: displayNames.get(uid) || uid,
	})))

	watchEffect(async () => {
		const missingUsers = toValue(uids).filter((uid) => !displayNames.has(uid))
		if (missingUsers.length > 0) {
			const { data } = await axios.post(generateUrl('/displaynames'), {
				users: missingUsers,
			})
			for (const [uid, displayName] of Object.entries(data.users)) {
				displayNames.set(uid, displayName as string)
			}
		}
	})

	return users
}

/**
 * Fetch and provide group display names for given GIDs, mapped to IUserData objects
 *
 * @param gids - The group ids to create entities for
 */
export function useGroups(gids: MaybeRefOrGetter<string[]>) {
	const groups = computed(() => toValue(gids).map((gid) => mapGroupToUserData(gid, groupDisplayNames.get(gid))))

	watchEffect(async () => {
		const missingGroups = toValue(gids).filter((gid) => !groupDisplayNames.has(gid) && !pendingGroups.has(gid))
		missingGroups.forEach((gid) => pendingGroups.add(gid))
		for (const gid of missingGroups) {
			try {
				const { data } = await axios.get(generateUrl('apps/files_external/ajax/applicable'), {
					params: { pattern: gid, limit: 50 },
				})
				groupDisplayNames.set(gid, data.groups[gid] ?? gid)
			} finally {
				pendingGroups.delete(gid)
			}
		}
	})

	return groups
}

/**
 * Map a group id to an IUserData object
 *
 * @param gid - The group id to map
 * @param displayName - The resolved display name for the group, falls back to the group id
 */
export function mapGroupToUserData(gid: string, displayName?: string) {
	return {
		id: gid,
		isNoUser: true,
		displayName: displayName || gid,
		iconSvg: svgAccountGroupOutline,
	}
}
