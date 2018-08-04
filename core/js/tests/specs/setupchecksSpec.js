/**
 * Copyright (c) 2015 Lukas Reschke <lukas@owncloud.com>
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
 *
 */

describe('OC.SetupChecks tests', function() {
	var suite = this;
	var protocolStub;

	beforeEach( function(){
		protocolStub = sinon.stub(OC, 'getProtocol');
		suite.server = sinon.fakeServer.create();
	});

	afterEach( function(){
		suite.server.restore();
		protocolStub.restore();
	});

	describe('checkWebDAV', function() {
		it('should fail with another response status code than 201 or 207', function(done) {
			var async = OC.SetupChecks.checkWebDAV();

			suite.server.requests[0].respond(200);

			async.done(function( data, s, x ){
				expect(data).toEqual([{
					msg: 'Your web server is not yet properly set up to allow file synchronization, because the WebDAV interface seems to be broken.',
					type: OC.SetupChecks.MESSAGE_TYPE_ERROR
				}]);
				done();
			});
		});

		it('should return no error with a response status code of 207', function(done) {
			var async = OC.SetupChecks.checkWebDAV();

			suite.server.requests[0].respond(207);

			async.done(function( data, s, x ){
				expect(data).toEqual([]);
				done();
			});
		});

		it('should return no error with a response status code of 401', function(done) {
			var async = OC.SetupChecks.checkWebDAV();

			suite.server.requests[0].respond(401);

			async.done(function( data, s, x ){
				expect(data).toEqual([]);
				done();
			});
		});
	});

	describe('checkWellKnownUrl', function() {
		it('should fail with another response status code than 207', function(done) {
			var async = OC.SetupChecks.checkWellKnownUrl('/.well-known/caldav/', 'http://example.org/PLACEHOLDER', true);

			suite.server.requests[0].respond(200);

			async.done(function( data, s, x ){
				expect(data).toEqual([{
					msg: 'Your web server is not properly set up to resolve "/.well-known/caldav/". Further information can be found in the <a href="http://example.org/admin-setup-well-known-URL" rel="noreferrer noopener">documentation</a>.',
					type: OC.SetupChecks.MESSAGE_TYPE_INFO
				}]);
				done();
			});
		});

		it('should return no error with a response status code of 207', function(done) {
			var async = OC.SetupChecks.checkWellKnownUrl('/.well-known/caldav/', 'http://example.org/PLACEHOLDER', true);

			suite.server.requests[0].respond(207);

			async.done(function( data, s, x ){
				expect(data).toEqual([]);
				done();
			});
		});

		it('should return no error when no check should be run', function(done) {
			var async = OC.SetupChecks.checkWellKnownUrl('/.well-known/caldav/', 'http://example.org/PLACEHOLDER', false);

			async.done(function( data, s, x ){
				expect(data).toEqual([]);
				done();
			});
		});
	});

	describe('checkDataProtected', function() {

		oc_dataURL = "data";

		it('should return an error if data directory is not protected', function(done) {
			var async = OC.SetupChecks.checkDataProtected();

			suite.server.requests[0].respond(200, {'Content-Type': 'text/plain'}, '');

			async.done(function( data, s, x ){
				expect(data).toEqual([
					{
						msg: 'Your data directory and files are probably accessible from the Internet. The .htaccess file is not working. It is strongly recommended that you configure your web server so that the data directory is no longer accessible, or move the data directory outside the web server document root.',
						type: OC.SetupChecks.MESSAGE_TYPE_ERROR
					}]);
				done();
			});
		});

		it('should not return an error if data directory is protected', function(done) {
			var async = OC.SetupChecks.checkDataProtected();

			suite.server.requests[0].respond(403);

			async.done(function( data, s, x ){
				expect(data).toEqual([]);
				done();
			});
		});

		it('should return an error if data directory is a boolean', function(done) {

			oc_dataURL = false;

			var async = OC.SetupChecks.checkDataProtected();

			async.done(function( data, s, x ){
				expect(data).toEqual([]);
				done();
			});
		});
	});

	describe('checkSetup', function() {
		it('should return an error if server has no internet connection', function(done) {
			var async = OC.SetupChecks.checkSetup();

			suite.server.requests[0].respond(
				200,
				{
					'Content-Type': 'application/json'
				},
				JSON.stringify({
					hasFileinfoInstalled: true,
					isGetenvServerWorking: true,
					isReadOnlyConfig: false,
					hasWorkingFileLocking: true,
					hasValidTransactionIsolationLevel: true,
					suggestedOverwriteCliURL: '',
					isUrandomAvailable: true,
					serverHasInternetConnection: false,
					memcacheDocs: 'https://docs.nextcloud.com/server/go.php?to=admin-performance',
					forwardedForHeadersWorking: true,
					isCorrectMemcachedPHPModuleInstalled: true,
					hasPassedCodeIntegrityCheck: true,
					isOpcacheProperlySetup: true,
					hasOpcacheLoaded: true,
					isSettimelimitAvailable: true,
					hasFreeTypeSupport: true,
					missingIndexes: [],
					outdatedCaches: [],
					cronErrors: [],
					cronInfo: {
						diffInSeconds: 0
					},
					isMemoryLimitSufficient: true
				})
			);

			async.done(function( data, s, x ){
				expect(data).toEqual([
					{
						msg: 'This server has no working Internet connection: Multiple endpoints could not be reached. This means that some of the features like mounting external storage, notifications about updates or installation of third-party apps will not work. Accessing files remotely and sending of notification emails might not work, either. Establish a connection from this server to the Internet to enjoy all features.',
						type: OC.SetupChecks.MESSAGE_TYPE_WARNING
					}, {
						msg: 'No memory cache has been configured. To enhance performance, please configure a memcache, if available. Further information can be found in the <a href="https://docs.nextcloud.com/server/go.php?to=admin-performance" rel="noreferrer noopener">documentation</a>.',
						type: OC.SetupChecks.MESSAGE_TYPE_INFO
					}]);
				done();
			});
		});

		it('should return an error if server has no internet connection and data directory is not protected', function(done) {
			var async = OC.SetupChecks.checkSetup();

			suite.server.requests[0].respond(
				200,
				{
					'Content-Type': 'application/json'
				},
				JSON.stringify({
					hasFileinfoInstalled: true,
					isGetenvServerWorking: true,
					isReadOnlyConfig: false,
					hasWorkingFileLocking: true,
					hasValidTransactionIsolationLevel: true,
					suggestedOverwriteCliURL: '',
					isUrandomAvailable: true,
					serverHasInternetConnection: false,
					memcacheDocs: 'https://docs.nextcloud.com/server/go.php?to=admin-performance',
					forwardedForHeadersWorking: true,
					isCorrectMemcachedPHPModuleInstalled: true,
					hasPassedCodeIntegrityCheck: true,
					isOpcacheProperlySetup: true,
					hasOpcacheLoaded: true,
					isSettimelimitAvailable: true,
					hasFreeTypeSupport: true,
					missingIndexes: [],
					outdatedCaches: [],
					cronErrors: [],
					cronInfo: {
						diffInSeconds: 0
					},
					isMemoryLimitSufficient: true
				})
			);

			async.done(function( data, s, x ){
				expect(data).toEqual([
					{
						msg: 'This server has no working Internet connection: Multiple endpoints could not be reached. This means that some of the features like mounting external storage, notifications about updates or installation of third-party apps will not work. Accessing files remotely and sending of notification emails might not work, either. Establish a connection from this server to the Internet to enjoy all features.',
						type: OC.SetupChecks.MESSAGE_TYPE_WARNING
					},
					{
						msg: 'No memory cache has been configured. To enhance performance, please configure a memcache, if available. Further information can be found in the <a href="https://docs.nextcloud.com/server/go.php?to=admin-performance" rel="noreferrer noopener">documentation</a>.',
						type: OC.SetupChecks.MESSAGE_TYPE_INFO
					}]);
				done();
			});
		});

		it('should return an error if server has no internet connection and data directory is not protected and memcache is available', function(done) {
			var async = OC.SetupChecks.checkSetup();

			suite.server.requests[0].respond(
				200,
				{
					'Content-Type': 'application/json',
				},
				JSON.stringify({
					hasFileinfoInstalled: true,
					isGetenvServerWorking: true,
					isReadOnlyConfig: false,
					hasWorkingFileLocking: true,
					hasValidTransactionIsolationLevel: true,
					suggestedOverwriteCliURL: '',
					isUrandomAvailable: true,
					serverHasInternetConnection: false,
					isMemcacheConfigured: true,
					forwardedForHeadersWorking: true,
					isCorrectMemcachedPHPModuleInstalled: true,
					hasPassedCodeIntegrityCheck: true,
					isOpcacheProperlySetup: true,
					hasOpcacheLoaded: true,
					isSettimelimitAvailable: true,
					hasFreeTypeSupport: true,
					missingIndexes: [],
					outdatedCaches: [],
					cronErrors: [],
					cronInfo: {
						diffInSeconds: 0
					},
					isMemoryLimitSufficient: true
				})
			);

			async.done(function( data, s, x ){
				expect(data).toEqual([
				{
					msg: 'This server has no working Internet connection: Multiple endpoints could not be reached. This means that some of the features like mounting external storage, notifications about updates or installation of third-party apps will not work. Accessing files remotely and sending of notification emails might not work, either. Establish a connection from this server to the Internet to enjoy all features.',
					type: OC.SetupChecks.MESSAGE_TYPE_WARNING
				}
				]);
				done();
			});
		});

		it('should return an error if /dev/urandom is not accessible', function(done) {
			var async = OC.SetupChecks.checkSetup();

			suite.server.requests[0].respond(
				200,
				{
					'Content-Type': 'application/json',
				},
				JSON.stringify({
					hasFileinfoInstalled: true,
					isGetenvServerWorking: true,
					isReadOnlyConfig: false,
					hasWorkingFileLocking: true,
					hasValidTransactionIsolationLevel: true,
					suggestedOverwriteCliURL: '',
					isUrandomAvailable: false,
					securityDocs: 'https://docs.owncloud.org/myDocs.html',
					serverHasInternetConnection: true,
					isMemcacheConfigured: true,
					forwardedForHeadersWorking: true,
					isCorrectMemcachedPHPModuleInstalled: true,
					hasPassedCodeIntegrityCheck: true,
					isOpcacheProperlySetup: true,
					hasOpcacheLoaded: true,
					isSettimelimitAvailable: true,
					hasFreeTypeSupport: true,
					missingIndexes: [],
					outdatedCaches: [],
					cronErrors: [],
					cronInfo: {
						diffInSeconds: 0
					},
					isMemoryLimitSufficient: true
				})
			);

			async.done(function( data, s, x ){
				expect(data).toEqual([{
					msg: '/dev/urandom is not readable by PHP which is highly discouraged for security reasons. Further information can be found in the <a href="https://docs.owncloud.org/myDocs.html" rel="noreferrer noopener">documentation</a>.',
					type: OC.SetupChecks.MESSAGE_TYPE_WARNING
				}]);
				done();
			});
		});

		it('should return an error if the wrong memcache PHP module is installed', function(done) {
			var async = OC.SetupChecks.checkSetup();

			suite.server.requests[0].respond(
				200,
				{
					'Content-Type': 'application/json',
				},
				JSON.stringify({
					hasFileinfoInstalled: true,
					isGetenvServerWorking: true,
					isReadOnlyConfig: false,
					hasWorkingFileLocking: true,
					hasValidTransactionIsolationLevel: true,
					suggestedOverwriteCliURL: '',
					isUrandomAvailable: true,
					securityDocs: 'https://docs.owncloud.org/myDocs.html',
					serverHasInternetConnection: true,
					isMemcacheConfigured: true,
					forwardedForHeadersWorking: true,
					isCorrectMemcachedPHPModuleInstalled: false,
					hasPassedCodeIntegrityCheck: true,
					isOpcacheProperlySetup: true,
					hasOpcacheLoaded: true,
					isSettimelimitAvailable: true,
					hasFreeTypeSupport: true,
					missingIndexes: [],
					outdatedCaches: [],
					cronErrors: [],
					cronInfo: {
						diffInSeconds: 0
					},
					isMemoryLimitSufficient: true
				})
			);

			async.done(function( data, s, x ){
				expect(data).toEqual([{
					msg: 'Memcached is configured as distributed cache, but the wrong PHP module "memcache" is installed. \\OC\\Memcache\\Memcached only supports "memcached" and not "memcache". See the <a href="https://code.google.com/p/memcached/wiki/PHPClientComparison" rel="noreferrer noopener">memcached wiki about both modules</a>.',
					type: OC.SetupChecks.MESSAGE_TYPE_WARNING
				}]);
				done();
			});
		});

		it('should return an error if the forwarded for headers are not working', function(done) {
			var async = OC.SetupChecks.checkSetup();

			suite.server.requests[0].respond(
				200,
				{
					'Content-Type': 'application/json',
				},
				JSON.stringify({
					hasFileinfoInstalled: true,
					isGetenvServerWorking: true,
					isReadOnlyConfig: false,
					hasWorkingFileLocking: true,
					hasValidTransactionIsolationLevel: true,
					suggestedOverwriteCliURL: '',
					isUrandomAvailable: true,
					serverHasInternetConnection: true,
					isMemcacheConfigured: true,
					forwardedForHeadersWorking: false,
					reverseProxyDocs: 'https://docs.owncloud.org/foo/bar.html',
					isCorrectMemcachedPHPModuleInstalled: true,
					hasPassedCodeIntegrityCheck: true,
					isOpcacheProperlySetup: true,
					hasOpcacheLoaded: true,
					isSettimelimitAvailable: true,
					hasFreeTypeSupport: true,
					missingIndexes: [],
					outdatedCaches: [],
					cronErrors: [],
					cronInfo: {
						diffInSeconds: 0
					},
					isMemoryLimitSufficient: true
				})
			);

			async.done(function( data, s, x ){
				expect(data).toEqual([{
					msg: 'The reverse proxy header configuration is incorrect, or you are accessing Nextcloud from a trusted proxy. If not, this is a security issue and can allow an attacker to spoof their IP address as visible to the Nextcloud. Further information can be found in the <a href="https://docs.owncloud.org/foo/bar.html" rel="noreferrer noopener">documentation</a>.',
					type: OC.SetupChecks.MESSAGE_TYPE_WARNING
				}]);
				done();
			});
		});

		it('should return an error if set_time_limit is unavailable', function(done) {
			var async = OC.SetupChecks.checkSetup();

			suite.server.requests[0].respond(
				200,
				{
					'Content-Type': 'application/json',
				},
				JSON.stringify({
					hasFileinfoInstalled: true,
					isGetenvServerWorking: true,
					isReadOnlyConfig: false,
					hasWorkingFileLocking: true,
					hasValidTransactionIsolationLevel: true,
					suggestedOverwriteCliURL: '',
					isUrandomAvailable: true,
					serverHasInternetConnection: true,
					isMemcacheConfigured: true,
					forwardedForHeadersWorking: true,
					reverseProxyDocs: 'https://docs.owncloud.org/foo/bar.html',
					isCorrectMemcachedPHPModuleInstalled: true,
					hasPassedCodeIntegrityCheck: true,
					isOpcacheProperlySetup: true,
					hasOpcacheLoaded: true,
					isSettimelimitAvailable: false,
					hasFreeTypeSupport: true,
					missingIndexes: [],
					outdatedCaches: [],
					cronErrors: [],
					cronInfo: {
						diffInSeconds: 0
					},
					isMemoryLimitSufficient: true
				})
			);

			async.done(function( data, s, x ){
				expect(data).toEqual([{
					msg: 'The PHP function "set_time_limit" is not available. This could result in scripts being halted mid-execution, breaking your installation. Enabling this function is strongly recommended.',
					type: OC.SetupChecks.MESSAGE_TYPE_WARNING
				}]);
				done();
			});
		});

		it('should return a warning if the memory limit is below the recommended value', function(done) {
			var async = OC.SetupChecks.checkSetup();

			suite.server.requests[0].respond(
				200,
				{
					'Content-Type': 'application/json',
				},
				JSON.stringify({
					hasFileinfoInstalled: true,
					isGetenvServerWorking: true,
					isReadOnlyConfig: false,
					hasWorkingFileLocking: true,
					hasValidTransactionIsolationLevel: true,
					suggestedOverwriteCliURL: '',
					isUrandomAvailable: true,
					serverHasInternetConnection: true,
					isMemcacheConfigured: true,
					forwardedForHeadersWorking: true,
					reverseProxyDocs: 'https://docs.owncloud.org/foo/bar.html',
					isCorrectMemcachedPHPModuleInstalled: true,
					hasPassedCodeIntegrityCheck: true,
					isOpcacheProperlySetup: true,
					hasOpcacheLoaded: true,
					isSettimelimitAvailable: true,
					hasFreeTypeSupport: true,
					missingIndexes: [],
					outdatedCaches: [],
					cronErrors: [],
					cronInfo: {
						diffInSeconds: 0
					},
					isMemoryLimitSufficient: false
				})
			);

			async.done(function( data, s, x ){
				expect(data).toEqual([{
					msg: 'The PHP memory limit is below the recommended value of 512MB.',
					type: OC.SetupChecks.MESSAGE_TYPE_WARNING
				}]);
				done();
			});
		});

		it('should return an error if the response has no statuscode 200', function(done) {
			var async = OC.SetupChecks.checkSetup();

			suite.server.requests[0].respond(
				500,
				{
					'Content-Type': 'application/json'
				},
				JSON.stringify({data: {serverHasInternetConnection: false}})
			);

			async.done(function( data, s, x ){
				expect(data).toEqual([{
					msg: 'Error occurred while checking server setup',
					type: OC.SetupChecks.MESSAGE_TYPE_ERROR
				}]);
				done();
			});
		});

		it('should return an error if the php version is no longer supported', function(done) {
			var async = OC.SetupChecks.checkSetup();

			suite.server.requests[0].respond(
				200,
				{
					'Content-Type': 'application/json',
				},
				JSON.stringify({
					hasFileinfoInstalled: true,
					isGetenvServerWorking: true,
					isReadOnlyConfig: false,
					hasWorkingFileLocking: true,
					hasValidTransactionIsolationLevel: true,
					suggestedOverwriteCliURL: '',
					isUrandomAvailable: true,
					securityDocs: 'https://docs.owncloud.org/myDocs.html',
					serverHasInternetConnection: true,
					isMemcacheConfigured: true,
					forwardedForHeadersWorking: true,
					phpSupported: {eol: true, version: '5.4.0'},
					isCorrectMemcachedPHPModuleInstalled: true,
					hasPassedCodeIntegrityCheck: true,
					isOpcacheProperlySetup: true,
					hasOpcacheLoaded: true,
					isSettimelimitAvailable: true,
					hasFreeTypeSupport: true,
					missingIndexes: [],
					outdatedCaches: [],
					cronErrors: [],
					cronInfo: {
						diffInSeconds: 0
					},
					isMemoryLimitSufficient: true
				})
			);

			async.done(function( data, s, x ){
				expect(data).toEqual([{
					msg: 'You are currently running PHP 5.4.0. Upgrade your PHP version to take advantage of <a href="https://secure.php.net/supported-versions.php" rel="noreferrer noopener">performance and security updates provided by the PHP Group</a> as soon as your distribution supports it.',
					type: OC.SetupChecks.MESSAGE_TYPE_INFO
				}]);
				done();
			});
		});

		it('should return an info if server has no proper opcache', function(done) {
			var async = OC.SetupChecks.checkSetup();

			suite.server.requests[0].respond(
				200,
				{
					'Content-Type': 'application/json'
				},
				JSON.stringify({
					hasFileinfoInstalled: true,
					isGetenvServerWorking: true,
					isReadOnlyConfig: false,
					hasWorkingFileLocking: true,
					hasValidTransactionIsolationLevel: true,
					suggestedOverwriteCliURL: '',
					isUrandomAvailable: true,
					securityDocs: 'https://docs.owncloud.org/myDocs.html',
					serverHasInternetConnection: true,
					isMemcacheConfigured: true,
					forwardedForHeadersWorking: true,
					isCorrectMemcachedPHPModuleInstalled: true,
					hasPassedCodeIntegrityCheck: true,
					isOpcacheProperlySetup: false,
					hasOpcacheLoaded: true,
					phpOpcacheDocumentation: 'https://example.org/link/to/doc',
					isSettimelimitAvailable: true,
					hasFreeTypeSupport: true,
					missingIndexes: [],
					outdatedCaches: [],
					cronErrors: [],
					cronInfo: {
						diffInSeconds: 0
					},
					isMemoryLimitSufficient: true
				})
			);

			async.done(function( data, s, x ){
				expect(data).toEqual([{
						msg: 'The PHP OPcache is not properly configured. <a href="https://example.org/link/to/doc" rel="noreferrer noopener">For better performance it is recommended</a> to use the following settings in the <code>php.ini</code>:' + "<pre><code>opcache.enable=1\nopcache.enable_cli=1\nopcache.interned_strings_buffer=8\nopcache.max_accelerated_files=10000\nopcache.memory_consumption=128\nopcache.save_comments=1\nopcache.revalidate_freq=1</code></pre>",
						type: OC.SetupChecks.MESSAGE_TYPE_INFO
					}]);
				done();
			});
		});

		it('should return an info if server has no opcache at all', function(done) {
			var async = OC.SetupChecks.checkSetup();

			suite.server.requests[0].respond(
				200,
				{
					'Content-Type': 'application/json'
				},
				JSON.stringify({
					hasFileinfoInstalled: true,
					isGetenvServerWorking: true,
					isReadOnlyConfig: false,
					hasWorkingFileLocking: true,
					hasValidTransactionIsolationLevel: true,
					suggestedOverwriteCliURL: '',
					isUrandomAvailable: true,
					securityDocs: 'https://docs.owncloud.org/myDocs.html',
					serverHasInternetConnection: true,
					isMemcacheConfigured: true,
					forwardedForHeadersWorking: true,
					isCorrectMemcachedPHPModuleInstalled: true,
					hasPassedCodeIntegrityCheck: true,
					isOpcacheProperlySetup: true,
					hasOpcacheLoaded: false,
					phpOpcacheDocumentation: 'https://example.org/link/to/doc',
					isSettimelimitAvailable: true,
					hasFreeTypeSupport: true,
					missingIndexes: [],
					outdatedCaches: [],
					cronErrors: [],
					cronInfo: {
						diffInSeconds: 0
					},
					isMemoryLimitSufficient: true
				})
			);

			async.done(function( data, s, x ){
				expect(data).toEqual([{
						msg: 'The PHP OPcache module is not loaded. <a href="https://example.org/link/to/doc" rel="noreferrer noopener">For better performance it is recommended</a> to load it into your PHP installation.',
						type: OC.SetupChecks.MESSAGE_TYPE_INFO
					}]);
				done();
			});
		});

		it('should return an info if server has no FreeType support', function(done) {
			var async = OC.SetupChecks.checkSetup();

			suite.server.requests[0].respond(
				200,
				{
					'Content-Type': 'application/json'
				},
				JSON.stringify({
					hasFileinfoInstalled: true,
					isGetenvServerWorking: true,
					isReadOnlyConfig: false,
					hasWorkingFileLocking: true,
					hasValidTransactionIsolationLevel: true,
					suggestedOverwriteCliURL: '',
					isUrandomAvailable: true,
					securityDocs: 'https://docs.owncloud.org/myDocs.html',
					serverHasInternetConnection: true,
					isMemcacheConfigured: true,
					forwardedForHeadersWorking: true,
					isCorrectMemcachedPHPModuleInstalled: true,
					hasPassedCodeIntegrityCheck: true,
					isOpcacheProperlySetup: true,
					hasOpcacheLoaded: true,
					phpOpcacheDocumentation: 'https://example.org/link/to/doc',
					isSettimelimitAvailable: true,
					hasFreeTypeSupport: false,
					missingIndexes: [],
					outdatedCaches: [],
					cronErrors: [],
					cronInfo: {
						diffInSeconds: 0
					},
					isMemoryLimitSufficient: true
				})
			);

			async.done(function( data, s, x ){
				expect(data).toEqual([{
					msg: 'Your PHP does not have FreeType support, resulting in breakage of profile pictures and the settings interface.',
					type: OC.SetupChecks.MESSAGE_TYPE_INFO
				}]);
				done();
			});
		});
	});

	describe('checkGeneric', function() {
		it('should return an error if the response has no statuscode 200', function(done) {
			var async = OC.SetupChecks.checkGeneric();

			suite.server.requests[0].respond(
				500,
				{
					'Content-Type': 'application/json'
				}
			);

			async.done(function( data, s, x ){
				expect(data).toEqual([{
					msg: 'Error occurred while checking server setup',
					type: OC.SetupChecks.MESSAGE_TYPE_ERROR
				},{
					msg: 'Error occurred while checking server setup',
					type: OC.SetupChecks.MESSAGE_TYPE_ERROR
				}]);
				done();
			});
		});

		it('should return all errors if all headers are missing', function(done) {
			protocolStub.returns('https');
			var async = OC.SetupChecks.checkGeneric();

			suite.server.requests[0].respond(
				200,
				{
					'Content-Type': 'application/json',
					'Strict-Transport-Security': 'max-age=15768000'
				}
			);

			async.done(function( data, s, x ){
				expect(data).toEqual([
				{
					msg: 'The "X-XSS-Protection" HTTP header is not set to "1; mode=block". This is a potential security or privacy risk, as it is recommended to adjust this setting accordingly.',
					type: OC.SetupChecks.MESSAGE_TYPE_WARNING
				}, {
					msg: 'The "X-Content-Type-Options" HTTP header is not set to "nosniff". This is a potential security or privacy risk, as it is recommended to adjust this setting accordingly.',
					type: OC.SetupChecks.MESSAGE_TYPE_WARNING
				}, {
					msg: 'The "X-Robots-Tag" HTTP header is not set to "none". This is a potential security or privacy risk, as it is recommended to adjust this setting accordingly.',
					type: OC.SetupChecks.MESSAGE_TYPE_WARNING

				}, {
					msg: 'The "X-Frame-Options" HTTP header is not set to "SAMEORIGIN". This is a potential security or privacy risk, as it is recommended to adjust this setting accordingly.',
					type: OC.SetupChecks.MESSAGE_TYPE_WARNING
				}, {
					msg: 'The "X-Download-Options" HTTP header is not set to "noopen". This is a potential security or privacy risk, as it is recommended to adjust this setting accordingly.',
					type: OC.SetupChecks.MESSAGE_TYPE_WARNING
				}, {
					msg: 'The "X-Permitted-Cross-Domain-Policies" HTTP header is not set to "none". This is a potential security or privacy risk, as it is recommended to adjust this setting accordingly.',
					type: OC.SetupChecks.MESSAGE_TYPE_WARNING
				}, {
					msg: 'The "Referrer-Policy" HTTP header is not set to "no-referrer", "no-referrer-when-downgrade", "strict-origin" or "strict-origin-when-cross-origin". This can leak referer information. See the <a href="https://www.w3.org/TR/referrer-policy/" rel="noreferrer noopener">W3C Recommendation ↗</a>.',
					type: OC.SetupChecks.MESSAGE_TYPE_INFO
				}
				]);
				done();
			});
		});

		it('should return only some errors if just some headers are missing', function(done) {
			protocolStub.returns('https');
			var async = OC.SetupChecks.checkGeneric();

			suite.server.requests[0].respond(
				200,
				{
					'X-Robots-Tag': 'none',
					'X-Frame-Options': 'SAMEORIGIN',
					'Strict-Transport-Security': 'max-age=15768000;preload',
					'X-Download-Options': 'noopen',
					'X-Permitted-Cross-Domain-Policies': 'none',
					'Referrer-Policy': 'no-referrer',
				}
			);

			async.done(function( data, s, x ){
				expect(data).toEqual([{
					msg: 'The "X-XSS-Protection" HTTP header is not set to "1; mode=block". This is a potential security or privacy risk, as it is recommended to adjust this setting accordingly.',
					type: OC.SetupChecks.MESSAGE_TYPE_WARNING,
				}, {
					msg: 'The "X-Content-Type-Options" HTTP header is not set to "nosniff". This is a potential security or privacy risk, as it is recommended to adjust this setting accordingly.',
					type: OC.SetupChecks.MESSAGE_TYPE_WARNING
				}]);
				done();
			});
		});

		it('should return none errors if all headers are there', function(done) {
			protocolStub.returns('https');
			var async = OC.SetupChecks.checkGeneric();

			suite.server.requests[0].respond(
				200,
				{
					'X-XSS-Protection': '1; mode=block',
					'X-Content-Type-Options': 'nosniff',
					'X-Robots-Tag': 'none',
					'X-Frame-Options': 'SAMEORIGIN',
					'Strict-Transport-Security': 'max-age=15768000',
					'X-Download-Options': 'noopen',
					'X-Permitted-Cross-Domain-Policies': 'none',
					'Referrer-Policy': 'no-referrer'
				}
			);

			async.done(function( data, s, x ){
				expect(data).toEqual([]);
				done();
			});
		});

		describe('check Referrer-Policy header', function() {
			it('should return no message if Referrer-Policy is set to no-referrer', function(done) {
				protocolStub.returns('https');
				var result = OC.SetupChecks.checkGeneric();

				suite.server.requests[0].respond(200, {
					'Strict-Transport-Security': 'max-age=15768000',
					'X-XSS-Protection': '1; mode=block',
					'X-Content-Type-Options': 'nosniff',
					'X-Robots-Tag': 'none',
					'X-Frame-Options': 'SAMEORIGIN',
					'X-Download-Options': 'noopen',
					'X-Permitted-Cross-Domain-Policies': 'none',
					'Referrer-Policy': 'no-referrer',
				});

				result.done(function( data, s, x ){
					expect(data).toEqual([]);
					done();
				});
			});

			it('should return no message if Referrer-Policy is set to no-referrer-when-downgrade', function(done) {
				protocolStub.returns('https');
				var result = OC.SetupChecks.checkGeneric();

				suite.server.requests[0].respond(200, {
					'Strict-Transport-Security': 'max-age=15768000',
					'X-XSS-Protection': '1; mode=block',
					'X-Content-Type-Options': 'nosniff',
					'X-Robots-Tag': 'none',
					'X-Frame-Options': 'SAMEORIGIN',
					'X-Download-Options': 'noopen',
					'X-Permitted-Cross-Domain-Policies': 'none',
					'Referrer-Policy': 'no-referrer-when-downgrade',
				});

				result.done(function( data, s, x ){
					expect(data).toEqual([]);
					done();
				});
			});

			it('should return no message if Referrer-Policy is set to strict-origin', function(done) {
				protocolStub.returns('https');
				var result = OC.SetupChecks.checkGeneric();

				suite.server.requests[0].respond(200, {
					'Strict-Transport-Security': 'max-age=15768000',
					'X-XSS-Protection': '1; mode=block',
					'X-Content-Type-Options': 'nosniff',
					'X-Robots-Tag': 'none',
					'X-Frame-Options': 'SAMEORIGIN',
					'X-Download-Options': 'noopen',
					'X-Permitted-Cross-Domain-Policies': 'none',
					'Referrer-Policy': 'strict-origin',
				});

				result.done(function( data, s, x ){
					expect(data).toEqual([]);
					done();
				});
			});

			it('should return no message if Referrer-Policy is set to strict-origin-when-cross-origin', function(done) {
				protocolStub.returns('https');
				var result = OC.SetupChecks.checkGeneric();

				suite.server.requests[0].respond(200, {
					'Strict-Transport-Security': 'max-age=15768000',
					'X-XSS-Protection': '1; mode=block',
					'X-Content-Type-Options': 'nosniff',
					'X-Robots-Tag': 'none',
					'X-Frame-Options': 'SAMEORIGIN',
					'X-Download-Options': 'noopen',
					'X-Permitted-Cross-Domain-Policies': 'none',
					'Referrer-Policy': 'strict-origin-when-cross-origin',
				});

				result.done(function( data, s, x ){
					expect(data).toEqual([]);
					done();
				});
			});

			it('should return a message if Referrer-Policy is set to same-origin', function(done) {
				protocolStub.returns('https');
				var result = OC.SetupChecks.checkGeneric();

				suite.server.requests[0].respond(200, {
					'Strict-Transport-Security': 'max-age=15768000',
					'X-XSS-Protection': '1; mode=block',
					'X-Content-Type-Options': 'nosniff',
					'X-Robots-Tag': 'none',
					'X-Frame-Options': 'SAMEORIGIN',
					'X-Download-Options': 'noopen',
					'X-Permitted-Cross-Domain-Policies': 'none',
					'Referrer-Policy': 'same-origin',
				});

				result.done(function( data, s, x ){
					expect(data).toEqual([
						{
							msg: 'The "Referrer-Policy" HTTP header is not set to "no-referrer", "no-referrer-when-downgrade", "strict-origin" or "strict-origin-when-cross-origin". This can leak referer information. See the <a href="https://www.w3.org/TR/referrer-policy/" rel="noreferrer noopener">W3C Recommendation ↗</a>.',
							type: OC.SetupChecks.MESSAGE_TYPE_INFO
						}
					]);
					done();
				});
			});

			it('should return a message if Referrer-Policy is set to origin', function(done) {
				protocolStub.returns('https');
				var result = OC.SetupChecks.checkGeneric();

				suite.server.requests[0].respond(200, {
					'Strict-Transport-Security': 'max-age=15768000',
					'X-XSS-Protection': '1; mode=block',
					'X-Content-Type-Options': 'nosniff',
					'X-Robots-Tag': 'none',
					'X-Frame-Options': 'SAMEORIGIN',
					'X-Download-Options': 'noopen',
					'X-Permitted-Cross-Domain-Policies': 'none',
					'Referrer-Policy': 'origin',
				});

				result.done(function( data, s, x ){
					expect(data).toEqual([
						{
							msg: 'The "Referrer-Policy" HTTP header is not set to "no-referrer", "no-referrer-when-downgrade", "strict-origin" or "strict-origin-when-cross-origin". This can leak referer information. See the <a href="https://www.w3.org/TR/referrer-policy/" rel="noreferrer noopener">W3C Recommendation ↗</a>.',
							type: OC.SetupChecks.MESSAGE_TYPE_INFO
						}
					]);
					done();
				});
			});

			it('should return a message if Referrer-Policy is set to origin-when-cross-origin', function(done) {
				protocolStub.returns('https');
				var result = OC.SetupChecks.checkGeneric();

				suite.server.requests[0].respond(200, {
					'Strict-Transport-Security': 'max-age=15768000',
					'X-XSS-Protection': '1; mode=block',
					'X-Content-Type-Options': 'nosniff',
					'X-Robots-Tag': 'none',
					'X-Frame-Options': 'SAMEORIGIN',
					'X-Download-Options': 'noopen',
					'X-Permitted-Cross-Domain-Policies': 'none',
					'Referrer-Policy': 'origin-when-cross-origin',
				});

				result.done(function( data, s, x ){
					expect(data).toEqual([
						{
							msg: 'The "Referrer-Policy" HTTP header is not set to "no-referrer", "no-referrer-when-downgrade", "strict-origin" or "strict-origin-when-cross-origin". This can leak referer information. See the <a href="https://www.w3.org/TR/referrer-policy/" rel="noreferrer noopener">W3C Recommendation ↗</a>.',
							type: OC.SetupChecks.MESSAGE_TYPE_INFO
						}
					]);
					done();
				});
			});

			it('should return a message if Referrer-Policy is set to unsafe-url', function(done) {
				protocolStub.returns('https');
				var result = OC.SetupChecks.checkGeneric();

				suite.server.requests[0].respond(200, {
					'Strict-Transport-Security': 'max-age=15768000',
					'X-XSS-Protection': '1; mode=block',
					'X-Content-Type-Options': 'nosniff',
					'X-Robots-Tag': 'none',
					'X-Frame-Options': 'SAMEORIGIN',
					'X-Download-Options': 'noopen',
					'X-Permitted-Cross-Domain-Policies': 'none',
					'Referrer-Policy': 'unsafe-url',
				});

				result.done(function( data, s, x ){
					expect(data).toEqual([
						{
							msg: 'The "Referrer-Policy" HTTP header is not set to "no-referrer", "no-referrer-when-downgrade", "strict-origin" or "strict-origin-when-cross-origin". This can leak referer information. See the <a href="https://www.w3.org/TR/referrer-policy/" rel="noreferrer noopener">W3C Recommendation ↗</a>.',
							type: OC.SetupChecks.MESSAGE_TYPE_INFO
						}
					]);
					done();
				});
			});
		});
	});

	it('should return a SSL warning if HTTPS is not used', function(done) {
		protocolStub.returns('http');
		var async = OC.SetupChecks.checkGeneric();

		suite.server.requests[0].respond(200,
			{
				'X-XSS-Protection': '1; mode=block',
				'X-Content-Type-Options': 'nosniff',
				'X-Robots-Tag': 'none',
				'X-Frame-Options': 'SAMEORIGIN',
				'X-Download-Options': 'noopen',
				'X-Permitted-Cross-Domain-Policies': 'none',
				'Referrer-Policy': 'no-referrer',
			}
		);

		async.done(function( data, s, x ){
			expect(data).toEqual([{
				msg: 'Accessing site insecurely via HTTP. You are strongly adviced to set up your server to require HTTPS instead, as described in the <a href="https://docs.example.org/admin-security">security tips ↗</a>.',
				type: OC.SetupChecks.MESSAGE_TYPE_WARNING
			}]);
			done();
		});
	});

	it('should return an error if the response has no statuscode 200', function(done) {
		var async = OC.SetupChecks.checkGeneric();

		suite.server.requests[0].respond(
			500,
			{
				'Content-Type': 'application/json'
			},
			JSON.stringify({data: {serverHasInternetConnection: false}})
		);
		async.done(function( data, s, x ){
			expect(data).toEqual([{
				msg: 'Error occurred while checking server setup',
				type: OC.SetupChecks.MESSAGE_TYPE_ERROR
			}, {
				msg: 'Error occurred while checking server setup',
				type: OC.SetupChecks.MESSAGE_TYPE_ERROR
			}]);
			done();
		});
	});

	it('should return a SSL warning if SSL used without Strict-Transport-Security-Header', function(done) {
		protocolStub.returns('https');
		var async = OC.SetupChecks.checkGeneric();

		suite.server.requests[0].respond(200,
			{
				'X-XSS-Protection': '1; mode=block',
				'X-Content-Type-Options': 'nosniff',
				'X-Robots-Tag': 'none',
				'X-Frame-Options': 'SAMEORIGIN',
				'X-Download-Options': 'noopen',
				'X-Permitted-Cross-Domain-Policies': 'none',
				'Referrer-Policy': 'no-referrer',
			}
		);

		async.done(function( data, s, x ){
			expect(data).toEqual([{
				msg: 'The "Strict-Transport-Security" HTTP header is not set to at least "15552000" seconds. For enhanced security, it is recommended to enable HSTS as described in the <a rel="noreferrer noopener" href="https://docs.example.org/admin-security">security tips ↗</a>.',
				type: OC.SetupChecks.MESSAGE_TYPE_WARNING
			}]);
			done();
		});
	});

	it('should return a SSL warning if SSL used with to small Strict-Transport-Security-Header', function(done) {
		protocolStub.returns('https');
		var async = OC.SetupChecks.checkGeneric();

		suite.server.requests[0].respond(200,
			{
				'Strict-Transport-Security': 'max-age=15551999',
				'X-XSS-Protection': '1; mode=block',
				'X-Content-Type-Options': 'nosniff',
				'X-Robots-Tag': 'none',
				'X-Frame-Options': 'SAMEORIGIN',
				'X-Download-Options': 'noopen',
				'X-Permitted-Cross-Domain-Policies': 'none',
				'Referrer-Policy': 'no-referrer',
			}
		);

		async.done(function( data, s, x ){
			expect(data).toEqual([{
				msg: 'The "Strict-Transport-Security" HTTP header is not set to at least "15552000" seconds. For enhanced security, it is recommended to enable HSTS as described in the <a rel="noreferrer noopener" href="https://docs.example.org/admin-security">security tips ↗</a>.',
				type: OC.SetupChecks.MESSAGE_TYPE_WARNING
			}]);
			done();
		});
	});

	it('should return a SSL warning if SSL used with to a bogus Strict-Transport-Security-Header', function(done) {
		protocolStub.returns('https');
		var async = OC.SetupChecks.checkGeneric();

		suite.server.requests[0].respond(200,
			{
				'Strict-Transport-Security': 'iAmABogusHeader342',
				'X-XSS-Protection': '1; mode=block',
				'X-Content-Type-Options': 'nosniff',
				'X-Robots-Tag': 'none',
				'X-Frame-Options': 'SAMEORIGIN',
				'X-Download-Options': 'noopen',
				'X-Permitted-Cross-Domain-Policies': 'none',
				'Referrer-Policy': 'no-referrer',
			}
		);

		async.done(function( data, s, x ){
			expect(data).toEqual([{
				msg: 'The "Strict-Transport-Security" HTTP header is not set to at least "15552000" seconds. For enhanced security, it is recommended to enable HSTS as described in the <a rel="noreferrer noopener" href="https://docs.example.org/admin-security">security tips ↗</a>.',
				type: OC.SetupChecks.MESSAGE_TYPE_WARNING
			}]);
			done();
		});
	});

	it('should return no SSL warning if SSL used with to exact the minimum Strict-Transport-Security-Header', function(done) {
		protocolStub.returns('https');
		var async = OC.SetupChecks.checkGeneric();

		suite.server.requests[0].respond(200, {
			'Strict-Transport-Security': 'max-age=15768000',
			'X-XSS-Protection': '1; mode=block',
			'X-Content-Type-Options': 'nosniff',
			'X-Robots-Tag': 'none',
			'X-Frame-Options': 'SAMEORIGIN',
			'X-Download-Options': 'noopen',
			'X-Permitted-Cross-Domain-Policies': 'none',
			'Referrer-Policy': 'no-referrer',
		});

		async.done(function( data, s, x ){
			expect(data).toEqual([]);
			done();
		});
	});

	it('should return no SSL warning if SSL used with to more than the minimum Strict-Transport-Security-Header', function(done) {
		protocolStub.returns('https');
		var async = OC.SetupChecks.checkGeneric();

		suite.server.requests[0].respond(200, {
			'Strict-Transport-Security': 'max-age=99999999',
			'X-XSS-Protection': '1; mode=block',
			'X-Content-Type-Options': 'nosniff',
			'X-Robots-Tag': 'none',
			'X-Frame-Options': 'SAMEORIGIN',
			'X-Download-Options': 'noopen',
			'X-Permitted-Cross-Domain-Policies': 'none',
			'Referrer-Policy': 'no-referrer',
		});

		async.done(function( data, s, x ){
			expect(data).toEqual([]);
			done();
		});
	});

	it('should return no SSL warning if SSL used with to more than the minimum Strict-Transport-Security-Header and includeSubDomains parameter', function(done) {
		protocolStub.returns('https');
		var async = OC.SetupChecks.checkGeneric();

		suite.server.requests[0].respond(200, {
			'Strict-Transport-Security': 'max-age=99999999; includeSubDomains',
			'X-XSS-Protection': '1; mode=block',
			'X-Content-Type-Options': 'nosniff',
			'X-Robots-Tag': 'none',
			'X-Frame-Options': 'SAMEORIGIN',
			'X-Download-Options': 'noopen',
			'X-Permitted-Cross-Domain-Policies': 'none',
			'Referrer-Policy': 'no-referrer',
		});

		async.done(function( data, s, x ){
			expect(data).toEqual([]);
			done();
		});
	});

	it('should return no SSL warning if SSL used with to more than the minimum Strict-Transport-Security-Header and includeSubDomains and preload parameter', function(done) {
		protocolStub.returns('https');
		var async = OC.SetupChecks.checkGeneric();

		suite.server.requests[0].respond(200, {
			'Strict-Transport-Security': 'max-age=99999999; preload; includeSubDomains',
			'X-XSS-Protection': '1; mode=block',
			'X-Content-Type-Options': 'nosniff',
			'X-Robots-Tag': 'none',
			'X-Frame-Options': 'SAMEORIGIN',
			'X-Download-Options': 'noopen',
			'X-Permitted-Cross-Domain-Policies': 'none',
			'Referrer-Policy': 'no-referrer',
		});

		async.done(function( data, s, x ){
			expect(data).toEqual([]);
			done();
		});
	});
});
