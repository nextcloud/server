/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import axios from '@nextcloud/axios'
import { showError, showInfo } from '@nextcloud/dialogs'
import { loadState } from '@nextcloud/initial-state'
import { generateUrl } from '@nextcloud/router'
import Vue from 'vue'
import logger from '../utils/logger.ts'
import api from './api.js'

const state = {
	apps: [],
	bundles: loadState('settings', 'appstoreBundles', []),
	categories: [],
	updateCount: loadState('settings', 'appstoreUpdateCount', 0),
	loading: {},
	gettingCategoriesPromise: null,
	appApiEnabled: ,
}

const mutations = {

	APPS_API_FAILURE(state, error) {
		showError(t('settings', 'An error occurred during the request. Unable to proceed.') + '<br>' + error.error.response.data.data.message, { isHTML: true })
		logger.error('An error occurred during the request. Unable to proceed.', { state, error })
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
			const app = state.apps.find((app) => app.id === _id)
			app.error = error
		})
	},

	clearError(state, { appId }) {
		const app = state.apps.find((app) => app.id === appId)
		app.error = null
	},

	enableApp(state, { appId, groups }) {
		const app = state.apps.find((app) => app.id === appId)
		app.active = true
		Vue.set(app, 'groups', [...groups])
		if (app.id === 'app_api') {
			state.appApiEnabled = true
		}
	},

	setInstallState(state, { appId, canInstall }) {
		const app = state.apps.find((app) => app.id === appId)
		if (app) {
			app.canInstall = canInstall === true
		}
	},

	disableApp(state, appId) {
		const app = state.apps.find((app) => app.id === appId)
		app.active = false
		app.groups = []
		if (app.removable) {
			app.canUnInstall = true
		}
		if (app.id === 'app_api') {
			state.appApiEnabled = false
		}
	},

	uninstallApp(state, appId) {
		state.apps.find((app) => app.id === appId).active = false
		state.apps.find((app) => app.id === appId).groups = []
		state.apps.find((app) => app.id === appId).needsDownload = true
		state.apps.find((app) => app.id === appId).installed = false
		state.apps.find((app) => app.id === appId).canUnInstall = false
		state.apps.find((app) => app.id === appId).canInstall = true
		if (appId === 'app_api') {
			state.appApiEnabled = false
		}
	},

	updateApp(state, appId) {
		const app = state.apps.find((app) => app.id === appId)
		const version = app.update
		app.update = null
		app.version = version
		state.updateCount--
	},

	resetApps(state) {
		state.apps = []
	},
	reset(state) {
		state.apps = []
		state.categories = []
		state.updateCount = 0
	},
	startLoading(state, id) {
		if (Array.isArray(id)) {
			id.forEach((_id) => {
				Vue.set(state.loading, _id, true)
			})
		} else {
			Vue.set(state.loading, id, true)
		}
	},
	stopLoading(state, id) {
		if (Array.isArray(id)) {
			id.forEach((_id) => {
				Vue.set(state.loading, _id, false)
			})
		} else {
			Vue.set(state.loading, id, false)
		}
	},
}

const getters = {
	isAppApiEnabled(state) {
		return state.appApiEnabled
	},
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
	getAppBundles(state) {
		return state.bundles
	},
	getUpdateCount(state) {
		return state.updateCount
	},
	getCategoryById: (state) => (selectedCategoryId) => {
		return state.categories.find((category) => category.id === selectedCategoryId)
	},
}

const actions = {

	enableApp(context, { appId, groups }) {
		let apps
		if (Array.isArray(appId)) {
			apps = appId
		} else {
			apps = [appId]
		}
		return api.requireAdmin().then(() => {
			context.commit('startLoading', apps)
			context.commit('startLoading', 'install')

			const previousState = {}
			apps.forEach((_appId) => {
				const app = context.state.apps.find((app) => app.id === _appId)
				if (app) {
					previousState[_appId] = {
						active: app.active,
						groups: [...(app.groups || [])],
					}
					context.commit('enableApp', { appId: _appId, groups })
				}
			})

			return api.post(generateUrl('settings/apps/enable'), { appIds: apps, groups })
				.then((response) => {
					context.commit('stopLoading', apps)
					context.commit('stopLoading', 'install')

					// check for server health
					return axios.get(generateUrl('apps/files/'))
						.then(() => {
							if (response.data.update_required) {
								showInfo(
									t(
										'settings',
										'The app has been enabled but needs to be updated. You will be redirected to the update page in 5 seconds.',
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
							if (!Array.isArray(appId)) {
								showError(t('settings', 'Error: This app cannot be enabled because it makes the server unstable'))
								context.commit('setError', {
									appId: apps,
									error: t('settings', 'Error: This app cannot be enabled because it makes the server unstable'),
								})
								context.dispatch('disableApp', { appId })
							}
						})
				})
				.catch((error) => {
					context.commit('stopLoading', apps)
					context.commit('stopLoading', 'install')

					apps.forEach((_appId) => {
						if (previousState[_appId]) {
							context.commit('enableApp', {
								appId: _appId,
								groups: previousState[_appId].groups,
							})
							if (!previousState[_appId].active) {
								context.commit('disableApp', _appId)
							}
						}
					})

					context.commit('setError', {
						appId: apps,
						error: error.response.data.data.message,
					})
					context.commit('APPS_API_FAILURE', { appId, error })
				})
		}).catch((error) => context.commit('API_FAILURE', { appId, error }))
	},
	forceEnableApp(context, { appId }) {
		let apps
		if (Array.isArray(appId)) {
			apps = appId
		} else {
			apps = [appId]
		}
		return api.requireAdmin().then(() => {
			context.commit('startLoading', apps)
			context.commit('startLoading', 'install')
			return api.post(generateUrl('settings/apps/force'), { appId })
				.then(() => {
					context.commit('setInstallState', { appId, canInstall: true })
				})
				.catch((error) => {
					context.commit('stopLoading', apps)
					context.commit('stopLoading', 'install')
					context.commit('setError', {
						appId: apps,
						error: error.response.data.data.message,
					})
					context.commit('APPS_API_FAILURE', { appId, error })
				})
				.finally(() => {
					context.commit('stopLoading', apps)
					context.commit('stopLoading', 'install')
				})
		}).catch((error) => context.commit('API_FAILURE', { appId, error }))
	},
	disableApp(context, { appId }) {
		let apps
		if (Array.isArray(appId)) {
			apps = appId
		} else {
			apps = [appId]
		}
		return api.requireAdmin().then(() => {
			context.commit('startLoading', apps)
			return api.post(generateUrl('settings/apps/disable'), { appIds: apps })
				.then(() => {
					context.commit('stopLoading', apps)
					apps.forEach((_appId) => {
						context.commit('disableApp', _appId)
					})
					return true
				})
				.catch((error) => {
					context.commit('stopLoading', apps)
					context.commit('APPS_API_FAILURE', { appId, error })
				})
		}).catch((error) => context.commit('API_FAILURE', { appId, error }))
	},
	uninstallApp(context, { appId }) {
		return api.requireAdmin().then(() => {
			context.commit('startLoading', appId)
			return api.get(generateUrl(`settings/apps/uninstall/${appId}`))
				.then(() => {
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
		return api.requireAdmin().then(() => {
			context.commit('startLoading', appId)
			context.commit('startLoading', 'install')
			return api.get(generateUrl(`settings/apps/update/${appId}`))
				.then(() => {
					context.commit('stopLoading', 'install')
					context.commit('stopLoading', appId)
					context.commit('updateApp', appId)
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
		return api.get(generateUrl('settings/apps/list'))
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
				const categoriesPromise = api.get(generateUrl('settings/apps/categories'))
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

}

export default { state, mutations, getters, actions }
