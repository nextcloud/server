/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { IAppstoreExApp, IDeployDaemon, IExAppStatus } from '../apps.d.ts'

import { createPinia, setActivePinia } from 'pinia'
import { afterEach, beforeEach, describe, expect, test, vi } from 'vitest'

const axios = vi.hoisted(() => ({ get: vi.fn(), post: vi.fn() }))
vi.mock('@nextcloud/axios', async (importActual) => ({
	...(await importActual<object>()),
	default: axios,
}))

const showError = vi.hoisted(() => vi.fn())
vi.mock('@nextcloud/dialogs', () => ({ showError }))

const spawnDialog = vi.hoisted(() => vi.fn())
vi.mock('@nextcloud/vue', () => ({ spawnDialog }))
vi.mock('../components/DaemonSelectionDialog/DaemonSelectionDialog.vue', () => ({ default: {} }))

const confirmPassword = vi.hoisted(() => vi.fn())
vi.mock('@nextcloud/password-confirmation', () => ({ confirmPassword }))

const { useExAppsStore } = await import('./exApps.ts')

const harpDaemon = daemon('harp', 'docker-install', { frp_address: 'appapi-harp:8782' })
const k8sDaemon = daemon('k8s', 'kubernetes-install', { exapp_direct: true })
const dspDaemon = daemon('dsp', 'docker-install')
const manualDaemon = daemon('manual', 'manual-install', null)

const deployingStatus: IExAppStatus = { type: 'install', action: 'deploy', deploy: 50, init: 0 }
const initializingStatus: IExAppStatus = { type: 'install', action: 'init', deploy: 100, init: 50 }
const deployedStatus: IExAppStatus = { type: '', action: '', deploy: 100, init: 100 }
// AppAPI keeps `init` of the previous run until the new deployment is initialized
const updatingStatus: IExAppStatus = { type: 'update', action: 'deploy', deploy: 50, init: 100 }

let daemons: IDeployDaemon[]
let apps: IAppstoreExApp[]
let statuses: (IExAppStatus | Error)[]
let statusDelay: number
let failures: Record<string, Error>

beforeEach(() => {
	vi.resetAllMocks()
	setActivePinia(createPinia())

	daemons = [harpDaemon]
	apps = [exApp()]
	statuses = [deployedStatus]
	statusDelay = 0
	failures = {}
	axios.get.mockImplementation(async (url: string) => {
		throwFailure(url)
		if (url.endsWith('/apps/app_api/daemons')) {
			return { data: { daemons, default_daemon_config: 'harp' } }
		}
		if (url.endsWith('/apps/app_api/apps/list')) {
			return { data: { apps } }
		}
		if (url.includes('/apps/app_api/apps/status/')) {
			const status = statuses.length > 1 ? statuses.shift()! : statuses[0]!
			if (statusDelay) {
				await new Promise((resolve) => setTimeout(resolve, statusDelay))
			}
			if (status instanceof Error) {
				throw status
			}
			return { data: status }
		}
		return { data: {} }
	})
	axios.post.mockImplementation(async (url: string) => {
		throwFailure(url)
		return { data: {} }
	})
})

afterEach(() => {
	vi.useRealTimers()
})

describe('deploy daemons', () => {
	test('offers HaRP daemons on Docker and Kubernetes only', async () => {
		daemons = [manualDaemon, dspDaemon, harpDaemon, k8sDaemon]
		const store = useExAppsStore()
		await store.initialize()

		expect(store.deployDaemons.map(({ name }) => name)).toEqual(['harp', 'k8s'])
		expect(store.defaultDaemon?.name).toBe('harp')
	})

	test.each([{ harp: null }, { harp: [] }])('does not offer a Docker daemon with HaRP settings $harp', async ({ harp }) => {
		daemons = [daemon('empty', 'docker-install', harp), harpDaemon]
		const store = useExAppsStore()
		await store.initialize()

		expect(store.deployDaemons.map(({ name }) => name)).toEqual(['harp'])
	})
})

describe('installing an ExApp', () => {
	test('uses the only deploy daemon without asking', async () => {
		daemons = [manualDaemon, dspDaemon, harpDaemon]
		const store = useExAppsStore()
		await store.initialize()

		await store.enableApp('test_app')

		expect(spawnDialog).not.toHaveBeenCalled()
		expect(axios.post).toHaveBeenCalledWith('/index.php/apps/app_api/apps/enable/test_app/harp', { deployOptions: undefined })
		expect(store.getById('test_app')!.daemon).toEqual(harpDaemon)
	})

	test('asks for the daemon when several can be used', async () => {
		daemons = [harpDaemon, k8sDaemon]
		spawnDialog.mockResolvedValue(k8sDaemon)
		const store = useExAppsStore()
		await store.initialize()

		await store.enableApp('test_app')

		expect(spawnDialog).toHaveBeenCalledOnce()
		expect(axios.post).toHaveBeenCalledWith('/index.php/apps/app_api/apps/enable/test_app/k8s', { deployOptions: undefined })
		expect(store.getById('test_app')!.daemon).toEqual(k8sDaemon)
	})

	test('does not show an error when no daemon was chosen', async () => {
		daemons = [harpDaemon, k8sDaemon]
		spawnDialog.mockResolvedValue(undefined)
		const store = useExAppsStore()
		await store.initialize()

		await expect(store.enableApp('test_app')).rejects.toThrow()

		expect(axios.post).not.toHaveBeenCalled()
		expect(showError).not.toHaveBeenCalled()
	})

	test('shows the error message sent by AppAPI', async () => {
		const message = 'Docker Socket Proxy daemons are no longer supported, use a HaRP daemon.'
		failures = { '/apps/app_api/apps/enable/': requestError(500, { data: { message } }) }
		const store = useExAppsStore()
		await store.initialize()

		await expect(store.enableApp('test_app')).rejects.toThrow()

		expect(showError).toHaveBeenCalledWith(message)
		expect(store.getById('test_app')!.needsDownload).toBe(true)
	})

	test('polls the status until the app is deployed', async () => {
		vi.useFakeTimers()
		statuses = [deployingStatus, initializingStatus, deployedStatus]
		const store = useExAppsStore()
		await store.initialize()

		await store.enableApp('test_app')
		expect(store.getById('test_app')!.status.action).toBe('deploy')

		await vi.advanceTimersByTimeAsync(2000)
		expect(store.getById('test_app')!.status.action).toBe('init')

		await vi.advanceTimersByTimeAsync(2000)
		expect(store.getById('test_app')!.status).toEqual(deployedStatus)

		await vi.advanceTimersByTimeAsync(10000)
		expect(statusRequests()).toBe(3)
	})

	test('fails when AppAPI removed the app right after the install request', async () => {
		statuses = [requestError(404, { error: 'ExApp not found, failed to get status' })]
		const store = useExAppsStore()
		await store.initialize()

		await expect(store.enableApp('test_app')).rejects.toThrow()

		expect(store.getById('test_app')!.needsDownload).toBe(true)
		expect(showError).toHaveBeenCalledExactlyOnceWith('ExApp not found, failed to get status')
	})

	test('stops polling when AppAPI removed the app after a failed deployment', async () => {
		vi.useFakeTimers()
		statuses = [
			deployingStatus,
			requestError(404, { error: 'ExApp not found, failed to get status' }),
		]
		const store = useExAppsStore()
		await store.initialize()

		await store.enableApp('test_app')
		await vi.advanceTimersByTimeAsync(2000)

		const app = store.getById('test_app')!
		expect(app.installed).toBe(false)
		expect(app.needsDownload).toBe(true)
		expect(app.status).toEqual({})
		expect(showError).toHaveBeenCalledExactlyOnceWith('ExApp not found, failed to get status')
		expect(vi.getTimerCount()).toBe(0)

		await vi.advanceTimersByTimeAsync(10000)
		expect(statusRequests()).toBe(2)
	})
})

describe('loading the ExApps', () => {
	test('polls the status of an app that is being deployed and enables it when done', async () => {
		vi.useFakeTimers()
		apps = [installedExApp({ active: false, status: deployingStatus })]
		statuses = [initializingStatus, deployedStatus]
		const store = useExAppsStore()
		await store.initialize()
		expect(statusRequests()).toBe(0)

		await vi.advanceTimersByTimeAsync(2000)
		expect(store.getById('test_app')!.status.action).toBe('init')
		expect(store.getById('test_app')!.active).toBe(false)

		await vi.advanceTimersByTimeAsync(2000)
		expect(store.getById('test_app')!.status).toEqual(deployedStatus)
		expect(store.getById('test_app')!.active).toBe(true)

		await vi.advanceTimersByTimeAsync(10000)
		expect(statusRequests()).toBe(2)
	})

	test('keeps an app disabled while its update is deployed', async () => {
		vi.useFakeTimers()
		apps = [installedExApp({ active: false, status: updatingStatus })]
		statuses = [{ ...updatingStatus, deploy: 80 }]
		const store = useExAppsStore()
		await store.initialize()

		await vi.advanceTimersByTimeAsync(2000)

		expect(store.getById('test_app')!.status.deploy).toBe(80)
		expect(store.getById('test_app')!.active).toBe(false)
	})

	test('does not poll the status of an app whose deployment failed', async () => {
		vi.useFakeTimers()
		apps = [installedExApp({ active: false, status: { ...deployingStatus, error: 'Image pull failed' } })]
		const store = useExAppsStore()
		await store.initialize()

		await vi.advanceTimersByTimeAsync(10000)
		expect(statusRequests()).toBe(0)
	})

	test('waits for a slow status answer before asking again', async () => {
		vi.useFakeTimers()
		apps = [installedExApp({ active: false, status: deployingStatus })]
		statusDelay = 3000
		const store = useExAppsStore()
		await store.initialize()

		await vi.advanceTimersByTimeAsync(4000)
		expect(statusRequests()).toBe(1)
		expect(store.getById('test_app')!.loading).toBeFalsy()

		await vi.advanceTimersByTimeAsync(1000)
		expect(store.getById('test_app')!.status).toEqual(deployedStatus)
		await vi.advanceTimersByTimeAsync(10000)
		expect(statusRequests()).toBe(1)
	})
})

describe('updating an ExApp', () => {
	test('confirms the password and polls the status of the update', async () => {
		vi.useFakeTimers()
		apps = [installedExApp({ active: true, update: '2.0.0' })]
		statuses = [updatingStatus, deployedStatus]
		const store = useExAppsStore()
		store.updateCount = 1
		await store.initialize()

		await store.updateApp('test_app')

		const updateRequest = axios.get.mock.calls.findIndex(([url]) => url.endsWith('/apps/app_api/apps/update/test_app'))
		expect(confirmPassword.mock.invocationCallOrder[0]).toBeLessThan(axios.get.mock.invocationCallOrder[updateRequest]!)
		expect(store.getById('test_app')!.update).toBeUndefined()
		expect(store.updateCount).toBe(0)

		await vi.advanceTimersByTimeAsync(10000)
		expect(store.getById('test_app')!.status).toEqual(deployedStatus)
		expect(statusRequests()).toBe(2)
	})

	test('keeps a disabled app disabled when its update failed early', async () => {
		apps = [installedExApp({ update: '2.0.0' })]
		statuses = [{ type: 'update', action: '', deploy: 100, init: 100, error: 'Failed to update info' }]
		const store = useExAppsStore()
		await store.initialize()

		await store.updateApp('test_app')

		expect(store.getById('test_app')!.active).toBe(false)
	})

	test('shows the error message sent by AppAPI and keeps the update', async () => {
		const message = 'Could not update ExApp'
		apps = [installedExApp({ active: true, update: '2.0.0' })]
		failures = { '/apps/app_api/apps/update/': requestError(500, { data: { message } }) }
		const store = useExAppsStore()
		store.updateCount = 1
		await store.initialize()

		await expect(store.updateApp('test_app')).rejects.toThrow()

		expect(showError).toHaveBeenCalledExactlyOnceWith(message)
		expect(store.getById('test_app')!.update).toBe('2.0.0')
		expect(store.updateCount).toBe(1)
	})
})

describe('managing an installed ExApp', () => {
	test.each([
		['disable', '/apps/app_api/apps/disable/', true],
		['uninstall', '/apps/app_api/apps/uninstall/', false],
		['forceEnable', '/apps/app_api/apps/force', false],
	] as const)('%s shows the error message sent by AppAPI', async (operation, url, active) => {
		const message = 'AppAPI could not do it'
		apps = [installedExApp({ active })]
		failures = { [url]: requestError(500, { data: { message } }) }
		const store = useExAppsStore()
		await store.initialize()

		const run = {
			disable: () => store.disableApp('test_app'),
			uninstall: () => store.uninstallApp('test_app'),
			forceEnable: () => store.forceEnableApp('test_app'),
		}[operation]
		await expect(run()).rejects.toThrow()

		expect(showError).toHaveBeenCalledExactlyOnceWith(message)
	})
})

describe('uninstalling an ExApp', () => {
	test('uninstalls the app and keeps its data', async () => {
		apps = [installedExApp()]
		const store = useExAppsStore()
		await store.initialize()

		await store.uninstallApp('test_app')

		expect(axios.get).toHaveBeenCalledWith('/index.php/apps/app_api/apps/uninstall/test_app?removeData=false')
		const app = store.getById('test_app')!
		expect(app.installed).toBe(false)
		expect(app.needsDownload).toBe(true)
		expect(app.daemon).toBeNull()
		expect(store.updateCount).toBe(0)
	})

	test('drops a pending update from the update count', async () => {
		apps = [installedExApp({ update: '2.0.0' })]
		const store = useExAppsStore()
		store.updateCount = 1
		await store.initialize()

		await store.uninstallApp('test_app')

		expect(store.updateCount).toBe(0)
		expect(store.getById('test_app')!.update).toBeUndefined()
	})

	test('removes an ExApp that is not listed in the app store', async () => {
		apps = [installedExApp({ appstore: false, update: null })]
		const store = useExAppsStore()
		await store.initialize()

		await store.uninstallApp('test_app')

		expect(store.getById('test_app')).toBeNull()
		expect(store.updateCount).toBe(0)
	})
})

/**
 * Create a daemon as AppAPI lists it.
 *
 * @param name - The daemon name
 * @param acceptsDeployId - The deploy type of the daemon
 * @param harp - The HaRP settings, if any
 */
function daemon(name: string, acceptsDeployId: string, harp?: IDeployDaemon['deploy_config']['harp']): IDeployDaemon {
	return {
		id: 1,
		name,
		display_name: name,
		accepts_deploy_id: acceptsDeployId,
		protocol: 'http',
		host: 'localhost',
		exAppsCount: 0,
		deploy_config: {
			computeDevice: { id: 'cpu', label: 'CPU' },
			net: 'host',
			nextcloud_url: 'http://nextcloud.local',
			...(harp === undefined ? {} : { harp }),
		},
	}
}

/**
 * Create an ExApp of the app store that is not installed.
 *
 * @param data - Values to override
 */
function exApp(data: Partial<IAppstoreExApp> = {}): IAppstoreExApp {
	return {
		id: 'test_app',
		name: 'Test app',
		app_api: true,
		appstore: true,
		active: false,
		installed: false,
		needsDownload: true,
		removable: false,
		isCompatible: true,
		daemon: null,
		status: {},
		releases: [],
		...data,
	} as IAppstoreExApp
}

/**
 * Create an ExApp that is installed and disabled.
 *
 * @param data - Values to override
 */
function installedExApp(data: Partial<IAppstoreExApp> = {}): IAppstoreExApp {
	return exApp({
		installed: true,
		needsDownload: false,
		removable: true,
		daemon: harpDaemon,
		status: deployedStatus,
		...data,
	})
}

/**
 * Create the error axios throws for a failed request.
 *
 * @param status - The HTTP status code
 * @param data - The response body
 */
function requestError(status: number, data: unknown) {
	return Object.assign(new Error(`Request failed with status code ${status}`), {
		isAxiosError: true,
		response: { status, data },
	})
}

/**
 * Throw the configured failure for a request URL, if any.
 *
 * @param url - The requested URL
 */
function throwFailure(url: string) {
	const failure = Object.entries(failures).find(([fragment]) => url.includes(fragment))
	if (failure) {
		throw failure[1]
	}
}

/**
 * Number of requests for the status of the test app.
 */
function statusRequests() {
	return axios.get.mock.calls.filter(([url]) => url.endsWith('/apps/app_api/apps/status/test_app')).length
}
