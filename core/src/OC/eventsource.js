/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2015 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/* eslint-disable */
import $ from 'jquery'

import { getRequestToken } from './requesttoken.ts'

/**
 * Create a new event source
 * @param {string} src
 * @param {object} [data] to be send as GET
 *
 * @constructs OCEventSource
 */
const OCEventSource = function(src, data) {
	var dataStr = ''
	var name
	var joinChar
	this.typelessListeners = []
	this.closed = false
	this.listeners = {}
	if (data) {
		for (name in data) {
			dataStr += name + '=' + encodeURIComponent(data[name]) + '&'
		}
	}
	dataStr += 'requesttoken=' + encodeURIComponent(getRequestToken())
	if (!this.useFallBack && typeof EventSource !== 'undefined') {
		joinChar = '&'
		if (src.indexOf('?') === -1) {
			joinChar = '?'
		}
		this.source = new EventSource(src + joinChar + dataStr)
		this.source.onmessage = function(e) {
			for (var i = 0; i < this.typelessListeners.length; i++) {
				this.typelessListeners[i](JSON.parse(e.data))
			}
		}.bind(this)
	} else {
		var iframeId = 'oc_eventsource_iframe_' + OCEventSource.iframeCount
		OCEventSource.fallBackSources[OCEventSource.iframeCount] = this
		this.iframe = $('<iframe></iframe>')
		this.iframe.attr('id', iframeId)
		this.iframe.hide()

		joinChar = '&'
		if (src.indexOf('?') === -1) {
			joinChar = '?'
		}
		this.iframe.attr('src', src + joinChar + 'fallback=true&fallback_id=' + OCEventSource.iframeCount + '&' + dataStr)
		$('body').append(this.iframe)
		this.useFallBack = true
		OCEventSource.iframeCount++
	}
	// add close listener
	this.listen('__internal__', function(data) {
		if (data === 'close') {
			this.close()
		}
	}.bind(this))
}
OCEventSource.fallBackSources = []
OCEventSource.iframeCount = 0// number of fallback iframes
OCEventSource.fallBackCallBack = function(id, type, data) {
	OCEventSource.fallBackSources[id].fallBackCallBack(type, data)
}
OCEventSource.prototype = {
	typelessListeners: [],
	iframe: null,
	listeners: {}, // only for fallback
	useFallBack: false,
	/**
	 * Fallback callback for browsers that don't have the
	 * native EventSource object.
	 *
	 * Calls the registered listeners.
	 *
	 * @private
	 * @param {String} type event type
	 * @param {Object} data received data
	 */
	fallBackCallBack: function(type, data) {
		var i
		// ignore messages that might appear after closing
		if (this.closed) {
			return
		}
		if (type) {
			if (typeof this.listeners.done !== 'undefined') {
				for (i = 0; i < this.listeners[type].length; i++) {
					this.listeners[type][i](data)
				}
			}
		} else {
			for (i = 0; i < this.typelessListeners.length; i++) {
				this.typelessListeners[i](data)
			}
		}
	},
	lastLength: 0, // for fallback
	/**
	 * Listen to a given type of events.
	 *
	 * @param {String} type event type
	 * @param {Function} callback event callback
	 */
	listen: function(type, callback) {
		if (callback && callback.call) {

			if (type) {
				if (this.useFallBack) {
					if (!this.listeners[type]) {
						this.listeners[type] = []
					}
					this.listeners[type].push(callback)
				} else {
					this.source.addEventListener(type, function(e) {
						if (typeof e.data !== 'undefined') {
							callback(JSON.parse(e.data))
						} else {
							callback('')
						}
					}, false)
				}
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
		if (typeof this.source !== 'undefined') {
			this.source.close()
		}
	}
}

export default OCEventSource
