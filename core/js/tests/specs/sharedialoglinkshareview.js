/**
 *
 * @copyright Copyright (c) 2015, Tom Needham (tom@owncloud.com)
 * @copyright Copyright (c) 2017, Daniel Calviño Sánchez (danxuliu@gmail.com)
 *
 * @license GNU AGPL version 3 or any later version
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

describe('OC.Share.ShareDialogLinkShareView', function () {

	var configModel;
	var shareModel;
	var view;

	beforeEach(function () {

		var fileInfoModel = new OCA.Files.FileInfoModel({
			id: 123,
			name: 'shared_file_name.txt',
			path: '/subdir',
			size: 100,
			mimetype: 'text/plain',
			permissions: OC.PERMISSION_ALL,
			sharePermissions: OC.PERMISSION_ALL
		});

		var attributes = {
			itemType: fileInfoModel.isDirectory() ? 'folder' : 'file',
			itemSource: fileInfoModel.get('id'),
			possiblePermissions: OC.PERMISSION_ALL,
			permissions: OC.PERMISSION_ALL
		};

		configModel = new OC.Share.ShareConfigModel({
			enforcePasswordForPublicLink: false,
			isResharingAllowed: true,
			isDefaultExpireDateEnabled: false,
			isDefaultExpireDateEnforced: false,
			defaultExpireDate: 7
		});

		sinon.stub(configModel, 'isShareWithLinkAllowed');

		shareModel = new OC.Share.ShareItemModel(attributes, {
			configModel: configModel,
			fileInfoModel: fileInfoModel
		});

		view = new OC.Share.ShareDialogLinkShareView({
			configModel: configModel,
			model: shareModel
		});

	});

	afterEach(function () {
		view.remove();
		configModel.isShareWithLinkAllowed.restore();
	});

	describe('hide download', function () {

		var $hideDownloadCheckbox;
		var $workingIcon;

		beforeEach(function () {
			// Needed to render the view
			configModel.isShareWithLinkAllowed.returns(true);

			shareModel.set({
				linkShares: [{
					id: 123
				}]
			});
			view.render();

			$hideDownloadCheckbox = view.$el.find('.hideDownloadCheckbox');
			$workingIcon = $hideDownloadCheckbox.prev('.icon-loading-small');

			sinon.stub(shareModel, 'saveLinkShare');

			expect($workingIcon.hasClass('hidden')).toBeTruthy();
		});

		afterEach(function () {
			shareModel.saveLinkShare.restore();
		});

		it('is shown if the share is a file', function() {
			expect($hideDownloadCheckbox.length).toBeTruthy();
		});

		it('is not shown if the share is a folder', function() {
			shareModel.fileInfoModel.set('mimetype', 'httpd/unix-directory');

			// Setting the item type also triggers the rendering
			shareModel.set({
				itemType: 'folder'
			});

			$hideDownloadCheckbox = view.$el.find('.hideDownloadCheckbox');

			expect($hideDownloadCheckbox.length).toBeTruthy();
		});

		it('checkbox is checked when the setting is enabled', function () {
			shareModel.set({
				linkShares: [{
					id: 123,
					hideDownload: true
				}]
			});
			view.render();

			$hideDownloadCheckbox = view.$el.find('.hideDownloadCheckbox');

			expect($hideDownloadCheckbox.is(':checked')).toEqual(true);
		});

		it('checkbox is not checked when the setting is disabled', function () {
			expect($hideDownloadCheckbox.is(':checked')).toEqual(false);
		});

		it('enables the setting if clicked when unchecked', function () {
			// Simulate the click by checking the checkbox and then triggering
			// the "change" event.
			$hideDownloadCheckbox.prop('checked', true);
			$hideDownloadCheckbox.change();

			expect($workingIcon.hasClass('hidden')).toBeFalsy();
			expect(shareModel.saveLinkShare.withArgs({ hideDownload: true, cid: 123 }).calledOnce).toBeTruthy();
		});

		it('disables the setting if clicked when checked', function () {
			shareModel.set({
				linkShares: [{
					id: 123,
					hideDownload: true
				}]
			});
			view.render();

			$hideDownloadCheckbox = view.$el.find('.hideDownloadCheckbox');
			$workingIcon = $hideDownloadCheckbox.prev('.icon-loading-small');

			// Simulate the click by unchecking the checkbox and then triggering
			// the "change" event.
			$hideDownloadCheckbox.prop('checked', false);
			$hideDownloadCheckbox.change();

			expect($workingIcon.hasClass('hidden')).toBeFalsy();
			expect(shareModel.saveLinkShare.withArgs({ hideDownload: false, cid: 123 }).calledOnce).toBeTruthy();
		});

	});

	describe('onPasswordEntered', function () {

		var $passwordText;
		var $workingIcon;

		beforeEach(function () {

			// Needed to render the view
			configModel.isShareWithLinkAllowed.returns(true);

			shareModel.set({
				linkShares: [{
					id: 123,
					password: 'password'
				}]
			});
			view.render();

			$passwordText = view.$el.find('.linkPassText');
			$workingIcon = view.$el.find('.linkPassMenu .icon-loading-small');

			sinon.stub(shareModel, 'saveLinkShare');

			expect($passwordText.hasClass('hidden')).toBeFalsy();
			expect($workingIcon.hasClass('hidden')).toBeTruthy();

			$passwordText.val('myPassword');
		});

		afterEach(function () {
			shareModel.saveLinkShare.restore();
		});

		it('shows the working icon when called', function () {
			view.onPasswordEntered({target: view.$el.find('.linkPassText')});

			expect($workingIcon.hasClass('hidden')).toBeFalsy();
			expect(shareModel.saveLinkShare.withArgs({ password: 'myPassword', cid: 123 }).calledOnce).toBeTruthy();
		});

		it('hides the working icon when saving the password succeeds', function () {
			view.onPasswordEntered({target: view.$el.find('.linkPassText')});

			expect($workingIcon.hasClass('hidden')).toBeFalsy();
			expect(shareModel.saveLinkShare.withArgs({ password: 'myPassword', cid: 123 }).calledOnce).toBeTruthy();

			shareModel.saveLinkShare.yieldTo("complete", [shareModel]);

			expect($workingIcon.hasClass('hidden')).toBeTruthy();
		});

		it('hides the working icon when saving the password fails', function () {
			view.onPasswordEntered({target: view.$el.find('.linkPassText')});

			expect($workingIcon.hasClass('hidden')).toBeFalsy();
			expect(shareModel.saveLinkShare.withArgs({ password: 'myPassword', cid: 123 }).calledOnce).toBeTruthy();

			shareModel.saveLinkShare.yieldTo("complete", [shareModel]);
			shareModel.saveLinkShare.yieldTo("error", [shareModel, "The error message"]);

			expect($workingIcon.hasClass('hidden')).toBeTruthy();
		});

	});

	describe('protect password by Talk', function () {

		var $passwordByTalkCheckbox;
		var $workingIcon;

		beforeEach(function () {
			// Needed to render the view
			configModel.isShareWithLinkAllowed.returns(true);

			// "Enable" Talk
			OC.appswebroots['spreed'] = OC.getRootPath() + '/apps/files/';

			shareModel.set({
				linkShares: [{
					id: 123,
					password: 'password'
				}]
			});
			view.render();

			$passwordByTalkCheckbox = view.$el.find('.passwordByTalkCheckbox');
			$workingIcon = $passwordByTalkCheckbox.prev('.icon-loading-small');

			sinon.stub(shareModel, 'saveLinkShare');

			expect($workingIcon.hasClass('hidden')).toBeTruthy();
		});

		afterEach(function () {
			shareModel.saveLinkShare.restore();
		});

		it('is shown if Talk is enabled and there is a password set', function() {
			expect($passwordByTalkCheckbox.length).toBeTruthy();
		});

		it('is not shown if Talk is enabled but there is no password set', function() {
			// Changing the password value also triggers the rendering
			shareModel.set({
				linkShares: [{
					id: 123
				}]
			});

			$passwordByTalkCheckbox = view.$el.find('.passwordByTalkCheckbox');

			expect($passwordByTalkCheckbox.length).toBeFalsy();
		});

		it('is not shown if there is a password set but Talk is not enabled', function() {
			// "Disable" Talk
			delete OC.appswebroots['spreed'];

			view.render();

			$passwordByTalkCheckbox = view.$el.find('.passwordByTalkCheckbox');

			expect($passwordByTalkCheckbox.length).toBeFalsy();
		});

		it('checkbox is checked when the setting is enabled', function () {
			shareModel.set({
				linkShares: [{
					id: 123,
					password: 'password',
					sendPasswordByTalk: true
				}]
			});
			view.render();

			$passwordByTalkCheckbox = view.$el.find('.passwordByTalkCheckbox');

			expect($passwordByTalkCheckbox.is(':checked')).toEqual(true);
		});

		it('checkbox is not checked when the setting is disabled', function () {
			expect($passwordByTalkCheckbox.is(':checked')).toEqual(false);
		});

		it('enables the setting if clicked when unchecked', function () {
			// Simulate the click by checking the checkbox and then triggering
			// the "change" event.
			$passwordByTalkCheckbox.prop('checked', true);
			$passwordByTalkCheckbox.change();

			expect($workingIcon.hasClass('hidden')).toBeFalsy();
			expect(shareModel.saveLinkShare.withArgs({ sendPasswordByTalk: true, cid: 123 }).calledOnce).toBeTruthy();
		});

		it('disables the setting if clicked when checked', function () {
			shareModel.set({
				linkShares: [{
					id: 123,
					password: 'password',
					sendPasswordByTalk: true
				}]
			});
			view.render();

			$passwordByTalkCheckbox = view.$el.find('.passwordByTalkCheckbox');
			$workingIcon = $passwordByTalkCheckbox.prev('.icon-loading-small');

			// Simulate the click by unchecking the checkbox and then triggering
			// the "change" event.
			$passwordByTalkCheckbox.prop('checked', false);
			$passwordByTalkCheckbox.change();

			expect($workingIcon.hasClass('hidden')).toBeFalsy();
			expect(shareModel.saveLinkShare.withArgs({ sendPasswordByTalk: false, cid: 123 }).calledOnce).toBeTruthy();
		});

	});

});
