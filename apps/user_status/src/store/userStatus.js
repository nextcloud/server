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
import { getCurrentUser } from '@nextcloud/auth'
import { getTimestampForClearAt } from '../services/clearAtService'
import { emit } from '@nextcloud/event-bus'

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
	 * @param {object} state The Vuex state
	 * @param {object} data The destructuring object
	 * @param {string} data.statusType The new status type
	 */
	setStatus(state, { statusType }) {
		state.status = statusType
		state.statusIsUserDefined = true
	},

	/**
	 * Sets a message using a predefined message
	 *
	 * @param {object} state The Vuex state
	 * @param {object} data The destructuring object
	 * @param {string} data.messageId The messageId
	 * @param {number | null} data.clearAt When to automatically clear the status
	 * @param {string} data.message The message
	 * @param {string} data.icon The icon
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
	 * @param {object} state The Vuex state
	 * @param {object} data The destructuring object
	 * @param {string} data.message The message
	 * @param {string} data.icon The icon
	 * @param {number} data.clearAt When to automatically clear the status
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
	 * @param {object} state The Vuex state
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
	 * @param {object} state The Vuex state
	 * @param {object} data The destructuring object
	 * @param {string} data.status The status type
	 * @param {boolean} data.statusIsUserDefined Whether or not this status is user-defined
	 * @param {string} data.message The message
	 * @param {string} data.icon The icon
	 * @param {number} data.clearAt When to automatically clear the status
	 * @param {boolean} data.messageIsPredefined Whether or not the message is predefined
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
	 * @param {object} vuex The Vuex destructuring object
	 * @param {Function} vuex.commit The Vuex commit function
	 * @param {object} vuex.state The Vuex state object
	 * @param {object} data The data destructuring object
	 * @param {string} data.statusType The new status type
	 * @return {Promise<void>}
	 */
	async setStatus({ commit, state }, { statusType }) {
		await setStatus(statusType)
		commit('setStatus', { statusType })
		emit('user_status:status.updated', {
			status: state.status,
			message: state.message,
			icon: state.icon,
			clearAt: state.clearAt,
			userId: getCurrentUser()?.uid,
		})
	},

	/**
	 * Update status from 'user_status:status.updated' update.
	 * This doesn't trigger another 'user_status:status.updated'
	 * event.
	 *
	 * @param {object} vuex The Vuex destructuring object
	 * @param {Function} vuex.commit The Vuex commit function
	 * @param {object} vuex.state The Vuex state object
	 * @param {string} status The new status
	 * @return {Promise<void>}
	 */
	async setStatusFromObject({ commit, state }, status) {
		commit('loadStatusFromServer', status)
	},

	/**
	 * Sets a message using a predefined message
	 *
	 * @param {object} vuex The Vuex destructuring object
	 * @param {Function} vuex.commit The Vuex commit function
	 * @param {object} vuex.state The Vuex state object
	 * @param {object} vuex.rootState The Vuex root state
	 * @param {object} data The data destructuring object
	 * @param {string} data.messageId The messageId
	 * @param {object | null} data.clearAt When to automatically clear the status
	 * @return {Promise<void>}
	 */
	async setPredefinedMessage({ commit, rootState, state }, { messageId, clearAt }) {
		const resolvedClearAt = getTimestampForClearAt(clearAt)

		await setPredefinedMessage(messageId, resolvedClearAt)
		const status = rootState.predefinedStatuses.predefinedStatuses.find((status) => status.id === messageId)
		const { message, icon } = status

		commit('setPredefinedMessage', { messageId, clearAt: resolvedClearAt, message, icon })
		emit('user_status:status.updated', {
			status: state.status,
			message: state.message,
			icon: state.icon,
			clearAt: state.clearAt,
			userId: getCurrentUser()?.uid,
		})
	},

	/**
	 * Sets a custom message
	 *
	 * @param {object} vuex The Vuex destructuring object
	 * @param {Function} vuex.commit The Vuex commit function
	 * @param {object} vuex.state The Vuex state object
	 * @param {object} data The data destructuring object
	 * @param {string} data.message The message
	 * @param {string} data.icon The icon
	 * @param {object | null} data.clearAt When to automatically clear the status
	 * @return {Promise<void>}
	 */
	async setCustomMessage({ commit, state }, { message, icon, clearAt }) {
		const resolvedClearAt = getTimestampForClearAt(clearAt)

		await setCustomMessage(message, icon, resolvedClearAt)
		commit('setCustomMessage', { message, icon, clearAt: resolvedClearAt })
		emit('user_status:status.updated', {
			status: state.status,
			message: state.message,
			icon: state.icon,
			clearAt: state.clearAt,
			userId: getCurrentUser()?.uid,
		})
	},

	/**
	 * Clears the status
	 *
	 * @param {object} vuex The Vuex destructuring object
	 * @param {Function} vuex.commit The Vuex commit function
	 * @param {object} vuex.state The Vuex state object
	 * @return {Promise<void>}
	 */
	async clearMessage({ commit, state }) {
		await clearMessage()
		commit('clearMessage')
		emit('user_status:status.updated', {
			status: state.status,
			message: state.message,
			icon: state.icon,
			clearAt: state.clearAt,
			userId: getCurrentUser()?.uid,
		})
	},

	/**
	 * Re-fetches the status from the server
	 *
	 * @param {object} vuex The Vuex destructuring object
	 * @param {Function} vuex.commit The Vuex commit function
	 * @return {Promise<void>}
	 */
	async reFetchStatusFromServer({ commit }) {
		const status = await fetchCurrentStatus()
		commit('loadStatusFromServer', status)
	},

	/**
	 * Stores the status we got in the reply of the heartbeat
	 *
	 * @param {object} vuex The Vuex destructuring object
	 * @param {Function} vuex.commit The Vuex commit function
	 * @param {object} status The data destructuring object
	 * @param {string} status.status The status type
	 * @param {boolean} status.statusIsUserDefined Whether or not this status is user-defined
	 * @param {string} status.message The message
	 * @param {string} status.icon The icon
	 * @param {number} status.clearAt When to automatically clear the status
	 * @param {boolean} status.messageIsPredefined Whether or not the message is predefined
	 * @param {string} status.messageId The id of the predefined message
	 * @return {Promise<void>}
	 */
	async setStatusFromHeartbeat({ commit }, status) {
		commit('loadStatusFromServer', status)
	},

	/**
	 * Loads the server from the initial state
	 *
	 * @param {object} vuex The Vuex destructuring object
	 * @param {Function} vuex.commit The Vuex commit function
	 */
	loadStatusFromInitialState({ commit }) {
		const status = loadState('user_status', 'status')
		commit('loadStatusFromServer', status)
	},
}

export default { state, mutations, getters, actions }
