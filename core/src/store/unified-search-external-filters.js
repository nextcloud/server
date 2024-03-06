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
import { defineStore } from 'pinia'

export const useSearchStore = defineStore({
	id: 'search',

	state: () => ({
		externalFilters: [],
	}),

	actions: {
		registerExternalFilter({ id, appId, label, callback, icon }) {
			this.externalFilters.push({ id, appId, name: label, callback, icon, isPluginFilter: true })
		},
	},
})
