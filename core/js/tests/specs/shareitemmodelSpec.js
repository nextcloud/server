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
	var oldCurrentUser;

	beforeEach(function() {
		oldCurrentUser = OC.currentUser;

		loadItemStub = sinon.stub(OC.Share, 'loadItem');

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
		OC.currentUser = oldCurrentUser;
	});

	describe('Fetching and parsing', function() {
		it('fetching calls loadItem with the correct arguments', function() {
			model.fetch();

			expect(loadItemStub.calledOnce).toEqual(true);
			expect(loadItemStub.calledWith('file', 123)).toEqual(true);
		});
		it('populates attributes with parsed response', function() {
			loadItemStub.yields({
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
			expect(shares[0].id).toEqual(100);
			expect(shares[0].permissions).toEqual(31);
			expect(shares[0].share_type).toEqual(OC.Share.SHARE_TYPE_USER);
			expect(shares[0].share_with).toEqual('user1');
			expect(shares[0].share_with_displayname).toEqual('User One');

			var linkShare = model.get('linkShare');
			expect(linkShare.isLinkShare).toEqual(true);

			// TODO: check more attributes
		});
		it('does not parse link share when for a different file', function() {
			loadItemStub.yields({
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
			// remaining share appears in this list
			expect(shares.length).toEqual(1);

			var linkShare = model.get('linkShare');
			expect(linkShare.isLinkShare).toEqual(false);
		});
		it('parses correct link share when a nested link share exists along with parent one', function() {
			loadItemStub.yields({
				reshare: [],
				/* jshint camelcase: false */
				shares: [{
					displayname_owner: 'root',
					expiration: '2015-10-12 00:00:00',
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
					expiration: '2015-10-15 00:00:00',
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
			// the parent share remains in the list
			expect(shares.length).toEqual(1);

			var linkShare = model.get('linkShare');
			expect(linkShare.isLinkShare).toEqual(true);
			expect(linkShare.token).toEqual('tehtoken');

			// TODO: check child too
		});
		it('reduces reshare permissions to the ones from the original share', function() {
			loadItemStub.yields({
				reshare: {
					permissions: OC.PERMISSION_READ,
					uid_owner: 'user1'
				},
				shares: []
			});
			model.fetch();

			// no resharing allowed
			expect(model.get('permissions')).toEqual(OC.PERMISSION_READ);
		});
		it('reduces reshare permissions to possible permissions', function() {
			loadItemStub.yields({
				reshare: {
					permissions: OC.PERMISSION_ALL,
					uid_owner: 'user1'
				},
				shares: []
			});

			model.set('possiblePermissions', OC.PERMISSION_READ);
			model.fetch();

			// no resharing allowed
			expect(model.get('permissions')).toEqual(OC.PERMISSION_READ);
		});
		it('allows owner to share their own share when they are also the recipient', function() {
			OC.currentUser = 'user1';
			loadItemStub.yields({
				reshare: {},
				shares: []
			});

			model.fetch();

			// sharing still allowed
			expect(model.get('permissions') & OC.PERMISSION_SHARE).toEqual(OC.PERMISSION_SHARE);
		});
		it('properly parses integer values when the server is in the mood of returning ints as string', function() {
			loadItemStub.yields({
				reshare: {},
				shares: [{
					displayname_owner: 'root',
					expiration: '2015-10-12 00:00:00',
					file_source: '123',
					file_target: '/folder',
					id: '20',
					item_source: '123',
					item_type: 'file',
					mail_send: '0',
					parent: '999',
					path: '/folder',
					permissions: '' + OC.PERMISSION_READ,
					share_type: '' + OC.Share.SHARE_TYPE_USER,
					share_with: 'user1',
					stime: '1403884258',
					storage: '1',
					token: 'tehtoken',
					uid_owner: 'root'
				}]
			});

			model.fetch();

			var shares = model.get('shares');
			expect(shares.length).toEqual(1);

			var share = shares[0];
			expect(share.id).toEqual(20);
			expect(share.file_source).toEqual(123);
			expect(share.file_target).toEqual('/folder');
			expect(share.item_source).toEqual(123);
			expect(share.item_type).toEqual('file');
			expect(share.displayname_owner).toEqual('root');
			expect(share.mail_send).toEqual(0);
			expect(share.parent).toEqual(999);
			expect(share.path).toEqual('/folder');
			expect(share.permissions).toEqual(OC.PERMISSION_READ);
			expect(share.share_type).toEqual(OC.Share.SHARE_TYPE_USER);
			expect(share.share_with).toEqual('user1');
			expect(share.stime).toEqual(1403884258);
			expect(share.expiration).toEqual('2015-10-12 00:00:00');
		});
	});
	describe('hasUserShares', function() {
		it('returns false when no user shares exist', function() {
			loadItemStub.yields({
				reshare: {},
				shares: []
			});

			model.fetch();

			expect(model.hasUserShares()).toEqual(false);
		});
		it('returns true when user shares exist on the current item', function() {
			loadItemStub.yields({
				reshare: {},
				shares: [{
					id: 1,
					share_type: OC.Share.SHARE_TYPE_USER,
					share_with: 'user1',
					item_source: '123'
				}]
			});

			model.fetch();

			expect(model.hasUserShares()).toEqual(true);
		});
		it('returns true when group shares exist on the current item', function() {
			loadItemStub.yields({
				reshare: {},
				shares: [{
					id: 1,
					share_type: OC.Share.SHARE_TYPE_GROUP,
					share_with: 'group1',
					item_source: '123'
				}]
			});

			model.fetch();

			expect(model.hasUserShares()).toEqual(true);
		});
		it('returns false when share exist on parent item', function() {
			loadItemStub.yields({
				reshare: {},
				shares: [{
					id: 1,
					share_type: OC.Share.SHARE_TYPE_GROUP,
					share_with: 'group1',
					item_source: '111'
				}]
			});

			model.fetch();

			expect(model.hasUserShares()).toEqual(false);
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
				expect(OC.Share.ShareItemModel.prototype._parseTime(value[0])).toEqual(value[1]);
			});

		});
	});

	describe('sendEmailPrivateLink', function() {
		it('succeeds', function() {
			loadItemStub.yields({
				shares: [{
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

			var res = model.sendEmailPrivateLink('foo@bar.com');

			expect(res.state()).toEqual('pending');
			expect(fakeServer.requests.length).toEqual(1);
			expect(fakeServer.requests[0].url).toEqual(OC.generateUrl('core/ajax/share.php'));
			expect(OC.parseQueryString(fakeServer.requests[0].requestBody)).toEqual(
				{
					action: 'email',
					toaddress: 'foo@bar.com',
					link: model.get('linkShare').link,
					itemType: 'file',
					itemSource: '123',
					file: 'shared_file_name.txt',
					expiration: ''
				}
			)

			fakeServer.requests[0].respond(
				200,
				{ 'Content-Type': 'application/json' },
				JSON.stringify({status: 'success'})
			);
			expect(res.state()).toEqual('resolved');
		});

		it('fails', function() {
			loadItemStub.yields({
				shares: [{
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

			var res = model.sendEmailPrivateLink('foo@bar.com');

			expect(res.state()).toEqual('pending');
			expect(fakeServer.requests.length).toEqual(1);
			expect(fakeServer.requests[0].url).toEqual(OC.generateUrl('core/ajax/share.php'));
			expect(OC.parseQueryString(fakeServer.requests[0].requestBody)).toEqual(
				{
					action: 'email',
					toaddress: 'foo@bar.com',
					link: model.get('linkShare').link,
					itemType: 'file',
					itemSource: '123',
					file: 'shared_file_name.txt',
					expiration: ''
				}
			)

			fakeServer.requests[0].respond(
				200,
				{ 'Content-Type': 'application/json' },
				JSON.stringify({data: {message: 'fail'}, status: 'error'})
			);
			expect(res.state()).toEqual('rejected');
		});
	});
});

