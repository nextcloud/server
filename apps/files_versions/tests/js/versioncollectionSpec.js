/*
 * Copyright (c) 2015
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
 *
 */
describe('OCA.Versions.VersionCollection', function() {
	var VersionCollection = OCA.Versions.VersionCollection;
	var collection, fileInfoModel;

	beforeEach(function() {
		fileInfoModel = new OCA.Files.FileInfoModel({
			path: '/subdir',
			name: 'some file.txt',
			id: 10,
		});
		collection = new VersionCollection();
		collection.setFileInfo(fileInfoModel);
		collection.setCurrentUser('user');
	});
	it('fetches the versions', function() {
		collection.fetch();

		expect(fakeServer.requests.length).toEqual(1);
		expect(fakeServer.requests[0].url).toEqual(
			OC.linkToRemoteBase('dav') + '/versions/user/versions/10'
		);
		fakeServer.requests[0].respond(200);
	});
});

