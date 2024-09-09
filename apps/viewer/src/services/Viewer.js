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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

import Images from '../models/images.js'
import Videos from '../models/videos.js'
import Audios from '../models/audios.js'
import logger from './logger.js'

/**
 * Handler type definition
 *
 * @typedef {object} Handler
 * @property {string} id unique identifier for the handler
 * @property {string[]} mimes list of mime types that are supported for opening
 * @property {object} component Vue component to render the file
 * @property {string} group group identifier to combine for navigating to the next/previous files
 * @property {?string} theme viewer modal theme (one of 'dark', 'light', 'default')
 * @property {boolean} canCompare Indicate support for comparing two files
 */

/**
 * File info type definition
 *
 * @typedef {object} Fileinfo
 * @property {string} filename File path of the remote item
 * @property {string} basename Base filename of the remote item, no path
 * @property {?string} source absolute path of a non-dav file, e.g. a static resource or provided by an app route
 * @property {string} mime file MIME type in the format type/sub-type
 * @property {string} [previewUrl] URL of the file preview
 * @property {boolean} hasPreview is there a WebDAV preview of this file?
 * @property {number} fileid Nextcloud file ID
 */

export default class Viewer {

	_state
	_mimetypes
	_mimetypesCompare

	constructor() {
		this._mimetypes = []
		this._mimetypesCompare = []
		this._state = {}
		this._state.file = ''
		this._state.fileInfo = null
		this._state.compareFileInfo = null
		this._state.files = []
		this._state.enableSidebar = true
		this._state.el = null
		this._state.loadMore = () => ([])
		this._state.onPrev = () => {}
		this._state.onNext = () => {}
		this._state.onClose = () => {}
		this._state.canLoop = true
		this._state.handlers = []
		this._state.overrideHandlerId = null

		// ! built-in handlers
		this.registerHandler(Images)
		this.registerHandler(Videos)
		this.registerHandler(Audios)

		logger.debug('OCA.Viewer initialized')
	}

	/**
	 * Return the registered handlers
	 *
	 * @readonly
	 * @memberof Viewer
	 * @return {Handler[]}
	 */
	get availableHandlers() {
		return this._state.handlers
	}

	/**
	 * Register a new handler
	 *
	 * @memberof Viewer
	 * @param {Handler} handler a new unregistered handler
	 */
	registerHandler(handler) {
		const error = this.validateHandler(handler)
		if (error) {
			logger.error('Could not register handler', { error, handler })
			return
		}

		this._state.handlers.push(handler)
		const handledMimes = [
			...handler.mimes,
			...Object.keys(handler.mimesAliases || {}),
		]
		this._mimetypes.push.apply(this._mimetypes, handledMimes)
		if (handler?.canCompare === true) {
			this._mimetypesCompare.push.apply(this._mimetypesCompare, handledMimes)
		}
	}

	validateHandler({ id, mimes, mimesAliases, component }) {
		// checking valid handler id
		if (!id || id.trim() === '' || typeof id !== 'string') {
			return 'The handler doesn\'t have a valid id'
		}

		// checking if handler is not already registered
		if (this._state.handlers.find(h => h.id === id)) {
			return 'The handler is already registered'
		}

		// Nothing available to process! Failure
		if (!(mimes && Array.isArray(mimes)) && !mimesAliases) {
			return 'Handler needs a valid mime array or mimesAliases'
		}

		// checking valid handler component data
		if ((!component || (typeof component !== 'object' && typeof component !== 'function'))) {
			return 'The handler doesn\'t have a valid component'
		}
	}

	/**
	 * Get the current opened file
	 *
	 * @memberof Viewer
	 * @return {string} the currently opened file
	 */
	get file() {
		return this._state.file
	}

	/**
	 * Get the current opened file fileInfo
	 *
	 * @memberof Viewer
	 * @return {?Fileinfo} the currently opened file fileInfo
	 */
	get fileInfo() {
		return this._state.fileInfo
	}

	/**
	 * Get the current comparison view opened file fileInfo
	 *
	 * @memberof Viewer
	 * @return {?Fileinfo} the currently opened file fileInfo
	 */
	get compareFileInfo() {
		return this._state.compareFileInfo
	}

	/**
	 * Get the current files list
	 *
	 * @memberof Viewer
	 * @return {Fileinfo[]} the current files list
	 */
	get files() {
		return this._state.files
	}

	/**
	 * Whether to enable the sidebar or not
	 *
	 * @memberof Viewer
	 * @return {boolean} whether to enable the sidebar or not
	 */
	get enableSidebar() {
		return this._state.enableSidebar
	}

	/**
	 * Get the element to render the current file in
	 *
	 * @memberof Viewer
	 * @return {string} selector of the element
	 */
	get el() {
		return this._state.el
	}

	/**
	 * Get the supported mimetypes that can be opened with the viewer
	 *
	 * @memberof Viewer
	 * @return {Array} list of mimetype strings that the viewer can open
	 */
	get mimetypes() {
		return this._mimetypes
	}

	/**
	 * Get the supported mimetypes that can be opened side by side for comparison
	 *
	 * @memberof Viewer
	 * @return {Array} list of mimetype strings that the viewer can open side by side for comparison
	 */
	get mimetypesCompare() {
		return this._mimetypesCompare
	}

	/**
	 * Return the method provided to fetch more results
	 *
	 * @memberof Viewer
	 * @return {Function}
	 */
	get loadMore() {
		return this._state.loadMore
	}

	/**
	 * Get the method to run on previous navigation
	 *
	 * @memberof Viewer
	 * @return {Function}
	 */
	get onPrev() {
		return this._state.onPrev
	}

	/**
	 * Get the method to run on next navigation
	 *
	 * @memberof Viewer
	 * @return {Function}
	 */
	get onNext() {
		return this._state.onNext
	}

	/**
	 * Get the method to run on viewer close
	 *
	 * @memberof Viewer
	 * @return {Function}
	 */
	get onClose() {
		return this._state.onClose
	}

	/**
	 * Is looping over the provided list allowed?
	 *
	 * @memberof Viewer
	 * @return {boolean}
	 */
	get canLoop() {
		return this._state.canLoop
	}

	/**
	 * If this handler is set, it should be used for viewing the next file.
	 *
	 * @memberof Viewer
	 */
	get overrideHandlerId() {
		return this._state.overrideHandlerId
	}

	/**
	 * Set element to open viewer in
	 *
	 * @memberof Viewer
	 * @param {string} el selector of the element to render the file in
	 */
	setRootElement(el = null) {
		if (this._state.file) {
			throw new Error('Please set root element before calling Viewer.open().')
		}
		this._state.el = el
	}

	/**
	 * Open the path into the viewer
	 *
	 * @memberof Viewer
	 * @param {object} options Options for opening the viewer
	 * @param {?string} options.path path of the file to open
	 * @param {?Fileinfo} options.fileInfo file info of the file to open
	 * @param {Fileinfo[]} [options.list] the list of files as objects (fileinfo) format
	 * @param {boolean} options.enableSidebar whether to enable the sidebar or not
	 * @param {Function} options.loadMore callback for loading more files
	 * @param {boolean} options.canLoop can the viewer loop over the array
	 * @param {Function} options.onPrev callback when navigating back to previous file
	 * @param {Function} options.onNext callback when navigation forward to next file
	 * @param {Function} options.onClose callback when closing the viewer
	 */
	open({ path, fileInfo, list = [], enableSidebar = true, loadMore = () => ([]), canLoop = true, onPrev = () => {}, onNext = () => {}, onClose = () => {} } = {}) {
		if (typeof arguments[0] === 'string') {
			throw new Error('Opening the viewer with a single string parameter is deprecated. Please use a destructuring object instead', `OCA.Viewer.open({ path: '${path}' })`)
		}
		if (!path && !fileInfo) {
			throw new Error('Viewer needs either an URL or path to open. None given')
		}

		if (path && !path.startsWith('/')) {
			throw new Error('Please use an absolute path')
		}

		if (!Array.isArray(list)) {
			throw new Error('The files list must be an array')
		}

		if (typeof loadMore !== 'function') {
			throw new Error('The loadMore method must be a function')
		}

		// Only assign the one that is used to prevent false watcher runs
		if (path) {
			this._state.file = path
		} else {
			this._state.fileInfo = fileInfo
		}
		if (!this._state.el) {
			this._state.files = list
			this._state.enableSidebar = enableSidebar
			this._state.loadMore = loadMore
			this._state.onPrev = onPrev
			this._state.onNext = onNext
			this._state.onClose = onClose
			this._state.canLoop = canLoop
		}
	}

	/**
	 * Open the path into the viewer
	 *
	 * @memberof Viewer
	 * @param {object} handlerId ID of the handler with which to open the files
	 * @param {object} options Options for opening the viewer
	 * @param {string} options.path path of the file to open
	 * @param {object[]} [options.list] the list of files as objects (fileinfo) format
	 * @param {boolean} [options.enableSidebar] Whether to enable the sidebar or not
	 * @param {Function} options.loadMore callback for loading more files
	 * @param {boolean} options.canLoop can the viewer loop over the array
	 * @param {Function} options.onPrev callback when navigating back to previous file
	 * @param {Function} options.onNext callback when navigation forward to next file
	 * @param {Function} options.onClose callback when closing the viewer
	 */
	openWith(handlerId, options = {}) {
		this._state.overrideHandlerId = handlerId
		this.open(options)
	}

	/**
	 * Open the viewer with two files side by side
	 *
	 * @memberof Viewer
	 * @param {Fileinfo} fileInfo current file
	 * @param {Fileinfo} compareFileInfo older file to compare
	 */
	compare(fileInfo, compareFileInfo) {
		this.open({
			fileInfo,
		})
		this._state.compareFileInfo = compareFileInfo
	}

	/**
	 * Close the opened file
	 *
	 * @memberof Viewer
	 */
	close() {
		this._state.file = ''
		this._state.fileInfo = null
		this._state.files = []
		this._state.enableSidebar = true
		this._state.canLoop = true
		this._state.loadMore = () => ([])
		this._state.overrideHandlerId = null
	}

}
