/*
 * Copyright (c) 2015
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
 *
 */
describe('OCA.Versions.VersionModel', function() {
	var VersionModel = OCA.Versions.VersionModel;
	var model;
	var uid = OC.currentUser = 'user';

	beforeEach(function() {
		model = new VersionModel({
			id: 10000000,
			fileId: 10,
			timestamp: 10000000,
			fullPath: '/subdir/some file.txt',
			name: 'some file.txt',
			size: 150,
			user: 'user',
			client: new OC.Files.Client({
				host: 'localhost',
				port: 80,
				root: '/remote.php/dav/versions/user',
				useHTTPS: OC.getProtocol() === 'https'
			})
		});
	});

	it('returns the full path', function() {
		expect(model.getFullPath()).toEqual('/subdir/some file.txt');
	});
	it('returns the preview url', function() {
		expect(model.getPreviewUrl())
			.toEqual(OC.generateUrl('/apps/files_versions/preview') +
					'?file=%2Fsubdir%2Fsome%20file.txt&version=10000000'
			);
	});
	it('returns the download url', function() {
		expect(model.getDownloadUrl())
			.toEqual(OC.linkToRemoteBase('dav') + '/versions/' + uid +
					'/versions/10/10000000'
			);
	});
	describe('reverting', function() {
		var revertEventStub;
		var successStub;
		var errorStub;

		beforeEach(function() {
			revertEventStub = sinon.stub();
			errorStub = sinon.stub();
			successStub = sinon.stub();

			model.on('revert', revertEventStub);
			model.on('error', errorStub);
		});
		it('tells the server to revert when calling the revert method', function(done) {
			var promise = model.revert({
				success: successStub
			});

			expect(fakeServer.requests.length).toEqual(1);
			var request = fakeServer.requests[0];
			expect(request.url)
				.toEqual(
					OC.linkToRemoteBase('dav') + '/versions/user/versions/10/10000000'
				);
			expect(request.requestHeaders.Destination).toEqual(OC.getRootPath() + '/remote.php/dav/versions/user/restore/target');
			request.respond(201);

			promise.then(function() {
				expect(revertEventStub.calledOnce).toEqual(true);
				expect(successStub.calledOnce).toEqual(true);
				expect(errorStub.notCalled).toEqual(true);

				done();
			});
		});
		it('triggers error event when server returns a failure', function(done) {
			var promise = model.revert({
				success: successStub
			});

			expect(fakeServer.requests.length).toEqual(1);
			var responseErrorHeaders = {
				"Content-Type": "application/xml"
			};
			var responseErrorBody =
				'<d:error xmlns:d="DAV:" xmlns:s="http://sabredav.org/ns">' +
				'    <s:exception>Sabre\\DAV\\Exception\\SomeException</s:exception>' +
				'    <s:message>Some error message</s:message>' +
				'</d:error>';
			fakeServer.requests[0].respond(404, responseErrorHeaders, responseErrorBody);

			promise.fail(function() {
				expect(revertEventStub.notCalled).toEqual(true);
				expect(successStub.notCalled).toEqual(true);
				expect(errorStub.calledOnce).toEqual(true);

				done();
			});
		});
	});
});

