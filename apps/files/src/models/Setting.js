/**
 * @copyright Copyright (c) 2019 Gary Kim <gary@garykim.dev>
 * @copyright Copyright (c) 2019 John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @author Gary Kim <gary@garykim.dev>
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
