/**
 * @copyright Copyright (c) 2018 John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 * @author Julius Härtl <jus@bitgrid.net>
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

import Vue from 'vue'
import Vuex, { Store } from 'vuex'
import users from './users.js'
import apps from './apps.js'
import settings from './users-settings.js'
import oc from './oc.js'
import { showError } from '@nextcloud/dialogs'

Vue.use(Vuex)

const debug = process.env.NODE_ENV !== 'production'

const mutations = {
	API_FAILURE(state, error) {
		try {
			const message = error.error.response.data.ocs.meta.message
			showError(t('settings', 'An error occurred during the request. Unable to proceed.') + '<br>' + message, { isHTML: true })
		} catch (e) {
			showError(t('settings', 'An error occurred during the request. Unable to proceed.'))
		}
		console.error(state, error)
	},
}

let store = null

export const useStore = () => {
	if (store === null) {
		store = new Store({
			modules: {
				users,
				apps,
				settings,
				oc,
			},
			strict: debug,
			mutations,
		})
	}
	return store
}
