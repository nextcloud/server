/**
 * ownCloud
 *
 * @author Tom Needham
 * @copyright 2015 Tom Needham <tom@owncloud.com>
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

describe('OC.Share.ShareDialogShareeListView', function () {

	var oldCurrentUser;
	var fileInfoModel;
	var configModel;
	var shareModel;
	var listView;
	var updateShareStub;

	beforeEach(function () {
		/* jshint camelcase:false */
		oldAppConfig = _.extend({}, OC.appConfig.core);
		OC.appConfig.core.enforcePasswordForPublicLink = false;

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
			possiblePermissions: 31,
			permissions: 31
		};

		shareModel = new OC.Share.ShareItemModel(attributes, {
			configModel: configModel,
			fileInfoModel: fileInfoModel
		});

		configModel = new OC.Share.ShareConfigModel({
			enforcePasswordForPublicLink: false,
			isResharingAllowed: true,
			isDefaultExpireDateEnabled: false,
			isDefaultExpireDateEnforced: false,
			defaultExpireDate: 7
		});

		listView = new OC.Share.ShareDialogShareeListView({
			configModel: configModel,
			model: shareModel
		});

		// required for proper event propagation when simulating clicks in some cases (jquery bugs)
		$('#testArea').append(listView.$el);

		shareModel.set({
			linkShares: []
		});

		oldCurrentUser = OC.currentUser;
		OC.currentUser = 'user0';
		updateShareStub = sinon.stub(OC.Share.ShareItemModel.prototype, 'updateShare');
	});

	afterEach(function () {
		OC.currentUser = oldCurrentUser;
		/* jshint camelcase:false */
		OC.appConfig.core = oldAppConfig;
		listView.remove();
		updateShareStub.restore();
	});

	describe('Sets correct initial checkbox state', function () {

		it('marks edit box as unchecked for file shares without edit permissions', function () {
			shareModel.set('shares', [{
				id: 100,
				item_source: 123,
				permissions: 1,
				share_type: OC.Share.SHARE_TYPE_USER,
				share_with: 'user1',
				share_with_displayname: 'User One',
				uid_owner: OC.getCurrentUser().uid,
				itemType: 'file'
			}]);
			listView.render();
			expect(listView.$el.find("input[name='edit']").is(':not(:checked)')).toEqual(true);
		});

		it('marks edit box as checked for file shares', function () {
			shareModel.set('shares', [{
				id: 100,
				item_source: 123,
				permissions: 1 | OC.PERMISSION_UPDATE,
				share_type: OC.Share.SHARE_TYPE_USER,
				share_with: 'user1',
				share_with_displayname: 'User One',
				uid_owner: OC.getCurrentUser().uid,
				itemType: 'file'
			}]);
			listView.render();
			expect(listView.$el.find("input[name='edit']").is(':checked')).toEqual(true);
		});

		it('marks edit box as indeterminate when only some permissions are given', function () {
			shareModel.set('shares', [{
				id: 100,
				item_source: 123,
				permissions: 1 | OC.PERMISSION_UPDATE,
				share_type: OC.Share.SHARE_TYPE_USER,
				share_with: 'user1',
				share_with_displayname: 'User One',
				uid_owner: OC.getCurrentUser().uid,
				itemType: 'folder'
			}]);
			shareModel.set('itemType', 'folder');
			listView.render();
			expect(listView.$el.find("input[name='edit']").is(':indeterminate')).toEqual(true);
		});

		it('marks edit box as indeterminate when only some permissions are given for sharee with special characters', function () {
			shareModel.set('shares', [{
				id: 100,
				item_source: 123,
				permissions: 1 | OC.PERMISSION_UPDATE,
				share_type: OC.Share.SHARE_TYPE_USER,
				share_with: 'user _.@-\'',
				share_with_displayname: 'User One',
				uid_owner: OC.getCurrentUser().uid,
				itemType: 'folder'
			}]);
			shareModel.set('itemType', 'folder');
			listView.render();
			expect(listView.$el.find("input[name='edit']").is(':indeterminate')).toEqual(true);
		});

		it('Checks edit box when all permissions are given', function () {
			shareModel.set('shares', [{
				id: 100,
				item_source: 123,
				permissions: 1 | OC.PERMISSION_CREATE | OC.PERMISSION_UPDATE | OC.PERMISSION_DELETE,
				share_type: OC.Share.SHARE_TYPE_USER,
				share_with: 'user1',
				share_with_displayname: 'User One',
				uid_owner: OC.getCurrentUser().uid,
				itemType: 'folder'
			}]);
			shareModel.set('itemType', 'folder');
			listView.render();
			expect(listView.$el.find("input[name='edit']").is(':checked')).toEqual(true);
		});

		it('Checks edit box when all permissions are given for sharee with special characters', function () {
			shareModel.set('shares', [{
				id: 100,
				item_source: 123,
				permissions: 1 | OC.PERMISSION_CREATE | OC.PERMISSION_UPDATE | OC.PERMISSION_DELETE,
				share_type: OC.Share.SHARE_TYPE_USER,
				share_with: 'user _.@-\'',
				share_with_displayname: 'User One',
				uid_owner: OC.getCurrentUser().uid,
				itemType: 'folder'
			}]);
			shareModel.set('itemType', 'folder');
			listView.render();
			expect(listView.$el.find("input[name='edit']").is(':checked')).toEqual(true);
		});
	});
	describe('Manages checkbox events correctly', function () {
		it('Checks cruds boxes when edit box checked', function () {
			shareModel.set('shares', [{
				id: 100,
				item_source: 123,
				permissions: 1,
				share_type: OC.Share.SHARE_TYPE_USER,
				share_with: 'user1',
				share_with_displayname: 'User One',
				uid_owner: OC.getCurrentUser().uid,
			}]);
			shareModel.set('itemType', 'folder');
			listView.render();
			listView.$el.find("input[name='edit']").click();
			expect(listView.$el.find("input[name='update']").is(':checked')).toEqual(true);
			expect(updateShareStub.calledOnce).toEqual(true);
		});

		it('marks edit box as indeterminate when some of create/update/delete are checked', function () {
			shareModel.set('shares', [{
				id: 100,
				item_source: 123,
				permissions: 1,
				share_type: OC.Share.SHARE_TYPE_USER,
				share_with: 'user1',
				share_with_displayname: 'User One',
				uid_owner: OC.getCurrentUser().uid,
				itemType: 'folder'
			}]);
			shareModel.set('itemType', 'folder');
			listView.render();
			listView.$el.find("input[name='update']").click();
			expect(listView.$el.find("input[name='edit']").is(':indeterminate')).toEqual(true);
			expect(updateShareStub.calledOnce).toEqual(true);
		});

		it('Checks edit box when all of create/update/delete are checked', function () {
			shareModel.set('shares', [{
				id: 100,
				item_source: 123,
				permissions: 1 | OC.PERMISSION_CREATE | OC.PERMISSION_DELETE,
				share_type: OC.Share.SHARE_TYPE_USER,
				share_with: 'user1',
				share_with_displayname: 'User One',
				uid_owner: OC.getCurrentUser().uid,
				itemType: 'folder'
			}]);
			shareModel.set('itemType', 'folder');
			listView.render();
			listView.$el.find("input[name='update']").click();
			expect(listView.$el.find("input[name='edit']").is(':checked')).toEqual(true);
			expect(updateShareStub.calledOnce).toEqual(true);
		});
	});

});
