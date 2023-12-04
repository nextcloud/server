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
		 * Check whether the .well-known URLs works.
		 *
		 * @param url the URL to test
		 * @param placeholderUrl the placeholder URL - can be found at OC.theme.docPlaceholderUrl
		 * @param {boolean} runCheck if this is set to false the check is skipped and no error is returned
		 *
		 * @return $.Deferred object resolved with an array of error messages
		 */
		checkProviderUrl: function(url, placeholderUrl, runCheck) {
			var expectedStatus = [200];
			var deferred = $.Deferred();

			if(runCheck === false) {
				deferred.resolve([]);
				return deferred.promise();
			}
			var afterCall = function(xhr) {
				var messages = [];
				if (expectedStatus.indexOf(xhr.status) === -1) {
					var docUrl = placeholderUrl.replace('PLACEHOLDER', 'admin-nginx');
					messages.push({
						msg: t('core', 'Your web server is not properly set up to resolve "{url}". This is most likely related to a web server configuration that was not updated to deliver this folder directly. Please compare your configuration against the shipped rewrite rules in ".htaccess" for Apache or the provided one in the documentation for Nginx at it\'s {linkstart}documentation page ↗{linkend}. On Nginx those are typically the lines starting with "location ~" that need an update.', { docLink: docUrl, url: url })
							.replace('{linkstart}', '<a target="_blank" rel="noreferrer noopener" class="external" href="' + docUrl + '">')
							.replace('{linkend}', '</a>'),
						type: OC.SetupChecks.MESSAGE_TYPE_WARNING
					});
				}
				deferred.resolve(messages);
			};

			$.ajax({
				type: 'GET',
				url: url,
				complete: afterCall,
				allowAuthErrors: true
			});
			return deferred.promise();
		},


		/**
		 * Check whether the WOFF2 URLs works.
		 *
		 * @param url the URL to test
		 * @param placeholderUrl the placeholder URL - can be found at OC.theme.docPlaceholderUrl
		 * @return $.Deferred object resolved with an array of error messages
		 */
		checkWOFF2Loading: function(url, placeholderUrl) {
			var deferred = $.Deferred();

			var afterCall = function(xhr) {
				var messages = [];
				if (xhr.status !== 200) {
					var docUrl = placeholderUrl.replace('PLACEHOLDER', 'admin-nginx');
					messages.push({
						msg: t('core', 'Your web server is not properly set up to deliver .woff2 files. This is typically an issue with the Nginx configuration. For Nextcloud 15 it needs an adjustement to also deliver .woff2 files. Compare your Nginx configuration to the recommended configuration in our {linkstart}documentation ↗{linkend}.', { docLink: docUrl, url: url })
							.replace('{linkstart}', '<a target="_blank" rel="noreferrer noopener" class="external" href="' + docUrl + '">')
							.replace('{linkend}', '</a>'),
						type: OC.SetupChecks.MESSAGE_TYPE_WARNING
					});
				}
				deferred.resolve(messages);
			};

			$.ajax({
				type: 'GET',
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
					if (data.suggestedOverwriteCliURL !== '') {
						messages.push({
							msg: t('core', 'Please make sure to set the "overwrite.cli.url" option in your config.php file to the URL that your users mainly use to access this Nextcloud. Suggestion: "{suggestedOverwriteCliURL}". Otherwise there might be problems with the URL generation via cron. (It is possible though that the suggested URL is not the URL that your users mainly use to access this Nextcloud. Best is to double check this in any case.)', {suggestedOverwriteCliURL: data.suggestedOverwriteCliURL}),
							type: OC.SetupChecks.MESSAGE_TYPE_WARNING
						});
					}
					if (data.cronErrors.length > 0) {
						var listOfCronErrors = "";
						data.cronErrors.forEach(function(element){
							listOfCronErrors += '<li>';
							listOfCronErrors += element.error;
							listOfCronErrors += ' ';
							listOfCronErrors += element.hint;
							listOfCronErrors += '</li>';
						});
						messages.push({
							msg: t('core', 'It was not possible to execute the cron job via CLI. The following technical errors have appeared:') + '<ul>' + listOfCronErrors + '</ul>',
							type: OC.SetupChecks.MESSAGE_TYPE_ERROR
						})
					}
					if (data.cronInfo.diffInSeconds > 3600) {
						messages.push({
							msg: t('core', 'Last background job execution ran {relativeTime}. Something seems wrong. {linkstart}Check the background job settings ↗{linkend}.', {relativeTime: data.cronInfo.relativeTime})
									.replace('{linkstart}', '<a target="_blank" rel="noreferrer noopener" class="external" href="' + data.cronInfo.backgroundJobsUrl + '">')
									.replace('{linkend}', '</a>'),
							type: OC.SetupChecks.MESSAGE_TYPE_ERROR
						});
					}
					if (!data.isFairUseOfFreePushService) {
						messages.push({
							msg: t('core', 'This is the unsupported community build of Nextcloud. Given the size of this instance, performance, reliability and scalability cannot be guaranteed. Push notifications are limited to avoid overloading our free service. Learn more about the benefits of Nextcloud Enterprise at {linkstart}https://nextcloud.com/enterprise{linkend}.')
								.replace('{linkstart}', '<a target="_blank" rel="noreferrer noopener" class="external" href="https://nextcloud.com/enterprise">')
								.replace('{linkend}', '</a>'),
							type: OC.SetupChecks.MESSAGE_TYPE_ERROR
						});
					}
					if(data.isUsedTlsLibOutdated) {
						messages.push({
							msg: data.isUsedTlsLibOutdated,
							type: OC.SetupChecks.MESSAGE_TYPE_WARNING
						});
					}
					if(!data.isCorrectMemcachedPHPModuleInstalled) {
						messages.push({
							msg: t('core', 'Memcached is configured as distributed cache, but the wrong PHP module "memcache" is installed. \\OC\\Memcache\\Memcached only supports "memcached" and not "memcache". See the {linkstart}memcached wiki about both modules ↗{linkend}.')
								.replace('{linkstart}', '<a target="_blank" rel="noreferrer noopener" class="external" href="https://code.google.com/p/memcached/wiki/PHPClientComparison">')
								.replace('{linkend}', '</a>'),
							type: OC.SetupChecks.MESSAGE_TYPE_WARNING
						});
					}
					if(!data.hasPassedCodeIntegrityCheck) {
						messages.push({
							msg: t('core', 'Some files have not passed the integrity check. Further information on how to resolve this issue can be found in the {linkstart1}documentation ↗{linkend}. ({linkstart2}List of invalid files…{linkend} / {linkstart3}Rescan…{linkend})')
								.replace('{linkstart1}', '<a target="_blank" rel="noreferrer noopener" class="external" href="' + data.codeIntegrityCheckerDocumentation + '">')
								.replace('{linkstart2}', '<a href="' + OC.generateUrl('/settings/integrity/failed') + '">')
								.replace('{linkstart3}', '<a href="' + OC.generateUrl('/settings/integrity/rescan?requesttoken={requesttoken}', {'requesttoken': OC.requestToken}) + '">')
								.replace(/{linkend}/g, '</a>'),
							type: OC.SetupChecks.MESSAGE_TYPE_ERROR
						});
					}
					if(data.OpcacheSetupRecommendations.length > 0) {
						var listOfOPcacheRecommendations = "";
						data.OpcacheSetupRecommendations.forEach(function(element){
							listOfOPcacheRecommendations += '<li>' + element + '</li>';
						});
						messages.push({
							msg: t('core', 'The PHP OPcache module is not properly configured. See the {linkstart}documentation ↗{linkend} for more information.')
								.replace('{linkstart}', '<a target="_blank" rel="noreferrer noopener" class="external" href="' + OC.theme.docPlaceholderUrl.replace('PLACEHOLDER', 'admin-php-opcache') + '">')
								.replace('{linkend}', '</a>') + '<ul>' + listOfOPcacheRecommendations + '</ul>',
							type: OC.SetupChecks.MESSAGE_TYPE_INFO
						});
					}
					if(!data.isSettimelimitAvailable) {
						messages.push({
							msg: t('core', 'The PHP function "set_time_limit" is not available. This could result in scripts being halted mid-execution, breaking your installation. Enabling this function is strongly recommended.'),
							type: OC.SetupChecks.MESSAGE_TYPE_WARNING
						});
					}
					if (!data.isImagickEnabled) {
						messages.push({
							msg: t(
								'core',
								'The PHP module "imagick" is not enabled although the theming app is. For favicon generation to work correctly, you need to install and enable this module.'
							),
							type: OC.SetupChecks.MESSAGE_TYPE_INFO
						})
					}
					if (!data.areWebauthnExtensionsEnabled) {
						messages.push({
							msg: t(
								'core',
								'The PHP modules "gmp" and/or "bcmath" are not enabled. If you use WebAuthn passwordless authentication, these modules are required.'
							),
							type: OC.SetupChecks.MESSAGE_TYPE_INFO
						})
					}
					if (data.imageMagickLacksSVGSupport) {
						messages.push({
							msg: t('core', 'Module php-imagick in this instance has no SVG support. For better compatibility it is recommended to install it.'),
							type: OC.SetupChecks.MESSAGE_TYPE_INFO
						})
					}
					if (data.pendingBigIntConversionColumns.length > 0) {
						var listOfPendingBigIntConversionColumns = "";
						data.pendingBigIntConversionColumns.forEach(function(element){
							listOfPendingBigIntConversionColumns += '<li>' + element + '</li>';
						});
						messages.push({
							msg: t('core', 'Some columns in the database are missing a conversion to big int. Due to the fact that changing column types on big tables could take some time they were not changed automatically. By running "occ db:convert-filecache-bigint" those pending changes could be applied manually. This operation needs to be made while the instance is offline. For further details read {linkstart}the documentation page about this ↗{linkend}.')
								.replace('{linkstart}', '<a target="_blank" rel="noreferrer noopener" class="external" href="' + OC.theme.docPlaceholderUrl.replace('PLACEHOLDER', 'admin-bigint-conversion') + '">')
								.replace('{linkend}', '</a>') + '<ul>' + listOfPendingBigIntConversionColumns + '</ul>',
							type: OC.SetupChecks.MESSAGE_TYPE_INFO
						})
					}
					if (data.isSqliteUsed) {
						messages.push({
							msg: t('core', 'SQLite is currently being used as the backend database. For larger installations we recommend that you switch to a different database backend.') + ' ' + t('core', 'This is particularly recommended when using the desktop client for file synchronisation.') + ' ' +
							t('core', 'To migrate to another database use the command line tool: "occ db:convert-type", or see the {linkstart}documentation ↗{linkend}.')
								.replace('{linkstart}', '<a target="_blank" rel="noreferrer noopener" class="external" href="' + data.databaseConversionDocumentation + '">')
								.replace('{linkend}', '</a>'),
							type: OC.SetupChecks.MESSAGE_TYPE_WARNING
						})
					}

					if(data.appDirsWithDifferentOwner && data.appDirsWithDifferentOwner.length > 0) {
						var appDirsWithDifferentOwner = data.appDirsWithDifferentOwner.reduce(
							function(appDirsWithDifferentOwner, directory) {
								return appDirsWithDifferentOwner + '<li>' + directory + '</li>';
							},
							''
						);
						messages.push({
							msg: t('core', 'Some app directories are owned by a different user than the web server one. ' +
									'This may be the case if apps have been installed manually. ' +
									'Check the permissions of the following app directories:')
									+ '<ul>' + appDirsWithDifferentOwner + '</ul>',
							type: OC.SetupChecks.MESSAGE_TYPE_WARNING
						});
					}
					if (data.isMysqlUsedWithoutUTF8MB4) {
						messages.push({
							msg: t('core', 'MySQL is used as database but does not support 4-byte characters. To be able to handle 4-byte characters (like emojis) without issues in filenames or comments for example it is recommended to enable the 4-byte support in MySQL. For further details read {linkstart}the documentation page about this ↗{linkend}.')
								.replace('{linkstart}', '<a target="_blank" rel="noreferrer noopener" class="external" href="' + OC.theme.docPlaceholderUrl.replace('PLACEHOLDER', 'admin-mysql-utf8mb4') + '">')
								.replace('{linkend}', '</a>'),
							type: OC.SetupChecks.MESSAGE_TYPE_WARNING
						})
					}
					if (!data.isEnoughTempSpaceAvailableIfS3PrimaryStorageIsUsed) {
						messages.push({
							msg: t('core', 'This instance uses an S3 based object store as primary storage. The uploaded files are stored temporarily on the server and thus it is recommended to have 50 GB of free space available in the temp directory of PHP. Check the logs for full details about the path and the available space. To improve this please change the temporary directory in the php.ini or make more space available in that path.'),
							type: OC.SetupChecks.MESSAGE_TYPE_WARNING
						})
					}
					if (!data.temporaryDirectoryWritable) {
						messages.push({
							msg: t('core', 'The temporary directory of this instance points to an either non-existing or non-writable directory.'),
							type: OC.SetupChecks.MESSAGE_TYPE_WARNING
						})
					}
					if (window.location.protocol === 'https:' && data.reverseProxyGeneratedURL.split('/')[0] !== 'https:') {
						messages.push({
							msg: t('core', 'You are accessing your instance over a secure connection, however your instance is generating insecure URLs. This most likely means that you are behind a reverse proxy and the overwrite config variables are not set correctly. Please read {linkstart}the documentation page about this ↗{linkend}.')
								.replace('{linkstart}', '<a target="_blank" rel="noreferrer noopener" class="external" href="' + data.reverseProxyDocs + '">')
								.replace('{linkend}', '</a>'),
							type: OC.SetupChecks.MESSAGE_TYPE_WARNING
						})
					}
					if (window.oc_debug) {
						messages.push({
							msg: t('core', 'This instance is running in debug mode. Only enable this for local development and not in production environments.'),
							type: OC.SetupChecks.MESSAGE_TYPE_WARNING
						})
					}
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

		addGenericSetupCheck: function(data, check, messages) {
			var setupCheck = data[check] || { pass: true, description: '', severity: 'info', linkToDoc: null}

			var type = OC.SetupChecks.MESSAGE_TYPE_INFO
			if (setupCheck.severity === 'warning') {
				type = OC.SetupChecks.MESSAGE_TYPE_WARNING
			} else if (setupCheck.severity === 'error') {
				type = OC.SetupChecks.MESSAGE_TYPE_ERROR
			}

			var message = setupCheck.description;
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
						msg: t('core', 'Your data directory and files are probably accessible from the internet. The .htaccess file is not working. It is strongly recommended that you configure your web server so that the data directory is no longer accessible, or move the data directory outside the web server document root.'),
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
				} else if (!/(?:^(?:localhost|127\.0\.0\.1|::1)|\.onion)$/.exec(window.location.hostname)) {
					messages.push({
						msg: t('core', 'Accessing site insecurely via HTTP. You are strongly advised to set up your server to require HTTPS instead, as described in the {linkstart}security tips ↗{linkend}. Without it some important web functionality like "copy to clipboard" or "service workers" will not work!')
							.replace('{linkstart}', '<a target="_blank" rel="noreferrer noopener" class="external" href="' + tipsUrl + '">')
							.replace('{linkend}', '</a>'),
						type: OC.SetupChecks.MESSAGE_TYPE_ERROR
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
