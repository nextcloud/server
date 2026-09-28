/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

export default class ShareSearch {

	_state

	constructor() {
		// init empty state
		this._state = {}

		// init default values
		this._state.results = []
		console.debug('OCA.Sharing.ShareSearch initialized')
	}

	/**
	 * Get the state
	 *
	 * @readonly
	 * @memberof ShareSearch
	 * @return {object} the data state
	 */
	get state() {
		return this._state
	}

	/**
	 * Register a new result
	 * Mostly used by the guests app.
	 * We should consider deprecation and add results via php ?
	 *
	 * @param {object} result entry to append
	 * @param {string} [result.user] entry user
	 * @param {string} result.displayName entry first line
	 * @param {string} [result.desc] entry second line
	 * @param {string} [result.icon] entry icon
	 * @param {Function} result.handler function to run on entry selection
	 * @param {Function} [result.condition] condition to add entry or not
	 * @return {boolean}
	 */
	addNewResult(result) {
		if (result.displayName.trim() !== ''
			&& typeof result.handler === 'function') {
			this._state.results.push(result)
			return true
		}
		console.error('Invalid search result provided', result)
		return false
	}

}
