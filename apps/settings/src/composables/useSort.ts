/**
 * @copyright 2024 Christopher Ng <chrng8@gmail.com>
 *
 * @author Christopher Ng <chrng8@gmail.com>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

import axios from '@nextcloud/axios'
import { computed, ref } from 'vue'
import { generateUrl } from '@nextcloud/router'
import { loadState } from '@nextcloud/initial-state'
import { showError } from '@nextcloud/dialogs'
import { translate as t } from '@nextcloud/l10n'

import { UserSortMode } from '../constants/UserManagement.ts'

type UserSortOrder = 'asc' | 'desc'

interface UsersSettings {
	sortMode?: UserSortMode,
	sortOrder?: UserSortOrder,
	[key: string]: unknown,
}

const settings = loadState<UsersSettings>('settings', 'usersSettings', {})

export const useSort = () => {
	const sortMode = ref<UserSortMode>(settings?.sortMode ?? UserSortMode.UserId)
	const sortOrder = ref<UserSortOrder>(settings?.sortOrder ?? 'asc')

	const isAscOrder = computed(() => sortOrder.value === 'asc')

	const toggleSortOrder = async () => {
		const order = isAscOrder ? 'desc' : 'asc'
		try {
			await axios.post(
				generateUrl('/settings/users/preferences/user.sortOrder'), {
					value: order,
				})
			sortOrder.value = order
		} catch (error) {
			showError(t('settings', 'Failed to set sort order'))
		}
	}

	const toggleSortMode = async (mode: UserSortMode) => {
		if (sortMode.value === mode) {
			toggleSortOrder()
			return
		}

		try {
			await axios.post(
				generateUrl('/settings/users/preferences/user.sortMode'), {
					value: mode,
				})
			sortMode.value = mode
		} catch (error) {
			showError(t('settings', 'Failed to set sort mode'))
		}
	}

	return {
		sortMode,
		sortOrder,
		isAscOrder,
		toggleSortOrder,
		toggleSortMode,
	}
}
