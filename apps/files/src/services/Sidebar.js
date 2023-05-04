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

export default class Sidebar {

	_state

	constructor() {
		// init empty state
		this._state = {}

		// init default values
		this._state.tabs = []
		this._state.views = []
		this._state.file = ''
		this._state.activeTab = ''
		console.debug('OCA.Files.Sidebar initialized')
	}

	/**
	 * Get the sidebar state
	 *
	 * @readonly
	 * @memberof Sidebar
	 * @return {object} the data state
	 */
	get state() {
		return this._state
	}

	/**
	 * Register a new tab view
	 *
	 * @memberof Sidebar
	 * @param {object} tab a new unregistered tab
	 * @return {boolean}
	 */
	registerTab(tab) {
		const hasDuplicate = this._state.tabs.findIndex(check => check.id === tab.id) > -1
		if (!hasDuplicate) {
			this._state.tabs.push(tab)
			return true
		}
		console.error(`An tab with the same id ${tab.id} already exists`, tab)
		return false
	}

	registerSecondaryView(view) {
		const hasDuplicate = this._state.views.findIndex(check => check.id === view.id) > -1
		if (!hasDuplicate) {
			this._state.views.push(view)
			return true
		}
		console.error('A similar view already exists', view)
		return false
	}

	/**
	 * Return current opened file
	 *
	 * @memberof Sidebar
	 * @return {string} the current opened file
	 */
	get file() {
		return this._state.file
	}

	/**
	 * Set the current visible sidebar tab
	 *
	 * @memberof Sidebar
	 * @param {string} id the tab unique id
	 */
	setActiveTab(id) {
		this._state.activeTab = id
	}

}
