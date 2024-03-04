/*
 * Copyright (c) 2014
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
 *
 */

(function() {
	OC.SetupChecks = {

		/* Message types */
		MESSAGE_TYPE_INFO:0,
		MESSAGE_TYPE_WARNING:1,
		MESSAGE_TYPE_ERROR:2,
		/**
		 * Check whether the WebDAV connection works.
		 *
		 * @return $.Deferred object resolved with an array of error messages
		 */
		checkWebDAV: function() {
			var deferred = $.Deferred();
			var afterCall = function(xhr) {
				var messages = [];
				if (xhr.status !== 207 && xhr.status !== 401) {
					messages.push({
						msg: t('core', 'Your web server is not yet properly set up to allow file synchronization, because the WebDAV interface seems to be broken.'),
						type: OC.SetupChecks.MESSAGE_TYPE_ERROR
					});
				}
				deferred.resolve(messages);
			};

			$.ajax({
				type: 'PROPFIND',
				url: OC.linkToRemoteBase('webdav'),
				data: '<?xml version="1.0"?>' +
						'<d:propfind xmlns:d="DAV:">' +
						'<d:prop><d:resourcetype/></d:prop>' +
						'</d:propfind>',
				contentType: 'application/xml; charset=utf-8',
				complete: afterCall,
				allowAuthErrors: true
			});
			return deferred.promise();
		},

		/**
		 * Check whether the .well-known URLs works.
		 *
		 * @param url the URL to test
		 * @param placeholderUrl the placeholder URL - can be found at OC.theme.docPlaceholderUrl
		 * @param {boolean} runCheck if this is set to false the check is skipped and no error is returned
		 * @param {int|int[]} expectedStatus the expected HTTP status to be returned by the URL, 207 by default
		 * @return $.Deferred object resolved with an array of error messages
		 */
		checkWellKnownUrl: function(verb, url, placeholderUrl, runCheck, expectedStatus, checkCustomHeader) {
			if (expectedStatus === undefined) {
				expectedStatus = [207];
			}

			if (!Array.isArray(expectedStatus)) {
				expectedStatus = [expectedStatus];
			}

			var deferred = $.Deferred();

			if(runCheck === false) {
				deferred.resolve([]);
				return deferred.promise();
			}
			var afterCall = function(xhr) {
				var messages = [];
				var customWellKnown = xhr.getResponseHeader('X-NEXTCLOUD-WELL-KNOWN')
				if (expectedStatus.indexOf(xhr.status) === -1 || (checkCustomHeader && !customWellKnown)) {
					var docUrl = placeholderUrl.replace('PLACEHOLDER', 'admin-setup-well-known-URL');
					messages.push({
						msg: t('core', 'Your web server is not properly set up to resolve "{url}". Further information can be found in the {linkstart}documentation ↗{linkend}.', { url: url })
							.replace('{linkstart}', '<a target="_blank" rel="noreferrer noopener" class="external" href="' + docUrl + '">')
							.replace('{linkend}', '</a>'),
						type: OC.SetupChecks.MESSAGE_TYPE_INFO
					});
				}
				deferred.resolve(messages);
			};

			$.ajax({
				type: verb,
				url: url,
				complete: afterCall,
				allowAuthErrors: true
			});
			return deferred.promise();
		},

		/**
		 * Runs setup checks on the server side
		 *
		 * @return $.Deferred object resolved with an array of error messages
		 */
		checkSetup: function() {
			var deferred = $.Deferred();
			var afterCall = function(data, statusText, xhr) {
				var messages = [];
				if (xhr.status === 200 && data) {
					if (Object.keys(data.generic).length > 0) {
						Object.keys(data.generic).forEach(function(key){
							Object.keys(data.generic[key]).forEach(function(title){
								if (data.generic[key][title].severity != 'success') {
									data.generic[key][title].pass = false;
									OC.SetupChecks.addGenericSetupCheck(data.generic[key], title, messages);
								}
							});
						});
					}
				} else {
					messages.push({
						msg: t('core', 'Error occurred while checking server setup'),
						type: OC.SetupChecks.MESSAGE_TYPE_ERROR
					});
				}
				deferred.resolve(messages);
			};

			$.ajax({
				type: 'GET',
				url: OC.generateUrl('settings/ajax/checksetup'),
				allowAuthErrors: true
			}).then(afterCall, afterCall);
			return deferred.promise();
		},

		escapeHTML: function(text) {
			return text.toString()
				.split('&').join('&amp;')
				.split('<').join('&lt;')
				.split('>').join('&gt;')
				.split('"').join('&quot;')
				.split('\'').join('&#039;')
		},

		/**
		* @param message      The message string containing placeholders.
		* @param parameters   An object with keys as placeholders and values as their replacements.
		*
		* @return The message with placeholders replaced by values.
		*/
		richToParsed: function (message, parameters) {
			for (var [placeholder, parameter] of Object.entries(parameters)) {
				var replacement;
				if (parameter.type === 'user') {
					replacement = '@' + this.escapeHTML(parameter.name);
				} else if (parameter.type === 'file') {
					replacement = this.escapeHTML(parameter.path) || this.escapeHTML(parameter.name);
				} else if (parameter.type === 'highlight') {
					replacement = '<a href="' + encodeURI(parameter.link) + '">' + this.escapeHTML(parameter.name) + '</a>';
				} else {
					replacement = this.escapeHTML(parameter.name);
				}
				message = message.replace('{' + placeholder + '}', replacement);
			}

			return message;
		},

		addGenericSetupCheck: function(data, check, messages) {
			var setupCheck = data[check] || { pass: true, description: '', severity: 'info', linkToDoc: null}

			var type = OC.SetupChecks.MESSAGE_TYPE_INFO
			if (setupCheck.severity === 'warning') {
				type = OC.SetupChecks.MESSAGE_TYPE_WARNING
			} else if (setupCheck.severity === 'error') {
				type = OC.SetupChecks.MESSAGE_TYPE_ERROR
			}

			var message = setupCheck.description;
			if (message) {
				message = this.escapeHTML(message)
			}
			if (setupCheck.descriptionParameters) {
				message = this.richToParsed(message, setupCheck.descriptionParameters);
			}
			if (setupCheck.linkToDoc) {
				message += ' ' + t('core', 'For more details see the {linkstart}documentation ↗{linkend}.')
					.replace('{linkstart}', '<a target="_blank" rel="noreferrer noopener" class="external" href="' + setupCheck.linkToDoc + '">')
					.replace('{linkend}', '</a>');
			}
			if (setupCheck.elements) {
				message += '<br><ul>'
				setupCheck.elements.forEach(function(element){
					message += '<li>';
					message += element
					message += '</li>';
				});
				message += '</ul>'
			}

			if (!setupCheck.pass) {
				messages.push({
					msg: message,
					type: type,
				})
			}
		},

		/**
		 * Runs generic checks on the server side, the difference to dedicated
		 * methods is that we use the same XHR object for all checks to save
		 * requests.
		 *
		 * @return $.Deferred object resolved with an array of error messages
		 */
		checkGeneric: function() {
			var self = this;
			var deferred = $.Deferred();
			var afterCall = function(data, statusText, xhr) {
				var messages = [];
				messages = messages.concat(self._checkSecurityHeaders(xhr));
				messages = messages.concat(self._checkSSL(xhr));
				deferred.resolve(messages);
			};

			$.ajax({
				type: 'GET',
				url: OC.generateUrl('heartbeat'),
				allowAuthErrors: true
			}).then(afterCall, afterCall);

			return deferred.promise();
		},

		/**
		 * Runs check for some generic security headers on the server side
		 *
		 * @param {Object} xhr
		 * @return {Array} Array with error messages
		 */
		_checkSecurityHeaders: function(xhr) {
			var messages = [];

			if (xhr.status === 200) {
				var securityHeaders = {
					'X-Content-Type-Options': ['nosniff'],
					'X-Robots-Tag': ['noindex, nofollow'],
					'X-Frame-Options': ['SAMEORIGIN', 'DENY'],
					'X-Permitted-Cross-Domain-Policies': ['none'],
				};
				for (var header in securityHeaders) {
					var option = securityHeaders[header][0];
					if(!xhr.getResponseHeader(header) || xhr.getResponseHeader(header).replace(/, /, ',').toLowerCase() !== option.replace(/, /, ',').toLowerCase()) {
						var msg = t('core', 'The "{header}" HTTP header is not set to "{expected}". This is a potential security or privacy risk, as it is recommended to adjust this setting accordingly.', {header: header, expected: option});
						if(xhr.getResponseHeader(header) && securityHeaders[header].length > 1 && xhr.getResponseHeader(header).toLowerCase() === securityHeaders[header][1].toLowerCase()) {
							msg = t('core', 'The "{header}" HTTP header is not set to "{expected}". Some features might not work correctly, as it is recommended to adjust this setting accordingly.', {header: header, expected: option});
						}
						messages.push({
							msg: msg,
							type: OC.SetupChecks.MESSAGE_TYPE_WARNING
						});
					}
				}

				var xssfields = xhr.getResponseHeader('X-XSS-Protection') ? xhr.getResponseHeader('X-XSS-Protection').split(';').map(function(item) { return item.trim(); }) : [];
				if (xssfields.length === 0 || xssfields.indexOf('1') === -1 || xssfields.indexOf('mode=block') === -1) {
					messages.push({
						msg: t('core', 'The "{header}" HTTP header does not contain "{expected}". This is a potential security or privacy risk, as it is recommended to adjust this setting accordingly.',
							{
								header: 'X-XSS-Protection',
								expected: '1; mode=block'
							}),
						type: OC.SetupChecks.MESSAGE_TYPE_WARNING
					});
				}

				const referrerPolicy = xhr.getResponseHeader('Referrer-Policy')
				if (referrerPolicy === null || !/(no-referrer(-when-downgrade)?|strict-origin(-when-cross-origin)?|same-origin)(,|$)/.test(referrerPolicy)) {
					messages.push({
						msg: t('core', 'The "{header}" HTTP header is not set to "{val1}", "{val2}", "{val3}", "{val4}" or "{val5}". This can leak referer information. See the {linkstart}W3C Recommendation ↗{linkend}.',
							{
								header: 'Referrer-Policy',
								val1: 'no-referrer',
								val2: 'no-referrer-when-downgrade',
								val3: 'strict-origin',
								val4: 'strict-origin-when-cross-origin',
								val5: 'same-origin'
							})
							.replace('{linkstart}', '<a target="_blank" rel="noreferrer noopener" class="external" href="https://www.w3.org/TR/referrer-policy/">')
							.replace('{linkend}', '</a>'),
						type: OC.SetupChecks.MESSAGE_TYPE_INFO
					})
				}
			} else {
				messages.push({
					msg: t('core', 'Error occurred while checking server setup'),
					type: OC.SetupChecks.MESSAGE_TYPE_ERROR
				});
			}

			return messages;
		},

		/**
		 * Runs check for some SSL configuration issues on the server side
		 *
		 * @param {Object} xhr
		 * @return {Array} Array with error messages
		 */
		_checkSSL: function(xhr) {
			var messages = [];

			if (xhr.status === 200) {
				var tipsUrl = OC.theme.docPlaceholderUrl.replace('PLACEHOLDER', 'admin-security');
				if(OC.getProtocol() === 'https') {
					// Extract the value of 'Strict-Transport-Security'
					var transportSecurityValidity = xhr.getResponseHeader('Strict-Transport-Security');
					if(transportSecurityValidity !== null && transportSecurityValidity.length > 8) {
						var firstComma = transportSecurityValidity.indexOf(";");
						if(firstComma !== -1) {
							transportSecurityValidity = transportSecurityValidity.substring(8, firstComma);
						} else {
							transportSecurityValidity = transportSecurityValidity.substring(8);
						}
					}

					var minimumSeconds = 15552000;
					if(isNaN(transportSecurityValidity) || transportSecurityValidity <= (minimumSeconds - 1)) {
						messages.push({
							msg: t('core', 'The "Strict-Transport-Security" HTTP header is not set to at least "{seconds}" seconds. For enhanced security, it is recommended to enable HSTS as described in the {linkstart}security tips ↗{linkend}.', {'seconds': minimumSeconds})
								.replace('{linkstart}', '<a target="_blank" rel="noreferrer noopener" class="external" href="' + tipsUrl + '">')
								.replace('{linkend}', '</a>'),
							type: OC.SetupChecks.MESSAGE_TYPE_WARNING
						});
					}
				}
			} else {
				messages.push({
					msg: t('core', 'Error occurred while checking server setup'),
					type: OC.SetupChecks.MESSAGE_TYPE_ERROR
				});
			}

			return messages;
		}
	};
})();
