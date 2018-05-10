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

const state = {
	apps: [],
	categories: [],
	updateCount: 0
};

const mutations = {
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
		return state.apps;
	},
	getUpdateCount(state) {
		return state.updateCount;
	}
};

const actions = {

	getApps(context, { category }) {
		return api.get(OC.generateUrl(`settings/apps/list?category=${category}`))
			.then((response) => {
				context.commit('setApps', response.data.apps);
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