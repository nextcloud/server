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
import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import logger from '../utils/logger.ts'

export const useExAppsStore = defineStore('external-apps', () => {
	/**
	 * Is the App API enabled
	 */
	const isEnabled = loadState('settings', 'appApiEnabled', false)

	const apps = ref<IAppstoreExApp[]>([])
	const updateCount = ref(loadState('settings', 'appstoreExAppUpdateCount', 0))
	const loading = ref<Record<string, boolean>>({})
	const loadingList = ref(false)
	const statusUpdater = ref<number | null | undefined>(null)
	const daemonAccessible = ref(loadState('settings', 'defaultDaemonConfigAccessible', false))
	const defaultDaemon = ref(loadState<IDeployDaemon | null>('settings', 'defaultDaemonConfig', null))
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
			showError(t('settings', 'Could not update the app. Please try again later.'))
		} finally {
			app.loading = false
		}
	}

	return {
		isEnabled,

		apps,
		updateCount,
		defaultDaemon,
		dockerDaemons,

		getById,
		updateApp,
	}

	/* enableApp(appId: string, daemon: IDeployDaemon, deployOptions: IDeployOptions) {
			this.setLoading(appId, true)
			this.setLoading('install', true)
			return confirmPassword().then(() => {
				return axios.post(generateUrl(`/apps/app_api/apps/enable/${appId}/${daemon.name}`), { deployOptions })
					.then((response) => {
						this.setLoading(appId, false)
						this.setLoading('install', false)

						const app = this.apps.find((app) => app.id === appId)
						if (app) {
							if (!app.installed) {
								app.installed = true
								app.needsDownload = false
								app.daemon = daemon
								app.status = {
									type: 'install',
									action: 'deploy',
									init: 0,
									deploy: 0,
								} as IExAppStatus
							}
							app.active = true
							app.canUnInstall = false
							app.removable = true
							app.error = ''
						}

						this.updateAppsStatus()

						return axios.get(generateUrl('apps/files'))
							.then(() => {
								if (response.data.update_required) {
									showInfo(
										t('settings', 'The app has been enabled but needs to be updated.'),
										{
											onClick: () => window.location.reload(),
											close: false,
										},
									)
									setTimeout(() => {
										location.reload()
									}, 5000)
								}
							})
							.catch(() => {
								this.setError(appId, t('settings', 'Error: This app cannot be enabled because it makes the server unstable'))
							})
					})
					.catch((error) => {
						this.setLoading(appId, false)
						this.setLoading('install', false)
						this.setError(appId, error.response.data.data.message)
						this.appsApiFailure({ appId, error })
					})
			}).catch(() => {
				this.setLoading(appId, false)
				this.setLoading('install', false)
			})
		},

		forceEnableApp(appId: string) {
			this.setLoading(appId, true)
			this.setLoading('install', true)
			return confirmPassword().then(() => {
				return api.post(generateUrl('/apps/app_api/apps/force'), { appId })
					.then(() => {
						location.reload()
					})
					.catch((error) => {
						this.setLoading(appId, false)
						this.setLoading('install', false)
						this.setError(appId, error.response.data.data.message)
						this.appsApiFailure({ appId, error })
					})
			}).catch(() => {
				this.setLoading(appId, false)
				this.setLoading('install', false)
			})
		},

		disableApp(appId: string) {
			this.setLoading(appId, true)
			return confirmPassword().then(() => {
				return api.get(generateUrl(`apps/app_api/apps/disable/${appId}`))
					.then(() => {
						this.setLoading(appId, false)
						const app = this.apps.find((app) => app.id === appId)
						if (app) {
							app.active = false
							if (app.removable) {
								app.canUnInstall = true
							}
						}
						return true
					})
					.catch((error) => {
						this.setLoading(appId, false)
						this.appsApiFailure({ appId, error })
					})
			}).catch(() => {
				this.setLoading(appId, false)
			})
		},

		uninstallApp(appId: string, removeData: boolean) {
			this.setLoading(appId, true)
			return confirmPassword().then(() => {
				return api.get(generateUrl(`/apps/app_api/apps/uninstall/${appId}?removeData=${removeData}`))
					.then(() => {
						this.setLoading(appId, false)
						const app = this.apps.find((app) => app.id === appId)
						if (app) {
							app.active = false
							app.needsDownload = true
							app.installed = false
							app.canUnInstall = false
							app.canInstall = true
							app.daemon = null
							app.status = {}
							if (app.update !== null) {
								this.updateCount--
							}
							app.update = undefined
						}
						return true
					})
					.catch((error) => {
						this.setLoading(appId, false)
						this.appsApiFailure({ appId, error })
					})
			})
		},

		async fetchAllApps() {
			this.loadingList = true
			try {
				const response = await api.get(generateUrl('/apps/app_api/apps/list'))
				this.apps = response.data.apps
				this.loadingList = false
				return true
			} catch (error) {
				logger.error(error as string)
				showError(t('settings', 'An error occurred during the request. Unable to proceed.'))
				this.loadingList = false
			}
		},

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

		async fetchDockerDaemons() {
			try {
				const { data } = await axios.get(generateUrl('/apps/app_api/daemons'))
				this.defaultDaemon = data.daemons.find((daemon: IDeployDaemon) => daemon.name === data.default_daemon_config)
				this.dockerDaemons = data.daemons.filter((daemon: IDeployDaemon) => daemon.accepts_deploy_id === 'docker-install')
			} catch (error) {
				logger.error('[app-api-store] Failed to fetch Docker daemons', { error })
				return false
			}
			return true
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
