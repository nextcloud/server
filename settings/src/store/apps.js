/*
 * @copyright Copyright (c) 2018 Julius Härtl <jus@bitgrid.net>
 *
 * @author Julius Härtl <jus@bitgrid.net>
 *
 * @license GNU AGPL version 3 or any later version
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

import api from './api';
import axios from 'axios/index';

const state = {
	apps: [],
	allApps: [],
	categories: [],
	updateCount: 0
};

const mutations = {

	APPS_API_FAILURE(state, error) {
		OC.Notification.showHtml(t('settings','An error occured during the request. Unable to proceed.')+'<br>'+error.error.response.data.data.message, {timeout: 7});
		console.log(state, error);
	},

	initCategories(state, {categories, updateCount}) {
		state.categories = categories;
		state.updateCount = updateCount;
	},

	setUpdateCount(state, updateCount) {
		state.updateCount = updateCount;
	},

	addCategory(state, category) {
		state.categories.push(category);
	},

	appendCategories(state, categoriesArray) {
		// convert obj to array
		state.categories = categoriesArray;
	},

	setApps(state, apps) {
		state.apps = apps;
	},

	setAllApps(state, apps) {
		state.allApps = apps;
	},

	enableApp(state, {appId, groups}) {
		let app = state.apps.find(app => app.id === appId);
		app.active = true;
		app.groups = groups;
	},

	disableApp(state, appId) {
		let app = state.apps.find(app => app.id === appId);
		app.active = false;
		app.groups = [];
		if (app.removable) {
			app.canUnInstall = true;
		}
	},

	uninstallApp(state, appId) {
		state.apps.find(app => app.id === appId).active = false;
		state.apps.find(app => app.id === appId).groups = [];
		state.apps.find(app => app.id === appId).needsDownload = true;
		state.apps.find(app => app.id === appId).canUnInstall = false;
		state.apps.find(app => app.id === appId).canInstall = true;
	},

	resetApps(state) {
		state.apps = [];
	},
	reset(state) {
		state.apps = [];
		state.categories = [];
		state.updateCount = 0;
	}
};

const getters = {
	getCategories(state) {
		return state.categories;
	},
	getApps(state) {
		return state.apps.concat([]).sort(function (a, b) {
			if (a.active !== b.active) {
				return (a.active ? -1 : 1)
			}
			if (a.update !== b.update) {
				return (a.update ? -1 : 1)
			}
			return OC.Util.naturalSortCompare(a.name, b.name);
		});
	},
	getAllApps(state) {
		return state.allApps;
	},
	getUpdateCount(state) {
		return state.updateCount;
	}
};

const actions = {

	enableApp(context, { appId, groups }) {
		return api.requireAdmin().then((response) => {
				return api.post(OC.generateUrl(`settings/apps/enable/${appId}`), {
					groups: groups
				})
				.then((response) => {
					context.commit('enableApp', {appId: appId, groups: groups});
					return true;
				})
				.catch((error) => context.commit('APPS_API_FAILURE', { appId, error }))
		}).catch((error) => context.commit('API_FAILURE', { appId, error }));

	},
	disableApp(context, { appId }) {
		return api.requireAdmin().then((response) => {
			return api.get(OC.generateUrl(`settings/apps/disable/${appId}`))
				.then((response) => {
					context.commit('disableApp', appId);
					return true;
				})
				.catch((error) => context.commit('APPS_API_FAILURE', { appId, error }))
		}).catch((error) => context.commit('API_FAILURE', { appId, error }));
	},
	installApp(context, { appId }) {
		return api.requireAdmin().then((response) => {
			return api.get(OC.generateUrl(`settings/apps/enable/${appId}`))
				.then((response) => {
					context.commit('enableApp', appId);
					return true;
				})
				.catch((error) => context.commit('APPS_API_FAILURE', { appId, error }))
		}).catch((error) => context.commit('API_FAILURE', { appId, error }));
	},
	uninstallApp(context, { appId }) {
		return api.requireAdmin().then((response) => {
			return api.get(OC.generateUrl(`settings/apps/uninstall/${appId}`))
				.then((response) => {
					context.commit('uninstallApp', appId);
					return true;
				})
				.catch((error) => context.commit('APPS_API_FAILURE', { appId, error }))
		}).catch((error) => context.commit('API_FAILURE', { appId, error }));
	},

	getApps(context, { category }) {
		return api.get(OC.generateUrl(`settings/apps/list?category=${category}`))
			.then((response) => {
				context.commit('setApps', response.data.apps);
				return true;
			})
			.catch((error) => context.commit('API_FAILURE', error))
	},

	getAllApps(context) {
		return api.get(OC.generateUrl(`settings/apps/list`))
			.then((response) => {
				context.commit('setAllApps', response.data.apps);
				return true;
			})
			.catch((error) => context.commit('API_FAILURE', error))
	},

	getCategories(context) {
		return api.get(OC.generateUrl('settings/apps/categories'))
			.then((response) => {
				if (response.data.length > 0) {
					context.commit('appendCategories', response.data);
					return true;
				}
				return false;
			})
			.catch((error) => context.commit('API_FAILURE', error));
	},

};

export default { state, mutations, getters, actions };