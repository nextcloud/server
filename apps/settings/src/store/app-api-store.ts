/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import axios from '@nextcloud/axios'
import { confirmPassword } from '@nextcloud/password-confirmation'
import { showError, showInfo } from '@nextcloud/dialogs'
import { loadState } from '@nextcloud/initial-state'
import { translate as t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'
import { defineStore } from 'pinia'

import api from './api'
import logger from '../logger'

import type { IAppstoreExApp, IDeployDaemon, IDeployOptions, IExAppStatus } from '../app-types.ts'
import Vue from 'vue'

interface AppApiState {
	apps: IAppstoreExApp[]
	updateCount: number
	loading: Record<string, boolean>
	loadingList: boolean
	statusUpdater: number | null | undefined
	daemonAccessible: boolean
	defaultDaemon: IDeployDaemon | null
}

export const useAppApiStore = defineStore('app-api-apps', {
	state: (): AppApiState => ({
		apps: [],
		updateCount: loadState('settings', 'appstoreExAppUpdateCount', 0),
		loading: {},
		loadingList: false,
		statusUpdater: null,
		daemonAccessible: loadState('settings', 'defaultDaemonConfigAccessible', false),
		defaultDaemon: loadState('settings', 'defaultDaemonConfig', null),
	}),

	getters: {
		getLoading: (state) => (id: string) => state.loading[id] ?? false,
		getAllApps: (state) => state.apps,
		getUpdateCount: (state) => state.updateCount,
		getDaemonAccessible: (state) => state.daemonAccessible,
		getDefaultDaemon: (state) => state.defaultDaemon,
		getAppStatus: (state) => (appId: string) =>
			state.apps.find((app) => app.id === appId)?.status || null,
		getStatusUpdater: (state) => state.statusUpdater,
		getInitializingOrDeployingApps: (state) =>
			state.apps.filter((app) =>
				app?.status?.action
				&& (app?.status?.action === 'deploy' || app.status.action === 'init' || app.status.action === 'healthcheck')
				&& app.status.type !== '',
			),
	},

	actions: {
		// eslint-disable-next-line @typescript-eslint/no-explicit-any
		appsApiFailure(error: any) {
			showError(t('settings', 'An error occurred during the request. Unable to proceed.') + '<br>' + error.error.response.data.data.message, { isHTML: true })
			logger.error(error)
		},

		setLoading(id: string, value: boolean) {
			Vue.set(this.loading, id, value)
		},

		setError(appId: string | string[], error: string) {
			const appIds = Array.isArray(appId) ? appId : [appId]
			appIds.forEach((_id) => {
				const app = this.apps.find((app) => app.id === _id)
				if (app) {
					app.error = error
				}
			})
		},

		enableApp(appId: string, deployOptions: IDeployOptions[] = []) {
			this.setLoading(appId, true)
			this.setLoading('install', true)
			return confirmPassword().then(() => {

				return axios.post(generateUrl(`/apps/app_api/apps/enable/${appId}`), { deployOptions })
					.then((response) => {
						this.setLoading(appId, false)
						this.setLoading('install', false)

						const app = this.apps.find((app) => app.id === appId)
						if (app) {
							if (!app.installed) {
								app.installed = true
								app.needsDownload = false
								app.daemon = this.defaultDaemon
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

		updateApp(appId: string) {
			this.setLoading(appId, true)
			this.setLoading('install', true)
			return confirmPassword().then(() => {

				return api.get(generateUrl(`/apps/app_api/apps/update/${appId}`))
					.then(() => {
						this.setLoading(appId, false)
						this.setLoading('install', false)
						const app = this.apps.find((app) => app.id === appId)
						if (app) {
							const version = app.update
							app.update = undefined
							app.version = version || app.version
							app.status = {
								type: 'update',
								action: 'deploy',
								init: 0,
								deploy: 0,
							} as IExAppStatus
							app.error = ''
						}
						this.updateCount--
						this.updateAppsStatus()
						return true
					})
					.catch((error) => {
						this.setLoading(appId, false)
						this.setLoading('install', false)
						this.appsApiFailure({ appId, error })
					})
			}).catch(() => {
				this.setLoading(appId, false)
				this.setLoading('install', false)
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
					console.debug('initializingOrDeployingApps after setAppStatus', initializingOrDeployingApps)
					if (initializingOrDeployingApps.length === 0) {
						console.debug('clearing interval')
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
				console.debug('initializingOrDeployingApps', initializingOrDeployingApps)
				initializingOrDeployingApps.forEach(app => {
					this.fetchAppStatus(app.id)
				})
			}, 2000) as unknown as number
		},
	},
})
