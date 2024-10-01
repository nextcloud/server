/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

export default class Settings {

	_settings

	constructor() {
		this._settings = []
		console.debug('OCA.Files.Settings initialized')
	}

	/**
	 * Register a new setting
	 *
	 * @since 19.0.0
	 * @param {OCA.Files.Settings.Setting} view element to add to settings
	 * @return {boolean} whether registering was successful
	 */
	register(view) {
		if (this._settings.filter(e => e.name === view.name).length > 0) {
			console.error('A setting with the same name is already registered')
			return false
		}
		this._settings.push(view)
		return true
	}

	/**
	 * All settings elements
	 *
	 * @return {OCA.Files.Settings.Setting[]} All currently registered settings
	 */
	get settings() {
		return this._settings
	}

}
