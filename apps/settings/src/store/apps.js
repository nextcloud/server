/**
 * @copyright Copyright (c) 2018 Julius Härtl <jus@bitgrid.net>
 *
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 * @author Julius Härtl <jus@bitgrid.net>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

import api from './api.js'
import Vue from 'vue'
import { generateUrl } from '@nextcloud/router'
import { showError, showInfo } from '@nextcloud/dialogs'

const state = {
	apps: [],
	categories: [],
	updateCount: 0,
	loading: {},
	loadingList: false,
	gettingCategoriesPromise: null,
}

const mutations = {

	APPS_API_FAILURE(state, error) {
		showError(t('settings', 'An error occurred during the request. Unable to proceed.') + '<br>' + error.error.response.data.data.message, { isHTML: true })
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

	clearError(state, { appId, error }) {
		const app = state.apps.find(app => app.id === appId)
		app.error = null
	},

	enableApp(state, { appId, groups }) {
		const app = state.apps.find(app => app.id === appId)
		app.active = true
		app.groups = groups
	},

	disableApp(state, appId) {
		const app = state.apps.find(app => app.id === appId)
		app.active = false
		app.groups = []
		if (app.removable) {
			app.canUnInstall = true
		}
	},

	uninstallApp(state, appId) {
		state.apps.find(app => app.id === appId).active = false
		state.apps.find(app => app.id === appId).groups = []
		state.apps.find(app => app.id === appId).needsDownload = true
		state.apps.find(app => app.id === appId).installed = false
		state.apps.find(app => app.id === appId).canUnInstall = false
		state.apps.find(app => app.id === appId).canInstall = true
	},

	updateApp(state, appId) {
		const app = state.apps.find(app => app.id === appId)
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
}

const actions = {

	enableApp(context, { appId, groups }) {
		let apps
		if (Array.isArray(appId)) {
			apps = appId
		} else {
			apps = [appId]
		}
		return api.requireAdmin().then((response) => {
			context.commit('startLoading', apps)
			context.commit('startLoading', 'install')
			return api.post(generateUrl('settings/apps/enable'), { appIds: apps, groups })
				.then((response) => {
					context.commit('stopLoading', apps)
					context.commit('stopLoading', 'install')
					apps.forEach(_appId => {
						context.commit('enableApp', { appId: _appId, groups })
					})

					// check for server health
					return api.get(generateUrl('apps/files'))
						.then(() => {
							if (response.data.update_required) {
								showInfo(
									t(
										'settings',
										'The app has been enabled but needs to be updated. You will be redirected to the update page in 5 seconds.'
									),
									{
										onClick: () => window.location.reload(),
										close: false,

									}
								)
								setTimeout(function() {
									location.reload()
								}, 5000)
							}
						})
						.catch(() => {
							if (!Array.isArray(appId)) {
								context.commit('setError', {
									appId: apps,
									error: t('settings', 'Error: This app cannot be enabled because it makes the server unstable'),
								})
							}
						})
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
		}).catch((error) => context.commit('API_FAILURE', { appId, error }))
	},
	forceEnableApp(context, { appId, groups }) {
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
				.then((response) => {
					// TODO: find a cleaner solution
					location.reload()
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
		}).catch((error) => context.commit('API_FAILURE', { appId, error }))
	},
	disableApp(context, { appId }) {
		let apps
		if (Array.isArray(appId)) {
			apps = appId
		} else {
			apps = [appId]
		}
		return api.requireAdmin().then((response) => {
			context.commit('startLoading', apps)
			return api.post(generateUrl('settings/apps/disable'), { appIds: apps })
				.then((response) => {
					context.commit('stopLoading', apps)
					apps.forEach(_appId => {
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
		return api.requireAdmin().then((response) => {
			context.commit('startLoading', appId)
			return api.get(generateUrl(`settings/apps/uninstall/${appId}`))
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
			return api.get(generateUrl(`settings/apps/update/${appId}`))
				.then((response) => {
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
