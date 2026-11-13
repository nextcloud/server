/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { IAppstoreApp } from '../apps.d.ts'
import type { AppAction } from './index.ts'

import { beforeEach, describe, expect, test, vi } from 'vitest'

const appsStore = vi.hoisted(() => ({ enableApp: vi.fn(), disableApp: vi.fn(), uninstallApp: vi.fn() }))
const updatesStore = vi.hoisted(() => ({ updateApp: vi.fn() }))
const exAppsStore = vi.hoisted(() => ({ daemonAccessible: true }))
vi.mock('../store/apps.ts', () => ({ useAppsStore: vi.fn(() => appsStore) }))
vi.mock('../store/updates.ts', () => ({ useUpdatesStore: vi.fn(() => updatesStore) }))
vi.mock('../store/exApps.ts', () => ({ useExAppsStore: vi.fn(() => exAppsStore) }))

const { useAppsStore } = await import('../store/apps.ts')
const { useExAppsStore } = await import('../store/exApps.ts')
const { pinia } = await import('../store/pinia.ts')
const { useUpdatesStore } = await import('../store/updates.ts')
const { actionDisable } = await import('./actionDisable.ts')
const { actionEnable } = await import('./actionEnable.ts')
const { actionForceEnable } = await import('./actionForceEnable.ts')
const { actionInstall } = await import('./actionInstall.ts')
const { actionInstallForced } = await import('./actionInstallForced.ts')
const { actionRemove } = await import('./actionRemove.ts')
const { actionUpdate } = await import('./actionUpdate.ts')

const app = { id: 'test_app', app_api: true, update: '2.0.0', daemon: null } as unknown as IAppstoreApp

describe('app actions use the Pinia of the app store', () => {
	beforeEach(() => {
		vi.clearAllMocks()
	})

	test.each([
		['disable', actionDisable, useAppsStore],
		['enable', actionEnable, useAppsStore],
		['force-enable', actionForceEnable, useAppsStore],
		['install', actionInstall, useAppsStore],
		['install-forced', actionInstallForced, useAppsStore],
		['remove', actionRemove, useAppsStore],
		['update', actionUpdate, useUpdatesStore],
	])('%s', async (_, action: AppAction, useStore) => {
		await (action as Extract<AppAction, { callback: unknown }>).callback(app)

		expect(vi.mocked(useStore).mock.calls).toEqual([[pinia]])
	})

	test('update checks the daemon in the app store Pinia', () => {
		expect(actionUpdate.enabled(app)).toBe(true)
		expect(vi.mocked(useExAppsStore).mock.calls).toEqual([[pinia]])
	})
})
