/*
 * @copyright Copyright (c) 2024 Fon E. Noel NFEBE <opensource@nfebe.com>
 *
 * @author Fon E. Noel NFEBE <opensource@nfebe.com>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
const state = {
	externalFilters: [],
}

const mutations = {
	registerExternalFilter(state, { id, label, callback, icon }) {
		state.externalFilters.push({ id, name: label, callback, icon, isPluginFilter: true })
	},
}

const actions = {
	registerExternalFilter({ commit }, { id, label, callback, icon }) {
		commit('registerExternalFilter', { id, label, callback, icon })
	},
}

export default {
	state,
	mutations,
	actions,
}
