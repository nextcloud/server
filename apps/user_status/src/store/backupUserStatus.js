/**
 * @copyright Copyright (c) 2020 Georg Ehrke
 *
 * @author Georg Ehrke <oc.list@georgehrke.com>
 * @author Carl Schwan <carl@carlschwan.eu>
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
	fetchCurrentBackupStatus,
} from '../services/statusService'

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
	loadBackupStatusFromServer(state, { status, statusIsUserDefined, message, icon, clearAt, messageIsPredefined, messageId }) {
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
	 * Fetches the backup status from the server
	 *
	 * @param {Object} vuex The Vuex destructuring object
	 * @param {Function} vuex.commit The Vuex commit function
	 * @returns {Promise<void>}
	 */
	async loadBackupStatus({ commit }) {
		const status = await fetchCurrentBackupStatus()
		if ('hasBackup' in status && status.hasBackup === false) {
			return
		}
		commit('loadBackupStatusFromServer', status)
	},
}

export default { state, mutations, getters, actions }
