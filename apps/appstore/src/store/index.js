/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { showError } from '@nextcloud/dialogs'
import { Store } from 'vuex'
import logger from '../utils/logger.js'
import apps from './apps.js'

const mutations = {
	API_FAILURE(state, error) {
		try {
			const message = error.error.response.data.ocs.meta.message
			showError(t('settings', 'An error occurred during the request. Unable to proceed.') + '<br>' + message, { isHTML: true })
		} catch {
			showError(t('settings', 'An error occurred during the request. Unable to proceed.'))
		}
		logger.error('An error occurred during the request.', { state, error })
	},
}

let store = null

/**
 *
 */
export function useStore() {
	if (store === null) {
		store = new Store({
			modules: {
				apps,
			},
			strict: !PRODUCTION,
			mutations,
		})
	}
	return store
}
