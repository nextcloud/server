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
							t('core', 'This server has no working Internet connection. This means that some of the features like mounting external storage, notifications about updates or installation of third-party apps won\'t work. Accessing files remotely and sending of notification emails might not work, either. We suggest to enable Internet connection for this server if you want to have all features.')
						);
					}
					if(!data.dataDirectoryProtected) {
						messages.push(
							t('core', 'Your data directory and your files are probably accessible from the Internet. The .htaccess file is not working. We strongly suggest that you configure your web server in a way that the data directory is no longer accessible or you move the data directory outside the web server document root.')
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
		}
	};
})();

