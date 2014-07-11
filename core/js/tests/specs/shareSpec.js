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

		beforeEach(function() {
			$('#testArea').append($('<div id="shareContainer"></div>'));
			// horrible parameters
			$('#testArea').append('<input id="allowShareWithLink" type="hidden" value="yes">');
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
				var $el = $('<div></div>').data('ui-autocomplete', {});
				return $el;
			});
		});
		afterEach(function() {
			/* jshint camelcase:false */
			oc_appconfig.core = oldAppConfig;
			loadItemStub.restore();

			autocompleteStub.restore();
		});
		it('calls loadItem with the correct arguments', function() {
			OC.Share.showDropDown(
				'file',
			   	123,
			   	$container,
				'http://localhost/dummylink',
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
				'http://localhost/dummylink',
				31,
				'shared_file_name.txt'
			);
			$el = $container.find('#dropdown');
			expect($el.length).toEqual(1);
			expect($el.attr('data-item-type')).toEqual('file');
			expect($el.attr('data-item-source')).toEqual('123');
			// TODO: expect that other parts are rendered correctly
		});
		it('shows default expiration date when set', function() {
			oc_appconfig.core.defaultExpireDateEnabled = "yes";
			oc_appconfig.core.defaultExpireDate = '';
			// TODO: expect that default date was set
		});
		it('shows default expiration date is set but disabled', function() {
			oc_appconfig.core.defaultExpireDateEnabled = "no";
			oc_appconfig.core.defaultExpireDate = '';
			// TODO: expect that default date was NOT set
		});
		describe('Share with link', function() {
			// TODO: test ajax calls
			// TODO: test password field visibility (whenever enforced or not)
			it('shows share with link checkbox when allowed', function() {
				$('#allowShareWithLink').val('yes');
				OC.Share.showDropDown(
					'file',
					123,
					$container,
					'http://localhost/dummylink',
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
					'http://localhost/dummylink',
					31,
					'shared_file_name.txt'
				);
				expect($('#dropdown #linkCheckbox').length).toEqual(0);
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
					'http://localhost/dummylink',
					31,
					'folder'
				);
				expect($('#dropdown #linkCheckbox').prop('checked')).toEqual(true);
				// this is how the OC.Share class does it...
				var link = parent.location.protocol + '//' + location.host +
					OC.linkTo('', 'public.php')+'?service=files&t=tehtoken';
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
					'http://localhost/dummylink',
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
					'http://localhost/dummylink',
					31,
					'folder'
				);
				expect($('#dropdown #linkCheckbox').prop('checked')).toEqual(true);
				// this is how the OC.Share class does it...
				var link = parent.location.protocol + '//' + location.host +
					OC.linkTo('', 'public.php')+'?service=files&t=tehtoken';
				expect($('#dropdown #linkText').val()).toEqual(link);

				// nested one
				OC.Share.showDropDown(
					'file',
					456,
					$container,
					'http://localhost/dummylink',
					31,
					'file_in_folder.txt'
				);
				expect($('#dropdown #linkCheckbox').prop('checked')).toEqual(true);
				// this is how the OC.Share class does it...
				link = parent.location.protocol + '//' + location.host +
					OC.linkTo('', 'public.php')+'?service=files&t=anothertoken';
				expect($('#dropdown #linkText').val()).toEqual(link);
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
					'http://localhost/dummylink',
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
	});
	describe('markFileAsShared', function() {
		var $file;
		var tipsyStub;

		beforeEach(function() {
			tipsyStub = sinon.stub($.fn, 'tipsy');
			$file = $('<tr><td class="filename">File name</td></tr>');
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
					expect($action.find('.remoteOwner').attr('title')).toEqual(title);
				} else {
					expect($action.find('.remoteOwner').attr('title')).not.toBeDefined();
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
					'User One',
					'User One@someserver.com'
				);
				checkOwner(
					'User One@someserver.com/',
					'User One',
					'User One@someserver.com'
				);
				checkOwner(
					'User One@someserver.com/root/of/owncloud',
					'User One',
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

		// TODO: add unit tests for folder icons
		// TODO: add unit tests for share recipients
	});
});

