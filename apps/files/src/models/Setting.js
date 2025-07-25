/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

export default class Setting {

	_close
	_el
	_name
	_open
	_order

	/**
	 * Create a new files app setting
	 *
	 * @since 19.0.0
	 * @param {string} name the name of this setting
	 * @param {object} component the component
	 * @param {Function} component.el function that returns an unmounted dom element to be added
	 * @param {Function} [component.open] callback for when setting is added
	 * @param {Function} [component.close] callback for when setting is closed
	 * @param {number} [component.order] the order of this setting, lower numbers are shown first
	 */
	constructor(name, { el, open, close, order }) {
		this._name = name
		this._el = el
		this._open = open
		this._close = close
		this._order = order || 0

		if (typeof this._open !== 'function') {
			this._open = () => {}
		}

		if (typeof this._close !== 'function') {
			this._close = () => {}
		}

		if (typeof this._el !== 'function') {
			throw new Error('Setting must have an `el` function that returns a DOM element')
		}

		if (typeof this._name !== 'string') {
			throw new Error('Setting must have a `name` string')
		}

		if (typeof this._order !== 'number') {
			throw new Error('Setting must have an `order` number')
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

	get order() {
		return this._order
	}

}
