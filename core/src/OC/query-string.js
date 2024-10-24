/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import $ from 'jquery'

/**
 * Parses a URL query string into a JS map
 *
 * @param {string} queryString query string in the format param1=1234&param2=abcde&param3=xyz
 * @return {Record<string, string>} map containing key/values matching the URL parameters
 */
export const parse = queryString => {
	let pos
	let components
	const result = {}
	let key
	if (!queryString) {
		return null
	}
	pos = queryString.indexOf('?')
	if (pos >= 0) {
		queryString = queryString.substr(pos + 1)
	}
	const parts = queryString.replace(/\+/g, '%20').split('&')
	for (let i = 0; i < parts.length; i++) {
		// split on first equal sign
		const part = parts[i]
		pos = part.indexOf('=')
		if (pos >= 0) {
			components = [
				part.substr(0, pos),
				part.substr(pos + 1),
			]
		} else {
			// key only
			components = [part]
		}
		if (!components.length) {
			continue
		}
		key = decodeURIComponent(components[0])
		if (!key) {
			continue
		}
		// if equal sign was there, return string
		if (components.length > 1) {
			result[key] = decodeURIComponent(components[1])
		} else {
			// no equal sign => null value
			result[key] = null
		}
	}
	return result
}

/**
 * Builds a URL query from a JS map.
 *
 * @param {Record<string, string>} params map containing key/values matching the URL parameters
 * @return {string} String containing a URL query (without question) mark
 */
export const build = params => {
	if (!params) {
		return ''
	}
	return $.map(params, function(value, key) {
		let s = encodeURIComponent(key)
		if (value !== null && typeof (value) !== 'undefined') {
			s += '=' + encodeURIComponent(value)
		}
		return s
	}).join('&')
}
