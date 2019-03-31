/*
 * @copyright 2019 Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @author 2019 Roeland Jago Douma <roeland@famdouma.nl>
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

import Vue from 'vue'
import Vuex from 'vuex'
import api from './api';

Vue.use(Vuex)

const state = {
	enforced: false,
	enforcedGroups: [],
	excludedGroups: [],
	enabledProvidersCurrentUser: [],
};

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
	setEnabledProvidersCurrentUser(state, providers) {
		Vue.set(state, 'enabledProvidersCurrentUser', providers)
	}
}

const getters = {
	getEnabledProvidersCurrentUser(state) {
		return state.enabledProvidersCurrentUser;
	}
}

const actions = {
	save ({commit}, ) {
		commit('setEnabled', false);

		return generateCodes()
			.then(({codes, state})  => {
			commit('setEnabled', state.enabled);
			commit('setTotal', state.total);
			commit('setUsed', state.used);
			commit('setCodes', codes);
			return true;
		});
	},
	getEnabledProvidersCurrentUser(context) {
		context.commit('startLoading', 'providers');
		var user = OC.getCurrentUser().uid;
		return api.get(OC.generateUrl(`/settings/api/users/${user}/twoFactorProviders`))
			.then((response) => {
				context.commit('setEnabledProvidersCurrentUser', response.data);
				context.commit('stopLoading', 'providers');
				return true;
			})
			.catch((error) => context.commit('API_FAILURE', error));
	},
}

export default {
	strict: process.env.NODE_ENV !== 'production',
	state,
	mutations,
	getters,
	actions
}
