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

	describe('onPasswordEntered', function () {

		var $passwordText;
		var $workingIcon;

		beforeEach(function () {

			// Needed to render the view
			configModel.isShareWithLinkAllowed.returns(true);

			// Setting the share also triggers the rendering
			shareModel.set({
				linkShare: {
					isLinkShare: true,
					password: 'password'
				}
			});

			var $passwordDiv = view.$el.find('#linkPass');
			$passwordText = view.$el.find('.linkPassText');
			$workingIcon = view.$el.find('.linkPass .icon-loading-small');

			sinon.stub(shareModel, 'saveLinkShare');

			expect($passwordDiv.hasClass('hidden')).toBeFalsy();
			expect($passwordText.hasClass('hidden')).toBeFalsy();
			expect($workingIcon.hasClass('hidden')).toBeTruthy();

			$passwordText.val('myPassword');
		});

		afterEach(function () {
			shareModel.saveLinkShare.restore();
		});

		it('shows the working icon when called', function () {
			view.onPasswordEntered();

			expect($workingIcon.hasClass('hidden')).toBeFalsy();
			expect(shareModel.saveLinkShare.withArgs({ password: 'myPassword' }).calledOnce).toBeTruthy();
		});

		it('hides the working icon when saving the password succeeds', function () {
			view.onPasswordEntered();

			expect($workingIcon.hasClass('hidden')).toBeFalsy();
			expect(shareModel.saveLinkShare.withArgs({ password: 'myPassword' }).calledOnce).toBeTruthy();

			shareModel.saveLinkShare.yieldTo("complete", [shareModel]);

			expect($workingIcon.hasClass('hidden')).toBeTruthy();
		});

		it('hides the working icon when saving the password fails', function () {
			view.onPasswordEntered();

			expect($workingIcon.hasClass('hidden')).toBeFalsy();
			expect(shareModel.saveLinkShare.withArgs({ password: 'myPassword' }).calledOnce).toBeTruthy();

			shareModel.saveLinkShare.yieldTo("complete", [shareModel]);
			shareModel.saveLinkShare.yieldTo("error", [shareModel, "The error message"]);

			expect($workingIcon.hasClass('hidden')).toBeTruthy();
		});

	});

});
