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
	 * Override davclient.js methods with IE8-compatible logic
	 */
	dav.Client.prototype = _.extend({}, dav.Client.prototype, {

		/**
		 * Generates a propFind request.
		 *
		 * @param {string} url Url to do the propfind request on
		 * @param {Array} properties List of properties to retrieve.
		 * @return {Promise}
		 */
		propFind : function(url, properties, depth) {

			if(typeof depth == "undefined") {
				depth = 0;
			}

			var headers = {
				Depth          : depth,
				'Content-Type' : 'application/xml; charset=utf-8'
			};

			var body =
				'<?xml version="1.0"?>\n' +
				'<d:propfind ';

			var namespace;
			for (namespace in this.xmlNamespaces) {
				body += ' xmlns:' + this.xmlNamespaces[namespace] + '="' + namespace + '"';
			}
			body += '>\n' +
				'  <d:prop>\n';

			for(var ii in properties) {
				var propText = properties[ii];
				if (typeof propText !== 'string') {
					// can happen on IE8
					continue;
				}
				var property = this.parseClarkNotation(properties[ii]);
				if (this.xmlNamespaces[property.namespace]) {
					body+='    <' + this.xmlNamespaces[property.namespace] + ':' + property.name + ' />\n';
				} else {
					body+='    <x:' + property.name + ' xmlns:x="' + property.namespace + '" />\n';
				}

			}
			body+='  </d:prop>\n';
			body+='</d:propfind>';

			return this.request('PROPFIND', url, headers, body).then(
				function(result) {
					var elements = this.parseMultiStatus(result.xhr.responseXML);
					var response;
					if (depth===0) {
						response = {
							status: result.status,
							body: elements[0]
						};
					} else {
						response = {
							status: result.status,
							body: elements
						};
					}
					return response;

				}.bind(this)
			);

		},


		_getElementsByTagName: function(node, name, resolver) {
			var parts = name.split(':');
			var tagName = parts[1];
			var namespace = resolver(parts[0]);
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
						var value = prop.textContent || prop.text;
						if (prop.childNodes && prop.childNodes.length > 0 && prop.childNodes[0].nodeType === 1) {
							value = prop.childNodes;
						}
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

