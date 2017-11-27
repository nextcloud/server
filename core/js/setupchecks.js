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
						msg: t('core', 'Your web server is not properly set up to resolve "{url}". Further information can be found in the <a target="_blank" rel="noreferrer noopener" href="{docLink}">documentation</a>.', { docLink: docUrl, url: url }),
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
							msg: t('core', 'This server has no working Internet connection: Multiple endpoints could not be reached. This means that some of the features like mounting external storage, notifications about updates or installation of third-party apps will not work. Accessing files remotely and sending of notification emails might not work, either. Establish a connection from this server to the Internet to enjoy all features.'),
							type: OC.SetupChecks.MESSAGE_TYPE_WARNING
						});
					}
					if(!data.isMemcacheConfigured) {
						messages.push({
							msg: t('core', 'No memory cache has been configured. To enhance performance, please configure a memcache, if available. Further information can be found in the <a target="_blank" rel="noreferrer noopener" href="{docLink}">documentation</a>.', {docLink: data.memcacheDocs}),
							type: OC.SetupChecks.MESSAGE_TYPE_INFO
						});
					}
					if(!data.isUrandomAvailable) {
						messages.push({
							msg: t('core', '/dev/urandom is not readable by PHP which is highly discouraged for security reasons. Further information can be found in the <a target="_blank" rel="noreferrer noopener" href="{docLink}">documentation</a>.', {docLink: data.securityDocs}),
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
							msg: t('core', 'You are currently running PHP {version}. Upgrade your PHP version to take advantage of <a target="_blank" rel="noreferrer noopener" href="{phpLink}">performance and security updates provided by the PHP Group</a> as soon as your distribution supports it.', {version: data.phpSupported.version, phpLink: 'https://secure.php.net/supported-versions.php'}),
							type: OC.SetupChecks.MESSAGE_TYPE_INFO
						});
					}
					if(!data.forwardedForHeadersWorking) {
						messages.push({
							msg: t('core', 'The reverse proxy header configuration is incorrect, or you are accessing Nextcloud from a trusted proxy. If not, this is a security issue and can allow an attacker to spoof their IP address as visible to the Nextcloud. Further information can be found in the <a target="_blank" rel="noreferrer noopener" href="{docLink}">documentation</a>.', {docLink: data.reverseProxyDocs}),
							type: OC.SetupChecks.MESSAGE_TYPE_WARNING
						});
					}
					if(!data.isCorrectMemcachedPHPModuleInstalled) {
						messages.push({
							msg: t('core', 'Memcached is configured as distributed cache, but the wrong PHP module "memcache" is installed. \\OC\\Memcache\\Memcached only supports "memcached" and not "memcache". See the <a target="_blank" rel="noreferrer noopener" href="{wikiLink}">memcached wiki about both modules</a>.', {wikiLink: 'https://code.google.com/p/memcached/wiki/PHPClientComparison'}),
							type: OC.SetupChecks.MESSAGE_TYPE_WARNING
						});
					}
					if(!data.hasPassedCodeIntegrityCheck) {
						messages.push({
							msg: t(
									'core',
									'Some files have not passed the integrity check. Further information on how to resolve this issue can be found in the <a target="_blank" rel="noreferrer noopener" href="{docLink}">documentation</a>. (<a href="{codeIntegrityDownloadEndpoint}">List of invalid files…</a> / <a href="{rescanEndpoint}">Rescan…</a>)',
									{
										docLink: data.codeIntegrityCheckerDocumentation,
										codeIntegrityDownloadEndpoint: OC.generateUrl('/settings/integrity/failed'),
										rescanEndpoint: OC.generateUrl('/settings/integrity/rescan?requesttoken={requesttoken}', {'requesttoken': OC.requestToken})
									}
							),
							type: OC.SetupChecks.MESSAGE_TYPE_ERROR
						});
					}
					if(!data.isOpcacheProperlySetup) {
						messages.push({
							msg: t(
								'core',
								'The PHP OPcache is not properly configured. <a target="_blank" rel="noreferrer noopener" href="{docLink}">For better performance it is recommended</a> to use the following settings in the <code>php.ini</code>:',
								{
									docLink: data.phpOpcacheDocumentation,
								}
							) + "<pre><code>opcache.enable=1\nopcache.enable_cli=1\nopcache.interned_strings_buffer=8\nopcache.max_accelerated_files=10000\nopcache.memory_consumption=128\nopcache.save_comments=1\nopcache.revalidate_freq=1</code></pre>",
							type: OC.SetupChecks.MESSAGE_TYPE_INFO
						});
					}
					if(!data.isSettimelimitAvailable) {
						messages.push({
							msg: t(
								'core',
								'The PHP function "set_time_limit" is not available. This could result in scripts being halted mid-execution, breaking your installation. Enabling this function is strongly recommended.'),
							type: OC.SetupChecks.MESSAGE_TYPE_WARNING
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
				// .ocdata is an empty file in the data directory - if this is readable then the data dir is not protected
				if (xhr.status === 200 && xhr.responseText === '') {
					messages.push({
						msg: t('core', 'Your data directory and files are probably accessible from the Internet. The .htaccess file is not working. It is strongly recommended that you configure your web server so that the data directory is no longer accessible, or move the data directory outside the web server document root.'),
						type: OC.SetupChecks.MESSAGE_TYPE_ERROR
					});
				}
				deferred.resolve(messages);
			};

			$.ajax({
				type: 'GET',
				url: OC.linkTo('', oc_dataURL+'/.ocdata?t=' + (new Date()).getTime()),
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
					'X-XSS-Protection': ['1; mode=block'],
					'X-Content-Type-Options': ['nosniff'],
					'X-Robots-Tag': ['none'],
					'X-Frame-Options': ['SAMEORIGIN', 'DENY'],
					'X-Download-Options': ['noopen'],
					'X-Permitted-Cross-Domain-Policies': ['none'],
				};
				for (var header in securityHeaders) {
					var option = securityHeaders[header][0];
					if(!xhr.getResponseHeader(header) || xhr.getResponseHeader(header).toLowerCase() !== option.toLowerCase()) {
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
				var tipsUrl = OC.generateUrl('settings/admin/tips-tricks');
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
							msg: t('core', 'The "Strict-Transport-Security" HTTP header is not set to at least "{seconds}" seconds. For enhanced security, it is recommended to enable HSTS as described in the <a href="{docUrl}" rel="noreferrer noopener">security tips</a>.', {'seconds': minimumSeconds, docUrl: tipsUrl}),
							type: OC.SetupChecks.MESSAGE_TYPE_WARNING
						});
					}
				} else {
					messages.push({
						msg: t('core', 'Accessing site insecurely via HTTP. You are strongly adviced to set up your server to require HTTPS instead, as described in the <a href="{docUrl}">security tips</a>.', {docUrl:  tipsUrl}),
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
