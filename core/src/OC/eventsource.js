/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2015 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { getRequestToken } from './requesttoken.ts'

/**
 * Create a new event source
 *
 * @param {string} src
 * @param {object} [data] to be send as GET
 *
 * @constructs OCEventSource
 */
function OCEventSource(src, data) {
	let dataStr = ''
	let name
	let joinChar
	this.typelessListeners = []
	this.closed = false
	if (data) {
		for (name in data) {
			dataStr += name + '=' + encodeURIComponent(data[name]) + '&'
		}
	}
	dataStr += 'requesttoken=' + encodeURIComponent(getRequestToken())
	joinChar = '&'
	if (src.indexOf('?') === -1) {
		joinChar = '?'
	}
	this.source = new EventSource(src + joinChar + dataStr)
	this.source.onmessage = function(e) {
		for (let i = 0; i < this.typelessListeners.length; i++) {
			this.typelessListeners[i](JSON.parse(e.data))
		}
	}.bind(this)
	// add close listener
	this.listen('__internal__', function(data) {
		if (data === 'close') {
			this.close()
		}
	}.bind(this))
}
OCEventSource.prototype = {
	typelessListeners: [],
	/**
	 * Listen to a given type of events.
	 *
	 * @param {string} type event type
	 * @param {Function} callback event callback
	 */
	listen: function(type, callback) {
		if (callback && callback.call) {
			if (type) {
				this.source.addEventListener(type, function(e) {
					if (typeof e.data !== 'undefined') {
						callback(JSON.parse(e.data))
					} else {
						callback('')
					}
				}, false)
			} else {
				this.typelessListeners.push(callback)
			}
		}
	},
	/**
	 * Closes this event source.
	 */
	close: function() {
		this.closed = true
		this.source.close()
	},
}

export default OCEventSource
