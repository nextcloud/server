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

	#component;
	#legacy;
	#name;

	/**
	 * Create a new tab instance
	 *
	 * @param {string} name the name of this tab
	 * @param {Object} component the vue component
	 * @param {boolean} [legacy] is this a legacy tab
	 */
	constructor(name, component, legacy) {
		this.#name = name
		this.#component = component
		this.#legacy = legacy === true

		if (this.#legacy) {
			console.warn('Legacy tabs are deprecated! They will be removed in nextcloud 20.')
		}

	}

	get name() {
		return this.#name
	}

	get component() {
		return this.#component
	}

	get isLegacyTab() {
		return this.#legacy === true
	}

}
