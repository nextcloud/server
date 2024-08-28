/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
