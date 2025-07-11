/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { generateFilePath } from '@nextcloud/router'

const loadedScripts = {}
const loadedStylesheets = {}
/**
 * @namespace OCP
 * @class Loader
 */
export default {

	/**
	 * Load a script asynchronously
	 *
	 * @param {string} app the app name
	 * @param {string} file the script file name
	 * @return {Promise}
	 */
	loadScript(app, file) {
		const key = app + file
		if (Object.prototype.hasOwnProperty.call(loadedScripts, key)) {
			return Promise.resolve()
		}
		loadedScripts[key] = true
		return new Promise(function(resolve, reject) {
			const scriptPath = generateFilePath(app, 'js', file)
			const script = document.createElement('script')
			script.src = scriptPath
			script.setAttribute('nonce', btoa(OC.requestToken))
			script.onload = () => resolve()
			script.onerror = () => reject(new Error(`Failed to load script from ${scriptPath}`))
			document.head.appendChild(script)
		})
	},

	/**
	 * Load a stylesheet file asynchronously
	 *
	 * @param {string} app the app name
	 * @param {string} file the script file name
	 * @return {Promise}
	 */
	loadStylesheet(app, file) {
		const key = app + file
		if (Object.prototype.hasOwnProperty.call(loadedStylesheets, key)) {
			return Promise.resolve()
		}
		loadedStylesheets[key] = true
		return new Promise(function(resolve, reject) {
			const stylePath = generateFilePath(app, 'css', file)
			const link = document.createElement('link')
			link.href = stylePath
			link.type = 'text/css'
			link.rel = 'stylesheet'
			link.onload = () => resolve()
			link.onerror = () => reject(new Error(`Failed to load stylesheet from ${stylePath}`))
			document.head.appendChild(link)
		})
	},
}
