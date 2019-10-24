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

/* global OC */

describe('OC.Share.ShareItemModel', function() {
	var fetchSharesStub, fetchReshareStub;
	var fetchSharesDeferred, fetchReshareDeferred;
	var fileInfoModel, configModel, model;
	var oldCurrentUser;
	var capsSpec;

	beforeEach(function() {
		oldCurrentUser = OC.currentUser;

		fetchSharesDeferred = new $.Deferred();
		fetchSharesStub = sinon.stub(OC.Share.ShareItemModel.prototype, '_fetchShares')
			.returns(fetchSharesDeferred.promise());
		fetchReshareDeferred = new $.Deferred();
		fetchReshareStub = sinon.stub(OC.Share.ShareItemModel.prototype, '_fetchReshare')
			.returns(fetchReshareDeferred.promise());

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
		capsSpec = sinon.stub(OC, 'getCapabilities');
		capsSpec.returns({
			'files_sharing': {
				'default_permissions': OC.PERMISSION_ALL
			}
		});
	});
	afterEach(function() {
		capsSpec.restore(); 
		if (fetchSharesStub) {
			fetchSharesStub.restore();
		}
		if (fetchReshareStub) {
			fetchReshareStub.restore();
		}
		OC.currentUser = oldCurrentUser;
	});

	function makeOcsResponse(data) {
		return [{
			ocs: {
				data: data
			}
		}];
	}

	describe('Fetching and parsing', function() {
		it('fetches both outgoing shares and the current incoming share', function() {
			model.fetch();

			expect(fetchSharesStub.calledOnce).toEqual(true);
			expect(fetchReshareStub.calledOnce).toEqual(true);
		});
		it('fetches shares for the current path', function() {
			fetchSharesStub.restore();

			model._fetchShares();

			expect(fakeServer.requests.length).toEqual(1);
			expect(fakeServer.requests[0].method).toEqual('GET');
			expect(fakeServer.requests[0].url).toEqual(
				OC.linkToOCS('apps/files_sharing/api/v1', 2) +
				'shares?format=json&path=%2Fsubdir%2Fshared_file_name.txt&reshares=true'
			);

			fetchSharesStub = null;
		});
		it('fetches reshare for the current path', function() {
			fetchReshareStub.restore();

			model._fetchReshare();

			expect(fakeServer.requests.length).toEqual(1);
			expect(fakeServer.requests[0].method).toEqual('GET');
			expect(fakeServer.requests[0].url).toEqual(
				OC.linkToOCS('apps/files_sharing/api/v1', 2) +
				'shares?format=json&path=%2Fsubdir%2Fshared_file_name.txt&shared_with_me=true'
			);

			fetchReshareStub = null;
		});
		it('populates attributes with parsed response', function() {
			/* jshint camelcase: false */
			fetchReshareDeferred.resolve(makeOcsResponse([
				{
					share_type: OC.Share.SHARE_TYPE_USER,
					uid_owner: 'owner',
					displayname_owner: 'Owner',
					permissions: 31
				}
			]));
			fetchSharesDeferred.resolve(makeOcsResponse([
				{
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
					uid_owner: 'root',
					hide_download: 1,
					send_password_by_talk: true
				}
			]));

			OC.currentUser = 'root';

			model.fetch();

			var shares = model.get('shares');
			expect(shares.length).toEqual(3);
			expect(shares[0].id).toEqual(100);
			expect(shares[0].permissions).toEqual(31);
			expect(shares[0].share_type).toEqual(OC.Share.SHARE_TYPE_USER);
			expect(shares[0].share_with).toEqual('user1');
			expect(shares[0].share_with_displayname).toEqual('User One');

			var linkShares = model.get('linkShares');
			expect(linkShares.length).toEqual(1);
			var linkShare = linkShares[0];
			expect(linkShare.hideDownload).toEqual(true);
			expect(linkShare.sendPasswordByTalk).toEqual(true);

			// TODO: check more attributes
		});
		it('groups reshare info into a single item', function() {
			/* jshint camelcase: false */
			fetchReshareDeferred.resolve(makeOcsResponse([
				{
					id: '1',
					share_type: OC.Share.SHARE_TYPE_USER,
					uid_owner: 'owner',
					displayname_owner: 'Owner',
					share_with: 'root',
					share_with_displayname: 'Wurzel',
					permissions: 1
				},
				{
					id: '2',
					share_type: OC.Share.SHARE_TYPE_GROUP,
					uid_owner: 'owner',
					displayname_owner: 'Owner',
					share_with: 'group1',
					permissions: 15
				},
				{
					id: '3',
					share_type: OC.Share.SHARE_TYPE_GROUP,
					uid_owner: 'owner',
					displayname_owner: 'Owner',
					share_with: 'group1',
					permissions: 17
				}
			]));
			fetchSharesDeferred.resolve(makeOcsResponse([]));

			OC.currentUser = 'root';

			model.fetch();

			var reshare = model.get('reshare');
			// max permissions
			expect(reshare.permissions).toEqual(31);
			// user share has higher priority
			expect(reshare.share_type).toEqual(OC.Share.SHARE_TYPE_USER);
			expect(reshare.share_with).toEqual('root');
			expect(reshare.share_with_displayname).toEqual('Wurzel');
			expect(reshare.id).toEqual('1');

			expect(model.getReshareWith()).toEqual('root');
			expect(model.getReshareWithDisplayName()).toEqual('Wurzel');
		});
		it('does not parse link share when for a different file', function() {
			/* jshint camelcase: false */
			fetchReshareDeferred.resolve(makeOcsResponse([]));
			fetchSharesDeferred.resolve(makeOcsResponse([{
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
			));

			model.fetch();

			var shares = model.get('shares');
			// remaining share appears in this list
			expect(shares.length).toEqual(1);

			var linkShares = model.get('linkShares');
			expect(linkShares.length).toEqual(0);
		});
		it('parses correct link share when a nested link share exists along with parent one', function() {
			/* jshint camelcase: false */
			fetchReshareDeferred.resolve(makeOcsResponse([]));
			fetchSharesDeferred.resolve(makeOcsResponse([{
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
					uid_owner: 'root',
					hide_download: 0,
					send_password_by_talk: false
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
					uid_owner: 'root',
					hide_download: 1,
					send_password_by_talk: true
				}]
			));
			OC.currentUser = 'root';
			model.fetch();

			var shares = model.get('shares');
			// the parent share remains in the list
			expect(shares.length).toEqual(1);

			var linkShares = model.get('linkShares');
			expect(linkShares.length).toEqual(1);
			var linkShare = linkShares[0];
			expect(linkShare.token).toEqual('tehtoken');
			expect(linkShare.hideDownload).toEqual(false);
			expect(linkShare.sendPasswordByTalk).toEqual(false);

			// TODO: check child too
		});
		it('reduces reshare permissions to the ones from the original share', function() {
			/* jshint camelcase: false */
			fetchReshareDeferred.resolve(makeOcsResponse([{
				id: 123,
				permissions: OC.PERMISSION_READ,
				uid_owner: 'user1'
			}]));
			fetchSharesDeferred.resolve(makeOcsResponse([]));
			model.fetch();

			// no resharing allowed
			expect(model.get('permissions')).toEqual(OC.PERMISSION_READ);
		});
		it('reduces reshare permissions to possible permissions', function() {
			/* jshint camelcase: false */
			fetchReshareDeferred.resolve(makeOcsResponse([{
				id: 123,
				permissions: OC.PERMISSION_ALL,
				uid_owner: 'user1'
			}]));
			fetchSharesDeferred.resolve(makeOcsResponse([]));

			model.fileInfoModel.set('permissions', OC.PERMISSION_READ);
			model.fetch();

			// no resharing allowed
			expect(model.get('permissions')).toEqual(OC.PERMISSION_READ);
		});
		it('allows owner to share their own share when they are also the recipient', function() {
			OC.currentUser = 'user1';
			fetchReshareDeferred.resolve(makeOcsResponse([]));
			fetchSharesDeferred.resolve(makeOcsResponse([]));

			model.fetch();

			// sharing still allowed
			expect(model.get('permissions') & OC.PERMISSION_SHARE).toEqual(OC.PERMISSION_SHARE);
		});
		it('properly parses integer values when the server is in the mood of returning ints as string', function() {
			/* jshint camelcase: false */
			fetchReshareDeferred.resolve(makeOcsResponse([]));
			fetchSharesDeferred.resolve(makeOcsResponse([{
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
			));

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
			fetchReshareDeferred.resolve(makeOcsResponse([]));
			fetchSharesDeferred.resolve(makeOcsResponse([]));

			model.fetch();

			expect(model.hasUserShares()).toEqual(false);
		});
		it('returns true when user shares exist on the current item', function() {
			/* jshint camelcase: false */
			fetchReshareDeferred.resolve(makeOcsResponse([]));
			fetchSharesDeferred.resolve(makeOcsResponse([{
				id: 1,
				share_type: OC.Share.SHARE_TYPE_USER,
				share_with: 'user1',
				item_source: '123'
			}]));

			model.fetch();

			expect(model.hasUserShares()).toEqual(true);
		});
		it('returns true when group shares exist on the current item', function() {
			/* jshint camelcase: false */
			fetchReshareDeferred.resolve(makeOcsResponse([]));
			fetchSharesDeferred.resolve(makeOcsResponse([{
				id: 1,
				share_type: OC.Share.SHARE_TYPE_GROUP,
				share_with: 'group1',
				item_source: '123'
			}]));

			model.fetch();

			expect(model.hasUserShares()).toEqual(true);
		});
		it('returns false when share exist on parent item', function() {
			/* jshint camelcase: false */
			fetchReshareDeferred.resolve(makeOcsResponse([]));
			fetchSharesDeferred.resolve(makeOcsResponse([{
				id: 1,
				share_type: OC.Share.SHARE_TYPE_GROUP,
				share_with: 'group1',
				item_source: '111'
			}]));

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

	
	describe('share permissions', function() {
		beforeEach(function() {
			OC.appConfig.core.resharingAllowed = true;
		});

		/**
		 * Tests sharing with the given possible permissions
		 *
		 * @param {int} possiblePermissions
		 * @return {int} permissions sent to the server
		 */
		function testWithPermissions(possiblePermissions) {
			model.set({
				permissions: possiblePermissions,
				possiblePermissions: possiblePermissions
			});
			model.addShare({
				shareType: OC.Share.SHARE_TYPE_USER,
				shareWith: 'user2'
			});

			var requestBody = OC.parseQueryString(_.last(fakeServer.requests).requestBody);
			return parseInt(requestBody.permissions, 10);
		}

		describe('regular sharing', function() {
			it('shares with given permissions with default config', function() {
				configModel.set('isResharingAllowed', true);
				model.set({
					reshare: {},
					shares: []
				});
				expect(
					testWithPermissions(OC.PERMISSION_READ | OC.PERMISSION_UPDATE | OC.PERMISSION_SHARE)
				).toEqual(OC.PERMISSION_READ | OC.PERMISSION_UPDATE | OC.PERMISSION_SHARE);
				expect(
					testWithPermissions(OC.PERMISSION_READ | OC.PERMISSION_SHARE)
				).toEqual(OC.PERMISSION_READ | OC.PERMISSION_SHARE);
			});
			it('removes share permission when not allowed', function() {
				configModel.set('isResharingAllowed', false);
				model.set({
					reshare: {},
					shares: []
				});
				expect(
					testWithPermissions(OC.PERMISSION_READ | OC.PERMISSION_UPDATE | OC.PERMISSION_SHARE)
				).toEqual(OC.PERMISSION_READ | OC.PERMISSION_UPDATE);
			});
			it('automatically adds READ permission even when not specified', function() {
				configModel.set('isResharingAllowed', false);
				model.set({
					reshare: {},
					shares: []
				});
				expect(
					testWithPermissions(OC.PERMISSION_UPDATE | OC.PERMISSION_SHARE)
				).toEqual(OC.PERMISSION_READ | OC.PERMISSION_UPDATE);
			});
			it('uses default permissions from capabilities', function() {
				capsSpec.returns({
					'files_sharing': {
						'default_permissions': OC.PERMISSION_READ | OC.PERMISSION_CREATE | OC.PERMISSION_SHARE
					}
				});
				configModel.set('isResharingAllowed', true);
				model.set({
					reshare: {},
					shares: []
				});
				expect(
					testWithPermissions(OC.PERMISSION_ALL)
				).toEqual(OC.PERMISSION_READ | OC.PERMISSION_CREATE | OC.PERMISSION_SHARE);
			});
		});
	});

	describe('saveLinkShare', function() {
		var addShareStub;
		var updateShareStub;

		beforeEach(function() {
			addShareStub = sinon.stub(model, 'addShare');
			updateShareStub = sinon.stub(model, 'updateShare');
		});
		afterEach(function() {
			addShareStub.restore();
			updateShareStub.restore();
		});

		it('creates a new share if no link share exists', function() {
			model.set({
				linkShares: [
				]
			});

			model.saveLinkShare();

			expect(addShareStub.calledOnce).toEqual(true);
			expect(addShareStub.firstCall.args[0]).toEqual({
				hideDownload: false,
				password: '',
				passwordChanged: false,
				sendPasswordByTalk: false,
				permissions: OC.PERMISSION_READ,
				expireDate: '',
				shareType: OC.Share.SHARE_TYPE_LINK
			});
			expect(updateShareStub.notCalled).toEqual(true);
		});
		it('creates a new share with default expiration date', function() {
			var clock = sinon.useFakeTimers(Date.UTC(2015, 6, 17, 1, 2, 0, 3));
			configModel.set({
				isDefaultExpireDateEnabled: true,
				defaultExpireDate: 7
			});
			model.set({
				linkShares: [
				]
			});

			model.saveLinkShare();

			expect(addShareStub.calledOnce).toEqual(true);
			expect(addShareStub.firstCall.args[0]).toEqual({
				hideDownload: false,
				password: '',
				passwordChanged: false,
				sendPasswordByTalk: false,
				permissions: OC.PERMISSION_READ,
			expireDate: '2015-07-24 00:00:00',
			shareType: OC.Share.SHARE_TYPE_LINK
			});
			expect(updateShareStub.notCalled).toEqual(true);
			clock.restore();
		});
		it('updates link share if it exists', function() {
			model.set({
				linkShares: [{
					id: 123
				}]
			});

			model.saveLinkShare({
				cid: 123,
				password: 'test'
			});

			expect(addShareStub.notCalled).toEqual(true);
			expect(updateShareStub.calledOnce).toEqual(true);
			expect(updateShareStub.firstCall.args[0]).toEqual(123);
			expect(updateShareStub.firstCall.args[1]).toEqual({
				cid: 123,
				password: 'test'
			});
		});
		it('forwards error message on add', function() {
			var errorStub = sinon.stub();
			model.set({
				linkShares: [
				]
			}, {
			});

			model.saveLinkShare({
			}, {
				error: errorStub
			});

			addShareStub.yieldTo('error', 'Some error message');

			expect(errorStub.calledOnce).toEqual(true);
			expect(errorStub.lastCall.args[0]).toEqual('Some error message');
		});
		it('forwards error message on update', function() {
			var errorStub = sinon.stub();
			model.set({
				linkShares: [{
					id: 123
				}]
			}, {
			});

			model.saveLinkShare({
				cid: 123,
				password: 'test'
			}, {
				error: errorStub
			});

			updateShareStub.yieldTo('error', 'Some error message');

			expect(errorStub.calledOnce).toEqual(true);
			expect(errorStub.lastCall.args[0]).toEqual('Some error message');
		});
	});
	describe('creating shares', function() {
		it('sends POST method to endpoint with passed values', function() {
			model.addShare({
				shareType: OC.Share.SHARE_TYPE_GROUP,
				shareWith: 'group1'
			});

			expect(fakeServer.requests.length).toEqual(1);
			expect(fakeServer.requests[0].method).toEqual('POST');
			expect(fakeServer.requests[0].url).toEqual(
				OC.linkToOCS('apps/files_sharing/api/v1', 2) +
				'shares?format=json'
			);
			expect(OC.parseQueryString(fakeServer.requests[0].requestBody)).toEqual({
				path: '/subdir/shared_file_name.txt',
				permissions: '' + OC.PERMISSION_READ,
				shareType: '' + OC.Share.SHARE_TYPE_GROUP,
				shareWith: 'group1'
			});
		});
		it('calls complete handler before refreshing the model', function() {
			var completeStub = sinon.stub();
			model.addShare({
				shareType: OC.Share.SHARE_TYPE_GROUP,
				shareWith: 'group1'
			}, {
				complete: completeStub
			});

			expect(fakeServer.requests.length).toEqual(1);
			fakeServer.requests[0].respond(
				200,
				{ 'Content-Type': 'application/json' },
				JSON.stringify({ })
			);

			expect(completeStub.calledOnce).toEqual(true);
			expect(completeStub.lastCall.args[0]).toEqual(model);

			fetchReshareDeferred.resolve(makeOcsResponse([]));
			fetchSharesDeferred.resolve(makeOcsResponse([]));

			expect(completeStub.calledOnce).toEqual(true);
		});
		it('calls success handler after refreshing the model', function() {
			var successStub = sinon.stub();
			model.addShare({
				shareType: OC.Share.SHARE_TYPE_GROUP,
				shareWith: 'group1'
			}, {
				success: successStub
			});

			expect(fakeServer.requests.length).toEqual(1);
			fakeServer.requests[0].respond(
				200,
				{ 'Content-Type': 'application/json' },
				JSON.stringify({ })
			);

			expect(successStub.called).toEqual(false);

			fetchReshareDeferred.resolve(makeOcsResponse([]));
			fetchSharesDeferred.resolve(makeOcsResponse([]));

			expect(successStub.calledOnce).toEqual(true);
			expect(successStub.lastCall.args[0]).toEqual(model);
		});
		it('calls complete handler before error handler', function() {
			var completeStub = sinon.stub();
			var errorStub = sinon.stub();
			model.addShare({
				shareType: OC.Share.SHARE_TYPE_GROUP,
				shareWith: 'group1'
			}, {
				complete: completeStub,
				error: errorStub
			});

			expect(fakeServer.requests.length).toEqual(1);
			fakeServer.requests[0].respond(
				400,
				{ 'Content-Type': 'application/json' },
				JSON.stringify({
					ocs: {
						meta: {
							message: 'Some error message'
						}
					}
				})
			);

			expect(completeStub.calledOnce).toEqual(true);
			expect(completeStub.lastCall.args[0]).toEqual(model);
			expect(errorStub.calledOnce).toEqual(true);
			expect(completeStub.calledBefore(errorStub)).toEqual(true);
		});
		it('calls error handler with error message', function() {
			var errorStub = sinon.stub();
			model.addShare({
				shareType: OC.Share.SHARE_TYPE_GROUP,
				shareWith: 'group1'
			}, {
				error: errorStub
			});

			expect(fakeServer.requests.length).toEqual(1);
			fakeServer.requests[0].respond(
				400,
				{ 'Content-Type': 'application/json' },
				JSON.stringify({
					ocs: {
						meta: {
							message: 'Some error message'
						}
					}
				})
			);

			expect(errorStub.calledOnce).toEqual(true);
			expect(errorStub.lastCall.args[0]).toEqual(model);
			expect(errorStub.lastCall.args[1]).toEqual('Some error message');
		});
	});
	describe('updating shares', function() {
		it('sends PUT method to endpoint with passed values', function() {
			model.updateShare(123, {
				permissions: OC.PERMISSION_READ | OC.PERMISSION_SHARE
			});

			expect(fakeServer.requests.length).toEqual(1);
			expect(fakeServer.requests[0].method).toEqual('PUT');
			expect(fakeServer.requests[0].url).toEqual(
				OC.linkToOCS('apps/files_sharing/api/v1', 2) +
				'shares/123?format=json'
			);
			expect(OC.parseQueryString(fakeServer.requests[0].requestBody)).toEqual({
				permissions: '' + (OC.PERMISSION_READ | OC.PERMISSION_SHARE)
			});
		});
		it('calls complete handler before refreshing the model', function() {
			var completeStub = sinon.stub();
			model.updateShare(123, {
				permissions: OC.PERMISSION_READ | OC.PERMISSION_SHARE
			}, {
				complete: completeStub
			});

			expect(fakeServer.requests.length).toEqual(1);
			fakeServer.requests[0].respond(
				200,
				{ 'Content-Type': 'application/json' },
				JSON.stringify({ })
			);

			expect(completeStub.calledOnce).toEqual(true);
			expect(completeStub.lastCall.args[0]).toEqual(model);

			fetchReshareDeferred.resolve(makeOcsResponse([]));
			fetchSharesDeferred.resolve(makeOcsResponse([]));

			expect(completeStub.calledOnce).toEqual(true);
		});
		it('calls success handler after refreshing the model', function() {
			var successStub = sinon.stub();
			model.updateShare(123, {
				permissions: OC.PERMISSION_READ | OC.PERMISSION_SHARE
			}, {
				success: successStub
			});

			expect(fakeServer.requests.length).toEqual(1);
			fakeServer.requests[0].respond(
				200,
				{ 'Content-Type': 'application/json' },
				JSON.stringify({ })
			);

			expect(successStub.called).toEqual(false);

			fetchReshareDeferred.resolve(makeOcsResponse([]));
			fetchSharesDeferred.resolve(makeOcsResponse([]));

			expect(successStub.calledOnce).toEqual(true);
			expect(successStub.lastCall.args[0]).toEqual(model);
		});
		it('calls complete handler before error handler', function() {
			var completeStub = sinon.stub();
			var errorStub = sinon.stub();
			model.updateShare(123, {
				permissions: OC.PERMISSION_READ | OC.PERMISSION_SHARE
			}, {
				complete: completeStub,
				error: errorStub
			});

			expect(fakeServer.requests.length).toEqual(1);
			fakeServer.requests[0].respond(
				400,
				{ 'Content-Type': 'application/json' },
				JSON.stringify({
					ocs: {
						meta: {
							message: 'Some error message'
						}
					}
				})
			);

			expect(completeStub.calledOnce).toEqual(true);
			expect(completeStub.lastCall.args[0]).toEqual(model);
			expect(errorStub.calledOnce).toEqual(true);
			expect(completeStub.calledBefore(errorStub)).toEqual(true);
		});
		it('calls error handler with error message', function() {
			var errorStub = sinon.stub();
			model.updateShare(123, {
				permissions: OC.PERMISSION_READ | OC.PERMISSION_SHARE
			}, {
				error: errorStub
			});

			expect(fakeServer.requests.length).toEqual(1);
			fakeServer.requests[0].respond(
				400,
				{ 'Content-Type': 'application/json' },
				JSON.stringify({
					ocs: {
						meta: {
							message: 'Some error message'
						}
					}
				})
			);

			expect(errorStub.calledOnce).toEqual(true);
			expect(errorStub.lastCall.args[0]).toEqual(model);
			expect(errorStub.lastCall.args[1]).toEqual('Some error message');
		});
	});
	describe('removing shares', function() {
		it('sends DELETE method to endpoint with share id', function() {
			model.removeShare(123);

			expect(fakeServer.requests.length).toEqual(1);
			expect(fakeServer.requests[0].method).toEqual('DELETE');
			expect(fakeServer.requests[0].url).toEqual(
				OC.linkToOCS('apps/files_sharing/api/v1', 2) +
				'shares/123?format=json'
			);
		});
		it('calls error handler with error message', function() {
			var errorStub = sinon.stub();
			model.removeShare(123, {
				error: errorStub
			});

			expect(fakeServer.requests.length).toEqual(1);
			fakeServer.requests[0].respond(
				400,
				{ 'Content-Type': 'application/json' },
				JSON.stringify({
					ocs: {
						meta: {
							message: 'Some error message'
						}
					}
				})
			);

			expect(errorStub.calledOnce).toEqual(true);
			expect(errorStub.lastCall.args[1]).toEqual('Some error message');
		});
	});

	describe('getShareTypes', function() {

		var dataProvider = [
			[
			],
			[
				OC.Share.SHARE_TYPE_USER,
				OC.Share.SHARE_TYPE_USER,
			],
			[
				OC.Share.SHARE_TYPE_USER,
				OC.Share.SHARE_TYPE_GROUP,
				OC.Share.SHARE_TYPE_LINK,
				OC.Share.SHARE_TYPE_REMOTE
			],
			[
				OC.Share.SHARE_TYPE_USER,
				OC.Share.SHARE_TYPE_GROUP,
				OC.Share.SHARE_TYPE_GROUP,
				OC.Share.SHARE_TYPE_LINK,
				OC.Share.SHARE_TYPE_LINK,
				OC.Share.SHARE_TYPE_REMOTE,
				OC.Share.SHARE_TYPE_REMOTE,
				OC.Share.SHARE_TYPE_REMOTE
			],
			[
				OC.Share.SHARE_TYPE_LINK,
				OC.Share.SHARE_TYPE_LINK,
				OC.Share.SHARE_TYPE_USER
			]
		];

		_.each(dataProvider, function testCase(shareTypes, i) {
			it('returns set of share types for case ' + i, function() {
				/* jshint camelcase: false */
				fetchReshareDeferred.resolve(makeOcsResponse([]));

				var id = 100;
				var shares = _.map(shareTypes, function(shareType) {
					return {
						id: id++,
						item_source: 123,
						permissions: 31,
						share_type: shareType,
						uid_owner: 'root'
					};
				});

				var expectedResult = _.uniq(shareTypes).sort();

				fetchSharesDeferred.resolve(makeOcsResponse(shares));

				OC.currentUser = 'root';

				model.fetch();

				expect(model.getShareTypes().sort()).toEqual(expectedResult);
			});
		});
	});
});

