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

import Images from '../models/images'
import Videos from '../models/videos'

export default class Viewer {

	#state;

	constructor() {
		this.#state = {}
		this.#state.file = ''
		this.#state.files = []
		this.#state.handlers = []

		// ! built-in handlers
		this.registerHandler(Images)
		this.registerHandler(Videos)

		console.debug('OCA.Viewer initialized')
	}

	/**
	 * Get the sidebar state
	 * DO NOT EDIT properties within
	 *
	 * @readonly
	 * @memberof Sidebar
	 * @returns {Object} the data state
	 */
	get state() {
		return this.#state
	}

	/**
	 * Return the registered handlers
	 *
	 * @readonly
	 * @memberof Viewer
	 */
	get availableHandlers() {
		return this.#state.handlers
	}

	/**
	 * Register a new handler
	 *
	 * @memberof Viewer
	 * @param {Object} handler a new unregistered handler
	 */
	registerHandler(handler) {
		this.#state.handlers.push(handler)
	}

	/**
	 * Get the current opened file
	 *
	 * @memberof Viewer
	 * @returns {string} the currently opened file
	 */
	get file() {
		return this.#state.file
	}

	/**
	 * Open the path into the viewer
	 *
	 * @memberof Viewer
	 * @param {string} path the path to open
	 * @param {Object[]} [list] the list of files as objects (fileinfo) format
	 */
	open(path, list = []) {
		if (!path.startsWith('/')) {
			throw new Error('Please use an absolute path')
		}

		if (!Array.isArray(list)) {
			throw new Error('The files list must be an array')
		}

		this.#state.file = path
		this.#state.files = list
	}

	/**
	 * Close the opened file
	 *
	 * @memberof Viewer
	 */
	close() {
		this.#state.file = ''
		this.#state.files = []
	}

}
