/*!
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { getCurrentUser } from '@nextcloud/auth'
import { defineStore } from 'pinia'
import { fetchBackupStatus, revertToBackupStatus } from '../services/statusService.js'
import { useStatusState } from './statusState.ts'
import { useUserStatusStore } from './userStatus.ts'

/**
 * The status the user had before it was set automatically (e.g. by a calendar event or an absence).
 */
export const useUserBackupStatusStore = defineStore('userBackupStatus', () => {
	const { loadStatus, ...state } = useStatusState()

	/**
	 * Fetch the backup status from the server.
	 */
	async function fetchBackupFromServer() {
		const uid = getCurrentUser()?.uid
		if (!uid) {
			return
		}

		try {
			loadStatus(await fetchBackupStatus(uid))
		} catch {
			// Ignore missing user backup status
		}
	}

	/**
	 * Restore the backup status as the current user status.
	 *
	 * @param messageId - The message id of the current (automatically set) status
	 */
	async function revertBackupFromServer(messageId: string) {
		const status = await revertToBackupStatus(messageId)
		if (status) {
			loadStatus({})

			const userStatusStore = useUserStatusStore()
			userStatusStore.loadStatusFromServer(status)
			userStatusStore.emitStatusUpdated()
		}
	}

	return {
		...state,
		fetchBackupFromServer,
		revertBackupFromServer,
	}
})
