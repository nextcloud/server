/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { IGroup } from '../views/user-types.d.ts'

import axios from '@nextcloud/axios'
import { generateOcsUrl } from '@nextcloud/router'
import { CancelablePromise } from 'cancelable-promise'

interface Group {
	id: string
	displayname: string
	usercount: number
	disabled: number
	canAdd: boolean
	canRemove: boolean
}

const formatGroup = (group: Group): Required<IGroup> => ({
	id: group.id,
	name: group.displayname,
	usercount: group.usercount,
	disabled: group.disabled,
	canAdd: group.canAdd,
	canRemove: group.canRemove,
})

/**
 * Search groups
 *
 * @param {object} options Options
 * @param {string} options.search Search query
 * @param {number} options.offset Offset
 * @param {number} options.limit Limit
 */
export const searchGroups = ({ search, offset, limit }): CancelablePromise<Required<IGroup>[]> => {
	const controller = new AbortController()
	return new CancelablePromise(async (resolve, reject, onCancel) => {
		onCancel(() => controller.abort())
		try {
			const { data } = await axios.get(
				generateOcsUrl('/cloud/groups/details?search={search}&offset={offset}&limit={limit}', { search, offset, limit }), {
					signal: controller.signal,
				},
			)
			const groups: Group[] = data.ocs?.data?.groups ?? []
			const formattedGroups = groups.map(formatGroup)
			resolve(formattedGroups)
		} catch (error) {
			reject(error)
		}
	})
}

/**
 * Load user groups
 *
 * @param {object} options Options
 * @param {string} options.userId User id
 */
export const loadUserGroups = async ({ userId }): Promise<Required<IGroup>[]> => {
	const url = generateOcsUrl('/cloud/users/{userId}/groups/details', { userId })
	const { data } = await axios.get(url)
	const groups: Group[] = data.ocs?.data?.groups ?? []
	const formattedGroups = groups.map(formatGroup)
	return formattedGroups
}

/**
 * Load user subadmin groups
 *
 * @param {object} options Options
 * @param {string} options.userId User id
 */
export const loadUserSubAdminGroups = async ({ userId }): Promise<Required<IGroup>[]> => {
	const url = generateOcsUrl('/cloud/users/{userId}/subadmins/details', { userId })
	const { data } = await axios.get(url)
	const groups: Group[] = data.ocs?.data?.groups ?? []
	const formattedGroups = groups.map(formatGroup)
	return formattedGroups
}
