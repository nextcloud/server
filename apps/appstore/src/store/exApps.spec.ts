/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { IAppstoreExApp, IDeployDaemon } from '../apps.d.ts'

import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, test, vi } from 'vitest'
import * as exAppApi from '../service/exAppApi.ts'
import { useExAppsStore } from './exApps.ts'

vi.mock('@nextcloud/initial-state', () => ({ loadState: (_app: string, _key: string, fallback: unknown) => fallback }))
vi.mock('@nextcloud/vue', () => ({ spawnDialog: vi.fn() }))
vi.mock('../service/exAppApi.ts', () => ({
	enableExApp: vi.fn(),
	fetchAppStatus: vi.fn(() => Promise.resolve({})),
}))

const daemon = {
	id: 1,
	name: 'harp_proxy_docker',
	display_name: 'HaRP Proxy (Docker)',
	accepts_deploy_id: 'docker-install',
	protocol: 'http',
	host: 'appapi-harp:8780',
	deploy_config: {},
	exAppsCount: 0,
} as unknown as IDeployDaemon

const app = {
	id: 'test_exapp',
	app_api: true,
	installed: false,
	needsDownload: true,
	daemon: undefined,
	status: {},
	releases: [],
} as unknown as IAppstoreExApp

describe('ExApps store: enableApp', () => {
	beforeEach(() => {
		vi.clearAllMocks()
		setActivePinia(createPinia())
	})

	test('deploys on the only docker daemon without asking for one', async () => {
		const store = useExAppsStore()
		store.apps = [structuredClone(app)]
		store.dockerDaemons = [daemon]

		await store.enableApp(app.id)

		expect(exAppApi.enableExApp).toHaveBeenCalledOnce()
		expect(exAppApi.enableExApp).toHaveBeenCalledWith(expect.objectContaining({ id: app.id }), daemon)
		expect(store.getById(app.id)!.daemon).toEqual(daemon)
	})

	test('fails when deploying on the only docker daemon fails', async () => {
		vi.mocked(exAppApi.enableExApp).mockRejectedValueOnce(new Error('Deployment failed'))
		const store = useExAppsStore()
		store.apps = [structuredClone(app)]
		store.dockerDaemons = [daemon]

		await expect(store.enableApp(app.id)).rejects.toThrow('Deployment failed')
		expect(store.getById(app.id)!.loading).toBe(false)
	})
})
