/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type * as EventBus from '@nextcloud/event-bus'

import { broadcast, emit } from '@nextcloud/event-bus'
import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, test, vi } from 'vitest'
import { useUserConfigStore } from './userconfig.ts'

vi.mock('@nextcloud/axios', () => ({ default: { put: vi.fn() } }))
vi.mock('@nextcloud/auth', () => ({ getCurrentUser: () => ({ uid: 'alice' }) }))
vi.mock('@nextcloud/event-bus', async (original) => {
	const eventBus = await original<typeof EventBus>()
	// broadcast() also emits in the current tab
	return { ...eventBus, broadcast: vi.fn(eventBus.emit) }
})

describe('user config store', () => {
	beforeEach(() => {
		setActivePinia(createPinia())
		vi.mocked(broadcast).mockClear()
	})

	test('a change is sent to the other tabs', async () => {
		const store = useUserConfigStore()
		await store.update('show_hidden', true)

		expect(store.userConfig.show_hidden).toBe(true)
		expect(broadcast).toHaveBeenCalledExactlyOnceWith('files:config:updated', { key: 'show_hidden', value: true })
	})

	test('a change from another tab is applied without sending it again', () => {
		const store = useUserConfigStore()
		// What the event bus emits locally when another tab broadcasts
		emit('files:config:updated', { key: 'grid_view', value: true })

		expect(store.userConfig.grid_view).toBe(true)
		expect(broadcast).not.toHaveBeenCalled()
	})
})
