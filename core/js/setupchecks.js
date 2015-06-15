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
					messages.push(
						t('core', 'Your web server is not yet set up properly to allow file synchronization because the WebDAV interface seems to be broken.')
					);
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
				complete: afterCall
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
					if (!data.serverHasInternetConnection) {
						messages.push(
							t('core', 'This server has no working Internet connection. This means that some of the features like mounting external storage, notifications about updates or installation of third-party apps will not work. Accessing files remotely and sending of notification emails might not work, either. We suggest to enable Internet connection for this server if you want to have all features.')
						);
					}
					if(!data.dataDirectoryProtected) {
						messages.push(
							t('core', 'Your data directory and your files are probably accessible from the Internet. The .htaccess file is not working. We strongly suggest that you configure your web server in a way that the data directory is no longer accessible or you move the data directory outside the web server document root.')
						);
					}
					if(!data.isMemcacheConfigured) {
						messages.push(
							t('core', 'No memory cache has been configured. To enhance your performance please configure a memcache if available. Further information can be found in our <a href="{docLink}">documentation</a>.', {docLink: data.memcacheDocs})
						);
					}
					if(!data.isUrandomAvailable) {
						messages.push(
							t('core', '/dev/urandom is not readable by PHP which is highly discouraged for security reasons. Further information can be found in our <a href="{docLink}">documentation</a>.', {docLink: data.securityDocs})
						);
					}
				} else {
					messages.push(t('core', 'Error occurred while checking server setup'));
				}
				deferred.resolve(messages);
			};

			$.ajax({
				type: 'GET',
				url: OC.generateUrl('settings/ajax/checksetup')
			}).then(afterCall, afterCall);
			return deferred.promise();
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
				url: OC.generateUrl('heartbeat')
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
					'X-XSS-Protection': '1; mode=block',
					'X-Content-Type-Options': 'nosniff',
					'X-Robots-Tag': 'none',
					'X-Frame-Options': 'SAMEORIGIN'
				};

				for (var header in securityHeaders) {
					if(!xhr.getResponseHeader(header) || xhr.getResponseHeader(header).toLowerCase() !== securityHeaders[header].toLowerCase()) {
						messages.push(
							t('core', 'The "{header}" HTTP header is not configured to equal to "{expected}". This is a potential security or privacy risk and we recommend adjusting this setting.', {header: header, expected: securityHeaders[header]})
						);
					}
				}
			} else {
				messages.push(t('core', 'Error occurred while checking server setup'));
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

					var minimumSeconds = 15768000;
					if(isNaN(transportSecurityValidity) || transportSecurityValidity <= (minimumSeconds - 1)) {
						messages.push(
							t('core', 'The "Strict-Transport-Security" HTTP header is not configured to least "{seconds}" seconds. For enhanced security we recommend enabling HSTS as described in our <a href="{docUrl}">security tips</a>.', {'seconds': minimumSeconds, docUrl: '#admin-tips'})
						);
					}
				} else {
					messages.push(
						t('core', 'You are accessing this site via HTTP. We strongly suggest you configure your server to require using HTTPS instead as described in our <a href="{docUrl}">security tips</a>.', {docUrl: '#admin-tips'})
					);
				}
			} else {
				messages.push(t('core', 'Error occurred while checking server setup'));
			}

			return messages;
		}
	};
})();
