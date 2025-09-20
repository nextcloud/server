/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import logger from './logger.ts'

export default class ExternalLinkActions {

	_state

	constructor() {
		// init empty state
		this._state = {}

		// init default values
		this._state.actions = []
		logger.debug('OCA.Sharing.ExternalLinkActions initialized')
	}

	/**
	 * Get the state
	 *
	 * @readonly
	 * @memberof ExternalLinkActions
	 * @return {object} the data state
	 */
	get state() {
		return this._state
	}

	/**
	 * Register a new action for the link share
	 * Mostly used by the social sharing app.
	 *
	 * @param {object} action new action component to register
	 * @return {boolean}
	 */
	registerAction(action) {
		logger.warn('OCA.Sharing.ExternalLinkActions is deprecated, use `registerSidebarAction` from `@nextcloud/sharing` instead')

		if (typeof action === 'object' && action.icon && action.name && action.url) {
			this._state.actions.push(action)
			return true
		}
		logger.error('Invalid action provided', action)
		return false
	}

}
