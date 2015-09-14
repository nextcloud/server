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
			name: 'some file.txt'
		});
		collection = new VersionCollection();
		collection.setFileInfo(fileInfoModel);
	});
	it('fetches the next page', function() {
		collection.fetchNext();

		expect(fakeServer.requests.length).toEqual(1);
		expect(fakeServer.requests[0].url).toEqual(
			OC.generateUrl('apps/files_versions/ajax/getVersions.php') +
			'?source=%2Fsubdir%2Fsome%20file.txt&start=0'
		);
		fakeServer.requests[0].respond(
			200,
			{ 'Content-Type': 'application/json' },
			JSON.stringify({
				status: 'success',
				data: {
					endReached: false,
					versions: [{
						version: 10000000,
						size: 123,
						name: 'some file.txt',
						fullPath: '/subdir/some file.txt'
					},{
						version: 15000000,
						size: 150,
						name: 'some file.txt',
						path: '/subdir/some file.txt'
					}]
				}
			})
		);

		expect(collection.length).toEqual(2);
		expect(collection.hasMoreResults()).toEqual(true);

		collection.fetchNext();

		expect(fakeServer.requests.length).toEqual(2);
		expect(fakeServer.requests[1].url).toEqual(
			OC.generateUrl('apps/files_versions/ajax/getVersions.php') +
			'?source=%2Fsubdir%2Fsome%20file.txt&start=2'
		);
		fakeServer.requests[1].respond(
			200,
			{ 'Content-Type': 'application/json' },
			JSON.stringify({
				status: 'success',
				data: {
					endReached: true,
					versions: [{
						version: 18000000,
						size: 123,
						name: 'some file.txt',
						path: '/subdir/some file.txt'
					}]
				}
			})
		);

		expect(collection.length).toEqual(3);
		expect(collection.hasMoreResults()).toEqual(false);

		collection.fetchNext();

		// no further requests
		expect(fakeServer.requests.length).toEqual(2);
	});
	it('properly parses the results', function() {
		collection.fetchNext();

		expect(fakeServer.requests.length).toEqual(1);
		expect(fakeServer.requests[0].url).toEqual(
			OC.generateUrl('apps/files_versions/ajax/getVersions.php') +
			'?source=%2Fsubdir%2Fsome%20file.txt&start=0'
		);
		fakeServer.requests[0].respond(
			200,
			{ 'Content-Type': 'application/json' },
			JSON.stringify({
				status: 'success',
				data: {
					endReached: false,
					versions: [{
						version: 10000000,
						size: 123,
						name: 'some file.txt',
						path: '/subdir/some file.txt'
					},{
						version: 15000000,
						size: 150,
						name: 'some file.txt',
						path: '/subdir/some file.txt'
					}]
				}
			})
		);

		expect(collection.length).toEqual(2);

		var model = collection.at(0);
		expect(model.get('id')).toEqual(10000000);
		expect(model.get('timestamp')).toEqual(10000000);
		expect(model.get('name')).toEqual('some file.txt');
		expect(model.get('fullPath')).toEqual('/subdir/some file.txt');
		expect(model.get('size')).toEqual(123);

		model = collection.at(1);
		expect(model.get('id')).toEqual(15000000);
		expect(model.get('timestamp')).toEqual(15000000);
		expect(model.get('name')).toEqual('some file.txt');
		expect(model.get('fullPath')).toEqual('/subdir/some file.txt');
		expect(model.get('size')).toEqual(150);
	});
	it('resets page counted when setting a new file info model', function() {
		collection.fetchNext();

		expect(fakeServer.requests.length).toEqual(1);
		fakeServer.requests[0].respond(
			200,
			{ 'Content-Type': 'application/json' },
			JSON.stringify({
				status: 'success',
				data: {
					endReached: true,
					versions: [{
						version: 18000000,
						size: 123,
						name: 'some file.txt',
						path: '/subdir/some file.txt'
					}]
				}
			})
		);

		expect(collection.hasMoreResults()).toEqual(false);

		collection.setFileInfo(fileInfoModel);

		expect(collection.hasMoreResults()).toEqual(true);
	});
});

