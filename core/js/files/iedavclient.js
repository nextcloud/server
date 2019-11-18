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
		 * @return {Promise}
		 */
		request : function(method, url, headers, body) {

			var self = this;
			var xhr = this.xhrProvider();
			headers = headers || {};

			if (this.userName) {
				headers.Authorization = 'Basic ' + btoa(this.userName + ':' + this.password);
				// xhr.open(method, this.resolveUrl(url), true, this.userName, this.password);
			}
			xhr.open(method, this.resolveUrl(url), true);
			var ii;
			for(ii in headers) {
				xhr.setRequestHeader(ii, headers[ii]);
			}

			if (body === undefined) {
				xhr.send();
			} else {
				xhr.send(body);
			}

			return new Promise(function(fulfill, reject) {

				xhr.onreadystatechange = function() {

					if (xhr.readyState !== 4) {
						return;
					}

					var resultBody = xhr.response;
					if (xhr.status === 207) {
						resultBody = self.parseMultiStatus(xhr.responseXML);
					}

					fulfill({
						body: resultBody,
						status: xhr.status,
						xhr: xhr
					});

				};

				xhr.ontimeout = function() {

					reject(new Error('Timeout exceeded'));

				};

			});

		},

		_getElementsByTagName: function(node, name, resolver) {
			var parts = name.split(':');
			var tagName = parts[1];
			var namespace = resolver(parts[0]);
			// make sure we can get elements
			if (typeof node === 'string') {
				var parser = new DOMParser()
				node = parser.parseFromString(node, 'text/xml')
			}
			if (node.getElementsByTagNameNS) {
				return node.getElementsByTagNameNS(namespace, tagName);
			}
			return node.getElementsByTagName(name);
		},

		/**
		 * Parses a multi-status response body.
		 *
		 * @param {string} xmlBody
		 * @param {Array}
		 */
		parseMultiStatus : function(doc) {
			var result = [];
			var resolver = function(foo) {
				var ii;
				for(ii in this.xmlNamespaces) {
					if (this.xmlNamespaces[ii] === foo) {
						return ii;
					}
				}
			}.bind(this);

			var responses = this._getElementsByTagName(doc, 'd:response', resolver);
			var i;
			for (i = 0; i < responses.length; i++) {
				var responseNode = responses[i];
				var response = {
					href : null,
					propStat : []
				};

				var hrefNode = this._getElementsByTagName(responseNode, 'd:href', resolver)[0];

				response.href = hrefNode.textContent || hrefNode.text;

				var propStatNodes = this._getElementsByTagName(responseNode, 'd:propstat', resolver);
				var j = 0;

				for (j = 0; j < propStatNodes.length; j++) {
					var propStatNode = propStatNodes[j];
					var statusNode = this._getElementsByTagName(propStatNode, 'd:status', resolver)[0];

					var propStat = {
						status : statusNode.textContent || statusNode.text,
						properties : []
					};

					var propNode = this._getElementsByTagName(propStatNode, 'd:prop', resolver)[0];
					if (!propNode) {
						continue;
					}
					var k = 0;
					for (k = 0; k < propNode.childNodes.length; k++) {
						var prop = propNode.childNodes[k];
						var value = this._parsePropNode(prop);
						propStat.properties['{' + prop.namespaceURI + '}' + (prop.localName || prop.baseName)] = value;

					}
					response.propStat.push(propStat);
				}

				result.push(response);
			}

			return result;

		}


	});

})(dav);

