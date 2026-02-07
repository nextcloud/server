/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { IAppstoreExApp, IDeployDaemon, IExAppStatus } from '../apps.d.ts'

import axios from '@nextcloud/axios'
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

	const statusUpdater = ref<number | null | undefined>(null)
	const daemonAccessible = ref(loadState('appstore', 'defaultDaemonConfigAccessible', false))
	const defaultDaemon = ref(loadState<IDeployDaemon | null>('appstore', 'defaultDaemonConfig', null))
	const dockerDaemons = ref<IDeployDaemon[]>([])

	const initializingOrDeployingApps = computed(() => apps.value.filter((app) => app?.status?.action
		&& (app?.status?.action === 'deploy' || app.status.action === 'init' || app.status.action === 'healthcheck')
		&& app.status.type !== ''))

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
				exAppApi.enableExApp(app, dockerDaemons[0])
				app.daemon = dockerDaemons[0]
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
		} finally {
			app.loading = false
		}
	}

	/**
	 * Uninstall an app by its id
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
			await exAppApi.disableExApp(appId)
			app.active = false
			app.needsDownload = true
			app.installed = false
			app.daemon = null
			app.status = {}
			if (app.update !== null) {
				updateCount.value--
			}
			delete app.update
			delete app.error
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
			await axios.get(generateUrl(`/apps/app_api/apps/update/${appId}`))
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
			// Trigger status updates
			// updateAppsStatus()
		} catch (error) {
			logger.error('Failed to update ex app', { appId, error })
			showError(t('appstore', 'Could not update the app. Please try again later.'))
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
		} catch (error) {
			logger.error('An error occurred while fetching apps', { error })
			showError(t('appstore', 'An error occurred during the request. Unable to proceed.'))
		}
	}

	/*
		async fetchAppStatus(appId: string) {
			return api.get(generateUrl(`/apps/app_api/apps/status/${appId}`))
				.then((response) => {
					const app = this.apps.find((app) => app.id === appId)
					if (app) {
						app.status = response.data
					}
					const initializingOrDeployingApps = this.getInitializingOrDeployingApps
					logger.debug('initializingOrDeployingApps after setAppStatus', { initializingOrDeployingApps })
					if (initializingOrDeployingApps.length === 0) {
						logger.debug('clearing interval')
						clearInterval(this.statusUpdater as number)
						this.statusUpdater = null
					}
					if (Object.hasOwn(response.data, 'error')
						&& response.data.error !== ''
						&& initializingOrDeployingApps.length === 1) {
						clearInterval(this.statusUpdater as number)
						this.statusUpdater = null
					}
				})
				.catch((error) => {
					this.appsApiFailure({ appId, error })
					this.apps = this.apps.filter((app) => app.id !== appId)
					this.updateAppsStatus()
				})
		},

		updateAppsStatus() {
			clearInterval(this.statusUpdater as number)
			const initializingOrDeployingApps = this.getInitializingOrDeployingApps
			if (initializingOrDeployingApps.length === 0) {
				return
			}
			this.statusUpdater = setInterval(() => {
				const initializingOrDeployingApps = this.getInitializingOrDeployingApps
				logger.debug('initializingOrDeployingApps', { initializingOrDeployingApps })
				initializingOrDeployingApps.forEach((app) => {
					this.fetchAppStatus(app.id)
				})
			}, 2000) as unknown as number
		},
	}, */
})
