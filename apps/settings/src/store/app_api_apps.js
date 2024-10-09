/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import api from './api.js'
import Vue from 'vue'
import { generateUrl } from '@nextcloud/router'
import { showError, showInfo } from '@nextcloud/dialogs'
import { loadState } from '@nextcloud/initial-state'

const state = {
	apps: [],
	categories: [],
	updateCount: loadState('settings', 'appstoreExAppUpdateCount', 0),
	loading: {},
	loadingList: false,
	statusUpdater: null,
	gettingCategoriesPromise: null,
	daemonAccessible: loadState('settings', 'defaultDaemonConfigAccessible', false),
	defaultDaemon: loadState('settings', 'defaultDaemonConfig', null),
}

const mutations = {

	APPS_API_FAILURE(state, error) {
		showError(t('app_api', 'An error occurred during the request. Unable to proceed.') + '<br>' + error.error.response.data.data.message, { isHTML: true })
		console.error(state, error)
	},

	initCategories(state, { categories, updateCount }) {
		state.categories = categories
		state.updateCount = updateCount
	},

	updateCategories(state, categoriesPromise) {
		state.gettingCategoriesPromise = categoriesPromise
	},

	setUpdateCount(state, updateCount) {
		state.updateCount = updateCount
	},

	addCategory(state, category) {
		state.categories.push(category)
	},

	appendCategories(state, categoriesArray) {
		// convert obj to array
		state.categories = categoriesArray
	},

	setAllApps(state, apps) {
		state.apps = apps
	},

	setError(state, { appId, error }) {
		if (!Array.isArray(appId)) {
			appId = [appId]
		}
		appId.forEach((_id) => {
			const app = state.apps.find(app => app.id === _id)
			app.error = error
		})
	},

	enableApp(state, { appId }) {
		const app = state.apps.find(app => app.id === appId)
		if (!app.installed) {
			app.installed = true
			app.needsDownload = false
			app.daemon = state.defaultDaemon
			app.status = {
				type: 'install',
				action: 'deploy',
				init: 0,
				deploy: 0,
			}
		}
		app.active = true
		app.canUnInstall = false
		app.removable = true
		app.error = null
	},

	disableApp(state, appId) {
		const app = state.apps.find(app => app.id === appId)
		app.active = false
		if (app.removable) {
			app.canUnInstall = true
		}
	},

	uninstallApp(state, appId) {
		state.apps.find(app => app.id === appId).active = false
		state.apps.find(app => app.id === appId).needsDownload = true
		state.apps.find(app => app.id === appId).installed = false
		state.apps.find(app => app.id === appId).canUnInstall = false
		state.apps.find(app => app.id === appId).canInstall = true
		state.apps.find(app => app.id === appId).daemon = null
		state.apps.find(app => app.id === appId).status = {}
		if (state.apps.find(app => app.id === appId).update !== null) {
			state.updateCount--
		}
		state.apps.find(app => app.id === appId).update = null
	},

	updateApp(state, { appId }) {
		const app = state.apps.find(app => app.id === appId)
		const version = app.update
		app.update = null
		app.version = version
		app.status = {
			type: 'update',
			action: 'deploy',
			init: 0,
			deploy: 0,
		}
		app.error = null
		state.updateCount--
	},

	startLoading(state, id) {
		Vue.set(state.loading, id, true) // eslint-disable-line
	},

	stopLoading(state, id) {
		Vue.set(state.loading, id, false) // eslint-disable-line
	},

	setDaemonAccessible(state, value) {
		state.daemonAccessible = value
	},

	setDefaultDaemon(state, value) {
		Vue.set(state, 'defaultDaemon', value) // eslint-disable-line
	},

	setAppStatus(state, { appId, status }) {
		const app = state.apps.find(app => app.id === appId)
		if (status.type === 'install' && status.deploy === 100 && status.action === '') {
			console.debug('catching intermediate state deploying -> initializing')
			// catching moment when app is deployed but initialization status not started yet
			status.action = 'init'
			app.canUnInstall = true
		}
		if (status.error !== '') {
			app.error = status.error
			app.canUnInstall = true
		}
		if (status.deploy === 100 && status.init === 100) {
			app.active = true
			app.canUnInstall = false
			app.removable = true
		}
		app.status = status
	},

	setIntervalUpdater(state, updater) {
		state.statusUpdater = updater
	},
}

const getters = {
	loading(state) {
		return function(id) {
			return state.loading[id]
		}
	},
	getCategories(state) {
		return state.categories
	},
	getAllApps(state) {
		return state.apps
	},
	getUpdateCount(state) {
		return state.updateCount
	},
	getCategoryById: (state) => (selectedCategoryId) => {
		return state.categories.find((category) => category.id === selectedCategoryId)
	},
	getDaemonAccessible(state) {
		return state.daemonAccessible
	},
	getAppStatus(state) {
		return function(appId) {
			return state.apps.find(app => app.id === appId).status
		}
	},
	getStatusUpdater(state) {
		return state.statusUpdater
	},
	getInitializingOrDeployingApps(state) {
		return state.apps.filter(app => Object.hasOwn(app.status, 'action')
			&& (app.status.action === 'deploy' || app.status.action === 'init' || app.status.action === 'healthcheck')
			&& app.status.type !== '')
	},
}

const actions = {

	enableApp(context, { appId }) {
		return api.requireAdmin().then((response) => {
			context.commit('startLoading', appId)
			context.commit('startLoading', 'install')
			return api.post(generateUrl(`/apps/app_api/apps/enable/${appId}`))
				.then((response) => {
					context.commit('stopLoading', appId)
					context.commit('stopLoading', 'install')

					context.commit('enableApp', { appId })

					context.dispatch('updateAppsStatus')

					// check for server health
					return axios.get(generateUrl('apps/files'))
						.then(() => {
							if (response.data.update_required) {
								showInfo(
									t(
										'app_api',
										'The app has been enabled but needs to be updated.',
									),
									{
										onClick: () => window.location.reload(),
										close: false,
									},
								)
								setTimeout(function() {
									location.reload()
								}, 5000)
							}
						})
						.catch(() => {
							context.commit('setError', {
								appId: [appId],
								error: t('app_api', 'Error: This app cannot be enabled because it makes the server unstable'),
							})
						})
				})
				.catch((error) => {
					context.commit('stopLoading', appId)
					context.commit('stopLoading', 'install')
					context.commit('setError', {
						appId: [appId],
						error: error.response.data.data.message,
					})
					context.commit('APPS_API_FAILURE', { appId, error })
				})
		}).catch((error) => context.commit('API_FAILURE', { appId, error }))
	},

	forceEnableApp(context, { appId }) {
		return api.requireAdmin().then(() => {
			context.commit('startLoading', appId)
			context.commit('startLoading', 'install')
			return api.post(generateUrl('/apps/app_api/apps/force'), { appId })
				.then((response) => {
					location.reload()
				})
				.catch((error) => {
					context.commit('stopLoading', appId)
					context.commit('stopLoading', 'install')
					context.commit('setError', {
						appId: [appId],
						error: error.response.data.data.message,
					})
					context.commit('APPS_API_FAILURE', { appId, error })
				})
		}).catch((error) => context.commit('API_FAILURE', { appId, error }))
	},

	disableApp(context, { appId }) {
		return api.requireAdmin().then((response) => {
			context.commit('startLoading', appId)
			return api.get(generateUrl(`apps/app_api/apps/disable/${appId}`))
				.then((response) => {
					context.commit('stopLoading', appId)
					context.commit('disableApp', appId)
					return true
				})
				.catch((error) => {
					context.commit('disableApp', appId)
					context.commit('stopLoading', appId)
					context.commit('APPS_API_FAILURE', { appId, error })
				})
		}).catch((error) => context.commit('API_FAILURE', { appId, error }))
	},

	uninstallApp(context, { appId, removeData }) {
		return api.requireAdmin().then((response) => {
			context.commit('startLoading', appId)
			return api.get(generateUrl(`/apps/app_api/apps/uninstall/${appId}?removeData=${removeData}`))
				.then((response) => {
					context.commit('stopLoading', appId)
					context.commit('uninstallApp', appId)
					return true
				})
				.catch((error) => {
					context.commit('stopLoading', appId)
					context.commit('APPS_API_FAILURE', { appId, error })
				})
		}).catch((error) => context.commit('API_FAILURE', { appId, error }))
	},

	updateApp(context, { appId }) {
		return api.requireAdmin().then((response) => {
			context.commit('startLoading', appId)
			context.commit('startLoading', 'install')
			return api.get(generateUrl(`/apps/app_api/apps/update/${appId}`))
				.then((response) => {
					context.commit('stopLoading', 'install')
					context.commit('stopLoading', appId)
					context.commit('updateApp', { appId })
					context.dispatch('updateAppsStatus')
					return true
				})
				.catch((error) => {
					context.commit('stopLoading', appId)
					context.commit('stopLoading', 'install')
					context.commit('APPS_API_FAILURE', { appId, error })
				})
		}).catch((error) => context.commit('API_FAILURE', { appId, error }))
	},

	getAllApps(context) {
		context.commit('startLoading', 'list')
		return api.get(generateUrl('/apps/app_api/apps/list'))
			.then((response) => {
				context.commit('setAllApps', response.data.apps)
				context.commit('stopLoading', 'list')
				return true
			})
			.catch((error) => context.commit('API_FAILURE', error))
	},

	async getCategories(context, { shouldRefetchCategories = false } = {}) {
		if (shouldRefetchCategories || !context.state.gettingCategoriesPromise) {
			context.commit('startLoading', 'categories')
			try {
				const categoriesPromise = api.get(generateUrl('/apps/app_api/apps/categories'))
				context.commit('updateCategories', categoriesPromise)
				const categoriesPromiseResponse = await categoriesPromise
				if (categoriesPromiseResponse.data.length > 0) {
					context.commit('appendCategories', categoriesPromiseResponse.data)
					context.commit('stopLoading', 'categories')
					return true
				}
				context.commit('stopLoading', 'categories')
				return false
			} catch (error) {
				context.commit('API_FAILURE', error)
			}
		}
		return context.state.gettingCategoriesPromise
	},

	getAppStatus(context, { appId }) {
		return api.get(generateUrl(`/apps/app_api/apps/status/${appId}`))
			.then((response) => {
				context.commit('setAppStatus', { appId, status: response.data })
				const initializingOrDeployingApps = context.getters.getInitializingOrDeployingApps
				console.debug('initializingOrDeployingApps after setAppStatus', initializingOrDeployingApps)
				if (initializingOrDeployingApps.length === 0) {
					console.debug('clearing interval')
					clearInterval(context.getters.getStatusUpdater)
					context.commit('setIntervalUpdater', null)
				}
				if (Object.hasOwn(response.data, 'error')
					&& response.data.error !== ''
					&& initializingOrDeployingApps.length === 1) {
					clearInterval(context.getters.getStatusUpdater)
					context.commit('setIntervalUpdater', null)
				}
			})
			.catch((error) => {
				context.commit('API_FAILURE', error)
				context.commit('unregisterApp', { appId })
				context.dispatch('updateAppsStatus')
			})
	},

	updateAppsStatus(context) {
		clearInterval(context.getters.getStatusUpdater) // clear previous interval if exists
		context.commit('setIntervalUpdater', setInterval(() => {
			const initializingOrDeployingApps = context.getters.getInitializingOrDeployingApps
			console.debug('initializingOrDeployingApps', initializingOrDeployingApps)
			Array.from(initializingOrDeployingApps).forEach(app => {
				context.dispatch('getAppStatus', { appId: app.id })
			})
		}, 2000))
	},

}

export default {
	namespaced: true, // we will use AppAPI store module explicitly, since methods names are the same, we need to scope it
	state, mutations, getters, actions
}
