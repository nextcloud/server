/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { IAppstoreExApp } from '../apps.d.ts'

import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, test, vi } from 'vitest'

const axios = vi.hoisted(() => ({ get: vi.fn(), post: vi.fn() }))
vi.mock('@nextcloud/axios', async (importActual) => ({
	...(await importActual<object>()),
	default: axios,
}))

const showError = vi.hoisted(() => vi.fn())
vi.mock('@nextcloud/dialogs', () => ({ showError }))

vi.mock('@nextcloud/initial-state', () => ({
	loadState: (app: string, key: string, fallback?: unknown) => ({
		appApiEnabled: true,
		appstoreBundles: [],
		appstoreExAppUpdateCount: 1,
	} as Record<string, unknown>)[key] ?? fallback,
}))

vi.mock('@nextcloud/password-confirmation', () => ({
	addPasswordConfirmationInterceptors: vi.fn(),
	confirmPassword: vi.fn(),
	PwdConfirmationMode: { Lax: 'lax', Strict: 'strict' },
}))
vi.mock('@nextcloud/vue', () => ({ spawnDialog: vi.fn() }))
vi.mock('../components/DaemonSelectionDialog/DaemonSelectionDialog.vue', () => ({ default: {} }))
vi.mock('../service/rebuild-navigation.ts', () => ({ rebuildNavigation: vi.fn() }))

const { useAppsStore } = await import('./apps.ts')
const { useUpdatesStore } = await import('./updates.ts')

let updateError: Error | undefined

beforeEach(() => {
	vi.resetAllMocks()
	setActivePinia(createPinia())

	updateError = undefined
	const exApp = {
		id: 'test_app',
		name: 'Test app',
		app_api: true,
		appstore: true,
		active: true,
		installed: true,
		needsDownload: false,
		removable: true,
		isCompatible: true,
		version: '1.0.0',
		update: '2.0.0',
		daemon: null,
		status: { type: '', action: '', deploy: 100, init: 100 },
		releases: [],
	} as unknown as IAppstoreExApp
	axios.get.mockImplementation(async (url: string) => {
		if (url.endsWith('/apps/app_api/apps/list')) {
			return { data: { apps: [exApp] } }
		}
		if (url.includes('/apps/app_api/apps/update/') && updateError) {
			throw updateError
		}
		if (url.includes('/apps/app_api/apps/status/')) {
			return { data: { type: '', action: '', deploy: 100, init: 100 } }
		}
		if (url.endsWith('/apps/app_api/daemons')) {
			return { data: { daemons: [], default_daemon_config: '' } }
		}
		return { data: { ocs: { data: [] } } }
	})
})

describe('updating an ExApp through the updates store', () => {
	test('clears the update when it succeeded', async () => {
		const apps = useAppsStore()
		const updates = useUpdatesStore()
		await vi.waitFor(() => expect(apps.getAppById('test_app')).not.toBeNull())

		await updates.updateApps(['test_app'])

		expect(apps.getAppById('test_app')!.update).toBeUndefined()
		expect(updates.updateCount).toBe(0)
	})

	test('keeps the update when it failed', async () => {
		updateError = Object.assign(new Error('Request failed with status code 500'), {
			isAxiosError: true,
			response: { status: 500, data: { data: { message: 'Could not update ExApp' } } },
		})
		const apps = useAppsStore()
		const updates = useUpdatesStore()
		await vi.waitFor(() => expect(apps.getAppById('test_app')).not.toBeNull())

		await updates.updateApps(['test_app'])

		expect(apps.getAppById('test_app')!.update).toBe('2.0.0')
		expect(updates.updateCount).toBe(1)
		expect(showError).toHaveBeenCalledExactlyOnceWith('Could not update ExApp')
	})
})
