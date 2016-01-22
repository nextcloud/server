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

/* global oc_appconfig */
describe('OC.Share.ShareDialogShareeListView', function () {

	var oldCurrentUser;
	var fileInfoModel;
	var configModel;
	var shareModel;
	var listView;
	var updateShareStub;

	beforeEach(function () {
		/* jshint camelcase:false */
		oldAppConfig = _.extend({}, oc_appconfig.core);
		oc_appconfig.core.enforcePasswordForPublicLink = false;

		$('#testArea').append('<input id="mailNotificationEnabled" name="mailNotificationEnabled" type="hidden" value="yes">');

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
			enforcePasswordForPublicLink: false,
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
			linkShare: {isLinkShare: false}
		});

		oldCurrentUser = OC.currentUser;
		OC.currentUser = 'user0';
		updateShareStub = sinon.stub(OC.Share.ShareItemModel.prototype, 'updateShare');
	});

	afterEach(function () {
		OC.currentUser = oldCurrentUser;
		/* jshint camelcase:false */
		oc_appconfig.core = oldAppConfig;
		listView.remove();
		updateShareStub.restore();
	});

	describe('Manages checkbox events correctly', function () {
		it('Checks cruds boxes when edit box checked', function () {
			shareModel.set('shares', [{
				id: 100,
				item_source: 123,
				permissions: 1,
				share_type: OC.Share.SHARE_TYPE_USER,
				share_with: 'user1',
				share_with_displayname: 'User One'
			}]);
			listView.render();
			listView.$el.find("input[name='edit']").click();
			expect(listView.$el.find("input[name='update']").is(':checked')).toEqual(true);
			expect(updateShareStub.calledOnce).toEqual(true);
		});

		it('Checks edit box when create/update/delete are checked', function () {
			shareModel.set('shares', [{
				id: 100,
				item_source: 123,
				permissions: 1,
				share_type: OC.Share.SHARE_TYPE_USER,
				share_with: 'user1',
				share_with_displayname: 'User One'
			}]);
			listView.render();
			listView.$el.find("input[name='update']").click();
			expect(listView.$el.find("input[name='edit']").is(':checked')).toEqual(true);
			expect(updateShareStub.calledOnce).toEqual(true);
		});

		it('shows cruds checkboxes when toggled', function () {
			shareModel.set('shares', [{
				id: 100,
				item_source: 123,
				permissions: 1,
				share_type: OC.Share.SHARE_TYPE_USER,
				share_with: 'user1',
				share_with_displayname: 'User One'
			}]);
			listView.render();
			listView.$el.find('a.showCruds').click();
			expect(listView.$el.find('li.cruds').hasClass('hidden')).toEqual(false);
		});

		it('sends notification to user when checkbox clicked', function () {
			shareModel.set('shares', [{
				id: 100,
				item_source: 123,
				permissions: 1,
				share_type: OC.Share.SHARE_TYPE_USER,
				share_with: 'user1',
				share_with_displayname: 'User One'
			}]);
			listView.render();
			var notificationStub = sinon.stub(listView.model, 'sendNotificationForShare');
			listView.$el.find("input[name='mailNotification']").click();
			expect(notificationStub.called).toEqual(true);
			notificationStub.restore();
		});

	});

});
