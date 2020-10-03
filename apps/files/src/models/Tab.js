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

export default class Tab {

	#id
	#name
	#icon
	#render
	#enabled

	/**
	 * Create a new tab instance
	 *
	 * @param {Object} options destructuring object
	 * @param {string} options.id the unique id of this tab
	 * @param {string} options.name the translated tab name
	 * @param {string} options.icon the vue component
	 * @param {Function} options.render function to render the tab
	 * @param {Function} [options.enabled] define conditions whether this tab is active. Must returns a boolean
	 */
	constructor({ id, name, icon, render, enabled }) {
		if (enabled === undefined) {
			enabled = () => true
		}

		// Sanity checks
		if (typeof id !== 'string' || id.trim() === '') {
			throw new Error('The id argument is not a valid string')
		}
		if (typeof name !== 'string' || name.trim() === '') {
			throw new Error('The name argument is not a valid string')
		}
		if (typeof icon !== 'string' || icon.trim() === '') {
			throw new Error('The icon argument is not a valid string')
		}
		if (typeof render !== 'function') {
			throw new Error('The render argument should be a function')
		}
		if (typeof enabled !== 'function') {
			throw new Error('The enabled argument should be a function')
		}

		this.#id = id
		this.#name = name
		this.#icon = icon
		this.#render = render
		this.#enabled = enabled

	}

	get id() {
		return this.#id
	}

	get name() {
		return this.#name
	}

	get icon() {
		return this.#icon
	}

	get render() {
		return this.#render
	}

	get enabled() {
		return this.#enabled
	}

}
