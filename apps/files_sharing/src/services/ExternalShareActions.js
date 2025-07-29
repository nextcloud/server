/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

export default class ExternalShareActions {

	_state

	constructor() {
		// init empty state
		this._state = {}

		// init default values
		this._state.actions = []
		console.debug('OCA.Sharing.ExternalShareActions initialized')
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
	 * @typedef ExternalShareActionData
	 * @property {import('vue').Component} is Vue component to render, for advanced actions the `async onSave` method of the component will be called when saved
	 */

	/**
	 * Register a new option/entry for the a given share type
	 *
	 * @param {object} action new action component to register
	 * @param {string} action.id unique action id
	 * @param {(data: any) => ExternalShareActionData & Record<string, unknown>} action.data data to bind the component to
	 * @param {Array} action.shareType list of \@nextcloud/sharing.Types.SHARE_XXX to be mounted on
	 * @param {boolean} action.advanced `true` if the action entry should be rendered within advanced settings
	 * @param {object} action.handlers list of listeners
	 * @return {boolean}
	 */
	registerAction(action) {
		// Validate action
		if (typeof action !== 'object'
			|| typeof action.id !== 'string'
			|| typeof action.data !== 'function' // () => {disabled: true}
			|| !Array.isArray(action.shareType) // [\@nextcloud/sharing.Types.Link, ...]
			|| typeof action.handlers !== 'object' // {click: () => {}, ...}
			|| !Object.values(action.handlers).every(handler => typeof handler === 'function')) {
			console.error('Invalid action provided', action)
			return false
		}

		// Check duplicates
		const hasDuplicate = this._state.actions.findIndex(check => check.id === action.id) > -1
		if (hasDuplicate) {
			console.error(`An action with the same id ${action.id} already exists`, action)
			return false
		}

		this._state.actions.push(action)
		return true
	}

}
