/**
 * @copyright Copyright (c) 2016 John Molakvoæ <skjnldsv@protonmail.com>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

// https://developer.mozilla.org/en-US/docs/Web/API/Element/closest#Polyfill

if (!Element.prototype.matches) {
	Element.prototype.matches
		= Element.prototype.msMatchesSelector
		|| Element.prototype.webkitMatchesSelector
}

if (!Element.prototype.closest) {
	Element.prototype.closest = function(s) {
		let el = this

		do {
			if (el.matches(s)) return el
			el = el.parentElement || el.parentNode
		} while (el !== null && el.nodeType === 1)
		return null
	}
}
