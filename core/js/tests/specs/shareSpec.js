/**
* ownCloud
*
* @author Vincent Petry
* @copyright 2014 Vincent Petry <pvince81@owncloud.com>
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
describe('OC.Share tests', function() {
	describe('dropdown', function() {
		var $container;
		var oldAppConfig;
		var loadItemStub;
		var autocompleteStub;
		var oldEnableAvatars;
		var avatarStub;
		var placeholderStub;
		var oldCurrentUser;

		beforeEach(function() {
			$('#testArea').append($('<div id="shareContainer"></div>'));
			// horrible parameters
			$('#testArea').append('<input id="allowShareWithLink" type="hidden" value="yes">');
			$('#testArea').append('<input id="mailPublicNotificationEnabled" name="mailPublicNotificationEnabled" type="hidden" value="yes">');
			$container = $('#shareContainer');
			/* jshint camelcase:false */
			oldAppConfig = _.extend({}, oc_appconfig.core);
			oc_appconfig.core.enforcePasswordForPublicLink = false;

			loadItemStub = sinon.stub(OC.Share, 'loadItem');
			loadItemStub.returns({
				reshare: [],
				shares: []
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
			loadItemStub.restore();

			autocompleteStub.restore();
			avatarStub.restore();
			placeholderStub.restore();
			oc_config.enable_avatars = oldEnableAvatars;
			$('#dropdown').remove();
		});
		it('calls loadItem with the correct arguments', function() {
			OC.Share.showDropDown(
				'file',
			   	123,
			   	$container,
				true,
				31,
				'shared_file_name.txt'
			);
			expect(loadItemStub.calledOnce).toEqual(true);
			expect(loadItemStub.calledWith('file', 123)).toEqual(true);
		});
		it('shows the dropdown with default values', function() {
			var $el;
			OC.Share.showDropDown(
				'file',
			   	123,
			   	$container,
				true,
				31,
				'shared_file_name.txt'
			);
			$el = $container.find('#dropdown');
			expect($el.length).toEqual(1);
			expect($el.attr('data-item-type')).toEqual('file');
			expect($el.attr('data-item-source')).toEqual('123');
			// TODO: expect that other parts are rendered correctly
		});
		describe('Share with link', function() {
			// TODO: test ajax calls
			// TODO: test password field visibility (whenever enforced or not)
			it('update password on focus out', function() {
				$('#allowShareWithLink').val('yes');

				OC.Share.showDropDown(
					'file',
					123,
					$container,
					true,
					31,
					'shared_file_name.txt'
				);

				// Toggle linkshare
				$('#dropdown [name=linkCheckbox]').click();
				fakeServer.requests[0].respond(
					200,
					{ 'Content-Type': 'application/json' },
					JSON.stringify({data: {token: 'xyz'}, status: 'success'})
				);

				// Enable password, enter password and focusout
				$('#dropdown [name=showPassword]').click();
				$('#dropdown #linkPassText').focus();
				$('#dropdown #linkPassText').val('foo');
				$('#dropdown #linkPassText').focusout();

				expect(fakeServer.requests[1].method).toEqual('POST');
				var body = OC.parseQueryString(fakeServer.requests[1].requestBody);
				expect(body['shareWith']).toEqual('foo');

				// Set password response
				fakeServer.requests[1].respond(
					200,
					{ 'Content-Type': 'application/json' },
					JSON.stringify({data: {token: 'xyz'}, status: 'success'})
				);

				expect($('#dropdown #linkPassText').val()).toEqual('');
				expect($('#dropdown #linkPassText').attr('placeholder')).toEqual('Password protected');
			});
			it('update password on enter', function() {
				$('#allowShareWithLink').val('yes');

				OC.Share.showDropDown(
					'file',
					123,
					$container,
					true,
					31,
					'shared_file_name.txt'
				);

				// Toggle linkshare
				$('#dropdown [name=linkCheckbox]').click();
				fakeServer.requests[0].respond(
					200,
					{ 'Content-Type': 'application/json' },
					JSON.stringify({data: {token: 'xyz'}, status: 'success'})
				);

				// Enable password and enter password
				$('#dropdown [name=showPassword]').click();
				$('#dropdown #linkPassText').focus();
				$('#dropdown #linkPassText').val('foo');
				$('#dropdown #linkPassText').trigger(new $.Event('keyup', {keyCode: 13}));

				expect(fakeServer.requests[1].method).toEqual('POST');
				var body = OC.parseQueryString(fakeServer.requests[1].requestBody);
				expect(body['shareWith']).toEqual('foo');

				// Set password response
				fakeServer.requests[1].respond(
					200,
					{ 'Content-Type': 'application/json' },
					JSON.stringify({data: {token: 'xyz'}, status: 'success'})
				);

				expect($('#dropdown #linkPassText').val()).toEqual('');
				expect($('#dropdown #linkPassText').attr('placeholder')).toEqual('Password protected');
			});
			it('shows share with link checkbox when allowed', function() {
				$('#allowShareWithLink').val('yes');
				OC.Share.showDropDown(
					'file',
					123,
					$container,
					true,		
					31,
					'shared_file_name.txt'
				);
				expect($('#dropdown #linkCheckbox').length).toEqual(1);
			});
			it('does not show share with link checkbox when not allowed', function() {
				$('#allowShareWithLink').val('no');
				OC.Share.showDropDown(
					'file',
					123,
					$container,
					true,		
					31,
					'shared_file_name.txt'
				);
				expect($('#dropdown #linkCheckbox').length).toEqual(0);
			});
			it('Reset link when password is enforced and link is toggled', function() { 
				var old = oc_appconfig.core.enforcePasswordForPublicLink;
				oc_appconfig.core.enforcePasswordForPublicLink = true;
				$('#allowShareWithLink').val('yes');

				OC.Share.showDropDown(
					'file',
					123,
					$container,
					true,
					31,
					'shared_file_name.txt'
				);

				// Toggle linkshare
				$('#dropdown [name=linkCheckbox]').click();
				expect($('#dropdown #linkText').val()).toEqual('');

				// Set password
				$('#dropdown #linkPassText').val('foo');
				$('#dropdown #linkPassText').trigger(new $.Event('keyup', {keyCode: 13}));
				fakeServer.requests[0].respond(
					200,
					{ 'Content-Type': 'application/json' },
					JSON.stringify({data: {token: 'xyz'}, status: 'success'})
				);

				// Remove link
				$('#dropdown [name=linkCheckbox]').click();
				fakeServer.requests[1].respond(
					200,
					{ 'Content-Type': 'application/json' },
					JSON.stringify({status: 'success'})
				);

				/*
				 * Try to share again
				 * The linkText should be emptied
				 */
				$('#dropdown [name=linkCheckbox]').click();
				expect($('#dropdown #linkText').val()).toEqual('');

				/*
				 * Do not set password but untoggle
				 * Since there is no share this should not result in another request to the server
				 */
				$('#dropdown [name=linkCheckbox]').click();
				expect(fakeServer.requests.length).toEqual(2);

				oc_appconfig.core.enforcePasswordForPublicLink = old;
			});

			it('Reset password placeholder when password is enforced and link is toggled', function() { 
				var old = oc_appconfig.core.enforcePasswordForPublicLink;
				oc_appconfig.core.enforcePasswordForPublicLink = true;
				$('#allowShareWithLink').val('yes');

				OC.Share.showDropDown(
					'file',
					123,
					$container,
					true,
					31,
					'shared_file_name.txt'
				);

				// Toggle linkshare
				$('#dropdown [name=linkCheckbox]').click();
				expect($('#dropdown #linkPassText').attr('placeholder')).toEqual('Choose a password for the public link');

				// Set password
				$('#dropdown #linkPassText').val('foo');
				$('#dropdown #linkPassText').trigger(new $.Event('keyup', {keyCode: 13}));
				fakeServer.requests[0].respond(
					200,
					{ 'Content-Type': 'application/json' },
					JSON.stringify({data: {token: 'xyz'}, status: 'success'})
				);
				expect($('#dropdown #linkPassText').attr('placeholder')).toEqual('**********');

				// Remove link
				$('#dropdown [name=linkCheckbox]').click();
				fakeServer.requests[1].respond(
					200,
					{ 'Content-Type': 'application/json' },
					JSON.stringify({status: 'success'})
				);

				// Try to share again
				$('#dropdown [name=linkCheckbox]').click();
				expect($('#dropdown #linkPassText').attr('placeholder')).toEqual('Choose a password for the public link');

				oc_appconfig.core.enforcePasswordForPublicLink = old;
			});
			it('reset password on toggle of share', function() {
				$('#allowShareWithLink').val('yes');
				OC.Share.showDropDown(
					'file',
					123,
					$container,
					true,
					31,
					'shared_file_name.txt'
				);
				$('#dropdown [name=linkCheckbox]').click();
				fakeServer.requests[0].respond(
					200,
					{ 'Content-Type': 'application/json' },
					JSON.stringify({data: {token: 'xyz'}, status: 'success'})
				);

				//Password protection should be unchecked and password field not visible
				expect($('#dropdown [name=showPassword]').prop('checked')).toEqual(false);
				expect($('#dropdown #linkPass').is(":visible")).toEqual(false);

				// Toggle and set password
				$('#dropdown [name=showPassword]').click();
				$('#dropdown #linkPassText').val('foo');
				$('#dropdown #linkPassText').trigger(new $.Event('keyup', {keyCode: 13}));
				fakeServer.requests[1].respond(
					200,
					{ 'Content-Type': 'application/json' },
					JSON.stringify({data: {token: 'xyz2'}, status: 'success'})
				);

				// Unshare
				$('#dropdown [name=linkCheckbox]').click();
				fakeServer.requests[2].respond(
					200,
					{ 'Content-Type': 'application/json' },
					JSON.stringify({status: 'success'})
				);

				// Toggle share again
				$('#dropdown [name=linkCheckbox]').click();
				fakeServer.requests[3].respond(
					200,
					{ 'Content-Type': 'application/json' },
					JSON.stringify({data: {token: 'xyz3'}, status: 'success'})
				);


				// Password checkbox should be unchecked
				expect($('#dropdown [name=showPassword]').prop('checked')).toEqual(false);
				expect($('#dropdown #linkPass').is(":visible")).toEqual(false);
			});
			it('reset expiration on toggle of share', function() {
				$('#allowShareWithLink').val('yes');
				OC.Share.showDropDown(
					'file',
					123,
					$container,
					true,
					31,
					'shared_file_name.txt'
				);
				$('#dropdown [name=linkCheckbox]').click();
				fakeServer.requests[0].respond(
					200,
					{ 'Content-Type': 'application/json' },
					JSON.stringify({data: {token: 'xyz'}, status: 'success'})
				);

				//Expiration should be unchecked and expiration field not visible
				expect($('#dropdown [name=expirationCheckbox]').prop('checked')).toEqual(false);
				expect($('#dropdown #expirationDate').is(":visible")).toEqual(false);

				// Toggle and set password
				$('#dropdown [name=expirationCheckbox]').click();
				d = new Date();
				d.setDate(d.getDate() + 1);
				date=d.getDate() + '-' + (d.getMonth()+1) + '-' + d.getFullYear();
				$('#dropdown #expirationDate').val(date);
				$('#dropdown #expirationDate').change();
				fakeServer.requests[1].respond(
					200,
					{ 'Content-Type': 'application/json' },
					JSON.stringify({data: {token: 'xyz2'}, status: 'success'})
				);

				// Unshare
				$('#dropdown [name=linkCheckbox]').click();
				fakeServer.requests[2].respond(
					200,
					{ 'Content-Type': 'application/json' },
					JSON.stringify({status: 'success'})
				);

				// Toggle share again
				$('#dropdown [name=linkCheckbox]').click();
				fakeServer.requests[3].respond(
					200,
					{ 'Content-Type': 'application/json' },
					JSON.stringify({data: {token: 'xyz3'}, status: 'success'})
				);

				// Recheck expire visibility
				expect($('#dropdown [name=expirationCheckbox]').prop('checked')).toEqual(false);
				expect($('#dropdown #expirationDate').is(":visible")).toEqual(false);
			});
			it('shows populated link share when a link share exists', function() {
				loadItemStub.returns({
					reshare: [],
					/* jshint camelcase: false */
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
				OC.Share.showDropDown(
					'file',
					123,
					$container,
					true,
					31,
					'folder'
				);
				expect($('#dropdown #linkCheckbox').prop('checked')).toEqual(true);
				// this is how the OC.Share class does it...
				var link = parent.location.protocol + '//' + location.host +
					OC.generateUrl('/s/') + 'tehtoken';
				expect($('#dropdown #linkText').val()).toEqual(link);
			});
			it('does not show populated link share when a link share exists for a different file', function() {
				loadItemStub.returns({
					reshare: [],
					/* jshint camelcase: false */
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
				OC.Share.showDropDown(
					'file',
					456, // another file
					$container,
					true,
					31,
					'folder'
				);
				expect($('#dropdown #linkCheckbox').prop('checked')).toEqual(false);
			});
			it('shows correct link share when a nest link share exists along with parent one', function() {
				loadItemStub.returns({
					reshare: [],
					/* jshint camelcase: false */
					shares: [{
						displayname_owner: 'root',
						expiration: null,
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
						expiration: null,
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

				// parent one
				OC.Share.showDropDown(
					'folder',
					123,
					$container,
					true,
					31,
					'folder'
				);
				expect($('#dropdown #linkCheckbox').prop('checked')).toEqual(true);
				// this is how the OC.Share class does it...
				var link = parent.location.protocol + '//' + location.host +
					OC.generateUrl('/s/') + 'tehtoken';
				expect($('#dropdown #linkText').val()).toEqual(link);

				// nested one
				OC.Share.showDropDown(
					'file',
					456,
					$container,
					true,
					31,
					'file_in_folder.txt'
				);
				expect($('#dropdown #linkCheckbox').prop('checked')).toEqual(true);
				// this is how the OC.Share class does it...
				link = parent.location.protocol + '//' + location.host +
					OC.generateUrl('/s/') + 'anothertoken';
				expect($('#dropdown #linkText').val()).toEqual(link);
			});
			describe('expiration date', function() {
				var shareData;
				var shareItem;
				var clock;
				var expectedMinDate;

				function showDropDown() {
					OC.Share.showDropDown(
						'file',
						123,
						$container,
						true,
						31,
						'folder'
					);
				}

				beforeEach(function() {
					// pick a fake date
					clock = sinon.useFakeTimers(new Date(2014, 0, 20, 14, 0, 0).getTime());
					expectedMinDate = new Date(2014, 0, 21, 14, 0, 0);
					shareItem = {
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
					};
					shareData = {
						reshare: [],
						shares: []
					};
					loadItemStub.returns(shareData);
					oc_appconfig.core.defaultExpireDate = 7;
					oc_appconfig.core.enforcePasswordForPublicLink = false;
					oc_appconfig.core.defaultExpireDateEnabled = false;
					oc_appconfig.core.defaultExpireDateEnforced = false;
				});
				afterEach(function() {
					clock.restore();
				});

				it('does not check expiration date checkbox when no date was set', function() {
					shareItem.expiration = null;
					shareData.shares.push(shareItem);
					showDropDown();
					expect($('#dropdown [name=expirationCheckbox]').prop('checked')).toEqual(false);
					expect($('#dropdown #expirationDate').val()).toEqual('');
				});
				it('does not check expiration date checkbox for new share', function() {
					showDropDown();
					expect($('#dropdown [name=expirationCheckbox]').prop('checked')).toEqual(false);
					expect($('#dropdown #expirationDate').val()).toEqual('');
				});
				it('checks expiration date checkbox and populates field when expiration date was set', function() {
					shareItem.expiration = 1234;
					shareData.shares.push(shareItem);
					showDropDown();
					expect($('#dropdown [name=expirationCheckbox]').prop('checked')).toEqual(true);
					expect($('#dropdown #expirationDate').val()).toEqual('1234');
				});
				it('sets default date when default date setting is enabled', function() {
					/* jshint camelcase:false */
					oc_appconfig.core.defaultExpireDateEnabled = true;
					showDropDown();
					$('#dropdown [name=linkCheckbox]').click();
					// enabled by default
					expect($('#dropdown [name=expirationCheckbox]').prop('checked')).toEqual(true);
					// TODO: those zeros must go...
					expect($('#dropdown #expirationDate').val()).toEqual('2014-1-27 00:00:00');

					// disabling is allowed
					$('#dropdown [name=expirationCheckbox]').click();
					expect($('#dropdown [name=expirationCheckbox]').prop('checked')).toEqual(false);
				});
				it('enforces default date when enforced date setting is enabled', function() {
					/* jshint camelcase:false */
					oc_appconfig.core.defaultExpireDateEnabled = true;
					oc_appconfig.core.defaultExpireDateEnforced = true;
					showDropDown();
					$('#dropdown [name=linkCheckbox]').click();
					expect($('#dropdown [name=expirationCheckbox]').prop('checked')).toEqual(true);
					// TODO: those zeros must go...
					expect($('#dropdown #expirationDate').val()).toEqual('2014-1-27 00:00:00');

					// disabling is not allowed
					expect($('#dropdown [name=expirationCheckbox]').prop('disabled')).toEqual(true);
					$('#dropdown [name=expirationCheckbox]').click();
					expect($('#dropdown [name=expirationCheckbox]').prop('checked')).toEqual(true);
				});
				it('enforces default date when enforced date setting is enabled and password is enforced', function() {
					/* jshint camelcase:false */
					oc_appconfig.core.enforcePasswordForPublicLink = true;
					oc_appconfig.core.defaultExpireDateEnabled = true;
					oc_appconfig.core.defaultExpireDateEnforced = true;
					showDropDown();
					$('#dropdown [name=linkCheckbox]').click();

					//Enter password
					$('#dropdown #linkPassText').val('foo');
					$('#dropdown #linkPassText').trigger(new $.Event('keyup', {keyCode: 13}));
					fakeServer.requests[0].respond(
						200,
						{ 'Content-Type': 'application/json' },
						JSON.stringify({data: {token: 'xyz'}, status: 'success'})
					);

					expect($('#dropdown [name=expirationCheckbox]').prop('checked')).toEqual(true);
					// TODO: those zeros must go...
					expect($('#dropdown #expirationDate').val()).toEqual('2014-1-27 00:00:00');

					// disabling is not allowed
					expect($('#dropdown [name=expirationCheckbox]').prop('disabled')).toEqual(true);
					$('#dropdown [name=expirationCheckbox]').click();
					expect($('#dropdown [name=expirationCheckbox]').prop('checked')).toEqual(true);
				});
				it('displayes email form when sending emails is enabled', function() {
					$('input[name=mailPublicNotificationEnabled]').val('yes');
					showDropDown();
					expect($('#emailPrivateLink').length).toEqual(1);
				});
				it('not renders email form when sending emails is disabled', function() {
					$('input[name=mailPublicNotificationEnabled]').val('no');
					showDropDown();
					expect($('#emailPrivateLink').length).toEqual(0);
				});
				it('sets picker minDate to today and no maxDate by default', function() {
					showDropDown();
					$('#dropdown [name=linkCheckbox]').click();
					$('#dropdown [name=expirationCheckbox]').click();
					expect($.datepicker._defaults.minDate).toEqual(expectedMinDate);
					expect($.datepicker._defaults.maxDate).toEqual(null);
				});
				it('limits the date range to X days after share time when enforced', function() {
					/* jshint camelcase:false */
					oc_appconfig.core.defaultExpireDateEnabled = true;
					oc_appconfig.core.defaultExpireDateEnforced = true;
					showDropDown();
					$('#dropdown [name=linkCheckbox]').click();
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
					showDropDown();
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
				$('#dropdown .unshare:eq(0)').click();
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
				$('#dropdown #linkCheckbox').click();
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
				$('#dropdown #linkCheckbox').click();
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
					expect($('#dropdown #shareWithList').length).toEqual(0);
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
					expect($('#dropdown #shareWithList').length).toEqual(0);
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
					expect($('#dropdown #shareWithList').length).toEqual(0);
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
					expect($('#dropdown #shareWithList').length).toEqual(0);
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
					expect($('#dropdown #shareWithList').length).toEqual(1);
				});
			});
		});
	});
	describe('markFileAsShared', function() {
		var $file;
		var tipsyStub;

		beforeEach(function() {
			tipsyStub = sinon.stub($.fn, 'tipsy');
			$file = $('<tr><td class="filename"><div class="thumbnail"></div><span class="name">File name</span></td></tr>');
			$file.find('.filename').append(
				'<span class="fileactions">' +
				'<a href="#" class="action action-share" data-action="Share">' +
				'<img></img><span> Share</span>' +
				'</a>' +
				'</span>'
			);
		});
		afterEach(function() {
			$file = null;
			tipsyStub.restore();
		});
		describe('displaying the share owner', function() {
			function checkOwner(input, output, title) {
				var $action;

				$file.attr('data-share-owner', input);
				OC.Share.markFileAsShared($file);

				$action = $file.find('.action-share>span');
				expect($action.text()).toEqual(output);
				if (_.isString(title)) {
					expect($action.find('.remoteAddress').attr('title')).toEqual(title);
				} else {
					expect($action.find('.remoteAddress').attr('title')).not.toBeDefined();
				}
				expect(tipsyStub.calledOnce).toEqual(true);
				tipsyStub.reset();
			}

			it('displays the local share owner as is', function() {
				checkOwner('User One', 'User One', null);
			});
			it('displays the user name part of a remote share owner', function() {
				checkOwner(
					'User One@someserver.com',
					'User One@…',
					'User One@someserver.com'
				);
				checkOwner(
					'User One@someserver.com/',
					'User One@…',
					'User One@someserver.com'
				);
				checkOwner(
					'User One@someserver.com/root/of/owncloud',
					'User One@…',
					'User One@someserver.com'
				);
			});
			it('displays the user name part with domain of a remote share owner', function() {
				checkOwner(
					'User One@example.com@someserver.com',
					'User One@example.com',
					'User One@example.com@someserver.com'
				);
				checkOwner(
					'User One@example.com@someserver.com/',
					'User One@example.com',
					'User One@example.com@someserver.com'
				);
				checkOwner(
					'User One@example.com@someserver.com/root/of/owncloud',
					'User One@example.com',
					'User One@example.com@someserver.com'
				);
			});
		});

		describe('displaying the folder icon', function() {
			function checkIcon(expectedImage) {
				var imageUrl = OC.TestUtil.getImageUrl($file.find('.filename .thumbnail'));
				expectedIcon = OC.imagePath('core', expectedImage);
				expect(imageUrl).toEqual(expectedIcon);
			}

			it('shows a plain folder icon for non-shared folders', function() {
				$file.attr('data-type', 'dir');
				OC.Share.markFileAsShared($file);

				checkIcon('filetypes/folder');
			});
			it('shows a shared folder icon for folders shared with another user', function() {
				$file.attr('data-type', 'dir');
				OC.Share.markFileAsShared($file, true);

				checkIcon('filetypes/folder-shared');
			});
			it('shows a shared folder icon for folders shared with the current user', function() {
				$file.attr('data-type', 'dir');
				$file.attr('data-share-owner', 'someoneelse');
				OC.Share.markFileAsShared($file);

				checkIcon('filetypes/folder-shared');
			});
			it('shows a link folder icon for folders shared with link', function() {
				$file.attr('data-type', 'dir');
				OC.Share.markFileAsShared($file, false, true);

				checkIcon('filetypes/folder-public');
			});
			it('shows a link folder icon for folders shared with both link and another user', function() {
				$file.attr('data-type', 'dir');
				OC.Share.markFileAsShared($file, true, true);

				checkIcon('filetypes/folder-public');
			});
			it('shows a link folder icon for folders reshared with link', function() {
				$file.attr('data-type', 'dir');
				$file.attr('data-share-owner', 'someoneelse');
				OC.Share.markFileAsShared($file, false, true);

				checkIcon('filetypes/folder-public');
			});
		});

		describe('displaying the recipoients', function() {
			function checkRecipients(input, output, title) {
				var $action;

				$file.attr('data-share-recipients', input);
				OC.Share.markFileAsShared($file, true);

				$action = $file.find('.action-share>span');
				expect($action.text()).toEqual(output);
				if (_.isString(title)) {
					expect($action.find('.remoteAddress').attr('title')).toEqual(title);
				} else if (_.isArray(title)) {
					var tooltips = $action.find('.remoteAddress');
					expect(tooltips.length).toEqual(title.length);

					tooltips.each(function(i) {
						expect($(this).attr('title')).toEqual(title[i]);
					});
				} else {
						expect($action.find('.remoteAddress').attr('title')).not.toBeDefined();
				}
				expect(tipsyStub.calledOnce).toEqual(true);
				tipsyStub.reset();
			}

			it('displays the local share owner as is', function() {
				checkRecipients('User One', 'Shared with User One', null);
			});
			it('displays the user name part of a remote recipient', function() {
				checkRecipients(
					'User One@someserver.com',
					'Shared with User One@…',
					'User One@someserver.com'
				);
				checkRecipients(
					'User One@someserver.com/',
					'Shared with User One@…',
					'User One@someserver.com'
				);
				checkRecipients(
					'User One@someserver.com/root/of/owncloud',
					'Shared with User One@…',
					'User One@someserver.com'
				);
			});
			it('displays the user name part with domain of a remote share owner', function() {
				checkRecipients(
					'User One@example.com@someserver.com',
					'Shared with User One@example.com',
					'User One@example.com@someserver.com'
				);
				checkRecipients(
					'User One@example.com@someserver.com/',
					'Shared with User One@example.com',
					'User One@example.com@someserver.com'
				);
				checkRecipients(
					'User One@example.com@someserver.com/root/of/owncloud',
					'Shared with User One@example.com',
					'User One@example.com@someserver.com'
				);
			});
			it('display multiple remote recipients', function() {
				checkRecipients(
					'One@someserver.com, two@otherserver.com',
					'Shared with One@…, two@…',
					['One@someserver.com', 'two@otherserver.com']
				);
				checkRecipients(
					'One@someserver.com/, two@otherserver.com',
					'Shared with One@…, two@…',
					['One@someserver.com', 'two@otherserver.com']
				);
				checkRecipients(
					'One@someserver.com/root/of/owncloud, two@otherserver.com',
					'Shared with One@…, two@…',
					['One@someserver.com', 'two@otherserver.com']
				);
			});
			it('display mixed recipients', function() {
				checkRecipients(
					'One, two@otherserver.com',
					'Shared with One, two@…',
					['two@otherserver.com']
				);
			});
		});
	});
});

