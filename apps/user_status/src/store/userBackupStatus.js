/**
 * @copyright Copyright (c) 2020 Georg Ehrke
 * @copyright Copyright (c) 2023 Joas Schilling <coding@schilljs.com>
 *
 * @author Georg Ehrke <oc.list@georgehrke.com>
 * @author Joas Schilling <coding@schilljs.com>
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

import {
	fetchBackupStatus,
	revertToBackupStatus,
} from '../services/statusService.js'
import { getCurrentUser } from '@nextcloud/auth'
import { emit } from '@nextcloud/event-bus'

const state = {
	// Status (online / away / dnd / invisible / offline)
	status: null,
	// Whether the status is user-defined
	statusIsUserDefined: null,
	// A custom message set by the user
	message: null,
	// The icon selected by the user
	icon: null,
	// When to automatically clean the status
	clearAt: null,
	// Whether the message is predefined
	// (and can automatically be translated by Nextcloud)
	messageIsPredefined: null,
	// The id of the message in case it's predefined
	messageId: null,
}

const mutations = {
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
	loadBackupStatusFromServer(state, { status, statusIsUserDefined, message, icon, clearAt, messageIsPredefined, messageId }) {
		state.status = status
		state.message = message
		state.icon = icon

		// Don't overwrite certain values if the refreshing comes in via short updates
		// E.g. from talk participant list which only has the status, message and icon
		if (typeof statusIsUserDefined !== 'undefined') {
			state.statusIsUserDefined = statusIsUserDefined
		}
		if (typeof clearAt !== 'undefined') {
			state.clearAt = clearAt
		}
		if (typeof messageIsPredefined !== 'undefined') {
			state.messageIsPredefined = messageIsPredefined
		}
		if (typeof messageId !== 'undefined') {
			state.messageId = messageId
		}
	},
}

const getters = {}

const actions = {
	/**
	 * Re-fetches the status from the server
	 *
	 * @param {object} vuex The Vuex destructuring object
	 * @param {Function} vuex.commit The Vuex commit function
	 * @return {Promise<void>}
	 */
	async fetchBackupFromServer({ commit }) {
		try {
			const status = await fetchBackupStatus(getCurrentUser()?.uid)
			commit('loadBackupStatusFromServer', status)
		} catch (e) {
			// Ignore missing user backup status
		}
	},

	async revertBackupFromServer({ commit }, { messageId }) {
		const status = await revertToBackupStatus(messageId)
		if (status) {
			commit('loadBackupStatusFromServer', {})
			commit('loadStatusFromServer', status)
			emit('user_status:status.updated', {
				status: status.status,
				message: status.message,
				icon: status.icon,
				clearAt: status.clearAt,
				userId: getCurrentUser()?.uid,
			})
		}
	},
}

export default { state, mutations, getters, actions }
