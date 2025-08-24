/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { Store } from 'vuex'
import users from './users.js'
import apps from './apps.js'
import settings from './users-settings.js'
import oc from './oc.js'
import { showError } from '@nextcloud/dialogs'

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
