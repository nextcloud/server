/**
 * Copyright (c) 2015
 *
 * @author Robin Appelman <robin@icewind.nl>
 * @author Vincent Petry <vincent@nextcloud.com>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
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

