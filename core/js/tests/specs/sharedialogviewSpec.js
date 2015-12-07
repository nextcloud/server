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
describe('OC.Share.ShareDialogView', function() {
	var $container;
	var oldAppConfig;
	var autocompleteStub;
	var oldEnableAvatars;
	var avatarStub;
	var placeholderStub;
	var oldCurrentUser;

	var fetchStub;

	var configModel;
	var shareModel;
	var fileInfoModel;
	var dialog;

	beforeEach(function() {
		// horrible parameters
		$('#testArea').append('<input id="allowShareWithLink" type="hidden" value="yes">');
		$('#testArea').append('<input id="mailPublicNotificationEnabled" name="mailPublicNotificationEnabled" type="hidden" value="yes">');
		$container = $('#shareContainer');
		/* jshint camelcase:false */
		oldAppConfig = _.extend({}, oc_appconfig.core);
		oc_appconfig.core.enforcePasswordForPublicLink = false;

		fetchStub = sinon.stub(OC.Share.ShareItemModel.prototype, 'fetch');

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
		configModel = new OC.Share.ShareConfigModel({
			enforcePasswordForPublicLink: false,
			isResharingAllowed: true,
			enforcePasswordForPublicLink: false,
			isDefaultExpireDateEnabled: false,
			isDefaultExpireDateEnforced: false,
			defaultExpireDate: 7
		});
		shareModel = new OC.Share.ShareItemModel(attributes, {
			configModel: configModel,
			fileInfoModel: fileInfoModel
		});
		dialog = new OC.Share.ShareDialogView({
			configModel: configModel,
			model: shareModel
		});

		// required for proper event propagation when simulating clicks in some cases (jquery bugs)
		$('#testArea').append(dialog.$el);

		// triggers rendering
		shareModel.set({
			shares: [],
			linkShare: {isLinkShare: false}
		});

		autocompleteStub = sinon.stub($.fn, 'autocomplete', function() {
			// dummy container with the expected attributes
			if (!$(this).length) {
				// simulate the real autocomplete that returns
				// nothing at all when no element is specified
				// (and potentially break stuff)
				return null;
			}
			var $el = $('<div></div>').data('ui-autocomplete', {});
			return $el;
		});

		oldEnableAvatars = oc_config.enable_avatars;
		oc_config.enable_avatars = false;
		avatarStub = sinon.stub($.fn, 'avatar');
		placeholderStub = sinon.stub($.fn, 'imageplaceholder');

		oldCurrentUser = OC.currentUser;
		OC.currentUser = 'user0';
	});
	afterEach(function() {
		OC.currentUser = oldCurrentUser;
		/* jshint camelcase:false */
		oc_appconfig.core = oldAppConfig;

		dialog.remove();
		fetchStub.restore();

		autocompleteStub.restore();
		avatarStub.restore();
		placeholderStub.restore();
		oc_config.enable_avatars = oldEnableAvatars;
	});
	describe('Share with link', function() {
		// TODO: test ajax calls
		// TODO: test password field visibility (whenever enforced or not)
		it('update password on focus out', function() {
			$('#allowShareWithLink').val('yes');

			dialog.render();

			// Toggle linkshare
			dialog.$el.find('.linkCheckbox').click();
			fakeServer.requests[0].respond(
				200,
				{ 'Content-Type': 'application/json' },
				JSON.stringify({data: {token: 'xyz'}, status: 'success'})
			);

			// Enable password, enter password and focusout
			dialog.$el.find('[name=showPassword]').click();
			dialog.$el.find('.linkPassText').focus();
			dialog.$el.find('.linkPassText').val('foo');
			dialog.$el.find('.linkPassText').focusout();

			expect(fakeServer.requests[1].method).toEqual('POST');
			var body = OC.parseQueryString(fakeServer.requests[1].requestBody);
			expect(body['shareWith[password]']).toEqual('foo');
			expect(body['shareWith[passwordChanged]']).toEqual('true');

			fetchStub.reset();

			// Set password response
			fakeServer.requests[1].respond(
				200,
				{ 'Content-Type': 'application/json' },
				JSON.stringify({data: {token: 'xyz'}, status: 'success'})
			);

			expect(fetchStub.calledOnce).toEqual(true);
			// fetching the model will rerender the view
			dialog.render();

			expect(dialog.$el.find('.linkPassText').val()).toEqual('');
			expect(dialog.$el.find('.linkPassText').attr('placeholder')).toEqual('**********');
		});
		it('update password on enter', function() {
			$('#allowShareWithLink').val('yes');

			dialog.render();

			// Toggle linkshare
			dialog.$el.find('.linkCheckbox').click();
			fakeServer.requests[0].respond(
				200,
				{ 'Content-Type': 'application/json' },
				JSON.stringify({data: {token: 'xyz'}, status: 'success'})
			);

			// Enable password and enter password
			dialog.$el.find('[name=showPassword]').click();
			dialog.$el.find('.linkPassText').focus();
			dialog.$el.find('.linkPassText').val('foo');
			dialog.$el.find('.linkPassText').trigger(new $.Event('keyup', {keyCode: 13}));

			expect(fakeServer.requests[1].method).toEqual('POST');
			var body = OC.parseQueryString(fakeServer.requests[1].requestBody);
			expect(body['shareWith[password]']).toEqual('foo');
			expect(body['shareWith[passwordChanged]']).toEqual('true');

			fetchStub.reset();

			// Set password response
			fakeServer.requests[1].respond(
				200,
				{ 'Content-Type': 'application/json' },
				JSON.stringify({data: {token: 'xyz'}, status: 'success'})
			);

			expect(fetchStub.calledOnce).toEqual(true);
			// fetching the model will rerender the view
			dialog.render();

			expect(dialog.$el.find('.linkPassText').val()).toEqual('');
			expect(dialog.$el.find('.linkPassText').attr('placeholder')).toEqual('**********');
		});
		it('shows share with link checkbox when allowed', function() {
			$('#allowShareWithLink').val('yes');

			dialog.render();

			expect(dialog.$el.find('.linkCheckbox').length).toEqual(1);
		});
		it('does not show share with link checkbox when not allowed', function() {
			$('#allowShareWithLink').val('no');

			dialog.render();

			expect(dialog.$el.find('.linkCheckbox').length).toEqual(0);
			expect(dialog.$el.find('.shareWithField').length).toEqual(1);
		});
		it('shows populated link share when a link share exists', function() {
			// this is how the OC.Share class does it...
			var link = parent.location.protocol + '//' + location.host +
				OC.generateUrl('/s/') + 'tehtoken';
			shareModel.set('linkShare', {
				isLinkShare: true,
				token: 'tehtoken',
				link: link,
				expiration: '',
				permissions: OC.PERMISSION_READ,
				stime: 1403884258,
			});

			dialog.render();

			expect(dialog.$el.find('.linkCheckbox').prop('checked')).toEqual(true);
			expect(dialog.$el.find('.linkText').val()).toEqual(link);
		});
		it('autofocus link text when clicked', function() {
			$('#allowShareWithLink').val('yes');

			dialog.render();

			// Toggle linkshare
			dialog.$el.find('.linkCheckbox').click();
			fakeServer.requests[0].respond(
				200,
				{ 'Content-Type': 'application/json' },
				JSON.stringify({data: {token: 'xyz'}, status: 'success'})
			);

			var focusStub = sinon.stub($.fn, 'focus');
			var selectStub = sinon.stub($.fn, 'select');
			dialog.$el.find('.linkText').click();

			expect(focusStub.calledOnce).toEqual(true);
			expect(selectStub.calledOnce).toEqual(true);

			focusStub.restore();
			selectStub.restore();
		});
		describe('password', function() {
			var slideToggleStub;

			beforeEach(function() {
				$('#allowShareWithLink').val('yes');
				configModel.set({
					enforcePasswordForPublicLink: false
				});

				slideToggleStub = sinon.stub($.fn, 'slideToggle');
			});
			afterEach(function() {
				slideToggleStub.restore();
			});

			it('enforced but toggled does not fire request', function() {
				configModel.set('enforcePasswordForPublicLink', true);
				dialog.render();

				dialog.$el.find('.linkCheckbox').click();

				// The password linkPass field is shown (slideToggle is called).
				// No request is made yet
				expect(slideToggleStub.callCount).toEqual(1);
				expect(slideToggleStub.getCall(0).thisValue.eq(0).attr('id')).toEqual('linkPass');
				expect(fakeServer.requests.length).toEqual(0);
				
				// Now untoggle share by link
				dialog.$el.find('.linkCheckbox').click();
				dialog.render();

				// Password field disappears and no ajax requests have been made
				expect(fakeServer.requests.length).toEqual(0);
				expect(slideToggleStub.callCount).toEqual(2);
				expect(slideToggleStub.getCall(1).thisValue.eq(0).attr('id')).toEqual('linkPass');
			});
		});
		describe('expiration date', function() {
			var shareData;
			var shareItem;
			var clock;
			var expectedMinDate;

			beforeEach(function() {
				// pick a fake date
				clock = sinon.useFakeTimers(new Date(2014, 0, 20, 14, 0, 0).getTime());
				expectedMinDate = new Date(2014, 0, 21, 14, 0, 0);

				configModel.set({
					enforcePasswordForPublicLink: false,
					isDefaultExpireDateEnabled: false,
					isDefaultExpireDateEnforced: false,
					defaultExpireDate: 7
				});

				shareModel.set('linkShare', {
					isLinkShare: true,
					token: 'tehtoken',
					permissions: OC.PERMISSION_READ,
					expiration: null
				});
			});
			afterEach(function() {
				clock.restore();
			});

			it('does not check expiration date checkbox when no date was set', function() {
				shareModel.get('linkShare').expiration = null;
				dialog.render();

				expect(dialog.$el.find('[name=expirationCheckbox]').prop('checked')).toEqual(false);
				expect(dialog.$el.find('.datepicker').val()).toEqual('');
			});
			it('does not check expiration date checkbox for new share', function() {
				dialog.render();

				expect(dialog.$el.find('[name=expirationCheckbox]').prop('checked')).toEqual(false);
				expect(dialog.$el.find('.datepicker').val()).toEqual('');
			});
			it('checks expiration date checkbox and populates field when expiration date was set', function() {
				shareModel.get('linkShare').expiration = '2014-02-01 00:00:00';
				dialog.render();
				expect(dialog.$el.find('[name=expirationCheckbox]').prop('checked')).toEqual(true);
				expect(dialog.$el.find('.datepicker').val()).toEqual('01-02-2014');
			});
			it('sets default date when default date setting is enabled', function() {
				configModel.set('isDefaultExpireDateEnabled', true);
				dialog.render();
				dialog.$el.find('.linkCheckbox').click();
				// here fetch would be called and the server returns the expiration date
				shareModel.get('linkShare').expiration = '2014-1-27 00:00:00';
				dialog.render();

				// enabled by default
				expect(dialog.$el.find('[name=expirationCheckbox]').prop('checked')).toEqual(true);
				expect(dialog.$el.find('.datepicker').val()).toEqual('27-01-2014');

				// disabling is allowed
				dialog.$el.find('[name=expirationCheckbox]').click();
				expect(dialog.$el.find('[name=expirationCheckbox]').prop('checked')).toEqual(false);
			});
			it('enforces default date when enforced date setting is enabled', function() {
				configModel.set({
					isDefaultExpireDateEnabled: true,
					isDefaultExpireDateEnforced: true
				});
				dialog.render();
				dialog.$el.find('.linkCheckbox').click();
				// here fetch would be called and the server returns the expiration date
				shareModel.get('linkShare').expiration = '2014-1-27 00:00:00';
				dialog.render();

				expect(dialog.$el.find('[name=expirationCheckbox]').prop('checked')).toEqual(true);
				expect(dialog.$el.find('.datepicker').val()).toEqual('27-01-2014');

				// disabling is not allowed
				expect(dialog.$el.find('[name=expirationCheckbox]').prop('disabled')).toEqual(true);
				dialog.$el.find('[name=expirationCheckbox]').click();
				expect(dialog.$el.find('[name=expirationCheckbox]').prop('checked')).toEqual(true);
			});
			it('enforces default date when enforced date setting is enabled and password is enforced', function() {
				configModel.set({
					enforcePasswordForPublicLink: true,
					isDefaultExpireDateEnabled: true,
					isDefaultExpireDateEnforced: true
				});
				dialog.render();
				dialog.$el.find('.linkCheckbox').click();
				// here fetch would be called and the server returns the expiration date
				shareModel.get('linkShare').expiration = '2014-1-27 00:00:00';
				dialog.render();

				//Enter password
				dialog.$el.find('.linkPassText').val('foo');
				dialog.$el.find('.linkPassText').trigger(new $.Event('keyup', {keyCode: 13}));
				fakeServer.requests[0].respond(
					200,
					{ 'Content-Type': 'application/json' },
					JSON.stringify({data: {token: 'xyz'}, status: 'success'})
				);

				expect(dialog.$el.find('[name=expirationCheckbox]').prop('checked')).toEqual(true);
				expect(dialog.$el.find('.datepicker').val()).toEqual('27-01-2014');

				// disabling is not allowed
				expect(dialog.$el.find('[name=expirationCheckbox]').prop('disabled')).toEqual(true);
				dialog.$el.find('[name=expirationCheckbox]').click();
				expect(dialog.$el.find('[name=expirationCheckbox]').prop('checked')).toEqual(true);
			});
			it('sets picker minDate to today and no maxDate by default', function() {
				dialog.render();
				dialog.$el.find('.linkCheckbox').click();
				dialog.$el.find('[name=expirationCheckbox]').click();
				expect($.datepicker._defaults.minDate).toEqual(expectedMinDate);
				expect($.datepicker._defaults.maxDate).toEqual(null);
			});
			it('limits the date range to X days after share time when enforced', function() {
				configModel.set({
					isDefaultExpireDateEnabled: true,
					isDefaultExpireDateEnforced: true
				});
				dialog.render();
				dialog.$el.find('.linkCheckbox').click();
				expect($.datepicker._defaults.minDate).toEqual(expectedMinDate);
				expect($.datepicker._defaults.maxDate).toEqual(new Date(2014, 0, 27, 0, 0, 0, 0));
			});
			it('limits the date range to X days after share time when enforced, even when redisplayed the next days', function() {
				// item exists, was created two days ago
				var shareItem = shareModel.get('linkShare');
				shareItem.expiration = '2014-1-27';
				// share time has time component but must be stripped later
				shareItem.stime = new Date(2014, 0, 20, 11, 0, 25).getTime() / 1000;
				configModel.set({
					isDefaultExpireDateEnabled: true,
					isDefaultExpireDateEnforced: true
				});
				dialog.render();
				expect($.datepicker._defaults.minDate).toEqual(expectedMinDate);
				expect($.datepicker._defaults.maxDate).toEqual(new Date(2014, 0, 27, 0, 0, 0, 0));
			});
		});
		describe('send link by email', function() {
			var sendEmailPrivateLinkStub;
			var clock;

			beforeEach(function() {
				configModel.set({
					isMailPublicNotificationEnabled: true
				});

				shareModel.set('linkShare', {
					isLinkShare: true,
					token: 'tehtoken',
					permissions: OC.PERMISSION_READ,
					expiration: null
				});

				sendEmailPrivateLinkStub = sinon.stub(dialog.model, "sendEmailPrivateLink");
				clock = sinon.useFakeTimers();
			});
			afterEach(function() {
				sendEmailPrivateLinkStub.restore();
				clock.restore();
			});

			it('displayes form when sending emails is enabled', function() {
				$('input[name=mailPublicNotificationEnabled]').val('yes');
				dialog.render();
				expect(dialog.$('#emailPrivateLink').length).toEqual(1);
			});
			it('form not rendered when sending emails is disabled', function() {
				$('input[name=mailPublicNotificationEnabled]').val('no');
				dialog.render();
				expect(dialog.$('#emailPrivateLink').length).toEqual(0);
			});
			it('input cleared on success', function() {
				var defer = $.Deferred();
				sendEmailPrivateLinkStub.returns(defer.promise());

				$('input[name=mailPublicNotificationEnabled]').val('yes');
				dialog.render();

				dialog.$el.find('#emailPrivateLink #email').val('a@b.c');
				dialog.$el.find('#emailPrivateLink').trigger('submit');

				expect(sendEmailPrivateLinkStub.callCount).toEqual(1);
				expect(dialog.$el.find('#emailPrivateLink #email').val()).toEqual('Sending ...');

				defer.resolve();
				expect(dialog.$el.find('#emailPrivateLink #email').val()).toEqual('Email sent');

				clock.tick(2000);
				expect(dialog.$el.find('#emailPrivateLink #email').val()).toEqual('');
			});
			it('input not cleared on failure', function() {
				var defer = $.Deferred();
				sendEmailPrivateLinkStub.returns(defer.promise());

				$('input[name=mailPublicNotificationEnabled]').val('yes');
				dialog.render();

				dialog.$el.find('#emailPrivateLink #email').val('a@b.c');
				dialog.$el.find('#emailPrivateLink').trigger('submit');

				expect(sendEmailPrivateLinkStub.callCount).toEqual(1);
				expect(dialog.$el.find('#emailPrivateLink #email').val()).toEqual('Sending ...');

				defer.reject();
				expect(dialog.$el.find('#emailPrivateLink #email').val()).toEqual('a@b.c');
			});
		});
	});
	describe('check for avatar', function() {
		beforeEach(function() {
			shareModel.set({
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
				},{
					id: 101,
					item_source: 123,
					permissions: 31,
					share_type: OC.Share.SHARE_TYPE_GROUP,
					share_with: 'group',
					share_with_displayname: 'group'
				},{
					id: 102,
					item_source: 123,
					permissions: 31,
					share_type: OC.Share.SHARE_TYPE_REMOTE,
					share_with: 'foo@bar.com/baz',
					share_with_displayname: 'foo@bar.com/baz'

				}]
			});
		});

		describe('avatars enabled', function() {
			beforeEach(function() {
				oc_config.enable_avatars = true;
				avatarStub.reset();
				dialog.render();
			});

			afterEach(function() {
				oc_config.enable_avatars = false;
			});

			it('test correct function calls', function() {
				expect(avatarStub.calledTwice).toEqual(true);
				expect(placeholderStub.calledTwice).toEqual(true);
				expect(dialog.$('.shareWithList').children().length).toEqual(3);
				expect(dialog.$('.avatar').length).toEqual(4);
			});

			it('test avatar owner', function() {
				var args = avatarStub.getCall(0).args;
				expect(args.length).toEqual(2);
				expect(args[0]).toEqual('owner');
			});

			it('test avatar user', function() {
				var args = avatarStub.getCall(1).args;
				expect(args.length).toEqual(2);
				expect(args[0]).toEqual('user1');
			});

			it('test avatar for groups', function() {
				var args = placeholderStub.getCall(0).args;
				expect(args.length).toEqual(1);
				expect(args[0]).toEqual('group ' + OC.Share.SHARE_TYPE_GROUP);
			});

			it('test avatar for remotes', function() {
				var args = placeholderStub.getCall(1).args;
				expect(args.length).toEqual(1);
				expect(args[0]).toEqual('foo@bar.com/baz ' + OC.Share.SHARE_TYPE_REMOTE);
			});
		});

		describe('avatars disabled', function() {
			beforeEach(function() {
				dialog.render();
			});

			it('no avatar classes', function() {
				expect($('.avatar').length).toEqual(0);
				expect(avatarStub.callCount).toEqual(0);
				expect(placeholderStub.callCount).toEqual(0);
			});
		});
	});
	describe('share permissions', function() {
		beforeEach(function() {
			oc_appconfig.core.resharingAllowed = true;
		});

		/**
		 * Tests sharing with the given possible permissions
		 *
		 * @param {int} possiblePermissions
		 * @return {int} permissions sent to the server
		 */
		function testWithPermissions(possiblePermissions) {
			shareModel.set({
				permissions: possiblePermissions,
				possiblePermissions: possiblePermissions
			});
			dialog.render();
			var autocompleteOptions = autocompleteStub.getCall(0).args[0];
			// simulate autocomplete selection
			autocompleteOptions.select(new $.Event('select'), {
				item: {
					label: 'User Two',
					value: {
						shareType: OC.Share.SHARE_TYPE_USER,
						shareWith: 'user2'
					}
				}
			});
			autocompleteStub.reset();
			var requestBody = OC.parseQueryString(_.last(fakeServer.requests).requestBody);
			return parseInt(requestBody.permissions, 10);
		}

		describe('regular sharing', function() {
			it('shares with given permissions with default config', function() {
				shareModel.set({
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
				shareModel.set({
					reshare: {},
					shares: []
				});
				expect(
					testWithPermissions(OC.PERMISSION_READ | OC.PERMISSION_UPDATE | OC.PERMISSION_SHARE)
				).toEqual(OC.PERMISSION_READ | OC.PERMISSION_UPDATE);
			});
			it('automatically adds READ permission even when not specified', function() {
				configModel.set('isResharingAllowed', false);
				shareModel.set({
					reshare: {},
					shares: []
				});
				expect(
					testWithPermissions(OC.PERMISSION_UPDATE | OC.PERMISSION_SHARE)
				).toEqual(OC.PERMISSION_READ | OC.PERMISSION_UPDATE | OC.PERMISSION_UPDATE);
			});
			it('does not show sharing options when sharing not allowed', function() {
				shareModel.set({
					reshare: {},
					shares: [],
					permissions: OC.PERMISSION_READ
				});
				dialog.render();
				expect(dialog.$el.find('.shareWithField').prop('disabled')).toEqual(true);
			});
			it('shows reshare owner', function() {
				shareModel.set({
					reshare: {
						uid_owner: 'user1'
					},
					shares: [],
					permissions: OC.PERMISSION_READ
				});
				dialog.render();
				expect(dialog.$el.find('.resharerInfoView .reshare').length).toEqual(1);
			});
			it('does not show reshare owner if owner is current user', function() {
				shareModel.set({
					reshare: {
						uid_owner: OC.currentUser
					},
					shares: [],
					permissions: OC.PERMISSION_READ
				});
				dialog.render();
				expect(dialog.$el.find('.resharerInfoView .reshare').length).toEqual(0);
			});
		});
	});
});

