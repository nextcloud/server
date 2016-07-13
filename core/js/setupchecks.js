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
						msg: t('core', 'Your web server is not yet set up properly to allow file synchronization because the WebDAV interface seems to be broken.'),
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
				complete: afterCall,
				allowAuthErrors: true
			});
			return deferred.promise();
		},

		/**
		 * Check whether the .well-known URLs works.
		 *
		 * @param url the URL to test
		 * @param placeholderUrl the placeholder URL - can be found at oc_defaults.docPlaceholderUrl
		 * @param {boolean} runCheck if this is set to false the check is skipped and no error is returned
		 * @return $.Deferred object resolved with an array of error messages
		 */
		checkWellKnownUrl: function(url, placeholderUrl, runCheck) {
			var deferred = $.Deferred();

			if(runCheck === false) {
				deferred.resolve([]);
				return deferred.promise();
			}
			var afterCall = function(xhr) {
				var messages = [];
				if (xhr.status !== 207) {
					var docUrl = placeholderUrl.replace('PLACEHOLDER', 'admin-setup-well-known-URL');
					messages.push({
						msg: t('core', 'Your web server is not set up properly to resolve "{url}". Further information can be found in our <a target="_blank" rel="noreferrer" href="{docLink}">documentation</a>.', { docLink: docUrl, url: url }),
						type: OC.SetupChecks.MESSAGE_TYPE_INFO
					});
				}
				deferred.resolve(messages);
			};

			$.ajax({
				type: 'PROPFIND',
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
					if (!data.serverHasInternetConnection) {
						messages.push({
							msg: t('core', 'This server has no working Internet connection. This means that some of the features like mounting external storage, notifications about updates or installation of third-party apps will not work. Accessing files remotely and sending of notification emails might not work, either. We suggest to enable Internet connection for this server if you want to have all features.'),
							type: OC.SetupChecks.MESSAGE_TYPE_WARNING
						});
					}
					if(!data.isMemcacheConfigured) {
						messages.push({
							msg: t('core', 'No memory cache has been configured. To enhance your performance please configure a memcache if available. Further information can be found in our <a target="_blank" rel="noreferrer" href="{docLink}">documentation</a>.', {docLink: data.memcacheDocs}),
							type: OC.SetupChecks.MESSAGE_TYPE_INFO
						});
					}
					if(!data.isUrandomAvailable) {
						messages.push({
							msg: t('core', '/dev/urandom is not readable by PHP which is highly discouraged for security reasons. Further information can be found in our <a target="_blank" rel="noreferrer" href="{docLink}">documentation</a>.', {docLink: data.securityDocs}),
							type: OC.SetupChecks.MESSAGE_TYPE_WARNING
						});
					}
					if(data.isUsedTlsLibOutdated) {
						messages.push({
							msg: data.isUsedTlsLibOutdated,
							type: OC.SetupChecks.MESSAGE_TYPE_WARNING
						});
					}
					if(data.phpSupported && data.phpSupported.eol) {
						messages.push({
							msg: t('core', 'You are currently running PHP {version}. We encourage you to upgrade your PHP version to take advantage of <a target="_blank" rel="noreferrer" href="{phpLink}">performance and security updates provided by the PHP Group</a> as soon as your distribution supports it.', {version: data.phpSupported.version, phpLink: 'https://secure.php.net/supported-versions.php'}),
							type: OC.SetupChecks.MESSAGE_TYPE_INFO
						});
					}
					if(!data.forwardedForHeadersWorking) {
						messages.push({
							msg: t('core', 'The reverse proxy headers configuration is incorrect, or you are accessing ownCloud from a trusted proxy. If you are not accessing ownCloud from a trusted proxy, this is a security issue and can allow an attacker to spoof their IP address as visible to ownCloud. Further information can be found in our <a target="_blank" rel="noreferrer" href="{docLink}">documentation</a>.', {docLink: data.reverseProxyDocs}),
							type: OC.SetupChecks.MESSAGE_TYPE_WARNING
						});
					}
					if(!data.isCorrectMemcachedPHPModuleInstalled) {
						messages.push({
							msg: t('core', 'Memcached is configured as distributed cache, but the wrong PHP module "memcache" is installed. \\OC\\Memcache\\Memcached only supports "memcached" and not "memcache". See the <a target="_blank" rel="noreferrer" href="{wikiLink}">memcached wiki about both modules</a>.', {wikiLink: 'https://code.google.com/p/memcached/wiki/PHPClientComparison'}),
							type: OC.SetupChecks.MESSAGE_TYPE_WARNING
						});
					}
					if(!data.hasPassedCodeIntegrityCheck) {
						messages.push({
							msg: t(
									'core',
									'Some files have not passed the integrity check. Further information on how to resolve this issue can be found in our <a target="_blank" rel="noreferrer" href="{docLink}">documentation</a>. (<a href="{codeIntegrityDownloadEndpoint}">List of invalid files…</a> / <a href="{rescanEndpoint}">Rescan…</a>)',
									{
										docLink: data.codeIntegrityCheckerDocumentation,
										codeIntegrityDownloadEndpoint: OC.generateUrl('/settings/integrity/failed'),
										rescanEndpoint: OC.generateUrl('/settings/integrity/rescan?requesttoken={requesttoken}', {'requesttoken': OC.requestToken})
									}
							),
							type: OC.SetupChecks.MESSAGE_TYPE_ERROR
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

		checkDataProtected: function() {
			var deferred = $.Deferred();
			if(oc_dataURL === false){
				return deferred.resolve([]);
			}
			var afterCall = function(xhr) {
				var messages = [];
				if (xhr.status !== 403 && xhr.status !== 307 && xhr.status !== 301 && xhr.responseText.indexOf('HTACCESSFAIL') >= 0) {
					messages.push({
						msg: t('core', 'Your data directory and your files are probably accessible from the Internet. The .htaccess file is not working. We strongly suggest that you configure your web server in a way that the data directory is no longer accessible or you move the data directory outside the web server document root.'),
						type: OC.SetupChecks.MESSAGE_TYPE_ERROR
					});
				}
				deferred.resolve(messages);
			};

			$.ajax({
				type: 'GET',
				url: OC.linkTo('', oc_dataURL+'/htaccesstest.txt?t=' + (new Date()).getTime()),
				complete: afterCall,
				allowAuthErrors: true
			});
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
					'X-Frame-Options': 'SAMEORIGIN',
					'X-Download-Options': 'noopen',
					'X-Permitted-Cross-Domain-Policies': 'none',
				};

				for (var header in securityHeaders) {
					if(!xhr.getResponseHeader(header) || xhr.getResponseHeader(header).toLowerCase() !== securityHeaders[header].toLowerCase()) {
						messages.push({
							msg: t('core', 'The "{header}" HTTP header is not configured to equal to "{expected}". This is a potential security or privacy risk and we recommend adjusting this setting.', {header: header, expected: securityHeaders[header]}),
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

					var minimumSeconds = 15552000;
					if(isNaN(transportSecurityValidity) || transportSecurityValidity <= (minimumSeconds - 1)) {
						messages.push({
							msg: t('core', 'The "Strict-Transport-Security" HTTP header is not configured to at least "{seconds}" seconds. For enhanced security we recommend enabling HSTS as described in our <a href="{docUrl}" rel="noreferrer">security tips</a>.', {'seconds': minimumSeconds, docUrl: '#admin-tips'}),
							type: OC.SetupChecks.MESSAGE_TYPE_WARNING
						});
					}
				} else {
					messages.push({
						msg: t('core', 'You are accessing this site via HTTP. We strongly suggest you configure your server to require using HTTPS instead as described in our <a href="{docUrl}">security tips</a>.', {docUrl: '#admin-tips'}),
						type: OC.SetupChecks.MESSAGE_TYPE_WARNING
					});
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
