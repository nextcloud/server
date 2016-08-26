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
					msg: 'Your web server is not yet set up properly to allow file synchronization because the WebDAV interface seems to be broken.',
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
					msg: 'Your web server is not set up properly to resolve "/.well-known/caldav/". Further information can be found in our <a target="_blank" rel="noreferrer" href="http://example.org/admin-setup-well-known-URL">documentation</a>.',
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

			suite.server.requests[0].respond(200, {'Content-Type': 'text/plain'}, 'file contents');

			async.done(function( data, s, x ){
				expect(data).toEqual([
					{
						msg: 'Your data directory and your files are probably accessible from the Internet. The .htaccess file is not working. We strongly suggest that you configure your web server in a way that the data directory is no longer accessible or you move the data directory outside the web server document root.',
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
					isUrandomAvailable: true,
					serverHasInternetConnection: false,
					memcacheDocs: 'https://doc.owncloud.org/server/go.php?to=admin-performance',
					forwardedForHeadersWorking: true,
					isCorrectMemcachedPHPModuleInstalled: true,
					hasPassedCodeIntegrityCheck: true,
				})
			);

			async.done(function( data, s, x ){
				expect(data).toEqual([
					{
						msg: 'This server has no working Internet connection. This means that some of the features like mounting external storage, notifications about updates or installation of third-party apps will not work. Accessing files remotely and sending of notification emails might not work, either. We suggest to enable Internet connection for this server if you want to have all features.',
						type: OC.SetupChecks.MESSAGE_TYPE_WARNING
					}, {
						msg: 'No memory cache has been configured. To enhance your performance please configure a memcache if available. Further information can be found in our <a target="_blank" rel="noreferrer" href="https://doc.owncloud.org/server/go.php?to=admin-performance">documentation</a>.',
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
					isUrandomAvailable: true,
					serverHasInternetConnection: false,
					memcacheDocs: 'https://doc.owncloud.org/server/go.php?to=admin-performance',
					forwardedForHeadersWorking: true,
					isCorrectMemcachedPHPModuleInstalled: true,
					hasPassedCodeIntegrityCheck: true,
				})
			);

			async.done(function( data, s, x ){
				expect(data).toEqual([
					{
						msg: 'This server has no working Internet connection. This means that some of the features like mounting external storage, notifications about updates or installation of third-party apps will not work. Accessing files remotely and sending of notification emails might not work, either. We suggest to enable Internet connection for this server if you want to have all features.',
						type: OC.SetupChecks.MESSAGE_TYPE_WARNING
					},
					{
						msg: 'No memory cache has been configured. To enhance your performance please configure a memcache if available. Further information can be found in our <a target="_blank" rel="noreferrer" href="https://doc.owncloud.org/server/go.php?to=admin-performance">documentation</a>.',
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
					isUrandomAvailable: true,
					serverHasInternetConnection: false,
					isMemcacheConfigured: true,
					forwardedForHeadersWorking: true,
					isCorrectMemcachedPHPModuleInstalled: true,
					hasPassedCodeIntegrityCheck: true,
				})
			);

			async.done(function( data, s, x ){
				expect(data).toEqual([
				{
					msg: 'This server has no working Internet connection. This means that some of the features like mounting external storage, notifications about updates or installation of third-party apps will not work. Accessing files remotely and sending of notification emails might not work, either. We suggest to enable Internet connection for this server if you want to have all features.',
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
					isUrandomAvailable: false,
					securityDocs: 'https://docs.owncloud.org/myDocs.html',
					serverHasInternetConnection: true,
					isMemcacheConfigured: true,
					forwardedForHeadersWorking: true,
					isCorrectMemcachedPHPModuleInstalled: true,
					hasPassedCodeIntegrityCheck: true,
				})
			);

			async.done(function( data, s, x ){
				expect(data).toEqual([{
					msg: '/dev/urandom is not readable by PHP which is highly discouraged for security reasons. Further information can be found in our <a target="_blank" rel="noreferrer" href="https://docs.owncloud.org/myDocs.html">documentation</a>.',
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
					isUrandomAvailable: true,
					securityDocs: 'https://docs.owncloud.org/myDocs.html',
					serverHasInternetConnection: true,
					isMemcacheConfigured: true,
					forwardedForHeadersWorking: true,
					isCorrectMemcachedPHPModuleInstalled: false,
					hasPassedCodeIntegrityCheck: true,
				})
			);

			async.done(function( data, s, x ){
				expect(data).toEqual([{
					msg: 'Memcached is configured as distributed cache, but the wrong PHP module "memcache" is installed. \\OC\\Memcache\\Memcached only supports "memcached" and not "memcache". See the <a target="_blank" rel="noreferrer" href="https://code.google.com/p/memcached/wiki/PHPClientComparison">memcached wiki about both modules</a>.',
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
					isUrandomAvailable: true,
					serverHasInternetConnection: true,
					isMemcacheConfigured: true,
					forwardedForHeadersWorking: false,
					reverseProxyDocs: 'https://docs.owncloud.org/foo/bar.html',
					isCorrectMemcachedPHPModuleInstalled: true,
					hasPassedCodeIntegrityCheck: true,
				})
			);

			async.done(function( data, s, x ){
				expect(data).toEqual([{
					msg: 'The reverse proxy headers configuration is incorrect, or you are accessing ownCloud from a trusted proxy. If you are not accessing ownCloud from a trusted proxy, this is a security issue and can allow an attacker to spoof their IP address as visible to ownCloud. Further information can be found in our <a target="_blank" rel="noreferrer" href="https://docs.owncloud.org/foo/bar.html">documentation</a>.',
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
					isUrandomAvailable: true,
					securityDocs: 'https://docs.owncloud.org/myDocs.html',
					serverHasInternetConnection: true,
					isMemcacheConfigured: true,
					forwardedForHeadersWorking: true,
					phpSupported: {eol: true, version: '5.4.0'},
					isCorrectMemcachedPHPModuleInstalled: true,
					hasPassedCodeIntegrityCheck: true,
				})
			);

			async.done(function( data, s, x ){
				expect(data).toEqual([{
					msg: 'You are currently running PHP 5.4.0. We encourage you to upgrade your PHP version to take advantage of <a target="_blank" rel="noreferrer" href="https://secure.php.net/supported-versions.php">performance and security updates provided by the PHP Group</a> as soon as your distribution supports it.',
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
					msg: 'The "X-XSS-Protection" HTTP header is not configured to equal to "1; mode=block". This is a potential security or privacy risk and we recommend adjusting this setting.',
					type: OC.SetupChecks.MESSAGE_TYPE_WARNING
				}, {
					msg: 'The "X-Content-Type-Options" HTTP header is not configured to equal to "nosniff". This is a potential security or privacy risk and we recommend adjusting this setting.',
					type: OC.SetupChecks.MESSAGE_TYPE_WARNING
				}, {
					msg: 'The "X-Robots-Tag" HTTP header is not configured to equal to "none". This is a potential security or privacy risk and we recommend adjusting this setting.',
					type: OC.SetupChecks.MESSAGE_TYPE_WARNING

				}, {
					msg: 'The "X-Frame-Options" HTTP header is not configured to equal to "SAMEORIGIN". This is a potential security or privacy risk and we recommend adjusting this setting.',
					type: OC.SetupChecks.MESSAGE_TYPE_WARNING
				}, {
					msg: 'The "X-Download-Options" HTTP header is not configured to equal to "noopen". This is a potential security or privacy risk and we recommend adjusting this setting.',
					type: OC.SetupChecks.MESSAGE_TYPE_WARNING
				}, {
					msg: 'The "X-Permitted-Cross-Domain-Policies" HTTP header is not configured to equal to "none". This is a potential security or privacy risk and we recommend adjusting this setting.',
					type: OC.SetupChecks.MESSAGE_TYPE_WARNING
				},
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
				}
			);

			async.done(function( data, s, x ){
				expect(data).toEqual([{
					msg: 'The "X-XSS-Protection" HTTP header is not configured to equal to "1; mode=block". This is a potential security or privacy risk and we recommend adjusting this setting.', 
					type: OC.SetupChecks.MESSAGE_TYPE_WARNING,
				}, {
					msg: 'The "X-Content-Type-Options" HTTP header is not configured to equal to "nosniff". This is a potential security or privacy risk and we recommend adjusting this setting.',
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
				}
			);

			async.done(function( data, s, x ){
				expect(data).toEqual([]);
				done();
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
			}
		);

		async.done(function( data, s, x ){
			expect(data).toEqual([{
				msg: 'You are accessing this site via HTTP. We strongly suggest you configure your server to require using HTTPS instead as described in our <a href="http://localhost/index.php/settings/admin/tips-tricks">security tips</a>.',
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
			}
		);

		async.done(function( data, s, x ){
			expect(data).toEqual([{
				msg: 'The "Strict-Transport-Security" HTTP header is not configured to at least "15552000" seconds. For enhanced security we recommend enabling HSTS as described in our <a href="http://localhost/index.php/settings/admin/tips-tricks" rel="noreferrer">security tips</a>.',
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
			}
		);

		async.done(function( data, s, x ){
			expect(data).toEqual([{
				msg: 'The "Strict-Transport-Security" HTTP header is not configured to at least "15552000" seconds. For enhanced security we recommend enabling HSTS as described in our <a href="http://localhost/index.php/settings/admin/tips-tricks" rel="noreferrer">security tips</a>.',
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
			}
		);

		async.done(function( data, s, x ){
			expect(data).toEqual([{
				msg: 'The "Strict-Transport-Security" HTTP header is not configured to at least "15552000" seconds. For enhanced security we recommend enabling HSTS as described in our <a href="http://localhost/index.php/settings/admin/tips-tricks" rel="noreferrer">security tips</a>.',
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
		});

		async.done(function( data, s, x ){
			expect(data).toEqual([]);
			done();
		});
	});
});
