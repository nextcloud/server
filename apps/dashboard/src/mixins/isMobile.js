/**
 * @copyright Copyright (c) 2020 Julius Härtl <jus@bitgrid.net>
 *
 * @author Julius Härtl <jus@bitgrid.net>
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

export default {
	data() {
		return {
			isMobile: this._isMobile(),
		}
	},
	beforeMount() {
		window.addEventListener('resize', this._onResize)
	},
	beforeDestroy() {
		window.removeEventListener('resize', this._onResize)
	},
	methods: {
		_onResize() {
			// Update mobile mode
			this.isMobile = this._isMobile()
		},
		_isMobile() {
			// check if content width is under 768px
			return document.documentElement.clientWidth < 768
		},
	},
}
