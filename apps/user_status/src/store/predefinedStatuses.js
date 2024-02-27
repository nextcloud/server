/**
 * @copyright Copyright (c) 2020 Georg Ehrke
 *
 * @author Georg Ehrke <oc.list@georgehrke.com>
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

import { fetchAllPredefinedStatuses } from '../services/predefinedStatusService.js'

const state = {
	predefinedStatuses: [],
}

const mutations = {

	/**
	 * Adds a predefined status to the state
	 *
	 * @param {object} state The Vuex state
	 * @param {object} status The status to add
	 */
	addPredefinedStatus(state, status) {
		state.predefinedStatuses = [...state.predefinedStatuses, status]
	},
}

const getters = {
	statusesHaveLoaded(state) {
		return state.predefinedStatuses.length > 0
	},
}

const actions = {

	/**
	 * Loads all predefined statuses from the server
	 *
	 * @param {object} vuex The Vuex components
	 * @param {Function} vuex.commit The Vuex commit function
	 * @param {object} vuex.state -
	 */
	async loadAllPredefinedStatuses({ state, commit }) {
		if (state.predefinedStatuses.length > 0) {
			return
		}

		const statuses = await fetchAllPredefinedStatuses()
		for (const status of statuses) {
			commit('addPredefinedStatus', status)
		}
	},

}

export default { state, mutations, getters, actions }
