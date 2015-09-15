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

	beforeEach(function() {
		model = new VersionModel({
			id: 10000000,
			timestamp: 10000000,
			fullPath: '/subdir/some file.txt',
			name: 'some file.txt',
			size: 150
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
			.toEqual(OC.generateUrl('/apps/files_versions/download.php') +
					'?file=%2Fsubdir%2Fsome%20file.txt&revision=10000000'
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
		it('tells the server to revert when calling the revert method', function() {
			model.revert({
				success: successStub
			});

			expect(fakeServer.requests.length).toEqual(1);
			expect(fakeServer.requests[0].url)
				.toEqual(
					OC.generateUrl('/apps/files_versions/ajax/rollbackVersion.php') +
					'?file=%2Fsubdir%2Fsome+file.txt&revision=10000000'
				);

			fakeServer.requests[0].respond(
				200,
				{ 'Content-Type': 'application/json' },
				JSON.stringify({
					status: 'success',
				})
			);

			expect(revertEventStub.calledOnce).toEqual(true);
			expect(successStub.calledOnce).toEqual(true);
			expect(errorStub.notCalled).toEqual(true);
		});
		it('triggers error event when server returns a failure', function() {
			model.revert({
				success: successStub
			});

			expect(fakeServer.requests.length).toEqual(1);
			fakeServer.requests[0].respond(
				200,
				{ 'Content-Type': 'application/json' },
				JSON.stringify({
					status: 'error',
				})
			);

			expect(revertEventStub.notCalled).toEqual(true);
			expect(successStub.notCalled).toEqual(true);
			expect(errorStub.calledOnce).toEqual(true);
		});
	});
});

