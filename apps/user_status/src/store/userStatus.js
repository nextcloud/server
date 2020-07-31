/**
 * @copyright Copyright (c) 2020 Georg Ehrke
 *
 * @author Georg Ehrke <oc.list@georgehrke.com>
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
import {
	fetchCurrentStatus,
	setStatus,
	setPredefinedMessage,
	setCustomMessage,
	clearMessage,
} from '../services/statusService'
import { loadState } from '@nextcloud/initial-state'
import { getTimestampForClearAt } from '../services/clearAtService'

const state = {
	// Status (online / away / dnd / invisible / offline)
	status: null,
	// Whether or not the status is user-defined
	statusIsUserDefined: null,
	// A custom message set by the user
	message: null,
	// The icon selected by the user
	icon: null,
	// When to automatically clean the status
	clearAt: null,
	// Whether or not the message is predefined
	// (and can automatically be translated by Nextcloud)
	messageIsPredefined: null,
	// The id of the message in case it's predefined
	messageId: null,
}

const mutations = {

	/**
	 * Sets a new status
	 *
	 * @param {Object} state The Vuex state
	 * @param {Object} data The destructuring object
	 * @param {String} data.statusType The new status type
	 */
	setStatus(state, { statusType }) {
		state.status = statusType
		state.statusIsUserDefined = true
	},

	/**
	 * Sets a message using a predefined message
	 *
	 * @param {Object} state The Vuex state
	 * @param {Object} data The destructuring object
	 * @param {String} data.messageId The messageId
	 * @param {Number|null} data.clearAt When to automatically clear the status
	 * @param {String} data.message The message
	 * @param {String} data.icon The icon
	 */
	setPredefinedMessage(state, { messageId, clearAt, message, icon }) {
		state.messageId = messageId
		state.messageIsPredefined = true

		state.message = message
		state.icon = icon
		state.clearAt = clearAt
	},

	/**
	 * Sets a custom message
	 *
	 * @param {Object} state The Vuex state
	 * @param {Object} data The destructuring object
	 * @param {String} data.message The message
	 * @param {String} data.icon The icon
	 * @param {Number} data.clearAt When to automatically clear the status
	 */
	setCustomMessage(state, { message, icon, clearAt }) {
		state.messageId = null
		state.messageIsPredefined = false

		state.message = message
		state.icon = icon
		state.clearAt = clearAt
	},

	/**
	 * Clears the status
	 *
	 * @param {Object} state The Vuex state
	 */
	clearMessage(state) {
		state.messageId = null
		state.messageIsPredefined = false

		state.message = null
		state.icon = null
		state.clearAt = null
	},

	/**
	 * Loads the status from initial state
	 *
	 * @param {Object} state The Vuex state
	 * @param {Object} data The destructuring object
	 * @param {String} data.status The status type
	 * @param {Boolean} data.statusIsUserDefined Whether or not this status is user-defined
	 * @param {String} data.message The message
	 * @param {String} data.icon The icon
	 * @param {Number} data.clearAt When to automatically clear the status
	 * @param {Boolean} data.messageIsPredefined Whether or not the message is predefined
	 * @param {string} data.messageId The id of the predefined message
	 */
	loadStatusFromServer(state, { status, statusIsUserDefined, message, icon, clearAt, messageIsPredefined, messageId }) {
		state.status = status
		state.statusIsUserDefined = statusIsUserDefined
		state.message = message
		state.icon = icon
		state.clearAt = clearAt
		state.messageIsPredefined = messageIsPredefined
		state.messageId = messageId
	},
}

const getters = {}

const actions = {

	/**
	 * Sets a new status
	 *
	 * @param {Object} vuex The Vuex destructuring object
	 * @param {Function} vuex.commit The Vuex commit function
	 * @param {Object} data The data destructuring object
	 * @param {String} data.statusType The new status type
	 * @returns {Promise<void>}
	 */
	async setStatus({ commit }, { statusType }) {
		await setStatus(statusType)
		commit('setStatus', { statusType })
	},

	/**
	 * Sets a message using a predefined message
	 *
	 * @param {Object} vuex The Vuex destructuring object
	 * @param {Function} vuex.commit The Vuex commit function
	 * @param {Object} vuex.rootState The Vuex root state
	 * @param {Object} data The data destructuring object
	 * @param {String} data.messageId The messageId
	 * @param {Object|null} data.clearAt When to automatically clear the status
	 * @returns {Promise<void>}
	 */
	async setPredefinedMessage({ commit, rootState }, { messageId, clearAt }) {
		const resolvedClearAt = getTimestampForClearAt(clearAt)

		await setPredefinedMessage(messageId, resolvedClearAt)
		const status = rootState.predefinedStatuses.predefinedStatuses.find((status) => status.id === messageId)
		const { message, icon } = status

		commit('setPredefinedMessage', { messageId, clearAt: resolvedClearAt, message, icon })
	},

	/**
	 * Sets a custom message
	 *
	 * @param {Object} vuex The Vuex destructuring object
	 * @param {Function} vuex.commit The Vuex commit function
	 * @param {Object} data The data destructuring object
	 * @param {String} data.message The message
	 * @param {String} data.icon The icon
	 * @param {Object|null} data.clearAt When to automatically clear the status
	 * @returns {Promise<void>}
	 */
	async setCustomMessage({ commit }, { message, icon, clearAt }) {
		const resolvedClearAt = getTimestampForClearAt(clearAt)

		await setCustomMessage(message, icon, resolvedClearAt)
		commit('setCustomMessage', { message, icon, clearAt: resolvedClearAt })
	},

	/**
	 * Clears the status
	 *
	 * @param {Object} vuex The Vuex destructuring object
	 * @param {Function} vuex.commit The Vuex commit function
	 * @returns {Promise<void>}
	 */
	async clearMessage({ commit }) {
		await clearMessage()
		commit('clearMessage')
	},

	/**
	 * Re-fetches the status from the server
	 *
	 * @param {Object} vuex The Vuex destructuring object
	 * @param {Function} vuex.commit The Vuex commit function
	 * @returns {Promise<void>}
	 */
	async reFetchStatusFromServer({ commit }) {
		const status = await fetchCurrentStatus()
		commit('loadStatusFromServer', status)
	},

	/**
	 * Loads the server from the initial state
	 *
	 * @param {Object} vuex The Vuex destructuring object
	 * @param {Function} vuex.commit The Vuex commit function
	 */
	loadStatusFromInitialState({ commit }) {
		const status = loadState('user_status', 'status')
		commit('loadStatusFromServer', status)
	},
}

export default { state, mutations, getters, actions }
