/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2015 ownCloud Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
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

	describe('checkSetup', function() {
		it('should return an error if server has no internet connection', function(done) {
			var async = OC.SetupChecks.checkSetup();

			suite.server.requests[0].respond(
				200,
				{
					'Content-Type': 'application/json'
				},
				JSON.stringify({
					generic: {
						network: {
							"Internet connectivity": {
								severity: "warning",
								description: 'This server has no working internet connection: Multiple endpoints could not be reached. This means that some of the features like mounting external storage, notifications about updates or installation of third-party apps will not work. Accessing files remotely and sending of notification emails might not work, either. Establish a connection from this server to the internet to enjoy all features.',
								linkToDoc: null
							}
						},
					},
				})
			);

			async.done(function( data, s, x ){
				expect(data).toEqual([
					{
						msg: 'This server has no working internet connection: Multiple endpoints could not be reached. This means that some of the features like mounting external storage, notifications about updates or installation of third-party apps will not work. Accessing files remotely and sending of notification emails might not work, either. Establish a connection from this server to the internet to enjoy all features.',
						type: OC.SetupChecks.MESSAGE_TYPE_WARNING
					},
				]);
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
					generic: {
						network: {
							"Internet connectivity": {
								severity: "warning",
								description: 'This server has no working internet connection: Multiple endpoints could not be reached. This means that some of the features like mounting external storage, notifications about updates or installation of third-party apps will not work. Accessing files remotely and sending of notification emails might not work, either. Establish a connection from this server to the internet to enjoy all features.',
								linkToDoc: null
							}
						},
					},
				})
			);

			async.done(function( data, s, x ){
				expect(data).toEqual([
					{
						msg: 'This server has no working internet connection: Multiple endpoints could not be reached. This means that some of the features like mounting external storage, notifications about updates or installation of third-party apps will not work. Accessing files remotely and sending of notification emails might not work, either. Establish a connection from this server to the internet to enjoy all features.',
						type: OC.SetupChecks.MESSAGE_TYPE_WARNING
					},
				]);
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
					generic: {
						network: {
							"Internet connectivity": {
								severity: "warning",
								description: 'This server has no working internet connection: Multiple endpoints could not be reached. This means that some of the features like mounting external storage, notifications about updates or installation of third-party apps will not work. Accessing files remotely and sending of notification emails might not work, either. Establish a connection from this server to the internet to enjoy all features.',
								linkToDoc: null
							}
						},
					},
				})
			);

			async.done(function( data, s, x ){
				expect(data).toEqual([
				{
					msg: 'This server has no working internet connection: Multiple endpoints could not be reached. This means that some of the features like mounting external storage, notifications about updates or installation of third-party apps will not work. Accessing files remotely and sending of notification emails might not work, either. Establish a connection from this server to the internet to enjoy all features.',
					type: OC.SetupChecks.MESSAGE_TYPE_WARNING
				}
				]);
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
					generic: {
						network: {
							"Internet connectivity": {
								severity: "success",
								description: null,
								linkToDoc: null
							}
						},
						php: {
							"Internet connectivity": {
								severity: "success",
								description: null,
								linkToDoc: null
							},
							"PHP memory limit": {
								severity: "error",
								description: "The PHP memory limit is below the recommended value of 512MB.",
								linkToDoc: null
							},
						},
					},
				})
			);

			async.done(function( data, s, x ){
				expect(data).toEqual([{
					msg: 'The PHP memory limit is below the recommended value of 512MB.',
					type: OC.SetupChecks.MESSAGE_TYPE_ERROR
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
				JSON.stringify({data: {serverHasInternetConnectionProblems: true}})
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
					generic: {
						network: {
							"Internet connectivity": {
								severity: "success",
								description: null,
								linkToDoc: null
							}
						},
						security: {
							"Checking for PHP version": {
								severity: "warning",
								description: "You are currently running PHP 8.0.30. PHP 8.0 is now deprecated in Nextcloud 27. Nextcloud 28 may require at least PHP 8.1. Please upgrade to one of the officially supported PHP versions provided by the PHP Group as soon as possible.",
								linkToDoc: "https://secure.php.net/supported-versions.php"
							}
						},
					},
				})
			);

			async.done(function( data, s, x ){
				expect(data).toEqual([{
					msg: 'You are currently running PHP 8.0.30. PHP 8.0 is now deprecated in Nextcloud 27. Nextcloud 28 may require at least PHP 8.1. Please upgrade to one of the officially supported PHP versions provided by the PHP Group as soon as possible. For more details see the <a target="_blank" rel="noreferrer noopener" class="external" href="https://secure.php.net/supported-versions.php">documentation ↗</a>.',
					type: OC.SetupChecks.MESSAGE_TYPE_WARNING
				}]);
				done();
			});
		});

		it('should not return an error if the protocol is http and the server generates http links', function(done) {
			var async = OC.SetupChecks.checkSetup();

			suite.server.requests[0].respond(
				200,
				{
					'Content-Type': 'application/json',
				},
				JSON.stringify({
					generic: {
						network: {
							"Internet connectivity": {
								severity: "success",
								description: null,
								linkToDoc: null
							}
						},
					},
				})
			);

			async.done(function( data, s, x ){
				expect(data).toEqual([]);
				done();
			});
		});

		it('should return an info if there is no default phone region', function(done) {
			var async = OC.SetupChecks.checkSetup();

			suite.server.requests[0].respond(
				200,
				{
					'Content-Type': 'application/json',
				},
				JSON.stringify({
					generic: {
						network: {
							"Internet connectivity": {
								severity: "success",
								description: null,
								linkToDoc: null
							}
						},
						config: {
							"Checking for default phone region": {
								severity: "info",
								description: "Your installation has no default phone region set. This is required to validate phone numbers in the profile settings without a country code. To allow numbers without a country code, please add \"default_phone_region\" with the respective ISO 3166-1 code of the region to your config file.",
								linkToDoc: "https://en.wikipedia.org/wiki/ISO_3166-1_alpha-2#Officially_assigned_code_elements"
							},
						},
					},
				})
			);

			async.done(function( data, s, x ){
				expect(data).toEqual([{
					msg: 'Your installation has no default phone region set. This is required to validate phone numbers in the profile settings without a country code. To allow numbers without a country code, please add &quot;default_phone_region&quot; with the respective ISO 3166-1 code of the region to your config file. For more details see the <a target="_blank" rel="noreferrer noopener" class="external" href="https://en.wikipedia.org/wiki/ISO_3166-1_alpha-2#Officially_assigned_code_elements">documentation ↗</a>.',
					type: OC.SetupChecks.MESSAGE_TYPE_INFO
				}]);
				done();
			});
		});
	});
});
