/**
 * @copyright 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 * @author Julius Härtl <jus@bitgrid.net>
 * @author Ferdinand Thiessen <opensource@fthiessen.de>
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

const warnIfNotTesting = function() {
	if (window.TESTING === undefined) {
		OC.debug && console.warn.apply(console, arguments)
	}
}

/**
 * Mark a function as deprecated and automatically
 * warn if used!
 *
 * @param {Function} func the library to deprecate
 * @param {string} funcName the name of the library
 * @param {number} version the version this gets removed
 * @return {Function}
 */
export const deprecate = (func, funcName, version) => {
	const oldFunc = func
	const newFunc = function() {
		warnIfNotTesting(`The ${funcName} library is deprecated! It will be removed in nextcloud ${version}.`)
		return oldFunc.apply(this, arguments)
	}
	Object.assign(newFunc, oldFunc)
	return newFunc
}

export const setDeprecatedProp = (global, cb, msg) => {
	(Array.isArray(global) ? global : [global]).forEach(global => {
		if (window[global] !== undefined) {
			delete window[global]
		}
		Object.defineProperty(window, global, {
			get: () => {
				if (msg) {
					warnIfNotTesting(`${global} is deprecated: ${msg}`)
				} else {
					warnIfNotTesting(`${global} is deprecated`)
				}

				return cb()
			},
		})
	})
}
