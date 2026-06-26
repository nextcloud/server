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
 * Map group ids to IUserData objects
 *
 * @param gids - The group ids to create entities for
 */
export function useGroups(gids: MaybeRefOrGetter<string[]>) {
	return computed(() => toValue(gids).map(mapGroupToUserData))
}

/**
 * Map a group id to an IUserData object
 *
 * @param gid - The group id to map
 */
export function mapGroupToUserData(gid: string) {
	return {
		id: gid,
		isNoUser: true,
		displayName: gid,
		iconSvg: svgAccountGroupOutline,
	}
}
