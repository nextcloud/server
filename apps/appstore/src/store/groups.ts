/**
 * SPDX-License-Identifier: AGPL-3.0-or-later
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 */

import type { OCSResponse } from '@nextcloud/typings/ocs'
import type { NcSelectUsersModel } from '@nextcloud/vue/components/NcSelectUsers'

import axios from '@nextcloud/axios'
import { generateOcsUrl } from '@nextcloud/router'
import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import logger from '../utils/logger.ts'

export const useGroupsStore = defineStore('groups', () => {
	const groups = ref(new Map<string, NcSelectUsersModel>())

	/**
	 * Search the API for groups matching the query
	 *
	 * @param query - Query to search
	 */
	async function searchGroups(query: string) {
		const url = generateOcsUrl('/cloud/groups/details')
		try {
			// eslint-disable-next-line @typescript-eslint/no-explicit-any
			const { data } = await axios.get<OCSResponse<{ groups: any }>>(url, {
				params: {
					search: query.trim(),
					limit: 10,
				},
			})
			for (const group of data.ocs.data.groups) {
				if (groups.value.has(group.id)) {
					continue
				}

				groups.value.set(group.id, {
					id: group.id,
					displayName: group.displayname,
					isNoUser: true,
				})
			}
		} catch (error) {
			logger.error('Failed to search groups', { error })
		}
	}

	/**
	 * Get a group by its id
	 *
	 * @param groupId - The id of the group to retrieve
	 */
	function getGroupById(groupId: string) {
		return groups.value.get(groupId)
	}

	return {
		groups: computed(() => Array.from(groups.value.values())),
		searchGroups,
		getGroupById,
	}
})
