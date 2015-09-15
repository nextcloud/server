/**
* ownCloud
*
* @author Vincent Petry
* @copyright 2015 Vincent Petry <pvince81@owncloud.com>
*
* This library is free software; you can redistribute it and/or
* modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
* License as published by the Free Software Foundation; either
* version 3 of the License, or any later version.
*
* This library is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU AFFERO GENERAL PUBLIC LICENSE for more details.
*
* You should have received a copy of the GNU Affero General Public
* License along with this library.  If not, see <http://www.gnu.org/licenses/>.
*
*/

/* global oc_appconfig */
describe('OC.Share.ShareItemModel', function() {
	var loadItemStub;
	var fileInfoModel, configModel, model;

	beforeEach(function() {
		loadItemStub = sinon.stub(OC.Share, 'loadItem');
		loadItemStub.returns({
			reshare: [],
			shares: []
		});

		fileInfoModel = new OCA.Files.FileInfoModel({
			id: 123,
			name: 'shared_file_name.txt',
			path: '/subdir',
			size: 100,
			mimetype: 'text/plain',
			permissions: 31,
			sharePermissions: 31
		});

		var attributes = {
			itemType: fileInfoModel.isDirectory() ? 'folder' : 'file',
			itemSource: fileInfoModel.get('id'),
			possiblePermissions: fileInfoModel.get('sharePermissions')
		};
		configModel = new OC.Share.ShareConfigModel();
		model = new OC.Share.ShareItemModel(attributes, {
			configModel: configModel,
			fileInfoModel: fileInfoModel
		});
	});
	afterEach(function() {
		loadItemStub.restore();
	});

	describe('Fetching and parsing', function() {
		it('fetching calls loadItem with the correct arguments', function() {
			model.fetch();

			expect(loadItemStub.calledOnce).toEqual(true);
			expect(loadItemStub.calledWith('file', 123)).toEqual(true);
		});
		it('populates attributes with parsed response', function() {
			loadItemStub.returns({
				/* jshint camelcase: false */
				reshare: {
					share_type: OC.Share.SHARE_TYPE_USER,
					uid_owner: 'owner',
					displayname_owner: 'Owner',
					permissions: 31
				},
				shares: [{
					id: 100,
					item_source: 123,
					permissions: 31,
					share_type: OC.Share.SHARE_TYPE_USER,
					share_with: 'user1',
					share_with_displayname: 'User One'
				}, {
					id: 101,
					item_source: 123,
					permissions: 31,
					share_type: OC.Share.SHARE_TYPE_GROUP,
					share_with: 'group',
					share_with_displayname: 'group'
				}, {
					id: 102,
					item_source: 123,
					permissions: 31,
					share_type: OC.Share.SHARE_TYPE_REMOTE,
					share_with: 'foo@bar.com/baz',
					share_with_displayname: 'foo@bar.com/baz'

				}, {
					displayname_owner: 'root',
					expiration: null,
					file_source: 123,
					file_target: '/folder',
					id: 20,
					item_source: '123',
					item_type: 'folder',
					mail_send: '0',
					parent: null,
					path: '/folder',
					permissions: OC.PERMISSION_READ,
					share_type: OC.Share.SHARE_TYPE_LINK,
					share_with: null,
					stime: 1403884258,
					storage: 1,
					token: 'tehtoken',
					uid_owner: 'root'
				}]
			});
			model.fetch();

			var shares = model.get('shares');
			expect(shares.length).toEqual(3);
			expect(shares.file_source).toEqual(123);

			var linkShare = model.get('linkShare');
			expect(linkShare.isLinkShare).toEqual(true);

			// TODO: check more attributes
		});
		it('does not parse link share when for a different file', function() {
			loadItemStub.returns({
				reshare: [],
				/* jshint camelcase: false */
				shares: [{
					displayname_owner: 'root',
					expiration: null,
					file_source: 456,
					file_target: '/folder',
					id: 20,
					item_source: '456',
					item_type: 'folder',
					mail_send: '0',
					parent: null,
					path: '/folder',
					permissions: OC.PERMISSION_READ,
					share_type: OC.Share.SHARE_TYPE_LINK,
					share_with: null,
					stime: 1403884258,
					storage: 1,
					token: 'tehtoken',
					uid_owner: 'root'
				}]
			});

			model.fetch();

			var shares = model.get('shares');
			expect(shares.length).toEqual(0);

			var linkShare = model.get('linkShare');
			expect(linkShare.isLinkShare).toEqual(false);
		});
		it('parsess correct link share when a nested link share exists along with parent one', function() {
			loadItemStub.returns({
				reshare: [],
				/* jshint camelcase: false */
				shares: [{
					displayname_owner: 'root',
					expiration: 1111,
					file_source: 123,
					file_target: '/folder',
					id: 20,
					item_source: '123',
					item_type: 'file',
					mail_send: '0',
					parent: null,
					path: '/folder',
					permissions: OC.PERMISSION_READ,
					share_type: OC.Share.SHARE_TYPE_LINK,
					share_with: null,
					stime: 1403884258,
					storage: 1,
					token: 'tehtoken',
					uid_owner: 'root'
				}, {
					displayname_owner: 'root',
					expiration: 2222,
					file_source: 456,
					file_target: '/file_in_folder.txt',
					id: 21,
					item_source: '456',
					item_type: 'file',
					mail_send: '0',
					parent: null,
					path: '/folder/file_in_folder.txt',
					permissions: OC.PERMISSION_READ,
					share_type: OC.Share.SHARE_TYPE_LINK,
					share_with: null,
					stime: 1403884509,
					storage: 1,
					token: 'anothertoken',
					uid_owner: 'root'
				}]
			});

			model.fetch();

			var shares = model.get('shares');
			expect(shares.length).toEqual(0);

			var linkShare = model.get('linkShare');
			expect(linkShare.isLinkShare).toEqual(false);
			expect(linkShare.token).toEqual('tehtoken');

			// TODO: check child too
		});
	});

	describe('Util', function() {
		it('parseTime should properly parse strings', function() {

			_.each([
				[ '123456', 123456],
				[  123456 , 123456],
				['0123456', 123456],
				['abcdefg',   null],
				['0x12345',   null],
				[       '',   null],
			], function(value) {
				expect(OC.Share._parseTime(value[0])).toEqual(value[1]);
			});

		});
	});
});

