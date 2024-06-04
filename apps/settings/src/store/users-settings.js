/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { loadState } from '@nextcloud/initial-state'

const state = {
	serverData: loadState('settings', 'usersSettings', {}),
}
const mutations = {
	setServerData(state, data) {
		state.serverData = data
	},
}
const getters = {
	getServerData(state) {
		return state.serverData
	},
}
const actions = {}

export default { state, mutations, getters, actions }
