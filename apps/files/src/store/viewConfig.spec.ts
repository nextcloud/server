/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type * as EventBus from '@nextcloud/event-bus'

import { broadcast, emit } from '@nextcloud/event-bus'
import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, test, vi } from 'vitest'
import { useViewConfigStore } from './viewConfig.ts'

vi.mock('@nextcloud/axios', () => ({ default: { put: vi.fn() } }))
vi.mock('@nextcloud/auth', () => ({ getCurrentUser: () => ({ uid: 'alice' }) }))
vi.mock('@nextcloud/event-bus', async (original) => {
	const eventBus = await original<typeof EventBus>()
	// broadcast() also emits in the current tab
	return { ...eventBus, broadcast: vi.fn(eventBus.emit) }
})

describe('view config store', () => {
	beforeEach(() => {
		setActivePinia(createPinia())
		vi.mocked(broadcast).mockClear()
	})

	test('a change is sent to the other tabs', async () => {
		const store = useViewConfigStore()
		await store.update('files', 'sorting_mode', 'size')

		expect(store.getConfig('files').sorting_mode).toBe('size')
		expect(broadcast).toHaveBeenCalledExactlyOnceWith('files:view-config:updated', { view: 'files', key: 'sorting_mode', value: 'size' })
	})

	test('a change from another tab is applied without sending it again', () => {
		const store = useViewConfigStore()
		// What the event bus emits locally when another tab broadcasts
		emit('files:view-config:updated', { view: 'recent', key: 'sorting_direction', value: 'desc' })

		expect(store.getConfig('recent').sorting_direction).toBe('desc')
		expect(broadcast).not.toHaveBeenCalled()
	})
})
