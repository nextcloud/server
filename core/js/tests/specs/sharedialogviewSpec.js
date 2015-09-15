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
		configModel = new OC.Share.ShareConfigModel();
		shareModel = new OC.Share.ShareItemModel(attributes, {
			configModel: configModel,
			fileInfoModel: fileInfoModel
		});
		dialog = new OC.Share.ShareDialogView({
			configModel: configModel,
			model: shareModel
		});

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

		fetchStub.restore();

		autocompleteStub.restore();
		avatarStub.restore();
		placeholderStub.restore();
		oc_config.enable_avatars = oldEnableAvatars;
	});
	describe('Share with link', function() {
		beforeEach(function() {
			configModel.set('enforcePasswordForPublicLink', false);
		});
		// TODO: test ajax calls
		// TODO: test password field visibility (whenever enforced or not)
		it('update password on focus out', function() {
			$('#allowShareWithLink').val('yes');

			dialog.render();

			// Toggle linkshare
			dialog.$el.find('[name=linkCheckbox]').click();
			fakeServer.requests[0].respond(
				200,
				{ 'Content-Type': 'application/json' },
				JSON.stringify({data: {token: 'xyz'}, status: 'success'})
			);

			// Enable password, enter password and focusout
			dialog.$el.find('[name=showPassword]').click();
			dialog.$el.find('#linkPassText').focus();
			dialog.$el.find('#linkPassText').val('foo');
			dialog.$el.find('#linkPassText').focusout();

			expect(fakeServer.requests[1].method).toEqual('POST');
			var body = OC.parseQueryString(fakeServer.requests[1].requestBody);
			expect(body['shareWith']).toEqual('foo');

			// Set password response
			fakeServer.requests[1].respond(
				200,
				{ 'Content-Type': 'application/json' },
				JSON.stringify({data: {token: 'xyz'}, status: 'success'})
			);

			expect(dialog.$el.find('#linkPassText').val()).toEqual('');
			expect(dialog.$el.find('#linkPassText').attr('placeholder')).toEqual('Password protected');
		});
		it('update password on enter', function() {
			$('#allowShareWithLink').val('yes');

			dialog.render();

			// Toggle linkshare
			dialog.$el.find('[name=linkCheckbox]').click();
			fakeServer.requests[0].respond(
				200,
				{ 'Content-Type': 'application/json' },
				JSON.stringify({data: {token: 'xyz'}, status: 'success'})
			);

			// Enable password and enter password
			dialog.$el.find('[name=showPassword]').click();
			dialog.$el.find('#linkPassText').focus();
			dialog.$el.find('#linkPassText').val('foo');
			dialog.$el.find('#linkPassText').trigger(new $.Event('keyup', {keyCode: 13}));

			expect(fakeServer.requests[1].method).toEqual('POST');
			var body = OC.parseQueryString(fakeServer.requests[1].requestBody);
			expect(body['shareWith']).toEqual('foo');

			// Set password response
			fakeServer.requests[1].respond(
				200,
				{ 'Content-Type': 'application/json' },
				JSON.stringify({data: {token: 'xyz'}, status: 'success'})
			);

			expect(dialog.$el.find('#linkPassText').val()).toEqual('');
			expect(dialog.$el.find('#linkPassText').attr('placeholder')).toEqual('Password protected');
		});
		it('shows share with link checkbox when allowed', function() {
			$('#allowShareWithLink').val('yes');

			dialog.render();

			expect(dialog.$el.find('#linkCheckbox').length).toEqual(1);
		});
		it('does not show share with link checkbox when not allowed', function() {
			$('#allowShareWithLink').val('no');

			dialog.render();

			expect(dialog.$el.find('#linkCheckbox').length).toEqual(0);
		});
		it('Reset link when password is enforced and link is toggled', function() { 
			var old = oc_appconfig.core.enforcePasswordForPublicLink;
			oc_appconfig.core.enforcePasswordForPublicLink = true;
			$('#allowShareWithLink').val('yes');

			dialog.render();

			// Toggle linkshare
			dialog.$el.find('[name=linkCheckbox]').click();
			expect(dialog.$el.find('#linkText').val()).toEqual('');

			// Set password
			dialog.$el.find('#linkPassText').val('foo');
			dialog.$el.find('#linkPassText').trigger(new $.Event('keyup', {keyCode: 13}));
			fakeServer.requests[0].respond(
				200,
				{ 'Content-Type': 'application/json' },
				JSON.stringify({data: {token: 'xyz'}, status: 'success'})
			);

			// Remove link
			dialog.$el.find('[name=linkCheckbox]').click();
			fakeServer.requests[1].respond(
				200,
				{ 'Content-Type': 'application/json' },
				JSON.stringify({status: 'success'})
			);

			/*
			 * Try to share again
			 * The linkText should be emptied
			 */
			dialog.$el.find('[name=linkCheckbox]').click();
			expect(dialog.$el.find('#linkText').val()).toEqual('');

			/*
			 * Do not set password but untoggle
			 * Since there is no share this should not result in another request to the server
			 */
			dialog.$el.find('[name=linkCheckbox]').click();
			expect(fakeServer.requests.length).toEqual(2);

			oc_appconfig.core.enforcePasswordForPublicLink = old;
		});

		it('Reset password placeholder when password is enforced and link is toggled', function() { 
			var old = oc_appconfig.core.enforcePasswordForPublicLink;
			oc_appconfig.core.enforcePasswordForPublicLink = true;
			$('#allowShareWithLink').val('yes');

			dialog.render();

			// Toggle linkshare
			dialog.$el.find('[name=linkCheckbox]').click();
			expect(dialog.$el.find('#linkPassText').attr('placeholder')).toEqual('Choose a password for the public link');

			// Set password
			dialog.$el.find('#linkPassText').val('foo');
			dialog.$el.find('#linkPassText').trigger(new $.Event('keyup', {keyCode: 13}));
			fakeServer.requests[0].respond(
				200,
				{ 'Content-Type': 'application/json' },
				JSON.stringify({data: {token: 'xyz'}, status: 'success'})
			);
			expect(dialog.$el.find('#linkPassText').attr('placeholder')).toEqual('**********');

			// Remove link
			dialog.$el.find('[name=linkCheckbox]').click();
			fakeServer.requests[1].respond(
				200,
				{ 'Content-Type': 'application/json' },
				JSON.stringify({status: 'success'})
			);

			// Try to share again
			dialog.$el.find('[name=linkCheckbox]').click();
			expect(dialog.$el.find('#linkPassText').attr('placeholder')).toEqual('Choose a password for the public link');

			oc_appconfig.core.enforcePasswordForPublicLink = old;
		});
		it('reset password on toggle of share', function() {
			$('#allowShareWithLink').val('yes');

			dialog.render();

			dialog.$el.find('[name=linkCheckbox]').click();
			fakeServer.requests[0].respond(
				200,
				{ 'Content-Type': 'application/json' },
				JSON.stringify({data: {token: 'xyz'}, status: 'success'})
			);

			//Password protection should be unchecked and password field not visible
			expect(dialog.$el.find('[name=showPassword]').prop('checked')).toEqual(false);
			expect(dialog.$el.find('#linkPass').is(":visible")).toEqual(false);

			// Toggle and set password
			dialog.$el.find('[name=showPassword]').click();
			dialog.$el.find('#linkPassText').val('foo');
			dialog.$el.find('#linkPassText').trigger(new $.Event('keyup', {keyCode: 13}));
			fakeServer.requests[1].respond(
				200,
				{ 'Content-Type': 'application/json' },
				JSON.stringify({data: {token: 'xyz2'}, status: 'success'})
			);

			// Unshare
			dialog.$el.find('[name=linkCheckbox]').click();
			fakeServer.requests[2].respond(
				200,
				{ 'Content-Type': 'application/json' },
				JSON.stringify({status: 'success'})
			);

			// Toggle share again
			dialog.$el.find('[name=linkCheckbox]').click();
			fakeServer.requests[3].respond(
				200,
				{ 'Content-Type': 'application/json' },
				JSON.stringify({data: {token: 'xyz3'}, status: 'success'})
			);


			// Password checkbox should be unchecked
			expect(dialog.$el.find('[name=showPassword]').prop('checked')).toEqual(false);
			expect(dialog.$el.find('#linkPass').is(":visible")).toEqual(false);
		});
		it('reset expiration on toggle of share', function() {
			$('#allowShareWithLink').val('yes');

			dialog.render();

			dialog.$el.find('[name=linkCheckbox]').click();
			fakeServer.requests[0].respond(
				200,
				{ 'Content-Type': 'application/json' },
				JSON.stringify({data: {token: 'xyz'}, status: 'success'})
			);

			//Expiration should be unchecked and expiration field not visible
			expect(dialog.$el.find('[name=expirationCheckbox]').prop('checked')).toEqual(false);
			expect(dialog.$el.find('#expirationDate').is(":visible")).toEqual(false);

			// Toggle and set password
			dialog.$el.find('[name=expirationCheckbox]').click();
			d = new Date();
			d.setDate(d.getDate() + 1);
			date=d.getDate() + '-' + (d.getMonth()+1) + '-' + d.getFullYear();
			dialog.$el.find('#expirationDate').val(date);
			dialog.$el.find('#expirationDate').change();
			fakeServer.requests[1].respond(
				200,
				{ 'Content-Type': 'application/json' },
				JSON.stringify({data: {token: 'xyz2'}, status: 'success'})
			);

			// Unshare
			dialog.$el.find('[name=linkCheckbox]').click();
			fakeServer.requests[2].respond(
				200,
				{ 'Content-Type': 'application/json' },
				JSON.stringify({status: 'success'})
			);

			// Toggle share again
			dialog.$el.find('[name=linkCheckbox]').click();
			fakeServer.requests[3].respond(
				200,
				{ 'Content-Type': 'application/json' },
				JSON.stringify({data: {token: 'xyz3'}, status: 'success'})
			);

			// Recheck expire visibility
			expect(dialog.$el.find('[name=expirationCheckbox]').prop('checked')).toEqual(false);
			expect(dialog.$el.find('#expirationDate').is(":visible")).toEqual(false);
		});
		it('shows populated link share when a link share exists', function() {
			shareModel.set('linkShare', {
				isLinkShare: true,
				token: 'tehtoken',
				link: 'TODO',
				expiration: '',
				permissions: OC.PERMISSION_READ,
				stime: 1403884258,
			});

			expect(dialog.$el.find('#linkCheckbox').prop('checked')).toEqual(true);
			// this is how the OC.Share class does it...
			var link = parent.location.protocol + '//' + location.host +
				OC.generateUrl('/s/') + 'tehtoken';
			expect(dialog.$el.find('#linkText').val()).toEqual(link);
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

				oc_appconfig.core.defaultExpireDate = 7;
				oc_appconfig.core.enforcePasswordForPublicLink = false;
				oc_appconfig.core.defaultExpireDateEnabled = false;
				oc_appconfig.core.defaultExpireDateEnforced = false;

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
				expect(dialog.$el.find('#expirationDate').val()).toEqual('');
			});
			it('does not check expiration date checkbox for new share', function() {
				dialog.render();

				expect(dialog.$el.find('[name=expirationCheckbox]').prop('checked')).toEqual(false);
				expect(dialog.$el.find('#expirationDate').val()).toEqual('');
			});
			it('checks expiration date checkbox and populates field when expiration date was set', function() {
				shareModel.get('linkShare').expiration = 1234;
				dialog.render();
				expect(dialog.$el.find('[name=expirationCheckbox]').prop('checked')).toEqual(true);
				expect(dialog.$el.find('#expirationDate').val()).toEqual('1234');
			});
			it('sets default date when default date setting is enabled', function() {
				/* jshint camelcase:false */
				oc_appconfig.core.defaultExpireDateEnabled = true;
				dialog.render();
				dialog.$el.find('[name=linkCheckbox]').click();
				// enabled by default
				expect(dialog.$el.find('[name=expirationCheckbox]').prop('checked')).toEqual(true);
				// TODO: those zeros must go...
				expect(dialog.$el.find('#expirationDate').val()).toEqual('2014-1-27 00:00:00');

				// disabling is allowed
				dialog.$el.find('[name=expirationCheckbox]').click();
				expect(dialog.$el.find('[name=expirationCheckbox]').prop('checked')).toEqual(false);
			});
			it('enforces default date when enforced date setting is enabled', function() {
				/* jshint camelcase:false */
				oc_appconfig.core.defaultExpireDateEnabled = true;
				oc_appconfig.core.defaultExpireDateEnforced = true;
				dialog.render();
				dialog.$el.find('[name=linkCheckbox]').click();
				expect(dialog.$el.find('[name=expirationCheckbox]').prop('checked')).toEqual(true);
				// TODO: those zeros must go...
				expect(dialog.$el.find('#expirationDate').val()).toEqual('2014-1-27 00:00:00');

				// disabling is not allowed
				expect(dialog.$el.find('[name=expirationCheckbox]').prop('disabled')).toEqual(true);
				dialog.$el.find('[name=expirationCheckbox]').click();
				expect(dialog.$el.find('[name=expirationCheckbox]').prop('checked')).toEqual(true);
			});
			it('enforces default date when enforced date setting is enabled and password is enforced', function() {
				/* jshint camelcase:false */
				oc_appconfig.core.enforcePasswordForPublicLink = true;
				oc_appconfig.core.defaultExpireDateEnabled = true;
				oc_appconfig.core.defaultExpireDateEnforced = true;
				dialog.render();
				dialog.$el.find('[name=linkCheckbox]').click();

				//Enter password
				dialog.$el.find('#linkPassText').val('foo');
				dialog.$el.find('#linkPassText').trigger(new $.Event('keyup', {keyCode: 13}));
				fakeServer.requests[0].respond(
					200,
					{ 'Content-Type': 'application/json' },
					JSON.stringify({data: {token: 'xyz'}, status: 'success'})
				);

				expect(dialog.$el.find('[name=expirationCheckbox]').prop('checked')).toEqual(true);
				// TODO: those zeros must go...
				expect(dialog.$el.find('#expirationDate').val()).toEqual('2014-1-27 00:00:00');

				// disabling is not allowed
				expect(dialog.$el.find('[name=expirationCheckbox]').prop('disabled')).toEqual(true);
				dialog.$el.find('[name=expirationCheckbox]').click();
				expect(dialog.$el.find('[name=expirationCheckbox]').prop('checked')).toEqual(true);
			});
			it('displayes email form when sending emails is enabled', function() {
				$('input[name=mailPublicNotificationEnabled]').val('yes');
				dialog.render();
				expect($('#emailPrivateLink').length).toEqual(1);
			});
			it('not renders email form when sending emails is disabled', function() {
				$('input[name=mailPublicNotificationEnabled]').val('no');
				dialog.render();
				expect($('#emailPrivateLink').length).toEqual(0);
			});
			it('sets picker minDate to today and no maxDate by default', function() {
				dialog.render();
				dialog.$el.find('[name=linkCheckbox]').click();
				dialog.$el.find('[name=expirationCheckbox]').click();
				expect($.datepicker._defaults.minDate).toEqual(expectedMinDate);
				expect($.datepicker._defaults.maxDate).toEqual(null);
			});
			it('limits the date range to X days after share time when enforced', function() {
				/* jshint camelcase:false */
				oc_appconfig.core.defaultExpireDateEnabled = true;
				oc_appconfig.core.defaultExpireDateEnforced = true;
				dialog.render();
				dialog.$el.find('[name=linkCheckbox]').click();
				expect($.datepicker._defaults.minDate).toEqual(expectedMinDate);
				expect($.datepicker._defaults.maxDate).toEqual(new Date(2014, 0, 27, 0, 0, 0, 0));
			});
			it('limits the date range to X days after share time when enforced, even when redisplayed the next days', function() {
				// item exists, was created two days ago
				shareItem.expiration = '2014-1-27';
				// share time has time component but must be stripped later
				shareItem.stime = new Date(2014, 0, 20, 11, 0, 25).getTime() / 1000;
				shareData.shares.push(shareItem);
				/* jshint camelcase:false */
				oc_appconfig.core.defaultExpireDateEnabled = true;
				oc_appconfig.core.defaultExpireDateEnforced = true;
				dialog.render();
				expect($.datepicker._defaults.minDate).toEqual(expectedMinDate);
				expect($.datepicker._defaults.maxDate).toEqual(new Date(2014, 0, 27, 0, 0, 0, 0));
			});
		});
	});
	describe('check for avatar', function() {
		beforeEach(function() {
			loadItemStub.returns({
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
				OC.Share.showDropDown(
					'file',
					123,
					$container,
					true,
					31,
					'shared_file_name.txt'
				);
			});

			afterEach(function() {
				oc_config.enable_avatars = false;
			});

			it('test correct function calls', function() {
				expect(avatarStub.calledTwice).toEqual(true);
				expect(placeholderStub.calledTwice).toEqual(true);
				expect($('#shareWithList').children().length).toEqual(3);
				expect($('.avatar').length).toEqual(4);
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
				OC.Share.showDropDown(
					'file',
					123,
					$container,
					true,
					31,
					'shared_file_name.txt'
				);
			});

			it('no avatar classes', function() {
				expect($('.avatar').length).toEqual(0);
				expect(avatarStub.callCount).toEqual(0);
				expect(placeholderStub.callCount).toEqual(0);
			});
		});
	});
	describe('"sharesChanged" event', function() {
		var autocompleteOptions;
		var handler;
		beforeEach(function() {
			handler = sinon.stub();
			loadItemStub.returns({
				reshare: [],
				shares: [{
					id: 100,
					item_source: 123,
					permissions: 31,
					share_type: OC.Share.SHARE_TYPE_USER,
					share_with: 'user1',
					share_with_displayname: 'User One'
				}]
			});
			OC.Share.showDropDown(
				'file',
				123,
				$container,
				true,
				31,
				'shared_file_name.txt'
			);
			$('#dropdown').on('sharesChanged', handler);
			autocompleteOptions = autocompleteStub.getCall(0).args[0];
		});
		afterEach(function() {
			autocompleteOptions = null;
			handler = null;
		});
		it('triggers "sharesChanged" event when adding shares', function() {
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
			fakeServer.requests[0].respond(
				200,
				{ 'Content-Type': 'application/json' },
				JSON.stringify({status: 'success'})
			);
			expect(handler.calledOnce).toEqual(true);
			var shares = handler.getCall(0).args[0].shares;
			expect(shares).toBeDefined();
			expect(shares[OC.Share.SHARE_TYPE_USER][0].share_with_displayname).toEqual('User One');
			expect(shares[OC.Share.SHARE_TYPE_USER][1].share_with_displayname).toEqual('User Two');
			expect(shares[OC.Share.SHARE_TYPE_GROUP]).not.toBeDefined();
		});
		it('triggers "sharesChanged" event when deleting shares', function() {
			dialog.$el.find('.unshare:eq(0)').click();
			fakeServer.requests[0].respond(
				200,
				{ 'Content-Type': 'application/json' },
				JSON.stringify({status: 'success'})
			);
			expect(handler.calledOnce).toEqual(true);
			var shares = handler.getCall(0).args[0].shares;
			expect(shares).toBeDefined();
			expect(shares[OC.Share.SHARE_TYPE_USER]).toEqual([]);
			expect(shares[OC.Share.SHARE_TYPE_GROUP]).not.toBeDefined();
		});
		it('triggers "sharesChanged" event when toggling link share', function() {
			// simulate autocomplete selection
			dialog.$el.find('#linkCheckbox').click();
			fakeServer.requests[0].respond(
				200,
				{ 'Content-Type': 'application/json' },
				JSON.stringify({status: 'success', data: { token: 'abc' }})
			);
			expect(handler.calledOnce).toEqual(true);
			var shares = handler.getCall(0).args[0].shares;
			expect(shares).toBeDefined();
			expect(shares[OC.Share.SHARE_TYPE_USER][0].share_with_displayname).toEqual('User One');
			expect(shares[OC.Share.SHARE_TYPE_GROUP]).not.toBeDefined();

			handler.reset();

			// uncheck checkbox
			dialog.$el.find('#linkCheckbox').click();
			fakeServer.requests[1].respond(
				200,
				{ 'Content-Type': 'application/json' },
				JSON.stringify({status: 'success'})
			);

			expect(handler.calledOnce).toEqual(true);
			shares = handler.getCall(0).args[0].shares;
			expect(shares).toBeDefined();
			expect(shares[OC.Share.SHARE_TYPE_USER][0].share_with_displayname).toEqual('User One');
			expect(shares[OC.Share.SHARE_TYPE_GROUP]).not.toBeDefined();
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
			OC.Share.showDropDown(
				'file',
				123,
				$container,
				true,
				possiblePermissions,
				'shared_file_name.txt'
			);
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
				loadItemStub.returns({
					reshare: [],
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
				oc_appconfig.core.resharingAllowed = false;
				loadItemStub.returns({
					reshare: [],
					shares: []
				});
				expect(
					testWithPermissions(OC.PERMISSION_READ | OC.PERMISSION_UPDATE | OC.PERMISSION_SHARE)
				).toEqual(OC.PERMISSION_READ | OC.PERMISSION_UPDATE);
			});
			it('automatically adds READ permission even when not specified', function() {
				oc_appconfig.core.resharingAllowed = false;
				loadItemStub.returns({
					reshare: [],
					shares: []
				});
				expect(
					testWithPermissions(OC.PERMISSION_UPDATE | OC.PERMISSION_SHARE)
				).toEqual(OC.PERMISSION_READ | OC.PERMISSION_UPDATE | OC.PERMISSION_UPDATE);
			});
			it('does not show sharing options when sharing not allowed', function() {
				loadItemStub.returns({
					reshare: [],
					shares: []
				});
				OC.Share.showDropDown(
					'file',
					123,
					$container,
					true,
					OC.PERMISSION_READ,
					'shared_file_name.txt'
				);
				expect(dialog.$el.find('#shareWithList').length).toEqual(0);
			});
		});
		describe('resharing', function() {
			it('shares with given permissions when original share had all permissions', function() {
				loadItemStub.returns({
					reshare: {
						permissions: OC.PERMISSION_ALL
					},
					shares: []
				});
				expect(
					testWithPermissions(OC.PERMISSION_READ | OC.PERMISSION_UPDATE | OC.PERMISSION_SHARE)
				).toEqual(OC.PERMISSION_READ | OC.PERMISSION_UPDATE | OC.PERMISSION_SHARE);
			});
			it('reduces reshare permissions to the ones from the original share', function() {
				loadItemStub.returns({
					reshare: {
						permissions: OC.PERMISSION_READ,
						uid_owner: 'user1'
					},
					shares: []
				});
				OC.Share.showDropDown(
					'file',
					123,
					$container,
					true,
					OC.PERMISSION_ALL,
					'shared_file_name.txt'
				);
				// no resharing allowed
				expect(dialog.$el.find('#shareWithList').length).toEqual(0);
			});
			it('reduces reshare permissions to possible permissions', function() {
				loadItemStub.returns({
					reshare: {
						permissions: OC.PERMISSION_ALL,
						uid_owner: 'user1'
					},
					shares: []
				});
				OC.Share.showDropDown(
					'file',
					123,
					$container,
					true,
					OC.PERMISSION_READ,
					'shared_file_name.txt'
				);
				// no resharing allowed
				expect(dialog.$el.find('#shareWithList').length).toEqual(0);
			});
			it('does not show sharing options when resharing not allowed', function() {
				loadItemStub.returns({
					reshare: {
						permissions: OC.PERMISSION_READ | OC.PERMISSION_UPDATE | OC.PERMISSION_DELETE,
						uid_owner: 'user1'
					},
					shares: []
				});
				OC.Share.showDropDown(
					'file',
					123,
					$container,
					true,
					OC.PERMISSION_ALL,
					'shared_file_name.txt'
				);
				expect(dialog.$el.find('#shareWithList').length).toEqual(0);
			});
			it('allows owner to share their own share when they are also the recipient', function() {
				OC.currentUser = 'user1';
				loadItemStub.returns({
					reshare: {
						permissions: OC.PERMISSION_READ,
						uid_owner: 'user1'
					},
					shares: []
				});
				OC.Share.showDropDown(
					'file',
					123,
					$container,
					true,
					OC.PERMISSION_ALL,
					'shared_file_name.txt'
				);
				// sharing still allowed
				expect(dialog.$el.find('#shareWithList').length).toEqual(1);
			});
		});
	});
});

