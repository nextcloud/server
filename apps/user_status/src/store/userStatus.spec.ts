/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { emit } from '@nextcloud/event-bus'
import { loadState } from '@nextcloud/initial-state'
import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import * as api from '../services/statusService.js'
import { usePredefinedStatusesStore } from './predefinedStatuses.ts'
import { useUserStatusStore } from './userStatus.ts'

vi.mock('@nextcloud/auth')
vi.mock('@nextcloud/event-bus', () => ({ emit: vi.fn() }))
vi.mock('@nextcloud/initial-state', () => ({ loadState: vi.fn() }))
vi.mock('../services/statusService.js', () => ({
	clearMessage: vi.fn(),
	fetchCurrentStatus: vi.fn(),
	setCustomMessage: vi.fn(),
	setPredefinedMessage: vi.fn(),
	setStatus: vi.fn(),
}))

const serverStatus = {
	status: 'away',
	statusIsUserDefined: true,
	message: 'In a meeting',
	icon: '📅',
	clearAt: 1700000000,
	messageIsPredefined: true,
	messageId: 'meeting',
}

describe('user status store', () => {
	beforeEach(() => {
		vi.resetAllMocks()
		setActivePinia(createPinia())
	})

	it('loads the status from the initial state', () => {
		vi.mocked(loadState).mockReturnValue(serverStatus)

		const store = useUserStatusStore()
		store.loadStatusFromInitialState()

		expect(loadState).toHaveBeenCalledWith('user_status', 'status')
		expect(store.$state).toEqual(serverStatus)
	})

	it('only replaces status, message and icon on partial updates', () => {
		const store = useUserStatusStore()
		store.loadStatusFromServer(serverStatus)
		store.loadStatusFromServer({ status: 'online', message: 'Back', icon: '👋' })

		expect(store.$state).toEqual({
			...serverStatus,
			status: 'online',
			message: 'Back',
			icon: '👋',
		})
		expect(emit).not.toHaveBeenCalled()
	})

	it('re-fetches the status from the server', async () => {
		vi.mocked(api.fetchCurrentStatus).mockResolvedValue(serverStatus)

		const store = useUserStatusStore()
		await store.reFetchStatusFromServer()

		expect(store.$state).toEqual(serverStatus)
	})

	it('sets the status type', async () => {
		const store = useUserStatusStore()
		await store.setStatus('dnd')

		expect(api.setStatus).toHaveBeenCalledWith('dnd')
		expect(store.status).toBe('dnd')
		expect(store.statusIsUserDefined).toBe(true)
		expect(emit).toHaveBeenCalledWith('user_status:status.updated', {
			status: 'dnd',
			message: null,
			icon: null,
			clearAt: null,
			userId: 'test',
		})
	})

	it('does not change the state if saving the status fails', async () => {
		vi.mocked(api.setStatus).mockRejectedValue(new Error('network'))

		const store = useUserStatusStore()
		await expect(store.setStatus('dnd')).rejects.toThrow('network')

		expect(store.status).toBeNull()
		expect(emit).not.toHaveBeenCalled()
	})

	it('sets a predefined message', async () => {
		usePredefinedStatusesStore().predefinedStatuses = [
			{ id: 'meeting', icon: '📅', message: 'In a meeting', clearAt: null },
			{ id: 'sick', icon: '🤒', message: 'Out sick', clearAt: null },
		]

		const store = useUserStatusStore()
		await store.setPredefinedMessage('sick', { type: '_time', time: 1700000000 })

		expect(api.setPredefinedMessage).toHaveBeenCalledWith('sick', 1700000000)
		expect(store.$state).toMatchObject({
			messageId: 'sick',
			messageIsPredefined: true,
			message: 'Out sick',
			icon: '🤒',
			clearAt: 1700000000,
		})
		expect(emit).toHaveBeenCalledWith('user_status:status.updated', expect.objectContaining({
			message: 'Out sick',
			icon: '🤒',
			clearAt: 1700000000,
		}))
	})

	it('sets a custom message', async () => {
		const store = useUserStatusStore()
		store.loadStatusFromServer(serverStatus)
		await store.setCustomMessage('Lunch', '🍕', null)

		expect(api.setCustomMessage).toHaveBeenCalledWith('Lunch', '🍕', null)
		expect(store.$state).toMatchObject({
			messageId: null,
			messageIsPredefined: false,
			message: 'Lunch',
			icon: '🍕',
			clearAt: null,
		})
		expect(emit).toHaveBeenCalledOnce()
	})

	it('clears the message', async () => {
		const store = useUserStatusStore()
		store.loadStatusFromServer(serverStatus)
		await store.clearMessage()

		expect(api.clearMessage).toHaveBeenCalledOnce()
		expect(store.$state).toEqual({
			...serverStatus,
			messageId: null,
			messageIsPredefined: false,
			message: null,
			icon: null,
			clearAt: null,
		})
		expect(emit).toHaveBeenCalledWith('user_status:status.updated', expect.objectContaining({
			status: 'away',
			message: null,
		}))
	})
})
