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
import Audios from '../models/audios'

export default class Viewer {

	#state;
	#mimetypes;

	constructor() {
		this.#mimetypes = []
		this.#state = {}
		this.#state.file = ''
		this.#state.files = []
		this.#state.loadMore = () => ([])
		this.#state.onPrev = () => {}
		this.#state.onNext = () => {}
		this.#state.onClose = () => {}
		this.#state.handlers = []

		// ! built-in handlers
		this.registerHandler(Images)
		this.registerHandler(Videos)
		this.registerHandler(Audios)

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
		this.#mimetypes.push.apply(this.#mimetypes, handler.mimes)
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
	 * Get the supported mimetypes that can be opened with the viewer
	 *
	 * @memberof Viewer
	 * @returns {array} list of mimetype strings that the viewer can open
	 */
	get mimetypes() {
		return this.#mimetypes
	}

	/**
	 * Open the path into the viewer
	 *
	 * @memberof Viewer
	 * @param {Object} options Options for opening the viewer
	 * @param {string} options.path path of the file to open
	 * @param {Object[]} [options.list] the list of files as objects (fileinfo) format
	 * @param {function} options.loadMore callback for loading more files
	 * @param {function} options.onPrev callback when navigating back to previous file
	 * @param {function} options.onNext callback when navigation forward to next file
	 * @param {function} options.onClose callback when closing the viewer
	 */
	open({ path, list = [], loadMore = () => ([]), onPrev = () => {}, onNext = () => {}, onClose = () => {} } = {}) {
		// TODO: remove legacy method in NC 20 ?
		if (typeof arguments[0] === 'string') {
			path = arguments[0]
			console.warn('Opening the viewer with a single string parameter is deprecated. Please use a destructuring object instead', `OCA.Viewer.open({ path: '${path}' })`)
		}

		if (!path.startsWith('/')) {
			throw new Error('Please use an absolute path')
		}

		if (!Array.isArray(list)) {
			throw new Error('The files list must be an array')
		}

		if (typeof loadMore !== 'function') {
			throw new Error('The loadMore method must be a function')
		}

		this.#state.file = path
		this.#state.files = list
		this.#state.loadMore = loadMore
		this.#state.onPrev = onPrev
		this.#state.onNext = onNext
		this.#state.onClose = onClose
	}

	/**
	 * Close the opened file
	 *
	 * @memberof Viewer
	 */
	close() {
		this.#state.file = ''
		this.#state.files = []
		this.#state.loadMore = () => ([])
	}

}
