/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { emit } from '@nextcloud/event-bus'
import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import * as api from '../services/statusService.js'
import { useUserBackupStatusStore } from './userBackupStatus.ts'
import { useUserStatusStore } from './userStatus.ts'

vi.mock('@nextcloud/auth')
vi.mock('@nextcloud/event-bus', () => ({ emit: vi.fn() }))
vi.mock('../services/statusService.js', () => ({
	fetchBackupStatus: vi.fn(),
	revertToBackupStatus: vi.fn(),
}))

const backupStatus = {
	status: 'online',
	statusIsUserDefined: true,
	message: 'Working remotely',
	icon: '🏡',
	clearAt: null,
	messageIsPredefined: true,
	messageId: 'remote-work',
}

describe('user backup status store', () => {
	beforeEach(() => {
		vi.resetAllMocks()
		setActivePinia(createPinia())
	})

	it('fetches the backup status of the current user', async () => {
		vi.mocked(api.fetchBackupStatus).mockResolvedValue(backupStatus)

		const store = useUserBackupStatusStore()
		await store.fetchBackupFromServer()

		expect(api.fetchBackupStatus).toHaveBeenCalledWith('test')
		expect(store.$state).toEqual(backupStatus)
	})

	it('ignores a missing backup status', async () => {
		vi.mocked(api.fetchBackupStatus).mockRejectedValue(new Error('404'))

		const store = useUserBackupStatusStore()
		await expect(store.fetchBackupFromServer()).resolves.toBeUndefined()
		expect(store.message).toBeNull()
	})

	it('reverts to the backup status', async () => {
		vi.mocked(api.fetchBackupStatus).mockResolvedValue(backupStatus)
		vi.mocked(api.revertToBackupStatus).mockResolvedValue(backupStatus)

		const store = useUserBackupStatusStore()
		const userStatusStore = useUserStatusStore()
		await store.fetchBackupFromServer()
		await store.revertBackupFromServer('meeting')

		expect(api.revertToBackupStatus).toHaveBeenCalledWith('meeting')
		expect(store.message).toBeNull()
		expect(store.icon).toBeNull()
		expect(userStatusStore.$state).toEqual(backupStatus)
		expect(emit).toHaveBeenCalledWith('user_status:status.updated', {
			status: 'online',
			message: 'Working remotely',
			icon: '🏡',
			clearAt: null,
			userId: 'test',
		})
	})

	it('keeps the current status if there is nothing to revert to', async () => {
		vi.mocked(api.revertToBackupStatus).mockResolvedValue(undefined)

		const userStatusStore = useUserStatusStore()
		userStatusStore.loadStatusFromServer({ status: 'dnd', message: 'Focus', icon: '🎧' })
		await useUserBackupStatusStore().revertBackupFromServer('meeting')

		expect(userStatusStore.message).toBe('Focus')
		expect(emit).not.toHaveBeenCalled()
	})
})
