/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import Vue from 'vue'
import Vuex, { Store } from 'vuex'
import { generateCodes } from './service/BackupCodesService.js'

Vue.use(Vuex)

const state = {
	enabled: false,
	total: 0,
	used: 0,
	codes: [],
}

const mutations = {
	setEnabled(state, enabled) {
		Vue.set(state, 'enabled', enabled)
	},
	setTotal(state, total) {
		Vue.set(state, 'total', total)
	},
	setUsed(state, used) {
		Vue.set(state, 'used', used)
	},
	setCodes(state, codes) {
		Vue.set(state, 'codes', codes)
	},
}

const actions = {
	generate({ commit }) {
		commit('setEnabled', false)

		return generateCodes().then(({ codes, state }) => {
			commit('setEnabled', state.enabled)
			commit('setTotal', state.total)
			commit('setUsed', state.used)
			commit('setCodes', codes)
			return true
		})
	},
}

export default new Store({
	strict: process.env.NODE_ENV !== 'production',
	state,
	mutations,
	actions,
})
