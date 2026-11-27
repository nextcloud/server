/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { IAppstoreExApp, IDeployDaemon, IExAppStatus } from '../apps.d.ts'

import axios, { isAxiosError } from '@nextcloud/axios'
import { showError } from '@nextcloud/dialogs'
import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'
import { spawnDialog } from '@nextcloud/vue'
import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import DaemonSelectionDialog from '../components/DaemonSelectionDialog/DaemonSelectionDialog.vue'
import * as exAppApi from '../service/exAppApi.ts'
import logger from '../utils/logger.ts'

export const useExAppsStore = defineStore('external-apps', () => {
	/**
	 * Is the App API enabled
	 */
	const isEnabled = loadState('appstore', 'appApiEnabled', false)

	/**
	 * All external apps available
	 */
	const apps = ref<IAppstoreExApp[]>([])

	/**
	 * Number of external apps with available updates, used to show the update badge in the UI
	 */
	const updateCount = ref(loadState('appstore', 'appstoreExAppUpdateCount', 0))

	/**
	 * The interval ID for the status updater
	 */
	let statusUpdater: number | null = null

	/**
	 * IDs of the apps whose status is being fetched
	 */
	const pendingStatusRequests = new Set<string>()

	/**
	 * Whether at least one of the configured daemons is accessible.
	 */
	const daemonAccessible = ref(loadState('appstore', 'defaultDaemonConfigAccessible', false))

	/**
	 * The default daemon, used for apps that don't specify a daemon or have a daemon that is not accessible.
	 */
	const defaultDaemon = ref(loadState<IDeployDaemon | null>('appstore', 'defaultDaemonConfig', null))

	/**
	 * The list of daemons that support docker-based deployment, used to show the daemon selection when enabling an app.
	 */
	const dockerDaemons = ref<IDeployDaemon[]>([])

	const initializingOrDeployingApps = computed(() => apps.value
		.filter((app) => app?.status?.action
			&& app.status.type !== ''
			&& !app.status.error
			&& (app?.status?.action === 'deploy' || app.status.action === 'init' || app.status.action === 'healthcheck')))

	/**
	 * Get an external app by its ID
	 *
	 * @param appId - The app ID
	 */
	function getById(appId: string): IAppstoreExApp | null {
		return apps.value.find(({ id }) => id === appId) ?? null
	}

	/**
	 * Enable an exApp.
	 *
	 * @param appId - The app ID
	 */
	async function enableApp(appId: string) {
		const app = getById(appId)
		if (!app) {
			throw new Error(`App with id ${appId} not found`)
		}

		app.loading = true
		try {
			if (dockerDaemons.value.length === 1 && app.needsDownload) {
				const daemon = dockerDaemons.value[0]!
				await exAppApi.enableExApp(app, daemon)
				app.daemon = daemon
			} else if (app.needsDownload) {
				const daemon = await spawnDialog(DaemonSelectionDialog, { app })
				if (!daemon) {
					throw new Error('No daemon selected')
				}
				await exAppApi.enableExApp(app, daemon)
				app.daemon = daemon
			} else {
				await exAppApi.enableExApp(app, app.daemon!)
			}

			if (!app.installed) {
				app.needsDownload = false
				app.status = {
					type: 'install',
					action: 'deploy',
					init: 0,
					deploy: 0,
				} as IExAppStatus
			}
			app.removable = true
			delete app.error

			if (!(await fetchAppStatus(appId))) {
				throw new Error(`App with id ${appId} is not registered`)
			}
			updateAppsStatus()
		} catch (error) {
			showRequestError(error)
			throw error
		} finally {
			app.loading = false
		}
	}

	/**
	 * Force enable an exApp by ignoring its dependencies.
	 *
	 * @param appId - The app to force-enable
	 */
	async function forceEnableApp(appId: string) {
		const app = getById(appId)
		if (!app) {
			throw new Error(`App with id ${appId} not found`)
		}

		app.loading = true
		try {
			await exAppApi.forceEnableExApp(appId)
			await initialize(true)
			app.active = false
		} catch (error) {
			showRequestError(error)
			throw error
		} finally {
			app.loading = false
		}
	}

	/**
	 * @param appId - The app to disable
	 */
	async function disableApp(appId: string) {
		const app = getById(appId)
		if (!app) {
			throw new Error(`App with id ${appId} not found`)
		}

		app.loading = true
		try {
			await exAppApi.disableExApp(appId)
			app.active = false
		} catch (error) {
			showRequestError(error)
			throw error
		} finally {
			app.loading = false
		}
	}

	/**
	 * Uninstall an app by its id, its data is kept
	 *
	 * @param appId - The app to uninstall
	 */
	async function uninstallApp(appId: string) {
		const app = getById(appId)
		if (!app) {
			throw new Error(`App with id ${appId} not found`)
		}

		app.loading = true
		try {
			await exAppApi.uninstallExApp(appId)
			forgetInstallation(app)
		} catch (error) {
			showRequestError(error)
			throw error
		} finally {
			app.loading = false
		}
	}

	/**
	 * Update an external app
	 *
	 * @param appId - The app ID
	 */
	async function updateApp(appId: string) {
		const app = getById(appId)
		if (!app) {
			throw new Error(`App with id ${appId} not found`)
		}

		app.loading = true
		try {
			await exAppApi.updateExApp(appId)
			app.version = app.update || app.version
			app.status = {
				type: 'update',
				action: 'deploy',
				init: 0,
				deploy: 0,
			} satisfies IExAppStatus
			delete app.update
			delete app.error
			updateCount.value--

			await fetchAppStatus(appId)
			updateAppsStatus()
		} catch (error) {
			logger.error('Failed to update ex app', { appId, error })
			showRequestError(error, t('appstore', 'Could not update the app. Please try again later.'))
			throw error
		} finally {
			app.loading = false
		}
	}

	/**
	 * Initialize the store.
	 * This only needs to be called when an app management operation is performed.
	 *
	 * @param force - If the initialization should be forced (to run again)
	 */
	async function initialize(force = false) {
		if (force || (!defaultDaemon.value || !dockerDaemons.value.length)) {
			await fetchDockerDaemons()
		}
		if (force || apps.value.length === 0) {
			await fetchAllApps()
		}
	}

	return {
		isEnabled,

		apps,
		updateCount,
		defaultDaemon,
		dockerDaemons,
		daemonAccessible,

		getById,
		disableApp,
		enableApp,
		forceEnableApp,
		updateApp,
		uninstallApp,
		initialize,
	}

	/**
	 * Fetch the configured docker daemons from the backend.
	 */
	async function fetchDockerDaemons() {
		try {
			const { data } = await axios.get(generateUrl('/apps/app_api/daemons'))
			defaultDaemon.value = data.daemons.find((daemon: IDeployDaemon) => daemon.name === data.default_daemon_config)
			dockerDaemons.value = data.daemons.filter((daemon: IDeployDaemon) => daemon.accepts_deploy_id === 'docker-install')
		} catch (error) {
			logger.error('[app-api-store] Failed to fetch Docker daemons', { error })
			return false
		}
		return true
	}

	/**
	 * Fetch the list of external apps from the backend.
	 */
	async function fetchAllApps() {
		try {
			apps.value = await exAppApi.fetchApps()
			updateAppsStatus()
		} catch (error) {
			logger.error('An error occurred while fetching apps', { error })
			showError(t('appstore', 'An error occurred during the request. Unable to proceed.'))
		}
	}

	/**
	 * Get the status of an external app.
	 *
	 * @param appId - The app ID to fetch the status for
	 * @return Whether the app is still registered in AppAPI
	 */
	async function fetchAppStatus(appId: string): Promise<boolean> {
		const app = getById(appId)
		if (!app) {
			logger.error('[app-api-store] app not found while fetching status', { appId })
			return false
		}

		pendingStatusRequests.add(appId)
		try {
			const status = await exAppApi.fetchAppStatus(appId)
			app.status = status
			if (status.init === 100 && !status.action && !status.error) {
				// AppAPI enables an ExApp once its initialization finished
				app.active = true
			}
			logger.debug('[app-api-store] initializingOrDeployingApps after setAppStatus', { initializingOrDeployingApps })
			if (initializingOrDeployingApps.value.length === 0) {
				logger.debug('[app-api-store] Clearing interval')
				clearInterval(statusUpdater as number)
				statusUpdater = null
			}
		} catch (error) {
			logger.error('[app-api-store] Failed to fetch app status', { appId, error })
			if (isAxiosError(error) && error.response?.status === 404) {
				// AppAPI unregisters an ExApp whose deployment failed
				forgetInstallation(app)
				showRequestError(error)
				updateAppsStatus()
				return false
			}
		} finally {
			pendingStatusRequests.delete(appId)
		}
		return true
	}

	/**
	 * Update the status of all apps that are currently initializing or deploying
	 */
	function updateAppsStatus() {
		clearInterval(statusUpdater as number)
		statusUpdater = null
		if (initializingOrDeployingApps.value.length === 0) {
			return
		}

		statusUpdater = window.setInterval(() => {
			logger.debug('[app-api-store] initializingOrDeployingApps', { initializingOrDeployingApps })
			for (const app of initializingOrDeployingApps.value) {
				if (!pendingStatusRequests.has(app.id)) {
					fetchAppStatus(app.id)
				}
			}
		}, 2000)
	}

	/**
	 * Reset an app that is no longer registered in AppAPI to its not installed state.
	 * Apps that are not listed in the app store are removed from the list.
	 *
	 * @param app - The app that was unregistered
	 */
	function forgetInstallation(app: IAppstoreExApp) {
		if (app.update) {
			updateCount.value--
		}
		if (!app.appstore) {
			apps.value = apps.value.filter(({ id }) => id !== app.id)
			return
		}
		app.active = false
		app.needsDownload = true
		app.installed = false
		app.daemon = null
		app.status = {}
		delete app.update
		delete app.error
	}

	/**
	 * Show the error message AppAPI sent for a failed request.
	 * Errors that are not request errors (e.g. a cancelled dialog) are not shown.
	 *
	 * @param error - The error to show
	 * @param fallback - The message to show if AppAPI sent none
	 */
	function showRequestError(error: unknown, fallback = t('appstore', 'An error occurred during the request. Unable to proceed.')) {
		if (isAxiosError(error)) {
			showError(getRequestErrorMessage(error) ?? fallback)
		}
	}
})

/**
 * Get the error message AppAPI sent with a failed request, if any.
 * AppAPI answers with `{ data: { message } }` or, for the status of an ExApp, with `{ error }`.
 *
 * @param error - The error thrown by the request
 */
function getRequestErrorMessage(error: unknown): string | undefined {
	if (isAxiosError(error)) {
		return error.response?.data?.data?.message ?? error.response?.data?.error
	}
	return undefined
}
