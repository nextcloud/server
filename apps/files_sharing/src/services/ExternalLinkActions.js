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

export default class ExternalLinkActions {

	_state

	constructor() {
		// init empty state
		this._state = {}

		// init default values
		this._state.actions = []
		console.debug('OCA.Sharing.ExternalLinkActions initialized')
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
		console.warn('OCA.Sharing.ExternalLinkActions is deprecated, use OCA.Sharing.ExternalShareAction instead')

		if (typeof action === 'object' && action.icon && action.name && action.url) {
			this._state.actions.push(action)
			return true
		}
		console.error('Invalid action provided', action)
		return false
	}

}
