/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import Vue from 'vue'
import Vuex, { Store } from 'vuex'

Vue.use(Vuex)

const state = {
	enforced: false,
	enforcedGroups: [],
	excludedGroups: [],
}

const mutations = {
	setEnforced(state, enabled) {
		Vue.set(state, 'enforced', enabled)
	},
	setEnforcedGroups(state, total) {
		Vue.set(state, 'enforcedGroups', total)
	},
	setExcludedGroups(state, used) {
		Vue.set(state, 'excludedGroups', used)
	},
}

export default new Store({
	strict: process.env.NODE_ENV !== 'production',
	state,
	mutations,
})
