/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { fetchAllPredefinedStatuses } from '../services/predefinedStatusService.js'
import { usePredefinedStatusesStore } from './predefinedStatuses.ts'

vi.mock('../services/predefinedStatusService.js', () => ({
	fetchAllPredefinedStatuses: vi.fn(),
}))

const statuses = [
	{ id: 'meeting', icon: '📅', message: 'In a meeting', clearAt: { type: 'period', time: 3600 } },
	{ id: 'sick', icon: '🤒', message: 'Out sick', clearAt: { type: 'end-of', time: 'day' } },
]

describe('predefined statuses store', () => {
	beforeEach(() => {
		vi.resetAllMocks()
		setActivePinia(createPinia())
	})

	it('loads the predefined statuses', async () => {
		vi.mocked(fetchAllPredefinedStatuses).mockResolvedValue(statuses)

		const store = usePredefinedStatusesStore()
		expect(store.statusesHaveLoaded).toBe(false)

		await store.loadAllPredefinedStatuses()

		expect(store.predefinedStatuses).toEqual(statuses)
		expect(store.statusesHaveLoaded).toBe(true)
	})

	it('loads the predefined statuses only once', async () => {
		vi.mocked(fetchAllPredefinedStatuses).mockResolvedValue(statuses)

		const store = usePredefinedStatusesStore()
		await store.loadAllPredefinedStatuses()
		await store.loadAllPredefinedStatuses()

		expect(fetchAllPredefinedStatuses).toHaveBeenCalledOnce()
	})
})
