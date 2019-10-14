/**
 * @copyright Copyright (c) 2019 John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @author John Molakvoæ <skjnldsv@protonmail.com>
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

export default class Sidebar {

	#state;
	#view;

	constructor() {
		// init empty state
		this.#state = {}

		// init default values
		this.#state.tabs = []
		this.#state.views = []
		this.#state.file = ''
		this.#state.activeTab = ''
		console.debug('OCA.Files.Sidebar initialized')
	}

	/**
	 * Get the sidebar state
	 *
	 * @readonly
	 * @memberof Sidebar
	 * @returns {Object} the data state
	 */
	get state() {
		return this.#state
	}

	/**
	 * @memberof Sidebar
	 * Register a new tab view
	 *
	 * @param {Object} tab a new unregistered tab
	 * @memberof Sidebar
	 * @returns {Boolean}
	 */
	registerTab(tab) {
		const hasDuplicate = this.#state.tabs.findIndex(check => check.name === tab.name) > -1
		if (!hasDuplicate) {
			this.#state.tabs.push(tab)
			return true
		}
		console.error(`An tab with the same name ${tab.name} already exists`, tab)
		return false
	}

	registerSecondaryView(view) {
		const hasDuplicate = this.#state.views.findIndex(check => check.name === view.name) > -1
		if (!hasDuplicate) {
			this.#state.views.push(view)
			return true
		}
		console.error(`A similar view already exists`, view)
		return false
	}

	/**
	 * Set the current sidebar file data
	 *
	 * @param {string} path the file path to load
	 * @memberof Sidebar
	 */
	set file(path) {
		this.#state.file = path
	}

	/**
	 * Set the current sidebar file data
	 *
	 * @returns {String} the current opened file
	 * @memberof Sidebar
	 */
	get file() {
		return this.#state.file
	}

	/**
	 * Set the current sidebar tab
	 *
	 * @param {string} id the tab unique id
	 * @memberof Sidebar
	 */
	set activeTab(id) {
		this.#state.activeTab = id
	}

}
