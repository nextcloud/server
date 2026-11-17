/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { IAppstoreApp } from '../apps.d.ts'

import { showError } from '@nextcloud/dialogs'
import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import * as api from '../service/api.ts'
import { useAppsStore } from './apps.ts'
import { useUpdatesStore } from './updates.ts'

vi.mock('../service/api.ts', () => ({
	getApps: vi.fn(),
	getCategories: vi.fn(),
	refreshApps: vi.fn(),
}))
vi.mock('@nextcloud/dialogs', () => ({ showError: vi.fn() }))
vi.mock('@nextcloud/initial-state', () => ({
	loadState: (_app: string, key: string, fallback?: unknown) => (key === 'appstoreUpdateCount' ? 0 : (fallback ?? [])),
}))

const app = (id: string, active: boolean, update?: string) => ({ id, name: id, active, update, groups: [] }) as unknown as IAppstoreApp

describe('apps store refresh', () => {
	beforeEach(() => {
		vi.resetAllMocks()
		setActivePinia(createPinia())
		vi.mocked(api.getCategories).mockResolvedValue([])
	})

	it('reloads the apps and counts the enabled ones with an update', async () => {
		vi.mocked(api.getApps).mockResolvedValue([])
		const store = useAppsStore()
		const updates = useUpdatesStore()
		vi.mocked(api.getApps).mockResolvedValue([
			app('calendar', true, '6.1.0'),
			app('deck', false, '2.0.0'),
			app('mail', true),
		])

		await store.refreshApps()

		expect(api.refreshApps).toHaveBeenCalledOnce()
		expect(store.apps.map(({ id }) => id)).toEqual(expect.arrayContaining(['calendar', 'deck', 'mail']))
		expect(updates.updateCount).toBe(1)
		expect(store.isRefreshing).toBe(false)
	})

	it('keeps the list when the app store cannot be asked', async () => {
		vi.mocked(api.getApps).mockResolvedValue([])
		const store = useAppsStore()
		await vi.waitFor(() => expect(api.getApps).toHaveBeenCalledOnce())
		vi.mocked(api.refreshApps).mockRejectedValue(new Error('offline'))

		await store.refreshApps()

		expect(api.getApps).toHaveBeenCalledOnce()
		expect(showError).toHaveBeenCalledOnce()
		expect(store.isRefreshing).toBe(false)
	})
})
