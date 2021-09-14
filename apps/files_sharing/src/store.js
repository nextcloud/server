/**
 * @copyright Copyright (c) 2021 Yogesh Shejwadkar <yogesh.shejwadkar@t-systems.com>
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

import Vue from 'vue'
import Vuex from 'vuex'
import Share from './models/Share'

Vue.use(Vuex)

const store = new Vuex.Store({
	state: {
		share: {
			type: Share,
			default: null,
		},
		option: {},
		fromInput: Boolean,
		currentTab: 'default',
	},
	mutations: {
		addShare(state, share) {
			state.share = share
			console.debug('this is addShare mutation', share)
		},
		addOption(state, option) {
			state.option = option
			console.debug('this is addOptions mutation', option)
		},
		addFromInput(state, fromInput) {
			state.fromInput = fromInput
			console.debug('this is addFromInput mutation', fromInput)
		},
		addCurrentTab(state, currentTab) {
			state.currentTab = currentTab
			console.debug('this is addCurrentTab mutation', currentTab)
		},
	},
	actions: {

	},
	getters: {
		getShare(state) {
			console.debug('this is getter getShare', state.share)
			return state.share
		},
		getOption(state) {
			console.debug('this is getter getOption', state.option)
			return state.option
		},
		getFromInput(state) {
			console.debug('this is getter getFromInput', state.fromInput)
			return state.fromInput
		},
		getCurrentTab(state) {
			console.debug('this is getter getCurrentTab', state.currentTab)
			return state.currentTab
		},
	},
})

export default store
