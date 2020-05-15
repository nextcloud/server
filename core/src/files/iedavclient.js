/* eslint-disable */
/*
 * Copyright (c) 2015
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
 *
 */

/* global dav */
(function(dav) {

	/**
	 * Override davclient.js methods with IE-compatible logic
	 */
	dav.Client.prototype = _.extend({}, dav.Client.prototype, {

		/**
		 * Performs a HTTP request, and returns a Promise
		 *
		 * @param {string} method HTTP method
		 * @param {string} url Relative or absolute url
		 * @param {Object} headers HTTP headers as an object.
		 * @param {string} body HTTP request body.
		 * @returns {Promise}
		 */
		request: function(method, url, headers, body) {

			const self = this
			const xhr = this.xhrProvider()
			headers = headers || {}

			if (this.userName) {
				headers.Authorization = 'Basic ' + btoa(this.userName + ':' + this.password)
				// xhr.open(method, this.resolveUrl(url), true, this.userName, this.password);
			}
			xhr.open(method, this.resolveUrl(url), true)
			let ii
			for (ii in headers) {
				xhr.setRequestHeader(ii, headers[ii])
			}

			if (body === undefined) {
				xhr.send()
			} else {
				xhr.send(body)
			}

			return new Promise(function(fulfill, reject) {

				xhr.onreadystatechange = function() {

					if (xhr.readyState !== 4) {
						return
					}

					let resultBody = xhr.response
					if (xhr.status === 207) {
						resultBody = self.parseMultiStatus(xhr.responseXML)
					}

					fulfill({
						body: resultBody,
						status: xhr.status,
						xhr: xhr,
					})

				}

				xhr.ontimeout = function() {

					reject(new Error('Timeout exceeded'))

				}

			})

		},

		_getElementsByTagName: function(node, name, resolver) {
			const parts = name.split(':')
			const tagName = parts[1]
			const namespace = resolver(parts[0])
			// make sure we can get elements
			if (typeof node === 'string') {
				const parser = new DOMParser()
				node = parser.parseFromString(node, 'text/xml')
			}
			if (node.getElementsByTagNameNS) {
				return node.getElementsByTagNameNS(namespace, tagName)
			}
			return node.getElementsByTagName(name)
		},

		/**
		 * Parses a multi-status response body.
		 *
		 * @param {string} xmlBody
		 * @param {Array}
		 */
		parseMultiStatus: function(doc) {
			const result = []
			const resolver = function(foo) {
				let ii
				for (ii in this.xmlNamespaces) {
					if (this.xmlNamespaces[ii] === foo) {
						return ii
					}
				}
			}.bind(this)

			const responses = this._getElementsByTagName(doc, 'd:response', resolver)
			let i
			for (i = 0; i < responses.length; i++) {
				const responseNode = responses[i]
				const response = {
					href: null,
					propStat: [],
				}

				const hrefNode = this._getElementsByTagName(responseNode, 'd:href', resolver)[0]

				response.href = hrefNode.textContent || hrefNode.text

				const propStatNodes = this._getElementsByTagName(responseNode, 'd:propstat', resolver)
				let j = 0

				for (j = 0; j < propStatNodes.length; j++) {
					const propStatNode = propStatNodes[j]
					const statusNode = this._getElementsByTagName(propStatNode, 'd:status', resolver)[0]

					const propStat = {
						status: statusNode.textContent || statusNode.text,
						properties: [],
					}

					const propNode = this._getElementsByTagName(propStatNode, 'd:prop', resolver)[0]
					if (!propNode) {
						continue
					}
					let k = 0
					for (k = 0; k < propNode.childNodes.length; k++) {
						const prop = propNode.childNodes[k]
						const value = this._parsePropNode(prop)
						propStat.properties['{' + prop.namespaceURI + '}' + (prop.localName || prop.baseName)] = value

					}
					response.propStat.push(propStat)
				}

				result.push(response)
			}

			return result

		},

	})

})(dav)
