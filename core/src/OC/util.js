/**
 * @copyright 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
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

import moment from 'moment'

import History from './util-history.js'
import OC from './index.js'
import { formatFileSize, parseFileSize } from '@nextcloud/files'

/**
 * @param {any} t -
 */
function chunkify(t) {
	// Adapted from http://my.opera.com/GreyWyvern/blog/show.dml/1671288
	const tz = []
	let x = 0
	let y = -1
	let n = 0
	let c

	while (x < t.length) {
		c = t.charAt(x)
		// only include the dot in strings
		const m = ((!n && c === '.') || (c >= '0' && c <= '9'))
		if (m !== n) {
			// next chunk
			y++
			tz[y] = ''
			n = m
		}
		tz[y] += c
		x++
	}
	return tz
}

/**
 * Utility functions
 *
 * @namespace OC.Util
 */
export default {

	History,

	/**
	 * @deprecated use `formatFileSize` from `@nextcloud/files`, see https://nextcloud-libraries.github.io/nextcloud-files/functions/formatFileSize.html
	 */
	humanFileSize: formatFileSize,

	/**
	 * Returns a file size in bytes from a humanly readable string
	 * Makes 2KiB to 2048.
	 * Inspired by computerFileSize in helper.php
	 *
	 * @param  {string} value file size in human-readable format
 	 * @param  {boolean} forceBinary for backwards compatibility this allows values to be base 2 (so 2KB means 2048 bytes instead of 2000 bytes)
	 * @deprecated use `parseFileSize` from the `@nextcloud/files` library
	 */
	computerFileSize: (value, forceBinary) => parseFileSize(value, forceBinary ?? true),

	/**
	 * @param {string|number} timestamp timestamp
	 * @param {string} format date format, see momentjs docs
	 * @return {string} timestamp formatted as requested
	 */
	formatDate(timestamp, format) {
		if (window.TESTING === undefined) {
			OC.debug && console.warn('OC.Util.formatDate is deprecated and will be removed in Nextcloud 21. See @nextcloud/moment')
		}
		format = format || 'LLL'
		return moment(timestamp).format(format)
	},

	/**
	 * @param {string|number} timestamp timestamp
	 * @return {string} human readable difference from now
	 */
	relativeModifiedDate(timestamp) {
		if (window.TESTING === undefined) {
			OC.debug && console.warn('OC.Util.relativeModifiedDate is deprecated and will be removed in Nextcloud 21. See @nextcloud/moment')
		}
		const diff = moment().diff(moment(timestamp))
		if (diff >= 0 && diff < 45000) {
			return t('core', 'seconds ago')
		}
		return moment(timestamp).fromNow()
	},

	/**
	 * Returns the width of a generic browser scrollbar
	 *
	 * @return {number} width of scrollbar
	 */
	getScrollBarWidth() {
		if (this._scrollBarWidth) {
			return this._scrollBarWidth
		}

		const inner = document.createElement('p')
		inner.style.width = '100%'
		inner.style.height = '200px'

		const outer = document.createElement('div')
		outer.style.position = 'absolute'
		outer.style.top = '0px'
		outer.style.left = '0px'
		outer.style.visibility = 'hidden'
		outer.style.width = '200px'
		outer.style.height = '150px'
		outer.style.overflow = 'hidden'
		outer.appendChild(inner)

		document.body.appendChild(outer)
		const w1 = inner.offsetWidth
		outer.style.overflow = 'scroll'
		let w2 = inner.offsetWidth
		if (w1 === w2) {
			w2 = outer.clientWidth
		}

		document.body.removeChild(outer)

		this._scrollBarWidth = (w1 - w2)

		return this._scrollBarWidth
	},

	/**
	 * Remove the time component from a given date
	 *
	 * @param {Date} date date
	 * @return {Date} date with stripped time
	 */
	stripTime(date) {
		// FIXME: likely to break when crossing DST
		// would be better to use a library like momentJS
		return new Date(date.getFullYear(), date.getMonth(), date.getDate())
	},

	/**
	 * Compare two strings to provide a natural sort
	 *
	 * @param {string} a first string to compare
	 * @param {string} b second string to compare
	 * @return {number} -1 if b comes before a, 1 if a comes before b
	 * or 0 if the strings are identical
	 */
	naturalSortCompare(a, b) {
		let x
		const aa = chunkify(a)
		const bb = chunkify(b)

		for (x = 0; aa[x] && bb[x]; x++) {
			if (aa[x] !== bb[x]) {
				const aNum = Number(aa[x]); const bNum = Number(bb[x])
				// note: == is correct here
				/* eslint-disable-next-line */
				if (aNum == aa[x] && bNum == bb[x]) {
					return aNum - bNum
				} else {
					// Note: This locale setting isn't supported by all browsers but for the ones
					// that do there will be more consistency between client-server sorting
					return aa[x].localeCompare(bb[x], OC.getLanguage())
				}
			}
		}
		return aa.length - bb.length
	},

	/**
	 * Calls the callback in a given interval until it returns true
	 *
	 * @param {Function} callback function to call on success
	 * @param {number} interval in milliseconds
	 */
	waitFor(callback, interval) {
		const internalCallback = function() {
			if (callback() !== true) {
				setTimeout(internalCallback, interval)
			}
		}

		internalCallback()
	},

	/**
	 * Checks if a cookie with the given name is present and is set to the provided value.
	 *
	 * @param {string} name name of the cookie
	 * @param {string} value value of the cookie
	 * @return {boolean} true if the cookie with the given name has the given value
	 */
	isCookieSetToValue(name, value) {
		const cookies = document.cookie.split(';')
		for (let i = 0; i < cookies.length; i++) {
			const cookie = cookies[i].split('=')
			if (cookie[0].trim() === name && cookie[1].trim() === value) {
				return true
			}
		}
		return false
	},
}
