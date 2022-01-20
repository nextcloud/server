/**
 * @copyright Copyright (c) 2019 John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @author John Molakvoæ <skjnldsv@protonmail.com>
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
	 * Register a new option/entry for the a given share type
	 *
	 * @param {object} action new action component to register
	 * @param {string} action.id unique action id
	 * @param {Function} action.data data to bind the component to
	 * @param {Array} action.shareType list of OC.Share.SHARE_XXX to be mounted on
	 * @param {object} action.handlers list of listeners
	 * @return {boolean}
	 */
	registerAction(action) {
		// Validate action
		if (typeof action !== 'object'
			|| typeof action.id !== 'string'
			|| typeof action.data !== 'function' // () => {disabled: true}
			|| !Array.isArray(action.shareType) // [OC.Share.SHARE_TYPE_LINK, ...]
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
