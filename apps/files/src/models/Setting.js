/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

export default class Setting {

	_close
	_el
	_name
	_open

	/**
	 * Create a new files app setting
	 *
	 * @since 19.0.0
	 * @param {string} name the name of this setting
	 * @param {object} component the component
	 * @param {Function} component.el function that returns an unmounted dom element to be added
	 * @param {Function} [component.open] callback for when setting is added
	 * @param {Function} [component.close] callback for when setting is closed
	 */
	constructor(name, { el, open, close }) {
		this._name = name
		this._el = el
		this._open = open
		this._close = close

		if (typeof this._open !== 'function') {
			this._open = () => {}
		}

		if (typeof this._close !== 'function') {
			this._close = () => {}
		}
	}

	get name() {
		return this._name
	}

	get el() {
		return this._el
	}

	get open() {
		return this._open
	}

	get close() {
		return this._close
	}

}
